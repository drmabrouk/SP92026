<?php
if (!defined('ABSPATH')) exit;

require_once SPORTEDIA_PLUGIN_DIR . 'modules/backup/class-sportedia-backup-handler.php';

global $wpdb;

// Save General Settings & Logo
if (isset($_POST['sportedia_save_settings_btn'])) {
    check_admin_referer('sportedia_settings_action');

    if (isset($_POST['system_name'])) {
        update_option('sportedia_system_name', sanitize_text_field($_POST['system_name']));
    }
    if (isset($_POST['attendance_timeout'])) {
        update_option('sportedia_attendance_timeout', intval($_POST['attendance_timeout']));
    }

    // Logo Upload Handling with Validation
    if (!empty($_FILES['system_logo']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml');
        $file_mime = mime_content_type($_FILES['system_logo']['tmp_name']);

        if (in_array($file_mime, $allowed_mimes)) {
            $attachment_id = media_handle_upload('system_logo', 0);
            if (!is_wp_error($attachment_id)) {
                $logo_url = wp_get_attachment_url($attachment_id);
                update_option('sportedia_system_logo', $logo_url);
            }
        }
    }

    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        delete_option('sportedia_system_logo');
    }

    echo '<div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:6px; margin-bottom:16px;">Settings updated successfully.</div>';
}

if (isset($_POST['sportedia_create_backup_btn'])) {
    check_admin_referer('sportedia_backup_action');
    $result = Sportedia_Backup_Handler::create_backup();
    if ($result['success']) {
        echo '<div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:6px; margin-bottom:16px;">Backup created successfully: ' . esc_html($result['filename']) . '</div>';
    }
}

$system_name = get_option('sportedia_system_name', 'Sportedia Platform');
$attendance_timeout = get_option('sportedia_attendance_timeout', '5');
$saved_logo = get_option('sportedia_system_logo', '');

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

$table_backups = "{$wpdb->prefix}sportedia_backups";
$backups = $wpdb->get_results("SELECT * FROM {$table_backups} ORDER BY id DESC LIMIT 10");
?>

<div class="sportedia-settings-module">
    <h2 style="margin: 0 0 24px 0; font-size: 20px; color: #111827;">System Settings</h2>

    <!-- Settings Group Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 24px;">
        <a href="?module=settings&tab=general" class="sportedia-btn <?php echo $tab === 'general' ? 'sportedia-btn-primary' : 'sportedia-btn-secondary'; ?>">General &amp; Branding</a>
        <a href="?module=settings&tab=attendance" class="sportedia-btn <?php echo $tab === 'attendance' ? 'sportedia-btn-primary' : 'sportedia-btn-secondary'; ?>">Attendance Configuration</a>
        <a href="?module=settings&tab=backup" class="sportedia-btn <?php echo $tab === 'backup' ? 'sportedia-btn-primary' : 'sportedia-btn-secondary'; ?>">Database Backup</a>
    </div>

    <?php if ($tab === 'general'): ?>
    <!-- General Settings Form -->
    <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <form method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field('sportedia_settings_action'); ?>
            <div class="sportedia-form-group">
                <input type="text" name="system_name" value="<?php echo esc_attr($system_name); ?>" class="sportedia-floating-input" placeholder=" " required />
                <label class="sportedia-floating-label">System Branding Name</label>
            </div>

            <!-- Logo Management -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">System Logo</label>
                <?php if (!empty($saved_logo)): ?>
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                        <img src="<?php echo esc_url($saved_logo); ?>" alt="Current Logo" style="max-height: 48px; border: 1px solid #e5e7eb; padding: 4px; border-radius: 4px;" />
                        <label style="font-size: 13px; color: #ef4444; cursor: pointer;">
                            <input type="checkbox" name="remove_logo" value="1" /> Remove existing logo
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="system_logo" accept="image/png, image/jpeg, image/webp, image/svg+xml" class="sportedia-floating-input" style="padding-top: 8px;" />
            </div>

            <button type="submit" name="sportedia_save_settings_btn" class="sportedia-btn sportedia-btn-primary">Save Changes</button>
        </form>
    </div>

    <?php elseif ($tab === 'attendance'): ?>
    <!-- Attendance Settings Form -->
    <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <form method="POST">
            <?php wp_nonce_field('sportedia_settings_action'); ?>
            <div class="sportedia-form-group">
                <input type="number" name="attendance_timeout" value="<?php echo esc_attr($attendance_timeout); ?>" class="sportedia-floating-input" placeholder=" " required />
                <label class="sportedia-floating-label">Dynamic Code Refresh Interval (Seconds)</label>
            </div>
            <button type="submit" name="sportedia_save_settings_btn" class="sportedia-btn sportedia-btn-primary">Save Attendance Settings</button>
        </form>
    </div>

    <?php elseif ($tab === 'backup'): ?>
    <!-- Database Backup Management -->
    <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 16px;">Database Backup &amp; Restore Logs</h3>
            <form method="POST">
                <?php wp_nonce_field('sportedia_backup_action'); ?>
                <button type="submit" name="sportedia_create_backup_btn" class="sportedia-btn sportedia-btn-secondary">Create New Backup</button>
            </form>
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 10px 12px;">Backup Filename</th>
                    <th style="padding: 10px 12px;">Size</th>
                    <th style="padding: 10px 12px;">Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($backups)): foreach ($backups as $b): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 10px 12px; font-family: monospace; font-weight: 600;"><?php echo esc_html($b->filename); ?></td>
                    <td style="padding: 10px 12px; color: #4b5563;"><?php echo esc_html(round($b->file_size / 1024, 2)); ?> KB</td>
                    <td style="padding: 10px 12px; color: #6b7280;"><?php echo esc_html($b->created_at); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="3" style="padding: 16px; text-align: center; color: #6b7280;">No database backups available.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
