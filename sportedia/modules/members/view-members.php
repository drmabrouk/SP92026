<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search_keyword = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$table_members = "{$wpdb->prefix}sportedia_members";
$table_programs = "{$wpdb->prefix}sportedia_programs";

$query = "SELECT m.*, p.program_name FROM {$table_members} m LEFT JOIN {$table_programs} p ON m.current_program_id = p.id WHERE 1=1";
if (!empty($search_keyword)) {
    $query .= $wpdb->prepare(" AND (m.full_name LIKE %s OR m.member_code LIKE %s OR m.phone LIKE %s)", '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%');
}
if (!empty($status_filter)) {
    $query .= $wpdb->prepare(" AND m.subscription_status = %s", $status_filter);
}
$query .= " ORDER BY m.id DESC LIMIT 50";

$members = $wpdb->get_results($query);
?>

<div class="sportedia-members-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Member Management</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="alert('Add Member Form')">+ Add Member</button>
    </div>

    <!-- Search & Filter Controls -->
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <input type="hidden" name="module" value="members" />
        <input type="text" name="search" value="<?php echo esc_attr($search_keyword); ?>" placeholder="Search by name, code, or phone..." class="sportedia-floating-input" style="flex: 2;" />
        <select name="status_filter" class="sportedia-floating-input" style="flex: 1;">
            <option value="">All Statuses</option>
            <option value="Active" <?php selected($status_filter, 'Active'); ?>>Active</option>
            <option value="Expired" <?php selected($status_filter, 'Expired'); ?>>Expired</option>
            <option value="Frozen" <?php selected($status_filter, 'Frozen'); ?>>Frozen</option>
        </select>
        <button type="submit" class="sportedia-btn sportedia-btn-secondary">Filter</button>
    </form>

    <!-- Members Table -->
    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Member Code</th>
                    <th style="padding: 12px 16px;">Full Name</th>
                    <th style="padding: 12px 16px;">Phone</th>
                    <th style="padding: 12px 16px;">Program</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px;">Joined Date</th>
                    <th style="padding: 12px 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($members)): foreach ($members as $m): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($m->member_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($m->full_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($m->phone ?: '--'); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($m->program_name ?: 'General'); ?></td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $m->subscription_status === 'Active' ? 'sportedia-pill-green' : ($m->subscription_status === 'Expired' ? 'sportedia-pill-red' : 'sportedia-pill-neutral'); ?>">
                            <?php echo esc_html($m->subscription_status); ?>
                        </span>
                    </td>
                    <td style="padding: 12px 16px; color: #6b7280;"><?php echo esc_html($m->joined_date ?: '--'); ?></td>
                    <td style="padding: 12px 16px;">
                        <button class="sportedia-btn sportedia-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" onclick="alert('Viewing Member ID: <?php echo $m->id; ?>')">View</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" style="padding: 24px; text-align: center; color: #6b7280;">No members found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
