<?php
/**
 * Custom post type for tracked links
 */
class WP_Link_Tracker_Post_Type {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
    }

    /**
     * Register the custom post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Tracked Links', 'post type general name', 'wp-link-tracker'),
            'singular_name'      => _x('Tracked Link', 'post type singular name', 'wp-link-tracker'),
            'menu_name'          => _x('Link Tracker', 'admin menu', 'wp-link-tracker'),
            'name_admin_bar'     => _x('Tracked Link', 'add new on admin bar', 'wp-link-tracker'),
            'add_new'            => _x('Add New', 'tracked link', 'wp-link-tracker'),
            'add_new_item'       => __('Add New Tracked Link', 'wp-link-tracker'),
            'new_item'           => __('New Tracked Link', 'wp-link-tracker'),
            'edit_item'          => __('Edit Tracked Link', 'wp-link-tracker'),
            'view_item'          => __('View Tracked Link', 'wp-link-tracker'),
            'all_items'          => __('All Tracked Links', 'wp-link-tracker'),
            'search_items'       => __('Search Tracked Links', 'wp-link-tracker'),
            'parent_item_colon'  => __('Parent Tracked Links:', 'wp-link-tracker'),
            'not_found'          => __('No tracked links found.', 'wp-link-tracker'),
            'not_found_in_trash' => __('No tracked links found in Trash.', 'wp-link-tracker')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Tracked links for the WP Link Tracker plugin.', 'wp-link-tracker'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'tracked-link'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 30,
            'menu_icon'          => 'dashicons-admin-links',
            'supports'           => array('title')
        );

        register_post_type('wplinktracker', $args);
    }

    /**
     * Register taxonomies for the post type.
     */
    public function register_taxonomies() {
        // Register campaign taxonomy
        $labels = array(
            'name'              => _x('Campaigns', 'taxonomy general name', 'wp-link-tracker'),
            'singular_name'     => _x('Campaign', 'taxonomy singular name', 'wp-link-tracker'),
            'search_items'      => __('Search Campaigns', 'wp-link-tracker'),
            'all_items'         => __('All Campaigns', 'wp-link-tracker'),
            'parent_item'       => __('Parent Campaign', 'wp-link-tracker'),
            'parent_item_colon' => __('Parent Campaign:', 'wp-link-tracker'),
            'edit_item'         => __('Edit Campaign', 'wp-link-tracker'),
            'update_item'       => __('Update Campaign', 'wp-link-tracker'),
            'add_new_item'      => __('Add New Campaign', 'wp-link-tracker'),
            'new_item_name'     => __('New Campaign Name', 'wp-link-tracker'),
            'menu_name'         => __('Campaigns', 'wp-link-tracker'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'campaign'),
        );

        register_taxonomy('wplinktracker_campaign', array('wplinktracker'), $args);
    }

    /**
     * Add meta boxes for the custom post type.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wplinktracker_link_details',
            __('Link Details', 'wp-link-tracker'),
            array($this, 'render_link_details_meta_box'),
            'wplinktracker',
            'normal',
            'high'
        );

        add_meta_box(
            'wplinktracker_link_stats',
            __('Link Statistics', 'wp-link-tracker'),
            array($this, 'render_link_stats_meta_box'),
            'wplinktracker',
            'normal',
            'default'
        );
    }

    /**
     * Render the link details meta box.
     */
    public function render_link_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('wplinktracker_save_meta_box_data', 'wplinktracker_meta_box_nonce');

        // Get existing values
        $destination_url = get_post_meta($post->ID, '_wplinktracker_destination_url', true);
        $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
        $short_url = home_url('go/' . $short_code);

        // Output fields
        ?>
        <p>
            <label for="wplinktracker_destination_url"><?php _e('Destination URL:', 'wp-link-tracker'); ?></label>
            <input type="url" id="wplinktracker_destination_url" name="wplinktracker_destination_url" value="<?php echo esc_url($destination_url); ?>" style="width: 100%;" required />
        </p>
        <?php if (!empty($short_code)) : ?>
        <p>
            <label><?php _e('Short URL:', 'wp-link-tracker'); ?></label>
            <input type="text" value="<?php echo esc_url($short_url); ?>" readonly style="width: 100%;" />
            <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_url($short_url); ?>')">Copy</button>
        </p>
        <p>
            <label><?php _e('Shortcode:', 'wp-link-tracker'); ?></label>
            <input type="text" value='[tracked_link id="<?php echo $post->ID; ?>"]' readonly style="width: 100%;" />
            <button type="button" class="button" onclick="navigator.clipboard.writeText('[tracked_link id=\'<?php echo $post->ID; ?>\']')">Copy</button>
        </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render the link stats meta box.
     */
    public function render_link_stats_meta_box($post) {
        ?>
        <div id="wplinktracker-stats-container">
            <div class="wplinktracker-stats-summary">
                <div class="wplinktracker-stat-box">
                    <h3><?php _e('Total Clicks', 'wp-link-tracker'); ?></h3>
                    <div class="wplinktracker-stat-value" id="wplinktracker-total-clicks">
                        <?php echo intval(get_post_meta($post->ID, '_wplinktracker_total_clicks', true)); ?>
                    </div>
                </div>
                <div class="wplinktracker-stat-box">
                    <h3><?php _e('Unique Visitors', 'wp-link-tracker'); ?></h3>
                    <div class="wplinktracker-stat-value" id="wplinktracker-unique-visitors">
                        <?php echo intval(get_post_meta($post->ID, '_wplinktracker_unique_visitors', true)); ?>
                    </div>
                </div>
                <div class="wplinktracker-stat-box">
                    <h3><?php _e('Conversion Rate', 'wp-link-tracker'); ?></h3>
                    <div class="wplinktracker-stat-value" id="wplinktracker-conversion-rate">
                        <?php 
                        $total_clicks = intval(get_post_meta($post->ID, '_wplinktracker_total_clicks', true));
                        $unique_visitors = intval(get_post_meta($post->ID, '_wplinktracker_unique_visitors', true));
                        echo ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
                        ?>
                    </div>
                </div>
            </div>
            <div class="wplinktracker-charts">
                <div class="wplinktracker-chart">
                    <h3><?php _e('Clicks Over Time', 'wp-link-tracker'); ?></h3>
                    <div id="wplinktracker-clicks-chart" style="height: 250px;">
                        <p><?php _e('Loading chart...', 'wp-link-tracker'); ?></p>
                    </div>
                </div>
            </div>
            <div class="wplinktracker-tables">
                <div class="wplinktracker-table">
                    <h3><?php _e('Top Referrers', 'wp-link-tracker'); ?></h3>
                    <div id="wplinktracker-referrers-table">
                        <p><?php _e('Loading data...', 'wp-link-tracker'); ?></p>
                    </div>
                </div>
                <div class="wplinktracker-table">
                    <h3><?php _e('Devices', 'wp-link-tracker'); ?></h3>
                    <div id="wplinktracker-devices-table">
                        <p><?php _e('Loading data...', 'wp-link-tracker'); ?></p>
                    </div>
                </div>
            </div>
            <input type="hidden" id="wplinktracker-post-id" value="<?php echo $post->ID; ?>" />
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Load stats via AJAX
                function loadStats() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_link_tracker_get_stats',
                            post_id: $('#wplinktracker-post-id').val(),
                            nonce: '<?php echo wp_create_nonce('wp_link_tracker_stats_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update stats
                                $('#wplinktracker-total-clicks').text(response.data.total_clicks);
                                $('#wplinktracker-unique-visitors').text(response.data.unique_visitors);
                                $('#wplinktracker-conversion-rate').text(response.data.conversion_rate);
                                
                                // Update tables
                                $('#wplinktracker-referrers-table').html(response.data.referrers_table);
                                $('#wplinktracker-devices-table').html(response.data.devices_table);
                                
                                // Update chart (assuming we're using a charting library)
                                if (typeof drawClicksChart === 'function') {
                                    drawClicksChart(response.data.clicks_data);
                                }
                            }
                        }
                    });
                }
                
                // Load stats on page load
                loadStats();
                
                // Refresh stats every 60 seconds
                setInterval(loadStats, 60000);
            });
        </script>
        <style>
            .wplinktracker-stats-summary {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
            }
            .wplinktracker-stat-box {
                flex: 1;
                margin-right: 15px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-align: center;
            }
            .wplinktracker-stat-box:last-child {
                margin-right: 0;
            }
            .wplinktracker-stat-value {
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }
            .wplinktracker-charts, .wplinktracker-tables {
                display: flex;
                margin-bottom: 20px;
            }
            .wplinktracker-chart, .wplinktracker-table {
                flex: 1;
                margin-right: 15px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .wplinktracker-chart:last-child, .wplinktracker-table:last-child {
                margin-right: 0;
            }
        </style>
        <?php
    }

    /**
     * Save meta box data.
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['wplinktracker_meta_box_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['wplinktracker_meta_box_nonce'], 'wplinktracker_save_meta_box_data')) {
            return;
        }

        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (isset($_POST['post_type']) && 'wplinktracker' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Save the destination URL
        if (isset($_POST['wplinktracker_destination_url'])) {
            $destination_url = esc_url_raw($_POST['wplinktracker_destination_url']);
            update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        }

        // Generate short code if it doesn't exist
        $short_code = get_post_meta($post_id, '_wplinktracker_short_code', true);
        if (empty($short_code)) {
            $short_code = $this->generate_short_code();
            update_post_meta($post_id, '_wplinktracker_short_code', $short_code);
        }
    }

    /**
     * Generate a unique short code.
     */
    private function generate_short_code($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $short_code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $short_code .= $characters[rand(0, $charactersLength - 1)];
        }
        
        // Check if the code already exists
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        // If the code exists, generate a new one
        if ($query->have_posts()) {
            return $this->generate_short_code($length);
        }
        
        return $short_code;
    }
}
