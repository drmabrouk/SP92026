<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// CSV/Excel Export Handler
if (isset($_GET['action']) && $_GET['action'] === 'export_finance_csv') {
    if (!current_user_can('sportedia_export_finance') && !current_user_can('administrator')) {
        wp_die('Unauthorized');
    }

    $table_invoices = "{$wpdb->prefix}sportedia_invoices";
    $table_members  = "{$wpdb->prefix}sportedia_members";

    $rows = $wpdb->get_results("
        SELECT i.invoice_no, m.member_code, m.full_name, i.amount, i.payment_type, i.payment_status, i.invoice_date
        FROM {$table_invoices} i
        LEFT JOIN {$table_members} m ON i.member_id = m.id
        ORDER BY i.id DESC
    ", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sportedia_finance_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Invoice Number', 'Member Code', 'Member Name', 'Amount', 'Payment Type', 'Status', 'Date'));

    if (!empty($rows)) {
        foreach ($rows as $r) {
            fputcsv($output, $r);
        }
    }
    fclose($output);
    exit;
}

$search_keyword = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

$table_invoices = "{$wpdb->prefix}sportedia_invoices";
$table_members  = "{$wpdb->prefix}sportedia_members";

$total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM {$table_invoices} WHERE payment_status = 'Paid'");
$monthly_revenue = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$table_invoices} WHERE payment_status = 'Paid' AND MONTH(invoice_date) = %d", date('m')));
$transaction_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_invoices}");

$query = "SELECT i.*, m.full_name, m.member_code
          FROM {$table_invoices} i
          LEFT JOIN {$table_members} m ON i.member_id = m.id
          WHERE 1=1";

if (!empty($search_keyword)) {
    $query .= $wpdb->prepare(" AND (i.invoice_no LIKE %s OR m.full_name LIKE %s OR m.member_code LIKE %s)", '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%');
}
$query .= " ORDER BY i.id DESC LIMIT 50";

$invoices = $wpdb->get_results($query);
?>

<div class="sportedia-finance-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Finance &amp; Revenue Overview</h2>
        <a href="?module=finance&action=export_finance_csv" class="sportedia-btn sportedia-btn-primary">Export Excel / CSV</a>
    </div>

    <!-- Summary Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 12px; color: #6b7280; font-weight: 600;">TOTAL REVENUE</div>
            <div style="font-size: 24px; font-weight: 700; color: #10b981; margin-top: 4px;">$<?php echo esc_html(number_format($total_revenue ?: 0, 2)); ?></div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 12px; color: #6b7280; font-weight: 600;">THIS MONTH REVENUE</div>
            <div style="font-size: 24px; font-weight: 700; color: #111827; margin-top: 4px;">$<?php echo esc_html(number_format($monthly_revenue ?: 0, 2)); ?></div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="font-size: 12px; color: #6b7280; font-weight: 600;">TOTAL TRANSACTIONS</div>
            <div style="font-size: 24px; font-weight: 700; color: #111827; margin-top: 4px;"><?php echo esc_html($transaction_count ?: 0); ?></div>
        </div>
    </div>

    <!-- Finance Search -->
    <form id="finance-search-form" method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <input type="hidden" name="module" value="finance" />
        <input type="text" name="search" value="<?php echo esc_attr($search_keyword); ?>" placeholder="Search Invoice #, Member Code, Name..." class="sportedia-floating-input" style="flex: 1;" />
        <button type="submit" class="sportedia-btn sportedia-btn-primary">Search</button>
        <button type="button" class="sportedia-btn sportedia-btn-secondary" onclick="SportediaUI.resetFilters('finance-search-form')">Reset</button>
    </form>

    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Invoice #</th>
                    <th style="padding: 12px 16px;">Member Code</th>
                    <th style="padding: 12px 16px;">Member Name</th>
                    <th style="padding: 12px 16px;">Amount</th>
                    <th style="padding: 12px 16px;">Payment Type</th>
                    <th style="padding: 12px 16px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): foreach ($invoices as $inv): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($inv->invoice_no); ?></td>
                    <td style="padding: 12px 16px; font-family: monospace; color: #4b5563;"><?php echo esc_html($inv->member_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($inv->full_name); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #10b981;">$<?php echo esc_html(number_format($inv->amount, 2)); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($inv->payment_type); ?></td>
                    <td style="padding: 12px 16px; color: #6b7280;"><?php echo esc_html($inv->invoice_date); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280;">No financial records found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
