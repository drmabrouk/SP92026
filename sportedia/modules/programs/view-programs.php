<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$table_programs = "{$wpdb->prefix}sportedia_programs";
$programs = $wpdb->get_results("SELECT * FROM {$table_programs} ORDER BY id DESC");
?>

<div class="sportedia-programs-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Member Programs &amp; Offers</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="alert('Create Program Form')">+ Create Program</button>
    </div>

    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Program Name</th>
                    <th style="padding: 12px 16px;">Sport</th>
                    <th style="padding: 12px 16px;">Duration</th>
                    <th style="padding: 12px 16px;">Price</th>
                    <th style="padding: 12px 16px;">Capacity</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($programs)): foreach ($programs as $p): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($p->program_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($p->sport); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($p->duration_days); ?> Days</td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;">$<?php echo esc_html(number_format($p->price, 2)); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($p->capacity); ?> Members</td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $p->status === 'Active' ? 'sportedia-pill-green' : 'sportedia-pill-neutral'; ?>">
                            <?php echo esc_html($p->status); ?>
                        </span>
                    </td>
                    <td style="padding: 12px 16px;">
                        <button class="sportedia-btn sportedia-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" onclick="alert('Editing Program ID: <?php echo $p->id; ?>')">Edit</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" style="padding: 24px; text-align: center; color: #6b7280;">No programs created yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
