<?php
/**
 * Plugin Name: Intelligize Digital ChatAssist
 * Plugin URI: https://wordpress.org/plugins/intelligizedigital-chatassist/
 * Description: A powerful, lightweight chat widget solution for WordPress. Connect n8n workflows, custom chat services, or any chat service via webhook URL. Customize appearance, track engagement, and display smartly with advanced rules. Perfect for customer support, sales, and engagement.
 * Version: 4.0.3
 * Author: Intelligize Digital
 * Author URI: https://intelligizedigital.com/
 * Text Domain: intelligizedigital-chatassist
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 7.0
 * Contributors: intelligize
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('INTELLIGIZEDIGITAL_CHATASSIST_VERSION', '4.0.3');
define('INTELLIGIZEDIGITAL_CHATASSIST_PATH', plugin_dir_path(__FILE__));
define('INTELLIGIZEDIGITAL_CHATASSIST_URL', plugin_dir_url(__FILE__));

// Include required files
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-display-rules.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-analytics.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-extended-rules.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-rest-api.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-gdpr.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-webhooks.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-ab-testing.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-widget-profiles.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-integrations.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-goals.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-marketing.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-mobile.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'includes/class-intelligizedigital-chatassist-push.php';
require_once INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'admin/class-intelligizedigital-chatassist-admin.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'intelligizedigital_chatassist_activate');
register_deactivation_hook(__FILE__, 'intelligizedigital_chatassist_deactivate');

/**
 * Plugin activation function
 */
function intelligizedigital_chatassist_activate() {
    // Add default options if not set
    $defaults = array(
        'intelligizedigital_chatassist_url' => '',
        'intelligizedigital_chatassist_enabled' => 'yes',
        'intelligizedigital_chatassist_position' => 'right',
        'intelligizedigital_chatassist_title' => 'Chat Support',
        'intelligizedigital_chatassist_color' => '#667eea',
        'intelligizedigital_chatassist_icon' => '💬',
        'intelligizedigital_chatassist_icon_type' => 'emoji',
        'intelligizedigital_chatassist_svg_icon' => '',
        'intelligizedigital_chatassist_zoom' => '100',
        'intelligizedigital_chatassist_show_on' => 'all',
        'intelligizedigital_chatassist_include_pages' => '',
        'intelligizedigital_chatassist_exclude_pages' => '',
        'intelligizedigital_chatassist_time_based' => 'no',
        'intelligizedigital_chatassist_start_time' => '09:00',
        'intelligizedigital_chatassist_end_time' => '17:00',
        'intelligizedigital_chatassist_role_based' => 'no',
        'intelligizedigital_chatassist_show_to_roles' => array('guest'),
        'intelligizedigital_chatassist_analytics_enabled' => 'yes',
        'intelligizedigital_chatassist_analytics' => array(),
        'intelligizedigital_chatassist_delay_seconds' => '0',
        'intelligizedigital_chatassist_scroll_depth' => '0',
        'intelligizedigital_chatassist_exit_intent' => 'no',
        'intelligizedigital_chatassist_proactive_message' => '',
        'intelligizedigital_chatassist_proactive_delay' => '10',
        'intelligizedigital_chatassist_pre_chat_form' => 'no',
        'intelligizedigital_chatassist_pre_chat_fields' => 'name,email',
        'intelligizedigital_chatassist_unread_badge' => 'no',
        'intelligizedigital_chatassist_theme' => 'light',
        'intelligizedigital_chatassist_gdpr_consent' => 'no',
        'intelligizedigital_chatassist_gdpr_message' => '',
        'intelligizedigital_chatassist_day_based' => 'no',
        'intelligizedigital_chatassist_show_days' => array(),
        'intelligizedigital_chatassist_device_based' => 'no',
        'intelligizedigital_chatassist_show_on_devices' => array('desktop', 'mobile', 'tablet'),
        'intelligizedigital_chatassist_geo_based' => 'no',
        'intelligizedigital_chatassist_show_countries' => array(),
        'intelligizedigital_chatassist_woo_pages' => array(),
        'intelligizedigital_chatassist_webhook_url' => '',
        'intelligizedigital_chatassist_slack_webhook' => '',
        'intelligizedigital_chatassist_discord_webhook' => '',
        'intelligizedigital_chatassist_slack_notify_opens' => 'no',
        'intelligizedigital_chatassist_slack_notify_messages' => 'no',
        'intelligizedigital_chatassist_slack_notify_leads' => 'no',
        'intelligizedigital_chatassist_discord_notify_messages' => 'no',
        'intelligizedigital_chatassist_discord_notify_leads' => 'no',
        'intelligizedigital_chatassist_crm_webhook' => '',
        'intelligizedigital_chatassist_crm_format' => 'hubspot',
        'intelligizedigital_chatassist_ab_testing' => 'no',
        'intelligizedigital_chatassist_ab_variants' => array(),
        'intelligizedigital_chatassist_layout' => 'popup',
        'intelligizedigital_chatassist_typing_indicator' => 'no',
        'intelligizedigital_chatassist_sound_enabled' => 'no',
        'intelligizedigital_chatassist_pre_chat_to_url' => 'yes',
        'intelligizedigital_chatassist_conversion_tracking' => 'no',
        'intelligizedigital_chatassist_heatmap_enabled' => 'no',
        'intelligizedigital_chatassist_goals' => array(),
        'intelligizedigital_chatassist_heatmap_data' => array(),
    );
    foreach ($defaults as $key => $val) {
        if (get_option($key, null) === null) {
            add_option($key, $val);
        }
    }

    // Set default capabilities
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_intelligizedigital_chatassist');
    }
}

/**
 * Plugin deactivation function
 */
function intelligizedigital_chatassist_deactivate() {
    // Don't delete options to preserve settings
}

/**
 * Initialize the admin settings
 */
function intelligizedigital_chatassist_init_admin() {
    if (!current_user_can('manage_intelligizedigital_chatassist') && !current_user_can('manage_options')) {
        return;
    }
    
    $admin = new IntelligizeDigital_ChatAssist_Admin();
    $admin->init();
}
add_action('init', 'intelligizedigital_chatassist_init_admin');

// Initialize REST API, GDPR, Webhooks, A/B Testing
IntelligizeDigital_ChatAssist_REST_API::init();
IntelligizeDigital_ChatAssist_GDPR::init();
IntelligizeDigital_ChatAssist_Webhooks::init();
IntelligizeDigital_ChatAssist_AB_Testing::init();
IntelligizeDigital_ChatAssist_Widget_Profiles::init();
IntelligizeDigital_ChatAssist_Integrations::init();
IntelligizeDigital_ChatAssist_Goals::init();
IntelligizeDigital_ChatAssist_Marketing::init();
IntelligizeDigital_ChatAssist_Mobile::init();
IntelligizeDigital_ChatAssist_Push::init();

/**
 * Shortcode: [intelligizedigital_chatassist]
 */
function intelligizedigital_chatassist_shortcode($atts) {
    if (!IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
        return '';
    }
    ob_start();
    include INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'public/partials/intelligizedigital-chatassist-public-display.php';
    return ob_get_clean();
}
add_shortcode('intelligizedigital_chatassist', 'intelligizedigital_chatassist_shortcode');

/**
 * Enqueue frontend scripts and styles
 */
function intelligizedigital_chatassist_enqueue_scripts() {
    if (!IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
        return;
    }
    
    wp_enqueue_style('intelligizedigital-chatassist-style', INTELLIGIZEDIGITAL_CHATASSIST_URL . 'assets/css/intelligizedigital-chatassist.css', array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION);
    wp_enqueue_script('intelligizedigital-chatassist-script', INTELLIGIZEDIGITAL_CHATASSIST_URL . 'assets/js/intelligizedigital-chatassist.js', array('jquery'), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
    
    $zoom = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_zoom', '100');
    $zoom = max(50, min(150, intval($zoom)));
    $color = apply_filters('intelligizedigital_chatassist_widget_color', intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_color', '#667eea'));
    
    $delay = max(0, intval(intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_delay_seconds', '0')));
    $scroll_depth = max(0, min(100, intval(intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_scroll_depth', '0'))));
    $gdpr_consent = IntelligizeDigital_ChatAssist_GDPR::consent_required();

    wp_localize_script('intelligizedigital-chatassist-script', 'intelligizedigitalChatAssistData', array(
        'chatUrl' => esc_url(intelligizedigital_chatassist_get_chat_url()),
        'position' => esc_attr(apply_filters('intelligizedigital_chatassist_widget_position', intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_position', 'right'))),
        'title' => esc_attr(apply_filters('intelligizedigital_chatassist_widget_title', intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_title', 'Chat Support'))),
        'color' => esc_attr($color),
        'icon' => esc_attr(intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_icon', '💬')),
        'iconType' => esc_attr(intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_icon_type', 'emoji')),
        'svgIcon' => esc_attr(intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_svg_icon', '')),
        'zoom' => $zoom,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('intelligizedigital_chatassist_ajax_nonce'),
        'analyticsEnabled' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_analytics_enabled', 'yes') === 'yes',
        'delaySeconds' => $delay,
        'scrollDepth' => $scroll_depth,
        'exitIntent' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_exit_intent', 'no') === 'yes',
        'proactiveMessage' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_proactive_message', ''),
        'proactiveDelay' => max(1, intval(intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_proactive_delay', '10'))),
        'preChatForm' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_pre_chat_form', 'no') === 'yes',
        'preChatFields' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_pre_chat_fields', 'name,email'),
        'unreadBadge' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_unread_badge', 'no') === 'yes',
        'theme' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_theme', 'light'),
        'gdprConsent' => $gdpr_consent,
        'layout' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_layout', 'popup'),
        'typingIndicator' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_typing_indicator', 'no') === 'yes',
        'soundEnabled' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_sound_enabled', 'no') === 'yes',
        'preChatToUrl' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_pre_chat_to_url', 'yes') === 'yes',
        'heatmapEnabled' => intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_heatmap_enabled', 'no') === 'yes',
        'abVariant' => class_exists('IntelligizeDigital_ChatAssist_AB_Testing') ? IntelligizeDigital_ChatAssist_AB_Testing::get_variant_for_js() : 'control',
    ));
    
    $custom_css = "
        .intelligizedigital-chatassist-button, .intelligizedigital-chatassist-header {
            background-color: " . esc_attr($color) . ";
        }
        .intelligizedigital-chatassist-button:hover {
            background-color: " . esc_attr(intelligizedigital_chatassist_adjust_color_brightness($color, -15)) . ";
        }
    ";
    wp_add_inline_style('intelligizedigital-chatassist-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'intelligizedigital_chatassist_enqueue_scripts');

/**
 * Add the chat widget to the footer
 */
function intelligizedigital_chatassist_add_to_footer() {
    if (!IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
        return;
    }
    
    do_action('intelligizedigital_chatassist_before_widget_render');
    
    ob_start();
    include INTELLIGIZEDIGITAL_CHATASSIST_PATH . 'public/partials/intelligizedigital-chatassist-public-display.php';
    $widget_html = ob_get_clean();
    
    $widget_html = apply_filters('intelligizedigital_chatassist_widget_html', $widget_html);
    echo wp_kses_post($widget_html);
    
    do_action('intelligizedigital_chatassist_after_widget_render');
}
add_action('wp_footer', 'intelligizedigital_chatassist_add_to_footer');


/**
 * WooCommerce: Set flag to open chat after add to cart
 */
add_action('woocommerce_add_to_cart', function() {
    set_transient('intelligizedigital_chatassist_open_trigger', 1, 60);
}, 10, 0);

/**
 * WooCommerce: Track conversion when order is completed
 */
add_action('woocommerce_order_status_completed', function() {
    if (get_option('intelligizedigital_chatassist_conversion_tracking', 'no') === 'yes') {
        IntelligizeDigital_ChatAssist_Analytics::track_conversion();
    }
});

/**
 * Contact Form 7: Set flag to open chat after form submission
 */
add_action('wpcf7_mail_sent', function() {
    set_transient('intelligizedigital_chatassist_open_trigger', 1, 60);
});

/**
 * WPForms: Set flag to open chat after form submission
 */
add_action('wpforms_process_complete', function($fields, $entry, $form_data) {
    set_transient('intelligizedigital_chatassist_open_trigger', 1, 60);
}, 10, 3);

/**
 * Enqueue script to trigger chat open when transient is set (WooCommerce, CF7, WPForms)
 */
add_action('wp_enqueue_scripts', function() {
    if (!get_transient('intelligizedigital_chatassist_open_trigger')) {
        return;
    }
    if (!IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
        delete_transient('intelligizedigital_chatassist_open_trigger');
        return;
    }
    delete_transient('intelligizedigital_chatassist_open_trigger');
    wp_add_inline_script(
        'intelligizedigital-chatassist-script',
        'jQuery(document).ready(function(){jQuery(document).trigger("intelligizedigital_chatassist_open");});'
    );
}, 20);

/**
 * Check if the chat widget is enabled.
 *
 * @since 1.0.0
 * @return bool True if enabled, false otherwise.
 */
function intelligizedigital_chatassist_is_enabled() {
    if (get_option('intelligizedigital_chatassist_enabled', 'yes') !== 'yes') {
        return false;
    }
    if (!empty(get_option('intelligizedigital_chatassist_url', ''))) {
        return true;
    }
    if (class_exists('IntelligizeDigital_ChatAssist_Widget_Profiles') && IntelligizeDigital_ChatAssist_Widget_Profiles::is_enabled()) {
        $profile = IntelligizeDigital_ChatAssist_Widget_Profiles::get_active_profile();
        return $profile && !empty($profile['url']);
    }
    return false;
}

/**
 * Get a plugin option value.
 *
 * @since 1.0.0
 * @param string $option_name Option name (e.g. 'intelligizedigital_chatassist_url').
 * @param mixed  $default     Default value if option not set.
 * @return mixed Option value.
 */
function intelligizedigital_chatassist_get_option($option_name, $default = '') {
    return get_option($option_name, $default);
}

/**
 * Get the configured chat URL.
 *
 * @since 1.0.0
 * @return string Chat URL.
 */
function intelligizedigital_chatassist_get_chat_url() {
    $url = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_url', '');
    return apply_filters('intelligizedigital_chatassist_chat_url', $url);
}

/**
 * Helper function to adjust color brightness
 */
function intelligizedigital_chatassist_adjust_color_brightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

/**
 * Helper function to display SVG images
 */
function intelligizedigital_chatassist_display_svg($svg_url, $size = array(24, 24), $attr = array()) {
    if (empty($svg_url)) {
        return '';
    }
    $default_attr = array('alt' => esc_attr__('Icon', 'intelligizedigital-chatassist'), 'class' => 'svg-icon');
    $attr = wp_parse_args($attr, $default_attr);
    $attachment_id = attachment_url_to_postid($svg_url);
    if ($attachment_id) {
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }
    $img_atts = array('src' => esc_url($svg_url), 'width' => esc_attr($size[0]), 'height' => esc_attr($size[1]));
    foreach ($attr as $name => $value) {
        $img_atts[$name] = esc_attr($value);
    }
    $img_html = '<img';
    foreach ($img_atts as $name => $value) {
        $img_html .= ' ' . $name . '="' . $value . '"';
    }
    $img_html .= '>';
    $allowed_html = array('img' => array('src' => array(), 'width' => array(), 'height' => array(), 'alt' => array(), 'class' => array(), 'style' => array(), 'id' => array()));
    return '<span class="intelligizedigital-chatassist-svg-wrapper">' . wp_kses($img_html, $allowed_html) . '</span>';
}

// Add plugin action links
function intelligizedigital_chatassist_plugin_action_links($links) {
    if (!current_user_can('manage_intelligizedigital_chatassist') && !current_user_can('manage_options')) {
        return $links;
    }
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=intelligizedigital_chatassist')) . '">' . esc_html__('Settings', 'intelligizedigital-chatassist') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'intelligizedigital_chatassist_plugin_action_links');

/**
 * AJAX handler for analytics tracking
 */
function intelligizedigital_chatassist_track_analytics() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'intelligizedigital_chatassist_ajax_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'intelligizedigital-chatassist')));
    }
    $event_type = isset($_POST['event_type']) ? sanitize_text_field(wp_unslash($_POST['event_type'])) : '';
    if (empty($event_type)) {
        wp_send_json_error(array('message' => __('Invalid event type.', 'intelligizedigital-chatassist')));
    }
    $ab_variant = isset($_POST['ab_variant']) ? sanitize_text_field(wp_unslash($_POST['ab_variant'])) : '';
    switch ($event_type) {
        case 'widget_opened':
            IntelligizeDigital_ChatAssist_Analytics::track_open();
            if (!empty($ab_variant)) {
                IntelligizeDigital_ChatAssist_Analytics::track_variant_event($ab_variant, 'open');
            }
            do_action('intelligizedigital_chatassist_widget_opened');
            break;
        case 'widget_closed':
            IntelligizeDigital_ChatAssist_Analytics::track_close();
            do_action('intelligizedigital_chatassist_widget_closed');
            break;
        case 'message_sent':
            IntelligizeDigital_ChatAssist_Analytics::track_message();
            do_action('intelligizedigital_chatassist_message_sent');
            break;
        case 'lead_captured':
            $lead = array(
                'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
                'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
                'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            );
            do_action('intelligizedigital_chatassist_lead_captured', $lead);
            $analytics = get_option('intelligizedigital_chatassist_analytics', array());
            $today = gmdate('Y-m-d');
            if (!isset($analytics[$today]['leads'])) $analytics[$today]['leads'] = 0;
            $analytics[$today]['leads']++;
            update_option('intelligizedigital_chatassist_analytics', $analytics);
            break;
        case 'conversion':
            IntelligizeDigital_ChatAssist_Analytics::track_conversion();
            if (!empty($ab_variant)) {
                IntelligizeDigital_ChatAssist_Analytics::track_variant_event($ab_variant, 'conversion');
            }
            break;
        case 'heatmap':
            $type = isset($_POST['heatmap_type']) ? sanitize_text_field(wp_unslash($_POST['heatmap_type'])) : '';
            $value = isset($_POST['heatmap_value']) ? sanitize_text_field(wp_unslash($_POST['heatmap_value'])) : '';
            if (in_array($type, array('scroll', 'click'))) {
                IntelligizeDigital_ChatAssist_Analytics::track_heatmap($type, $value);
            }
            break;
        default:
            wp_send_json_error(array('message' => __('Unknown event type.', 'intelligizedigital-chatassist')));
            return;
    }
    wp_send_json_success(array('message' => __('Event tracked.', 'intelligizedigital-chatassist')));
}
add_action('wp_ajax_intelligizedigital_chatassist_track', 'intelligizedigital_chatassist_track_analytics');
add_action('wp_ajax_nopriv_intelligizedigital_chatassist_track', 'intelligizedigital_chatassist_track_analytics');

/**
 * AJAX: Attribute WooCommerce order to chat (session-based)
 */
function intelligizedigital_chatassist_attr_order() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'intelligizedigital_chatassist_attr_order')) {
        wp_send_json_error();
    }
    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    $order_key = isset($_POST['order_key']) ? sanitize_text_field(wp_unslash($_POST['order_key'])) : '';
    if (!$order_id || !function_exists('wc_get_order')) {
        wp_send_json_error();
    }
    $order = wc_get_order($order_id);
    if (!$order || !is_a($order, 'WC_Order')) {
        wp_send_json_error();
    }
    if (!empty($order_key) && $order->get_order_key() !== $order_key) {
        wp_send_json_error();
    }
    $order->update_meta_data('_intelligizedigital_chatassist_attributed', '1');
    $order->save();
    wp_send_json_success();
}
add_action('wp_ajax_intelligizedigital_chatassist_attr_order', 'intelligizedigital_chatassist_attr_order');
add_action('wp_ajax_nopriv_intelligizedigital_chatassist_attr_order', 'intelligizedigital_chatassist_attr_order');

/**
 * WooCommerce: On order-received, if chat cookie exists, attribute order
 */
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id || get_option('intelligizedigital_chatassist_conversion_tracking', 'no') !== 'yes') {
        return;
    }
    if (!function_exists('wc_get_order')) {
        return;
    }
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    $ajax_url  = admin_url('admin-ajax.php');
    $nonce     = wp_create_nonce('intelligizedigital_chatassist_attr_order');
    $order_key = $order->get_order_key();
    $js        = '(function(){if(document.cookie.indexOf("intelligizedigital_chatassist_opened=") === -1)return;';
    $js       .= 'var x=new XMLHttpRequest();';
    $js       .= 'x.open("POST",' . wp_json_encode($ajax_url) . ',true);';
    $js       .= 'x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");';
    $js       .= 'x.send("action=intelligizedigital_chatassist_attr_order';
    $js       .= '&order_id=' . absint($order_id);
    $js       .= '&order_key=' . rawurlencode($order_key);
    $js       .= '&nonce=' . rawurlencode($nonce) . '");';
    $js       .= '})();';
    wp_register_script('intelligizedigital-chatassist-attr', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
    wp_enqueue_script('intelligizedigital-chatassist-attr');
    wp_add_inline_script('intelligizedigital-chatassist-attr', $js);
}, 5);
