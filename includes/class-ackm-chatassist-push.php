<?php
/**
 * Push notifications subscription management.
 *
 * Collects browser push subscriptions via the Web Push API.
 * Sending notifications requires external VAPID key configuration.
 *
 * @package Ackm_ChatAssist
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ackm_ChatAssist_Push {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_ajax_ackm_chatassist_push_subscribe', array( __CLASS__, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_nopriv_ackm_chatassist_push_subscribe', array( __CLASS__, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_ackm_chatassist_push_send', array( __CLASS__, 'ajax_send' ) );
		add_action( 'wp_ajax_ackm_chatassist_push_generate_keys', array( __CLASS__, 'ajax_generate_keys' ) );
	}

	public static function is_enabled() {
		return get_option( 'ackm_chatassist_push_enabled', 'no' ) === 'yes'
			&& ! empty( self::get_vapid_public() );
	}

	public static function get_vapid_public() {
		return get_option( 'ackm_chatassist_vapid_public', '' );
	}

	public static function get_vapid_private() {
		return get_option( 'ackm_chatassist_vapid_private', '' );
	}

	/**
	 * AJAX: generate VAPID keys.
	 * Keys must be entered manually — generation requires external tooling.
	 */
	public static function ajax_generate_keys() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ackm_chatassist_push_admin' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'intelligizedigital-chatassist' ) ) );
		}
		wp_send_json_error( array(
			'message' => esc_html__( 'Please generate VAPID keys using a tool such as web-push-codelab.glitch.me and enter them manually in the settings.', 'intelligizedigital-chatassist' ),
		) );
	}

	/**
	 * AJAX: store a push subscription from the browser.
	 */
	public static function ajax_subscribe() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ackm_chatassist_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'intelligizedigital-chatassist' ) ) );
		}
		$raw = isset( $_POST['subscription'] ) ? sanitize_textarea_field( wp_unslash( $_POST['subscription'] ) ) : '';
		if ( empty( $raw ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No subscription data provided.', 'intelligizedigital-chatassist' ) ) );
		}
		$sub = json_decode( $raw, true );
		if ( empty( $sub['endpoint'] ) || empty( $sub['keys']['p256dh'] ) || empty( $sub['keys']['auth'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid subscription format.', 'intelligizedigital-chatassist' ) ) );
		}
		self::store_subscription( $sub );
		wp_send_json_success();
	}

	/**
	 * AJAX: send a push notification (admin-only stub).
	 */
	public static function ajax_send() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ackm_chatassist_push_admin' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'intelligizedigital-chatassist' ) ) );
		}
		wp_send_json_error( array(
			'message' => esc_html__( 'Push notification delivery requires a server-side WebPush library. Please use a dedicated push service or self-hosted solution with your stored VAPID keys.', 'intelligizedigital-chatassist' ),
		) );
	}

	/**
	 * Send push notifications to all subscribers.
	 * Returns error — sending requires a server-side WebPush implementation.
	 *
	 * @param string $title Notification title.
	 * @param string $body  Notification body.
	 * @return array Result array.
	 */
	public static function send_to_all( $title, $body = '' ) {
		return array(
			'sent'   => 0,
			'failed' => 0,
			'error'  => esc_html__( 'Push notification sending requires a WebPush server library. Configure VAPID keys and use a dedicated push service.', 'intelligizedigital-chatassist' ),
		);
	}

	/**
	 * REST API: store a push subscription.
	 */
	public static function register_routes() {
		register_rest_route( 'ackm-chatassist/v1', '/push/subscribe', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_subscribe' ),
			'permission_callback' => '__return_true',
		) );
	}

	public static function rest_subscribe( $request ) {
		$sub = $request->get_json_params();
		if ( empty( $sub['endpoint'] ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Invalid subscription', 'intelligizedigital-chatassist' ), array( 'status' => 400 ) );
		}
		self::store_subscription( $sub );
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Persist a push subscription (sanitized).
	 *
	 * @param array $sub Raw subscription array from browser.
	 */
	private static function store_subscription( array $sub ) {
		$endpoint = esc_url_raw( $sub['endpoint'] );
		if ( empty( $endpoint ) ) {
			return;
		}
		$keys = isset( $sub['keys'] ) && is_array( $sub['keys'] )
			? array_map( 'sanitize_text_field', $sub['keys'] )
			: array();

		$subs = get_option( 'ackm_chatassist_push_subscriptions', array() );
		$subs = is_array( $subs ) ? $subs : array();
		$key          = md5( $endpoint );
		$subs[ $key ] = array(
			'endpoint'        => $endpoint,
			'keys'            => $keys,
			'contentEncoding' => isset( $sub['contentEncoding'] ) ? sanitize_text_field( $sub['contentEncoding'] ) : 'aesgcm',
			'created'         => time(),
		);
		$subs = array_slice( $subs, -500, 500, true );
		update_option( 'ackm_chatassist_push_subscriptions', $subs );
	}
}
