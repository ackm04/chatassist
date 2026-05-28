<?php
/**
 * Multiple Widget Profiles for ChatAssist
 *
 * @package Ackm_ChatAssist
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget profiles - different chat URLs per page/post
 */
class Ackm_ChatAssist_Widget_Profiles {

    public static function init() {
        add_filter('ackm_chatassist_chat_url', array(__CLASS__, 'get_profile_url'), 5, 1);
        add_filter('ackm_chatassist_widget_position', array(__CLASS__, 'get_profile_position'), 5, 1);
        add_filter('ackm_chatassist_widget_title', array(__CLASS__, 'get_profile_title'), 5, 1);
        add_filter('ackm_chatassist_widget_color', array(__CLASS__, 'get_profile_color'), 5, 1);
    }

    public static function is_enabled() {
        return get_option('ackm_chatassist_multi_widgets', 'no') === 'yes';
    }

    public static function get_profiles() {
        $profiles = get_option('ackm_chatassist_widget_profiles', array());
        return is_array($profiles) ? $profiles : array();
    }

    /**
     * Get the active profile for current page
     */
    public static function get_active_profile() {
        if (!self::is_enabled()) {
            return null;
        }
        $profiles = self::get_profiles();
        if (empty($profiles)) {
            return null;
        }
        $current_page_id = get_queried_object_id();
        $current_post_type = get_post_type();
        $is_front = is_front_page();
        $is_shop = function_exists('is_shop') && is_shop();
        $is_product = function_exists('is_product') && is_product();
        $is_cart = function_exists('is_cart') && is_cart();
        $is_checkout = function_exists('is_checkout') && is_checkout();

        foreach ($profiles as $profile) {
            if (empty($profile['url'])) {
                continue;
            }
            $pages = isset($profile['pages']) ? array_map('intval', array_filter((array) $profile['pages'])) : array();
            $exclude = isset($profile['exclude_pages']) ? array_map('intval', array_filter((array) $profile['exclude_pages'])) : array();
            $post_types = isset($profile['post_types']) ? (array) $profile['post_types'] : array();
            $conditions = isset($profile['conditions']) ? (array) $profile['conditions'] : array();

            if (!empty($exclude) && $current_page_id > 0 && in_array($current_page_id, $exclude, true)) {
                continue;
            }
            $match = false;
            if (!empty($pages) && in_array($current_page_id, $pages, true)) {
                $match = true;
            }
            if ($is_front && in_array('homepage', $conditions, true)) {
                $match = true;
            }
            if ($is_shop && in_array('shop', $conditions, true)) {
                $match = true;
            }
            if ($is_product && in_array('product', $conditions, true)) {
                $match = true;
            }
            if ($is_cart && in_array('cart', $conditions, true)) {
                $match = true;
            }
            if ($is_checkout && in_array('checkout', $conditions, true)) {
                $match = true;
            }
            if (!empty($post_types) && in_array($current_post_type, $post_types, true)) {
                $match = true;
            }
            if ($match) {
                return $profile;
            }
        }
        return null;
    }

    public static function get_profile_url($url) {
        $profile = self::get_active_profile();
        return $profile && !empty($profile['url']) ? $profile['url'] : $url;
    }

    public static function get_profile_position($pos) {
        $profile = self::get_active_profile();
        return $profile && !empty($profile['position']) ? $profile['position'] : $pos;
    }

    public static function get_profile_title($title) {
        $profile = self::get_active_profile();
        return $profile && !empty($profile['title']) ? $profile['title'] : $title;
    }

    public static function get_profile_color($color) {
        $profile = self::get_active_profile();
        return $profile && !empty($profile['color']) ? $profile['color'] : $color;
    }

    public static function get_profile_id() {
        $profile = self::get_active_profile();
        return $profile && !empty($profile['id']) ? $profile['id'] : 'default';
    }
}
