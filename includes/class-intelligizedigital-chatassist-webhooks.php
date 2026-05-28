<?php
/**
 * Webhooks & Integrations for Intelligize ChatAssist
 *
 * @package Intelligize_ChatAssist
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Outgoing webhooks for Zapier, Slack, Discord, CRM
 */
class IntelligizeDigital_ChatAssist_Webhooks {

    public static function init() {
        add_action('intelligizedigital_chatassist_widget_opened', array(__CLASS__, 'on_widget_opened'));
        add_action('intelligizedigital_chatassist_widget_closed', array(__CLASS__, 'on_widget_closed'));
        add_action('intelligizedigital_chatassist_message_sent', array(__CLASS__, 'on_message_sent'));
        add_action('intelligizedigital_chatassist_lead_captured', array(__CLASS__, 'on_lead_captured'));
    }

    private static function fire_webhook($event, $payload) {
        $url = get_option('intelligizedigital_chatassist_webhook_url', '');
        if (empty($url)) {
            return;
        }
        $payload['event'] = $event;
        $payload['timestamp'] = gmdate('c');
        $payload['site'] = get_bloginfo('name');
        $payload['site_url'] = home_url();

        wp_remote_post($url, array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
            'blocking' => false,
        ));
    }

    private static function notify_slack($message, $payload = array()) {
        $url = get_option('intelligizedigital_chatassist_slack_webhook', '');
        if (empty($url)) {
            return;
        }
        $attach = array();
        if (!empty($payload)) {
            $attach[] = array(
                'fields' => array_map(function($k, $v) {
                    return array('title' => $k, 'value' => is_array($v) ? wp_json_encode($v) : $v, 'short' => true);
                }, array_keys($payload), $payload),
            );
        }
        wp_remote_post($url, array(
            'timeout' => 5,
            'body' => wp_json_encode(array(
                'text' => $message,
                'attachments' => $attach,
            )),
            'blocking' => false,
        ));
    }

    private static function notify_discord($message, $payload = array()) {
        $url = get_option('intelligizedigital_chatassist_discord_webhook', '');
        if (empty($url)) {
            return;
        }
        $embed = array(
            'description' => $message,
            'color' => 5814783,
            'fields' => array_map(function($k, $v) {
                return array('name' => $k, 'value' => is_array($v) ? wp_json_encode($v) : (string)$v, 'inline' => true);
            }, array_keys($payload), $payload),
        );
        wp_remote_post($url, array(
            'timeout' => 5,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array('content' => $message, 'embeds' => array($embed))),
            'blocking' => false,
        ));
    }

    public static function on_widget_opened() {
        $payload = array('page' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '');
        self::fire_webhook('widget_opened', $payload);
        if (get_option('intelligizedigital_chatassist_slack_notify_opens', 'no') === 'yes') {
            self::notify_slack('Chat widget opened', $payload);
        }
    }

    public static function on_widget_closed() {
        self::fire_webhook('widget_closed', array());
    }

    public static function on_message_sent() {
        $payload = array();
        self::fire_webhook('message_sent', $payload);
        if (get_option('intelligizedigital_chatassist_slack_notify_messages', 'no') === 'yes') {
            self::notify_slack('New chat message sent', $payload);
        }
        if (get_option('intelligizedigital_chatassist_discord_notify_messages', 'no') === 'yes') {
            self::notify_discord('New chat message sent', $payload);
        }
    }

    public static function on_lead_captured($lead_data) {
        $payload = array(
            'name' => isset($lead_data['name']) ? $lead_data['name'] : '',
            'email' => isset($lead_data['email']) ? $lead_data['email'] : '',
            'phone' => isset($lead_data['phone']) ? $lead_data['phone'] : '',
        );
        self::fire_webhook('lead_captured', $payload);
        self::send_to_crm($payload);
        if (get_option('intelligizedigital_chatassist_slack_notify_leads', 'no') === 'yes') {
            self::notify_slack('New lead captured: ' . $payload['email'], $payload);
        }
        if (get_option('intelligizedigital_chatassist_discord_notify_leads', 'no') === 'yes') {
            self::notify_discord('New lead captured', $payload);
        }
    }

    private static function send_to_crm($lead) {
        $crm_url = get_option('intelligizedigital_chatassist_crm_webhook', '');
        if (empty($crm_url)) {
            return;
        }
        $format = get_option('intelligizedigital_chatassist_crm_format', 'hubspot');
        $body = $format === 'salesforce' ? array(
            'FirstName' => $lead['name'],
            'Email' => $lead['email'],
            'Phone' => $lead['phone'],
        ) : array(
            'properties' => array(
                array('property' => 'email', 'value' => $lead['email']),
                array('property' => 'firstname', 'value' => $lead['name']),
                array('property' => 'phone', 'value' => $lead['phone']),
            ),
        );
        wp_remote_post($crm_url, array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'blocking' => false,
        ));
    }
}
