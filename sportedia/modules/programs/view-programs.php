<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search_keyword = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$sport_filter   = isset($_GET['sport_filter']) ? sanitize_text_field($_GET['sport_filter']) : '';

$table_programs = "{$wpdb->prefix}sportedia_programs";
$table_sports   = "{$wpdb->prefix}sportedia_sports";

$sports = $wpdb->get_results("SELECT * FROM {$table_sports} ORDER BY sport_name ASC");

$query = "SELECT p.*, s.sport_name
          FROM {$table_programs} p
          LEFT JOIN {$table_sports} s ON p.sport_id = s.id
          WHERE 1=1";

if (!empty($search_keyword)) {
    $query .= $wpdb->prepare(" AND p.program_name LIKE %s", '%' . $wpdb->esc_like($search_keyword) . '%');
}
if (!empty($sport_filter)) {
    $query .= $wpdb->prepare(" AND s.sport_name = %s", $sport_filter);
}

$programs = $wpdb->get_results($query);
?>

<div class="sportedia-programs-module">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 20px; color: #111827;">Programs &amp; Offers</h2>
        <button class="sportedia-btn sportedia-btn-primary" onclick="alert('Add Program Modal')">+ Add Program</button>
    </div>

    <!-- Search & Filter by Sport -->
    <form id="program-search-form" method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
        <input type="hidden" name="module" value="programs" />
        <input type="text" name="search" value="<?php echo esc_attr($search_keyword); ?>" placeholder="Search program name..." class="sportedia-floating-input" style="flex: 2;" />
        <select name="sport_filter" class="sportedia-floating-input" style="flex: 1;">
            <option value="">All Sports</option>
            <?php foreach ($sports as $sp): ?>
                <option value="<?php echo esc_attr($sp->sport_name); ?>" <?php selected($sport_filter, $sp->sport_name); ?>><?php echo esc_html($sp->sport_name); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="sportedia-btn sportedia-btn-primary">Search</button>
        <button type="button" class="sportedia-btn sportedia-btn-secondary" onclick="SportediaUI.resetFilters('program-search-form')">Reset</button>
    </form>

    <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 12px 16px;">Program Name</th>
                    <th style="padding: 12px 16px;">Sport</th>
                    <th style="padding: 12px 16px;">Duration</th>
                    <th style="padding: 12px 16px;">Price</th>
                    <th style="padding: 12px 16px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($programs)): foreach ($programs as $p): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo esc_html($p->program_name); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($p->sport_name ?: $p->sport); ?></td>
                    <td style="padding: 12px 16px; color: #4b5563;"><?php echo esc_html($p->duration_days); ?> Days</td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #111827;">$<?php echo esc_html(number_format($p->price, 2)); ?></td>
                    <td style="padding: 12px 16px;">
                        <span class="sportedia-pill <?php echo $p->status === 'Active' ? 'sportedia-pill-green' : 'sportedia-pill-neutral'; ?>">
                            <?php echo esc_html($p->status); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" style="padding: 24px; text-align: center; color: #6b7280;">No programs found matching filters.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
