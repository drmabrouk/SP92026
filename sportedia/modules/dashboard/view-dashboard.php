<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$active_tab = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : 'dashboard';
$saved_logo = get_option('sportedia_system_logo', '');
$logout_url = wp_logout_url(get_permalink());
?>

<div class="sportedia-app">
    <!-- LTR Top Bar -->
    <div class="sportedia-top-bar">
        <div class="sportedia-brand">
            <?php if (!empty($saved_logo)): ?>
                <img src="<?php echo esc_url($saved_logo); ?>" alt="Sportedia Logo" style="height: 32px; width: auto;" />
            <?php else: ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <?php endif; ?>
            <span><?php echo esc_html(get_option('sportedia_system_name', 'Sportedia')); ?></span>
        </div>

        <div class="sportedia-top-actions">
            <button type="button" class="sportedia-btn sportedia-btn-secondary" onclick="SportediaUI.openModal('modal-new-subscription')">+ New Subscription</button>
            <button type="button" class="sportedia-btn sportedia-btn-primary" onclick="SportediaUI.openModal('modal-renew-subscription')">Renew Subscription</button>

            <!-- Custom System Administrator Profile Dropdown -->
            <div class="sportedia-dropdown">
                <button type="button" class="sportedia-btn sportedia-btn-secondary" data-sportedia-toggle="dropdown">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span><?php echo esc_html($current_user->display_name ?: 'Administrator'); ?></span>
                </button>
                <div class="sportedia-dropdown-menu">
                    <div class="sportedia-dropdown-item" style="font-weight: 600; color: #111827; pointer-events: none; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                        <?php echo esc_html($current_user->user_email); ?>
                    </div>
                    <a href="#" class="sportedia-dropdown-item" onclick="SportediaUI.openModal('modal-edit-profile'); return false;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Profile
                    </a>
                    <a href="?module=settings" class="sportedia-dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        System Settings
                    </a>
                    <div class="sportedia-dropdown-divider"></div>
                    <a href="<?php echo esc_url($logout_url); ?>" class="sportedia-dropdown-item" style="color: #ef4444;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="sportedia-app-wrapper">
        <!-- Left Vertical Sidebar with Official SVG Icons and Zero Separators -->
        <aside class="sportedia-sidebar">
            <ul class="sportedia-nav-list">
                <li class="sportedia-nav-item <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                    <a href="?module=dashboard">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'attendance' ? 'active' : ''; ?>">
                    <a href="?module=attendance">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Attendance &amp; Check-In</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'members' ? 'active' : ''; ?>">
                    <a href="?module=members">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Member Management</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'expired' ? 'active' : ''; ?>">
                    <a href="?module=expired">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Expired Subscriptions</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'employees' ? 'active' : ''; ?>">
                    <a href="?module=employees">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Employee Management</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'branches' ? 'active' : ''; ?>">
                    <a href="?module=branches">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <span>Branch Management</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'programs' ? 'active' : ''; ?>">
                    <a href="?module=programs">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>Sports Programs</span>
                    </a>
                </li>
                <li class="sportedia-nav-item <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                    <a href="?module=settings">
                        <svg class="sportedia-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>System Settings</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Right Main Content Area -->
        <main class="sportedia-main-content">
            <?php
            switch ($active_tab) {
                case 'attendance':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/attendance/view-attendance.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/attendance/view-attendance.php';
                    }
                    break;
                case 'members':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/members/view-members.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/members/view-members.php';
                    }
                    break;
                case 'expired':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/expired-subscriptions/view-expired.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/expired-subscriptions/view-expired.php';
                    }
                    break;
                case 'employees':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/employees/view-employees.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/employees/view-employees.php';
                    }
                    break;
                case 'branches':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/branches/view-branches.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/branches/view-branches.php';
                    }
                    break;
                case 'programs':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/programs/view-programs.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/programs/view-programs.php';
                    }
                    break;
                case 'settings':
                    if (file_exists(SPORTEDIA_PLUGIN_DIR . 'modules/settings/view-settings.php')) {
                        include SPORTEDIA_PLUGIN_DIR . 'modules/settings/view-settings.php';
                    }
                    break;
                default:
                    include SPORTEDIA_PLUGIN_DIR . 'modules/dashboard/view-dashboard-overview.php';
                    break;
            }
            ?>
        </main>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="modal-edit-profile" class="sportedia-modal-overlay">
    <div class="sportedia-modal-card">
        <div class="sportedia-modal-header">
            <h3 class="sportedia-modal-title">Edit Profile</h3>
            <button type="button" class="sportedia-modal-close" data-sportedia-dismiss="modal">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="sportedia-form-group">
                <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" class="sportedia-floating-input" placeholder=" " required />
                <label class="sportedia-floating-label">Display Name</label>
            </div>
            <div class="sportedia-form-group">
                <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="sportedia-floating-input" placeholder=" " required />
                <label class="sportedia-floating-label">Email Address</label>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                <button type="button" class="sportedia-btn sportedia-btn-secondary" data-sportedia-dismiss="modal">Cancel</button>
                <button type="submit" class="sportedia-btn sportedia-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
