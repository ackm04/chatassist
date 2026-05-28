<?php
/**
 * Extended display rules for Intelligize ChatAssist
 *
 * @package Intelligize_ChatAssist
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extended Display Rules - Day of week, device, geo, WooCommerce
 */
class IntelligizeDigital_ChatAssist_Extended_Rules {

    /**
     * Check day-of-week rules
     */
    public static function check_day_rules() {
        $day_based = get_option('intelligizedigital_chatassist_day_based', 'no');
        if ($day_based !== 'yes') {
            return true;
        }
        $days = get_option('intelligizedigital_chatassist_show_days', array());
        if (empty($days)) {
            return true;
        }
        $timezone = get_option('timezone_string', 'UTC');
        if (empty($timezone)) {
            $timezone = 'UTC';
        }
        try {
            $now = new DateTime('now', new DateTimeZone($timezone));
            $current_day = strtolower($now->format('l'));
            return in_array($current_day, $days, true);
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Check device rules (desktop/mobile/tablet)
     */
    public static function check_device_rules() {
        $device_based = get_option('intelligizedigital_chatassist_device_based', 'no');
        if ($device_based !== 'yes') {
            return true;
        }
        $devices = get_option('intelligizedigital_chatassist_show_on_devices', array('desktop', 'mobile', 'tablet'));
        if (empty($devices)) {
            return true;
        }
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $is_mobile = wp_is_mobile();
        $is_tablet = (bool) preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $user_agent);
        $is_desktop = !$is_mobile && !$is_tablet;

        if ($is_tablet && in_array('tablet', $devices, true)) {
            return true;
        }
        if ($is_mobile && !$is_tablet && in_array('mobile', $devices, true)) {
            return true;
        }
        if ($is_desktop && in_array('desktop', $devices, true)) {
            return true;
        }
        return false;
    }

    /**
     * Check geo rules (country - requires client-side or external service)
     */
    public static function check_geo_rules() {
        $geo_based = get_option('intelligizedigital_chatassist_geo_based', 'no');
        if ($geo_based !== 'yes') {
            return true;
        }
        $countries = get_option('intelligizedigital_chatassist_show_countries', array());
        if (empty($countries)) {
            return true;
        }
        $user_country = self::get_user_country();
        if (empty($user_country)) {
            return true;
        }
        return in_array($user_country, $countries, true);
    }

    /**
     * Get user country from cookie (set by frontend JS) or header
     */
    private static function get_user_country() {
        if (isset($_COOKIE['intelligizedigital_chatassist_country'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['intelligizedigital_chatassist_country']));
        }
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']));
        }
        return '';
    }

    /**
     * Check WooCommerce page rules - show only on selected WooCommerce pages
     */
    public static function check_woocommerce_rules() {
        $woo_pages = get_option('intelligizedigital_chatassist_woo_pages', array());
        if (empty($woo_pages) || !function_exists('is_woocommerce')) {
            return true;
        }
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return true;
        }
        if (is_shop() && in_array('shop', $woo_pages, true)) {
            return true;
        }
        if (is_product() && in_array('product', $woo_pages, true)) {
            return true;
        }
        if (is_cart() && in_array('cart', $woo_pages, true)) {
            return true;
        }
        if (is_checkout() && in_array('checkout', $woo_pages, true)) {
            return true;
        }
        if (is_account_page() && in_array('account', $woo_pages, true)) {
            return true;
        }
        return false;
    }

    /**
     * Get available days
     */
    public static function get_days() {
        return array(
            'monday' => __('Monday', 'intelligizedigital-chatassist'),
            'tuesday' => __('Tuesday', 'intelligizedigital-chatassist'),
            'wednesday' => __('Wednesday', 'intelligizedigital-chatassist'),
            'thursday' => __('Thursday', 'intelligizedigital-chatassist'),
            'friday' => __('Friday', 'intelligizedigital-chatassist'),
            'saturday' => __('Saturday', 'intelligizedigital-chatassist'),
            'sunday' => __('Sunday', 'intelligizedigital-chatassist'),
        );
    }
}
