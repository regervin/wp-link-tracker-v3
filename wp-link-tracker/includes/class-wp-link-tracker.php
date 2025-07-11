<?php
/**
 * The main plugin class
 */
class WP_Link_Tracker {
    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $post_type;
    protected $shortcode;
    protected $redirect;
    protected $stats;
    protected $admin;

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        $this->post_type = new WP_Link_Tracker_Post_Type();
        $this->shortcode = new WP_Link_Tracker_Shortcode();
        $this->redirect = new WP_Link_Tracker_Redirect();
        $this->stats = new WP_Link_Tracker_Stats();
        $this->admin = new WP_Link_Tracker_Admin();
    }

    /**
     * Run the plugin.
     */
    public function run() {
        $this->post_type->init();
        $this->shortcode->init();
        $this->redirect->init();
        $this->stats->init();
        $this->admin->init();
        
        // Add AJAX handlers
        add_action('wp_ajax_wp_link_tracker_get_stats', array($this->stats, 'get_stats_ajax'));
        add_action('wp_ajax_wp_link_tracker_create_link', array($this->admin, 'create_link_ajax'));
    }
}
