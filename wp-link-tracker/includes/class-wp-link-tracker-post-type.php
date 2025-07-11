<?php
/**
 * Handles the custom post type for tracked links
 */
class WP_Link_Tracker_Post_Type {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_wplinktracker_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_wplinktracker_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
    }

    /**
     * Register the custom post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Tracked Links', 'Post Type General Name', 'wp-link-tracker'),
            'singular_name'         => _x('Tracked Link', 'Post Type Singular Name', 'wp-link-tracker'),
            'menu_name'             => __('Link Tracker', 'wp-link-tracker'),
            'name_admin_bar'        => __('Tracked Link', 'wp-link-tracker'),
            'archives'              => __('Link Archives', 'wp-link-tracker'),
            'attributes'            => __('Link Attributes', 'wp-link-tracker'),
            'parent_item_colon'     => __('Parent Link:', 'wp-link-tracker'),
            'all_items'             => __('All Links', 'wp-link-tracker'),
            'add_new_item'          => __('Add New Link', 'wp-link-tracker'),
            'add_new'               => __('Add New', 'wp-link-tracker'),
            'new_item'              => __('New Link', 'wp-link-tracker'),
            'edit_item'             => __('Edit Link', 'wp-link-tracker'),
            'update_item'           => __('Update Link', 'wp-link-tracker'),
            'view_item'             => __('View Link', 'wp-link-tracker'),
            'view_items'            => __('View Links', 'wp-link-tracker'),
            'search_items'          => __('Search Links', 'wp-link-tracker'),
            'not_found'             => __('Not found', 'wp-link-tracker'),
            'not_found_in_trash'    => __('Not found in Trash', 'wp-link-tracker'),
            'featured_image'        => __('Featured Image', 'wp-link-tracker'),
            'set_featured_image'    => __('Set featured image', 'wp-link-tracker'),
            'remove_featured_image' => __('Remove featured image', 'wp-link-tracker'),
            'use_featured_image'    => __('Use as featured image', 'wp-link-tracker'),
            'insert_into_item'      => __('Insert into link', 'wp-link-tracker'),
            'uploaded_to_this_item' => __('Uploaded to this link', 'wp-link-tracker'),
            'items_list'            => __('Links list', 'wp-link-tracker'),
            'items_list_navigation' => __('Links list navigation', 'wp-link-tracker'),
            'filter_items_list'     => __('Filter links list', 'wp-link-tracker'),
        );

        $args = array(
            'label'                 => __('Tracked Link', 'wp-link-tracker'),
            'description'           => __('Links that are being tracked', 'wp-link-tracker'),
            'labels'                => $labels,
            'supports'              => array('title'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-admin-links',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => false,
        );

        register_post_type('wplinktracker', $args);
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wplinktracker_link_details',
            __('Link Details', 'wp-link-tracker'),
            array($this, 'link_details_meta_box'),
            'wplinktracker',
            'normal',
            'high'
        );

        add_meta_box(
            'wplinktracker_statistics',
            __('Statistics', 'wp-link-tracker'),
            array($this, 'statistics_meta_box'),
            'wplinktracker',
            'side',
            'default'
        );
    }

    /**
     * Link details meta box.
     */
    public function link_details_meta_box($post) {
        wp_nonce_field('wplinktracker_meta_box', 'wplinktracker_meta_box_nonce');

        $destination_url = get_post_meta($post->ID, '_wplinktracker_destination_url', true);
        $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
        $short_url = $this->get_short_url($short_code);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wplinktracker_destination_url"><?php _e('Destination URL', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="url" id="wplinktracker_destination_url" name="wplinktracker_destination_url" 
                           value="<?php echo esc_attr($destination_url); ?>" class="regular-text" required />
                    <p class="description"><?php _e('The URL where visitors will be redirected.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wplinktracker_short_code"><?php _e('Short Code', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" id="wplinktracker_short_code" name="wplinktracker_short_code" 
                           value="<?php echo esc_attr($short_code); ?>" class="regular-text" 
                           pattern="[a-zA-Z0-9]+" title="<?php _e('Only letters and numbers allowed', 'wp-link-tracker'); ?>" />
                    <p class="description"><?php _e('Leave blank to auto-generate. Only letters and numbers allowed.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
            <?php if (!empty($short_url)): ?>
            <tr>
                <th scope="row">
                    <label><?php _e('Short URL', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" value="<?php echo esc_attr($short_url); ?>" class="regular-text" readonly />
                    <button type="button" class="button" onclick="copyToClipboard('<?php echo esc_js($short_url); ?>')"><?php _e('Copy', 'wp-link-tracker'); ?></button>
                    <p class="description"><?php _e('This is your trackable short URL.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('<?php _e('URL copied to clipboard!', 'wp-link-tracker'); ?>');
            });
        }
        </script>
        <?php
    }

    /**
     * Statistics meta box.
     */
    public function statistics_meta_box($post) {
        $total_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
        $unique_visitors = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);

        ?>
        <p><strong><?php _e('Total Clicks:', 'wp-link-tracker'); ?></strong> <?php echo intval($total_clicks); ?></p>
        <p><strong><?php _e('Unique Visitors:', 'wp-link-tracker'); ?></strong> <?php echo intval($unique_visitors); ?></p>
        
        <?php if ($post->ID): ?>
        <p>
            <a href="<?php echo admin_url('admin.php?page=wp-link-tracker&link_id=' . $post->ID); ?>" class="button">
                <?php _e('View Detailed Stats', 'wp-link-tracker'); ?>
            </a>
        </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Save meta boxes.
     */
    public function save_meta_boxes($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['wplinktracker_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['wplinktracker_meta_box_nonce'], 'wplinktracker_meta_box')) {
            return;
        }

        // Check if user has permissions to save data
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if (get_post_type($post_id) !== 'wplinktracker') {
            return;
        }

        // Save destination URL
        if (isset($_POST['wplinktracker_destination_url'])) {
            $destination_url = sanitize_url($_POST['wplinktracker_destination_url']);
            update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        }

        // Save or generate short code
        $short_code = '';
        if (isset($_POST['wplinktracker_short_code']) && !empty($_POST['wplinktracker_short_code'])) {
            $short_code = sanitize_text_field($_POST['wplinktracker_short_code']);
            // Validate short code format
            if (!preg_match('/^[a-zA-Z0-9]+$/', $short_code)) {
                $short_code = '';
            }
        }

        // If no short code provided or invalid, generate one
        if (empty($short_code)) {
            $short_code = $this->generate_short_code();
        }

        // Make sure short code is unique
        $short_code = $this->ensure_unique_short_code($short_code, $post_id);
        
        update_post_meta($post_id, '_wplinktracker_short_code', $short_code);

        // Initialize click counts if this is a new post
        if (!get_post_meta($post_id, '_wplinktracker_total_clicks', true)) {
            update_post_meta($post_id, '_wplinktracker_total_clicks', 0);
        }
        if (!get_post_meta($post_id, '_wplinktracker_unique_visitors', true)) {
            update_post_meta($post_id, '_wplinktracker_unique_visitors', 0);
        }
    }

    /**
     * Generate a random short code.
     */
    private function generate_short_code($length = 6) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $short_code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $short_code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $short_code;
    }

    /**
     * Ensure the short code is unique.
     */
    private function ensure_unique_short_code($short_code, $current_post_id = 0) {
        $original_short_code = $short_code;
        $counter = 1;
        
        while ($this->short_code_exists($short_code, $current_post_id)) {
            $short_code = $original_short_code . $counter;
            $counter++;
            
            // Prevent infinite loop
            if ($counter > 1000) {
                $short_code = $this->generate_short_code(8);
                break;
            }
        }
        
        return $short_code;
    }

    /**
     * Check if a short code already exists.
     */
    private function short_code_exists($short_code, $exclude_post_id = 0) {
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
            'post_status' => array('publish', 'draft', 'private'),
            'fields' => 'ids'
        );
        
        if ($exclude_post_id > 0) {
            $args['post__not_in'] = array($exclude_post_id);
        }
        
        $query = new WP_Query($args);
        return $query->have_posts();
    }

    /**
     * Get the full short URL.
     */
    private function get_short_url($short_code) {
        if (empty($short_code)) {
            return '';
        }
        
        return home_url('/go/' . $short_code);
    }

    /**
     * Add custom columns to the post list.
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['destination_url'] = __('Destination URL', 'wp-link-tracker');
                $new_columns['short_url'] = __('Short URL', 'wp-link-tracker');
                $new_columns['clicks'] = __('Clicks', 'wp-link-tracker');
                $new_columns['unique_visitors'] = __('Unique Visitors', 'wp-link-tracker');
            }
        }
        
        return $new_columns;
    }

    /**
     * Display custom column content.
     */
    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'destination_url':
                $destination_url = get_post_meta($post_id, '_wplinktracker_destination_url', true);
                if ($destination_url) {
                    echo '<a href="' . esc_url($destination_url) . '" target="_blank">' . esc_html($destination_url) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'short_url':
                $short_code = get_post_meta($post_id, '_wplinktracker_short_code', true);
                if ($short_code) {
                    $short_url = $this->get_short_url($short_code);
                    echo '<a href="' . esc_url($short_url) . '" target="_blank">' . esc_html($short_url) . '</a>';
                    echo '<br><button type="button" class="button button-small" onclick="copyToClipboard(\'' . esc_js($short_url) . '\')">' . __('Copy', 'wp-link-tracker') . '</button>';
                } else {
                    echo '—';
                }
                break;
                
            case 'clicks':
                $total_clicks = get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                echo intval($total_clicks);
                break;
                
            case 'unique_visitors':
                $unique_visitors = get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                echo intval($unique_visitors);
                break;
        }
    }
}
