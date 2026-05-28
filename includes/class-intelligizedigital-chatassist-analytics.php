<?php
/**
 * Analytics tracking for Intelligize ChatAssist
 *
 * @package Intelligize_ChatAssist
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Intelligize ChatAssist Analytics Class
 */
class IntelligizeDigital_ChatAssist_Analytics {

    /**
     * Track widget open event
     *
     * @since 1.0.0
     */
    public static function track_open() {
        self::track_event('widget_opened');
    }

    /**
     * Track widget close event
     *
     * @since 1.0.0
     */
    public static function track_close() {
        self::track_event('widget_closed');
    }

    /**
     * Track message sent event
     *
     * @since 1.0.0
     */
    public static function track_message() {
        self::track_event('message_sent');
    }

    /**
     * Track a custom event
     *
     * @since 1.0.0
     * @param string $event_type Event type.
     */
    private static function track_event($event_type) {
        if (get_option('intelligizedigital_chatassist_analytics_enabled', 'yes') !== 'yes') {
            return;
        }

        $analytics = get_option('intelligizedigital_chatassist_analytics', array());
        
        $today = gmdate('Y-m-d');
        
        if (!isset($analytics[$today])) {
            $analytics[$today] = array(
                'widget_opened' => 0,
                'widget_closed' => 0,
                'message_sent' => 0,
                'conversions' => 0,
                'leads' => 0,
            );
        }

        if (isset($analytics[$today][$event_type])) {
            $analytics[$today][$event_type]++;
        } else {
            $analytics[$today][$event_type] = 1;
        }

        $analytics = array_slice($analytics, -90, 90, true);

        update_option('intelligizedigital_chatassist_analytics', $analytics);
    }

    /**
     * Track variant stats for A/B testing (opens, conversions per variant)
     */
    public static function track_variant_event($variant_id, $event_type) {
        if (!in_array($event_type, array('open', 'conversion'), true)) {
            return;
        }
        $stats = get_option('intelligizedigital_chatassist_ab_variant_stats', array());
        $today = gmdate('Y-m-d');
        if (!isset($stats[$today])) {
            $stats[$today] = array();
        }
        if (!isset($stats[$today][$variant_id])) {
            $stats[$today][$variant_id] = array('open' => 0, 'conversion' => 0);
        }
        $stats[$today][$variant_id][$event_type]++;
        $stats = array_slice($stats, -90, 90, true);
        update_option('intelligizedigital_chatassist_ab_variant_stats', $stats);
    }

    /**
     * Get A/B variant stats for admin
     */
    public static function get_variant_stats($days = 14) {
        $stats = get_option('intelligizedigital_chatassist_ab_variant_stats', array());
        $stats = is_array($stats) ? $stats : array();
        $recent = array_slice($stats, -$days, $days, true);
        $totals = array();
        foreach ($recent as $day_data) {
            foreach ($day_data as $vid => $vdata) {
                if (!isset($totals[$vid])) {
                    $totals[$vid] = array('open' => 0, 'conversion' => 0);
                }
                $totals[$vid]['open'] += isset($vdata['open']) ? intval($vdata['open']) : 0;
                $totals[$vid]['conversion'] += isset($vdata['conversion']) ? intval($vdata['conversion']) : 0;
            }
        }
        return $totals;
    }

    /**
     * Get analytics data
     *
     * @since 1.0.0
     * @param int $days Number of days to retrieve.
     * @return array Analytics data.
     */
    public static function get_data($days = 30) {
        $analytics = get_option('intelligizedigital_chatassist_analytics', array());
        
        if (empty($analytics)) {
            return array();
        }

        $recent = array_slice($analytics, -$days, $days, true);

        $total = array(
            'widget_opened' => 0,
            'widget_closed' => 0,
            'message_sent' => 0,
            'conversions' => 0,
            'leads' => 0,
        );

        foreach ($recent as $day_data) {
            foreach ($total as $key => $value) {
                if (isset($day_data[$key])) {
                    $total[$key] += intval($day_data[$key]);
                }
            }
        }

        return array(
            'daily' => $recent,
            'total' => $total,
            'days' => $days,
        );
    }

    /**
     * Get widget open rate
     *
     * @since 1.0.0
     * @param int $days Number of days to calculate.
     * @return float Open rate percentage.
     */
    public static function get_open_rate($days = 30) {
        $data = self::get_data($days);
        
        if (empty($data['total']['widget_opened'])) {
            return 0;
        }

        $opens = $data['total']['widget_opened'];
        $closes = $data['total']['widget_closed'];
        
        if ($opens + $closes === 0) {
            return 0;
        }

        return round(($opens / ($opens + $closes)) * 100, 2);
    }

    /**
     * Export analytics data
     *
     * @since 1.0.0
     * @param string $format Export format (csv, json).
     * @return string|array Exported data.
     */
    public static function export($format = 'json') {
        $data = self::get_data(90);

        if ($format === 'csv') {
            $csv = "Date,Widget Opens,Widget Closes,Messages Sent\n";
            
            foreach ($data['daily'] as $date => $day_data) {
                $csv .= sprintf(
                    "%s,%d,%d,%d\n",
                    $date,
                    isset($day_data['widget_opened']) ? $day_data['widget_opened'] : 0,
                    isset($day_data['widget_closed']) ? $day_data['widget_closed'] : 0,
                    isset($day_data['message_sent']) ? $day_data['message_sent'] : 0
                );
            }

            return $csv;
        }

        return $data;
    }

    /**
     * Get comparison data (this period vs previous)
     */
    public static function get_comparison($days = 7) {
        $current = self::get_data($days);
        $previous = self::get_data($days * 2);
        $prev_total = array('widget_opened' => 0, 'widget_closed' => 0, 'message_sent' => 0);
        $prev_daily = array_slice($previous['daily'], 0, $days, true);
        foreach ($prev_daily as $d) {
            foreach ($prev_total as $k => $v) {
                $prev_total[$k] += isset($d[$k]) ? intval($d[$k]) : 0;
            }
        }
        return array(
            'current' => $current['total'],
            'previous' => $prev_total,
            'change' => array(
                'widget_opened' => self::percent_change($current['total']['widget_opened'], $prev_total['widget_opened']),
                'message_sent' => self::percent_change($current['total']['message_sent'], $prev_total['message_sent']),
            ),
        );
    }

    private static function percent_change($current, $previous) {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Track conversion (purchase, signup)
     */
    public static function track_conversion($goal_id = '') {
        if (get_option('intelligizedigital_chatassist_conversion_tracking', 'no') !== 'yes') {
            return;
        }
        $analytics = get_option('intelligizedigital_chatassist_analytics', array());
        $today = gmdate('Y-m-d');
        if (!isset($analytics[$today]['conversions'])) {
            $analytics[$today]['conversions'] = 0;
        }
        $analytics[$today]['conversions']++;
        update_option('intelligizedigital_chatassist_analytics', $analytics);
    }

    /**
     * Track heatmap data (scroll depth, clicks)
     */
    public static function track_heatmap($type, $value) {
        if (get_option('intelligizedigital_chatassist_heatmap_enabled', 'no') !== 'yes') {
            return;
        }
        $heatmap = get_option('intelligizedigital_chatassist_heatmap_data', array());
        $today = gmdate('Y-m-d');
        if (!isset($heatmap[$today])) {
            $heatmap[$today] = array('scroll' => array(), 'clicks' => array());
        }
        if ($type === 'scroll') {
            $heatmap[$today]['scroll'][] = intval($value);
        }
        if ($type === 'click') {
            $heatmap[$today]['clicks'][] = $value;
        }
        $heatmap = array_slice($heatmap, -30, 30, true);
        update_option('intelligizedigital_chatassist_heatmap_data', $heatmap);
    }

    /**
     * Export as PDF (HTML print-friendly)
     */
    public static function export_pdf() {
        $data = self::get_data(30);
        ob_start();
        ?>
        <!DOCTYPE html><html><head><meta charset="utf-8"><title>ChatAssist Analytics</title>
        <style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;} th{background:#f5f5f5;}</style>
        </head><body onload="window.print()">
        <h1>Intelligize ChatAssist Analytics Report</h1>
        <p>Generated: <?php echo esc_html(gmdate('Y-m-d H:i')); ?></p>
        <h2>Summary (Last 30 Days)</h2>
        <p>Widget Opens: <?php echo esc_html($data['total']['widget_opened'] ?? 0); ?></p>
        <p>Widget Closes: <?php echo esc_html($data['total']['widget_closed'] ?? 0); ?></p>
        <p>Messages Sent: <?php echo esc_html($data['total']['message_sent'] ?? 0); ?></p>
        <h2>Daily Breakdown</h2>
        <table><tr><th>Date</th><th>Opens</th><th>Closes</th><th>Messages</th></tr>
        <?php foreach (array_reverse($data['daily'] ?? array(), true) as $date => $d) : ?>
        <tr><td><?php echo esc_html($date); ?></td><td><?php echo esc_html($d['widget_opened'] ?? 0); ?></td><td><?php echo esc_html($d['widget_closed'] ?? 0); ?></td><td><?php echo esc_html($d['message_sent'] ?? 0); ?></td></tr>
        <?php endforeach; ?>
        </table>
        </body></html>
        <?php
        return ob_get_clean();
    }
}
