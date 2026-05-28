<?php
/**
 * Marketing features: campaigns, coupons, newsletter
 *
 * @package Intelligize_ChatAssist
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class IntelligizeDigital_ChatAssist_Marketing {

    public static function init() {
        add_filter('intelligizedigital_chatassist_chat_url', array(__CLASS__, 'append_marketing_params'), 20, 1);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_newsletter_assets'), 2);
        add_action('wp_footer', array(__CLASS__, 'maybe_output_newsletter_modal'), 2);
        add_action('wp_ajax_intelligizedigital_chatassist_newsletter_signup', array(__CLASS__, 'ajax_newsletter_signup'));
        add_action('wp_ajax_nopriv_intelligizedigital_chatassist_newsletter_signup', array(__CLASS__, 'ajax_newsletter_signup'));
    }

    public static function append_marketing_params($url) {
        if (empty($url)) {
            return $url;
        }
        $params = array();
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Campaign is a public URL parameter for tracking, not form submission
        if (isset($_GET['campaign']) && !empty($_GET['campaign'])) {
            $params['campaign'] = sanitize_text_field(wp_unslash($_GET['campaign']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $coupon = get_option('intelligizedigital_chatassist_coupon_code', '');
        if (!empty($coupon)) {
            $params['coupon'] = $coupon;
        }
        if (empty($params)) {
            return $url;
        }
        $sep = strpos($url, '?') !== false ? '&' : '?';
        return $url . $sep . http_build_query($params);
    }

    public static function is_newsletter_enabled() {
        return get_option('intelligizedigital_chatassist_newsletter_enabled', 'no') === 'yes';
    }

    public static function enqueue_newsletter_assets() {
        if (!self::is_newsletter_enabled() || !IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
            return;
        }

        $valid_timings = array( 'on_chat_open', 'after_first_message', 'on_exit_intent' );
        $timing        = get_option('intelligizedigital_chatassist_newsletter_timing', 'on_chat_open');
        if (!in_array($timing, $valid_timings, true)) {
            $timing = 'on_chat_open';
        }
        $nonce     = wp_create_nonce('intelligizedigital_chatassist_newsletter');
        $ajax_url  = admin_url('admin-ajax.php');
        $timing_js = wp_json_encode($timing);

        $js  = '(function(){';
        $js .= 'var modal=document.getElementById("intelligizedigital-chatassist-newsletter-modal");';
        $js .= 'if(!modal||localStorage.getItem("intelligizedigital_chatassist_newsletter_done"))return;';
        $js .= 'var timing=' . $timing_js . ';';
        $js .= 'var show=function(){modal.classList.add("is-visible");};';
        $js .= 'if(timing==="on_chat_open"){jQuery(document).on("intelligizedigital_chatassist_open",function(){setTimeout(show,500);});}';
        $js .= 'else if(timing==="after_first_message"){jQuery(document).on("intelligizedigital_chatassist_message_sent",function(){setTimeout(show,500);});}';
        $js .= 'else if(timing==="on_exit_intent"){var exitDone=false;jQuery(document).on("mouseout",function(e){if(exitDone)return;if(e.clientY<10&&e.relatedTarget===null){exitDone=true;setTimeout(show,300);}});}';
        $js .= 'jQuery(modal).find(".intelligizedigital-chatassist-newsletter-skip").on("click",function(){';
        $js .= 'modal.classList.remove("is-visible");localStorage.setItem("intelligizedigital_chatassist_newsletter_done","1");});';
        $js .= 'jQuery("#intelligizedigital-chatassist-newsletter-form").on("submit",function(e){e.preventDefault();';
        $js .= 'var email=jQuery(this).find("input[name=email]").val();';
        $js .= 'jQuery.post(' . wp_json_encode($ajax_url) . ',{action:"intelligizedigital_chatassist_newsletter_signup",email:email,nonce:' . wp_json_encode($nonce) . '})';
        $js .= '.done(function(){modal.classList.remove("is-visible");localStorage.setItem("intelligizedigital_chatassist_newsletter_done","1");});';
        $js .= '});})();';

        $css  = '.intelligizedigital-chatassist-newsletter-modal{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999998;display:none;align-items:center;justify-content:center;padding:20px;}';
        $css .= '.intelligizedigital-chatassist-newsletter-modal.is-visible{display:flex;}';
        $css .= '.intelligizedigital-chatassist-newsletter-inner{background:#fff;padding:24px;border-radius:12px;max-width:400px;width:100%;box-shadow:0 10px 40px rgba(0,0,0,0.2);}';
        $css .= '.intelligizedigital-chatassist-newsletter-inner h3{margin:0 0 16px 0;}';
        $css .= '.intelligizedigital-chatassist-newsletter-inner input{width:100%;padding:10px;margin-bottom:10px;border:1px solid #ddd;border-radius:6px;}';
        $css .= '.intelligizedigital-chatassist-newsletter-inner button[type=submit]{width:100%;padding:10px;background:#667eea;color:#fff;border:none;border-radius:6px;cursor:pointer;}';
        $css .= '.intelligizedigital-chatassist-newsletter-skip{display:block;margin-top:12px;background:none;border:none;color:#666;cursor:pointer;font-size:13px;}';

        wp_add_inline_style('intelligizedigital-chatassist-style', $css);

        wp_register_script('intelligizedigital-chatassist-newsletter', false, array('jquery'), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-newsletter');
        wp_add_inline_script('intelligizedigital-chatassist-newsletter', $js);
    }

    public static function maybe_output_newsletter_modal() {
        if (!self::is_newsletter_enabled() || !IntelligizeDigital_ChatAssist_Display_Rules::should_display()) {
            return;
        }
        $title = get_option('intelligizedigital_chatassist_newsletter_title', __('Subscribe to our newsletter', 'intelligizedigital-chatassist'));
        $cta   = get_option('intelligizedigital_chatassist_newsletter_cta', __('Subscribe', 'intelligizedigital-chatassist'));
        ?>
        <div id="intelligizedigital-chatassist-newsletter-modal" class="intelligizedigital-chatassist-newsletter-modal">
            <div class="intelligizedigital-chatassist-newsletter-inner">
                <h3><?php echo esc_html($title); ?></h3>
                <form id="intelligizedigital-chatassist-newsletter-form">
                    <input type="email" name="email" required placeholder="<?php esc_attr_e('Your email', 'intelligizedigital-chatassist'); ?>" />
                    <button type="submit"><?php echo esc_html($cta); ?></button>
                </form>
                <button type="button" class="intelligizedigital-chatassist-newsletter-skip"><?php esc_html_e('Skip', 'intelligizedigital-chatassist'); ?></button>
            </div>
        </div>
        <?php
    }

    public static function ajax_newsletter_signup() {
        check_ajax_referer('intelligizedigital_chatassist_newsletter', 'nonce');
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (empty($email) || !is_email($email)) {
            wp_send_json_error();
        }
        do_action('intelligizedigital_chatassist_newsletter_signup', $email);
        $webhook = get_option('intelligizedigital_chatassist_newsletter_webhook', '');
        if (!empty($webhook)) {
            wp_remote_post($webhook, array(
                'body'    => array('email' => $email, 'source' => 'intelligizedigital_chatassist'),
                'timeout' => 10,
            ));
        }
        wp_send_json_success();
    }
}
