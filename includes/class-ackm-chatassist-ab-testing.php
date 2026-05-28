<?php
/**
 * A/B Testing for ChatAssist
 *
 * @package Ackm_ChatAssist
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A/B test different chat URLs or variants
 */
class Ackm_ChatAssist_AB_Testing {

    public static function init() {
        add_filter('ackm_chatassist_chat_url', array(__CLASS__, 'get_variant_url'), 10, 1);
        add_filter('ackm_chatassist_widget_color', array(__CLASS__, 'get_variant_color'), 10, 1);
    }

    public static function is_enabled() {
        return get_option('ackm_chatassist_ab_testing', 'no') === 'yes';
    }

    private static $cached_variant = null;

    private static function get_user_variant() {
        if (self::$cached_variant !== null) {
            return self::$cached_variant;
        }
        if (!self::is_enabled()) {
            self::$cached_variant = 'control';
            return 'control';
        }
        if (isset($_COOKIE['ackm_chatassist_variant'])) {
            self::$cached_variant = sanitize_text_field(wp_unslash($_COOKIE['ackm_chatassist_variant']));
            return self::$cached_variant;
        }
        $variants = get_option('ackm_chatassist_ab_variants', array());
        if (empty($variants)) {
            self::$cached_variant = 'control';
            return 'control';
        }
        $weights = array();
        foreach ($variants as $v) {
            $weights[$v['id']] = isset($v['weight']) ? intval($v['weight']) : 50;
        }
        $total = array_sum($weights);
        $rand = wp_rand(1, max(1, $total));
        foreach ($weights as $id => $w) {
            $rand -= $w;
            if ($rand <= 0) {
                self::$cached_variant = $id;
                return $id;
            }
        }
        self::$cached_variant = 'control';
        return 'control';
    }

    public static function get_variant_url($url) {
        if (!self::is_enabled()) {
            return $url;
        }
        $variant = self::get_user_variant();
        $variants = get_option('ackm_chatassist_ab_variants', array());
        foreach ($variants as $v) {
            if (isset($v['id']) && $v['id'] === $variant && !empty($v['url'])) {
                return $v['url'];
            }
        }
        return $url;
    }

    public static function get_variant_color($color) {
        if (!self::is_enabled()) {
            return $color;
        }
        $variant = self::get_user_variant();
        $variants = get_option('ackm_chatassist_ab_variants', array());
        foreach ($variants as $v) {
            if (isset($v['id']) && $v['id'] === $variant && !empty($v['color'])) {
                return $v['color'];
            }
        }
        return $color;
    }

    public static function get_variant_for_js() {
        if (!self::is_enabled()) {
            return 'control';
        }
        return self::get_user_variant();
    }
}
