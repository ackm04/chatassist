<?php
/**
 * Plugin Name: ChatAssist by Ajay
 * Plugin URI: https://wordpress.org/plugins/intelligizedigital-chatassist/
 * Description: A powerful, lightweight chat widget for WordPress. Connect n8n workflows, custom AI bots, or any chat service via webhook URL. Customize appearance, track engagement, target audiences with smart display rules. Perfect for customer support, sales, and engagement.
 * Version: 4.0.4
 * Author: Ajay
 * Author URI: https://github.com/ackm04
 * Text Domain: intelligizedigital-chatassist
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 7.0
 * Contributors: ackm04
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ACKM_CHATASSIST_VERSION', '4.0.4');
define('ACKM_CHATASSIST_PATH', plugin_dir_path(__FILE__));
define('ACKM_CHATASSIST_URL', plugin_dir_url(__FILE__));

// Include required files
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-display-rules.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-analytics.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-extended-rules.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-rest-api.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-gdpr.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-webhooks.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-ab-testing.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-widget-profiles.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-integrations.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-goals.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-marketing.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-mobile.php';
require_once ACKM_CHATASSIST_PATH . 'includes/class-ackm-chatassist-push.php';
require_once ACKM_CHATASSIST_PATH . 'admin/class-ackm-chatassist-admin.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'ackm_chatassist_activate');
register_deactivation_hook(__FILE__, 'ackm_chatassist_deactivate');

/**
 * Plugin activation function
 */
function ackm_chatassist_activate() {
    // Add default options if not set
    $defaults = array(
        'ackm_chatassist_url' => '',
        'ackm_chatassist_enabled' => 'yes',
        'ackm_chatassist_position' => 'right',
        'ackm_chatassist_title' => 'Chat Support',
        'ackm_chatassist_color' => '#667eea',
        'ackm_chatassist_icon' => '💬',
        'ackm_chatassist_icon_type' => 'emoji',
        'ackm_chatassist_svg_icon' => '',
        'ackm_chatassist_zoom' => '100',
        'ackm_chatassist_show_on' => 'all',
        'ackm_chatassist_include_pages' => '',
        'ackm_chatassist_exclude_pages' => '',
        'ackm_chatassist_time_based' => 'no',
        'ackm_chatassist_start_time' => '09:00',
        'ackm_chatassist_end_time' => '17:00',
        'ackm_chatassist_role_based' => 'no',
        'ackm_chatassist_show_to_roles' => array('guest'),
        'ackm_chatassist_analytics_enabled' => 'yes',
        'ackm_chatassist_analytics' => array(),
        'ackm_chatassist_delay_seconds' => '0',
        'ackm_chatassist_scroll_depth' => '0',
        'ackm_chatassist_exit_intent' => 'no',
        'ackm_chatassist_proactive_message' => '',
        'ackm_chatassist_proactive_delay' => '10',
        'ackm_chatassist_pre_chat_form' => 'no',
        'ackm_chatassist_pre_chat_fields' => 'name,email',
        'ackm_chatassist_unread_badge' => 'no',
        'ackm_chatassist_theme' => 'light',
        'ackm_chatassist_gdpr_consent' => 'no',
        'ackm_chatassist_gdpr_message' => '',
        'ackm_chatassist_day_based' => 'no',
        'ackm_chatassist_show_days' => array(),
        'ackm_chatassist_device_based' => 'no',
        'ackm_chatassist_show_on_devices' => array('desktop', 'mobile', 'tablet'),
        'ackm_chatassist_geo_based' => 'no',
        'ackm_chatassist_show_countries' => array(),
        'ackm_chatassist_woo_pages' => array(),
        'ackm_chatassist_webhook_url' => '',
        'ackm_chatassist_slack_webhook' => '',
        'ackm_chatassist_discord_webhook' => '',
        'ackm_chatassist_slack_notify_opens' => 'no',
        'ackm_chatassist_slack_notify_messages' => 'no',
        'ackm_chatassist_slack_notify_leads' => 'no',
        'ackm_chatassist_discord_notify_messages' => 'no',
        'ackm_chatassist_discord_notify_leads' => 'no',
        'ackm_chatassist_crm_webhook' => '',
        'ackm_chatassist_crm_format' => 'hubspot',
        'ackm_chatassist_ab_testing' => 'no',
        'ackm_chatassist_ab_variants' => array(),
        'ackm_chatassist_layout' => 'popup',
        'ackm_chatassist_typing_indicator' => 'no',
        'ackm_chatassist_sound_enabled' => 'no',
        'ackm_chatassist_pre_chat_to_url' => 'yes',
        'ackm_chatassist_conversion_tracking' => 'no',
        'ackm_chatassist_heatmap_enabled' => 'no',
        'ackm_chatassist_goals' => array(),
        'ackm_chatassist_heatmap_data' => array(),
    );
    foreach ($defaults as $key => $val) {
        if (get_option($key, null) === null) {
            add_option($key, $val);
        }
    }

    // Set default capabilities
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_ackm_chatassist');
    }
}

/**
 * Plugin deactivation function
 */
function ackm_chatassist_deactivate() {
    // Don't delete options to preserve settings
}

/**
 * One-time migration: copy old `intelligizedigital_chatassist_*` options to new
 * `ackm_chatassist_*` keys so existing installs don't lose their data after
 * the v4.0.4 internal prefix rename. Runs once, then sets a flag.
 *
 * Also migrates the custom capability and any saved user-meta keys.
 *
 * @since 4.0.4
 */
function ackm_chatassist_maybe_migrate_legacy_options() {
    if (get_option('ackm_chatassist_migrated_v404', '0') === '1') {
        return;
    }

    global $wpdb;
    $legacy_prefix = 'intelligizedigital_chatassist_';
    $new_prefix    = 'ackm_chatassist_';

    // Find every option that begins with the legacy prefix.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $legacy_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like($legacy_prefix) . '%'
        )
    );

    if (!empty($legacy_rows)) {
        foreach ($legacy_rows as $row) {
            $new_key = $new_prefix . substr($row->option_name, strlen($legacy_prefix));
            // Only migrate if the new key does not already have a value.
            if (get_option($new_key, null) === null) {
                $value = maybe_unserialize($row->option_value);
                add_option($new_key, $value, '', $row->autoload);
            }
            // Remove the legacy key to keep the DB clean.
            delete_option($row->option_name);
        }
    }

    // Migrate the custom capability for every role that had it.
    foreach (wp_roles()->roles as $role_slug => $role_info) {
        $role = get_role($role_slug);
        if ($role && $role->has_cap('manage_intelligizedigital_chatassist')) {
            $role->add_cap('manage_ackm_chatassist');
            $role->remove_cap('manage_intelligizedigital_chatassist');
        }
    }

    update_option('ackm_chatassist_migrated_v404', '1', true);
}
add_action('plugins_loaded', 'ackm_chatassist_maybe_migrate_legacy_options', 1);

/**
 * Initialize the admin settings
 */
function ackm_chatassist_init_admin() {
    if (!current_user_can('manage_ackm_chatassist') && !current_user_can('manage_options')) {
        return;
    }
    
    $admin = new Ackm_ChatAssist_Admin();
    $admin->init();
}
add_action('init', 'ackm_chatassist_init_admin');

// Initialize REST API, GDPR, Webhooks, A/B Testing
Ackm_ChatAssist_REST_API::init();
Ackm_ChatAssist_GDPR::init();
Ackm_ChatAssist_Webhooks::init();
Ackm_ChatAssist_AB_Testing::init();
Ackm_ChatAssist_Widget_Profiles::init();
Ackm_ChatAssist_Integrations::init();
Ackm_ChatAssist_Goals::init();
Ackm_ChatAssist_Marketing::init();
Ackm_ChatAssist_Mobile::init();
Ackm_ChatAssist_Push::init();

/**
 * Shortcode: [ackm_chatassist]
 */
function ackm_chatassist_shortcode($atts) {
    if (!Ackm_ChatAssist_Display_Rules::should_display()) {
        return '';
    }
    ob_start();
    include ACKM_CHATASSIST_PATH . 'public/partials/ackm-chatassist-public-display.php';
    return ob_get_clean();
}
add_shortcode('ackm_chatassist', 'ackm_chatassist_shortcode');

/**
 * Enqueue frontend scripts and styles
 */
function ackm_chatassist_enqueue_scripts() {
    if (!Ackm_ChatAssist_Display_Rules::should_display()) {
        return;
    }
    
    wp_enqueue_style('ackm-chatassist-style', ACKM_CHATASSIST_URL . 'assets/css/ackm-chatassist.css', array(), ACKM_CHATASSIST_VERSION);
    wp_enqueue_script('ackm-chatassist-script', ACKM_CHATASSIST_URL . 'assets/js/ackm-chatassist.js', array('jquery'), ACKM_CHATASSIST_VERSION, true);
    
    $zoom = ackm_chatassist_get_option('ackm_chatassist_zoom', '100');
    $zoom = max(50, min(150, intval($zoom)));
    $color = apply_filters('ackm_chatassist_widget_color', ackm_chatassist_get_option('ackm_chatassist_color', '#667eea'));
    
    $delay = max(0, intval(ackm_chatassist_get_option('ackm_chatassist_delay_seconds', '0')));
    $scroll_depth = max(0, min(100, intval(ackm_chatassist_get_option('ackm_chatassist_scroll_depth', '0'))));
    $gdpr_consent = Ackm_ChatAssist_GDPR::consent_required();

    wp_localize_script('ackm-chatassist-script', 'ackmChatAssistData', array(
        'chatUrl' => esc_url(ackm_chatassist_get_chat_url()),
        'position' => esc_attr(apply_filters('ackm_chatassist_widget_position', ackm_chatassist_get_option('ackm_chatassist_position', 'right'))),
        'title' => esc_attr(apply_filters('ackm_chatassist_widget_title', ackm_chatassist_get_option('ackm_chatassist_title', 'Chat Support'))),
        'color' => esc_attr($color),
        'icon' => esc_attr(ackm_chatassist_get_option('ackm_chatassist_icon', '💬')),
        'iconType' => esc_attr(ackm_chatassist_get_option('ackm_chatassist_icon_type', 'emoji')),
        'svgIcon' => esc_attr(ackm_chatassist_get_option('ackm_chatassist_svg_icon', '')),
        'zoom' => $zoom,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ackm_chatassist_ajax_nonce'),
        'analyticsEnabled' => ackm_chatassist_get_option('ackm_chatassist_analytics_enabled', 'yes') === 'yes',
        'delaySeconds' => $delay,
        'scrollDepth' => $scroll_depth,
        'exitIntent' => ackm_chatassist_get_option('ackm_chatassist_exit_intent', 'no') === 'yes',
        'proactiveMessage' => ackm_chatassist_get_option('ackm_chatassist_proactive_message', ''),
        'proactiveDelay' => max(1, intval(ackm_chatassist_get_option('ackm_chatassist_proactive_delay', '10'))),
        'preChatForm' => ackm_chatassist_get_option('ackm_chatassist_pre_chat_form', 'no') === 'yes',
        'preChatFields' => ackm_chatassist_get_option('ackm_chatassist_pre_chat_fields', 'name,email'),
        'unreadBadge' => ackm_chatassist_get_option('ackm_chatassist_unread_badge', 'no') === 'yes',
        'theme' => ackm_chatassist_get_option('ackm_chatassist_theme', 'light'),
        'gdprConsent' => $gdpr_consent,
        'layout' => ackm_chatassist_get_option('ackm_chatassist_layout', 'popup'),
        'typingIndicator' => ackm_chatassist_get_option('ackm_chatassist_typing_indicator', 'no') === 'yes',
        'soundEnabled' => ackm_chatassist_get_option('ackm_chatassist_sound_enabled', 'no') === 'yes',
        'preChatToUrl' => ackm_chatassist_get_option('ackm_chatassist_pre_chat_to_url', 'yes') === 'yes',
        'heatmapEnabled' => ackm_chatassist_get_option('ackm_chatassist_heatmap_enabled', 'no') === 'yes',
        'abVariant' => class_exists('Ackm_ChatAssist_AB_Testing') ? Ackm_ChatAssist_AB_Testing::get_variant_for_js() : 'control',
    ));
    
    $custom_css = "
        .ackm-chatassist-button, .ackm-chatassist-header {
            background-color: " . esc_attr($color) . ";
        }
        .ackm-chatassist-button:hover {
            background-color: " . esc_attr(ackm_chatassist_adjust_color_brightness($color, -15)) . ";
        }
    ";
    wp_add_inline_style('ackm-chatassist-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'ackm_chatassist_enqueue_scripts');

/**
 * Add the chat widget to the footer
 */
function ackm_chatassist_add_to_footer() {
    if (!Ackm_ChatAssist_Display_Rules::should_display()) {
        return;
    }
    
    do_action('ackm_chatassist_before_widget_render');
    
    ob_start();
    include ACKM_CHATASSIST_PATH . 'public/partials/ackm-chatassist-public-display.php';
    $widget_html = ob_get_clean();
    
    $widget_html = apply_filters('ackm_chatassist_widget_html', $widget_html);
    echo wp_kses_post($widget_html);
    
    do_action('ackm_chatassist_after_widget_render');
}
add_action('wp_footer', 'ackm_chatassist_add_to_footer');


/**
 * WooCommerce: Set flag to open chat after add to cart
 */
add_action('woocommerce_add_to_cart', function() {
    set_transient('ackm_chatassist_open_trigger', 1, 60);
}, 10, 0);

/**
 * WooCommerce: Track conversion when order is completed
 */
add_action('woocommerce_order_status_completed', function() {
    if (get_option('ackm_chatassist_conversion_tracking', 'no') === 'yes') {
        Ackm_ChatAssist_Analytics::track_conversion();
    }
});

/**
 * Contact Form 7: Set flag to open chat after form submission
 */
add_action('wpcf7_mail_sent', function() {
    set_transient('ackm_chatassist_open_trigger', 1, 60);
});

/**
 * WPForms: Set flag to open chat after form submission
 */
add_action('wpforms_process_complete', function($fields, $entry, $form_data) {
    set_transient('ackm_chatassist_open_trigger', 1, 60);
}, 10, 3);

/**
 * Enqueue script to trigger chat open when transient is set (WooCommerce, CF7, WPForms)
 */
add_action('wp_enqueue_scripts', function() {
    if (!get_transient('ackm_chatassist_open_trigger')) {
        return;
    }
    if (!Ackm_ChatAssist_Display_Rules::should_display()) {
        delete_transient('ackm_chatassist_open_trigger');
        return;
    }
    delete_transient('ackm_chatassist_open_trigger');
    wp_add_inline_script(
        'ackm-chatassist-script',
        'jQuery(document).ready(function(){jQuery(document).trigger("ackm_chatassist_open");});'
    );
}, 20);

/**
 * Check if the chat widget is enabled.
 *
 * @since 1.0.0
 * @return bool True if enabled, false otherwise.
 */
function ackm_chatassist_is_enabled() {
    if (get_option('ackm_chatassist_enabled', 'yes') !== 'yes') {
        return false;
    }
    if (!empty(get_option('ackm_chatassist_url', ''))) {
        return true;
    }
    if (class_exists('Ackm_ChatAssist_Widget_Profiles') && Ackm_ChatAssist_Widget_Profiles::is_enabled()) {
        $profile = Ackm_ChatAssist_Widget_Profiles::get_active_profile();
        return $profile && !empty($profile['url']);
    }
    return false;
}

/**
 * Get a plugin option value.
 *
 * @since 1.0.0
 * @param string $option_name Option name (e.g. 'ackm_chatassist_url').
 * @param mixed  $default     Default value if option not set.
 * @return mixed Option value.
 */
function ackm_chatassist_get_option($option_name, $default = '') {
    return get_option($option_name, $default);
}

/**
 * Get the configured chat URL.
 *
 * @since 1.0.0
 * @return string Chat URL.
 */
function ackm_chatassist_get_chat_url() {
    $url = ackm_chatassist_get_option('ackm_chatassist_url', '');
    return apply_filters('ackm_chatassist_chat_url', $url);
}

/**
 * Helper function to adjust color brightness
 */
function ackm_chatassist_adjust_color_brightness($hex, $steps) {
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
function ackm_chatassist_display_svg($svg_url, $size = array(24, 24), $attr = array()) {
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
    return '<span class="ackm-chatassist-svg-wrapper">' . wp_kses($img_html, $allowed_html) . '</span>';
}

// Add plugin action links
function ackm_chatassist_plugin_action_links($links) {
    if (!current_user_can('manage_ackm_chatassist') && !current_user_can('manage_options')) {
        return $links;
    }
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=ackm_chatassist')) . '">' . esc_html__('Settings', 'intelligizedigital-chatassist') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ackm_chatassist_plugin_action_links');

/**
 * AJAX handler for analytics tracking
 */
function ackm_chatassist_track_analytics() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ackm_chatassist_ajax_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'intelligizedigital-chatassist')));
    }
    $event_type = isset($_POST['event_type']) ? sanitize_text_field(wp_unslash($_POST['event_type'])) : '';
    if (empty($event_type)) {
        wp_send_json_error(array('message' => __('Invalid event type.', 'intelligizedigital-chatassist')));
    }
    $ab_variant = isset($_POST['ab_variant']) ? sanitize_text_field(wp_unslash($_POST['ab_variant'])) : '';
    switch ($event_type) {
        case 'widget_opened':
            Ackm_ChatAssist_Analytics::track_open();
            if (!empty($ab_variant)) {
                Ackm_ChatAssist_Analytics::track_variant_event($ab_variant, 'open');
            }
            do_action('ackm_chatassist_widget_opened');
            break;
        case 'widget_closed':
            Ackm_ChatAssist_Analytics::track_close();
            do_action('ackm_chatassist_widget_closed');
            break;
        case 'message_sent':
            Ackm_ChatAssist_Analytics::track_message();
            do_action('ackm_chatassist_message_sent');
            break;
        case 'lead_captured':
            $lead = array(
                'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
                'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
                'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            );
            do_action('ackm_chatassist_lead_captured', $lead);
            $analytics = get_option('ackm_chatassist_analytics', array());
            $today = gmdate('Y-m-d');
            if (!isset($analytics[$today]['leads'])) $analytics[$today]['leads'] = 0;
            $analytics[$today]['leads']++;
            update_option('ackm_chatassist_analytics', $analytics);
            break;
        case 'conversion':
            Ackm_ChatAssist_Analytics::track_conversion();
            if (!empty($ab_variant)) {
                Ackm_ChatAssist_Analytics::track_variant_event($ab_variant, 'conversion');
            }
            break;
        case 'heatmap':
            $type = isset($_POST['heatmap_type']) ? sanitize_text_field(wp_unslash($_POST['heatmap_type'])) : '';
            $value = isset($_POST['heatmap_value']) ? sanitize_text_field(wp_unslash($_POST['heatmap_value'])) : '';
            if (in_array($type, array('scroll', 'click'))) {
                Ackm_ChatAssist_Analytics::track_heatmap($type, $value);
            }
            break;
        default:
            wp_send_json_error(array('message' => __('Unknown event type.', 'intelligizedigital-chatassist')));
            return;
    }
    wp_send_json_success(array('message' => __('Event tracked.', 'intelligizedigital-chatassist')));
}
add_action('wp_ajax_ackm_chatassist_track', 'ackm_chatassist_track_analytics');
add_action('wp_ajax_nopriv_ackm_chatassist_track', 'ackm_chatassist_track_analytics');

/**
 * AJAX: Attribute WooCommerce order to chat (session-based)
 */
function ackm_chatassist_attr_order() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ackm_chatassist_attr_order')) {
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
    $order->update_meta_data('_ackm_chatassist_attributed', '1');
    $order->save();
    wp_send_json_success();
}
add_action('wp_ajax_ackm_chatassist_attr_order', 'ackm_chatassist_attr_order');
add_action('wp_ajax_nopriv_ackm_chatassist_attr_order', 'ackm_chatassist_attr_order');

/**
 * WooCommerce: On order-received, if chat cookie exists, attribute order
 */
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id || get_option('ackm_chatassist_conversion_tracking', 'no') !== 'yes') {
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
    $nonce     = wp_create_nonce('ackm_chatassist_attr_order');
    $order_key = $order->get_order_key();
    $js        = '(function(){if(document.cookie.indexOf("ackm_chatassist_opened=") === -1)return;';
    $js       .= 'var x=new XMLHttpRequest();';
    $js       .= 'x.open("POST",' . wp_json_encode($ajax_url) . ',true);';
    $js       .= 'x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");';
    $js       .= 'x.send("action=ackm_chatassist_attr_order';
    $js       .= '&order_id=' . absint($order_id);
    $js       .= '&order_key=' . rawurlencode($order_key);
    $js       .= '&nonce=' . rawurlencode($nonce) . '");';
    $js       .= '})();';
    wp_register_script('ackm-chatassist-attr', false, array(), ACKM_CHATASSIST_VERSION, true);
    wp_enqueue_script('ackm-chatassist-attr');
    wp_add_inline_script('ackm-chatassist-attr', $js);
}, 5);
