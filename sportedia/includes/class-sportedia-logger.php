<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Logger {
    public static function log($action, $details = '') {
        global $wpdb;
        $user_id = get_current_user_id();

        $table_name = "{$wpdb->prefix}sm_logs";
        $col_check = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'device_source'");
        if (empty($col_check)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN device_source VARCHAR(20) DEFAULT 'desktop'");
        }

        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        $is_mobile = function_exists('wp_is_mobile') ? wp_is_mobile() : preg_match('/(android|iphone|ipad|mobile)/i', $user_agent);
        $device_source = $is_mobile ? 'mobile' : 'desktop';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'details' => sanitize_textarea_field($details),
                'device_source' => $device_source,
                'created_at' => current_time('mysql')
            )
        );

        $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        if ($count > 100) {
            $limit = $count - 100;
            $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} ORDER BY id ASC LIMIT %d", $limit));
        }
    }

    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name, u.user_login FROM {$wpdb->prefix}sm_logs l LEFT JOIN {$wpdb->base_prefix}users u ON l.user_id = u.ID ORDER BY l.id DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        return $logs;
    }

    public static function get_total_logs() {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_logs");
    }
}

class SM_Logger extends Sportedia_Logger {}
