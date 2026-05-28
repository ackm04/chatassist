<?php
/**
 * Direct Chat Integrations - Intercom, Crisp, Drift, Tawk.to, LiveChat
 *
 * @package Intelligize_ChatAssist
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Native integrations for popular chat services
 */
class IntelligizeDigital_ChatAssist_Integrations {

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue_integration'));
        add_filter('intelligizedigital_chatassist_should_display', array(__CLASS__, 'maybe_hide_widget'), 1);
    }

    public static function get_active_integration() {
        return get_option('intelligizedigital_chatassist_native_integration', '');
    }

    public static function maybe_hide_widget($show) {
        $integration = self::get_active_integration();
        if (empty($integration)) {
            return $show;
        }
        if (get_option('intelligizedigital_chatassist_integration_replace_widget', 'yes') === 'yes') {
            return false;
        }
        return $show;
    }

    public static function maybe_enqueue_integration() {
        $integration = self::get_active_integration();
        if (empty($integration)) {
            return;
        }
        $method = 'enqueue_' . $integration;
        if (method_exists(__CLASS__, $method)) {
            self::$method();
        }
    }

    private static function enqueue_intercom() {
        $app_id = get_option('intelligizedigital_chatassist_intercom_app_id', '');
        if (empty($app_id)) {
            return;
        }
        $app_id = esc_js($app_id);
        $js  = '(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic("reattach_activator");ic("update",w.intercomSettings);}';
        $js .= 'else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;';
        $js .= 'var l=function(){var s=d.createElement("script");s.type="text/javascript";s.async=true;';
        $js .= 's.src="https://widget.intercom.io/widget/' . $app_id . '";';
        $js .= 'var x=d.getElementsByTagName("script")[0];x.parentNode.insertBefore(s,x);};';
        $js .= 'if(document.readyState==="complete"){l();}else if(w.attachEvent){w.attachEvent("onload",l);}';
        $js .= 'else{w.addEventListener("load",l,false);}}})();';
        wp_register_script('intelligizedigital-chatassist-intercom', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-intercom');
        wp_add_inline_script('intelligizedigital-chatassist-intercom', $js);
    }

    private static function enqueue_crisp() {
        $site_id = get_option('intelligizedigital_chatassist_crisp_site_id', '');
        if (empty($site_id)) {
            return;
        }
        $site_id = esc_js($site_id);
        $js  = 'window.$crisp=[];window.CRISP_WEBSITE_ID="' . $site_id . '";';
        $js .= '(function(){var d=document,s=d.createElement("script");';
        $js .= 's.src="https://client.crisp.chat/l.js";s.async=1;';
        $js .= 'd.getElementsByTagName("head")[0].appendChild(s);})();';
        wp_register_script('intelligizedigital-chatassist-crisp', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-crisp');
        wp_add_inline_script('intelligizedigital-chatassist-crisp', $js);
    }

    private static function enqueue_drift() {
        $id = get_option('intelligizedigital_chatassist_drift_id', '');
        if (empty($id)) {
            return;
        }
        $id = esc_js($id);
        $js  = '!function(){var t=window.driftt=window.drift=window.driftt||[];';
        $js .= 'if(!t.init){if(t.invoked)return void(window.console&&console.error&&console.error("Drift snippet included twice."));';
        $js .= 't.invoked=!0,t.methods=["identify","config","track","reset","debug","show","ping","page","hide","off","on"],';
        $js .= 't.factory=function(e){return function(){var n=Array.prototype.slice.call(arguments);n.unshift(e),t.push(n),t};},';
        $js .= 't.methods.forEach(function(e){t[e]=t.factory(e)}),t.load=function(t){var e=document.createElement("script");';
        $js .= 'e.type="text/javascript",e.async=!0,e.src="https://js.driftt.com/include/"+t+"/"+t+".js";';
        $js .= 'var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(e,n)}};';
        $js .= 'drift.SNIPPET_VERSION="0.3.1",drift.load("' . $id . '");}();';
        wp_register_script('intelligizedigital-chatassist-drift', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-drift');
        wp_add_inline_script('intelligizedigital-chatassist-drift', $js);
    }

    private static function enqueue_tawk() {
        $id  = get_option('intelligizedigital_chatassist_tawk_id', '');
        $key = get_option('intelligizedigital_chatassist_tawk_key', '');
        if (empty($id) || empty($key)) {
            return;
        }
        $id  = esc_js($id);
        $key = esc_js($key);
        $js  = 'var Tawk_API=Tawk_API||{},Tawk_LoadStart=new Date();';
        $js .= '(function(){var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];';
        $js .= 's1.async=true;s1.src="https://embed.tawk.to/' . $id . '/' . $key . '";';
        $js .= 's1.charset="UTF-8";s1.setAttribute("crossorigin","*");s0.parentNode.insertBefore(s1,s0);})();';
        wp_register_script('intelligizedigital-chatassist-tawk', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-tawk');
        wp_add_inline_script('intelligizedigital-chatassist-tawk', $js);
    }

    private static function enqueue_livechat() {
        $license = get_option('intelligizedigital_chatassist_livechat_license', '');
        if (empty($license)) {
            return;
        }
        $license = absint($license);
        $js  = 'window.__lc=window.__lc||{};window.__lc.license=' . $license . ';';
        $js .= '(function(n,t,c){function i(n){return e._h?e._h.apply(null,n):e._q.push(n)}';
        $js .= 'var e={_q:[],_h:null,_v:"2.0",on:function(){i(["on",c.call(arguments)])},';
        $js .= 'once:function(){i(["once",c.call(arguments)])},off:function(){i(["off",c.call(arguments)])},';
        $js .= 'get:function(){if(!e._h)throw new Error("[LiveChat Widget] You can\'t use getters before load.");';
        $js .= 'return i(["get",c.call(arguments)])},call:function(){i(["call",c.call(arguments)])},';
        $js .= 'init:function(){var n=t.createElement("script");n.async=!0,n.type="text/javascript",';
        $js .= 'n.src="https://cdn.livechatinc.com/tracking.js",t.head.appendChild(n)}};';
        $js .= '!n.__lc.asyncInit&&e.init(),n.LiveChatWidget=n.LiveChatWidget||e}(window,document,["__lc"]));';
        wp_register_script('intelligizedigital-chatassist-livechat', false, array(), INTELLIGIZEDIGITAL_CHATASSIST_VERSION, true);
        wp_enqueue_script('intelligizedigital-chatassist-livechat');
        wp_add_inline_script('intelligizedigital-chatassist-livechat', $js);
    }

    public static function get_integration_options() {
        return array(
            '' => __('None (use custom URL)', 'intelligizedigital-chatassist'),
            'intercom' => 'Intercom',
            'crisp' => 'Crisp',
            'drift' => 'Drift',
            'tawk' => 'Tawk.to',
            'livechat' => 'LiveChat',
        );
    }
}
