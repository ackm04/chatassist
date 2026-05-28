<?php
/**
 * GDPR / Consent mode for ChatAssist
 *
 * @package Ackm_ChatAssist
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GDPR consent - load widget only after user consent
 */
class Ackm_ChatAssist_GDPR {

    public static function init() {
        add_filter('ackm_chatassist_should_display', array(__CLASS__, 'check_consent'), 5);
        add_action('wp_footer', array(__CLASS__, 'maybe_output_consent_banner'), 5);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_consent_assets'), 20);
    }

    public static function enqueue_consent_assets() {
        if (!self::consent_required() || !ackm_chatassist_is_enabled()) {
            return;
        }
        if (isset($_COOKIE['ackm_chatassist_consent'])) {
            return;
        }
        wp_register_style('ackm-chatassist-consent', false, array(), ACKM_CHATASSIST_VERSION);
        wp_enqueue_style('ackm-chatassist-consent');
        wp_add_inline_style('ackm-chatassist-consent', self::get_consent_css());
        wp_register_script('ackm-chatassist-consent', false, array('jquery'), ACKM_CHATASSIST_VERSION, true);
        wp_enqueue_script('ackm-chatassist-consent');
        wp_add_inline_script('ackm-chatassist-consent', self::get_consent_js());
    }

    private static function get_consent_css() {
        return '.ackm-chatassist-consent-banner{position:fixed;bottom:20px;left:20px;right:20px;max-width:500px;margin:0 auto;padding:20px;background:white;border-radius:12px;box-shadow:0 4px 25px rgba(0,0,0,.2);z-index:999998;font-size:14px}.ackm-chatassist-consent-banner p{margin:0 0 15px 0}.ackm-chatassist-consent-buttons{display:flex;gap:10px}.ackm-chatassist-consent-btn{padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-weight:600}.ackm-chatassist-consent-btn.accept{background:#667eea;color:white}.ackm-chatassist-consent-btn.decline{background:#e5e7eb;color:#374151}';
    }

    private static function get_consent_js() {
        return "jQuery(document).on('click','#ackm-chatassist-consent-accept',function(){document.cookie='ackm_chatassist_consent=accepted;path=/;max-age=31536000';jQuery('#ackm-chatassist-consent-banner').fadeOut(300,function(){location.reload()})});jQuery(document).on('click','#ackm-chatassist-consent-decline',function(){document.cookie='ackm_chatassist_consent=declined;path=/;max-age=86400';jQuery('#ackm-chatassist-consent-banner').fadeOut(300)})";
    }

    public static function consent_required() {
        return get_option('ackm_chatassist_gdpr_consent', 'no') === 'yes';
    }

    public static function check_consent($should_display) {
        if (!self::consent_required()) {
            return $should_display;
        }
        if (isset($_COOKIE['ackm_chatassist_consent']) && sanitize_text_field(wp_unslash($_COOKIE['ackm_chatassist_consent'])) === 'accepted') {
            return $should_display;
        }
        return false;
    }

    public static function maybe_output_consent_banner() {
        if (!self::consent_required()) {
            return;
        }
        if (isset($_COOKIE['ackm_chatassist_consent'])) {
            return;
        }
        if (!ackm_chatassist_is_enabled()) {
            return;
        }
        $message = get_option('ackm_chatassist_gdpr_message', '');
        if (empty($message)) {
            $message = __('We use a chat widget to assist you. By clicking Accept, you consent to load the chat. You can change your mind anytime.', 'intelligizedigital-chatassist');
        }
        ?>
        <div id="ackm-chatassist-consent-banner" class="ackm-chatassist-consent-banner">
            <p><?php echo esc_html($message); ?></p>
            <div class="ackm-chatassist-consent-buttons">
                <button type="button" id="ackm-chatassist-consent-accept" class="ackm-chatassist-consent-btn accept"><?php esc_html_e('Accept', 'intelligizedigital-chatassist'); ?></button>
                <button type="button" id="ackm-chatassist-consent-decline" class="ackm-chatassist-consent-btn decline"><?php esc_html_e('Decline', 'intelligizedigital-chatassist'); ?></button>
            </div>
        </div>
        <?php
    }
}
