<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Attendance_Token {

    /**
     * Get or create server secret for attendance HMAC generation.
     */
    private static function get_server_secret() {
        $secret = get_option('sportedia_attendance_secret');
        if (empty($secret)) {
            $secret = wp_generate_password(64, true, true);
            update_option('sportedia_attendance_secret', $secret);
        }
        return $secret;
    }

    /**
     * Generate dynamic attendance token valid for the current 5-second window.
     * Timestamp format: YYYYMMDDHHMMSS rounded down to the nearest 5 seconds.
     *
     * @param int|null $time Unix timestamp (defaults to current time)
     * @return array Contains 'token', 'timestamp', and 'expires_in'
     */
    public static function generate_token($time = null) {
        if ($time === null) {
            $time = time();
        }

        // Round down to 5-second interval
        $window = 5;
        $rounded_time = floor($time / $window) * $window;
        $timestamp_str = date('YmdHis', $rounded_time);

        $secret = self::get_server_secret();
        $signature = hash_hmac('sha256', "sportedia_attendance_{$timestamp_str}", $secret);

        $token = "{$timestamp_str}.{$signature}";
        $expires_in = $window - ($time % $window);

        return array(
            'token'         => $token,
            'timestamp_str' => $timestamp_str,
            'expires_in'    => $expires_in,
            'rounded_time'  => $rounded_time
        );
    }

    /**
     * Validate an incoming attendance token server-side.
     *
     * @param string $token Incoming token string
     * @param int|null $current_employee_id Optional employee ID
     * @return array ['valid' => bool, 'message' => string, 'timestamp_str' => string]
     */
    public static function validate_token($token, $current_employee_id = null) {
        global $wpdb;

        if (empty($token) || strpos($token, '.') === false) {
            return array('valid' => false, 'message' => 'Invalid token structure.');
        }

        list($timestamp_str, $signature) = explode('.', $token, 2);

        if (strlen($timestamp_str) !== 14 || !ctype_digit($timestamp_str)) {
            return array('valid' => false, 'message' => 'Invalid token timestamp.');
        }

        // Parse timestamp
        $year   = (int) substr($timestamp_str, 0, 4);
        $month  = (int) substr($timestamp_str, 4, 2);
        $day    = (int) substr($timestamp_str, 6, 2);
        $hour   = (int) substr($timestamp_str, 8, 2);
        $minute = (int) substr($timestamp_str, 10, 2);
        $second = (int) substr($timestamp_str, 12, 2);

        $token_time = mktime($hour, $minute, $second, $month, $day, $year);
        $now = time();

        // 5-second lifetime check (with 1-second drift allowance)
        if (($now - $token_time) > 6 || ($token_time - $now) > 2) {
            return array('valid' => false, 'message' => 'Attendance code has expired. Please scan the current active code.');
        }

        // HMAC verification
        $secret = self::get_server_secret();
        $expected_signature = hash_hmac('sha256', "sportedia_attendance_{$timestamp_str}", $secret);

        if (!hash_equals($expected_signature, $signature)) {
            return array('valid' => false, 'message' => 'Invalid attendance security token signature.');
        }

        // Replay Protection: Check if token has already been used in sportedia_attendance_tokens
        $table_tokens = "{$wpdb->prefix}sportedia_attendance_tokens";
        $token_hash = md5($token);

        $used = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_tokens} WHERE token_hash = %s AND used_status = 1", $token_hash));
        if ($used) {
            return array('valid' => false, 'message' => 'This attendance code has already been processed.');
        }

        // Record token usage
        $wpdb->insert($table_tokens, array(
            'token_hash'           => $token_hash,
            'timestamp_str'        => $timestamp_str,
            'expires_at'           => $token_time + 5,
            'used_status'          => 1,
            'used_by_employee_id'  => $current_employee_id
        ));

        return array(
            'valid'         => true,
            'message'       => 'Token validated successfully.',
            'timestamp_str' => $timestamp_str
        );
    }
}
