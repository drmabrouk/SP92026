<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$active_tab = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : 'dashboard';
?>

<!-- Sportedia Application Shell -->
<div class="sportedia-top-bar">
    <div class="sportedia-brand">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        <span>Sportedia</span>
    </div>
    <div class="sportedia-top-actions">
        <button type="button" class="sportedia-btn sportedia-btn-secondary" onclick="SportediaApp.openModal('new-subscription')">+ New Subscription</button>
        <button type="button" class="sportedia-btn sportedia-btn-primary" onclick="SportediaApp.openModal('renew-subscription')">Renew Subscription</button>
        <span class="sportedia-pill sportedia-pill-neutral"><?php echo esc_html($current_user->display_name ?: 'Admin'); ?></span>
    </div>
</div>

<div class="sportedia-app-wrapper">
    <!-- Left Vertical Sidebar -->
    <aside class="sportedia-sidebar">
        <ul class="sportedia-nav-list">
            <li class="sportedia-nav-item <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <a href="?module=dashboard">Dashboard</a>
            </li>
            <li class="sportedia-nav-item <?php echo $active_tab === 'members' ? 'active' : ''; ?>">
                <a href="?module=members">Member Management</a>
            </li>
            <li class="sportedia-nav-item <?php echo $active_tab === 'expired' ? 'active' : ''; ?>">
                <a href="?module=expired">Expired Subscriptions</a>
            </li>
            <li class="sportedia-nav-item <?php echo $active_tab === 'employees' ? 'active' : ''; ?>">
                <a href="?module=employees">Employee Management</a>
            </li>
            <li class="sportedia-nav-item <?php echo $active_tab === 'branches' ? 'active' : ''; ?>">
                <a href="?module=branches">Branch Management</a>
            </li>
            <li class="sportedia-nav-item <?php echo $active_tab === 'programs' ? 'active' : ''; ?>">
                <a href="?module=programs">Sports Programs</a>
            </li>
            <li class="sportedia-nav-item <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                <a href="?module=settings">System Settings</a>
            </li>
        </ul>
    </aside>

    <!-- Right Main Content Area -->
    <main class="sportedia-main-content">
        <?php
        switch ($active_tab) {
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

<script>
window.SportediaApp = {
    openModal: function(modalType) {
        alert('Action: ' + modalType);
    }
};
</script>
