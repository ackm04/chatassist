<?php
/**
 * REST API for Intelligize ChatAssist
 *
 * @package Intelligize_ChatAssist
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API endpoints
 */
class IntelligizeDigital_ChatAssist_REST_API {

    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes() {
        register_rest_route('intelligizedigital-chatassist/v1', '/settings', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_settings'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        register_rest_route('intelligizedigital-chatassist/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_analytics'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            'args' => array(
                'days' => array(
                    'default' => 30,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));

        register_rest_route('intelligizedigital-chatassist/v1', '/analytics/export', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_analytics'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            'args' => array(
                'format' => array(
                    'default' => 'csv',
                    'enum' => array('csv', 'json'),
                ),
            ),
        ));
    }

    public static function check_admin_permission() {
        return current_user_can('manage_intelligizedigital_chatassist') || current_user_can('manage_options');
    }

    public static function get_settings($request) {
        $settings = array(
            'enabled' => get_option('intelligizedigital_chatassist_enabled', 'yes'),
            'url' => esc_url_raw( get_option('intelligizedigital_chatassist_url', '') ),
            'position' => get_option('intelligizedigital_chatassist_position', 'right'),
            'title' => get_option('intelligizedigital_chatassist_title', 'Chat Support'),
            'color' => get_option('intelligizedigital_chatassist_color', '#667eea'),
        );
        return rest_ensure_response($settings);
    }

    public static function get_analytics($request) {
        $days = $request->get_param('days');
        $data = IntelligizeDigital_ChatAssist_Analytics::get_data($days);
        return rest_ensure_response($data);
    }

    public static function export_analytics($request) {
        $format = $request->get_param('format');
        $data = IntelligizeDigital_ChatAssist_Analytics::export($format);
        if ($format === 'csv') {
            return new WP_REST_Response($data, 200, array(
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="chatassist-analytics-' . gmdate('Y-m-d') . '.csv"',
            ));
        }
        return rest_ensure_response($data);
    }
}
