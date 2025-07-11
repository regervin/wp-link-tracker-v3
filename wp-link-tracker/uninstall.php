<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom post type posts
$posts = get_posts(array(
    'post_type' => 'wplinktracker',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

// Delete custom taxonomy terms
$terms = get_terms(array(
    'taxonomy' => 'wplinktracker_campaign',
    'hide_empty' => false
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'wplinktracker_campaign');
}

// Delete options
delete_option('wp_link_tracker_settings');

// Delete database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wplinktracker_clicks");

// Clear any cached data that might be in the database
wp_cache_flush();
