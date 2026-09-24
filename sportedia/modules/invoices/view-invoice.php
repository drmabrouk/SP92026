<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;

$table_invoices = "{$wpdb->prefix}sportedia_invoices";
$table_members  = "{$wpdb->prefix}sportedia_members";

$invoice = $wpdb->get_row($wpdb->prepare("
    SELECT i.*, m.full_name, m.member_code, m.phone
    FROM {$table_invoices} i
    LEFT JOIN {$table_members} m ON i.member_id = m.id
    WHERE i.id = %d
", $invoice_id));

if (!$invoice) {
    echo '<div style="padding:20px; color:#ef4444;">Invoice not found.</div>';
    return;
}
?>

<div class="sportedia-printable-invoice" style="max-width: 600px; margin: 20px auto; padding: 32px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0; font-size: 20px;">Sportedia</h2>
            <div style="font-size: 12px; color: #6b7280;">Official Subscription Invoice</div>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: 700;">INVOICE #<?php echo esc_html($invoice->invoice_no); ?></div>
            <div style="font-size: 12px; color: #6b7280;"><?php echo esc_html($invoice->invoice_date); ?></div>
        </div>
    </div>

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; color: #6b7280; text-transform: uppercase;">Billed To:</div>
        <div style="font-weight: 600; font-size: 16px; color: #111827;"><?php echo esc_html($invoice->full_name); ?></div>
        <div style="font-size: 13px; color: #4b5563;">Member Code: <?php echo esc_html($invoice->member_code); ?></div>
        <div style="font-size: 13px; color: #4b5563;">Phone: <?php echo esc_html($invoice->phone ?: '--'); ?></div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
        <thead>
            <tr style="border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 12px; color: #6b7280;">
                <th style="padding: 8px 0;">Description</th>
                <th style="padding: 8px 0; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #f3f4f6;">
                <td style="padding: 12px 0;">Subscription Activation / Renewal</td>
                <td style="padding: 12px 0; text-align: right; font-weight: 600;">$<?php echo esc_html(number_format($invoice->amount, 2)); ?></td>
            </tr>
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #111827; padding-top: 16px;">
        <span style="font-weight: 700; font-size: 16px;">Total Paid:</span>
        <span style="font-weight: 700; font-size: 18px;">$<?php echo esc_html(number_format($invoice->amount, 2)); ?></span>
    </div>

    <div style="margin-top: 32px; text-align: center;">
        <button class="sportedia-btn sportedia-btn-primary" onclick="window.print();">Print Invoice</button>
    </div>
</div>
