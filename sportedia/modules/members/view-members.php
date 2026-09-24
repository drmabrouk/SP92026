<?php
if (!defined('ABSPATH')) exit;

require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-action-card.php';

global $wpdb;

$search_keyword = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter  = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$branch_filter  = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$table_members  = "{$wpdb->prefix}sportedia_members";
$table_programs = "{$wpdb->prefix}sportedia_programs";
$table_branches = "{$wpdb->prefix}sportedia_branches";

$branches = $wpdb->get_results("SELECT * FROM {$table_branches} ORDER BY branch_name ASC");

$query = "SELECT m.*, p.program_name, b.branch_name
          FROM {$table_members} m
          LEFT JOIN {$table_programs} p ON m.current_program_id = p.id
          LEFT JOIN {$table_branches} b ON m.branch_id = b.id
          WHERE 1=1";

if (!empty($search_keyword)) {
    $query .= $wpdb->prepare(" AND (m.full_name LIKE %s OR m.member_code LIKE %s OR m.phone LIKE %s)", '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%');
}
if (!empty($status_filter)) {
    $query .= $wpdb->prepare(" AND m.subscription_status = %s", $status_filter);
}
if ($branch_filter > 0) {
    $query .= $wpdb->prepare(" AND m.branch_id = %d", $branch_filter);
}

$query .= " ORDER BY m.id DESC LIMIT 50";
$members = $wpdb->get_results($query);
?>

<div class="sportedia-members-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Member Management</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="SportediaUI.openModal('modal-add-member')">+ Add Member</button>
    </div>

    <!-- Reusable Action Cards -->
    <div class="sportedia-action-grid">
        <?php
        Sportedia_Action_Card::render(array(
            'title' => 'Add Member',
            'description' => 'Register a new sports member in the system',
            'icon' => 'user-plus',
            'action' => "SportediaUI.openModal('modal-add-member')",
            'btn_text' => 'Register'
        ));
        Sportedia_Action_Card::render(array(
            'title' => 'View Members',
            'description' => 'Browse all active registered sports members',
            'icon' => 'users',
            'action' => "document.getElementById('member-search-form').scrollIntoView()",
            'btn_text' => 'View List'
        ));
        ?>
    </div>

    <!-- Real Database Search & Filter Controls -->
    <form id="member-search-form" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <input type="hidden" name="module" value="members" />
        <input type="text" name="search" value="<?php echo esc_attr($search_keyword); ?>" placeholder="Search name, code, phone..." class="sportedia-floating-input" style="flex: 2; min-width: 200px;" />
        <select name="status_filter" class="sportedia-floating-input" style="flex: 1; min-width: 140px;">
            <option value="">All Statuses</option>
            <option value="Active" <?php selected($status_filter, 'Active'); ?>>Active</option>
            <option value="Expired" <?php selected($status_filter, 'Expired'); ?>>Expired</option>
            <option value="Frozen" <?php selected($status_filter, 'Frozen'); ?>>Frozen</option>
        </select>
        <select name="branch_filter" class="sportedia-floating-input" style="flex: 1; min-width: 140px;">
            <option value="0">All Branches</option>
            <?php foreach ($branches as $br): ?>
                <option value="<?php echo $br->id; ?>" <?php selected($branch_filter, $br->id); ?>><?php echo esc_html($br->branch_name); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="sportedia-btn sportedia-btn-primary">Search</button>
        <button type="button" class="sportedia-btn sportedia-btn-secondary" onclick="SportediaUI.resetFilters('member-search-form')">Reset</button>
    </form>

    <!-- Members Table -->
    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Member Code</th>
                    <th style="padding: 12px 16px;">Full Name</th>
                    <th style="padding: 12px 16px;">Branch</th>
                    <th style="padding: 12px 16px;">Program</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px;">Joined Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($members)): foreach ($members as $m): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($m->member_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($m->full_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($m->branch_name ?: 'Main Branch'); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($m->program_name ?: 'General'); ?></td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $m->subscription_status === 'Active' ? 'sportedia-pill-green' : ($m->subscription_status === 'Expired' ? 'sportedia-pill-red' : 'sportedia-pill-neutral'); ?>">
                            <?php echo esc_html($m->subscription_status); ?>
                        </span>
                    </td>
                    <td style="padding: 12px 16px; color: #6b7280;"><?php echo esc_html($m->joined_date ?: '--'); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280;">No members found matching your search.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
