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
        $msg = rawurlencode("Welcomeً $player_name، تم Issue الفاتورة رقم $invoice_no بقيمة $amount AED في منصة Sportedia. شكراً لثقتكم بنا!");
        return "https://wa.me/$clean_phone?text=$msg";
    }

    public static function get_renewal_reminder_link($phone, $player_name, $remaining_sessions, $end_date) {
        $clean_phone = self::format_phone_for_whatsapp($phone);
        $msg = rawurlencode("تذكير من منصة Sportedia: عزيزي المشترك/الNoعب $player_name، المتبقي من حصصك الرياضية هو $remaining_sessions حصة وEnd Date $end_date. يرجى Renew Subscription للضمان اNoستمرارية.");
        return "https://wa.me/$clean_phone?text=$msg";
    }

    public static function get_sessions_info_link($phone, $player_name, $sport_type, $remaining_sessions) {
        $clean_phone = self::format_phone_for_whatsapp($phone);
        $msg = rawurlencode("تفاصيل رياضة $sport_type لNoعب $player_name: Remaining Sessions المتاحة هي $remaining_sessions حصة في أكاديمية Sportedia.");
        return "https://wa.me/$clean_phone?text=$msg";
    }
}
