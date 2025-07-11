<?php
/**
 * Plugin Name: WP Link Tracker
 * Plugin URI: https://example.com/wp-link-tracker
 * Description: A powerful link shortener and tracker for WordPress, similar to ClickMagic.
 * Version: 1.0.0
 * Author: xBesh
 * Author URI: https://example.com
 * Text Domain: wp-link-tracker
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_LINK_TRACKER_VERSION', '1.0.0');
define('WP_LINK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_LINK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker.php';
require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-post-type.php';
require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-shortcode.php';
require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-redirect.php';
require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-stats.php';
require_once WP_LINK_TRACKER_PLUGIN_DIR . 'admin/class-wp-link-tracker-admin.php';

// Initialize the plugin
function wp_link_tracker_init() {
    $plugin = new WP_Link_Tracker();
    $plugin->run();
}
add_action('plugins_loaded', 'wp_link_tracker_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_link_tracker_activate');
function wp_link_tracker_activate() {
    // Create database tables
    require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-activator.php';
    WP_Link_Tracker_Activator::activate();
    
    // Initialize rewrite rules
    wp_link_tracker_init();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_link_tracker_deactivate');
function wp_link_tracker_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Add debug function for troubleshooting
function wp_link_tracker_debug_rewrite_rules() {
    if (current_user_can('manage_options') && isset($_GET['debug_rewrite'])) {
        echo '<pre>';
        print_r(get_option('rewrite_rules'));
        echo '</pre>';
        exit;
    }
}
add_action('init', 'wp_link_tracker_debug_rewrite_rules');
