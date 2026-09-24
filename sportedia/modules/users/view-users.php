<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search_keyword = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$role_filter    = isset($_GET['role_filter']) ? sanitize_text_field($_GET['role_filter']) : '';

$table_users    = "{$wpdb->prefix}sportedia_users";
$table_branches = "{$wpdb->prefix}sportedia_branches";

$query = "SELECT u.*, b.branch_name
          FROM {$table_users} u
          LEFT JOIN {$table_branches} b ON u.branch_id = b.id
          WHERE 1=1";

if (!empty($search_keyword)) {
    $query .= $wpdb->prepare(" AND (u.full_name LIKE %s OR u.user_code LIKE %s OR u.email LIKE %s)", '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%', '%' . $wpdb->esc_like($search_keyword) . '%');
}
if (!empty($role_filter)) {
    $query .= $wpdb->prepare(" AND u.role_slug = %s", $role_filter);
}
$query .= " ORDER BY u.id DESC LIMIT 50";

$users = $wpdb->get_results($query);
?>

<div class="sportedia-users-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">User Management</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="alert('Add User Modal')">+ Add User</button>
    </div>

    <!-- User Search & Filters -->
    <form id="user-search-form" method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <input type="hidden" name="module" value="users" />
        <input type="text" name="search" value="<?php echo esc_attr($search_keyword); ?>" placeholder="Search by User ID, Name, Email..." class="sportedia-floating-input" style="flex: 2;" />
        <select name="role_filter" class="sportedia-floating-input" style="flex: 1;">
            <option value="">All Roles</option>
            <option value="sportedia_general_manager" <?php selected($role_filter, 'sportedia_general_manager'); ?>>General Manager</option>
            <option value="sportedia_admin_manager" <?php selected($role_filter, 'sportedia_admin_manager'); ?>>Administrative Manager</option>
            <option value="sportedia_sports_supervisor" <?php selected($role_filter, 'sportedia_sports_supervisor'); ?>>Sports Supervisor</option>
            <option value="sportedia_coach" <?php selected($role_filter, 'sportedia_coach'); ?>>Coach</option>
            <option value="sportedia_receptionist" <?php selected($role_filter, 'sportedia_receptionist'); ?>>Receptionist</option>
            <option value="sportedia_finance_officer" <?php selected($role_filter, 'sportedia_finance_officer'); ?>>Finance Officer</option>
        </select>
        <button type="submit" class="sportedia-btn sportedia-btn-primary">Search</button>
        <button type="button" class="sportedia-btn sportedia-btn-secondary" onclick="SportediaUI.resetFilters('user-search-form')">Reset</button>
    </form>

    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">User ID</th>
                    <th style="padding: 12px 16px;">Full Name</th>
                    <th style="padding: 12px 16px;">Email</th>
                    <th style="padding: 12px 16px;">Role</th>
                    <th style="padding: 12px 16px;">Branch</th>
                    <th style="padding: 12px 16px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($u->user_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($u->full_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($u->email); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html(str_replace('sportedia_', '', $u->role_slug)); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($u->branch_name ?: 'Main Branch'); ?></td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $u->status === 'Active' ? 'sportedia-pill-green' : 'sportedia-pill-neutral'; ?>">
                            <?php echo esc_html($u->status); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280;">No system users found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
