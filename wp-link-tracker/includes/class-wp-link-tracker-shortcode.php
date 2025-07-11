<?php
/**
 * Shortcode functionality for the plugin
 */
class WP_Link_Tracker_Shortcode {
    /**
     * Initialize the class.
     */
    public function init() {
        add_shortcode('tracked_link', array($this, 'tracked_link_shortcode'));
    }

    /**
     * Shortcode handler for [tracked_link]
     */
    public function tracked_link_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'text' => '',
            'class' => '',
            'campaign' => '',
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_term' => '',
            'utm_content' => '',
        ), $atts, 'tracked_link');

        // If no ID is provided, return empty
        if (empty($atts['id'])) {
            return '';
        }

        // Get the post
        $post = get_post($atts['id']);
        if (!$post || 'wplinktracker' !== $post->post_type) {
            return '';
        }

        // Get the short code
        $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
        if (empty($short_code)) {
            return '';
        }

        // Build the URL
        $url = home_url('go/' . $short_code);

        // Add UTM parameters if provided
        $utm_params = array();
        foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content') as $param) {
            if (!empty($atts[$param])) {
                $utm_params[$param] = $atts[$param];
            }
        }

        if (!empty($utm_params)) {
            $url = add_query_arg($utm_params, $url);
        }

        // Determine link text
        $link_text = !empty($atts['text']) ? $atts['text'] : (!empty($content) ? $content : $post->post_title);

        // Build the link
        $class = !empty($atts['class']) ? ' class="' . esc_attr($atts['class']) . '"' : '';
        $link = '<a href="' . esc_url($url) . '"' . $class . '>' . esc_html($link_text) . '</a>';

        return $link;
    }
}
