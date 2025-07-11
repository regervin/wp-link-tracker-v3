<?php
/**
 * Handles statistics and reporting
 */
class WP_Link_Tracker_Stats {
    /**
     * Initialize the class.
     */
    public function init() {
        // No initialization needed for now
    }

    /**
     * Get statistics for a link via AJAX.
     */
    public function get_stats_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_stats_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check post ID
        if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Check if the post exists and is a tracked link
        $post = get_post($post_id);
        if (!$post || 'wplinktracker' !== $post->post_type) {
            wp_send_json_error('Invalid tracked link');
        }
        
        // Get statistics
        $stats = $this->get_link_stats($post_id);
        
        wp_send_json_success($stats);
    }

    /**
     * Get statistics for a link.
     */
    public function get_link_stats($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get total clicks
        $total_clicks = (int) get_post_meta($post_id, '_wplinktracker_total_clicks', true);
        
        // Get unique visitors
        $unique_visitors = (int) get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
        
        // Calculate conversion rate
        $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
        
        // Get clicks over time (last 30 days)
        $clicks_data = $this->get_clicks_over_time($post_id, 30);
        
        // Get top referrers
        $referrers = $this->get_top_referrers($post_id);
        $referrers_table = $this->build_referrers_table($referrers);
        
        // Get device breakdown
        $devices = $this->get_device_breakdown($post_id);
        $devices_table = $this->build_devices_table($devices);
        
        return array(
            'total_clicks' => $total_clicks,
            'unique_visitors' => $unique_visitors,
            'conversion_rate' => $conversion_rate,
            'clicks_data' => $clicks_data,
            'referrers_table' => $referrers_table,
            'devices_table' => $devices_table
        );
    }

    /**
     * Get clicks over time.
     */
    private function get_clicks_over_time($post_id, $days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(click_time) as date, COUNT(*) as clicks
            FROM $table_name
            WHERE post_id = %d
            AND click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(click_time)
            ORDER BY date ASC",
            $post_id, $days
        ));
        
        // Fill in missing dates
        $data = array();
        $current_date = new DateTime();
        $current_date->modify('-' . $days . ' days');
        
        for ($i = 0; $i < $days; $i++) {
            $date = $current_date->format('Y-m-d');
            $data[$date] = 0;
            $current_date->modify('+1 day');
        }
        
        foreach ($results as $row) {
            $data[$row->date] = (int) $row->clicks;
        }
        
        // Format for chart
        $formatted_data = array();
        foreach ($data as $date => $clicks) {
            $formatted_data[] = array(
                'date' => $date,
                'clicks' => $clicks
            );
        }
        
        return $formatted_data;
    }

    /**
     * Get top referrers.
     */
    private function get_top_referrers($post_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d AND referrer != ''
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT %d",
            $post_id, $limit
        ));
        
        return $results;
    }

    /**
     * Get device breakdown.
     */
    private function get_device_breakdown($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get device types
        $device_types = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d
            GROUP BY device_type
            ORDER BY count DESC",
            $post_id
        ));
        
        // Get browsers
        $browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 5",
            $post_id
        ));
        
        // Get operating systems
        $operating_systems = $wpdb->get_results($wpdb->prepare(
            "SELECT os, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d
            GROUP BY os
            ORDER BY count DESC
            LIMIT 5",
            $post_id
        ));
        
        return array(
            'device_types' => $device_types,
            'browsers' => $browsers,
            'operating_systems' => $operating_systems
        );
    }

    /**
     * Build referrers table HTML.
     */
    private function build_referrers_table($referrers) {
        if (empty($referrers)) {
            return '<p>' . __('No referrer data available.', 'wp-link-tracker') . '</p>';
        }
        
        $html = '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Referrer', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        foreach ($referrers as $referrer) {
            $display_referrer = empty($referrer->referrer) ? __('Direct', 'wp-link-tracker') : esc_html(parse_url($referrer->referrer, PHP_URL_HOST));
            
            $html .= '<tr>';
            $html .= '<td>' . $display_referrer . '</td>';
            $html .= '<td>' . esc_html($referrer->count) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * Build devices table HTML.
     */
    private function build_devices_table($devices) {
        $html = '<div class="wplinktracker-devices-grid">';
        
        // Device types
        $html .= '<div class="wplinktracker-device-section">';
        $html .= '<h4>' . __('Device Types', 'wp-link-tracker') . '</h4>';
        
        if (empty($devices['device_types'])) {
            $html .= '<p>' . __('No device data available.', 'wp-link-tracker') . '</p>';
        } else {
            $html .= '<div style="max-height: 300px; overflow-y: auto;">';
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('Device', 'wp-link-tracker') . '</th>';
            $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($devices['device_types'] as $device) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($device->device_type) . '</td>';
                $html .= '<td>' . esc_html($device->count) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Browsers
        $html .= '<div class="wplinktracker-device-section">';
        $html .= '<h4>' . __('Browsers', 'wp-link-tracker') . '</h4>';
        
        if (empty($devices['browsers'])) {
            $html .= '<p>' . __('No browser data available.', 'wp-link-tracker') . '</p>';
        } else {
            $html .= '<div style="max-height: 300px; overflow-y: auto;">';
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('Browser', 'wp-link-tracker') . '</th>';
            $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($devices['browsers'] as $browser) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($browser->browser) . '</td>';
                $html .= '<td>' . esc_html($browser->count) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Operating systems
        $html .= '<div class="wplinktracker-device-section">';
        $html .= '<h4>' . __('Operating Systems', 'wp-link-tracker') . '</h4>';
        
        if (empty($devices['operating_systems'])) {
            $html .= '<p>' . __('No OS data available.', 'wp-link-tracker') . '</p>';
        } else {
            $html .= '<div style="max-height: 300px; overflow-y: auto;">';
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('OS', 'wp-link-tracker') . '</th>';
            $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($devices['operating_systems'] as $os) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($os->os) . '</td>';
                $html .= '<td>' . esc_html($os->count) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '<style>
            .wplinktracker-devices-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                grid-gap: 15px;
            }
            .wplinktracker-device-section {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
            }
            @media (max-width: 782px) {
                .wplinktracker-devices-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>';
        
        return $html;
    }
}
