<?php
/**
 * The admin-specific functionality of the plugin.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class Ackm_ChatAssist_Admin {

    /**
     * Initialize the class and set up settings.
     */
    public function init() {
        // Change hook priority for admin_menu to make it run earlier
        add_action('admin_menu', array($this, 'add_settings_page'), 9);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add an admin notice to make settings more visible
        add_action('admin_notices', array($this, 'admin_notice'));
        
        // AJAX handler for dismissing the config notice
        add_action('wp_ajax_ackm_chatassist_dismiss_notice', array($this, 'ajax_dismiss_notice'));
        
        // Add script for persistent notice dismissal on dashboard
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dismiss_script'));
        
        // Add media uploader scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_uploader'));
        
        // Handle settings update
        add_action('admin_init', array($this, 'handle_settings_update'));
        add_action('admin_init', array($this, 'handle_analytics_export'));
    }

    /**
     * Handle analytics CSV export
     */
    public function handle_analytics_export() {
        if (!isset($_GET['ackm_chatassist_export'])) {
            return;
        }
        $format = sanitize_text_field(wp_unslash($_GET['ackm_chatassist_export']));
        if (!in_array($format, array('csv', 'pdf'))) {
            return;
        }
        if (!current_user_can('manage_ackm_chatassist') && !current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'intelligizedigital-chatassist'));
        }
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ackm_chatassist_export')) {
            wp_die(esc_html__('Security check failed', 'intelligizedigital-chatassist'));
        }
        if ($format === 'csv') {
            $csv = Ackm_ChatAssist_Analytics::export('csv');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="chatassist-analytics-' . gmdate('Y-m-d') . '.csv"');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV export from plugin data
            echo $csv;
        } else {
            $html = Ackm_ChatAssist_Analytics::export_pdf();
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="chatassist-analytics-' . gmdate('Y-m-d') . '.html"');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML export from plugin data
            echo $html;
        }
        exit;
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_ackm_chatassist' !== $hook) {
            return;
        }
        
        // Add WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Add custom admin styles
        wp_enqueue_style('ackm-chatassist-admin-css', ACKM_CHATASSIST_URL . 'admin/css/ackm-chatassist-admin.css', array(), ACKM_CHATASSIST_VERSION);
        
        // Add tabs functionality FIRST so it runs before other scripts that might error
        wp_enqueue_script('ackm-chatassist-tabs-js', ACKM_CHATASSIST_URL . 'admin/js/ackm-chatassist-tabs.js', array('jquery'), ACKM_CHATASSIST_VERSION, true);
        wp_add_inline_script('ackm-chatassist-tabs-js', "
            jQuery(document).on('click', '.ackm-chatassist-tab', function() {
                var tabId = jQuery(this).data('tab');
                if (!tabId) return;
                jQuery('.ackm-chatassist-tab').removeClass('active');
                jQuery('.ackm-chatassist-tab-content').removeClass('active');
                jQuery(this).addClass('active');
                jQuery('#tab-' + tabId).addClass('active');
            });
        ");
        
        // Add custom admin script
        wp_enqueue_script('ackm-chatassist-admin-js', ACKM_CHATASSIST_URL . 'admin/js/ackm-chatassist-admin.js', array('jquery', 'wp-color-picker'), ACKM_CHATASSIST_VERSION, true);
        
        // Add preview script
        wp_enqueue_script('ackm-chatassist-preview-js', ACKM_CHATASSIST_URL . 'admin/js/ackm-chatassist-preview.js', array('jquery'), ACKM_CHATASSIST_VERSION, true);
        // Chart.js for analytics (bundled locally for WordPress.org compliance)
        wp_enqueue_script('chart-js', ACKM_CHATASSIST_URL . 'admin/js/chart.umd.min.js', array(), '4.4.6', true);
        
        // Add extra admin functionality
        wp_enqueue_script('ackm-chatassist-admin-extra-js', ACKM_CHATASSIST_URL . 'admin/js/ackm-chatassist-admin-extra.js', array('jquery', 'wp-color-picker'), ACKM_CHATASSIST_VERSION, true);
        
        // Localize script with translated strings
        wp_localize_script('ackm-chatassist-admin-js', 'ackmChatAssistSettings', array(
            'positionTemplate' => /* translators: %s: position of the chat button (left or right) */ esc_html__('This chat button will appear in the bottom %s corner of your website.', 'intelligizedigital-chatassist'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ackm_chatassist_admin_nonce')
        ));
        
        // Add zoom animations via wp_add_inline_style
        $zoom_animations = "
            @keyframes ackm-chatassist-zoom-in {
                from {transform: scale(0.5); opacity: 0;}
                to {transform: scale(1); opacity: 1;}
            }
            
            @keyframes ackm-chatassist-zoom-out {
                from {transform: scale(1); opacity: 1;}
                to {transform: scale(0.5); opacity: 0;}
            }
            
            @keyframes ackm-chatassist-fade-in {
                from {opacity: 0;}
                to {opacity: 1;}
            }
            
            @keyframes ackm-chatassist-fade-out {
                from {opacity: 1;}
                to {opacity: 0;}
            }
        ";
        wp_add_inline_style('ackm-chatassist-admin-css', $zoom_animations);
    }

    /**
     * Enqueue script for persistent notice dismissal on dashboard
     */
    public function enqueue_dismiss_script($hook) {
        if ('index.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ackm-chatassist-dismiss', '', array('jquery'), ACKM_CHATASSIST_VERSION, true);
        wp_add_inline_script('ackm-chatassist-dismiss', sprintf(
            'jQuery(function($){$(document).on(\'click\',\'.notice[data-notice="ackm-chatassist-config"] .notice-dismiss\',function(){$.post(ajaxurl,{action:\'ackm_chatassist_dismiss_notice\',nonce:\'%s\'})})});',
            wp_create_nonce('ackm_chatassist_dismiss')
        ));
    }

    /**
     * Enqueue media uploader scripts
     */
    public function enqueue_media_uploader($hook) {
        if ('toplevel_page_ackm_chatassist' !== $hook) {
            return;
        }
        
        wp_enqueue_media();
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_settings_page() {
        // Only add as a top-level menu for maximum visibility
        add_menu_page(
            __('ChatAssist by Ajay', 'intelligizedigital-chatassist'),
            __('ChatAssist', 'intelligizedigital-chatassist'),
            'manage_ackm_chatassist',
            'ackm_chatassist',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            81
        );
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting('ackm_chatassist_options', 'ackm_chatassist_url', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_enabled', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => 'yes',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_position', array(
            'sanitize_callback' => array($this, 'sanitize_position'),
            'default' => 'right',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_title', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Chat Support',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#667eea',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_icon', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '💬',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_icon_type', array(
            'sanitize_callback' => array($this, 'sanitize_icon_type'),
            'default' => 'emoji',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_svg_icon', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_zoom', array(
            'sanitize_callback' => array($this, 'sanitize_zoom'),
            'default' => '100',
        ));
        
        // Display rules settings
        register_setting('ackm_chatassist_options', 'ackm_chatassist_show_on', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'all',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_include_pages', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_exclude_pages', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_time_based', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => 'no',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_start_time', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '09:00',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_end_time', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '17:00',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_role_based', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => 'no',
        ));
        
        register_setting('ackm_chatassist_options', 'ackm_chatassist_show_to_roles', array(
            'sanitize_callback' => array($this, 'sanitize_array'),
            'default' => array('guest'),
        ));
        
        // Analytics settings
        register_setting('ackm_chatassist_options', 'ackm_chatassist_analytics_enabled', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => 'yes',
        ));

        // Triggers
        register_setting('ackm_chatassist_options', 'ackm_chatassist_delay_seconds', array('sanitize_callback' => 'absint', 'default' => '0'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_scroll_depth', array('sanitize_callback' => 'absint', 'default' => '0'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_exit_intent', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));

        // Engagement
        register_setting('ackm_chatassist_options', 'ackm_chatassist_proactive_message', array('sanitize_callback' => 'sanitize_textarea_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_proactive_delay', array('sanitize_callback' => 'absint', 'default' => '10'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_pre_chat_form', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_pre_chat_fields', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'name,email'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_unread_badge', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));

        // Appearance
        register_setting('ackm_chatassist_options', 'ackm_chatassist_theme', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'light'));

        // GDPR
        register_setting('ackm_chatassist_options', 'ackm_chatassist_gdpr_consent', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_gdpr_message', array('sanitize_callback' => 'sanitize_textarea_field', 'default' => ''));

        // Extended display rules
        register_setting('ackm_chatassist_options', 'ackm_chatassist_day_based', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_show_days', array('sanitize_callback' => array($this, 'sanitize_array'), 'default' => array()));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_device_based', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_show_on_devices', array('sanitize_callback' => array($this, 'sanitize_array'), 'default' => array('desktop', 'mobile', 'tablet')));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_geo_based', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_show_countries', array('sanitize_callback' => array($this, 'sanitize_array'), 'default' => array()));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_woo_pages', array('sanitize_callback' => array($this, 'sanitize_array'), 'default' => array()));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_webhook_url', array('sanitize_callback' => 'esc_url_raw', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_slack_webhook', array('sanitize_callback' => 'esc_url_raw', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_discord_webhook', array('sanitize_callback' => 'esc_url_raw', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_slack_notify_opens', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_slack_notify_messages', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_slack_notify_leads', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_discord_notify_messages', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_discord_notify_leads', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_crm_webhook', array('sanitize_callback' => 'esc_url_raw', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_crm_format', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'hubspot'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_ab_testing', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_ab_variants', array('sanitize_callback' => array($this, 'sanitize_ab_variants'), 'default' => array()));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_layout', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'popup'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_typing_indicator', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_sound_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_pre_chat_to_url', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'yes'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_conversion_tracking', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_heatmap_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));

        // Widget profiles (multiple chat widgets)
        register_setting('ackm_chatassist_options', 'ackm_chatassist_multi_widgets', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_widget_profiles', array('sanitize_callback' => array($this, 'sanitize_widget_profiles'), 'default' => array()));

        // Direct chat integrations
        register_setting('ackm_chatassist_options', 'ackm_chatassist_native_integration', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_integration_replace_widget', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'yes'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_intercom_app_id', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_crisp_site_id', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_drift_id', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_tawk_id', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_tawk_key', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_livechat_license', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));

        register_setting('ackm_chatassist_options', 'ackm_chatassist_goals', array('sanitize_callback' => array($this, 'sanitize_goals'), 'default' => array()));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_coupon_code', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_newsletter_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_newsletter_title', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_newsletter_cta', array('sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_newsletter_webhook', array('sanitize_callback' => 'esc_url_raw', 'default' => ''));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_newsletter_timing', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'on_chat_open'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_pwa_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));
        register_setting('ackm_chatassist_options', 'ackm_chatassist_push_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => 'no'));

        add_settings_section(
            'ackm_chatassist_general',
            __('General Settings', 'intelligizedigital-chatassist'),
            array($this, 'render_section_info'),
            'intelligizedigital-chatassist'
        );

        add_settings_field(
            'ackm_chatassist_url',
            __('Chat URL', 'intelligizedigital-chatassist'),
            array($this, 'render_url_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );

        add_settings_field(
            'ackm_chatassist_enabled',
            __('Enable Chat Widget', 'intelligizedigital-chatassist'),
            array($this, 'render_enabled_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );

        add_settings_field(
            'ackm_chatassist_position',
            __('Widget Position', 'intelligizedigital-chatassist'),
            array($this, 'render_position_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );
        
        add_settings_field(
            'ackm_chatassist_title',
            __('Chat Widget Title', 'intelligizedigital-chatassist'),
            array($this, 'render_title_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );
        
        add_settings_field(
            'ackm_chatassist_color',
            __('Widget Color', 'intelligizedigital-chatassist'),
            array($this, 'render_color_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );
        
        add_settings_field(
            'ackm_chatassist_icon_settings',
            __('Chat Icon', 'intelligizedigital-chatassist'),
            array($this, 'render_icon_settings_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );
        
        add_settings_field(
            'ackm_chatassist_zoom',
            __('Chat Content Zoom', 'intelligizedigital-chatassist'),
            array($this, 'render_zoom_field'),
            'intelligizedigital-chatassist',
            'ackm_chatassist_general'
        );
    }

    /**
     * Sanitize checkbox values.
     */
    public function sanitize_checkbox($input) {
        return ($input === 'yes') ? 'yes' : 'no';
    }

    /**
     * Sanitize position value.
     */
    public function sanitize_position($input) {
        $valid_positions = array('left', 'right');
        return in_array($input, $valid_positions) ? $input : 'right';
    }
    
    /**
     * Sanitize icon type.
     */
    public function sanitize_icon_type($input) {
        $valid_types = array('emoji', 'svg');
        return in_array($input, $valid_types) ? $input : 'emoji';
    }
    
    /**
     * Sanitize zoom value.
     */
    public function sanitize_zoom($input) {
        $input = absint($input);
        return max(50, min(150, $input)); // Limit zoom between 50% and 150%
    }
    
    /**
     * Sanitize array values.
     */
    public function sanitize_array($input) {
        if (!is_array($input)) {
            return array();
        }
        return array_map('sanitize_text_field', $input);
    }

    public function sanitize_ab_variants($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = $decoded;
            } else {
                return array();
            }
        }
        if (!is_array($input)) {
            return array();
        }
        $out = array();
        foreach ($input as $v) {
            if (is_array($v) && !empty($v['id'])) {
                $out[] = array(
                    'id' => sanitize_text_field($v['id']),
                    'url' => isset($v['url']) ? esc_url_raw($v['url']) : '',
                    'color' => isset($v['color']) ? sanitize_hex_color($v['color']) : '',
                    'weight' => isset($v['weight']) ? max(1, min(100, intval($v['weight']))) : 50,
                );
            }
        }
        return $out;
    }

    public function sanitize_widget_profiles($input) {
        if (is_string($input)) {
            $decoded = json_decode(stripslashes($input), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = $decoded;
            } else {
                return array();
            }
        }
        if (!is_array($input)) {
            return array();
        }
        $out = array();
        foreach ($input as $idx => $v) {
            if (!is_array($v)) {
                continue;
            }
            $id = isset($v['id']) ? sanitize_text_field($v['id']) : 'profile-' . ($idx + 1);
            if (empty($id)) {
                $id = 'profile-' . ($idx + 1);
            }
            $pages = isset($v['pages']) ? array_map('absint', array_filter((array) $v['pages'])) : array();
            $exclude = isset($v['exclude_pages']) ? array_map('absint', array_filter((array) $v['exclude_pages'])) : array();
            $conditions = isset($v['conditions']) ? array_map('sanitize_text_field', (array) $v['conditions']) : array();
            $post_types = isset($v['post_types']) ? array_map('sanitize_text_field', (array) $v['post_types']) : array();
            $out[] = array(
                'id' => $id,
                'name' => isset($v['name']) ? sanitize_text_field($v['name']) : $id,
                'url' => isset($v['url']) ? esc_url_raw($v['url']) : '',
                'title' => isset($v['title']) ? sanitize_text_field($v['title']) : '',
                'position' => isset($v['position']) && in_array($v['position'], array('left', 'right'), true) ? $v['position'] : 'right',
                'color' => isset($v['color']) ? sanitize_hex_color($v['color']) : '',
                'pages' => $pages,
                'exclude_pages' => $exclude,
                'conditions' => $conditions,
                'post_types' => $post_types,
            );
        }
        return $out;
    }

    public function sanitize_goals($input) {
        if (!is_array($input)) {
            return array();
        }
        $out = array();
        foreach ($input as $idx => $v) {
            if (!is_array($v) || empty($v['name'])) {
                continue;
            }
            $id = isset($v['id']) ? sanitize_text_field($v['id']) : 'goal-' . ($idx + 1);
            $types = array('widget_opened', 'message_sent', 'lead_captured', 'conversion', 'custom');
            $type = isset($v['type']) && in_array($v['type'], $types, true) ? $v['type'] : 'widget_opened';
            $out[] = array(
                'id' => $id,
                'name' => sanitize_text_field($v['name']),
                'type' => $type,
                'target' => isset($v['target']) ? max(1, intval($v['target'])) : 100,
                'start_date' => isset($v['start_date']) ? sanitize_text_field($v['start_date']) : gmdate('Y-m-d'),
            );
        }
        return $out;
    }

    /**
     * Render the section info.
     */
    public function render_section_info() {
        echo '<p>' . esc_html__('Configure your ChatAssist settings below.', 'intelligizedigital-chatassist') . '</p>';
    }

    /**
     * Render URL field.
     */
    public function render_url_field() {
        $url = get_option('ackm_chatassist_url');
        ?>
        <div class="ackm-chatassist-flex-row">
            <input type="url" id="ackm_chatassist_url" name="ackm_chatassist_url" value="<?php echo esc_attr($url); ?>" class="regular-text" style="flex: 1; margin-right: 10px;" placeholder="https://your-chat-service.com/webhook/your-chat-id/chat" />
            <button type="button" id="load-preview-button" class="button button-secondary"><?php esc_html_e('Save & Preview', 'intelligizedigital-chatassist'); ?></button>
        </div>
        <p class="description"><?php esc_html_e('Enter the full URL of your chat webhook.', 'intelligizedigital-chatassist'); ?></p>
        <details class="chat-url-help ackm-chatassist-mt-10">
            <summary class="ackm-chatassist-help-summary"><?php esc_html_e('Need help getting your Chat URL?', 'intelligizedigital-chatassist'); ?></summary>
            <div class="ackm-chatassist-help-box">
                <p><strong><?php esc_html_e('How to get your Chat URL:', 'intelligizedigital-chatassist'); ?></strong></p>
                <ol style="margin-left: 20px; margin-bottom: 10px;">
                    <li><?php esc_html_e('Set up a chat workflow using n8n, your preferred chat service, or custom solution', 'intelligizedigital-chatassist'); ?></li>
                    <li><?php esc_html_e('Connect it to an AI agent, chain, or configure your chat logic', 'intelligizedigital-chatassist'); ?></li>
                    <li><?php esc_html_e('Enable public access to your chat service (e.g., make your n8n workflow publicly accessible)', 'intelligizedigital-chatassist'); ?></li>
                    <li><?php esc_html_e('Configure your chat service settings', 'intelligizedigital-chatassist'); ?></li>
                    <li><?php esc_html_e('Activate your workflow and copy the Chat URL (webhook URL)', 'intelligizedigital-chatassist'); ?></li>
                </ol>
                <p class="description ackm-chatassist-mt-10">
                    <?php esc_html_e('This plugin works with n8n workflows, custom chat services, AI chatbots, and any service that provides a webhook URL.', 'intelligizedigital-chatassist'); ?>
                </p>
                <p>
                    <a class="ackm-chatassist-mr-15" href="#" target="_blank"><span class="dashicons dashicons-media-document" style="margin-right: 3px;"></span><?php esc_html_e('Chat Documentation', 'intelligizedigital-chatassist'); ?></a>
                </p>
            </div>
        </details>
        <?php
    }

    /**
     * Render enabled field.
     */
    public function render_enabled_field() {
        $enabled = get_option('ackm_chatassist_enabled', 'yes');
        ?>
        <label for="ackm_chatassist_enabled">
            <input type="checkbox" id="ackm_chatassist_enabled" name="ackm_chatassist_enabled" value="yes" <?php checked('yes', $enabled); ?> />
            <?php esc_html_e('Enable chat widget on the website', 'intelligizedigital-chatassist'); ?>
        </label>
        <?php
    }

    /**
     * Render position field.
     */
    public function render_position_field() {
        $position = get_option('ackm_chatassist_position', 'right');
        ?>
        <select id="ackm_chatassist_position" name="ackm_chatassist_position">
            <option value="right" <?php selected('right', $position); ?>><?php esc_html_e('Right', 'intelligizedigital-chatassist'); ?></option>
            <option value="left" <?php selected('left', $position); ?>><?php esc_html_e('Left', 'intelligizedigital-chatassist'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Select the position of the chat widget button.', 'intelligizedigital-chatassist'); ?></p>
        <?php
    }
    
    /**
     * Render title field.
     */
    public function render_title_field() {
        $title = get_option('ackm_chatassist_title', 'Chat Support');
        ?>
        <input type="text" id="ackm_chatassist_title" name="ackm_chatassist_title" value="<?php echo esc_attr($title); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter the title for the chat widget.', 'intelligizedigital-chatassist'); ?></p>
        <?php
    }
    
    /**
     * Render color field.
     */
    public function render_color_field() {
        $color = get_option('ackm_chatassist_color', '#667eea');
        ?>
        <input type="text" id="ackm_chatassist_color" name="ackm_chatassist_color" value="<?php echo esc_attr($color); ?>" class="ackm-chatassist-color-picker" data-default-color="#667eea" />
        <p class="description"><?php esc_html_e('Select the primary color for the chat widget.', 'intelligizedigital-chatassist'); ?></p>
        <?php
    }
    
    /**
     * Render icon settings field.
     */
    public function render_icon_settings_field() {
        $icon_type = get_option('ackm_chatassist_icon_type', 'emoji');
        $icon = get_option('ackm_chatassist_icon', '💬');
        $svg_icon = get_option('ackm_chatassist_svg_icon', '');
        $popular_icons = array('💬', '🤖', '💻', '🔔', '📨', '📝', '🎯', '🔍', '📱', '👋');
        ?>
        <div class="icon-type-selector" style="margin-bottom: 15px;">
            <label class="ackm-chatassist-mr-15">
                <input type="radio" name="ackm_chatassist_icon_type" value="emoji" <?php checked('emoji', $icon_type); ?> />
                <?php esc_html_e('Use Emoji', 'intelligizedigital-chatassist'); ?>
            </label>
            <label>
                <input type="radio" name="ackm_chatassist_icon_type" value="svg" <?php checked('svg', $icon_type); ?> />
                <?php esc_html_e('Use SVG Icon', 'intelligizedigital-chatassist'); ?>
            </label>
        </div>
        
        <div id="emoji-icon-section" class="ackm-chatassist-icon-section<?php echo $icon_type === 'emoji' ? '' : ' ackm-chatassist-hidden'; ?>">
            <input type="text" id="ackm_chatassist_icon" name="ackm_chatassist_icon" value="<?php echo esc_attr($icon); ?>" style="width: 60px; font-size: 24px; text-align: center;" maxlength="2" />
            <div class="icon-suggestions ackm-chatassist-mt-10">
                <p class="description"><?php esc_html_e('Popular icons:', 'intelligizedigital-chatassist'); ?></p>
                <div class="icon-grid" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
                    <?php foreach ($popular_icons as $emoji) : ?>
                    <button type="button" class="icon-option" style="font-size: 24px; width: 40px; height: 40px; cursor: pointer; border: 1px solid #ddd; background: #f7f7f7;"><?php echo esc_html($emoji); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="description"><?php esc_html_e('Choose an emoji for the chat button.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        
        <div id="svg-icon-section" class="ackm-chatassist-icon-section<?php echo $icon_type === 'svg' ? '' : ' ackm-chatassist-hidden'; ?>">
            <div class="svg-upload-container" style="margin-bottom: 10px;">
                <input type="text" id="ackm_chatassist_svg_icon" name="ackm_chatassist_svg_icon" value="<?php echo esc_url($svg_icon); ?>" class="regular-text ackm-chatassist-mr-10" readonly/>
                <button type="button" id="upload_svg_button" class="button"><?php esc_html_e('Upload SVG Icon', 'intelligizedigital-chatassist'); ?></button>
            </div>
            <?php if (!empty($svg_icon)) : ?>
            <div class="svg-preview" style="margin: 10px 0;">
                <p class="description"><?php esc_html_e('Current icon:', 'intelligizedigital-chatassist'); ?></p>
                <div style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: <?php echo esc_attr(get_option('ackm_chatassist_color', '#667eea')); ?>;">
                    <?php 
                    // Try to get attachment ID from URL
                    $attachment_id = attachment_url_to_postid($svg_icon);
                    if ($attachment_id) {
                        echo wp_get_attachment_image($attachment_id, array(24, 24), false, array(
                            'style' => 'max-width: 60%; max-height: 60%;',
                            'alt' => esc_attr__('SVG Icon', 'intelligizedigital-chatassist')
                        ));
                    } else {
                        // Use the helper function for proper display
                        echo wp_kses_post(ackm_chatassist_display_svg($svg_icon, array(24, 24), array(
                            'style' => 'max-width: 60%; max-height: 60%;',
                            'alt' => esc_attr__('SVG Icon', 'intelligizedigital-chatassist')
                        )));
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('Upload an SVG icon for the chat button. Recommended size: 24x24px.', 'intelligizedigital-chatassist'); ?></p>
            <p class="description"><?php esc_html_e('The SVG icon will be displayed inside a circular button with the chosen widget color as background.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <?php
    }

    /**
     * Render zoom field.
     */
    public function render_zoom_field() {
        $zoom = get_option('ackm_chatassist_zoom', '100');
        $chat_url = get_option('ackm_chatassist_url');
        $color = get_option('ackm_chatassist_color', '#667eea');
        $title = get_option('ackm_chatassist_title', 'Chat Support');
        $position = get_option('ackm_chatassist_position', 'right');
        ?>
        <div class="zoom-settings-container" style="display: flex; flex-wrap: wrap; gap: 30px; align-items: flex-start;">
            <!-- Left column: Zoom controls -->
            <div class="zoom-controls" style="flex: 1; min-width: 280px;">
                <div class="zoom-control ackm-chatassist-flex-row">
                    <input type="range" id="ackm_chatassist_zoom_slider" min="50" max="150" step="5" value="<?php echo esc_attr($zoom); ?>" style="flex: 1;" />
                    <input type="number" id="ackm_chatassist_zoom" name="ackm_chatassist_zoom" value="<?php echo esc_attr($zoom); ?>" min="50" max="150" step="5" style="width: 65px; margin-left: 10px;" />
                    <span style="margin-left: 5px;">%</span>
                </div>
                <p class="description"><?php esc_html_e('Adjust the zoom level of the chat content (50% - 150%).', 'intelligizedigital-chatassist'); ?></p>
                <p class="description"><?php esc_html_e('This setting affects how the chat content is displayed in the widget.', 'intelligizedigital-chatassist'); ?></p>
            </div>
            
            <!-- Right column: Preview -->
            <div class="preview-wrapper ackm-chatassist-preview-wrapper" style="flex: 1; min-width: 350px; display: flex; flex-direction: column; align-items: center;">
                <?php if (!empty($chat_url)) : ?>
                <h4 style="margin-top: 0; align-self: flex-start;"><?php esc_html_e('Live Preview', 'intelligizedigital-chatassist'); ?></h4>
                <div class="ackm-chatassist-preview" style="position: relative; width: 350px; height: 500px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2); overflow: hidden; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background-color: <?php echo esc_attr($color); ?>; color: white;">
                        <div style="font-weight: bold; font-size: 16px;"><?php echo esc_html($title); ?></div>
                        <button type="button" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                    </div>
                    <div style="position: relative; height: calc(100% - 60px); overflow: hidden;">
                        <div id="preview-loading-spinner" class="preview-loading-spinner" style="position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #fff; z-index: 1;">
                            <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid <?php echo esc_attr($color); ?>; border-radius: 50%; animation: ackm-chatassist-spin 1s linear infinite;"></div>
                        </div>
                        <iframe id="zoom-preview-iframe" src="<?php echo esc_url($chat_url); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; transform-origin: top left; transform: scale(<?php echo esc_attr($zoom / 100); ?>);"></iframe>
                    </div>
                </div>
                <p class="description ackm-chatassist-mt-10"><?php esc_html_e('This is how your chat will appear with the current zoom level.', 'intelligizedigital-chatassist'); ?></p>
                <?php else : ?>
                <h4 style="margin-top: 0; align-self: flex-start;"><?php esc_html_e('Preview', 'intelligizedigital-chatassist'); ?></h4>
                <div class="ackm-chatassist-preview" style="position: relative; width: 350px; height: 500px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2); overflow: hidden; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background-color: <?php echo esc_attr($color); ?>; color: white;">
                        <div style="font-weight: bold; font-size: 16px;"><?php echo esc_html($title); ?></div>
                        <button type="button" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                    </div>
                    <div style="height: calc(100% - 60px); padding: 20px; background-color: #f9f9f9; overflow-y: auto;">
                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
                            <p style="margin-bottom: 15px; padding: 12px; background-color: #e9e9e9; border-radius: 18px 18px 18px 4px; max-width: 80%;"><strong>User:</strong> Hello, I need some help!</p>
                            <p style="margin-bottom: 15px; padding: 12px; background-color: #d9f4f4; border-radius: 18px 18px 4px 18px; margin-left: 20%; max-width: 80%;"><strong>Bot:</strong> Hi there! How can I assist you today?</p>
                            <p style="margin-bottom: 15px; padding: 12px; background-color: #e9e9e9; border-radius: 18px 18px 18px 4px; max-width: 80%;"><strong>User:</strong> I have a question about...</p>
                        </div>
                    </div>
                </div>
                <p class="description" style="margin-top: 10px; color: #d63638;"><?php esc_html_e('Please enter an Chat URL above to see a live preview.', 'intelligizedigital-chatassist'); ?></p>
                <?php endif; ?>
                
                <!-- Chat button preview -->
                <div style="margin-top: 30px; display: flex; align-items: center; justify-content: space-between; width: 350px; padding: 20px; background: #f8f8f8; border-radius: 8px; flex-shrink: 0;" class="button-preview-container">
                    <div>
                        <h4 style="margin-top: 0; margin-bottom: 10px;"><?php esc_html_e('Button Preview', 'intelligizedigital-chatassist'); ?></h4>
                        <div id="preview-chat-button" style="width: 60px; height: 60px; border-radius: 50%; background-color: <?php echo esc_attr($color); ?>; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                            <span id="preview-button-icon">
                            <?php 
                            $icon_type = get_option('ackm_chatassist_icon_type', 'emoji');
                            $icon = get_option('ackm_chatassist_icon', '💬');
                            $svg_icon = get_option('ackm_chatassist_svg_icon', '');
                            
                            if ($icon_type === 'emoji') {
                                echo esc_html($icon);
                            } elseif (!empty($svg_icon)) {
                                $attachment_id = attachment_url_to_postid($svg_icon);
                                if ($attachment_id) {
                                    echo wp_get_attachment_image($attachment_id, array(24, 24), false, array(
                                        'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                        'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist')
                                    ));
                                } else {
                                    echo wp_kses_post(ackm_chatassist_display_svg($svg_icon, array(24, 24), array(
                                        'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                        'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist')
                                    )));
                                }
                            } else {
                                echo '💬';
                            }
                            ?>
                            </span>
                        </div>
                    </div>
                    <p id="preview-position-text" class="description" style="margin-left: 15px; flex: 1; max-width: 250px;">
                        <?php 
                        printf(
                            /* translators: %s: position of the chat button (left or right) */
                            esc_html__('This chat button will appear in the bottom %s corner of your website.', 'intelligizedigital-chatassist'),
                            esc_html($position)
                        );
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_ackm_chatassist') && !current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'intelligizedigital-chatassist'));
        }
        
        // Get common settings for preview
        $color = get_option('ackm_chatassist_color', '#667eea');
        $title = get_option('ackm_chatassist_title', 'Chat Support');
        $position = get_option('ackm_chatassist_position', 'right');
        $zoom = get_option('ackm_chatassist_zoom', '100');
        $chat_url = get_option('ackm_chatassist_url');
        $icon_type = get_option('ackm_chatassist_icon_type', 'emoji');
        $icon = get_option('ackm_chatassist_icon', '💬');
        $svg_icon = get_option('ackm_chatassist_svg_icon', '');
        $enabled = get_option('ackm_chatassist_enabled', 'yes');
        
        // Get analytics data
        $analytics_data = Ackm_ChatAssist_Analytics::get_data(30);
        $total_opens = isset($analytics_data['total']['widget_opened']) ? $analytics_data['total']['widget_opened'] : 0;
        $total_closes = isset($analytics_data['total']['widget_closed']) ? $analytics_data['total']['widget_closed'] : 0;
        $total_messages = isset($analytics_data['total']['message_sent']) ? $analytics_data['total']['message_sent'] : 0;
        $open_rate = Ackm_ChatAssist_Analytics::get_open_rate(30);
        ?>
        <div class="wrap ackm-chatassist-admin-wrapper">
            <!-- Premium Header -->
            <div class="ackm-chatassist-header">
                <h1>
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php echo esc_html(get_admin_page_title()); ?>
                </h1>
                <p><?php esc_html_e('Connect any chat service to your WordPress website with a beautiful, customizable widget. Premium design, powerful features.', 'intelligizedigital-chatassist'); ?></p>
            </div>

            <!-- Stats Cards -->
            <?php if ($enabled === 'yes' && get_option('ackm_chatassist_analytics_enabled', 'yes') === 'yes') : ?>
            <div class="ackm-chatassist-stats-grid">
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Widget Opens', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html(number_format($total_opens)); ?></p>
                    <p class="stat-label"><?php esc_html_e('Last 30 days', 'intelligizedigital-chatassist'); ?></p>
                </div>
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Messages Sent', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html(number_format($total_messages)); ?></p>
                    <p class="stat-label"><?php esc_html_e('Last 30 days', 'intelligizedigital-chatassist'); ?></p>
                </div>
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Open Rate', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html($open_rate); ?>%</p>
                    <p class="stat-label"><?php esc_html_e('Engagement rate', 'intelligizedigital-chatassist'); ?></p>
                </div>
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Status', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value" style="font-size: 18px;">
                        <?php echo $enabled === 'yes' ? '<span style="color: #28a745;">●</span> ' . esc_html__('Active', 'intelligizedigital-chatassist') : '<span style="color: #dc3545;">●</span> ' . esc_html__('Inactive', 'intelligizedigital-chatassist'); ?>
                    </p>
                    <p class="stat-label"><?php esc_html_e('Widget status', 'intelligizedigital-chatassist'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabs Navigation -->
            <div class="ackm-chatassist-tabs">
                <button class="ackm-chatassist-tab active" data-tab="general">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('General', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="widget-profiles">
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <?php esc_html_e('Widget Profiles', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="display">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Display Rules', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="analytics">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Analytics', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="heatmap">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Heatmap', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="triggers">
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Triggers', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="advanced">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Advanced', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="integrations">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php esc_html_e('Integrations', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="marketing">
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Marketing', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="mobile">
                    <span class="dashicons dashicons-smartphone"></span>
                    <?php esc_html_e('Mobile', 'intelligizedigital-chatassist'); ?>
                </button>
                <button class="ackm-chatassist-tab" data-tab="preview">
                    <span class="dashicons dashicons-desktop"></span>
                    <?php esc_html_e('Preview', 'intelligizedigital-chatassist'); ?>
                </button>
            </div>

            <!-- Tab Contents -->
            <form action="options.php" method="post" id="ackm-chatassist-settings-form">
                <?php settings_fields('ackm_chatassist_options'); ?>
                
                <!-- General Tab -->
                <div class="ackm-chatassist-tab-content active" id="tab-general">
                    <div class="ackm-chatassist-admin-layout">
                        <div class="ackm-chatassist-settings-card">
                            <h2><?php esc_html_e('General Settings', 'intelligizedigital-chatassist'); ?></h2>
                            <?php $this->render_settings_fields(); ?>
                            
                            <div class="ackm-chatassist-btn-group">
                                <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
                                </button>
                                <button type="button" id="load-preview-button" class="ackm-chatassist-btn ackm-chatassist-btn-secondary">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Save & Preview', 'intelligizedigital-chatassist'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="ackm-chatassist-preview-card">
                            <h2><?php esc_html_e('Live Preview', 'intelligizedigital-chatassist'); ?></h2>
                            <div class="ackm-chatassist-preview-wrapper">
                                <div class="ackm-chatassist-preview-widget">
                                    <div class="ackm-chatassist-preview-header" id="preview-widget-header">
                                        <div class="ackm-chatassist-preview-title" id="preview-widget-title"><?php echo esc_html($title); ?></div>
                                        <button type="button" class="ackm-chatassist-preview-close" aria-label="<?php esc_attr_e('Close', 'intelligizedigital-chatassist'); ?>">&times;</button>
                                    </div>
                                    <div class="ackm-chatassist-preview-body">
                                        <?php if (!empty($chat_url)) : ?>
                                        <div id="preview-loading-spinner" class="preview-loading-spinner" style="position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #fff; z-index: 1;">
                                            <div class="ackm-chatassist-spinner"></div>
                                        </div>
                                        <iframe id="zoom-preview-iframe" src="<?php echo esc_url($chat_url); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; transform-origin: top left; transform: scale(<?php echo esc_attr($zoom / 100); ?>);"></iframe>
                                        <?php else : ?>
                                        <div class="ackm-chatassist-empty-state">
                                            <span class="dashicons dashicons-format-chat ackm-chatassist-empty-icon"></span>
                                            <p style="margin-top: 20px; font-size: 15px; font-weight: 500;"><?php esc_html_e('Enter a Chat URL to see live preview', 'intelligizedigital-chatassist'); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="text-align: center; margin-top: 28px;">
                                    <h4 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: var(--ackm-chatassist-dark);"><?php esc_html_e('Button Preview', 'intelligizedigital-chatassist'); ?></h4>
                                    <div id="preview-chat-button" class="ackm-chatassist-preview-button">
                                        <span id="preview-button-icon">
                                        <?php 
                                        if ($icon_type === 'emoji') {
                                            echo esc_html($icon);
                                        } elseif (!empty($svg_icon)) {
                                            $attachment_id = attachment_url_to_postid($svg_icon);
                                            if ($attachment_id) {
                                                echo wp_get_attachment_image($attachment_id, array(32, 32), false, array(
                                                    'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                                    'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist')
                                                ));
                                            } else {
                                                echo wp_kses_post(ackm_chatassist_display_svg($svg_icon, array(32, 32), array(
                                                    'style' => 'max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);',
                                                    'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist')
                                                )));
                                            }
                                        } else {
                                            echo '💬';
                                        }
                                        ?>
                                        </span>
                                    </div>
                                    <p id="preview-position-text" class="description" style="margin-top: 18px; font-size: 14px; color: var(--ackm-chatassist-gray-600);">
                                        <?php 
                                        printf(
                                            /* translators: %s: position of the chat button (left or right) */
                                            esc_html__('This chat button will appear in the bottom %s corner of your website.', 'intelligizedigital-chatassist'),
                                            esc_html($position)
                                        );
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget Profiles Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-widget-profiles">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Multiple Chat Widgets', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_widget_profiles_fields(); ?>
                    </div>
                </div>

                <!-- Display Rules Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-display">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Display Rules', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_display_rules_fields(); ?>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-analytics">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Analytics Dashboard', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_analytics_dashboard(); ?>
                    </div>
                </div>

                <!-- Heatmap Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-heatmap">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Heatmap Visualization', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_heatmap_fields(); ?>
                    </div>
                </div>

                <!-- Triggers Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-triggers">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Display Triggers', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_triggers_fields(); ?>
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-advanced">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Advanced Settings', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_advanced_fields(); ?>
                    </div>
                </div>

                <!-- Integrations Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-integrations">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Integrations', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_integrations_fields(); ?>
                    </div>
                </div>

                <!-- Marketing Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-marketing">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Marketing', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_marketing_fields(); ?>
                    </div>
                </div>

                <!-- Mobile Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-mobile">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Mobile & PWA', 'intelligizedigital-chatassist'); ?></h2>
                        <?php $this->render_mobile_fields(); ?>
                    </div>
                </div>

                <!-- Preview Tab -->
                <div class="ackm-chatassist-tab-content" id="tab-preview">
                    <div class="ackm-chatassist-settings-card">
                        <h2><?php esc_html_e('Widget Preview', 'intelligizedigital-chatassist'); ?></h2>
                        <div class="ackm-chatassist-preview-wrapper">
                            <div class="ackm-chatassist-preview-widget">
                                <div class="ackm-chatassist-preview-header" id="preview-widget-header-full">
                                    <div class="ackm-chatassist-preview-title" id="preview-widget-title-full"><?php echo esc_html($title); ?></div>
                                    <button type="button" class="ackm-chatassist-preview-close">&times;</button>
                                </div>
                                    <div class="ackm-chatassist-preview-body">
                                        <?php if (!empty($chat_url)) : ?>
                                        <iframe id="zoom-preview-iframe-full" src="<?php echo esc_url($chat_url); ?>" style="width: 100%; height: 100%; border: none; transform-origin: top left; transform: scale(<?php echo esc_attr($zoom / 100); ?>);"></iframe>
                                        <?php else : ?>
                                        <div class="ackm-chatassist-empty-state">
                                            <span class="dashicons dashicons-format-chat ackm-chatassist-empty-icon"></span>
                                            <p style="margin-top: 20px; font-size: 15px; font-weight: 500;"><?php esc_html_e('Enter a Chat URL to see live preview', 'intelligizedigital-chatassist'); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Support Links -->
            <div class="ackm-chatassist-support-links">
                <a href="https://github.com/ackm04/chatassist/issues/new" target="_blank" rel="noopener noreferrer" class="ackm-chatassist-support-link">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Report Bug or Request a Feature', 'intelligizedigital-chatassist'); ?>
                </a>
                <a href="https://github.com/ackm04/chatassist" target="_blank" rel="noopener noreferrer" class="ackm-chatassist-support-link">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php esc_html_e('View on GitHub', 'intelligizedigital-chatassist'); ?>
                </a>
                <a href="https://www.buymeacoffee.com/ackm04" target="_blank" rel="noopener noreferrer" class="ackm-chatassist-support-link ackm-chatassist-coffee-link">
                    <span class="dashicons dashicons-coffee"></span>
                    <?php esc_html_e('Buy me a coffee', 'intelligizedigital-chatassist'); ?>
                </a>
                <a href="https://github.com/sponsors/ackm04" target="_blank" rel="noopener noreferrer" class="ackm-chatassist-support-link ackm-chatassist-sponsor-link">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Sponsor on GitHub', 'intelligizedigital-chatassist'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Custom method to render settings fields
     */
    private function render_settings_fields() {
        // Chat URL field
        $url = get_option('ackm_chatassist_url');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_url"><?php esc_html_e('Chat URL', 'intelligizedigital-chatassist'); ?></label>
            <input type="url" id="ackm_chatassist_url" name="ackm_chatassist_url" value="<?php echo esc_attr($url); ?>" placeholder="https://your-chat-service.com/webhook/your-chat-id/chat" />
            <p class="description"><?php esc_html_e('Enter the full URL of your chat webhook.', 'intelligizedigital-chatassist'); ?></p>
            <details class="ackm-chatassist-help-box ackm-chatassist-mt-15">
                <summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;"><?php esc_html_e('Need help getting your Chat URL?', 'intelligizedigital-chatassist'); ?></summary>
                <div class="ackm-chatassist-mt-10">
                    <p><strong><?php esc_html_e('How to get your Chat URL:', 'intelligizedigital-chatassist'); ?></strong></p>
                    <ol>
                        <li><?php esc_html_e('Set up a chat workflow using n8n, your preferred chat service, or custom solution', 'intelligizedigital-chatassist'); ?></li>
                        <li><?php esc_html_e('Connect it to an AI agent, chain, or configure your chat logic', 'intelligizedigital-chatassist'); ?></li>
                        <li><?php esc_html_e('Enable public access to your chat service (e.g., make your n8n workflow publicly accessible)', 'intelligizedigital-chatassist'); ?></li>
                        <li><?php esc_html_e('Configure your chat service settings', 'intelligizedigital-chatassist'); ?></li>
                        <li><?php esc_html_e('Activate your workflow and copy the Chat URL (webhook URL)', 'intelligizedigital-chatassist'); ?></li>
                    </ol>
                    <p class="description ackm-chatassist-mt-10">
                        <?php 
                        printf(
                            /* translators: %s: Link to n8n documentation */
                            esc_html__('This plugin works with n8n workflows, custom chat services, AI chatbots, and any service that provides a webhook URL. For n8n specifically, you can create a chat workflow and use its webhook URL here.', 'intelligizedigital-chatassist')
                        ); 
                        ?>
                    </p>
                </div>
            </details>
        </div>
        
        <?php
        // Enable widget field
        $enabled = get_option('ackm_chatassist_enabled', 'yes');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_enabled"><?php esc_html_e('Enable Chat Widget', 'intelligizedigital-chatassist'); ?></label>
            <label class="ackm-chatassist-flex-row">
                <input type="checkbox" id="ackm_chatassist_enabled" name="ackm_chatassist_enabled" value="yes" <?php checked('yes', $enabled); ?> />
                <?php esc_html_e('Enable chat widget on the website', 'intelligizedigital-chatassist'); ?>
            </label>
        </div>
        
        <?php
        // Position field
        $position = get_option('ackm_chatassist_position', 'right');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_position"><?php esc_html_e('Widget Position', 'intelligizedigital-chatassist'); ?></label>
            <select id="ackm_chatassist_position" name="ackm_chatassist_position">
                <option value="right" <?php selected('right', $position); ?>><?php esc_html_e('Right', 'intelligizedigital-chatassist'); ?></option>
                <option value="left" <?php selected('left', $position); ?>><?php esc_html_e('Left', 'intelligizedigital-chatassist'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Select the position of the chat widget button.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        
        <?php
        // Title field
        $title = get_option('ackm_chatassist_title', 'Chat Support');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_title"><?php esc_html_e('Chat Widget Title', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" id="ackm_chatassist_title" name="ackm_chatassist_title" value="<?php echo esc_attr($title); ?>" />
            <p class="description"><?php esc_html_e('Enter the title for the chat widget.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        
        <?php
        // Color field
        $color = get_option('ackm_chatassist_color', '#667eea');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_color"><?php esc_html_e('Widget Color', 'intelligizedigital-chatassist'); ?></label>
            <div class="ackm-chatassist-color-picker-wrapper">
                <input type="text" id="ackm_chatassist_color" name="ackm_chatassist_color" value="<?php echo esc_attr($color); ?>" class="ackm-chatassist-color-picker" data-default-color="#667eea" />
                <div class="ackm-chatassist-color-preview" style="background-color: <?php echo esc_attr($color); ?>;"></div>
            </div>
            <p class="description"><?php esc_html_e('Select the primary color for the chat widget.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        
        <?php
        // Icon settings
        $icon_type = get_option('ackm_chatassist_icon_type', 'emoji');
        $icon = get_option('ackm_chatassist_icon', '💬');
        $svg_icon = get_option('ackm_chatassist_svg_icon', '');
        $popular_icons = array('💬', '🤖', '💻', '🔔', '📨', '📝', '🎯', '🔍', '📱', '👋', '💬', '🎨', '⚡', '🌟', '🚀', '💡');
        ?>
        <div class="ackm-chatassist-form-group">
            <label><?php esc_html_e('Chat Icon', 'intelligizedigital-chatassist'); ?></label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="ackm_chatassist_icon_type" value="emoji" <?php checked('emoji', $icon_type); ?> />
                    <?php esc_html_e('Use Emoji', 'intelligizedigital-chatassist'); ?>
                </label>
                <label>
                    <input type="radio" name="ackm_chatassist_icon_type" value="svg" <?php checked('svg', $icon_type); ?> />
                    <?php esc_html_e('Use SVG Icon', 'intelligizedigital-chatassist'); ?>
                </label>
            </div>
            
            <div id="emoji-icon-section" class="ackm-chatassist-icon-section<?php echo $icon_type === 'emoji' ? '' : ' ackm-chatassist-hidden'; ?>">
                <input type="text" id="ackm_chatassist_icon" name="ackm_chatassist_icon" value="<?php echo esc_attr($icon); ?>" style="width: 80px; font-size: 32px; text-align: center; margin-top: 15px; border: 2px solid #e0e0e0; border-radius: 8px;" maxlength="2" />
                <p class="description ackm-chatassist-mt-10"><?php esc_html_e('Popular icons:', 'intelligizedigital-chatassist'); ?></p>
                <div class="ackm-chatassist-icon-grid">
                    <?php foreach ($popular_icons as $emoji) : ?>
                    <button type="button" class="ackm-chatassist-icon-option <?php echo ($icon === $emoji) ? 'selected' : ''; ?>" data-emoji="<?php echo esc_attr($emoji); ?>">
                        <?php echo esc_html($emoji); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="svg-icon-section" class="ackm-chatassist-icon-section<?php echo $icon_type === 'svg' ? '' : ' ackm-chatassist-hidden'; ?>">
                <div class="ackm-chatassist-svg-upload <?php echo !empty($svg_icon) ? 'has-image' : ''; ?>">
                    <input type="text" id="ackm_chatassist_svg_icon" name="ackm_chatassist_svg_icon" value="<?php echo esc_url($svg_icon); ?>" readonly />
                    <button type="button" id="upload_svg_button" class="ackm-chatassist-btn ackm-chatassist-btn-secondary ackm-chatassist-mt-10">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Upload SVG Icon', 'intelligizedigital-chatassist'); ?>
                    </button>
                    <?php if (!empty($svg_icon)) : ?>
                    <div class="ackm-chatassist-svg-preview ackm-chatassist-mt-15">
                        <?php
                        $attachment_id = attachment_url_to_postid($svg_icon);
                        if ($attachment_id) {
                            echo wp_get_attachment_image($attachment_id, array(100, 100), false, array('alt' => esc_attr__('SVG Icon', 'intelligizedigital-chatassist')));
                        } else {
                            echo wp_kses_post(ackm_chatassist_display_svg($svg_icon, array(100, 100), array('alt' => esc_attr__('SVG Icon', 'intelligizedigital-chatassist'))));
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="description"><?php esc_html_e('Upload an SVG icon for the chat button. Recommended size: 24x24px.', 'intelligizedigital-chatassist'); ?></p>
            </div>
        </div>
        
        <?php
        // Zoom field
        $zoom = get_option('ackm_chatassist_zoom', '100');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_zoom_slider"><?php esc_html_e('Chat Content Zoom', 'intelligizedigital-chatassist'); ?></label>
            <div class="ackm-chatassist-range-wrapper">
                <input type="range" id="ackm_chatassist_zoom_slider" min="50" max="150" step="5" value="<?php echo esc_attr($zoom); ?>" class="ackm-chatassist-range-slider" />
                <input type="number" id="ackm_chatassist_zoom" name="ackm_chatassist_zoom" value="<?php echo esc_attr($zoom); ?>" min="50" max="150" step="5" class="ackm-chatassist-range-value" />
                <span>%</span>
            </div>
            <p class="description"><?php esc_html_e('Adjust the zoom level of the chat content (50% - 150%).', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <?php
    }

    /**
     * Render display rules fields
     */
    private function render_display_rules_fields() {
        $show_on = get_option('ackm_chatassist_show_on', 'all');
        $include_pages = get_option('ackm_chatassist_include_pages', '');
        $exclude_pages = get_option('ackm_chatassist_exclude_pages', '');
        $time_based = get_option('ackm_chatassist_time_based', 'no');
        $start_time = get_option('ackm_chatassist_start_time', '09:00');
        $end_time = get_option('ackm_chatassist_end_time', '17:00');
        $role_based = get_option('ackm_chatassist_role_based', 'no');
        $show_to_roles = get_option('ackm_chatassist_show_to_roles', array('guest'));
        
        $display_options = Ackm_ChatAssist_Display_Rules::get_display_options();
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_show_on"><?php esc_html_e('Show Widget On', 'intelligizedigital-chatassist'); ?></label>
            <select id="ackm_chatassist_show_on" name="ackm_chatassist_show_on">
                <?php foreach ($display_options as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($show_on, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Choose where to display the chat widget.', 'intelligizedigital-chatassist'); ?></p>
        </div>

        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_include_pages"><?php esc_html_e('Include Pages (Optional)', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" id="ackm_chatassist_include_pages" name="ackm_chatassist_include_pages" value="<?php echo esc_attr($include_pages); ?>" placeholder="1, 5, 10" />
            <p class="description"><?php esc_html_e('Enter page IDs separated by commas to show widget only on these pages.', 'intelligizedigital-chatassist'); ?></p>
        </div>

        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_exclude_pages"><?php esc_html_e('Exclude Pages (Optional)', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" id="ackm_chatassist_exclude_pages" name="ackm_chatassist_exclude_pages" value="<?php echo esc_attr($exclude_pages); ?>" placeholder="2, 8, 15" />
            <p class="description"><?php esc_html_e('Enter page IDs separated by commas to hide widget on these pages.', 'intelligizedigital-chatassist'); ?></p>
        </div>

        <div class="ackm-chatassist-form-group">
            <label>
                <input type="checkbox" name="ackm_chatassist_time_based" value="yes" <?php checked('yes', $time_based); ?> />
                <?php esc_html_e('Enable Time-Based Display', 'intelligizedigital-chatassist'); ?>
            </label>
            <p class="description"><?php esc_html_e('Show widget only during specific hours.', 'intelligizedigital-chatassist'); ?></p>
            
            <div style="margin-top: 20px; display: <?php echo $time_based === 'yes' ? 'block' : 'none'; ?>; padding: 20px; background: var(--ackm-chatassist-gray-50); border-radius: var(--ackm-chatassist-radius-lg);" id="time-settings">
                <div class="ackm-chatassist-flex-grid-2">
                    <div class="ackm-chatassist-form-group ackm-chatassist-mb-0">
                        <label for="ackm_chatassist_start_time"><?php esc_html_e('Start Time', 'intelligizedigital-chatassist'); ?></label>
                        <input type="time" id="ackm_chatassist_start_time" name="ackm_chatassist_start_time" value="<?php echo esc_attr($start_time); ?>" />
                    </div>
                    <div class="ackm-chatassist-form-group ackm-chatassist-mb-0">
                        <label for="ackm_chatassist_end_time"><?php esc_html_e('End Time', 'intelligizedigital-chatassist'); ?></label>
                        <input type="time" id="ackm_chatassist_end_time" name="ackm_chatassist_end_time" value="<?php echo esc_attr($end_time); ?>" />
                    </div>
                </div>
            </div>
        </div>

        <div class="ackm-chatassist-form-group">
            <label>
                <input type="checkbox" name="ackm_chatassist_role_based" value="yes" <?php checked('yes', $role_based); ?> />
                <?php esc_html_e('Enable Role-Based Display', 'intelligizedigital-chatassist'); ?>
            </label>
            <p class="description"><?php esc_html_e('Show widget only to specific user roles.', 'intelligizedigital-chatassist'); ?></p>
            
            <div style="margin-top: 20px; display: <?php echo $role_based === 'yes' ? 'block' : 'none'; ?>; padding: 20px; background: var(--ackm-chatassist-gray-50); border-radius: var(--ackm-chatassist-radius-lg);" id="role-settings">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                    <?php
                    $roles = get_editable_roles();
                    $roles['guest'] = array('name' => __('Guest (Not Logged In)', 'intelligizedigital-chatassist'));
                    foreach ($roles as $role_key => $role_data) :
                    ?>
                    <label style="display: flex; align-items: center; padding: 12px; background: white; border-radius: var(--ackm-chatassist-radius-md); border: 2px solid var(--ackm-chatassist-gray-200); transition: var(--ackm-chatassist-transition); cursor: pointer;">
                        <input type="checkbox" name="ackm_chatassist_show_to_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, (array)$show_to_roles)); ?> />
                        <span style="font-weight: 500; color: var(--ackm-chatassist-gray-700);"><?php echo esc_html($role_data['name']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render analytics dashboard
     */
    private function render_analytics_dashboard() {
        $analytics_enabled = get_option('ackm_chatassist_analytics_enabled', 'yes');
        $analytics_data = Ackm_ChatAssist_Analytics::get_data(30);
        ?>
        <div class="ackm-chatassist-form-group">
            <label>
                <input type="checkbox" name="ackm_chatassist_analytics_enabled" value="yes" <?php checked('yes', $analytics_enabled); ?> />
                <?php esc_html_e('Enable Analytics Tracking', 'intelligizedigital-chatassist'); ?>
            </label>
            <p class="description"><?php esc_html_e('Track widget opens, closes, and messages sent.', 'intelligizedigital-chatassist'); ?></p>
        </div>

        <?php if ($analytics_enabled === 'yes' && !empty($analytics_data['daily'])) :
            $comparison = Ackm_ChatAssist_Analytics::get_comparison(7);
            $daily = $analytics_data['daily'];
            $chart_labels = array();
            $chart_opens = array();
            $chart_closes = array();
            $chart_messages = array();
            foreach (array_slice($daily, -14, 14, true) as $date => $d) {
                $chart_labels[] = date_i18n('M j', strtotime($date));
                $chart_opens[] = isset($d['widget_opened']) ? (int) $d['widget_opened'] : 0;
                $chart_closes[] = isset($d['widget_closed']) ? (int) $d['widget_closed'] : 0;
                $chart_messages[] = isset($d['message_sent']) ? (int) $d['message_sent'] : 0;
            }
        ?>
        <div class="ackm-chatassist-mt-30">
            <h3><?php esc_html_e('Trends (Last 14 Days)', 'intelligizedigital-chatassist'); ?></h3>
            <div class="ackm-chatassist-chart-wrapper">
                <canvas id="ackm-chatassist-analytics-chart"></canvas>
            </div>
            <?php
            $opens_label    = esc_js(__('Opens', 'intelligizedigital-chatassist'));
            $closes_label   = esc_js(__('Closes', 'intelligizedigital-chatassist'));
            $messages_label = esc_js(__('Messages', 'intelligizedigital-chatassist'));
            $chart_init     = '(function(){var initChart=function(){if(typeof Chart==="undefined"||window.intelligizeChartInstance)return;';
            $chart_init    .= 'var ctx=document.getElementById("ackm-chatassist-analytics-chart");if(!ctx)return;';
            $chart_init    .= 'window.intelligizeChartInstance=new Chart(ctx.getContext("2d"),{type:"line",data:{';
            $chart_init    .= 'labels:' . wp_json_encode($chart_labels) . ',datasets:[';
            $chart_init    .= '{label:"' . $opens_label . '",data:' . wp_json_encode($chart_opens) . ',borderColor:"#667eea",backgroundColor:"rgba(102,126,234,0.1)",fill:true,tension:0.3},';
            $chart_init    .= '{label:"' . $closes_label . '",data:' . wp_json_encode($chart_closes) . ',borderColor:"#48bb78",backgroundColor:"rgba(72,187,120,0.1)",fill:true,tension:0.3},';
            $chart_init    .= '{label:"' . $messages_label . '",data:' . wp_json_encode($chart_messages) . ',borderColor:"#ed8936",backgroundColor:"rgba(237,137,54,0.1)",fill:true,tension:0.3}';
            $chart_init    .= ']},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:"top"}},scales:{y:{beginAtZero:true}}}});};';
            $chart_init    .= 'jQuery(document).on("click",".ackm-chatassist-tab[data-tab=\\"analytics\\"]",function(){setTimeout(initChart,150);});})();';
            wp_add_inline_script('chart-js', $chart_init);
            ?>
        </div>
        <div class="ackm-chatassist-mt-30">
            <h3><?php esc_html_e('Comparison (This Week vs Last Week)', 'intelligizedigital-chatassist'); ?></h3>
            <?php /* translators: 1: current opens count, 2: percentage change vs last week */ ?>
            <p><?php printf(esc_html__('Opens: %1$s (%2$s%% vs last week)', 'intelligizedigital-chatassist'), esc_html($comparison['current']['widget_opened']), esc_html($comparison['change']['widget_opened'])); ?></p>
            <?php /* translators: 1: current messages count, 2: percentage change vs last week */ ?>
            <p><?php printf(esc_html__('Messages: %1$s (%2$s%% vs last week)', 'intelligizedigital-chatassist'), esc_html($comparison['current']['message_sent']), esc_html($comparison['change']['message_sent'])); ?></p>
        </div>
        <div class="ackm-chatassist-mt-30">
            <h3><?php esc_html_e('Last 30 Days Statistics', 'intelligizedigital-chatassist'); ?></h3>
            <div class="ackm-chatassist-stats-grid ackm-chatassist-mt-20">
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Total Opens', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html(number_format($analytics_data['total']['widget_opened'])); ?></p>
                </div>
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Total Closes', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html(number_format($analytics_data['total']['widget_closed'])); ?></p>
                </div>
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Messages Sent', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html(number_format($analytics_data['total']['message_sent'])); ?></p>
                </div>
                <div class="ackm-chatassist-stat-card">
                    <h3><?php esc_html_e('Open Rate', 'intelligizedigital-chatassist'); ?></h3>
                    <p class="stat-value"><?php echo esc_html(Ackm_ChatAssist_Analytics::get_open_rate(30)); ?>%</p>
                </div>
            </div>
            
            <div class="ackm-chatassist-mt-30">
                <h3><?php esc_html_e('Daily Breakdown', 'intelligizedigital-chatassist'); ?></h3>
            <div style="background: white; border-radius: var(--ackm-chatassist-radius-xl); padding: 24px; margin-top: 20px; box-shadow: var(--ackm-chatassist-shadow-sm); border: 1px solid var(--ackm-chatassist-gray-200);">
                <table class="wp-list-table widefat fixed striped" style="border-collapse: collapse; width: 100%;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Date', 'intelligizedigital-chatassist'); ?></th>
                                <th><?php esc_html_e('Opens', 'intelligizedigital-chatassist'); ?></th>
                                <th><?php esc_html_e('Closes', 'intelligizedigital-chatassist'); ?></th>
                                <th><?php esc_html_e('Messages', 'intelligizedigital-chatassist'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $daily_data = $analytics_data['daily'];
                            $daily_data = array_slice($daily_data, -10, 10, true);
                            foreach (array_reverse($daily_data, true) as $date => $day_data) :
                            ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($date))); ?></td>
                                <td><?php echo esc_html(isset($day_data['widget_opened']) ? $day_data['widget_opened'] : 0); ?></td>
                                <td><?php echo esc_html(isset($day_data['widget_closed']) ? $day_data['widget_closed'] : 0); ?></td>
                                <td><?php echo esc_html(isset($day_data['message_sent']) ? $day_data['message_sent'] : 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php
            $goals = class_exists('Ackm_ChatAssist_Goals') ? Ackm_ChatAssist_Goals::get_goals() : array();
            $goal_types = class_exists('Ackm_ChatAssist_Goals') ? Ackm_ChatAssist_Goals::get_goal_types() : array();
            ?>
            <div class="ackm-chatassist-mt-30">
                <h3><?php esc_html_e('Goals', 'intelligizedigital-chatassist'); ?></h3>
                <p class="description"><?php esc_html_e('Set targets and track progress.', 'intelligizedigital-chatassist'); ?></p>
                <div id="ackm-chatassist-goals-list">
                    <?php
                    if (empty($goals)) {
                        $goals = array(array('id' => 'goal-1', 'name' => '', 'type' => 'widget_opened', 'target' => 100, 'start_date' => gmdate('Y-m-d')));
                    }
                    foreach ($goals as $i => $g) :
                        $g = wp_parse_args($g, array('id' => '', 'name' => '', 'type' => 'widget_opened', 'target' => 100, 'start_date' => ''));
                        $progress = class_exists('Ackm_ChatAssist_Goals') ? Ackm_ChatAssist_Goals::get_progress($g) : array('current' => 0, 'target' => 100, 'percent' => 0, 'complete' => false);
                    ?>
                    <div class="ackm-chatassist-goal-row" style="border: 1px solid #ddd; padding: 12px 16px; margin-bottom: 10px; border-radius: 6px; background: #f9f9f9;">
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-bottom: 8px;">
                            <input type="hidden" name="ackm_chatassist_goals[<?php echo esc_attr($i); ?>][id]" value="<?php echo esc_attr($g['id']); ?>" />
                            <input type="text" name="ackm_chatassist_goals[<?php echo esc_attr($i); ?>][name]" value="<?php echo esc_attr($g['name']); ?>" placeholder="<?php esc_attr_e('Goal name', 'intelligizedigital-chatassist'); ?>" style="width: 180px;" />
                            <select name="ackm_chatassist_goals[<?php echo esc_attr($i); ?>][type]">
                                <?php foreach ($goal_types as $tv => $tl) : ?>
                                <option value="<?php echo esc_attr($tv); ?>" <?php selected($g['type'], $tv); ?>><?php echo esc_html($tl); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="ackm_chatassist_goals[<?php echo esc_attr($i); ?>][target]" value="<?php echo esc_attr($g['target']); ?>" min="1" placeholder="100" />
                            <input type="date" name="ackm_chatassist_goals[<?php echo esc_attr($i); ?>][start_date]" value="<?php echo esc_attr($g['start_date']); ?>" />
                            <button type="button" class="button ackm-chatassist-remove-goal"><?php esc_html_e('Remove', 'intelligizedigital-chatassist'); ?></button>
                        </div>
                        <?php if (!empty($g['name'])) : ?>
                        <div class="ackm-chatassist-mt-8">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #666;">
                                <span><?php echo esc_html($progress['current']); ?> / <?php echo esc_html($progress['target']); ?></span>
                                <span><?php echo esc_html($progress['percent']); ?>% <?php echo $progress['complete'] ? '✓' : ''; ?></span>
                            </div>
                            <div style="height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; margin-top: 4px;">
                                <div style="height: 100%; width: <?php echo esc_attr(min(100, $progress['percent'])); ?>%; background: <?php echo $progress['complete'] ? '#48bb78' : '#667eea'; ?>;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="ackm-chatassist-add-goal" class="button"><?php esc_html_e('Add Goal', 'intelligizedigital-chatassist'); ?></button>
                <p class="description ackm-chatassist-mt-10"><?php esc_html_e('Custom goals: trigger via jQuery(document).trigger("ackm_chatassist_goal", {goal_id: "your-goal-id"})', 'intelligizedigital-chatassist'); ?></p>
                <?php
                $goals_js  = 'jQuery(function($){';
                $goals_js .= '$("#ackm-chatassist-add-goal").on("click",function(){';
                $goals_js .= 'var cnt=$("#ackm-chatassist-goals-list .ackm-chatassist-goal-row").length;';
                $goals_js .= 'var tpl=$("#ackm-chatassist-goals-list .ackm-chatassist-goal-row").first().clone();';
                $goals_js .= 'tpl.find("input,select").each(function(){var n=$(this).attr("name");if(n)$(this).attr("name",n.replace(/\[\\d+\]/,"["+cnt+"]"));});';
                $goals_js .= 'tpl.find("input[type=text],input[type=number]").val("");';
                $goals_js .= 'tpl.find("input[name$=\'[id]\']").val("goal-"+(cnt+1));';
                $goals_js .= 'tpl.find("input[name$=\'[target]\']").val("100");';
                $goals_js .= 'var today=new Date();var d=today.getFullYear()+"-"+("0"+(today.getMonth()+1)).slice(-2)+"-"+("0"+today.getDate()).slice(-2);';
                $goals_js .= 'tpl.find("input[name$=\'[start_date]\']").val(d);';
                $goals_js .= 'tpl.find("input[name$=\'[name]\']").val("");';
                $goals_js .= 'tpl.find(".ackm-chatassist-goal-row>div:last-child").remove();';
                $goals_js .= '$("#ackm-chatassist-goals-list").append(tpl);});';
                $goals_js .= '$(document).on("click",".ackm-chatassist-remove-goal",function(){$(this).closest(".ackm-chatassist-goal-row").remove();});';
                $goals_js .= '});';
                wp_add_inline_script('ackm-chatassist-admin-js', $goals_js);
                ?>
            </div>
        </div>
        <?php else : ?>
        <div style="text-align: center; padding: 60px 40px; background: linear-gradient(135deg, var(--ackm-chatassist-gray-50) 0%, white 100%); border-radius: var(--ackm-chatassist-radius-xl); margin-top: 32px; border: 2px dashed var(--ackm-chatassist-gray-300);">
            <span class="dashicons dashicons-chart-bar ackm-chatassist-empty-icon"></span>
            <p style="margin-top: 20px; color: var(--ackm-chatassist-gray-600); font-size: 15px; font-weight: 500;"><?php esc_html_e('Enable analytics tracking to see statistics here.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <?php endif; ?>

        <div class="ackm-chatassist-btn-group">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=ackm_chatassist&ackm_chatassist_export=csv'), 'ackm_chatassist_export')); ?>" class="ackm-chatassist-btn ackm-chatassist-btn-secondary ackm-chatassist-link-plain">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export CSV', 'intelligizedigital-chatassist'); ?>
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=ackm_chatassist&ackm_chatassist_export=pdf'), 'ackm_chatassist_export')); ?>" class="ackm-chatassist-btn ackm-chatassist-btn-secondary ackm-chatassist-link-plain" target="_blank">
                <span class="dashicons dashicons-media-document"></span>
                <?php esc_html_e('Export PDF', 'intelligizedigital-chatassist'); ?>
            </a>
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render heatmap visualization (scroll depth, clicks)
     */
    private function render_heatmap_fields() {
        $heatmap_enabled = get_option('ackm_chatassist_heatmap_enabled', 'no');
        $heatmap_data = get_option('ackm_chatassist_heatmap_data', array());
        $heatmap_data = is_array($heatmap_data) ? $heatmap_data : array();
        $scroll_dist = array(25 => 0, 50 => 0, 75 => 0, 100 => 0);
        $total_clicks = 0;
        foreach (array_slice($heatmap_data, -14, 14, true) as $day_data) {
            if (isset($day_data['scroll']) && is_array($day_data['scroll'])) {
                foreach ($day_data['scroll'] as $v) {
                    if (isset($scroll_dist[$v])) {
                        $scroll_dist[$v]++;
                    }
                }
            }
            if (isset($day_data['clicks']) && is_array($day_data['clicks'])) {
                $total_clicks += count($day_data['clicks']);
            }
        }
        $max_scroll = max(1, max($scroll_dist));
        ?>
        <p class="description"><?php esc_html_e('Enable heatmap in Integrations tab to collect scroll depth and click data.', 'intelligizedigital-chatassist'); ?></p>
        <?php if ($heatmap_enabled === 'yes') : ?>
        <div class="ackm-chatassist-mt-25">
            <h3><?php esc_html_e('Scroll Depth Distribution (Last 14 Days)', 'intelligizedigital-chatassist'); ?></h3>
            <p class="description"><?php esc_html_e('How many users reached each scroll depth milestone.', 'intelligizedigital-chatassist'); ?></p>
            <div class="ackm-chatassist-flex-grid-4">
                <?php foreach ($scroll_dist as $pct => $count) : ?>
                <div style="background: linear-gradient(to top, rgba(102,126,234,0.3) <?php echo esc_attr(($count / $max_scroll) * 100); ?>%, #f0f0f0 0%); border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #667eea;"><?php echo esc_html($pct); ?>%</div>
                    <div style="font-size: 14px; color: #666;"><?php echo esc_html(number_format($count)); ?> <?php esc_html_e('reaches', 'intelligizedigital-chatassist'); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="ackm-chatassist-mt-25">
            <h3><?php esc_html_e('Click Activity', 'intelligizedigital-chatassist'); ?></h3>
            <p class="description"><?php esc_html_e('Total click events recorded in the last 14 days.', 'intelligizedigital-chatassist'); ?></p>
            <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 10px;">
                <span style="font-size: 28px; font-weight: bold; color: #667eea;"><?php echo esc_html(number_format($total_clicks)); ?></span>
                <span style="color: #666;"> <?php esc_html_e('clicks', 'intelligizedigital-chatassist'); ?></span>
            </div>
        </div>
        <?php if (array_sum($scroll_dist) === 0 && $total_clicks === 0) : ?>
        <p style="margin-top: 20px; color: #666;"><?php esc_html_e('No heatmap data yet. Data is collected when visitors scroll and interact with your site.', 'intelligizedigital-chatassist'); ?></p>
        <?php endif; ?>
        <?php else : ?>
        <div style="margin-top: 20px; padding: 30px; background: #f9f9f9; border: 1px dashed #ddd; border-radius: 8px;">
            <p><?php esc_html_e('Enable the Heatmap option in the Integrations tab to start collecting scroll depth and click data.', 'intelligizedigital-chatassist'); ?></p>
            <a href="#" class="button ackm-chatassist-tab" data-tab="integrations"><?php esc_html_e('Go to Integrations', 'intelligizedigital-chatassist'); ?></a>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render marketing fields (campaigns, coupon, newsletter)
     */
    private function render_marketing_fields() {
        $coupon = get_option('ackm_chatassist_coupon_code', '');
        $newsletter = get_option('ackm_chatassist_newsletter_enabled', 'no');
        $newsletter_timing = get_option('ackm_chatassist_newsletter_timing', 'on_chat_open');
        $newsletter_title = get_option('ackm_chatassist_newsletter_title', __('Subscribe to our newsletter', 'intelligizedigital-chatassist'));
        $newsletter_cta = get_option('ackm_chatassist_newsletter_cta', __('Subscribe', 'intelligizedigital-chatassist'));
        $newsletter_webhook = get_option('ackm_chatassist_newsletter_webhook', '');
        ?>
        <h3><?php esc_html_e('Campaign Tracking', 'intelligizedigital-chatassist'); ?></h3>
        <p class="description"><?php esc_html_e('Add ?campaign=xyz to any URL to pass the campaign to your chat (e.g. ?campaign=summer_sale).', 'intelligizedigital-chatassist'); ?></p>
        <h3><?php esc_html_e('Coupon Code', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_coupon_code"><?php esc_html_e('Default Coupon', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" name="ackm_chatassist_coupon_code" value="<?php echo esc_attr($coupon); ?>" class="regular-text" placeholder="SAVE10" />
            <p class="description"><?php esc_html_e('Pass this coupon to your chat URL as query param. Your chat can display it to users.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <h3><?php esc_html_e('Newsletter Signup', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_newsletter_enabled" value="yes" <?php checked('yes', $newsletter); ?> /> <?php esc_html_e('Enable newsletter signup modal', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_newsletter_timing"><?php esc_html_e('When to show', 'intelligizedigital-chatassist'); ?></label>
            <select name="ackm_chatassist_newsletter_timing" id="ackm_chatassist_newsletter_timing">
                <option value="on_chat_open" <?php selected($newsletter_timing, 'on_chat_open'); ?>><?php esc_html_e('When chat opens', 'intelligizedigital-chatassist'); ?></option>
                <option value="after_first_message" <?php selected($newsletter_timing, 'after_first_message'); ?>><?php esc_html_e('After first message sent', 'intelligizedigital-chatassist'); ?></option>
                <option value="on_exit_intent" <?php selected($newsletter_timing, 'on_exit_intent'); ?>><?php esc_html_e('On exit intent', 'intelligizedigital-chatassist'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('"After first message" requires your chat to send postMessage: {type: "ackm_chatassist_message_sent"}', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_newsletter_title"><?php esc_html_e('Modal Title', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" name="ackm_chatassist_newsletter_title" value="<?php echo esc_attr($newsletter_title); ?>" class="regular-text" />
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_newsletter_cta"><?php esc_html_e('Button Text', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" name="ackm_chatassist_newsletter_cta" value="<?php echo esc_attr($newsletter_cta); ?>" />
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_newsletter_webhook"><?php esc_html_e('Webhook URL (Zapier/Mailchimp)', 'intelligizedigital-chatassist'); ?></label>
            <input type="url" name="ackm_chatassist_newsletter_webhook" value="<?php echo esc_attr($newsletter_webhook); ?>" class="regular-text" placeholder="https://hooks.zapier.com/..." />
            <p class="description"><?php esc_html_e('Receive signups via webhook. Payload: email, source=ackm_chatassist', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render mobile/PWA fields
     */
    private function render_mobile_fields() {
        $pwa = get_option('ackm_chatassist_pwa_enabled', 'no');
        $push = get_option('ackm_chatassist_push_enabled', 'no');
        $vapid_pub = class_exists('Ackm_ChatAssist_Push') ? Ackm_ChatAssist_Push::get_vapid_public() : '';
        $subs = get_option('ackm_chatassist_push_subscriptions', array());
        $sub_count = is_array($subs) ? count($subs) : 0;
        ?>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_pwa_enabled" value="yes" <?php checked('yes', $pwa); ?> /> <?php esc_html_e('Enable PWA support', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Adds manifest, service worker, and app-like meta tags. Users can "Add to Home Screen".', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <hr style="margin: 24px 0;" />
        <h3><?php esc_html_e('Push Notifications', 'intelligizedigital-chatassist'); ?></h3>
        <div class="notice notice-info inline" style="margin: 0 0 16px;">
            <p>
                <?php
                printf(
                    /* translators: %s: URL to a VAPID key generator tool */
                    esc_html__( 'Web push notifications require VAPID keys. Generate a key pair at %s, then enter the keys in your push service configuration. Subscriber endpoints collected by this plugin can be exported and used with any WebPush-compatible server.', 'intelligizedigital-chatassist' ),
                    '<a href="https://web-push-codelab.glitch.me" target="_blank" rel="noopener noreferrer">web-push-codelab.glitch.me</a>'
                );
                ?>
            </p>
        </div>
        <div class="ackm-chatassist-form-group">
            <p><strong><?php echo esc_html( number_format( $sub_count ) ); ?></strong> <?php esc_html_e( 'push subscribers stored', 'intelligizedigital-chatassist' ); ?></p>
        </div>
        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render triggers fields (delay, scroll, exit-intent)
     */
    private function render_triggers_fields() {
        $delay = get_option('ackm_chatassist_delay_seconds', '0');
        $scroll = get_option('ackm_chatassist_scroll_depth', '0');
        $exit_intent = get_option('ackm_chatassist_exit_intent', 'no');
        $proactive = get_option('ackm_chatassist_proactive_message', '');
        $proactive_delay = get_option('ackm_chatassist_proactive_delay', '10');
        $pre_chat = get_option('ackm_chatassist_pre_chat_form', 'no');
        $pre_chat_fields = get_option('ackm_chatassist_pre_chat_fields', 'name,email');
        $unread = get_option('ackm_chatassist_unread_badge', 'no');
        ?>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_delay_seconds"><?php esc_html_e('Delay Before Show (seconds)', 'intelligizedigital-chatassist'); ?></label>
            <input type="number" name="ackm_chatassist_delay_seconds" value="<?php echo esc_attr($delay); ?>" min="0" max="300" />
            <p class="description"><?php esc_html_e('Show chat button after X seconds. 0 = show immediately.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_scroll_depth"><?php esc_html_e('Scroll Depth Trigger (%)', 'intelligizedigital-chatassist'); ?></label>
            <input type="number" name="ackm_chatassist_scroll_depth" value="<?php echo esc_attr($scroll); ?>" min="0" max="100" />
            <p class="description"><?php esc_html_e('Show button when user scrolls X% of page. 0 = disabled.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_exit_intent" value="yes" <?php checked('yes', $exit_intent); ?> /> <?php esc_html_e('Exit Intent Trigger', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Show button when user moves mouse to leave the page.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_proactive_message"><?php esc_html_e('Proactive Message', 'intelligizedigital-chatassist'); ?></label>
            <textarea class="ackm-chatassist-w-full" name="ackm_chatassist_proactive_message" rows="3"><?php echo esc_textarea($proactive); ?></textarea>
            <p class="description"><?php esc_html_e('Optional message bubble shown before chat opens. Leave empty to disable.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_proactive_delay"><?php esc_html_e('Proactive Message Delay (seconds)', 'intelligizedigital-chatassist'); ?></label>
            <input type="number" name="ackm_chatassist_proactive_delay" value="<?php echo esc_attr($proactive_delay); ?>" min="1" />
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_pre_chat_form" value="yes" <?php checked('yes', $pre_chat); ?> /> <?php esc_html_e('Pre-Chat Form', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Collect name/email before opening chat.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_pre_chat_fields"><?php esc_html_e('Pre-Chat Fields', 'intelligizedigital-chatassist'); ?></label>
            <input type="text" name="ackm_chatassist_pre_chat_fields" value="<?php echo esc_attr($pre_chat_fields); ?>" placeholder="name,email,phone" />
            <p class="description"><?php esc_html_e('Comma-separated: name, email, phone', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_unread_badge" value="yes" <?php checked('yes', $unread); ?> /> <?php esc_html_e('Unread Message Badge', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render advanced fields (theme, GDPR, extended rules)
     */
    private function render_advanced_fields() {
        $theme = get_option('ackm_chatassist_theme', 'light');
        $gdpr = get_option('ackm_chatassist_gdpr_consent', 'no');
        $gdpr_msg = get_option('ackm_chatassist_gdpr_message', '');
        $day_based = get_option('ackm_chatassist_day_based', 'no');
        $show_days = get_option('ackm_chatassist_show_days', array());
        $device_based = get_option('ackm_chatassist_device_based', 'no');
        $devices = get_option('ackm_chatassist_show_on_devices', array('desktop', 'mobile', 'tablet'));
        $woo_pages = get_option('ackm_chatassist_woo_pages', array());
        $days = class_exists('Ackm_ChatAssist_Extended_Rules') ? Ackm_ChatAssist_Extended_Rules::get_days() : array();
        ?>
        <h3><?php esc_html_e('Layout', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_layout"><?php esc_html_e('Widget Layout', 'intelligizedigital-chatassist'); ?></label>
            <select name="ackm_chatassist_layout">
                <option value="popup" <?php selected('popup', get_option('ackm_chatassist_layout', 'popup')); ?>><?php esc_html_e('Popup (default)', 'intelligizedigital-chatassist'); ?></option>
                <option value="collapsible" <?php selected('collapsible', get_option('ackm_chatassist_layout', 'popup')); ?>><?php esc_html_e('Collapsible', 'intelligizedigital-chatassist'); ?></option>
            </select>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_typing_indicator" value="yes" <?php checked('yes', get_option('ackm_chatassist_typing_indicator', 'no')); ?> /> <?php esc_html_e('Typing Indicator', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_sound_enabled" value="yes" <?php checked('yes', get_option('ackm_chatassist_sound_enabled', 'no')); ?> /> <?php esc_html_e('Sound for New Messages', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_pre_chat_to_url" value="yes" <?php checked('yes', get_option('ackm_chatassist_pre_chat_to_url', 'yes')); ?> /> <?php esc_html_e('Pass Pre-Chat Data to Chat URL', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <h3><?php esc_html_e('Appearance', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_theme"><?php esc_html_e('Theme', 'intelligizedigital-chatassist'); ?></label>
            <select name="ackm_chatassist_theme">
                <option value="light" <?php selected('light', $theme); ?>><?php esc_html_e('Light', 'intelligizedigital-chatassist'); ?></option>
                <option value="dark" <?php selected('dark', $theme); ?>><?php esc_html_e('Dark', 'intelligizedigital-chatassist'); ?></option>
            </select>
        </div>
        <h3><?php esc_html_e('GDPR / Consent', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_gdpr_consent" value="yes" <?php checked('yes', $gdpr); ?> /> <?php esc_html_e('Require Consent Before Loading', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_gdpr_message"><?php esc_html_e('Consent Message', 'intelligizedigital-chatassist'); ?></label>
            <textarea class="ackm-chatassist-w-full" name="ackm_chatassist_gdpr_message" rows="3"><?php echo esc_textarea($gdpr_msg); ?></textarea>
        </div>
        <h3><?php esc_html_e('Day of Week', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_day_based" value="yes" <?php checked('yes', $day_based); ?> /> <?php esc_html_e('Show Only on Selected Days', 'intelligizedigital-chatassist'); ?></label>
            <?php if (!empty($days)) : ?>
            <div class="ackm-chatassist-checkbox-pills">
                <?php foreach ($days as $key => $label) : ?>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_show_days[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, (array)$show_days)); ?> /> <span><?php echo esc_html($label); ?></span></label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <h3><?php esc_html_e('Device', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_device_based" value="yes" <?php checked('yes', $device_based); ?> /> <?php esc_html_e('Show Only on Selected Devices', 'intelligizedigital-chatassist'); ?></label>
            <div class="ackm-chatassist-checkbox-pills">
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_show_on_devices[]" value="desktop" <?php checked(in_array('desktop', (array)$devices)); ?> /> <span><?php esc_html_e('Desktop', 'intelligizedigital-chatassist'); ?></span></label>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_show_on_devices[]" value="mobile" <?php checked(in_array('mobile', (array)$devices)); ?> /> <span><?php esc_html_e('Mobile', 'intelligizedigital-chatassist'); ?></span></label>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_show_on_devices[]" value="tablet" <?php checked(in_array('tablet', (array)$devices)); ?> /> <span><?php esc_html_e('Tablet', 'intelligizedigital-chatassist'); ?></span></label>
            </div>
        </div>
        <?php if (function_exists('is_woocommerce')) : ?>
        <h3><?php esc_html_e('WooCommerce Pages', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label><?php esc_html_e('Show on WooCommerce Pages', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Leave all unchecked to show on all pages. Select specific pages to show only there.', 'intelligizedigital-chatassist'); ?></p>
            <div class="ackm-chatassist-checkbox-pills">
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_woo_pages[]" value="shop" <?php checked(in_array('shop', (array)$woo_pages)); ?> /> <span><?php esc_html_e('Shop', 'intelligizedigital-chatassist'); ?></span></label>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_woo_pages[]" value="product" <?php checked(in_array('product', (array)$woo_pages)); ?> /> <span><?php esc_html_e('Product', 'intelligizedigital-chatassist'); ?></span></label>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_woo_pages[]" value="cart" <?php checked(in_array('cart', (array)$woo_pages)); ?> /> <span><?php esc_html_e('Cart', 'intelligizedigital-chatassist'); ?></span></label>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_woo_pages[]" value="checkout" <?php checked(in_array('checkout', (array)$woo_pages)); ?> /> <span><?php esc_html_e('Checkout', 'intelligizedigital-chatassist'); ?></span></label>
                <label class="ackm-chatassist-checkbox-pill"><input type="checkbox" name="ackm_chatassist_woo_pages[]" value="account" <?php checked(in_array('account', (array)$woo_pages)); ?> /> <span><?php esc_html_e('Account', 'intelligizedigital-chatassist'); ?></span></label>
            </div>
        </div>
        <?php endif; ?>
        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render widget profiles tab (multiple chat URLs per page)
     */
    private function render_widget_profiles_fields() {
        $multi = get_option('ackm_chatassist_multi_widgets', 'no');
        $profiles = get_option('ackm_chatassist_widget_profiles', array());
        $pages = get_pages(array('sort_column' => 'post_title'));
        $conditions = array('homepage' => __('Homepage', 'intelligizedigital-chatassist'), 'shop' => __('Shop', 'intelligizedigital-chatassist'), 'product' => __('Product', 'intelligizedigital-chatassist'), 'cart' => __('Cart', 'intelligizedigital-chatassist'), 'checkout' => __('Checkout', 'intelligizedigital-chatassist'));
        ?>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_multi_widgets" value="yes" <?php checked('yes', $multi); ?> /> <?php esc_html_e('Enable multiple widget profiles', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Use different chat URLs per page/post (e.g. sales vs support).', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div id="ackm-chatassist-profiles-list" style="<?php echo $multi === 'yes' ? '' : 'display: none;'; ?>">
            <h3><?php esc_html_e('Profiles', 'intelligizedigital-chatassist'); ?></h3>
            <p class="description"><?php esc_html_e('First matching profile wins. Add conditions: pages, WooCommerce pages, or post types.', 'intelligizedigital-chatassist'); ?></p>
            <div id="ackm-chatassist-profiles-container">
                <?php
                if (empty($profiles)) {
                    $profiles = array(array('id' => 'profile-1', 'name' => 'Support', 'url' => '', 'title' => '', 'position' => 'right', 'color' => '', 'pages' => array(), 'exclude_pages' => array(), 'conditions' => array(), 'post_types' => array()));
                }
                foreach ($profiles as $i => $p) :
                    $p = wp_parse_args($p, array('id' => '', 'name' => '', 'url' => '', 'title' => '', 'position' => 'right', 'color' => '', 'pages' => array(), 'exclude_pages' => array(), 'conditions' => array(), 'post_types' => array()));
                ?>
                <div class="ackm-chatassist-profile-card" style="border: 1px solid #ddd; background: #f9f9f9; padding: 16px; margin-bottom: 16px; border-radius: 6px;">
                    <div class="ackm-chatassist-flex-between">
                        <strong><?php echo esc_html($p['name'] ?: __('Profile', 'intelligizedigital-chatassist') . ' ' . ($i + 1)); ?></strong>
                        <button type="button" class="button ackm-chatassist-remove-profile"><?php esc_html_e('Remove', 'intelligizedigital-chatassist'); ?></button>
                    </div>
                    <input type="hidden" name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][id]" value="<?php echo esc_attr($p['id']); ?>" />
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('Name', 'intelligizedigital-chatassist'); ?></label>
                        <input type="text" name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][name]" value="<?php echo esc_attr($p['name']); ?>" /> 
                    </div>
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('Chat URL', 'intelligizedigital-chatassist'); ?></label>
                        <input type="url" name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][url]" value="<?php echo esc_attr($p['url']); ?>" class="regular-text" />
                    </div>
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('Title (optional)', 'intelligizedigital-chatassist'); ?></label>
                        <input type="text" name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][title]" value="<?php echo esc_attr($p['title']); ?>" />
                    </div>
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('Position', 'intelligizedigital-chatassist'); ?></label>
                        <select name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][position]">
                            <option value="right" <?php selected('right', $p['position']); ?>><?php esc_html_e('Right', 'intelligizedigital-chatassist'); ?></option>
                            <option value="left" <?php selected('left', $p['position']); ?>><?php esc_html_e('Left', 'intelligizedigital-chatassist'); ?></option>
                        </select>
                    </div>
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('Color (optional)', 'intelligizedigital-chatassist'); ?></label>
                        <input type="text" name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][color]" value="<?php echo esc_attr($p['color']); ?>" class="ackm-chatassist-color-picker" />
                    </div>
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('Show on pages', 'intelligizedigital-chatassist'); ?></label>
                        <select name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][pages][]" multiple style="width:100%;height:80px;">
                            <?php foreach ($pages as $pg) : ?>
                            <option value="<?php echo esc_attr($pg->ID); ?>" <?php selected(in_array($pg->ID, (array)$p['pages'])); ?>><?php echo esc_html($pg->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select pages where this profile applies.', 'intelligizedigital-chatassist'); ?></p>
                    </div>
                    <div class="ackm-chatassist-form-group">
                        <label><?php esc_html_e('WooCommerce conditions', 'intelligizedigital-chatassist'); ?></label>
                        <?php foreach ($conditions as $cval => $clabel) : ?>
                        <label style="display:block;"><input type="checkbox" name="ackm_chatassist_widget_profiles[<?php echo esc_attr($i); ?>][conditions][]" value="<?php echo esc_attr($cval); ?>" <?php checked(in_array($cval, (array)$p['conditions'])); ?> /> <?php echo esc_html($clabel); ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="ackm-chatassist-add-profile" class="button"><?php esc_html_e('Add Profile', 'intelligizedigital-chatassist'); ?></button>
        </div>
        <p class="description ackm-chatassist-mt-15"><?php esc_html_e('Default URL (General tab) is used when no profile matches.', 'intelligizedigital-chatassist'); ?></p>
        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
        $profiles_js  = 'jQuery(function($){';
        $profiles_js .= '$("input[name=\'ackm_chatassist_multi_widgets\']").on("change",function(){';
        $profiles_js .= '$("#ackm-chatassist-profiles-list").toggle($(this).prop("checked"));});';
        $profiles_js .= '$("#ackm-chatassist-add-profile").on("click",function(){';
        $profiles_js .= 'var tpl=$("#ackm-chatassist-profiles-container .ackm-chatassist-profile-card").first().clone();';
        $profiles_js .= 'var idx=$("#ackm-chatassist-profiles-container .ackm-chatassist-profile-card").length;';
        $profiles_js .= 'tpl.find("input,select").each(function(){var n=$(this).attr("name");if(n)$(this).attr("name",n.replace(/\[\\d+\]/,"["+idx+"]"));});';
        $profiles_js .= 'tpl.find("input[type=text],input[type=url]").val("");';
        $profiles_js .= 'tpl.find("input[name$=\'[id]\']").val("profile-"+(idx+1));';
        $profiles_js .= 'tpl.find("input[name$=\'[name]\']").val("");';
        $profiles_js .= 'tpl.find("select").prop("selectedIndex",-1);';
        $profiles_js .= 'tpl.find("input[type=checkbox]").prop("checked",false);';
        $profiles_js .= '$("#ackm-chatassist-profiles-container").append(tpl);});';
        $profiles_js .= '$(document).on("click",".ackm-chatassist-remove-profile",function(){$(this).closest(".ackm-chatassist-profile-card").remove();});';
        $profiles_js .= '});';
        wp_add_inline_script('ackm-chatassist-admin-js', $profiles_js);
        ?>
        <?php
    }

    /**
     * Render integrations tab (webhooks, Slack, Discord, CRM, A/B testing)
     */
    private function render_integrations_fields() {
        $integration = get_option('ackm_chatassist_native_integration', '');
        $replace = get_option('ackm_chatassist_integration_replace_widget', 'yes');
        $options = class_exists('Ackm_ChatAssist_Integrations') ? Ackm_ChatAssist_Integrations::get_integration_options() : array();
        ?>
        <h3><?php esc_html_e('Direct Chat Integrations', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_native_integration"><?php esc_html_e('Chat Service', 'intelligizedigital-chatassist'); ?></label>
            <select name="ackm_chatassist_native_integration" id="ackm_chatassist_native_integration">
                <?php foreach ($options as $val => $label) : ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($integration, $val); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Use Intercom, Crisp, Drift, Tawk.to, or LiveChat instead of custom URL.', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <?php if (!empty($integration)) : ?>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_integration_replace_widget" value="yes" <?php checked('yes', $replace); ?> /> <?php esc_html_e('Hide native widget when using this integration', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-integration-keys ackm-chatassist-mt-15">
            <?php if ($integration === 'intercom') : ?>
            <div class="ackm-chatassist-form-group">
                <label for="ackm_chatassist_intercom_app_id"><?php esc_html_e('Intercom App ID', 'intelligizedigital-chatassist'); ?></label>
                <input type="text" name="ackm_chatassist_intercom_app_id" value="<?php echo esc_attr(get_option('ackm_chatassist_intercom_app_id', '')); ?>" class="regular-text" />
            </div>
            <?php elseif ($integration === 'crisp') : ?>
            <div class="ackm-chatassist-form-group">
                <label for="ackm_chatassist_crisp_site_id"><?php esc_html_e('Crisp Site ID', 'intelligizedigital-chatassist'); ?></label>
                <input type="text" name="ackm_chatassist_crisp_site_id" value="<?php echo esc_attr(get_option('ackm_chatassist_crisp_site_id', '')); ?>" class="regular-text" />
            </div>
            <?php elseif ($integration === 'drift') : ?>
            <div class="ackm-chatassist-form-group">
                <label for="ackm_chatassist_drift_id"><?php esc_html_e('Drift ID', 'intelligizedigital-chatassist'); ?></label>
                <input type="text" name="ackm_chatassist_drift_id" value="<?php echo esc_attr(get_option('ackm_chatassist_drift_id', '')); ?>" class="regular-text" />
            </div>
            <?php elseif ($integration === 'tawk') : ?>
            <div class="ackm-chatassist-form-group">
                <label for="ackm_chatassist_tawk_id"><?php esc_html_e('Tawk.to Property ID', 'intelligizedigital-chatassist'); ?></label>
                <input type="text" name="ackm_chatassist_tawk_id" value="<?php echo esc_attr(get_option('ackm_chatassist_tawk_id', '')); ?>" class="regular-text" />
            </div>
            <div class="ackm-chatassist-form-group">
                <label for="ackm_chatassist_tawk_key"><?php esc_html_e('Tawk.to Widget ID', 'intelligizedigital-chatassist'); ?></label>
                <input type="text" name="ackm_chatassist_tawk_key" value="<?php echo esc_attr(get_option('ackm_chatassist_tawk_key', '')); ?>" class="regular-text" />
            </div>
            <?php elseif ($integration === 'livechat') : ?>
            <div class="ackm-chatassist-form-group">
                <label for="ackm_chatassist_livechat_license"><?php esc_html_e('LiveChat License ID', 'intelligizedigital-chatassist'); ?></label>
                <input type="text" name="ackm_chatassist_livechat_license" value="<?php echo esc_attr(get_option('ackm_chatassist_livechat_license', '')); ?>" class="regular-text" />
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <hr style="margin: 25px 0;" />
        <?php
        $webhook = get_option('ackm_chatassist_webhook_url', '');
        $slack = get_option('ackm_chatassist_slack_webhook', '');
        $discord = get_option('ackm_chatassist_discord_webhook', '');
        $slack_opens = get_option('ackm_chatassist_slack_notify_opens', 'no');
        $slack_messages = get_option('ackm_chatassist_slack_notify_messages', 'no');
        $slack_leads = get_option('ackm_chatassist_slack_notify_leads', 'no');
        $discord_messages = get_option('ackm_chatassist_discord_notify_messages', 'no');
        $discord_leads = get_option('ackm_chatassist_discord_notify_leads', 'no');
        $crm = get_option('ackm_chatassist_crm_webhook', '');
        $crm_format = get_option('ackm_chatassist_crm_format', 'hubspot');
        $ab = get_option('ackm_chatassist_ab_testing', 'no');
        $conversion = get_option('ackm_chatassist_conversion_tracking', 'no');
        $heatmap = get_option('ackm_chatassist_heatmap_enabled', 'no');
        ?>
        <h3><?php esc_html_e('Zapier / Webhook', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_webhook_url"><?php esc_html_e('Webhook URL', 'intelligizedigital-chatassist'); ?></label>
            <input type="url" name="ackm_chatassist_webhook_url" value="<?php echo esc_attr($webhook); ?>" class="regular-text" placeholder="https://hooks.zapier.com/..." />
            <p class="description"><?php esc_html_e('Receive events: widget_opened, widget_closed, message_sent, lead_captured', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <h3><?php esc_html_e('Slack', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_slack_webhook"><?php esc_html_e('Slack Incoming Webhook URL', 'intelligizedigital-chatassist'); ?></label>
            <input type="url" name="ackm_chatassist_slack_webhook" value="<?php echo esc_attr($slack); ?>" class="regular-text" />
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_slack_notify_opens" value="yes" <?php checked('yes', $slack_opens); ?> /> <?php esc_html_e('Notify on widget opens', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_slack_notify_messages" value="yes" <?php checked('yes', $slack_messages); ?> /> <?php esc_html_e('Notify on new messages', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_slack_notify_leads" value="yes" <?php checked('yes', $slack_leads); ?> /> <?php esc_html_e('Notify on lead capture', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <h3><?php esc_html_e('Discord', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_discord_webhook"><?php esc_html_e('Discord Webhook URL', 'intelligizedigital-chatassist'); ?></label>
            <input type="url" name="ackm_chatassist_discord_webhook" value="<?php echo esc_attr($discord); ?>" class="regular-text" />
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_discord_notify_messages" value="yes" <?php checked('yes', $discord_messages); ?> /> <?php esc_html_e('Notify on new messages', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_discord_notify_leads" value="yes" <?php checked('yes', $discord_leads); ?> /> <?php esc_html_e('Notify on lead capture', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <h3><?php esc_html_e('CRM (HubSpot / Salesforce)', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_crm_webhook"><?php esc_html_e('CRM Webhook URL', 'intelligizedigital-chatassist'); ?></label>
            <input type="url" name="ackm_chatassist_crm_webhook" value="<?php echo esc_attr($crm); ?>" class="regular-text" />
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_crm_format"><?php esc_html_e('Format', 'intelligizedigital-chatassist'); ?></label>
            <select name="ackm_chatassist_crm_format">
                <option value="hubspot" <?php selected('hubspot', $crm_format); ?>><?php esc_html_e('HubSpot', 'intelligizedigital-chatassist'); ?></option>
                <option value="salesforce" <?php selected('salesforce', $crm_format); ?>><?php esc_html_e('Salesforce', 'intelligizedigital-chatassist'); ?></option>
            </select>
        </div>
        <h3><?php esc_html_e('A/B Testing', 'intelligizedigital-chatassist'); ?></h3>
        <?php
        if ($ab === 'yes') {
            $variant_stats = Ackm_ChatAssist_Analytics::get_variant_stats(14);
            $variants_list = get_option('ackm_chatassist_ab_variants', array());
            if (!empty($variant_stats) || !empty($variants_list)) :
        ?>
        <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 6px;">
            <h4 style="margin-top: 0;"><?php esc_html_e('Variant Performance (Last 14 Days)', 'intelligizedigital-chatassist'); ?></h4>
            <table class="wp-list-table widefat fixed striped ackm-chatassist-mt-10">
                <thead><tr><th><?php esc_html_e('Variant', 'intelligizedigital-chatassist'); ?></th><th><?php esc_html_e('Opens', 'intelligizedigital-chatassist'); ?></th><th><?php esc_html_e('Conversions', 'intelligizedigital-chatassist'); ?></th><th><?php esc_html_e('Conv. Rate', 'intelligizedigital-chatassist'); ?></th></tr></thead>
                <tbody>
                <?php
                $all_ids = array_unique(array_merge(array_keys($variant_stats), array_column($variants_list, 'id')));
                foreach ($all_ids as $vid) :
                    $s = isset($variant_stats[$vid]) ? $variant_stats[$vid] : array('open' => 0, 'conversion' => 0);
                    $conv_rate = $s['open'] > 0 ? round(($s['conversion'] / $s['open']) * 100, 1) : 0;
                    $label = $vid === 'control' ? __('Control (default)', 'intelligizedigital-chatassist') : $vid;
                ?>
                <tr><td><?php echo esc_html($label); ?></td><td><?php echo esc_html($s['open']); ?></td><td><?php echo esc_html($s['conversion']); ?></td><td><?php echo esc_html($conv_rate); ?>%</td></tr>
                <?php endforeach; ?>
                <?php if (empty($all_ids)) : ?>
                <tr><td colspan="4"><?php esc_html_e('No variant data yet.', 'intelligizedigital-chatassist'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; } ?>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_ab_testing" value="yes" <?php checked('yes', $ab); ?> /> <?php esc_html_e('Enable A/B Testing', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Add variants below. Each variant: id, url, color (optional), weight (1-100).', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label for="ackm_chatassist_ab_variants"><?php esc_html_e('Variants (JSON)', 'intelligizedigital-chatassist'); ?></label>
            <?php
            $variants = get_option('ackm_chatassist_ab_variants', array());
            $variants_json = !empty($variants) ? wp_json_encode($variants, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : "[]";
            ?>
            <textarea class="ackm-chatassist-textarea-mono" name="ackm_chatassist_ab_variants" rows="8"><?php echo esc_textarea($variants_json); ?></textarea>
        </div>
        <h3><?php esc_html_e('Analytics Extensions', 'intelligizedigital-chatassist'); ?></h3>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_conversion_tracking" value="yes" <?php checked('yes', $conversion); ?> /> <?php esc_html_e('Conversion Tracking', 'intelligizedigital-chatassist'); ?></label>
            <p class="description"><?php esc_html_e('Track conversions via JS: jQuery(document).trigger("ackm_chatassist_conversion")', 'intelligizedigital-chatassist'); ?></p>
        </div>
        <div class="ackm-chatassist-form-group">
            <label><input type="checkbox" name="ackm_chatassist_heatmap_enabled" value="yes" <?php checked('yes', $heatmap); ?> /> <?php esc_html_e('Heatmap (scroll depth & clicks)', 'intelligizedigital-chatassist'); ?></label>
        </div>
        <div class="ackm-chatassist-btn-group">
            <button type="button" id="preview-save-changes" class="ackm-chatassist-btn ackm-chatassist-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Save Changes', 'intelligizedigital-chatassist'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * AJAX handler for dismissing the config notice
     */
    public function ajax_dismiss_notice() {
        check_ajax_referer('ackm_chatassist_dismiss', 'nonce');
        update_user_meta(get_current_user_id(), 'ackm_chatassist_config_notice_dismissed', 1);
        wp_send_json_success();
    }

    /**
     * Display an admin notice if the chat URL is not set.
     * Per Guideline 11: Limited scope - only on dashboard, dismissible with persistence.
     */
    public function admin_notice() {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }
        
        // Only show on dashboard (index.php) - not on every admin page
        if ('dashboard' !== $screen->id) {
            return;
        }
        
        // Skip if we're already on the settings page
        if ('toplevel_page_ackm_chatassist' === $screen->id) {
            return;
        }
        
        // Check if user has dismissed the notice
        $dismissed = get_user_meta(get_current_user_id(), 'ackm_chatassist_config_notice_dismissed', true);
        if ($dismissed) {
            return;
        }
        
        // Show notice if URL is not set, no native integration, and no widget profiles
        $url = get_option('ackm_chatassist_url', '');
        $native = get_option('ackm_chatassist_native_integration', '');
        $multi = get_option('ackm_chatassist_multi_widgets', 'no');
        $profiles = get_option('ackm_chatassist_widget_profiles', array());
        $has_profile_url = false;
        if ($multi === 'yes' && is_array($profiles)) {
            foreach ($profiles as $p) {
                if (!empty($p['url'])) {
                    $has_profile_url = true;
                    break;
                }
            }
        }
        if (empty($url) && empty($native) && !$has_profile_url) {
            ?>
            <div class="notice notice-warning is-dismissible" data-notice="ackm-chatassist-config">
                <p>
                    <?php esc_html_e('ChatAssist is activated but not configured yet.', 'intelligizedigital-chatassist'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ackm_chatassist')); ?>">
                        <?php esc_html_e('Click here to configure', 'intelligizedigital-chatassist'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Handle form submission and settings update
     */
    public function handle_settings_update() {
        // Check if our form was submitted
        if (isset($_POST['option_page']) && sanitize_text_field(wp_unslash($_POST['option_page'])) === 'ackm_chatassist_options') {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ackm_chatassist_options-options')) {
                add_settings_error('ackm_chatassist_messages', 'ackm_chatassist_errors', __('Security check failed. Please try again.', 'intelligizedigital-chatassist'), 'error');
                return;
            }
            
            // Add a success message if settings were updated
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('ChatAssist settings updated successfully.', 'intelligizedigital-chatassist'); ?></p>
                </div>
                <?php
            });
        }
    }
} 