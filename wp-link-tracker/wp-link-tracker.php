<?php
/**
 * Plugin Name: WP Link Tracker
 * Plugin URI: https://example.com/wp-link-tracker
 * Description: A comprehensive WordPress plugin for creating, managing, and tracking short links with detailed analytics.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-link-tracker
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_LINK_TRACKER_VERSION', '1.0.0');
define('WP_LINK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_LINK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_LINK_TRACKER_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class WP_Link_Tracker {
    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('WP_Link_Tracker', 'uninstall'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('wp-link-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->include_files();
        
        // Initialize components
        $this->init_components();
        
        // Register post type and taxonomy
        $this->register_post_type();
        $this->register_taxonomy();
    }

    /**
     * Include required files
     */
    private function include_files() {
        require_once WP_LINK_TRACKER_PLUGIN_DIR . 'admin/class-wp-link-tracker-admin.php';
        require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-redirect.php';
        require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-shortcode.php';
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize admin functionality
        if (is_admin()) {
            $admin = new WP_Link_Tracker_Admin();
            $admin->init();
        }
        
        // Initialize redirect handler
        $redirect = new WP_Link_Tracker_Redirect();
        $redirect->init();
        
        // Initialize shortcode handler
        $shortcode = new WP_Link_Tracker_Shortcode();
        $shortcode->init();
    }

    /**
     * Register the tracked link post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Tracked Links', 'Post type general name', 'wp-link-tracker'),
            'singular_name'         => _x('Tracked Link', 'Post type singular name', 'wp-link-tracker'),
            'menu_name'             => _x('Link Tracker', 'Admin Menu text', 'wp-link-tracker'),
            'name_admin_bar'        => _x('Tracked Link', 'Add New on Toolbar', 'wp-link-tracker'),
            'add_new'               => __('Add New', 'wp-link-tracker'),
            'add_new_item'          => __('Add New Tracked Link', 'wp-link-tracker'),
            'new_item'              => __('New Tracked Link', 'wp-link-tracker'),
            'edit_item'             => __('Edit Tracked Link', 'wp-link-tracker'),
            'view_item'             => __('View Tracked Link', 'wp-link-tracker'),
            'all_items'             => __('All Tracked Links', 'wp-link-tracker'),
            'search_items'          => __('Search Tracked Links', 'wp-link-tracker'),
            'parent_item_colon'     => __('Parent Tracked Links:', 'wp-link-tracker'),
            'not_found'             => __('No tracked links found.', 'wp-link-tracker'),
            'not_found_in_trash'    => __('No tracked links found in Trash.', 'wp-link-tracker'),
            'featured_image'        => _x('Tracked Link Cover Image', 'Overrides the "Featured Image" phrase', 'wp-link-tracker'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'wp-link-tracker'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'wp-link-tracker'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'wp-link-tracker'),
            'archives'              => _x('Tracked Link archives', 'The post type archive label', 'wp-link-tracker'),
            'insert_into_item'      => _x('Insert into tracked link', 'Overrides the "Insert into post" phrase', 'wp-link-tracker'),
            'uploaded_to_this_item' => _x('Uploaded to this tracked link', 'Overrides the "Uploaded to this post" phrase', 'wp-link-tracker'),
            'filter_items_list'     => _x('Filter tracked links list', 'Screen reader text for the filter links heading', 'wp-link-tracker'),
            'items_list_navigation' => _x('Tracked links list navigation', 'Screen reader text for the pagination heading', 'wp-link-tracker'),
            'items_list'            => _x('Tracked links list', 'Screen reader text for the items list heading', 'wp-link-tracker'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-admin-links',
            'supports'           => array('title'),
            'show_in_rest'       => false,
        );

        register_post_type('wplinktracker', $args);
    }

    /**
     * Register the campaign taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                       => _x('Campaigns', 'Taxonomy General Name', 'wp-link-tracker'),
            'singular_name'              => _x('Campaign', 'Taxonomy Singular Name', 'wp-link-tracker'),
            'menu_name'                  => __('Campaigns', 'wp-link-tracker'),
            'all_items'                  => __('All Campaigns', 'wp-link-tracker'),
            'parent_item'                => __('Parent Campaign', 'wp-link-tracker'),
            'parent_item_colon'          => __('Parent Campaign:', 'wp-link-tracker'),
            'new_item_name'              => __('New Campaign Name', 'wp-link-tracker'),
            'add_new_item'               => __('Add New Campaign', 'wp-link-tracker'),
            'edit_item'                  => __('Edit Campaign', 'wp-link-tracker'),
            'update_item'                => __('Update Campaign', 'wp-link-tracker'),
            'view_item'                  => __('View Campaign', 'wp-link-tracker'),
            'separate_items_with_commas' => __('Separate campaigns with commas', 'wp-link-tracker'),
            'add_or_remove_items'        => __('Add or remove campaigns', 'wp-link-tracker'),
            'choose_from_most_used'      => __('Choose from the most used', 'wp-link-tracker'),
            'popular_items'              => __('Popular Campaigns', 'wp-link-tracker'),
            'search_items'               => __('Search Campaigns', 'wp-link-tracker'),
            'not_found'                  => __('Not Found', 'wp-link-tracker'),
            'no_terms'                   => __('No campaigns', 'wp-link-tracker'),
            'items_list'                 => __('Campaigns list', 'wp-link-tracker'),
            'items_list_navigation'      => __('Campaigns list navigation', 'wp-link-tracker'),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => false,
            'show_tagcloud'              => false,
            'show_in_rest'               => false,
        );

        register_taxonomy('wplinktracker_campaign', array('wplinktracker'), $args);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Register post type and taxonomy
        $this->register_post_type();
        $this->register_taxonomy();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $default_options = array(
            'link_prefix' => 'go'
        );
        
        if (!get_option('wp_link_tracker_settings')) {
            add_option('wp_link_tracker_settings', $default_options);
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        global $wpdb;
        
        // Remove all tracked link posts
        $posts = get_posts(array(
            'post_type' => 'wplinktracker',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Remove taxonomy terms
        $terms = get_terms(array(
            'taxonomy' => 'wplinktracker_campaign',
            'hide_empty' => false
        ));
        
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'wplinktracker_campaign');
        }
        
        // Drop custom tables
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Remove options
        delete_option('wp_link_tracker_settings');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            visitor_id varchar(32) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            referrer text,
            device_type varchar(20),
            browser varchar(50),
            os varchar(50),
            click_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            utm_source varchar(255),
            utm_medium varchar(255),
            utm_campaign varchar(255),
            utm_term varchar(255),
            utm_content varchar(255),
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY visitor_id (visitor_id),
            KEY click_time (click_time),
            KEY device_type (device_type),
            KEY browser (browser),
            KEY os (os)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
WP_Link_Tracker::get_instance();
