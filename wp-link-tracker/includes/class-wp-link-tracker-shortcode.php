<?php
/**
 * Handles shortcodes for the plugin
 */
class WP_Link_Tracker_Shortcode {
    /**
     * Initialize the class.
     */
    public function init() {
        add_shortcode('tracked_link', array($this, 'render_tracked_link'));
        add_shortcode('link_stats', array($this, 'render_link_stats'));
    }

    /**
     * Render a tracked link shortcode.
     * 
     * Usage: [tracked_link id="123" text="Click here" class="my-class"]
     */
    public function render_tracked_link($atts, $content = null) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => '',
            'class' => '',
            'target' => '_blank',
            'rel' => 'noopener noreferrer'
        ), $atts, 'tracked_link');

        // Validate post ID
        if (empty($atts['id']) || !is_numeric($atts['id'])) {
            return '<span class="error">Invalid link ID</span>';
        }

        $post_id = intval($atts['id']);
        
        // Check if post exists and is the correct type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'wplinktracker') {
            return '<span class="error">Link not found</span>';
        }

        // Get the short code
        $short_code = get_post_meta($post_id, '_wplinktracker_short_code', true);
        if (empty($short_code)) {
            return '<span class="error">Short code not found</span>';
        }

        // Build the short URL
        $short_url = home_url('go/' . $short_code);
        
        // Determine link text
        $link_text = '';
        if (!empty($content)) {
            $link_text = $content;
        } elseif (!empty($atts['text'])) {
            $link_text = $atts['text'];
        } else {
            $link_text = $post->post_title;
        }

        // Build CSS classes
        $css_classes = 'tracked-link';
        if (!empty($atts['class'])) {
            $css_classes .= ' ' . sanitize_html_class($atts['class']);
        }

        // Build the link
        $link = sprintf(
            '<a href="%s" class="%s" target="%s" rel="%s">%s</a>',
            esc_url($short_url),
            esc_attr($css_classes),
            esc_attr($atts['target']),
            esc_attr($atts['rel']),
            esc_html($link_text)
        );

        return $link;
    }

    /**
     * Render link statistics shortcode.
     * 
     * Usage: [link_stats id="123" show="clicks,visitors,rate"]
     */
    public function render_link_stats($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'show' => 'clicks,visitors,rate',
            'class' => 'link-stats'
        ), $atts, 'link_stats');

        // Validate post ID
        if (empty($atts['id']) || !is_numeric($atts['id'])) {
            return '<span class="error">Invalid link ID</span>';
        }

        $post_id = intval($atts['id']);
        
        // Check if post exists and is the correct type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'wplinktracker') {
            return '<span class="error">Link not found</span>';
        }

        // Get statistics
        $total_clicks = get_post_meta($post_id, '_wplinktracker_total_clicks', true);
        $unique_visitors = get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
        $last_clicked = get_post_meta($post_id, '_wplinktracker_last_clicked', true);

        // Calculate conversion rate
        $total_clicks_int = (int) $total_clicks;
        $unique_visitors_int = (int) $unique_visitors;
        $conversion_rate = ($unique_visitors_int > 0) ? round(($total_clicks_int / $unique_visitors_int) * 100, 2) : 0;

        // Parse what to show
        $show_items = array_map('trim', explode(',', $atts['show']));
        
        $output = '<div class="' . esc_attr($atts['class']) . '">';
        
        foreach ($show_items as $item) {
            switch ($item) {
                case 'clicks':
                    $output .= '<span class="stat-item clicks">';
                    $output .= '<strong>' . __('Clicks:', 'wp-link-tracker') . '</strong> ';
                    $output .= esc_html($total_clicks ?: '0');
                    $output .= '</span>';
                    break;
                    
                case 'visitors':
                    $output .= '<span class="stat-item visitors">';
                    $output .= '<strong>' . __('Unique Visitors:', 'wp-link-tracker') . '</strong> ';
                    $output .= esc_html($unique_visitors ?: '0');
                    $output .= '</span>';
                    break;
                    
                case 'rate':
                    $output .= '<span class="stat-item conversion-rate">';
                    $output .= '<strong>' . __('Conversion Rate:', 'wp-link-tracker') . '</strong> ';
                    $output .= esc_html($conversion_rate . '%');
                    $output .= '</span>';
                    break;
                    
                case 'last':
                    if (!empty($last_clicked)) {
                        $output .= '<span class="stat-item last-clicked">';
                        $output .= '<strong>' . __('Last Clicked:', 'wp-link-tracker') . '</strong> ';
                        $output .= esc_html(date_i18n(get_option('date_format'), strtotime($last_clicked)));
                        $output .= '</span>';
                    }
                    break;
            }
        }
        
        $output .= '</div>';
        
        // Add basic styling
        $output .= '<style>
            .link-stats { margin: 10px 0; }
            .link-stats .stat-item { 
                display: inline-block; 
                margin-right: 15px; 
                padding: 5px 10px; 
                background: #f9f9f9; 
                border-radius: 3px; 
                font-size: 14px;
            }
            .link-stats .stat-item:last-child { margin-right: 0; }
        </style>';

        return $output;
    }
}
