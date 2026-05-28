<?php
/**
 * Display rules for ChatAssist
 *
 * @package Ackm_ChatAssist
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChatAssist Display Rules Class
 */
class Ackm_ChatAssist_Display_Rules {

    /**
     * Check if widget should be displayed
     *
     * @since 1.0.0
     * @return bool True if should display, false otherwise.
     */
    public static function should_display() {
        // Check if widget is enabled
        if (get_option('ackm_chatassist_enabled') !== 'yes') {
            return false;
        }

        // Check if URL is set (or we have a matching widget profile with URL)
        $url = get_option('ackm_chatassist_url', '');
        if (empty($url) && class_exists('Ackm_ChatAssist_Widget_Profiles') && Ackm_ChatAssist_Widget_Profiles::is_enabled()) {
            $profile = Ackm_ChatAssist_Widget_Profiles::get_active_profile();
            if ($profile && !empty($profile['url'])) {
                $url = $profile['url'];
            }
        }
        if (empty($url)) {
            return false;
        }

        // Check page targeting rules
        if (!self::check_page_rules()) {
            return false;
        }

        // Check time-based rules
        if (!self::check_time_rules()) {
            return false;
        }

        // Check user role rules
        if (!self::check_user_role_rules()) {
            return false;
        }

        // Check extended rules (day, device, geo, WooCommerce) if class exists
        if (class_exists('Ackm_ChatAssist_Extended_Rules')) {
            if (!Ackm_ChatAssist_Extended_Rules::check_day_rules()) {
                return false;
            }
            if (!Ackm_ChatAssist_Extended_Rules::check_device_rules()) {
                return false;
            }
            if (!Ackm_ChatAssist_Extended_Rules::check_geo_rules()) {
                return false;
            }
            if (!Ackm_ChatAssist_Extended_Rules::check_woocommerce_rules()) {
                return false;
            }
        }

        // Allow filtering
        return apply_filters('ackm_chatassist_should_display', true);
    }

    /**
     * Check page targeting rules
     *
     * @since 1.0.0
     * @return bool True if should display on current page.
     */
    private static function check_page_rules() {
        $show_on = get_option('ackm_chatassist_show_on', 'all');

        if ($show_on === 'all') {
            return true;
        }

        if ($show_on === 'homepage' && is_front_page()) {
            return true;
        }

        if ($show_on === 'posts' && is_single() && get_post_type() === 'post') {
            return true;
        }

        if ($show_on === 'pages' && is_page()) {
            return true;
        }

        // Check exclude pages
        $exclude_pages = get_option('ackm_chatassist_exclude_pages', '');
        if (!empty($exclude_pages)) {
            $exclude_ids = array_map('intval', explode(',', $exclude_pages));
            if (is_page($exclude_ids) || is_single($exclude_ids)) {
                return false;
            }
        }

        // Check include pages
        $include_pages = get_option('ackm_chatassist_include_pages', '');
        if (!empty($include_pages)) {
            $include_ids = array_map('intval', explode(',', $include_pages));
            if (is_page($include_ids) || is_single($include_ids)) {
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Check time-based rules
     *
     * @since 1.0.0
     * @return bool True if should display at current time.
     */
    private static function check_time_rules() {
        $time_based = get_option('ackm_chatassist_time_based', 'no');

        if ($time_based !== 'yes') {
            return true;
        }

        $start_time = get_option('ackm_chatassist_start_time', '09:00');
        $end_time = get_option('ackm_chatassist_end_time', '17:00');
        $timezone = get_option('timezone_string', 'UTC');

        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        try {
            $timezone_obj = new DateTimeZone($timezone);
            $current_time = new DateTime('now', $timezone_obj);
            
            $start = DateTime::createFromFormat('H:i', $start_time, $timezone_obj);
            $end = DateTime::createFromFormat('H:i', $end_time, $timezone_obj);

            if (!$start || !$end) {
                return true;
            }

            $current_hour_minute = $current_time->format('H:i');

            if ($start_time > $end_time) {
                return ($current_hour_minute >= $start_time || $current_hour_minute <= $end_time);
            }

            return ($current_hour_minute >= $start_time && $current_hour_minute <= $end_time);
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Check user role rules
     *
     * @since 1.0.0
     * @return bool True if should display for current user.
     */
    private static function check_user_role_rules() {
        $role_based = get_option('ackm_chatassist_role_based', 'no');

        if ($role_based !== 'yes') {
            return true;
        }

        $show_to_roles = get_option('ackm_chatassist_show_to_roles', array());

        if (empty($show_to_roles)) {
            return true;
        }

        if (!is_user_logged_in()) {
            return in_array('guest', $show_to_roles, true);
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;

        foreach ($user_roles as $role) {
            if (in_array($role, $show_to_roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available display rule options
     *
     * @since 1.0.0
     * @return array Display rule options.
     */
    public static function get_display_options() {
        return array(
            'all' => __('All Pages', 'intelligizedigital-chatassist'),
            'homepage' => __('Homepage Only', 'intelligizedigital-chatassist'),
            'posts' => __('Posts Only', 'intelligizedigital-chatassist'),
            'pages' => __('Pages Only', 'intelligizedigital-chatassist'),
        );
    }
}
