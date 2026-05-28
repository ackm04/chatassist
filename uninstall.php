<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package IntelligizeDigital_ChatAssist
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
$intelligizedigital_chatassist_options = array(
    'intelligizedigital_chatassist_url',
    'intelligizedigital_chatassist_enabled',
    'intelligizedigital_chatassist_position',
    'intelligizedigital_chatassist_title',
    'intelligizedigital_chatassist_color',
    'intelligizedigital_chatassist_icon',
    'intelligizedigital_chatassist_icon_type',
    'intelligizedigital_chatassist_svg_icon',
    'intelligizedigital_chatassist_zoom',
    'intelligizedigital_chatassist_show_on',
    'intelligizedigital_chatassist_include_pages',
    'intelligizedigital_chatassist_exclude_pages',
    'intelligizedigital_chatassist_time_based',
    'intelligizedigital_chatassist_start_time',
    'intelligizedigital_chatassist_end_time',
    'intelligizedigital_chatassist_role_based',
    'intelligizedigital_chatassist_show_to_roles',
    'intelligizedigital_chatassist_analytics_enabled',
    'intelligizedigital_chatassist_analytics',
    'intelligizedigital_chatassist_multi_widgets',
    'intelligizedigital_chatassist_widget_profiles',
    'intelligizedigital_chatassist_native_integration',
    'intelligizedigital_chatassist_integration_replace_widget',
    'intelligizedigital_chatassist_intercom_app_id',
    'intelligizedigital_chatassist_crisp_site_id',
    'intelligizedigital_chatassist_drift_id',
    'intelligizedigital_chatassist_tawk_id',
    'intelligizedigital_chatassist_tawk_key',
    'intelligizedigital_chatassist_livechat_license',
    'intelligizedigital_chatassist_goals',
    'intelligizedigital_chatassist_goal_custom',
    'intelligizedigital_chatassist_ab_variant_stats',
    'intelligizedigital_chatassist_coupon_code',
    'intelligizedigital_chatassist_newsletter_enabled',
    'intelligizedigital_chatassist_newsletter_title',
    'intelligizedigital_chatassist_newsletter_cta',
    'intelligizedigital_chatassist_newsletter_webhook',
    'intelligizedigital_chatassist_pwa_enabled',
    'intelligizedigital_chatassist_push_enabled',
    'intelligizedigital_chatassist_vapid_public',
    'intelligizedigital_chatassist_vapid_private',
    'intelligizedigital_chatassist_push_subscriptions',
);

foreach ( $intelligizedigital_chatassist_options as $intelligizedigital_chatassist_option ) {
    delete_option( $intelligizedigital_chatassist_option );
}

// Remove user meta for dismissed notice.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'intelligizedigital_chatassist_config_notice_dismissed'" );

// Remove capability from administrator role.
$role = get_role( 'administrator' );
if ( $role ) {
    $role->remove_cap( 'manage_intelligizedigital_chatassist' );
}
