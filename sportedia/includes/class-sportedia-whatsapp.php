<?php
if (!defined('ABSPATH')) exit;

class Sportedia_WhatsApp {

    public static function format_phone_for_whatsapp($phone) {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (substr($clean, 0, 1) === '0') {
            $clean = '971' . substr($clean, 1);
        }
        return $clean;
    }

    public static function get_invoice_link($phone, $player_name, $invoice_no, $amount) {
        $clean_phone = self::format_phone_for_whatsapp($phone);
        $msg = rawurlencode("Welcome $player_name  Issue   $invoice_no  $amount AED   Sportedia.   !");
        return "https://wa.me/$clean_phone?text=$msg";
    }

    public static function get_renewal_reminder_link($phone, $player_name, $remaining_sessions, $end_date) {
        $clean_phone = self::format_phone_for_whatsapp($phone);
        $msg = rawurlencode("   Sportedia:  /No $player_name      $remaining_sessions  End Date $end_date.  Renew Subscription  No.");
        return "https://wa.me/$clean_phone?text=$msg";
    }

    public static function get_sessions_info_link($phone, $player_name, $sport_type, $remaining_sessions) {
        $clean_phone = self::format_phone_for_whatsapp($phone);
        $msg = rawurlencode("  $sport_type No $player_name: Remaining Sessions   $remaining_sessions    Sportedia.");
        return "https://wa.me/$clean_phone?text=$msg";
    }
}
