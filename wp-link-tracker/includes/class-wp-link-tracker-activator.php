<?php
/**
 * Fired during plugin activation
 */
class WP_Link_Tracker_Activator {
    /**
     * Create the necessary database tables.
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            visitor_id varchar(32) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            referrer text,
            device_type varchar(20) NOT NULL,
            browser varchar(50) NOT NULL,
            os varchar(50) NOT NULL,
            click_time datetime NOT NULL,
            utm_source varchar(100),
            utm_medium varchar(100),
            utm_campaign varchar(100),
            utm_term varchar(100),
            utm_content varchar(100),
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY visitor_id (visitor_id),
            KEY click_time (click_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
