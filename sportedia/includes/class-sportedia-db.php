<?php

if (!defined('ABSPATH')) exit;

class Sportedia_DB {

    public static function record_player_attendance($player_id, $status, $date, $coach_id = null) {
        global $wpdb;
        $player_id = intval($player_id);

        $inserted = $wpdb->insert("{$wpdb->prefix}sm_attendance", array(
            'student_id' => $player_id,
            'status'     => sanitize_text_field($status),
            'date'       => sanitize_text_field($date),
            'teacher_id' => intval($coach_id ?: get_current_user_id())
        ));

        if ($inserted && ($status === 'present' || $status === '')) {
            $table_players = "{$wpdb->prefix}sportedia_players";
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_players'") === $table_players) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_players} SET used_sessions = used_sessions + 1, remaining_sessions = GREATEST(0, remaining_sessions - 1) WHERE id = %d",
                    $player_id
                ));
            }
        }

        return $inserted;
    }

    public static function fast_player_lookup($query_str) {
        global $wpdb;
        $clean = trim((string)$query_str);
        if ($clean === '') return null;

        $table = "{$wpdb->prefix}sportedia_players";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $table = "{$wpdb->prefix}sm_students";
        }

        $like = '%' . $wpdb->esc_like($clean) . '%';
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE student_code = %s OR barcode = %s OR national_id = %s OR guardian_phone = %s OR whatsapp_number = %s OR name LIKE %s LIMIT 1",
            $clean, $clean, $clean, $clean, $clean, $like
        );

        return $wpdb->get_row($sql);
    }

    public static function get_students($filters = array()) {
        global $wpdb;
        $table = "{$wpdb->prefix}sportedia_players";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $table = "{$wpdb->prefix}sm_students";
        }

        $query = "SELECT * FROM {$table} WHERE 1=1";

        if (!empty($filters['search'])) {
            $search_str = trim($filters['search']);
            $search_like = '%' . $wpdb->esc_like($search_str) . '%';
            $query .= $wpdb->prepare(" AND (name LIKE %s OR student_code LIKE %s OR national_id LIKE %s OR guardian_phone LIKE %s OR whatsapp_number LIKE %s)", $search_like, $search_like, $search_like, $search_like, $search_like);
        }

        if (!empty($filters['sport_type'])) {
            $query .= $wpdb->prepare(" AND sport_type = %s", $filters['sport_type']);
        }

        $query .= " ORDER BY id DESC";

        if (isset($filters['limit']) && intval($filters['limit']) > 0) {
            $limit_val = intval($filters['limit']);
            $offset_val = isset($filters['offset']) ? intval($filters['offset']) : 0;
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit_val, $offset_val);
        }

        return $wpdb->get_results($query);
    }

    public static function get_student_by_code($code) {
        return self::fast_player_lookup($code);
    }

    public static function get_student_by_id($id) {
        global $wpdb;
        $table = "{$wpdb->prefix}sportedia_players";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $table = "{$wpdb->prefix}sm_students";
        }
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($id)));
    }

    public static function get_statistics() {
        global $wpdb;
        $table_players = "{$wpdb->prefix}sportedia_players";
        $table_payments = "{$wpdb->prefix}sportedia_payments";

        $total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$table_players}") ?: 0;
        $active_players = $wpdb->get_var("SELECT COUNT(*) FROM {$table_players} WHERE status = 'Active'") ?: 0;
        $expiring_subscriptions = $wpdb->get_var("SELECT COUNT(*) FROM {$table_players} WHERE remaining_sessions <= 2 OR end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)") ?: 0;
        $monthly_revenue = $wpdb->get_var("SELECT SUM(amount) FROM {$table_payments} WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())") ?: 0;

        return array(
            'total_students'         => $total_players,
            'active_players'         => $active_players,
            'expiring_subscriptions' => $expiring_subscriptions,
            'monthly_revenue'        => $monthly_revenue
        );
    }
}

class SM_DB extends Sportedia_DB {}
