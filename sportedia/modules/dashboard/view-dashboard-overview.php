<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$table_members = "{$wpdb->prefix}sportedia_members";
$table_employees = "{$wpdb->prefix}sportedia_employees";
$table_branches = "{$wpdb->prefix}sportedia_branches";
$table_attendance = "{$wpdb->prefix}sportedia_attendance";

$total_members = $wpdb->get_var("SELECT COUNT(*) FROM {$table_members}");
$active_members = $wpdb->get_var("SELECT COUNT(*) FROM {$table_members} WHERE subscription_status = 'Active'");
$expired_members = $wpdb->get_var("SELECT COUNT(*) FROM {$table_members} WHERE subscription_status = 'Expired'");
$total_employees = $wpdb->get_var("SELECT COUNT(*) FROM {$table_employees}");
$total_branches = $wpdb->get_var("SELECT COUNT(*) FROM {$table_branches}");
$today_attendance = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_attendance} WHERE scan_date = %s", date('Y-m-d')));
?>

<div class="sportedia-dashboard-overview">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 13px; color: #6b7280; font-weight: 600;">TOTAL MEMBERS</div>
            <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 6px;"><?php echo esc_html($total_members ?: 0); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 13px; color: #6b7280; font-weight: 600;">ACTIVE SUBSCRIPTIONS</div>
            <div style="font-size: 28px; font-weight: 700; color: #10b981; margin-top: 6px;"><?php echo esc_html($active_members ?: 0); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 13px; color: #6b7280; font-weight: 600;">EXPIRED SUBSCRIPTIONS</div>
            <div style="font-size: 28px; font-weight: 700; color: #ef4444; margin-top: 6px;"><?php echo esc_html($expired_members ?: 0); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 13px; color: #6b7280; font-weight: 600;">TOTAL EMPLOYEES</div>
            <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 6px;"><?php echo esc_html($total_employees ?: 0); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 13px; color: #6b7280; font-weight: 600;">ACTIVE BRANCHES</div>
            <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 6px;"><?php echo esc_html($total_branches ?: 1); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 13px; color: #6b7280; font-weight: 600;">TODAY'S ATTENDANCE</div>
            <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 6px;"><?php echo esc_html($today_attendance ?: 0); ?></div>
        </div>
    </div>
</div>
