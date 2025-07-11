<?php
/**
 * Handles link redirections
 */
class WP_Link_Tracker_Redirect {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Ensure rewrite rules are flushed when needed
        add_action('wp_loaded', array($this, 'maybe_flush_rewrite_rules'));
    }

    /**
     * Add custom query vars.
     */
    public function add_query_vars($vars) {
        $vars[] = 'wplinktracker_shortcode';
        return $vars;
    }

    /**
     * Add rewrite rules for short links.
     */
    public function add_rewrite_rules() {
        // Add rewrite rule for /go/shortcode format
        add_rewrite_rule(
            '^go/([a-zA-Z0-9]+)/?$',
            'index.php?wplinktracker_shortcode=$matches[1]',
            'top'
        );
        
        // Add the query var
        add_rewrite_tag('%wplinktracker_shortcode%', '([a-zA-Z0-9]+)');
    }

    /**
     * Maybe flush rewrite rules if they haven't been set up properly.
     */
    public function maybe_flush_rewrite_rules() {
        // Check if our rewrite rule exists
        $rules = get_option('rewrite_rules');
        
        if (!$rules || !isset($rules['^go/([a-zA-Z0-9]+)/?$'])) {
            // Our rule doesn't exist, flush rewrite rules
            flush_rewrite_rules();
        }
    }

    /**
     * Handle the redirect for short links.
     */
    public function handle_redirect() {
        global $wp_query;
        
        // Check if this is a tracked link request
        $short_code = get_query_var('wplinktracker_shortcode');
        
        if (empty($short_code)) {
            return;
        }
        
        // Find the post with this short code
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'publish'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $destination_url = get_post_meta($post_id, '_wplinktracker_destination_url', true);
            
            // Reset post data
            wp_reset_postdata();
            
            if (!empty($destination_url)) {
                // Validate the destination URL
                if (!filter_var($destination_url, FILTER_VALIDATE_URL)) {
                    // If it's not a valid URL, try adding http://
                    if (!preg_match('/^https?:\/\//', $destination_url)) {
                        $destination_url = 'http://' . $destination_url;
                    }
                }
                
                // Track this click
                $this->track_click($post_id);
                
                // Add UTM parameters if they exist in the request
                $utm_params = array();
                foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content') as $param) {
                    if (isset($_GET[$param])) {
                        $utm_params[$param] = sanitize_text_field($_GET[$param]);
                    }
                }
                
                if (!empty($utm_params)) {
                    $destination_url = add_query_arg($utm_params, $destination_url);
                }
                
                // Perform the redirect
                wp_redirect($destination_url, 302);
                exit;
            }
        }
        
        // Reset post data in case of error
        wp_reset_postdata();
        
        // If we get here, the short code was not found or the destination URL is empty
        // Redirect to home page with a 404 status
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit;
    }

    /**
     * Track a click on a link.
     */
    private function track_click($post_id) {
        global $wpdb;
        
        // Get user information
        $ip_address = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Parse user agent to get device information
        $device_type = $this->get_device_type($user_agent);
        $browser = $this->get_browser($user_agent);
        $os = $this->get_operating_system($user_agent);
        
        // Get visitor ID (hash of IP and user agent)
        $visitor_id = md5($ip_address . $user_agent);
        
        // Insert click data into the database
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'visitor_id' => $visitor_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'referrer' => $referrer,
                'device_type' => $device_type,
                'browser' => $browser,
                'os' => $os,
                'click_time' => current_time('mysql'),
                'utm_source' => isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '',
                'utm_medium' => isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : '',
                'utm_campaign' => isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '',
                'utm_term' => isset($_GET['utm_term']) ? sanitize_text_field($_GET['utm_term']) : '',
                'utm_content' => isset($_GET['utm_content']) ? sanitize_text_field($_GET['utm_content']) : '',
            ),
            array(
                '%d', // post_id
                '%s', // visitor_id
                '%s', // ip_address
                '%s', // user_agent
                '%s', // referrer
                '%s', // device_type
                '%s', // browser
                '%s', // os
                '%s', // click_time
                '%s', // utm_source
                '%s', // utm_medium
                '%s', // utm_campaign
                '%s', // utm_term
                '%s'  // utm_content
            )
        );
        
        // Log any database errors
        if ($result === false) {
            error_log('WP Link Tracker: Failed to insert click data - ' . $wpdb->last_error);
        }
        
        // Update click counts
        $total_clicks = (int) get_post_meta($post_id, '_wplinktracker_total_clicks', true);
        update_post_meta($post_id, '_wplinktracker_total_clicks', $total_clicks + 1);
        
        // Count unique visitors
        $unique_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM $table_name WHERE post_id = %d",
            $post_id
        ));
        
        if ($unique_visitors !== null) {
            update_post_meta($post_id, '_wplinktracker_unique_visitors', $unique_visitors);
        }
    }

    /**
     * Get the client IP address.
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR
        if (isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return '0.0.0.0';
    }

    /**
     * Get the device type from user agent.
     */
    private function get_device_type($user_agent) {
        if (empty($user_agent)) {
            return 'Unknown';
        }
        
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent)) {
            return 'Tablet';
        }
        
        if (preg_match('/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/', $user_agent)) {
            return 'Mobile';
        }
        
        return 'Desktop';
    }

    /**
     * Get the browser from user agent.
     */
    private function get_browser($user_agent) {
        if (empty($user_agent)) {
            return 'Unknown';
        }
        
        if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
            return 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            return 'Firefox';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            if (preg_match('/Edge/i', $user_agent)) {
                return 'Edge';
            } elseif (preg_match('/Edg/i', $user_agent)) {
                return 'Edge';
            } elseif (preg_match('/OPR/i', $user_agent)) {
                return 'Opera';
            } else {
                return 'Chrome';
            }
        } elseif (preg_match('/Safari/i', $user_agent)) {
            return 'Safari';
        } elseif (preg_match('/Opera/i', $user_agent)) {
            return 'Opera';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get the operating system from user agent.
     */
    private function get_operating_system($user_agent) {
        if (empty($user_agent)) {
            return 'Unknown';
        }
        
        if (preg_match('/windows|win32|win64/i', $user_agent)) {
            return 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            return 'Mac OS';
        } elseif (preg_match('/linux/i', $user_agent)) {
            return 'Linux';
        } elseif (preg_match('/android/i', $user_agent)) {
            return 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
            return 'iOS';
        } else {
            return 'Unknown';
        }
    }
}
