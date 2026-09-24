<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search_keyword = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

$table_members = "{$wpdb->prefix}sportedia_members";
$table_programs = "{$wpdb->prefix}sportedia_programs";
$table_subscriptions = "{$wpdb->prefix}sportedia_subscriptions";

$query = "SELECT m.*, p.program_name, s.end_date as expiration_date
          FROM {$table_members} m
          LEFT JOIN {$table_programs} p ON m.current_program_id = p.id
          LEFT JOIN {$table_subscriptions} s ON m.id = s.member_id
          WHERE m.subscription_status = 'Expired'";

if (!empty($search_keyword)) {
    $query .= $wpdb->prepare(" AND (m.full_name LIKE %s OR m.member_code LIKE %s OR m.phone LIKE %s)", '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%');
}
$query .= " GROUP BY m.id ORDER BY s.end_date DESC LIMIT 50";

$expired_members = $wpdb->get_results($query);
?>

<div class="sportedia-expired-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Expired Subscriptions</h2>
    </div>

    <!-- Search Form -->
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <input type="hidden" name="module" value="expired" />
        <input type="text" name="search" value="<?php echo esc_attr($search_keyword); ?>" placeholder="Search expired members by name or code..." class="sportedia-floating-input" style="flex: 1;" />
        <button type="submit" class="sportedia-btn sportedia-btn-secondary">Search</button>
    </form>

    <!-- Expired Members Table -->
    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Member Code</th>
                    <th style="padding: 12px 16px;">Full Name</th>
                    <th style="padding: 12px 16px;">Phone</th>
                    <th style="padding: 12px 16px;">Previous Program</th>
                    <th style="padding: 12px 16px;">Expiration Date</th>
                    <th style="padding: 12px 16px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($expired_members)): foreach ($expired_members as $m): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($m->member_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($m->full_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($m->phone ?: '--'); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($m->program_name ?: 'General Program'); ?></td>
                    <td style="padding: 12px 16px; color: #ef4444; font-weight: 600;"><?php echo esc_html($m->expiration_date ?: 'Expired'); ?></td>
                    <td style="padding: 12px 16px;">
                        <button class="sportedia-btn sportedia-btn-primary" style="height: 32px; padding: 0 12px; font-size: 12px;" onclick="alert('Renewing Member ID: <?php echo $m->id; ?>')">Renew Now</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280;">No expired subscriptions found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
