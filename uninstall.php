<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Ackm_ChatAssist
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
$ackm_chatassist_options = array(
    'ackm_chatassist_url',
    'ackm_chatassist_enabled',
    'ackm_chatassist_position',
    'ackm_chatassist_title',
    'ackm_chatassist_color',
    'ackm_chatassist_icon',
    'ackm_chatassist_icon_type',
    'ackm_chatassist_svg_icon',
    'ackm_chatassist_zoom',
    'ackm_chatassist_show_on',
    'ackm_chatassist_include_pages',
    'ackm_chatassist_exclude_pages',
    'ackm_chatassist_time_based',
    'ackm_chatassist_start_time',
    'ackm_chatassist_end_time',
    'ackm_chatassist_role_based',
    'ackm_chatassist_show_to_roles',
    'ackm_chatassist_analytics_enabled',
    'ackm_chatassist_analytics',
    'ackm_chatassist_multi_widgets',
    'ackm_chatassist_widget_profiles',
    'ackm_chatassist_native_integration',
    'ackm_chatassist_integration_replace_widget',
    'ackm_chatassist_intercom_app_id',
    'ackm_chatassist_crisp_site_id',
    'ackm_chatassist_drift_id',
    'ackm_chatassist_tawk_id',
    'ackm_chatassist_tawk_key',
    'ackm_chatassist_livechat_license',
    'ackm_chatassist_goals',
    'ackm_chatassist_goal_custom',
    'ackm_chatassist_ab_variant_stats',
    'ackm_chatassist_coupon_code',
    'ackm_chatassist_newsletter_enabled',
    'ackm_chatassist_newsletter_title',
    'ackm_chatassist_newsletter_cta',
    'ackm_chatassist_newsletter_webhook',
    'ackm_chatassist_pwa_enabled',
    'ackm_chatassist_push_enabled',
    'ackm_chatassist_vapid_public',
    'ackm_chatassist_vapid_private',
    'ackm_chatassist_push_subscriptions',
);

foreach ( $ackm_chatassist_options as $ackm_chatassist_option ) {
    delete_option( $ackm_chatassist_option );
}

// Also clean up legacy option keys from before the v4.0.4 prefix rename,
// in case the migration step never ran on this install.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'intelligizedigital_chatassist_%'" );

// Remove user meta for dismissed notice (new + legacy keys).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('ackm_chatassist_config_notice_dismissed', 'intelligizedigital_chatassist_config_notice_dismissed')" );

// Remove migration flag.
delete_option( 'ackm_chatassist_migrated_v404' );

// Remove capabilities from every role that had them.
foreach ( wp_roles()->roles as $role_slug => $role_info ) {
    $role = get_role( $role_slug );
    if ( $role ) {
        $role->remove_cap( 'manage_ackm_chatassist' );
        $role->remove_cap( 'manage_intelligizedigital_chatassist' );
    }
}
