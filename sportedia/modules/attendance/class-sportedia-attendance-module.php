<?php
if (!defined('ABSPATH')) exit;

require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-attendance-token.php';

class Sportedia_Attendance_Module {

    public function __construct() {
        add_action('wp_ajax_sportedia_get_dynamic_token', array($this, 'ajax_get_dynamic_token'));
        add_action('wp_ajax_nopriv_sportedia_get_dynamic_token', array($this, 'ajax_get_dynamic_token'));

        add_action('wp_ajax_sportedia_process_attendance_scan', array($this, 'ajax_process_attendance_scan'));
        add_action('wp_ajax_nopriv_sportedia_process_attendance_scan', array($this, 'ajax_process_attendance_scan'));
    }

    public function ajax_get_dynamic_token() {
        $token_info = Sportedia_Attendance_Token::generate_token();
        wp_send_json_success($token_info);
    }

    public function ajax_process_attendance_scan() {
        global $wpdb;

        check_ajax_referer('sportedia_attendance_nonce', 'nonce');

        $token = isset($_POST['attendance_token']) ? sanitize_text_field($_POST['attendance_token']) : '';
        $user_id = get_current_user_id();

        // 1. Validate token
        $validation = Sportedia_Attendance_Token::validate_token($token, $user_id);
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }

        // 2. Identify Employee or Member
        $table_employees = "{$wpdb->prefix}sportedia_employees";
        $employee = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_employees} WHERE user_id = %d", $user_id));

        $employee_id = $employee ? $employee->id : null;
        $branch_id   = $employee ? $employee->branch_id : 1;
        $today       = date('Y-m-d');
        $now_time    = current_time('mysql');

        $table_attendance = "{$wpdb->prefix}sportedia_attendance";

        // 3. Check recent attendance record for today
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_attendance} WHERE employee_id = %d AND scan_date = %s ORDER BY id DESC LIMIT 1",
            $employee_id, $today
        ));

        if (!$existing) {
            // First Scan: Check-In
            $wpdb->insert($table_attendance, array(
                'employee_id'   => $employee_id,
                'branch_id'     => $branch_id,
                'scan_date'     => $today,
                'check_in_time' => $now_time,
                'status'        => 'Check-In',
                'token_used'    => $token
            ));

            wp_send_json_success(array(
                'status'  => 'Check-In',
                'message' => 'Checked In Successfully.'
            ));
        } else {
            // Second scan logic: Check if >= 2 hours since Check-In
            $check_in_ts = strtotime($existing->check_in_time);
            $current_ts  = strtotime($now_time);
            $hours_diff  = ($current_ts - $check_in_ts) / 3600;

            if ($hours_diff >= 2 && empty($existing->check_out_time)) {
                // Perform Check-Out
                $duration = round(($current_ts - $check_in_ts) / 60);

                $wpdb->update($table_attendance, array(
                    'check_out_time'   => $now_time,
                    'status'           => 'Check-Out',
                    'duration_minutes' => $duration
                ), array('id' => $existing->id));

                wp_send_json_success(array(
                    'status'  => 'Check-Out',
                    'message' => 'Check-out recorded successfully.'
                ));
            } else {
                wp_send_json_error('Attendance already recorded for this session. Repeat scans are ignored within 2 hours of Check-In.');
            }
        }
    }
}

new Sportedia_Attendance_Module();
