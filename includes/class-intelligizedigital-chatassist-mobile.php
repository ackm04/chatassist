<?php
/**
 * Mobile: PWA support, app-like experience
 *
 * @package Intelligize_ChatAssist
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class IntelligizeDigital_ChatAssist_Mobile {

    public static function init() {
        add_action('wp_head', array(__CLASS__, 'output_meta_tags'), 5);
        add_action('wp_head', array(__CLASS__, 'output_manifest_link'), 5);
        add_action('template_redirect', array(__CLASS__, 'maybe_serve_manifest'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_sw_registration'), 99);
    }

    public static function is_enabled() {
        return get_option('intelligizedigital_chatassist_pwa_enabled', 'no') === 'yes';
    }

    public static function output_meta_tags() {
        if (!self::is_enabled()) {
            return;
        }
        $color = get_option('intelligizedigital_chatassist_color', '#667eea');
        ?>
        <meta name="theme-color" content="<?php echo esc_attr($color); ?>" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <?php
    }

    public static function output_manifest_link() {
        if (!self::is_enabled()) {
            return;
        }
        $url = add_query_arg('intelligizedigital_chatassist_manifest', '1', home_url('/'));
        ?>
        <link rel="manifest" href="<?php echo esc_url($url); ?>" />
        <?php
    }

    public static function maybe_serve_manifest() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public manifest endpoint, no form submission
        $manifest_param = isset($_GET['intelligizedigital_chatassist_manifest']) ? sanitize_text_field(wp_unslash($_GET['intelligizedigital_chatassist_manifest'])) : '';
        if ($manifest_param !== '1') {
            return;
        }
        $name = get_bloginfo('name');
        $color = get_option('intelligizedigital_chatassist_color', '#667eea');
        $icon = get_site_icon_url(192);
        if (empty($icon)) {
            $icon = get_option('intelligizedigital_chatassist_svg_icon', '');
        }
        if (empty($icon)) {
            $icon = includes_url('images/w-logo-blue.png');
        }
        $manifest = array(
            'name' => $name,
            'short_name' => $name,
            'start_url' => home_url('/'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $color,
            'icons' => array(
                array('src' => $icon, 'sizes' => '192x192', 'type' => 'image/png'),
            ),
        );
        header('Content-Type: application/json');
        echo wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function enqueue_sw_registration() {
        if (!self::is_enabled() || !IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
            return;
        }
        $sw_url      = INTELLIGIZEDIGITAL_CHATASSIST_URL . 'assets/sw.js';
        $push_enabled = class_exists('IntelligizeDigital_ChatAssist_Push') && IntelligizeDigital_ChatAssist_Push::is_enabled();
        $vapid       = $push_enabled ? IntelligizeDigital_ChatAssist_Push::get_vapid_public() : '';

        $js  = '(function(){if(!("serviceWorker" in navigator))return;';
        $js .= 'navigator.serviceWorker.register(' . wp_json_encode($sw_url) . ').then(function(reg){';
        if ($push_enabled && !empty($vapid)) {
            $js .= 'window.intelligizedigitalChatAssistPush={reg:reg,vapid:' . wp_json_encode($vapid) . ',';
            $js .= 'ajaxUrl:' . wp_json_encode(admin_url('admin-ajax.php')) . ',';
            $js .= 'nonce:' . wp_json_encode(wp_create_nonce('intelligizedigital_chatassist_ajax_nonce')) . '};';
        }
        $js .= '}).catch(function(){});})();';

        wp_register_script('intelligizedigital-chatassist-sw', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-sw');
        wp_add_inline_script('intelligizedigital-chatassist-sw', $js);
    }
}
