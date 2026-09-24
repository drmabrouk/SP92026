<?php
if (!defined('ABSPATH')) exit;

require_once SPORTEDIA_PLUGIN_DIR . 'modules/backup/class-sportedia-backup-handler.php';

global $wpdb;

if (isset($_POST['sportedia_create_backup_btn'])) {
    check_admin_referer('sportedia_backup_action');
    $result = Sportedia_Backup_Handler::create_backup();
    if ($result['success']) {
        echo '<div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:6px; margin-bottom:16px;">Backup created successfully: ' . esc_html($result['filename']) . '</div>';
    }
}

$system_name = get_option('sportedia_system_name', 'Sportedia Platform');
$attendance_timeout = get_option('sportedia_attendance_timeout', '5');

$table_backups = "{$wpdb->prefix}sportedia_backups";
$backups = $wpdb->get_results("SELECT * FROM {$table_backups} ORDER BY id DESC LIMIT 10");
?>

<div class="sportedia-settings-module">
    <h2 style="margin: 0 0 24px 0; font-size: 20px; color: #111827;">System Settings &amp; Configuration</h2>

    <!-- General Settings Form -->
    <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 24px;">
        <h3 style="margin: 0 0 16px 0; font-size: 16px;">General System Branding</h3>
        <form method="POST">
            <?php wp_nonce_field('sportedia_settings_action'); ?>
            <div class="sportedia-form-group">
                <input type="text" name="system_name" value="<?php echo esc_attr($system_name); ?>" class="sportedia-floating-input" placeholder=" " />
                <label class="sportedia-floating-label">System Name</label>
            </div>
            <div class="sportedia-form-group">
                <input type="number" name="attendance_timeout" value="<?php echo esc_attr($attendance_timeout); ?>" class="sportedia-floating-input" placeholder=" " />
                <label class="sportedia-floating-label">Attendance Code Refresh Interval (Seconds)</label>
            </div>
            <button type="submit" class="sportedia-btn sportedia-btn-primary">Save Configuration</button>
        </form>
    </div>

    <!-- Database Backup Management -->
    <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 16px;">Database Backup Management</h3>
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
</div>
