<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$table_employees = "{$wpdb->prefix}sportedia_employees";
$table_branches  = "{$wpdb->prefix}sportedia_branches";

$employees = $wpdb->get_results("
    SELECT e.*, b.branch_name
    FROM {$table_employees} e
    LEFT JOIN {$table_branches} b ON e.branch_id = b.id
    ORDER BY e.id DESC
    LIMIT 50
");
?>

<div class="sportedia-employees-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Employee Management</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="alert('Add Employee Form')">+ Add Employee</button>
    </div>

    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Code</th>
                    <th style="padding: 12px 16px;">Full Name</th>
                    <th style="padding: 12px 16px;">Email</th>
                    <th style="padding: 12px 16px;">Branch</th>
                    <th style="padding: 12px 16px;">Position</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($employees)): foreach ($employees as $emp): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-family: monospace; font-weight: 600;"><?php echo esc_html($emp->employee_code); ?></td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($emp->full_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($emp->email); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($emp->branch_name ?: 'Main Branch'); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($emp->rank_position); ?></td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $emp->status === 'Active' ? 'sportedia-pill-green' : 'sportedia-pill-neutral'; ?>">
                            <?php echo esc_html($emp->status); ?>
                        </span>
                    </td>
                    <td style="padding: 12px 16px;">
                        <button class="sportedia-btn sportedia-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" onclick="alert('Viewing Employee ID: <?php echo $emp->id; ?>')">Profile</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" style="padding: 24px; text-align: center; color: #6b7280;">No employees found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
