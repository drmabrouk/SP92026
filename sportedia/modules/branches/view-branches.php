<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$table_branches = "{$wpdb->prefix}sportedia_branches";
$branches = $wpdb->get_results("SELECT * FROM {$table_branches} ORDER BY id ASC");
?>

<div class="sportedia-branches-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Branch Management</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="alert('Add Branch Form')">+ Add Branch</button>
    </div>

    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Branch Code</th>
                    <th style="padding: 12px 16px;">Branch Name</th>
                    <th style="padding: 12px 16px;">Phone</th>
                    <th style="padding: 12px 16px;">Capacity</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($branches)): foreach ($branches as $b): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($b->branch_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($b->branch_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($b->phone ?: '--'); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($b->capacity); ?> Members</td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $b->status === 'Active' ? 'sportedia-pill-green' : 'sportedia-pill-neutral'; ?>">
                            <?php echo esc_html($b->status); ?>
                        </span>
                    </td>
                    <td style="padding: 12px 16px;">
                        <button class="sportedia-btn sportedia-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" onclick="alert('Viewing Branch ID: <?php echo $b->id; ?>')">Details</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280;">No branches found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
