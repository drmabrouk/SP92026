<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl">
    <h1>  Academy  - Dashboard</h1>
    <hr>

    <?php if (current_user_can('_')): ?>
        <?php include SM_PLUGIN_DIR . 'templates/public-dashboard-summary.php'; ?>
    <?php else: ?>
        <div class="welcome-panel" style="padding: 20px; margin-top: 20px;">
            <h2>Welcome <?php echo wp_get_current_user()->display_name; ?></h2>
            <p> No     Academy .       .</p>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
        <?php if (current_user_can('_No')): ?>
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); box-shadow: var(--sm-shadow);">
            <h3 style="color: var(--sm-primary-color);">System Management</h3>
            <p> Add No  Manage Coaches   No.</p>
            <a href="admin.php?page=sm-students" class="sm-btn">Manage Players</a>
        </div>
        <?php endif; ?>

        <?php if (current_user_can('_')): ?>
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); box-shadow: var(--sm-shadow);">
            <h3 style="color: var(--sm-primary-color);"> </h3>
            <p>          .</p>
            <button onclick="smOpenViolationModal()" class="sm-btn">  </button>
        </div>
        <?php endif; ?>

        <?php if (current_user_can('manage_options')): ?>
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border: 1px solid var(--sm-border-color); border-radius: var(--sm-radius); box-shadow: var(--sm-shadow);">
            <h3 style="color: var(--sm-primary-color);">  </h3>
            <ul style="list-style: disc; padding-right: 20px;">
                <li><code>[sm_login]</code> -  Login .</li>
                <li><code>[sm_admin]</code> -    (Frontend).</li>
            </ul>
            <p style="font-size: 0.9em; color: #666;">:   Users   Dashboard  Login.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
