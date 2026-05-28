<?php
/**
 * Goal tracking for ChatAssist
 *
 * @package Ackm_ChatAssist
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ackm_ChatAssist_Goals {

    public static function init() {
        add_action('wp_ajax_ackm_chatassist_track_goal', array(__CLASS__, 'ajax_track_goal'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_goal_script'), 99);
    }

    public static function get_goals() {
        $goals = get_option('ackm_chatassist_goals', array());
        return is_array($goals) ? $goals : array();
    }

    public static function get_goal_types() {
        return array(
            'widget_opened' => __('Widget Opens', 'intelligizedigital-chatassist'),
            'message_sent' => __('Messages Sent', 'intelligizedigital-chatassist'),
            'lead_captured' => __('Leads Captured', 'intelligizedigital-chatassist'),
            'conversion' => __('Conversions', 'intelligizedigital-chatassist'),
            'custom' => __('Custom (via JS)', 'intelligizedigital-chatassist'),
        );
    }

    /**
     * Get current progress for a goal (from analytics or custom tracking)
     */
    public static function get_progress($goal, $days = 30) {
        $target = isset($goal['target']) ? max(1, intval($goal['target'])) : 100;
        $type = isset($goal['type']) ? $goal['type'] : 'widget_opened';
        $start = isset($goal['start_date']) ? $goal['start_date'] : gmdate('Y-m-d', strtotime("-{$days} days"));

        if ($type === 'custom') {
            $custom = get_option('ackm_chatassist_goal_custom', array());
            $goal_id = isset($goal['id']) ? $goal['id'] : '';
            $current = isset($custom[$goal_id]) ? intval($custom[$goal_id]) : 0;
        } else {
            $data = Ackm_ChatAssist_Analytics::get_data($days);
            $current = isset($data['total'][$type]) ? intval($data['total'][$type]) : 0;
        }

        $pct = $target > 0 ? min(100, round(($current / $target) * 100, 1)) : 0;
        return array(
            'current' => $current,
            'target' => $target,
            'percent' => $pct,
            'complete' => $current >= $target,
        );
    }

    public static function track_custom_goal($goal_id) {
        $custom = get_option('ackm_chatassist_goal_custom', array());
        if (!isset($custom[$goal_id])) {
            $custom[$goal_id] = 0;
        }
        $custom[$goal_id]++;
        update_option('ackm_chatassist_goal_custom', $custom);
    }

    public static function ajax_track_goal() {
        check_ajax_referer('ackm_chatassist_goal', 'nonce');
        $goal_id = isset($_POST['goal_id']) ? sanitize_text_field(wp_unslash($_POST['goal_id'])) : '';
        if (empty($goal_id)) {
            wp_send_json_error();
        }
        self::track_custom_goal($goal_id);
        wp_send_json_success();
    }

    public static function enqueue_goal_script() {
        if (!Ackm_ChatAssist_Display_Rules::should_display()) {
            return;
        }
        $goals        = self::get_goals();
        $custom_goals = array();
        foreach ($goals as $g) {
            if (isset($g['type']) && $g['type'] === 'custom' && !empty($g['id'])) {
                $custom_goals[] = $g['id'];
            }
        }
        if (empty($custom_goals)) {
            return;
        }
        $nonce = wp_create_nonce('ackm_chatassist_goal');
        $js    = '(function(){var goals=' . wp_json_encode($custom_goals) . ';';
        $js   .= 'var nonce=' . wp_json_encode($nonce) . ';';
        $js   .= 'jQuery(document).on("ackm_chatassist_goal",function(e,data){';
        $js   .= 'var id=data&&data.goal_id?data.goal_id:"";';
        $js   .= 'if(id&&goals.indexOf(id)!==-1){';
        $js   .= 'jQuery.post(' . wp_json_encode(admin_url('admin-ajax.php')) . ',';
        $js   .= '{action:"ackm_chatassist_track_goal",goal_id:id,nonce:nonce});}';
        $js   .= '});})();';

        wp_register_script('ackm-chatassist-goals', false, array('jquery'), ACKM_CHATASSIST_VERSION, true);
        wp_enqueue_script('ackm-chatassist-goals');
        wp_add_inline_script('ackm-chatassist-goals', $js);
    }
}
