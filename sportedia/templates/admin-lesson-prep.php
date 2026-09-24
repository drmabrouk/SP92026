<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();

// Determine layout and view options
$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_activities_sup = in_array('sm_activities_supervisor', $roles);
$is_teacher = in_array('sm_teacher', $roles);

$can_review = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_activities_sup;

// Auto-assign supervisor helper
if (!function_exists('eess_get_teacher_supervisor')) {
    function eess_get_teacher_supervisor($teacher_id) {
        $supervisors = get_users(array('role__in' => array('sm_supervisor', 'sm_principal', 'administrator')));
        if (!empty($supervisors)) {
            return $supervisors[0]->ID;
        }
        return 1;
    }
}

// Authoritative Asia/Dubai / WordPress Timezone Timestamp Helper
if (!function_exists('eess_get_app_timestamp')) {
    function eess_get_app_timestamp($time_str = 'now') {
        $tz = wp_timezone();
        if ($time_str === 'now') {
            return (new DateTime('now', $tz))->getTimestamp();
        }
        return (new DateTime($time_str, $tz))->getTimestamp();
    }
}

// Fetch general settings with additional parameters
$prep_settings = get_option('sm_lesson_prep_settings', array(
    'submission_frequency' => 'daily',
    'submission_deadline'  => '10:00',
    'working_days'         => array('sun', 'mon', 'tue', 'wed', 'thu'),
    'pe_monday_only'       => 'yes',
    'subject_exceptions'   => '  ',
    'reminder_intervals'   => '1hour',
    'notification_prefs'   => array('email', 'system'),
    'approval_workflow'    => 'single',
    'revision_limits'      => '0',
    'template_mgmt'        => 'default',
    'auto_status_updates'  => 'yes',
    'late_submission_rules'=> 'flag',
    'calendar_integration' => 'no'
));

$deadline_time = ($prep_settings['submission_deadline'] ?? '10:00') . ':00';

// Handle deleting a lesson prep
if (isset($_POST['eess_delete_lesson_prep']) && wp_verify_nonce($_POST['eess_lesson_prep_nonce'], 'eess_lesson_prep_action')) {
    $prep_id_to_delete = intval($_POST['delete_prep_id']);
    if ($prep_id_to_delete > 0) {
        $owner_id = $wpdb->get_var($wpdb->prepare("SELECT teacher_id FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id_to_delete));
        if ($owner_id == $user_id || $is_admin || $is_sys_admin) {
            $wpdb->delete("{$wpdb->prefix}sm_lesson_preps", array('id' => $prep_id_to_delete));
            $wpdb->delete("{$wpdb->prefix}sm_lesson_comments", array('prep_id' => $prep_id_to_delete));
            echo '<div style="background:#dcfce7; color:#15803d; padding:15px; border-radius:8px; border:1px solid #bbf7d0; font-weight:700; margin-bottom:20px; font-family:\'Cairo\'; text-align:right;">✅  Delete   Notes   .</div>';
        }
    }
}

// Handle Form Submissions
if (isset($_POST['eess_save_lesson_prep']) && wp_verify_nonce($_POST['eess_lesson_prep_nonce'], 'eess_lesson_prep_action')) {
    $prep_method   = sanitize_text_field($_POST['prep_method'] ?? 'create');
    $title         = ($prep_method === 'upload') ? sanitize_text_field($_POST['upload_lesson_title'] ?? $_POST['lesson_title']) : sanitize_text_field($_POST['lesson_title']);
    $subject       = sanitize_text_field($_POST['lesson_subject']);
    $grade_level   = sanitize_text_field($_POST['lesson_grade']);
    $class_section = sanitize_text_field($_POST['lesson_section']);
    $lesson_date   = ($prep_method === 'upload') ? sanitize_text_field($_POST['upload_lesson_date'] ?? $_POST['lesson_date']) : sanitize_text_field($_POST['lesson_date']);
    $status        = sanitize_text_field($_POST['lesson_status']); // draft or submitted or scheduled

    $resources_json = isset($_POST['selected_resources_json']) ? sanitize_text_field($_POST['selected_resources_json']) : '[]';
    $resources_array = json_decode(stripslashes($resources_json), true);

    // Handle document file upload if uploaded
    $uploaded_file_url = '';
    if (!empty($_FILES['prep_document_file']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $uploaded = wp_handle_upload($_FILES['prep_document_file'], array('test_form' => false));
        if (isset($uploaded['url'])) {
            $uploaded_file_url = $uploaded['url'];
        }
    }

    $lesson_data = array(
        'prep_method'     => $prep_method,
        'file_url'        => $uploaded_file_url,
        'objectives'      => sanitize_textarea_field($_POST['objectives'] ?? ''),
        'warmup'          => sanitize_textarea_field($_POST['warmup'] ?? ''),
        'physical_prep'   => sanitize_textarea_field($_POST['physical_prep'] ?? ''),
        'skill_prep'      => sanitize_textarea_field($_POST['skill_prep'] ?? ''),
        'conclusion'      => sanitize_textarea_field($_POST['conclusion'] ?? ''),
        'national_agenda' => sanitize_textarea_field($_POST['national_agenda'] ?? ''),
        'cross_subject'   => sanitize_textarea_field($_POST['cross_subject'] ?? ''),
        'activities'      => sanitize_textarea_field($_POST['activities'] ?? ''),
        'resources'       => is_array($resources_array) ? array_map('sanitize_text_field', $resources_array) : array(),
        'evaluation'      => sanitize_textarea_field($_POST['evaluation'] ?? ''),
        'homework'        => sanitize_textarea_field($_POST['homework'] ?? ''),
        'notes'           => sanitize_textarea_field($_POST['notes'] ?? ''),
        'scheduled_time'  => isset($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : '',
    );

    // Compute Late Submission status if submitted
    $delay_seconds = 0;
    $final_status = $status;
    $submission_time = null;

    if ($status === 'submitted') {
        // Authoritative server timestamp (Asia/Dubai timezone configured for WordPress)
        $calc_res        = EESS_Org_Helper::calculate_lesson_prep_status($subject);
        $submission_time = $calc_res['submission_time'];
        $final_status    = $calc_res['status'];
        $delay_seconds   = $calc_res['delay_seconds'];
    }

    $supervisor_id = eess_get_teacher_supervisor($user_id);

    if (isset($_POST['prep_id']) && !empty($_POST['prep_id'])) {
        $prep_id = intval($_POST['prep_id']);
        $existing_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));

        // Preserve history by incrementing version if resubmitting from revision_required
        $version = 1;
        $parent_id = 0;
        if ($existing_status === 'revision_required' && $status === 'submitted') {
            $version_data = $wpdb->get_row($wpdb->prepare("SELECT version, parent_id FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            $version = intval($version_data->version) + 1;
            $parent_id = $version_data->parent_id == 0 ? $prep_id : $version_data->parent_id;

            // Create a new version record
            $wpdb->insert(
                "{$wpdb->prefix}sm_lesson_preps",
                array(
                    'teacher_id'      => $user_id,
                    'supervisor_id'   => $supervisor_id,
                    'title'           => $title,
                    'subject'         => $subject,
                    'grade_level'     => $grade_level,
                    'class_section'   => $class_section,
                    'lesson_date'     => $lesson_date,
                    'submission_time' => $submission_time,
                    'status'          => $final_status,
                    'delay_seconds'   => $delay_seconds,
                    'lesson_data'     => json_encode($lesson_data),
                    'version'         => $version,
                    'parent_id'       => $parent_id,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql')
                )
            );
            $wpdb->update("{$wpdb->prefix}sm_lesson_preps", array('status' => 'resubmitted'), array('id' => $prep_id));
        } else {
            // Standard update
            $wpdb->update(
                "{$wpdb->prefix}sm_lesson_preps",
                array(
                    'title'           => $title,
                    'subject'         => $subject,
                    'grade_level'     => $grade_level,
                    'class_section'   => $class_section,
                    'lesson_date'     => $lesson_date,
                    'submission_time' => $submission_time,
                    'status'          => $final_status,
                    'delay_seconds'   => $delay_seconds,
                    'lesson_data'     => json_encode($lesson_data),
                    'updated_at'      => current_time('mysql')
                ),
                array('id' => $prep_id)
            );
        }
    } else {
        // Insert new prep
        $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'teacher_id'      => $user_id,
                'supervisor_id'   => $supervisor_id,
                'title'           => $title,
                'subject'         => $subject,
                'grade_level'     => $grade_level,
                'class_section'   => $class_section,
                'lesson_date'     => $lesson_date,
                'submission_time' => $submission_time,
                'status'          => $final_status,
                'delay_seconds'   => $delay_seconds,
                'lesson_data'     => json_encode($lesson_data),
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            )
        );
    }
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:15px; font-weight:700; font-size:13px;"> Save  .</div>';
}

// Handle Supervisor Actions
if (isset($_POST['eess_supervisor_action']) && wp_verify_nonce($_POST['eess_supervisor_nonce'], 'eess_supervisor_action_nonce')) {
    $prep_id = intval($_POST['prep_id']);
    $action  = sanitize_text_field($_POST['prep_status_action']);
    $comment = sanitize_textarea_field($_POST['supervisor_comment']);

    $wpdb->update(
        "{$wpdb->prefix}sm_lesson_preps",
        array('status' => $action, 'updated_at' => current_time('mysql')),
        array('id' => $prep_id)
    );

    if (!empty($comment)) {
        $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_comments",
            array(
                'prep_id'      => $prep_id,
                'user_id'      => $user_id,
                'comment_text' => $comment,
                'created_at'   => current_time('mysql')
            )
        );
    }
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:15px; font-weight:700; font-size:13px;"> Update   Add Notes .</div>';
}

// Handle Settings Update (expanded fields)
if (isset($_POST['eess_save_prep_settings']) && wp_verify_nonce($_POST['eess_settings_nonce'], 'eess_settings_action')) {
    $new_settings = array(
        'submission_frequency' => sanitize_text_field($_POST['submission_frequency']),
        'submission_deadline'  => sanitize_text_field($_POST['submission_deadline']),
        'working_days'         => isset($_POST['working_days']) ? array_map('sanitize_text_field', $_POST['working_days']) : array(),
        'pe_monday_only'       => sanitize_text_field($_POST['pe_monday_only'] ?? 'no'),
        'subject_exceptions'   => sanitize_text_field($_POST['subject_exceptions'] ?? ''),
        'reminder_intervals'   => sanitize_text_field($_POST['reminder_intervals'] ?? ''),
        'notification_prefs'   => isset($_POST['notification_prefs']) ? array_map('sanitize_text_field', $_POST['notification_prefs']) : array(),
        'approval_workflow'    => sanitize_text_field($_POST['approval_workflow'] ?? 'single'),
        'revision_limits'      => sanitize_text_field($_POST['revision_limits'] ?? '0'),
        'template_mgmt'        => sanitize_text_field($_POST['template_mgmt'] ?? 'default'),
        'auto_status_updates'  => sanitize_text_field($_POST['auto_status_updates'] ?? 'no'),
        'late_submission_rules'=> sanitize_text_field($_POST['late_submission_rules'] ?? ''),
        'calendar_integration' => sanitize_text_field($_POST['calendar_integration'] ?? 'no')
    );
    update_option('sm_lesson_prep_settings', $new_settings);
    $prep_settings = $new_settings;
    $deadline_time = ($prep_settings['submission_deadline'] ?? '10:00') . ':00';
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:15px; font-weight:700; font-size:13px;"> Save    .</div>';
}

// Load edit prep details
$edit_prep = null;
if (isset($_GET['edit_prep_id'])) {
    $edit_prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['edit_prep_id']), $user_id));
}

// Load duplicate prep details
if (isset($_GET['duplicate_prep_id'])) {
    $dup_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['duplicate_prep_id']), $user_id));
    if ($dup_source) {
        $edit_prep = $dup_source;
        $edit_prep->id = 0;
        $edit_prep->title .= ' ()';
        $edit_prep->lesson_date = current_time('Y-m-d');
        $edit_prep->status = 'draft';
    }
}

$all_subjects = SM_DB::get_subjects();
$unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $all_subjects));
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 14px 18px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 14px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 22px; width: 22px; height: 22px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 2px 0; font-size: 18px; font-weight: 800; color: #0f172a;">     </h2>
                <p style="margin: 0; font-size: 11.5px; color: #64748b; font-weight: 500;">     Academy    </p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Modern Compact Pastel Wine-Red Bulk Download Button -->
            <button type="button" onclick="document.getElementById('eess-prep-bulk-download-modal').style.display='flex'" title="Upload    " style="background: #fef2f2; color: #881337; border: 1px solid #fecdd3; height: 34px; border-radius: 10px; padding: 0 14px; font-weight: 800; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); transition: background 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                <span class="dashicons dashicons-download" style="font-size: 15px; width: 15px; height: 15px; margin: 0; color: #881337;"></span>
                <span>Upload  </span>
            </button>

            <?php if ($is_teacher): ?>
            <button type="button" onclick="document.getElementById('prep-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>Add  </span>
            </button>
            <?php endif; ?>

            <?php if ($can_review): ?>
            <!-- Unified Print Report Dropdown Action -->
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="const d=document.getElementById('eess-print-report-dropdown'); d.style.display = d.style.display==='none'?'block':'none'; event.stopPropagation();" class="sm-btn" style="background: #0284c7; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 18px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(2,132,199,0.2);">
                    <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; color: #fff;"></span>
                    <span>Print </span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; color: #fff;"></span>
                </button>
                <div id="eess-print-report-dropdown" style="display: none; position: absolute; right: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 230px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <a href="javascript:void(0)" onclick="document.getElementById('eess-print-report-dropdown').style.display='none'; eessOpenSchoolPrepReportModal();" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; border-bottom: 1px solid #f1f5f9;">
                        <span class="dashicons dashicons-building" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>Print   </span>
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=non_submission_lesson_prep'); ?>" target="_blank" onclick="document.getElementById('eess-print-report-dropdown').style.display='none';" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #dc2626; font-size: 12px; font-weight: 700; text-decoration: none;">
                        <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>Print    </span>
                    </a>
                </div>
            </div>


            <?php if ($is_admin): ?>
            <!-- Assign Ready-Made Lesson Prep Button (System Admin Only) -->
            <button type="button" onclick="document.getElementById('eess-assign-prep-modal').style.display='flex'" class="sm-btn" style="background: #0f172a; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 18px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-user-freelance" style="font-size: 16px; width: 16px; height: 16px; color: #38bdf8;"></span>
                <span> </span>
            </button>
            <?php endif; ?>

            <!-- Settings Gear Icon Button (Icon Only) -->
            <button type="button" onclick="document.getElementById('prep-settings-modal').style.display='flex'" title=" " class="sm-btn sm-btn-outline" style="width: 38px; height: 38px; border-radius: 50% !important; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 0;">
                <span class="dashicons dashicons-admin-generic" style="font-size: 18px; width: 18px; height: 18px; margin: 0; color: #475569;"></span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Administrative Compliance & Follow-up Statistics for Lesson Preparation -->
    <?php if ($can_review):
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);
        if (!$user_scope['unrestricted'] && !empty($user_scope['schools'])) {
            $all_teachers_prep = get_users(array(
                'role' => 'sm_teacher',
                'meta_query' => array(
                    'relation' => 'OR',
                    array('key' => 'eess_school_id', 'value' => $user_scope['schools'], 'compare' => 'IN'),
                    array('key' => 'sm_school_id', 'value' => $user_scope['schools'], 'compare' => 'IN')
                )
            ));
        } else {
            $all_teachers_prep = get_users(array('role' => 'sm_teacher'));
        }
        $pe_teachers_prep = array_filter($all_teachers_prep, function($t) {
            $spec = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: ''));
            return (mb_strpos($spec, '') !== false || mb_strpos($spec, '') !== false || mb_strpos($spec, 'Health') !== false || mb_strpos($spec, 'Physical') !== false);
        });
        $target_prep_teachers = !empty($pe_teachers_prep) ? $pe_teachers_prep : $all_teachers_prep;
        $prep_teacher_ids = array_map(function($t) { return $t->ID; }, $target_prep_teachers);
        $total_prep_teachers = count($prep_teacher_ids);

        if (!empty($prep_teacher_ids)) {
            $prep_placeholders = implode(',', array_fill(0, count($prep_teacher_ids), '%d'));
            $stats_row = $wpdb->get_row($wpdb->prepare("
                SELECT
                    SUM(CASE WHEN status IN ('submitted', 'approved', 'resubmitted', 'late') THEN 1 ELSE 0 END) as cnt_submitted,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as cnt_approved,
                    SUM(CASE WHEN status = 'revision_required' THEN 1 ELSE 0 END) as cnt_revision,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as cnt_rejected,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as cnt_late
                FROM {$wpdb->prefix}sm_lesson_preps
                WHERE teacher_id IN ($prep_placeholders)
            ", ...$prep_teacher_ids));

            $stats_submitted = intval($stats_row->cnt_submitted ?? 0);
            $stats_approved  = intval($stats_row->cnt_approved ?? 0);
            $stats_revision  = intval($stats_row->cnt_revision ?? 0);
            $stats_rejected  = intval($stats_row->cnt_rejected ?? 0);
            $stats_late      = intval($stats_row->cnt_late ?? 0);
        } else {
            $stats_submitted = 0;
            $stats_approved  = 0;
            $stats_revision  = 0;
            $stats_rejected  = 0;
            $stats_late      = 0;
        }

        $stats_missing = max(0, $total_prep_teachers - $stats_submitted);
        $prep_compliance_rate = $total_prep_teachers > 0 ? round(($stats_submitted / $total_prep_teachers) * 100) : 0;
    ?>
    <div style="background: #ffffff; padding: 18px 20px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02); margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #334155; text-align: center;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 4px;">  </div>
                <div style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo $total_prep_teachers; ?></div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #0284c7; text-align: center;">
                <div style="font-size: 11px; color: #0369a1; font-weight: 700; margin-bottom: 4px;"> </div>
                <div style="font-size: 18px; font-weight: 900; color: #0284c7;"><?php echo $stats_submitted; ?></div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #16a34a; text-align: center;">
                <div style="font-size: 11px; color: #166534; font-weight: 700; margin-bottom: 4px;"> </div>
                <div style="font-size: 18px; font-weight: 900; color: #16a34a;"><?php echo $stats_approved; ?></div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #d97706; text-align: center;">
                <div style="font-size: 11px; color: #b45309; font-weight: 700; margin-bottom: 4px;"> Edit</div>
                <div style="font-size: 18px; font-weight: 900; color: #d97706;"><?php echo $stats_revision; ?></div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #b91c1c; text-align: center;">
                <div style="font-size: 11px; color: #991b1b; font-weight: 700; margin-bottom: 4px;"> </div>
                <div style="font-size: 18px; font-weight: 900; color: #b91c1c;"><?php echo $stats_rejected; ?></div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #dc2626; text-align: center;">
                <div style="font-size: 11px; color: #991b1b; font-weight: 700; margin-bottom: 4px;">  / </div>
                <div style="font-size: 18px; font-weight: 900; color: #dc2626;"><?php echo $stats_missing; ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">

        <!-- Step-by-Step Modal Wizard for Lesson Prep -->
        <?php if ($is_teacher): ?>
        <div id="prep-modal" class="sm-modal-overlay" style="display: <?php echo ($edit_prep && $edit_prep->id > 0) ? 'flex' : 'none'; ?>; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;">
            <div class="sm-modal-content" style="background: #ffffff; border-radius: 20px; max-width: 960px; width: 100%; max-height: 94vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); border: 1px solid #cbd5e1; display: flex; flex-direction: column;">

                <!-- Clean White Modal Header without Colored Banner -->
                <div style="background: #ffffff; padding: 22px 28px 14px 28px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; position: relative;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 44px; height: 44px; background: #f8fafc; border-radius: 50%; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; color: #0f172a;">
                            <span class="dashicons dashicons-welcome-write-blog" style="font-size: 22px; width: 22px; height: 22px; margin: 0;"></span>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; font-family: 'Cairo', sans-serif;">
                                <?php echo ($edit_prep && $edit_prep->id > 0) ? 'Edit   ' : '   '; ?>
                            </h3>
                            <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b; font-weight: 500;">   Active   </p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span id="prep-autosave-badge" style="background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1;"></span>
                        <button type="button" onclick="document.getElementById('prep-modal').style.display='none'" style="width: 34px; height: 34px; border-radius: 50%; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; font-size: 20px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div style="padding: 24px; box-sizing: border-box; overflow-y: auto; flex: 1;">
                    <?php
                    $assigned_subject = get_user_meta($user_id, 'sm_specialization', true) ?: '';
                    $is_locked = ($is_teacher && !empty($assigned_subject));
                    $current_subject = !empty($edit_prep->subject) ? $edit_prep->subject : $assigned_subject;

                    $subj_fields = SM_Settings::get_subject_lesson_fields($current_subject);
                    $data = $edit_prep ? json_decode($edit_prep->lesson_data, true) : array();
                    ?>

                    <form method="post" enctype="multipart/form-data" id="eess-lesson-prep-wizard-form" oninput="eessTriggerPrepAutoSave()">
                        <?php wp_nonce_field('eess_lesson_prep_action', 'eess_lesson_prep_nonce'); ?>
                        <input type="hidden" name="prep_id" id="eess_prep_db_id" value="<?php echo $edit_prep->id ?? 0; ?>">

                        <input type="hidden" name="prep_method" id="eess_prep_method" value="create">

                        <!-- Initial Preparation Method Selection Screen -->
                        <div id="eess-prep-method-select" style="display: <?php echo ($edit_prep && $edit_prep->id > 0) ? 'none' : 'block'; ?>; text-align: center; padding: 15px 0;">
                            <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a; text-align: center;">Welcome . <?php echo esc_html($user->display_name); ?> —   </h4>
                            <p style="margin: 0 0 24px 0; font-size: 13px; color: #64748b; line-height: 1.6; text-align: center;">       :</p>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 10px;">
                                <!-- Option 1: Create Lesson Preparation -->
                                <div id="eess-prep-card-create" onclick="eessChoosePrepMethod('create')" style="background: #ffffff; border: 2px solid #cbd5e1; border-radius: 16px; padding: 22px 18px; cursor: pointer; transition: all 0.2s ease; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='#881337'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)';">
                                    <div style="width: 52px; height: 52px; background: #fef2f2; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; color: #881337; margin-bottom: 12px;">
                                        <span class="dashicons dashicons-welcome-write-blog" style="font-size: 26px; width: 26px; height: 26px;"></span>
                                    </div>
                                    <h4 style="margin: 0 0 6px 0; font-size: 14.5px; font-weight: 800; color: #881337;">   </h4>
                                    <p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">   Active   .</p>
                                </div>

                                <!-- Option 2: Upload Ready Lesson File -->
                                <div id="eess-prep-card-upload" onclick="eessChoosePrepMethod('upload')" style="background: #ffffff; border: 2px solid #cbd5e1; border-radius: 16px; padding: 22px 18px; cursor: pointer; transition: all 0.2s ease; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='#0284c7'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)';">
                                    <div style="width: 52px; height: 52px; background: #e0f2fe; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; color: #0284c7; margin-bottom: 12px;">
                                        <span class="dashicons dashicons-upload" style="font-size: 26px; width: 26px; height: 26px;"></span>
                                    </div>
                                    <h4 style="margin: 0 0 6px 0; font-size: 14.5px; font-weight: 800; color: #0f172a;">    (PDF / Word)</h4>
                                    <p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">       No.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper Track Container (Hidden initially until method chosen) -->
                        <div id="eess-prep-stepper-track" style="display: <?php echo ($edit_prep && $edit_prep->id > 0) ? 'block' : 'none'; ?>; background: #f8fafc; padding: 14px 20px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 22px; position: relative;">
                            <div style="position: absolute; top: 50%; left: 40px; right: 40px; height: 2px; background: #e2e8f0; transform: translateY(-50%); z-index: 1;"></div>
                            <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <div class="eess-prep-step-indicator active" id="eess-prep-ind-1" style="font-weight: 800; font-size: 11.5px; color: #881337; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span id="eess-prep-num-1" style="background: #881337; color: white; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">1</span>
                                    <span id="eess-prep-step-lbl-1"> </span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-2" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span id="eess-prep-num-2" style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">2</span>
                                    <span id="eess-prep-step-lbl-2"> </span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-3" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span id="eess-prep-num-3" style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">3</span>
                                    <span id="eess-prep-step-lbl-3"> Send</span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-4" style="display: none; font-weight: 700; font-size: 11.5px; color: #94a3b8; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span id="eess-prep-num-4" style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">4</span>
                                    <span id="eess-prep-step-lbl-4"> Send</span>
                                </div>
                            </div>
                        </div>

                        <?php
                            $teacher_school = get_user_meta($user_id, 'eess_school_name', true) ?: 'Academy  Home';
                            $teacher_grade  = get_user_meta($user_id, 'sm_grade_level', true) ?: (get_user_meta($user_id, 'grade', true) ?: 'Training Group ');
                            $teacher_section= get_user_meta($user_id, 'sm_class_section', true) ?: (get_user_meta($user_id, 'section', true) ?: '');
                        ?>
                        <input type="hidden" id="eess_lesson_subject" name="lesson_subject" value="<?php echo esc_attr($current_subject); ?>">
                        <input type="hidden" id="eess_lesson_grade" name="lesson_grade" value="<?php echo esc_attr($edit_prep->grade_level ?? $teacher_grade); ?>">
                        <input type="hidden" id="eess_lesson_section" name="lesson_section" value="<?php echo esc_attr($edit_prep->class_section ?? $teacher_section); ?>">

                        <!-- ==================== WORKFLOW 1: UPLOAD READY FILE (3 STEPS) ==================== -->
                        <div id="eess-prep-workflow-upload" style="display: none;">
                            <!-- Upload Step 1: Lesson Information -->
                            <div class="eess-prep-upload-stage" id="eess-prep-upload-stage-1" style="display: block;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :   Home</h4>
                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
                                    <div>
                                        <label for="eess_upload_lesson_title" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">   <span style="color:#ef4444;">*</span></label>
                                        <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">       </span>
                                        <input type="text" id="eess_upload_lesson_title" name="upload_lesson_title" value="<?php echo esc_attr($edit_prep->title ?? ''); ?>" class="sm-input" placeholder="   ..." style="height: 34px; font-size: 12px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; width: 100%; box-sizing: border-box;">
                                    </div>
                                    <div>
                                        <label for="eess_upload_lesson_date" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">  <span style="color:#ef4444;">*</span></label>
                                        <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">    </span>
                                        <input type="date" id="eess_upload_lesson_date" name="upload_lesson_date" value="<?php echo esc_attr($edit_prep->lesson_date ?? current_time('Y-m-d')); ?>" class="sm-input" style="height: 34px; font-size: 12px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; width: 100%; box-sizing: border-box;">
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Step 2: Upload File -->
                            <div class="eess-prep-upload-stage" id="eess-prep-upload-stage-2" style="display: none;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :    </h4>
                                <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px; padding: 22px; margin-bottom: 16px;">
                                    <label for="eess_prep_document_file" style="display: block; font-weight: 800; font-size: 13px; color: #0369a1; margin-bottom: 2px;">     (PDF, DOC, DOCX) <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #0284c7; font-weight: 500; margin-bottom: 10px;"> No     10       </span>
                                    <input type="file" name="prep_document_file" id="eess_prep_document_file" accept=".pdf,.doc,.docx" class="sm-input" onchange="eessValidatePrepFile(this)" style="height: 44px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12.5px; padding: 8px 12px; width: 100%; box-sizing: border-box;">
                                    <div id="eess_prep_file_status_preview" style="display: none; margin-top: 12px; font-size: 12px; font-weight: 700; color: #166534; background: #dcfce7; padding: 10px 14px; border-radius: 8px; border: 1px solid #bbf7d0;"></div>
                                </div>
                            </div>

                            <!-- Upload Step 3: Confirmation & Submission -->
                            <div class="eess-prep-upload-stage" id="eess-prep-upload-stage-3" style="display: none;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :     </h4>
                                <div style="background: #f8fafc; padding: 20px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 12.5px; line-height: 1.6; margin-bottom: 20px;" id="eess-prep-upload-review-summary">
                                    <!-- Dynamic Upload Summary -->
                                </div>
                            </div>
                        </div>

                        <!-- ==================== WORKFLOW 2: CREATE IN SYSTEM (4 STEPS) ==================== -->
                        <div id="eess-prep-workflow-create" style="display: none;">
                            <!-- Create Step 1: Basic Information -->
                            <div class="eess-prep-create-stage" id="eess-prep-create-stage-1" style="display: block;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :    </h4>
                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
                                    <div>
                                        <label for="eess_lesson_title" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">   <span style="color:#ef4444;">*</span></label>
                                        <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">      </span>
                                        <input type="text" id="eess_lesson_title" name="lesson_title" value="<?php echo esc_attr($edit_prep->title ?? ''); ?>" class="sm-input" placeholder=" ..." style="height: 34px; font-size: 12px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; width: 100%; box-sizing: border-box;">
                                    </div>
                                    <div>
                                        <label for="eess_lesson_date" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">   <span style="color:#ef4444;">*</span></label>
                                        <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">   </span>
                                        <input type="date" id="eess_lesson_date" name="lesson_date" value="<?php echo esc_attr($edit_prep->lesson_date ?? current_time('Y-m-d')); ?>" class="sm-input" style="height: 34px; font-size: 12px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; width: 100%; box-sizing: border-box;">
                                    </div>
                                </div>

                                <!-- Lesson Objective Field (150 - 350 chars) -->
                                <div style="margin-bottom: 6px;">
                                    <label for="eess_objectives" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">    <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">   (  No  ) —  : 150   : 350 </span>
                                    <textarea id="eess_objectives" name="objectives" maxlength="350" oninput="eessUpdateCharBounds(this, 150, 350, 'cnt_objectives')" class="sm-input" style="height: 100px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="   ..."><?php echo esc_textarea($data['objectives'] ?? ''); ?></textarea>
                                </div>
                                <div style="text-align: left; font-size: 11px; font-weight: 700; color: #dc2626; font-family: monospace; margin-bottom: 20px;" id="cnt_objectives">0 / 150 - 350  ( 150   )</div>
                            </div>

                            <!-- Create Step 2: Physical Education Lesson Components -->
                            <div class="eess-prep-create-stage" id="eess-prep-create-stage-2" style="display: none;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :     </h4>

                                <!-- Warm-up (150 - 350 chars) -->
                                <div style="margin-bottom: 4px;">
                                    <label for="eess_warmup" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">1.    (Warm-Up) <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">       (150 – 350 )</span>
                                    <textarea id="eess_warmup" name="warmup" maxlength="350" oninput="eessUpdateCharBounds(this, 150, 350, 'cnt_warmup')" class="sm-input" style="height: 85px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="    ..."><?php echo esc_textarea($data['warmup'] ?? ''); ?></textarea>
                                </div>
                                <div style="text-align: left; font-size: 11px; font-weight: 700; color: #dc2626; font-family: monospace; margin-bottom: 16px;" id="cnt_warmup">0 / 150 - 350  ( 150   )</div>

                                <!-- Physical Preparation (150 - 400 chars) -->
                                <div style="margin-bottom: 4px;">
                                    <label for="eess_physical_prep" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">2.     <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">      (150 – 400 )</span>
                                    <textarea id="eess_physical_prep" name="physical_prep" maxlength="400" oninput="eessUpdateCharBounds(this, 150, 400, 'cnt_physical_prep')" class="sm-input" style="height: 90px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="   ..."><?php echo esc_textarea($data['physical_prep'] ?? ''); ?></textarea>
                                </div>
                                <div style="text-align: left; font-size: 11px; font-weight: 700; color: #dc2626; font-family: monospace; margin-bottom: 16px;" id="cnt_physical_prep">0 / 150 - 400  ( 150   )</div>

                                <!-- Skill Preparation (150 - 400 chars) -->
                                <div style="margin-bottom: 4px;">
                                    <label for="eess_skill_prep" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">3.    <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">       (150 – 400 )</span>
                                    <textarea id="eess_skill_prep" name="skill_prep" maxlength="400" oninput="eessUpdateCharBounds(this, 150, 400, 'cnt_skill_prep')" class="sm-input" style="height: 90px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="   ..."><?php echo esc_textarea($data['skill_prep'] ?? ''); ?></textarea>
                                </div>
                                <div style="text-align: left; font-size: 11px; font-weight: 700; color: #dc2626; font-family: monospace; margin-bottom: 16px;" id="cnt_skill_prep">0 / 150 - 400  ( 150   )</div>

                                <!-- Conclusion (150 - 350 chars) -->
                                <div style="margin-bottom: 4px;">
                                    <label for="eess_conclusion" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">4.   No <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">   Status    (150 – 350 )</span>
                                    <textarea id="eess_conclusion" name="conclusion" maxlength="350" oninput="eessUpdateCharBounds(this, 150, 350, 'cnt_conclusion')" class="sm-input" style="height: 85px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="   ..."><?php echo esc_textarea($data['conclusion'] ?? ''); ?></textarea>
                                </div>
                                <div style="text-align: left; font-size: 11px; font-weight: 700; color: #dc2626; font-family: monospace; margin-bottom: 16px;" id="cnt_conclusion">0 / 150 - 350  ( 150   )</div>
                            </div>

                            <!-- Create Step 3: Educational Connections -->
                            <div class="eess-prep-create-stage" id="eess-prep-create-stage-3" style="display: none;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :   </h4>

                                <!-- Connection to National Agenda -->
                                <div style="margin-bottom: 20px;">
                                    <label for="eess_national_agenda" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">    Country <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">         </span>
                                    <textarea id="eess_national_agenda" name="national_agenda" class="sm-input" style="height: 85px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="    ..."><?php echo esc_textarea($data['national_agenda'] ?? ''); ?></textarea>
                                </div>

                                <!-- Connection to Other Subjects -->
                                <div style="margin-bottom: 18px;">
                                    <label for="eess_cross_subject" style="display: block; font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 2px;"> Sports Activities   <span style="color:#ef4444;">*</span></label>
                                    <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500; margin-bottom: 6px;">       No</span>
                                    <textarea id="eess_cross_subject" name="cross_subject" class="sm-input" style="height: 85px; font-size: 12.5px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 12px; width: 100%; box-sizing: border-box; line-height: 1.5;" placeholder="   Sports Activities ..."><?php echo esc_textarea($data['cross_subject'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- Create Step 4: Review & Submission -->
                            <div class="eess-prep-create-stage" id="eess-prep-create-stage-4" style="display: none;">
                                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;"> :    Send </h4>
                                <div style="background: #f8fafc; padding: 20px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 12.5px; line-height: 1.6; margin-bottom: 20px;" id="eess-prep-create-review-summary">
                                    <!-- Dynamic Create Summary -->
                                </div>
                            </div>
                        </div>

                        <script>
                            function eessUpdateCharBounds(input, minLen, maxLen, badgeId) {
                                var len = input.value.length;
                                var badge = document.getElementById(badgeId);
                                if (badge) {
                                    if (len < minLen) {
                                        badge.style.color = '#dc2626';
                                        badge.innerText = len + ' / ' + minLen + ' - ' + maxLen + '  ( ' + (minLen - len) + '   )';
                                    } else if (len > maxLen) {
                                        badge.style.color = '#dc2626';
                                        badge.innerText = len + ' / ' + minLen + ' - ' + maxLen + '  (    ' + (len - maxLen) + ' )';
                                    } else {
                                        badge.style.color = '#16a34a';
                                        badge.innerText = len + ' / ' + minLen + ' - ' + maxLen + '  ✓ ( )';
                                    }
                                }
                            }

                            function eessValidatePrepFile(input) {
                                var preview = document.getElementById('eess_prep_file_status_preview');
                                if (input.files && input.files[0]) {
                                    var file = input.files[0];
                                    var ext = file.name.split('.').pop().toLowerCase();
                                    if (!['pdf', 'doc', 'docx'].includes(ext)) {
                                        alert('    PDF  Word .');
                                        input.value = '';
                                        if (preview) preview.style.display = 'none';
                                        return;
                                    }
                                    if (preview) {
                                        preview.innerHTML = '   : <strong>' + file.name + '</strong> (' + Math.round(file.size / 1024) + ' )';
                                        preview.style.display = 'block';
                                    }
                                } else if (preview) {
                                    preview.style.display = 'none';
                                }
                            }

                            function eessChoosePrepMethod(method) {
                                document.getElementById('eess_prep_method').value = method;
                                document.getElementById('eess-prep-method-select').style.display = 'none';
                                document.getElementById('eess-prep-stepper-track').style.display = 'block';

                                const ind4 = document.getElementById('eess-prep-ind-4');

                                if (method === 'upload') {
                                    document.getElementById('eess-prep-step-lbl-1').innerText = ' ';
                                    document.getElementById('eess-prep-step-lbl-2').innerText = ' ';
                                    document.getElementById('eess-prep-step-lbl-3').innerText = ' Send';
                                    if (ind4) ind4.style.display = 'none';

                                    document.getElementById('eess-prep-workflow-upload').style.display = 'block';
                                    document.getElementById('eess-prep-workflow-create').style.display = 'none';
                                } else {
                                    document.getElementById('eess-prep-step-lbl-1').innerText = ' ';
                                    document.getElementById('eess-prep-step-lbl-2').innerText = ' ';
                                    document.getElementById('eess-prep-step-lbl-3').innerText = ' ';
                                    document.getElementById('eess-prep-step-lbl-4').innerText = ' Send';
                                    if (ind4) ind4.style.display = 'flex';

                                    document.getElementById('eess-prep-workflow-upload').style.display = 'none';
                                    document.getElementById('eess-prep-workflow-create').style.display = 'block';
                                }

                                eessGoToPrepStage(1);
                            }
                        </script>

                        <!-- Wizard Step Action Controls (RTL structure: Next/Submit on far-left, Previous on far-right) -->
                        <div style="display: flex; gap: 12px; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="button" id="eess-prep-next-btn" class="sm-btn" style="width: auto; height: 38px; padding: 0 22px; font-size: 12.5px; background: #881337; border: none; border-radius: 9999px !important; cursor:pointer; color: white !important; font-weight: 800;" onclick="eessGoToPrepStage(eessActivePrepStage + 1)">  Next ➔</button>

                                <!-- Submit for Review Action (Dark Red Primary Token) -->
                                <button type="submit" name="eess_save_lesson_prep" id="eess-prep-submit-btn" onclick="document.getElementById('lesson_status').value='submitted'; eessClearPrepDraftBackup();" class="sm-btn" style="width: auto; height: 38px; padding: 0 22px; font-size: 12.5px; background: #dc2626; border-radius: 9999px !important; border: none; color: white !important; font-weight: 800; display: none; cursor:pointer; box-shadow: 0 4px 12px rgba(220,38,38,0.2);">Send </button>

                                <!-- Save as Draft Action (Secondary Token) -->
                                <button type="submit" name="eess_save_lesson_prep" id="eess-prep-draft-btn" onclick="document.getElementById('lesson_status').value='draft'" class="sm-btn sm-btn-secondary" style="width: auto; height: 38px; padding: 0 18px; font-size: 12.5px; background: #475569; color: white !important; border-radius: 9999px !important; border: none; font-weight: 700; display: none; cursor:pointer;">Save </button>
                            </div>

                            <div>
                                <button type="button" id="eess-prep-prev-btn" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; padding: 0 18px; font-size: 12.5px; border-radius: 9999px !important; display: none; cursor:pointer; border: 1px solid #cbd5e1; color: #475569; font-weight: 700;" onclick="eessGoToPrepStage(eessActivePrepStage - 1)">Previous</button>
                            </div>
                        </div>

                        <input type="hidden" name="lesson_status" id="lesson_status" value="submitted">
                    </form>
                </div>
            </div>
        </div>

        <script>
            let eessAutoSaveTimeout = null;

            function eessTriggerPrepAutoSave() {
                // Save to local storage for offline recovery
                const draftData = {
                    title: document.getElementById('eess_lesson_title').value,
                    subject: document.getElementById('eess_lesson_subject') ? document.getElementById('eess_lesson_subject').value : '',
                    grade: document.getElementById('eess_lesson_grade').value,
                    section: document.getElementById('eess_lesson_section').value,
                    date: document.getElementById('eess_lesson_date').value,
                    objectives: document.getElementById('eess_objectives').value,
                    warmup: document.getElementById('eess_warmup').value,
                    activities: document.getElementById('eess_activities').value,
                    evaluation: document.getElementById('eess_evaluation').value,
                    homework: document.getElementById('eess_homework').value,
                    notes: document.getElementById('eess_notes').value
                };
                localStorage.setItem('eess_lesson_prep_draft_' + <?php echo $user_id; ?>, JSON.stringify(draftData));

                const badge = document.getElementById('prep-autosave-badge');
                if (badge) badge.innerText = ' ( Save...)';

                clearTimeout(eessAutoSaveTimeout);
                eessAutoSaveTimeout = setTimeout(() => {
                    if (badge) badge.innerText = ' ( Save)';
                }, 1000);
            }

            function eessClearPrepDraftBackup() {
                localStorage.removeItem('eess_lesson_prep_draft_' + <?php echo $user_id; ?>);
            }

            // Check and restore draft if exists on modal open
            window.addEventListener('load', function() {
                const savedDraft = localStorage.getItem('eess_lesson_prep_draft_' + <?php echo $user_id; ?>);
                if (savedDraft) {
                    try {
                        const parsed = JSON.parse(savedDraft);
                        if (parsed.title && !document.getElementById('eess_lesson_title').value) {
                            if (confirm('     .       ')) {
                                document.getElementById('eess_lesson_title').value = parsed.title || '';
                                if (document.getElementById('eess_lesson_subject') && parsed.subject) {
                                    document.getElementById('eess_lesson_subject').value = parsed.subject;
                                }
                                document.getElementById('eess_lesson_grade').value = parsed.grade || '';
                                document.getElementById('eess_lesson_section').value = parsed.section || '';
                                document.getElementById('eess_lesson_date').value = parsed.date || '';
                                document.getElementById('eess_objectives').value = parsed.objectives || '';
                                document.getElementById('eess_warmup').value = parsed.warmup || '';
                                document.getElementById('eess_activities').value = parsed.activities || '';
                                document.getElementById('eess_evaluation').value = parsed.evaluation || '';
                                document.getElementById('eess_homework').value = parsed.homework || '';
                                document.getElementById('eess_notes').value = parsed.notes || '';
                            }
                        }
                    } catch(e) {}
                }
            });
        </script>
        <?php endif; ?>

        <!-- List Panel (Compacted & Cleaned Up) -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">

            <!-- Table Header Bar: Compact Single-Row Search + Status Filter + Sort -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; background: #ffffff; padding: 12px 18px; border-radius: 14px; border: 1px solid #cbd5e1; font-family: 'Cairo', sans-serif;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-list-view" style="font-size: 18px; width: 18px; height: 18px; color: #881337;"></span>
                    <span>No       </span>
                </h3>

                <form method="get" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0; flex-grow: 1; justify-content: flex-end; direction: rtl;">
                    <input type="hidden" name="sm_tab" value="lesson-plans">

                    <div style="position: relative; width: 220px; min-width: 160px; max-width: 100%;">
                        <input type="text" name="s_query" value="<?php echo isset($_GET['s_query']) ? esc_attr($_GET['s_query']) : ''; ?>" placeholder="Search   Sport Activity  ..." class="sm-input" style="height: 36px; font-size: 12px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 32px 0 12px; width: 100%; box-sizing: border-box;">
                        <span class="dashicons dashicons-search" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 15px; width: 15px; height: 15px; color: #94a3b8; pointer-events: none;"></span>
                    </div>

                    <?php $cur_sort = isset($_GET['sort_dir']) && $_GET['sort_dir'] === 'asc' ? 'asc' : 'desc'; ?>
                    <input type="hidden" name="sort_dir" id="eess_sort_dir_val" value="<?php echo $cur_sort; ?>">
                    <button type="button" onclick="const sInput = document.getElementById('eess_sort_dir_val'); sInput.value = (sInput.value === 'desc' ? 'asc' : 'desc'); this.form.submit();" title="<?php echo $cur_sort === 'asc' ? ':  No' : ':  No'; ?>" style="height: 36px; padding: 0 14px; border-radius: 9999px !important; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; flex-shrink: 0;" onmouseover="this.style.borderColor='#881337'" onmouseout="this.style.borderColor='#cbd5e1'">
                        <span class="dashicons <?php echo $cur_sort === 'asc' ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'; ?>" style="font-size: 14px; width: 14px; height: 14px; margin: 0; color: #881337;"></span>
                        <span><?php echo $cur_sort === 'asc' ? ' No' : ' No'; ?></span>
                    </button>

                    <button type="submit" class="sm-btn" style="height: 36px; font-size: 12px; padding: 0 18px; background: #881337; border-radius: 9999px !important; color: #ffffff !important; font-weight: 800; border: none; cursor: pointer; white-space: nowrap; flex-shrink: 0; box-shadow: 0 1px 3px rgba(136,19,55,0.2);">Search Filter</button>
                    <?php if (!empty($_GET['s_query']) || (isset($_GET['sort_dir']) && $_GET['sort_dir'] === 'asc')): ?>
                        <a href="<?php echo esc_url(remove_query_arg(array('s_query', 'filter_status', 'sort_dir'))); ?>" class="sm-btn sm-btn-outline" style="height: 36px; font-size: 11.5px; padding: 0 12px; border-radius: 9999px !important; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 700; flex-shrink: 0;" title="Cancel Filter">
                            <span>Cancel</span>
                            <span class="dashicons dashicons-dismiss" style="font-size: 12px; width: 12px; height: 12px; margin: 0;"></span>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <style>
                .eess-table-capsule {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    height: 22px !important;
                    padding: 0 6px !important;
                    border-radius: 10px !important;
                    font-size: 11px !important;
                    font-weight: 600 !important;
                    line-height: 1 !important;
                    white-space: nowrap !important;
                    box-sizing: border-box !important;
                    vertical-align: middle !important;
                }
            </style>
            <!-- Table of Submissions -->
            <div class="sm-table-container" style="overflow-x: auto; border: none !important; box-shadow: none !important; background: transparent !important;">
                <table class="sm-table" style="min-width: 850px; direction: rtl;">
                    <thead style="background: #000000 !important; color: #ffffff !important;">
                        <tr style="background: #000000 !important; color: #ffffff !important;">
                            <th style="width: 40px; text-align: center; vertical-align: middle; color: #ffffff !important; background: #000000 !important;">#</th>
                            <th style="width: 18%; text-align: right; vertical-align: middle; color: #ffffff !important; background: #000000 !important;">  </th>
                            <th style="width: 22%; text-align: right; vertical-align: middle; color: #ffffff !important; background: #000000 !important;">Academy  Training Groups </th>
                            <th style="width: 18%; text-align: right; vertical-align: middle; color: #ffffff !important; background: #000000 !important;"> </th>
                            <th style="width: 10%; text-align: center; vertical-align: middle; color: #ffffff !important; background: #000000 !important;"> </th>
                            <th style="width: 10%; text-align: center; vertical-align: middle; color: #ffffff !important; background: #000000 !important;"> </th>
                            <th style="width: 10%; text-align: center; vertical-align: middle; color: #ffffff !important; background: #000000 !important;"> No</th>
                            <th style="width: 12%; text-align: center; vertical-align: middle; color: #ffffff !important; background: #000000 !important;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT p.*, u.display_name as teacher_name
                                  FROM {$wpdb->prefix}sm_lesson_preps p
                                  JOIN {$wpdb->prefix}users u ON p.teacher_id = u.ID";

                        $conditions = array();
                        $params = array();

                        if (!$can_review) {
                            $conditions[] = "p.teacher_id = %d";
                            $params[] = $user_id;
                        } elseif ($is_hod && !$is_admin && !$is_sys_admin) {
                            $hod_subject = get_user_meta($user_id, 'sm_specialization', true);
                            $hod_scope = EESS_Org_Helper::get_user_scope($user_id);
                            $hod_schools = !empty($hod_scope['schools']) ? $hod_scope['schools'] : array();

                            if (!empty($hod_subject)) {
                                $conditions[] = "p.subject = %s";
                                $params[] = $hod_subject;
                            }

                            if (!empty($hod_schools)) {
                                $placeholders = implode(',', array_fill(0, count($hod_schools), '%d'));
                                $conditions[] = "p.teacher_id IN (SELECT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE school_id IN ($placeholders))";
                                foreach ($hod_schools as $sch_id) {
                                    $params[] = $sch_id;
                                }
                            }
                        }

                        if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                            $conditions[] = "p.lesson_date = %s";
                            $params[] = sanitize_text_field($_GET['filter_date']);
                        }

                        if (isset($_GET['s_query']) && !empty($_GET['s_query'])) {
                            $conditions[] = "(p.title LIKE %s OR u.display_name LIKE %s OR p.subject LIKE %s)";
                            $like_param = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s_query'])) . '%';
                            $params[] = $like_param;
                            $params[] = $like_param;
                            $params[] = $like_param;
                        }

                        if (!empty($conditions)) {
                            $query .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $sort_dir = (isset($_GET['sort_dir']) && $_GET['sort_dir'] === 'asc') ? 'ASC' : 'DESC';
                        $query .= " ORDER BY CASE WHEN p.status IN ('submitted', 'resubmitted', 'pending') THEN 1 ELSE 2 END ASC, COALESCE(p.submission_time, p.updated_at, p.created_at) {$sort_dir}, p.id {$sort_dir}";

                        if (!empty($params)) {
                            $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
                        } else {
                            $submissions = $wpdb->get_results($query);
                        }

                        if (empty($submissions)):
                        ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 25px; font-size: 13px;">No        Filter.</td>
                        </tr>
                        <?php
                        else:
                            // Calculate total records for Chronological Oldest (1) -> Newest (Highest Number) numbering
                            $total_subs_count = count($submissions);
                            $sort_dir_is_asc = (isset($_GET['sort_dir']) && $_GET['sort_dir'] === 'asc');

                            // Pastel Multi-Color Palettes
                            $grade_palette = array(
                                array('bg' => '#f0fdf4', 'text' => '#166534', 'border' => '#bbf7d0'),
                                array('bg' => '#e0f2fe', 'text' => '#0369a1', 'border' => '#bae6fd'),
                                array('bg' => '#fef3c7', 'text' => '#b45309', 'border' => '#fde68a'),
                                array('bg' => '#faf5ff', 'text' => '#6b21a8', 'border' => '#e9d5ff'),
                                array('bg' => '#fdf2f8', 'text' => '#9d174d', 'border' => '#fbcfe8'),
                            );

                            $week_palette = array(
                                1  => array('bg' => '#fef2f2', 'text' => '#881337', 'border' => '#fecdd3'),
                                2  => array('bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#bfdbfe'),
                                3  => array('bg' => '#f0fdf4', 'text' => '#15803d', 'border' => '#bbf7d0'),
                                4  => array('bg' => '#fef3c7', 'text' => '#b45309', 'border' => '#fde68a'),
                                5  => array('bg' => '#faf5ff', 'text' => '#6b21a8', 'border' => '#e9d5ff'),
                                6  => array('bg' => '#fdf2f8', 'text' => '#9d174d', 'border' => '#fbcfe8'),
                                7  => array('bg' => '#f0f9ff', 'text' => '#0369a1', 'border' => '#bae6fd'),
                                8  => array('bg' => '#ecfdf5', 'text' => '#047857', 'border' => '#a7f3d0'),
                                9  => array('bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#ffedd5'),
                                10 => array('bg' => '#f5f3ff', 'text' => '#5b21b6', 'border' => '#ddd6fe'),
                                11 => array('bg' => '#fff1f2', 'text' => '#be123c', 'border' => '#fecdd3'),
                                12 => array('bg' => '#f0fdfa', 'text' => '#0f766e', 'border' => '#99f6e4'),
                                13 => array('bg' => '#fefce8', 'text' => '#a16207', 'border' => '#fef08a'),
                                14 => array('bg' => '#fdf4ff', 'text' => '#86198f', 'border' => '#f5d0fe'),
                                15 => array('bg' => '#f8fafc', 'text' => '#334155', 'border' => '#cbd5e1'),
                                16 => array('bg' => '#e0e7ff', 'text' => '#3730a3', 'border' => '#c7d2fe'),
                            );

                            foreach ($submissions as $index => $sub):
                                // Chronological number calculation (Oldest submission = 1, Newest = Highest)
                                $chrono_num = $sort_dir_is_asc ? ($index + 1) : ($total_subs_count - $index);

                                $parsed_sub_data = !empty($sub->lesson_data) ? json_decode($sub->lesson_data, true) : array();
                                $sub_file_url = $parsed_sub_data['file_url'] ?? '';

                                // User meta & assignments
                                $t_user = get_userdata($sub->teacher_id);
                                $t_roles = $t_user ? (array)$t_user->roles : array();
                                $primary_role = reset($t_roles) ?: 'sm_teacher';
                                $role_labels = array(
                                    'administrator' => ' ',
                                    'sm_system_admin' => ' ',
                                    'sm_principal' => ' ',
                                    'sm_supervisor' => ' ',
                                    'sm_coordinator' => ' ',
                                    'sm_teacher' => '',
                                    'sm_hod' => ' '
                                );
                                $role_lbl = $role_labels[$primary_role] ?? '';
                                $teacher_emp_id = get_user_meta($sub->teacher_id, 'eess_employee_number', true) ?: ('EMP-' . $sub->teacher_id);

                                // School & Grade Assignments
                                $teacher_school = get_user_meta($sub->teacher_id, 'eess_school_name', true);
                                if (empty($teacher_school)) {
                                    $teacher_school = get_user_meta($sub->teacher_id, 'sm_school_name', true) ?: 'Academy  Home';
                                }

                                // Clean Grade Levels parsing: strip brackets [], quotes "", trailing commas, etc.
                                $assigned_grades_raw = get_user_meta($sub->teacher_id, 'sm_assigned_grades', true) ?: (get_user_meta($sub->teacher_id, 'eess_assigned_grades', true) ?: (get_user_meta($sub->teacher_id, 'sm_grade_level', true) ?: $sub->grade_level));
                                if (is_array($assigned_grades_raw)) {
                                    $assigned_grades_raw = implode(',', $assigned_grades_raw);
                                }
                                $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);
                                $assigned_grades_array = array_unique(array_filter(array_map('trim', explode(',', $assigned_grades_clean))));
                                if (empty($assigned_grades_array)) {
                                    $assigned_grades_array = array($sub->grade_level);
                                }
                        ?>
                        <tr style="font-size: 12px; vertical-align: middle;" id="prep-row-<?php echo $sub->id; ?>">
                            <!-- Column 1: # Sequential Chronological Record Number (Oldest = 1 -> Newest = Highest) -->
                            <td style="text-align: center; vertical-align: middle; color: #64748b; font-weight: 700; font-size: 11px; font-family: monospace;">
                                <?php echo $chrono_num; ?>
                            </td>

                            <!-- Column 3: Teacher & Employee Number (Single Row below Name; Removed Years of Experience) -->
                            <td style="vertical-align: middle; text-align: right;">
                                <div style="font-weight: 800; color: #0f172a; font-size: 12.5px; margin-bottom: 3px;">
                                    <a href="javascript:void(0)" onclick="window.eessOpenTeacherProfile(<?php echo $sub->teacher_id; ?>)" style="color: #0f172a; text-decoration: none;" onmouseover="this.style.color='#0284c7';" onmouseout="this.style.color='#0f172a';">
                                        <?php echo esc_html($sub->teacher_name); ?>
                                    </a>
                                </div>
                                <div style="display: flex; gap: 4px; align-items: center; flex-wrap: nowrap;">
                                    <!-- Employee Number Capsule -->
                                    <span class="eess-table-capsule" style="background: #fef2f2; color: #881337; border: 1px solid #fecdd3; font-family: monospace;">
                                        <?php echo esc_html($teacher_emp_id); ?>
                                    </span>
                                    <!-- Role Capsule -->
                                    <span class="eess-table-capsule" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                        <?php echo esc_html($role_lbl); ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Column 4: School & Individual Multi-Color Grade Level Pastel Capsules -->
                            <td style="vertical-align: middle; text-align: right;">
                                <div style="font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 3px; display: flex; align-items: center; gap: 4px;">
                                    <span></span>
                                    <span><?php echo esc_html($teacher_school); ?></span>
                                </div>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap; align-items: center;">
                                    <?php
                                    foreach ($assigned_grades_array as $g_idx => $g_title):
                                        $g_style = $grade_palette[$g_idx % count($grade_palette)];
                                    ?>
                                        <span class="eess-table-capsule" style="background: <?php echo $g_style['bg']; ?>; color: <?php echo $g_style['text']; ?>; border: 1px solid <?php echo $g_style['border']; ?>;">
                                            <?php echo esc_html($g_title); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>

                            <!-- Column 5: Lesson Title Only -->
                            <td style="vertical-align: middle; text-align: right;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 12px;">
                                    <?php echo esc_html($sub->title); ?>
                                </div>
                            </td>

                            <!-- Column 6: Academic Week (Sequential with Distinct Pastel Color per Week) -->
                            <td style="text-align: center; vertical-align: middle;">
                                <?php
                                $sub_created_time = strtotime($sub->created_at ?: $sub->lesson_date);
                                $w_day = date('N', $sub_created_time); // 1 (Mon) .. 5 (Fri) .. 7 (Sun)
                                $w_time = date('H:i', $sub_created_time);

                                // Academic Start Anchor: 30 August 2026 (Academic Week 1)
                                $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
                                if ($sub_created_time >= $acad_anchor_ts) {
                                    $diff_seconds = $sub_created_time - $acad_anchor_ts;
                                    $cycle_week_num = intval(floor($diff_seconds / (7 * 86400))) + 1;
                                } else {
                                    $cycle_week_num = 1;
                                }

                                $arabic_weeks = array(
                                    1 => ' ', 2 => ' ', 3 => ' ', 4 => ' ',
                                    5 => ' ', 6 => ' ', 7 => ' ', 8 => ' ',
                                    9 => ' ', 10 => ' ', 11 => '  ', 12 => '  ',
                                    13 => '  ', 14 => '  ', 15 => '  ', 16 => '  '
                                );
                                $week_title = $arabic_weeks[$cycle_week_num] ?? (' ' . $cycle_week_num);
                                $wk_style = $week_palette[$cycle_week_num] ?? $week_palette[15];

                                // Lateness check
                                $is_cycle_late = false;
                                if ($w_day == 1 && $w_time > '09:30') {
                                    $is_cycle_late = true;
                                } elseif ($w_day >= 2 && $w_day <= 4) {
                                    $is_cycle_late = true;
                                }
                                ?>
                                <span class="eess-table-capsule" style="background: <?php echo $wk_style['bg']; ?>; color: <?php echo $wk_style['text']; ?>; border: 1px solid <?php echo $wk_style['border']; ?>;">
                                    <?php echo esc_html($week_title); ?>
                                </span>
                            </td>

                            <!-- Column 7: Submission Status Only (Date/Time strictly hidden inside Tooltip hover title attribute; Clickable for Edit Modal) -->
                            <td style="text-align: center; vertical-align: middle;">
                                <?php
                                $sub_dt = $sub->submission_time ?: $sub->created_at;
                                $formatted_time = date_i18n('j M Y • h:i A', strtotime($sub_dt));
                                $formatted_time = str_replace(array('AM', 'PM', '', '', '', ''), array('', '', '', '', '', ''), $formatted_time);
                                $can_click_edit = $can_review ? 'onclick="eessOpenEditPrepStatusModal(' . $sub->id . ', \'' . esc_js($sub->title) . '\', \'' . esc_js($sub->status) . '\', \'' . date('Y-m-d\TH:i', strtotime($sub_dt)) . '\')"' : '';
                                ?>
                                <?php if ($sub->delay_seconds > 0 || $is_cycle_late): ?>
                                    <span class="eess-table-capsule" title="  : <?php echo esc_attr($formatted_time); ?>" <?php echo $can_click_edit; ?> style="background: #fef2f2; color: #dc2626; border: 1px solid #fecdd3; cursor: <?php echo $can_review ? 'pointer' : 'default'; ?>;">
                                        ⚠️
                                    </span>
                                <?php else: ?>
                                    <span class="eess-table-capsule" title="  : <?php echo esc_attr($formatted_time); ?>" <?php echo $can_click_edit; ?> style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; cursor: <?php echo $can_review ? 'pointer' : 'default'; ?>;">
                                        ✓
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Column 8: Approval Status -->
                            <td style="text-align: center; vertical-align: middle;">
                                <?php
                                $approver_title = '';
                                if ($sub->status === 'approved') {
                                    if (!empty($sub->reviewed_by)) {
                                        $rev_user = get_userdata($sub->reviewed_by);
                                        if ($rev_user) {
                                            $approver_title = ' No : ' . $rev_user->display_name;
                                        }
                                    }
                                    if (empty($approver_title)) {
                                        $approver_title = '       Academy';
                                    }
                                }

                                $status_labels = array(
                                    'draft' => array('label' => '', 'bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1'),
                                    'submitted' => array('label' => ' ', 'bg' => '#e0f2fe', 'color' => '#0284c7', 'border' => '#bae6fd'),
                                    'approved' => array('label' => '', 'bg' => '#dcfce7', 'color' => '#15803d', 'border' => '#bbf7d0'),
                                    'revision_required' => array('label' => ' Edit', 'bg' => '#ffedd5', 'color' => '#c2410c', 'border' => '#fed7aa'),
                                    'rejected' => array('label' => '', 'bg' => '#fee2e2', 'color' => '#b91c1c', 'border' => '#fecdd3'),
                                    'late' => array('label' => ' ', 'bg' => '#ffedd5', 'color' => '#8b1e1e', 'border' => '#fed7aa'),
                                    'resubmitted' => array('label' => ' ', 'bg' => '#e0f2fe', 'color' => '#0369a1', 'border' => '#bae6fd'),
                                );
                                $badge = $status_labels[$sub->status] ?? array('label' => $sub->status, 'bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1');
                                ?>
                                <span class="eess-table-capsule" <?php if (!empty($approver_title)) echo 'title="' . esc_attr($approver_title) . '"'; ?> style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>; border: 1px solid <?php echo $badge['border']; ?>;">
                                    <?php echo esc_html($badge['label']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="sm-action-btn-group">
                                    <?php if (!empty($sub_file_url)): ?>
                                        <!-- View Uploaded File Button -->
                                        <a href="<?php echo esc_url($sub_file_url); ?>" target="_blank" title="   " class="sm-action-btn sm-action-btn-neutral">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>

                                        <!-- Download Uploaded File Button -->
                                        <a href="<?php echo esc_url($sub_file_url); ?>" download title="Upload    " class="sm-action-btn sm-action-btn-primary">
                                            <span class="dashicons dashicons-download"></span>
                                        </a>
                                    <?php else: ?>
                                        <!-- System Lesson Preview Button -->
                                        <button onclick="smOpenPrepViewer(<?php echo $sub->id; ?>)" class="sm-action-btn sm-action-btn-neutral" title="View   ">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>

                                        <!-- Print PDF Button -->
                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=lesson_prep&prep_id=' . $sub->id); ?>" target="_blank" class="sm-action-btn sm-action-btn-neutral" title="Print  Export  PDF ">
                                            <span class="dashicons dashicons-printer"></span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($can_review && ($sub->status === 'submitted' || $sub->status === 'late' || $sub->status === 'resubmitted')): ?>
                                        <!-- Approve Button -->
                                        <button id="btn-approve-<?php echo $sub->id; ?>" onclick="smQuickApprovePrep(<?php echo $sub->id; ?>)" class="sm-action-btn sm-action-btn-success" title="   ">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                        </button>

                                        <!-- Reject / Return Button -->
                                        <button id="btn-reject-<?php echo $sub->id; ?>" onclick="eessOpenRejectPrepModal(<?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-action-btn sm-action-btn-danger" title="Reject     Edit">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($is_teacher && ($sub->status === 'draft' || $sub->status === 'revision_required')): ?>
                                        <!-- Edit Button -->
                                        <a href="<?php echo add_query_arg('edit_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-action-btn sm-action-btn-warning" title="Edit  ">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($is_admin): ?>
                                        <!-- Copy Record Button (System Administrator Only) -->
                                        <button type="button" onclick="eessOpenCopyRecordModal('lesson_prep', <?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-action-btn sm-action-btn-primary" title="     ">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                    <?php endif; ?>

                                    <!-- WhatsApp Direct Contact Button (Positioned immediately to the left of Delete in RTL) -->
                                    <?php
                                    $t_phone = get_user_meta($sub->teacher_id, 'phone_number', true) ?: (get_user_meta($sub->teacher_id, 'sm_phone', true) ?: (get_user_meta($sub->teacher_id, 'phone', true) ?: ''));
                                    $clean_phone = preg_replace('/[^0-9]/', '', $t_phone);
                                    if (empty($clean_phone) || strlen($clean_phone) < 8) $clean_phone = '971500000000';
                                    $wa_msg = rawurlencode("No   \n      .      .");
                                    $wa_url = "https://wa.me/" . $clean_phone . "?text=" . $wa_msg;
                                    ?>
                                    <a href="<?php echo esc_url($wa_url); ?>" target="_blank" onclick="eessMarkTeacherContacted(<?php echo $sub->teacher_id; ?>, 'prep', <?php echo $sub->id; ?>)" class="sm-action-btn sm-action-btn-success" title="     ">
                                        <span class="dashicons dashicons-whatsapp"></span>
                                    </a>

                                    <!-- Delete Button (Far-Left in RTL) -->
                                    <button onclick="smOpenDeletePrepModal(<?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-action-btn sm-action-btn-danger" title="Delete  ">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Administrative Settings Overlay Modal -->
    <?php if ($is_admin || $is_sys_admin || $is_principal || $is_supervisor): ?>
    <div id="prep-settings-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
        <div class="sm-modal-content" style="background:#fff; max-width: 650px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
            <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <h3 style="margin:0; font-weight:800; color:var(--sm-primary-color); display:flex; align-items:center; gap:8px; font-size: 15px;">
                    <span class="dashicons dashicons-admin-generic" style="font-size: 20px; width: 20px; height: 20px; margin: 0;"></span>

                </h3>
                <button type="button" onclick="document.getElementById('prep-settings-modal').style.display='none'" class="sm-modal-close" style="width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px;">&times;</button>
            </div>
            <div class="sm-modal-body" style="text-align:right;">
                <form method="post">
                    <?php wp_nonce_field('eess_settings_action', 'eess_settings_nonce'); ?>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 20px;">

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">  </label>
                            <select name="submission_frequency" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="daily" <?php selected(($prep_settings['submission_frequency'] ?? 'daily') === 'daily'); ?>>   </option>
                                <option value="weekly" <?php selected(($prep_settings['submission_frequency'] ?? 'daily') === 'weekly'); ?>>   </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;"> Close   </label>
                            <input type="time" name="submission_deadline" value="<?php echo esc_attr($prep_settings['submission_deadline'] ?? '10:00'); ?>" class="sm-input" style="height: 38px; font-size: 12px;">
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">   </label>
                            <select name="pe_monday_only" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="yes" <?php selected(($prep_settings['pe_monday_only'] ?? 'yes') === 'yes'); ?>>Yes -  No   </option>
                                <option value="no" <?php selected(($prep_settings['pe_monday_only'] ?? 'yes') === 'no'); ?>>No -   Sports Activities</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">   (  )</label>
                            <input type="text" name="subject_exceptions" value="<?php echo esc_attr($prep_settings['subject_exceptions'] ?? ''); ?>" class="sm-input" placeholder=":  " style="height: 38px; font-size: 12px;">
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">   Close</label>
                            <select name="reminder_intervals" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="none" <?php selected(($prep_settings['reminder_intervals'] ?? '') === 'none'); ?>> </option>
                                <option value="30min" <?php selected(($prep_settings['reminder_intervals'] ?? '') === '30min'); ?>>  </option>
                                <option value="1hour" <?php selected(($prep_settings['reminder_intervals'] ?? '1hour') === '1hour'); ?>>  </option>
                                <option value="2hours" <?php selected(($prep_settings['reminder_intervals'] ?? '') === '2hours'); ?>> </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">No Edit  ( )</label>
                            <select name="revision_limits" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="0" <?php selected(($prep_settings['revision_limits'] ?? '0') === '0'); ?>> (No  )</option>
                                <option value="1" <?php selected(($prep_settings['revision_limits'] ?? '') === '1'); ?>>   </option>
                                <option value="2" <?php selected(($prep_settings['revision_limits'] ?? '') === '2'); ?>>  </option>
                                <option value="3" <?php selected(($prep_settings['revision_limits'] ?? '') === '3'); ?>>3   </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">  No </label>
                            <select name="approval_workflow" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="single" <?php selected(($prep_settings['approval_workflow'] ?? 'single') === 'single'); ?>>   ( )</option>
                                <option value="multi" <?php selected(($prep_settings['approval_workflow'] ?? '') === 'multi'); ?>>   (  )</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">   No</label>
                            <select name="template_mgmt" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="default" <?php selected(($prep_settings['template_mgmt'] ?? 'default') === 'default'); ?>>   (6 )</option>
                                <option value="compact" <?php selected(($prep_settings['template_mgmt'] ?? '') === 'compact'); ?>>  </option>
                                <option value="detailed" <?php selected(($prep_settings['template_mgmt'] ?? '') === 'detailed'); ?>> Advanced   </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">Update    Close</label>
                            <select name="auto_status_updates" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="yes" <?php selected(($prep_settings['auto_status_updates'] ?? 'yes') === 'yes'); ?>>Yes -     Close</option>
                                <option value="no" <?php selected(($prep_settings['auto_status_updates'] ?? 'yes') === 'no'); ?>>No -  Status   </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">Actions   </label>
                            <select name="late_submission_rules" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="flag" <?php selected(($prep_settings['late_submission_rules'] ?? 'flag') === 'flag'); ?>> No   </option>
                                <option value="deduct" <?php selected(($prep_settings['late_submission_rules'] ?? '') === 'deduct'); ?>> No    </option>
                                <option value="block" <?php selected(($prep_settings['late_submission_rules'] ?? '') === 'block'); ?>>    </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">    </label>
                            <select name="calendar_integration" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="no" <?php selected(($prep_settings['calendar_integration'] ?? 'no') === 'no'); ?>> </option>
                                <option value="yes" <?php selected(($prep_settings['calendar_integration'] ?? 'no') === 'yes'); ?>>   No  </option>
                            </select>
                        </div>

                        <!-- Notification Preferences Checkboxes -->
                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;"> No   </label>
                            <div style="display:flex; flex-direction: column; gap:5px; background:#f8fafc; padding:8px; border-radius:6px; border:1px solid #cbd5e1; font-size: 11px;">
                                <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="notification_prefs[]" value="email" <?php checked(in_array('email', $prep_settings['notification_prefs'] ?? array())); ?>>
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="notification_prefs[]" value="system" <?php checked(in_array('system', $prep_settings['notification_prefs'] ?? array())); ?>>
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="notification_prefs[]" value="whatsapp" <?php checked(in_array('whatsapp', $prep_settings['notification_prefs'] ?? array())); ?>>
                                </label>
                            </div>
                        </div>

                        <div style="grid-column: span 2;">
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">    </label>
                            <div style="display:flex; gap:12px; flex-wrap:wrap; background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                                <?php
                                $days_list = array('sun' => '', 'mon' => 'No', 'tue' => 'No', 'wed' => '', 'thu' => '');
                                foreach ($days_list as $key => $lbl): ?>
                                    <label style="font-size:11px; display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                        <input type="checkbox" name="working_days[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $prep_settings['working_days'] ?? array())); ?>> <?php echo $lbl; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="submit" name="eess_save_prep_settings" class="sm-btn" style="width: auto; background: var(--sm-primary-color); height: 36px; padding: 0 20px; font-weight: bold; font-size: 12px;">Save   Settings</button>
                        <button type="button" onclick="document.getElementById('prep-settings-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width: auto; height: 36px; padding: 0 15px; font-size: 12px;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Rejection / Revision Notes Modal -->
<div id="eess-reject-prep-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #fecdd3; box-shadow: 0 25px 50px -12px rgba(220,38,38,0.25); overflow: hidden;">
        <div style="background: #dc2626; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-no-alt" style="font-size: 22px; width: 22px; height: 22px; color: #ffffff;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;" id="reject_prep_modal_title"> Edit / Reject </h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-reject-prep-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form onsubmit="eessSubmitRejectPrep(event)" style="padding: 24px;">
            <input type="hidden" id="reject_prep_id" value="0">
            <div style="margin-bottom: 16px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;"> Reject / Notes Edit   <span style="color:#ef4444;">*</span></label>
                <textarea id="reject_prep_notes" required rows="4" class="sm-input" placeholder=" Notes      ..." style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px; font-size: 12.5px; box-sizing: border-box;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" id="reject_prep_submit_btn" class="sm-btn" style="background: #dc2626; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">Send Notes Reject</button>
                <button type="button" onclick="document.getElementById('eess-reject-prep-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Document Viewer Modal -->
<div id="prep-viewer-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
    <div class="sm-modal-content" style="background:#fff; max-width: 750px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 id="view-modal-title" style="margin:0; font-weight:800; color:var(--sm-primary-color); font-size: 15px;"> </h3>
            <button onclick="document.getElementById('prep-viewer-modal').style.display='none'" class="sm-modal-close" style="width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px;">&times;</button>
        </div>
        <div class="sm-modal-body" id="prep-viewer-body" style="line-height: 1.6; font-size:13px; text-align:right;">
            <!-- Rendered dynamically -->
        </div>
    </div>
</div>

<!-- Modal for Editing Lesson Prep Submission Status & Date/Time (Authorized Reviewers Only) -->
<div id="eess-edit-prep-status-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 480px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #0f172a; color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-edit" style="font-size: 20px; width: 20px; height: 20px; color: #38bdf8;"></span>
                <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #ffffff;" id="eess_edit_prep_modal_title">Edit   </h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-edit-prep-status-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form onsubmit="eessSubmitEditPrepStatus(event)" style="padding: 20px;">
            <input type="hidden" id="eess_edit_prep_id" value="0">

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">  No  <span style="color:#ef4444;">*</span></label>
                <select id="eess_edit_prep_status" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; font-weight: 700;" required>
                    <option value="submitted">✓   ( )</option>
                    <option value="late">⚠️  </option>
                    <option value="approved">✓  </option>
                    <option value="revision_required">⚠  Edit</option>
                    <option value="rejected">✗ </option>
                    <option value="draft"></option>
                </select>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">Edit     <span style="color:#ef4444;">*</span></label>
                <input type="datetime-local" id="eess_edit_prep_datetime" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;" required>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="submit" id="eess_edit_prep_submit_btn" class="sm-btn" style="background: #0f172a; color: #ffffff !important; height: 36px; padding: 0 20px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">Save Edit</button>
                <button type="button" onclick="document.getElementById('eess-edit-prep-status-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 36px; padding: 0 16px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function eessOpenEditPrepStatusModal(prepId, title, currentStatus, currentDatetime) {
    document.getElementById('eess_edit_prep_id').value = prepId;
    document.getElementById('eess_edit_prep_modal_title').innerText = 'Edit  : ' + title;
    document.getElementById('eess_edit_prep_status').value = currentStatus || 'submitted';
    if (currentDatetime) {
        document.getElementById('eess_edit_prep_datetime').value = currentDatetime;
    }
    document.getElementById('eess-edit-prep-status-modal').style.display = 'flex';
}

function eessSubmitEditPrepStatus(e) {
    e.preventDefault();
    var prepId = document.getElementById('eess_edit_prep_id').value;
    var newStatus = document.getElementById('eess_edit_prep_status').value;
    var newDatetime = document.getElementById('eess_edit_prep_datetime').value;

    if (!prepId) return;

    var btn = document.getElementById('eess_edit_prep_submit_btn');
    btn.disabled = true;
    btn.innerText = ' Save...';

    var formData = new FormData();
    formData.append('action', 'sm_update_prep_status_and_time');
    formData.append('prep_id', prepId);
    formData.append('status', newStatus);
    formData.append('submission_time', newDatetime);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'Save Edit';
        if (res.success) {
            document.getElementById('eess-edit-prep-status-modal').style.display = 'none';
            if (typeof smShowNotification === 'function') {
                smShowNotification(res.data.message || ' Update     .');
            }
            setTimeout(() => location.reload(), 500);
        } else {
            alert(': ' + (res.data || ' Update .'));
        }
    });
}
</script>

<!-- Lesson Preparation Bulk Download Modal -->
<div id="eess-prep-bulk-download-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; direction: rtl; font-family: 'Cairo', sans-serif;">
    <div style="background: #ffffff; width: 100%; max-width: 520px; border-radius: 20px; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column;">
        <!-- Minimal White Header -->
        <div style="background: #ffffff; color: #0f172a; padding: 20px 24px 12px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <span class="dashicons dashicons-download" style="font-size: 22px; width: 22px; height: 22px; color: #0f172a; margin-top: 2px;"></span>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">Upload        </h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b; font-weight: 500;">     Download   .</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('eess-prep-bulk-download-modal').style.display='none'" style="background: none; border: none; color: #0f172a; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form id="eess-prep-bulk-form" onsubmit="eessExecutePrepBulkDownloadInModal(event)" style="padding: 24px;">
            <input type="hidden" name="action" value="sm_bulk_download_lesson_preps">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('eess_admin_action'); ?>">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">  Download :</label>
                <select name="scope_type" id="eess_prep_scope_type" onchange="eessUpdatePrepScopeFields(this.value)" class="sm-select" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
                    <option value="all">   </option>
                    <option value="week">  </option>
                    <option value="month">  </option>
                    <option value="date">  </option>
                    <option value="range">   ( - )</option>
                </select>
            </div>

            <!-- Scope Field: Week -->
            <div id="eess-prep-scope-week" style="display: none; margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">   (: 30  2026):</label>
                <select name="week_num" class="sm-select" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
                    <option value="0">  (   )</option>
                    <?php
                    $arabic_weeks_list = array(
                        1 => ' ', 2 => ' ', 3 => ' ', 4 => ' ',
                        5 => ' ', 6 => ' ', 7 => ' ', 8 => ' ',
                        9 => ' ', 10 => ' ', 11 => '  ', 12 => '  ',
                        13 => '  ', 14 => '  ', 15 => '  ', 16 => '  '
                    );
                    foreach ($arabic_weeks_list as $w_num => $w_title): ?>
                        <option value="<?php echo $w_num; ?>"><?php echo esc_html($w_title . ' (Week ' . $w_num . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Scope Field: Month -->
            <div id="eess-prep-scope-month" style="display: none; margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"> :</label>
                <input type="month" name="month_val" value="<?php echo date('Y-m'); ?>" class="sm-input" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
            </div>

            <!-- Scope Field: Specific Date -->
            <div id="eess-prep-scope-date" style="display: none; margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">  :</label>
                <input type="date" name="date_val" value="<?php echo date('Y-m-d'); ?>" class="sm-input" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
            </div>

            <!-- Scope Field: Date Range -->
            <div id="eess-prep-scope-range" style="display: none; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"> :</label>
                        <input type="date" name="date_from" value="<?php echo date('Y-m-01'); ?>" class="sm-input" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"> :</label>
                        <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>" class="sm-input" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; width: 100%;">
                    </div>
                </div>
            </div>

            <!-- Inline Loading State Indicator -->
            <div id="eess_prep_bulk_loading_status" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px; margin-bottom: 16px; text-align: center;">
                <div style="font-size: 13px; font-weight: 800; color: #166534; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="dashicons dashicons-update spin" style="font-size: 18px; width: 18px; height: 18px;"></span>
                    <span id="eess_prep_bulk_loading_text">    ...</span>
                </div>
                <div style="font-size: 11.5px; color: #15803d; font-weight: 600; margin-top: 4px;">Please wait...    Close...</div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('eess-prep-bulk-download-modal').style.display='none'" class="sm-btn" style="background: #f1f5f9; color: #475569; height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer;">Cancel</button>
                <button type="submit" id="eess_prep_bulk_submit_btn" class="sm-btn" style="background: #0f172a; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 9999px !important; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                    <span> Upload  (ZIP)</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function eessExecutePrepBulkDownloadInModal(e) {
    e.preventDefault();
    var form = document.getElementById('eess-prep-bulk-form');
    var btn = document.getElementById('eess_prep_bulk_submit_btn');
    var loadingBox = document.getElementById('eess_prep_bulk_loading_status');
    var loadingText = document.getElementById('eess_prep_bulk_loading_text');

    btn.disabled = true;
    loadingBox.style.display = 'block';
    loadingText.innerText = ' Search   ...';

    setTimeout(() => { loadingText.innerText = '     ZIP ...'; }, 1200);

    var params = new URLSearchParams(new FormData(form)).toString();
    var fetchUrl = '<?php echo admin_url("admin-ajax.php"); ?>?' + params;

    fetch(fetchUrl)
    .then(response => {
        var contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            return response.json().then(data => {
                throw new Error(data.data || 'An error occurred  .');
            });
        }
        if (!response.ok) throw new Error('  ');
        return response.blob();
    })
    .then(blob => {
        loadingText.innerText = '  Download ...';
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'Lesson_Preps_Archive_' + new Date().toISOString().slice(0, 10) + '.zip';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        setTimeout(() => {
            btn.disabled = false;
            loadingBox.style.display = 'none';
            document.getElementById('eess-prep-bulk-download-modal').style.display = 'none';
        }, 800);
    })
    .catch(err => {
        btn.disabled = false;
        loadingBox.style.display = 'none';
        alert(err.message || 'An error occurred  Download  .');
    });
}
</script>

<script>
function eessUpdatePrepScopeFields(scope) {
    document.getElementById('eess-prep-scope-week').style.display = (scope === 'week') ? 'block' : 'none';
    document.getElementById('eess-prep-scope-month').style.display = (scope === 'month') ? 'block' : 'none';
    document.getElementById('eess-prep-scope-date').style.display = (scope === 'date') ? 'block' : 'none';
    document.getElementById('eess-prep-scope-range').style.display = (scope === 'range') ? 'block' : 'none';
}
</script>

<!-- School-Specific Lesson Prep Report Modal -->
<div id="eess-school-prep-report-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden; display: flex; flex-direction: column;">
        <!-- Clean Minimal White Header -->
        <div style="background: #ffffff; color: #0f172a; padding: 20px 24px 14px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <span class="dashicons dashicons-building" style="font-size: 22px; width: 22px; height: 22px; color: #0f172a; margin-top: 2px;"></span>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">   —      </h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b; font-weight: 500;"> Print      Academy   .</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('eess-school-prep-report-modal').style.display='none'" style="background: none; border: none; color: #0f172a; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 24px;">
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;"> Academy  / Organization    <span style="color:#ef4444;">*</span></label>
                <select id="eess_target_school_prep" onchange="eessFetchSchoolPrepWeeks()" class="sm-input" style="height: 34px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-weight: 700;">
                    <?php
                    $all_schools_list = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_all_schools() : array();
                    if (!empty($all_schools_list)):
                        foreach ($all_schools_list as $sch): ?>
                            <option value="<?php echo esc_attr($sch->id); ?>"><?php echo esc_html($sch->name); ?></option>
                        <?php endforeach;
                    else: ?>
                        <option value="1">Academy  Home</option>
                    <?php endif; ?>
                </select>
            </div>
            <div style="margin-bottom: 18px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">   (  )</label>
                <select id="eess_target_week_prep" class="sm-input" style="height: 34px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-weight: 700;">
                    <option value="0">  (   )</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('eess-school-prep-report-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 8px; border: 1px solid #cbd5e1; color: #475569; cursor: pointer; font-weight: 700;">Cancel</button>
                <button type="button" onclick="eessGenerateSchoolPrepReport()" class="sm-btn" style="background: #0f172a; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                    <span>Print   A4</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function eessOpenSchoolPrepReportModal() {
    document.getElementById('eess-school-prep-report-modal').style.display = 'flex';
    eessFetchSchoolPrepWeeks();
}

function eessFetchSchoolPrepWeeks() {
    var schoolSelect = document.getElementById('eess_target_school_prep');
    var weekSelect = document.getElementById('eess_target_week_prep');
    if (!schoolSelect || !weekSelect) return;

    var schoolId = schoolSelect.value;
    if (!schoolId) return;

    weekSelect.disabled = true;
    weekSelect.innerHTML = '<option value="0"> Upload  ...</option>';

    var nonce = '<?php echo wp_create_nonce("eess_admin_action"); ?>';
    var url = '<?php echo admin_url("admin-ajax.php"); ?>?action=sm_get_school_prep_weeks&school_id=' + encodeURIComponent(schoolId) + '&nonce=' + encodeURIComponent(nonce);

    fetch(url)
    .then(r => r.json())
    .then(res => {
        weekSelect.disabled = false;
        weekSelect.innerHTML = '<option value="0">  (   )</option>';
        if (res.success && res.data && res.data.weeks && res.data.weeks.length > 0) {
            res.data.weeks.forEach(function(w) {
                var opt = document.createElement('option');
                opt.value = w.week_num;
                opt.textContent = w.week_name;
                weekSelect.appendChild(opt);
            });
        }
    })
    .catch(err => {
        weekSelect.disabled = false;
        weekSelect.innerHTML = '<option value="0">  (   )</option>';
    });
}

function eessGenerateSchoolPrepReport() {
    var schId = document.getElementById('eess_target_school_prep').value;
    var wkNum = document.getElementById('eess_target_week_prep') ? document.getElementById('eess_target_week_prep').value : '0';
    if (!schId) return;
    document.getElementById('eess-school-prep-report-modal').style.display = 'none';
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=school_lesson_prep_report&school_id='); ?>' + encodeURIComponent(schId) + '&week_num=' + encodeURIComponent(wkNum), '_blank');
}
</script>

<?php include_once SM_PLUGIN_DIR . 'templates/partials/unified-user-modal.php'; ?>
<?php include_once SM_PLUGIN_DIR . 'templates/partials/teacher-profile-readonly-modal.php'; ?>

<!-- Assign Lesson Prep Modal (System Administrator Only) -->
<div id="eess-assign-prep-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
        <!-- Minimal White Header -->
        <div style="background: #ffffff; color: #0f172a; padding: 20px 24px 12px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <span class="dashicons dashicons-user-freelance" style="font-size: 22px; width: 22px; height: 22px; color: #0f172a; margin-top: 2px;"></span>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">    </h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b; font-weight: 500;">       No   Sport Activity Training Groups.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('eess-assign-prep-modal').style.display='none'" style="background: none; border: none; color: #0f172a; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form id="eess-assign-prep-form" onsubmit="eessSubmitAssignPrepForm(event)" style="padding: 24px;">
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">   <span style="color:#ef4444;">*</span></label>
                <?php
                $teachers_list = get_users(array('role' => 'sm_teacher', 'orderby' => 'display_name', 'order' => 'ASC'));
                $teacher_assignments_map = array();
                foreach ($teachers_list as $t) {
                    $t_subj = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: '  '));
                    $t_grade = get_user_meta($t->ID, 'sm_assigned_grades', true) ?: (get_user_meta($t->ID, 'eess_assigned_grades', true) ?: (get_user_meta($t->ID, 'sm_grade_level', true) ?: 'Training Group '));
                    if (is_array($t_grade)) $t_grade = implode(',', $t_grade);
                    $t_grade_clean = str_replace(array('[', ']', '"', "'"), '', (string)$t_grade);
                    $teacher_assignments_map[$t->ID] = array('subject' => $t_subj, 'grade' => $t_grade_clean);
                }
                ?>
                <select id="assign_prep_teacher_id" onchange="eessAutoFillTeacherPrepAssignment(this.value)" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; font-weight: 700;" required>
                    <option value="">--   --</option>
                    <?php foreach ($teachers_list as $t): ?>
                        <option value="<?php echo $t->ID; ?>"><?php echo esc_html($t->display_name . ' (' . $t->user_login . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Auto-retrieved Teacher Metadata Display Capsule -->
            <div id="assign_prep_auto_meta_badge" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; font-size: 12px; color: #334155;">
                <div> <strong>Sport Activity :</strong> <span id="assign_prep_auto_subj" style="color: #0284c7; font-weight: 800;">---</span></div>
                <div style="margin-top: 4px;"> <strong>Training Group :</strong> <span id="assign_prep_auto_grade" style="color: #166534; font-weight: 800;">---</span></div>
            </div>

            <input type="hidden" id="assign_prep_subject" value="">
            <input type="hidden" id="assign_prep_grade" value="">

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">  <span style="color:#ef4444;">*</span></label>
                <input type="text" id="assign_prep_title" placeholder=":     " class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12px;" required>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">  <span style="color:#ef4444;">*</span></label>
                <input type="date" id="assign_prep_date" value="<?php echo current_time('Y-m-d'); ?>" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12px;" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">  (PDF / Word)</label>
                <input type="file" id="assign_prep_file" accept=".pdf,.doc,.docx" style="width: 100%; font-size: 12px;">
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" id="assign_prep_submit_btn" class="sm-btn" style="background: #0f172a; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">  </button>
                <button type="button" onclick="document.getElementById('eess-assign-prep-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var eessTeacherPrepMap = <?php echo json_encode($teacher_assignments_map); ?>;

function eessAutoFillTeacherPrepAssignment(tUid) {
    var badge = document.getElementById('assign_prep_auto_meta_badge');
    if (tUid && eessTeacherPrepMap[tUid]) {
        var info = eessTeacherPrepMap[tUid];
        document.getElementById('assign_prep_subject').value = info.subject || ' ';
        document.getElementById('assign_prep_grade').value = info.grade || 'Training Group ';

        document.getElementById('assign_prep_auto_subj').innerText = info.subject || ' ';
        document.getElementById('assign_prep_auto_grade').innerText = info.grade || 'Training Group ';
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
        document.getElementById('assign_prep_subject').value = '';
        document.getElementById('assign_prep_grade').value = '';
    }
}
</script>

<script>
function eessMarkTeacherContacted(teacherId, recordType, recordId) {
    var formData = new FormData();
    formData.append('action', 'sm_mark_teacher_contacted');
    formData.append('teacher_id', teacherId);
    formData.append('record_type', recordType);
    formData.append('record_id', recordId);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    jQuery.ajax({
        url: '<?php echo esc_url(admin_url("admin-ajax.php")); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.success) {
                if (typeof eessShowMobileToast === 'function') {
                    eessShowMobileToast('✓ ' + res.data.message, 'success');
                }
            }
        }
    });
}

function eessSubmitAssignPrepForm(e) {
    e.preventDefault();
    var tUid = document.getElementById('assign_prep_teacher_id').value;
    var title = document.getElementById('assign_prep_title').value;
    var subj = document.getElementById('assign_prep_subject').value;
    var grade = document.getElementById('assign_prep_grade').value;
    var lDate = document.getElementById('assign_prep_date').value;
    var fileInput = document.getElementById('assign_prep_file');

    if (!tUid || !title) {
        alert('     .');
        return;
    }

    var btn = document.getElementById('assign_prep_submit_btn');
    btn.disabled = true;
    btn.innerText = ' ...';

    var formData = new FormData();
    formData.append('action', 'sm_assign_lesson_prep');
    formData.append('target_user_id', tUid);
    formData.append('title', title);
    formData.append('subject', subj);
    formData.append('grade_level', grade);
    formData.append('lesson_date', lDate);
    if (fileInput.files[0]) {
        formData.append('prep_file', fileInput.files[0]);
    }
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = '  ';
        if (res.success) {
            if (typeof smShowNotification === 'function') smShowNotification(res.data.message || '  ');
            document.getElementById('eess-assign-prep-modal').style.display = 'none';
            setTimeout(() => location.reload(), 600);
        } else {
            alert(': ' + (res.data || '  .'));
        }
    });
}
</script>

<!-- In-System Custom Confirmation Modal for Deleting Lesson Prep -->
<div id="eess-delete-prep-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div class="sm-modal-content" style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; display: flex; flex-direction: column; padding: 28px; text-align: center;">
        <div style="width: 56px; height: 56px; background: #fef2f2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #dc2626; margin: 0 auto 16px auto; border: 1px solid #fecdd3;">
            <span class="dashicons dashicons-trash" style="font-size: 28px; width: 28px; height: 28px;"></span>
        </div>

        <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 800; color: #0f172a;">Confirm Delete   </h3>
        <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; line-height: 1.5;">      Delete   Next  No     .</p>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 24px; font-weight: 700; font-size: 12px; color: #334155;" id="eess_delete_prep_title_display">
            <!-- Title filled dynamically -->
        </div>
        <input type="hidden" id="eess_delete_prep_target_id" value="0">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <button type="button" id="eess-btn-confirm-delete-prep" onclick="eessExecuteConfirmDeletePrep()" class="sm-btn" style="height: 40px; border-radius: 9999px !important; font-size: 13px; background: #dc2626; color: #ffffff !important; font-weight: 800; border: none; cursor: pointer;">Confirm Delete</button>
            <button type="button" onclick="document.getElementById('eess-delete-prep-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 40px; border-radius: 9999px !important; font-size: 13px; color: #475569; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff;">Cancel</button>
        </div>
    </div>
</div>

<!-- Supervisor Review Action Modal -->
<div id="prep-review-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
    <div class="sm-modal-content" style="background:#fff; max-width: 550px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 style="margin:0; font-weight:800; font-size: 15px;">   </h3>
            <button onclick="document.getElementById('prep-review-modal').style.display='none'" class="sm-modal-close" style="width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px;">&times;</button>
        </div>
        <div class="sm-modal-body" style="text-align:right;">
            <form method="post">
                <?php wp_nonce_field('eess_supervisor_action_nonce', 'eess_supervisor_nonce'); ?>
                <input type="hidden" name="prep_id" id="review-prep-id">

                <div style="margin-bottom: 12px;">
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">  </label>
                    <input type="text" id="review-prep-title" class="sm-input" readonly style="background:#f1f5f9; color:#475569; height: 38px; font-size: 12px;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">  No</label>
                    <select name="prep_status_action" class="sm-select" required style="height: 38px; font-size: 12px;">
                        <option value="approved">✓    ()</option>
                        <option value="revision_required">⚠   Edit (Edit )</option>
                        <option value="rejected">✗ Reject Cancel   ()</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">Notes   </label>
                    <textarea name="supervisor_comment" class="sm-input" style="height: 80px; font-size: 12px;" placeholder="    ..."></textarea>
                </div>

                <button type="submit" name="eess_supervisor_action" class="sm-btn" style="background:#16a34a; width: 100%; height: 38px; font-weight: bold; font-size: 13px;">  Save Notes</button>
            </form>
        </div>
    </div>
</div>

<script>
let eessActivePrepStage = 1;

function eessGoToPrepStage(stageNum) {
    const method = document.getElementById('eess_prep_method') ? document.getElementById('eess_prep_method').value : 'create';
    const totalStages = (method === 'upload') ? 3 : 4;

    if (stageNum < 1 || stageNum > totalStages) return;

    // Validation before advancing
    if (stageNum > eessActivePrepStage) {
        if (method === 'upload') {
            if (eessActivePrepStage === 1) {
                const upTitle = document.getElementById('eess_upload_lesson_title').value.trim();
                if (!upTitle) {
                    alert('     .');
                    return;
                }
            } else if (eessActivePrepStage === 2) {
                const docFile = document.getElementById('eess_prep_document_file');
                const hasExistingFile = document.getElementById('eess_prep_file_status_preview') && document.getElementById('eess_prep_file_status_preview').style.display !== 'none';
                if ((!docFile || !docFile.files || docFile.files.length === 0) && !hasExistingFile) {
                    alert('     (PDF  Word) Confirm   .');
                    return;
                }
            }
        } else {
            // Create Method Character Bounds Validation
            if (eessActivePrepStage === 1) {
                const title = document.getElementById('eess_lesson_title').value.trim();
                const obj = document.getElementById('eess_objectives').value.trim();
                if (!title) {
                    alert('    .');
                    return;
                }
                if (obj.length < 150 || obj.length > 350) {
                    alert('      150  350  (: ' + obj.length + ' ).');
                    return;
                }
            } else if (eessActivePrepStage === 2) {
                const warmup = document.getElementById('eess_warmup').value.trim();
                const phys = document.getElementById('eess_physical_prep').value.trim();
                const skill = document.getElementById('eess_skill_prep').value.trim();
                const conc = document.getElementById('eess_conclusion').value.trim();

                if (warmup.length < 150 || warmup.length > 350) {
                    alert('       150  350  (: ' + warmup.length + ' ).');
                    return;
                }
                if (phys.length < 150 || phys.length > 400) {
                    alert('        150  400  (: ' + phys.length + ' ).');
                    return;
                }
                if (skill.length < 150 || skill.length > 400) {
                    alert('       150  400  (: ' + skill.length + ' ).');
                    return;
                }
                if (conc.length < 150 || conc.length > 350) {
                    alert('      150  350  (: ' + conc.length + ' ).');
                    return;
                }
            } else if (eessActivePrepStage === 3) {
                const natAgenda = document.getElementById('eess_national_agenda').value.trim();
                const crossSubj = document.getElementById('eess_cross_subject').value.trim();
                if (!natAgenda || !crossSubj) {
                    alert('       Sports Activities  (*).');
                    return;
                }
            }
        }
    }

    eessActivePrepStage = stageNum;

    // Toggle stages based on workflow method
    if (method === 'upload') {
        document.querySelectorAll('.eess-prep-upload-stage').forEach((el, idx) => {
            el.style.display = (idx + 1 === stageNum) ? 'block' : 'none';
        });
    } else {
        document.querySelectorAll('.eess-prep-create-stage').forEach((el, idx) => {
            el.style.display = (idx + 1 === stageNum) ? 'block' : 'none';
        });
    }

    // Update progress indicator styling
    document.querySelectorAll('.eess-prep-step-indicator').forEach((el, idx) => {
        const stepNum = idx + 1;
        const numSpan = document.getElementById('eess-prep-num-' + stepNum);
        if (stepNum > totalStages) {
            el.style.display = 'none';
            return;
        }
        el.style.display = 'flex';

        if (stepNum === stageNum) {
            el.style.color = '#881337';
            el.classList.add('active');
            if (numSpan) { numSpan.style.background = '#881337'; numSpan.style.color = 'white'; numSpan.innerText = stepNum; }
        } else if (stepNum < stageNum) {
            el.style.color = '#15803d';
            el.classList.remove('active');
            if (numSpan) { numSpan.style.background = '#dcfce7'; numSpan.style.color = '#15803d'; numSpan.innerText = '✓'; }
        } else {
            el.style.color = '#94a3b8';
            el.classList.remove('active');
            if (numSpan) { numSpan.style.background = '#e2e8f0'; numSpan.style.color = '#475569'; numSpan.innerText = stepNum; }
        }
    });

    // Toggle Back/Next/Submit button displays
    const prevBtn = document.getElementById('eess-prep-prev-btn');
    const nextBtn = document.getElementById('eess-prep-next-btn');
    const submitBtn = document.getElementById('eess-prep-submit-btn');
    const draftBtn = document.getElementById('eess-prep-draft-btn');

    if (prevBtn) prevBtn.style.display = (stageNum > 1) ? 'inline-flex' : 'none';

    if (stageNum === totalStages) {
        if (nextBtn) nextBtn.style.display = 'none';
        if (submitBtn) submitBtn.style.display = 'inline-flex';
        if (draftBtn) draftBtn.style.display = 'inline-flex';

        if (method === 'upload') {
            const summaryEl = document.getElementById('eess-prep-upload-review-summary');
            if (summaryEl) {
                const titleVal = document.getElementById('eess_upload_lesson_title').value;
                const dateVal = document.getElementById('eess_upload_lesson_date').value;
                const subjVal = document.getElementById('eess_lesson_subject').value;
                const docFile = document.getElementById('eess_prep_document_file');
                const fileName = (docFile && docFile.files && docFile.files[0]) ? docFile.files[0].name : '  ';

                summaryEl.innerHTML = `
                    <div style="font-size:13.5px; font-weight:800; color:#0f172a; margin-bottom:12px; border-bottom:1px solid #cbd5e1; padding-bottom:8px;"> Send    ( ):</div>
                    <div style="margin-bottom:8px;"> <strong> :</strong> ${titleVal}</div>
                    <div style="margin-bottom:8px;"> <strong>Sport Activity :</strong> ${subjVal} — ${dateVal}</div>
                    <div style="margin-bottom:8px;"> <strong> :</strong> <span style="color:#0284c7; font-weight:800;">${fileName}</span></div>
                    <div style="color:#16a34a; font-weight:800; margin-top:14px; background:#f0fdf4; padding:10px 14px; border-radius:8px; border:1px solid #bbf7d0;">✓      Send   No .</div>
                `;
            }
        } else {
            const summaryEl = document.getElementById('eess-prep-create-review-summary');
            if (summaryEl) {
                const titleVal = document.getElementById('eess_lesson_title').value;
                const dateVal = document.getElementById('eess_lesson_date').value;
                const subjVal = document.getElementById('eess_lesson_subject').value;

                summaryEl.innerHTML = `
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; border-bottom:1px solid #cbd5e1; padding-bottom:10px; margin-bottom:12px;">
                        <div> <strong> :</strong> ${titleVal}</div>
                        <div> <strong>Sport Activity :</strong> ${subjVal} — ${dateVal}</div>
                    </div>
                    <div style="margin-bottom:10px;"><strong> 1.  :</strong><p style="margin:4px 0 0 0; color:#334155; background:#fff; padding:8px; border-radius:6px; border:1px solid #e2e8f0;">${document.getElementById('eess_objectives').value.replace(/\n/g, '<br>')}</p></div>
                    <div style="margin-bottom:10px;"><strong> 2.   :</strong>
                        <ul style="margin:4px 0 0 18px; color:#334155; font-size:12px; line-height:1.5;">
                            <li><strong>:</strong> ${document.getElementById('eess_warmup').value}</li>
                            <li><strong> :</strong> ${document.getElementById('eess_physical_prep').value}</li>
                            <li><strong> :</strong> ${document.getElementById('eess_skill_prep').value}</li>
                            <li><strong> :</strong> ${document.getElementById('eess_conclusion').value}</li>
                        </ul>
                    </div>
                    <div><strong> 3.   :</strong>
                        <p style="margin:4px 0 0 0; color:#334155; font-size:12px;">• <strong> :</strong> ${document.getElementById('eess_national_agenda').value}</p>
                        <p style="margin:2px 0 0 0; color:#334155; font-size:12px;">• <strong>Sports Activities :</strong> ${document.getElementById('eess_cross_subject').value}</p>
                    </div>
                `;
            }
        }
    } else {
        if (nextBtn) nextBtn.style.display = 'inline-flex';
        if (submitBtn) submitBtn.style.display = 'none';
        if (draftBtn) draftBtn.style.display = 'none';
    }
}

const eessSubmissions = <?php
    $preps_for_js = array();
    if (!empty($submissions)) {
        $prep_ids = array_map(function($s) { return $s->id; }, $submissions);
        $comments_map = array();
        if (!empty($prep_ids)) {
            $p_placeholders = implode(',', array_fill(0, count($prep_ids), '%d'));
            $all_comments = $wpdb->get_results($wpdb->prepare("SELECT c.*, u.display_name FROM {$wpdb->prefix}sm_lesson_comments c JOIN {$wpdb->users} u ON c.user_id = u.ID WHERE c.prep_id IN ($p_placeholders) ORDER BY c.created_at ASC", ...$prep_ids));
            foreach ($all_comments as $com) {
                $comments_map[$com->prep_id][] = array(
                    'author' => $com->display_name,
                    'text'   => $com->comment_text,
                    'date'   => date_i18n('Y-m-d H:i', strtotime($com->created_at))
                );
            }
        }

        foreach ($submissions as $sub) {
            $parsed_data = json_decode($sub->lesson_data, true) ?: array();
            $comments_array = $comments_map[$sub->id] ?? array();

            $preps_for_js[$sub->id] = array(
                'title' => $sub->title,
                'subject' => $sub->subject,
                'grade' => $sub->grade_level,
                'section' => $sub->class_section,
                'date' => $sub->lesson_date,
                'file_url' => $parsed_data['file_url'] ?? '',
                'objectives' => $parsed_data['objectives'] ?? '',
                'warmup' => $parsed_data['warmup'] ?? '',
                'activities' => $parsed_data['activities'] ?? '',
                'evaluation' => $parsed_data['evaluation'] ?? '',
                'homework' => $parsed_data['homework'] ?? '',
                'notes' => $parsed_data['notes'] ?? '',
                'comments' => $comments_array
            );
        }
    }
    echo json_encode($preps_for_js);
?>;

function smOpenPrepViewer(id) {
    const data = eessSubmissions[id];
    if (!data) return;

    document.getElementById('view-modal-title').innerText = data.title;

    const subLower = data.subject.toLowerCase();
    const isPe = (subLower.indexOf('') !== -1 || subLower.indexOf('') !== -1 || subLower.indexOf('pe') !== -1 || subLower.indexOf('physical') !== -1 || subLower.indexOf('health') !== -1);

    const label1 = isPe ? '  (Physical Prep)' : '  ';
    const label2 = isPe ? '  (Skill Prep)' : '  ';
    const label3 = isPe ? ' / (Main/Practical Activity)' : 'No Active   No';
    const label4 = isPe ? '  (Cool-down & Closing)' : ' Training Group  ';
    const label5 = isPe ? '    ' : '   Academy';
    const label6 = isPe ? ' Mother No Notes' : 'Notes  No ';

    let html = `
        <div style="background:#f8fafc; padding: 12px; border-radius: 8px; border:1px solid #e2e8f0; margin-bottom:15px; display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size: 12px;">
            <div><strong>Sport Activity:</strong> ${data.subject}</div>
            <div><strong>Training Group :</strong> ${data.grade} (${data.section})</div>
            <div><strong> :</strong> ${data.date}</div>
        </div>
    `;

    if (data.file_url) {
        html += `
            <div style="background:#e0f2fe; border:1px solid #bae6fd; border-radius:10px; padding:14px; margin-bottom:15px; text-align:center;">
                <div style="font-weight:800; color:#0369a1; font-size:13px; margin-bottom:8px;">    :</div>
                <a href="${data.file_url}" target="_blank" class="sm-btn" style="background:#0284c7; color:#fff !important; height:36px; padding:0 20px; font-size:12px; border-radius:9999px !important; text-decoration:none; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-visibility"></span>
                    <span> Upload   </span>
                </a>
            </div>
        `;
    }

    html += `
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-primary-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-primary-color); font-size:12px; font-weight:800;">${label1}</h4>
            <p style="margin:0; font-size:12px;">${data.objectives.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-secondary-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-secondary-color); font-size:12px; font-weight:800;">${label2}</h4>
            <p style="margin:0; font-size:12px;">${data.warmup.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-accent-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-accent-color); font-size:12px; font-weight:800;">${label3}</h4>
            <p style="margin:0; font-size:12px;">${data.activities.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-dark-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-dark-color); font-size:12px; font-weight:800;">${label4}</h4>
            <p style="margin:0; font-size:12px;">${data.evaluation.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid #8b1e1e; padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:#8b1e1e; font-size:12px; font-weight:800;">${label5}</h4>
            <p style="margin:0; font-size:12px;">${data.homework ? data.homework.replace(/\n/g, '<br>') : 'No    '}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid #64748b; padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:#64748b; font-size:12px; font-weight:800;">${label6}</h4>
            <p style="margin:0; font-size:12px;">${data.notes ? data.notes.replace(/\n/g, '<br>') : 'No  Notes '}</p>
        </div>
    `;

    if (data.comments && data.comments.length > 0) {
        html += `
            <div style="margin-top: 20px; padding-top: 12px; border-top: 2px dashed #e2e8f0;">
                <h4 style="margin: 0 0 10px 0; color:#dc2626; font-size:12px; font-weight:800;">  Notes   </h4>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    ${data.comments.map(c => `
                        <div style="background:#fff5f5; border:1px solid #fca5a5; padding:10px; border-radius:6px;">
                            <div style="display:flex; justify-content:space-between; font-size:10px; color:#c53030; font-weight:800; margin-bottom:3px;">
                                <span> : ${c.author}</span>
                                <span>${c.date}</span>
                            </div>
                            <p style="margin:0; font-size:11px; color:#991b1b; line-height:1.5;">${c.text.replace(/\n/g, '<br>')}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    document.getElementById('prep-viewer-body').innerHTML = html;
    document.getElementById('prep-viewer-modal').style.display = 'flex';
}

function smOpenReviewModal(id, title) {
    document.getElementById('review-prep-id').value = id;
    document.getElementById('review-prep-title').value = title;
    document.getElementById('prep-review-modal').style.display = 'flex';
}


function eessOpenRejectPrepModal(prepId, title) {
    document.getElementById('reject_prep_id').value = prepId;
    document.getElementById('reject_prep_notes').value = '';
    document.getElementById('reject_prep_modal_title').innerText = ' Edit / Reject: ' + title;
    document.getElementById('eess-reject-prep-modal').style.display = 'flex';
}

function eessSubmitRejectPrep(e) {
    e.preventDefault();
    var prepId = document.getElementById('reject_prep_id').value;
    var notes = document.getElementById('reject_prep_notes').value;

    if (!prepId || !notes) return;

    var btn = document.getElementById('reject_prep_submit_btn');
    btn.disabled = true;
    btn.innerText = ' Save Reject...';

    var formData = new FormData();
    formData.append('action', 'eess_reject_lesson_prep');
    formData.append('prep_id', prepId);
    formData.append('notes', notes);
    formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_lesson_prep_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'Send Notes Reject';
        if (res.success) {
            document.getElementById('eess-reject-prep-modal').style.display = 'none';
            if (typeof smShowNotification === 'function') {
                smShowNotification('✓   Notes    .');
            }
            var row = document.getElementById('prep-row-' + prepId);
            if (row) {
                var badgeCell = row.cells[7];
                if (badgeCell) {
                    badgeCell.innerHTML = '<span style="display:inline-block; padding:2px 8px; border-radius:50px; font-size:10px; font-weight:bold; background:#ffedd5; color:#c2410c;"> Edit</span>';
                }
            }
        } else {
            alert(': ' + (res.data || '  Reject.'));
        }
    });
}

window.smQuickApprovePrep = function(prepId) {
    if (!prepId) return;

    var confirmMsg = '    Confirm Approve     ';
    var runApproval = function() {
        var btn = document.getElementById('btn-approve-' + prepId);
        if (btn) btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'eess_quick_approve_prep');
        formData.append('prep_id', prepId);
        formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_lesson_prep_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (typeof smShowNotification === 'function') {
                    smShowNotification('✓       .');
                }
                var row = document.getElementById('prep-row-' + prepId);
                if (row) {
                    var badgeCell = row.cells[7];
                    if (badgeCell) {
                        badgeCell.innerHTML = '<span style="display:inline-block; padding:2px 8px; border-radius:50px; font-size:10px; font-weight:bold; background:#dcfce7; color:#15803d;"></span>';
                    }
                }
                if (btn) btn.style.display = 'none';

                var statCounter = document.getElementById('eess-pending-review-stat-counter');
                if (statCounter) {
                    var cur = parseInt(statCounter.innerText) || 0;
                    statCounter.innerText = Math.max(0, cur - 1);
                }
            } else {
                alert(': ' + (res.data || '  .'));
                if (btn) btn.disabled = false;
            }
        })
        .catch(err => {
            alert('An error occurred  No .');
            if (btn) btn.disabled = false;
        });
    };

    if (typeof window.smConfirmAction === 'function') {
        window.smConfirmAction(confirmMsg).then(function(confirmed) {
            if (confirmed) runApproval();
        });
    } else {
        if (confirm(confirmMsg)) runApproval();
    }
};

window.smOpenDeletePrepModal = function(prepId, title) {
    if (!prepId) return;
    document.getElementById('eess_delete_prep_target_id').value = prepId;
    document.getElementById('eess_delete_prep_title_display').innerText = title || ' ';
    document.getElementById('eess-delete-prep-modal').style.display = 'flex';
};

window.eessExecuteConfirmDeletePrep = function() {
    var prepId = document.getElementById('eess_delete_prep_target_id').value;
    if (!prepId || prepId === '0') return;

    var btn = document.getElementById('eess-btn-confirm-delete-prep');
    btn.disabled = true;
    btn.innerText = ' Delete...';

    var formData = new FormData();
    formData.append('action', 'eess_bulk_lesson_action');
    formData.append('bulk_action', 'delete');
    formData.append('prep_ids[]', prepId);
    formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_lesson_prep_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'Confirm Delete ';
        document.getElementById('eess-delete-prep-modal').style.display = 'none';

        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification(' Delete   ');
            } else {
                alert(' Delete   ');
            }
            var row = document.getElementById('prep-row-' + prepId);
            if (row) row.remove();
        } else {
            alert(': ' + (res.data || ' Delete .'));
        }
    });
};
</script>

<!-- Dynamic Lesson Preparation Reporting & Compliance Modal -->
<?php
if ($is_hod && !$is_admin && !$is_sys_admin) {
    $hod_subject = get_user_meta($user_id, 'sm_specialization', true);
    $prep_report_teachers = get_users(array(
        'role'       => 'sm_teacher',
        'meta_key'   => 'sm_specialization',
        'meta_value' => $hod_subject
    ));
    $prep_report_submitted = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->users} u ON p.teacher_id = u.ID WHERE p.subject = %s AND p.status IN ('submitted', 'approved', 'late') ORDER BY p.id DESC LIMIT 30",
        $hod_subject
    ));
} else {
    $user_scope = EESS_Org_Helper::get_user_scope($user_id);
    if (!$user_scope['unrestricted'] && !empty($user_scope['schools'])) {
        $prep_report_teachers = get_users(array(
            'role'       => 'sm_teacher',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => 'eess_school_id', 'value' => $user_scope['schools'], 'compare' => 'IN'),
                array('key' => 'sm_school_id', 'value' => $user_scope['schools'], 'compare' => 'IN')
            )
        ));
    } else {
        $prep_report_teachers = get_users(array('role' => 'sm_teacher'));
    }
    $prep_report_submitted = $wpdb->get_results("SELECT p.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->users} u ON p.teacher_id = u.ID WHERE p.status IN ('submitted', 'approved', 'late') ORDER BY p.id DESC LIMIT 30");
}

$prep_report_inst = $wpdb->get_results("SELECT COALESCE(um.meta_value, '   ') as inst, COUNT(*) as cnt FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->usermeta} um ON p.teacher_id = um.user_id AND um.meta_key = 'eess_school_name' GROUP BY inst ORDER BY cnt DESC");
$prep_report_dept = $wpdb->get_results("SELECT COALESCE(um.meta_value, ' ') as dept, COUNT(*) as cnt FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->usermeta} um ON p.teacher_id = um.user_id AND um.meta_key = 'eess_department' GROUP BY dept ORDER BY cnt DESC");
$prep_report_subject = $wpdb->get_results("SELECT subject as name, COUNT(*) as cnt FROM {$wpdb->prefix}sm_lesson_preps GROUP BY subject ORDER BY cnt DESC");

$prep_report_daily = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE DATE(lesson_date) = CURDATE()") ?: 0;
$prep_report_weekly = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE YEARWEEK(lesson_date, 1) = YEARWEEK(CURDATE(), 1)") ?: 0;
$prep_report_monthly = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE MONTH(lesson_date) = MONTH(CURDATE()) AND YEAR(lesson_date) = YEAR(CURDATE())") ?: 0;

$prep_report_ranking = $wpdb->get_results("SELECT p.teacher_id, u.display_name, COUNT(*) as total, SUM(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) as approved_count FROM {$wpdb->prefix}sm_lesson_preps p JOIN {$wpdb->users} u ON p.teacher_id = u.ID GROUP BY p.teacher_id ORDER BY approved_count DESC, total DESC LIMIT 10");
$prep_report_avg_late = $wpdb->get_var("SELECT AVG(delay_seconds / 60) FROM {$wpdb->prefix}sm_lesson_preps WHERE delay_seconds > 0") ?: 0;
$prep_report_total_late = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'late'") ?: 0;
?>

<div id="eess-prep-report-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px); direction: rtl;">
    <div style="background: #fff; width: 100%; max-width: 850px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; max-height: 85vh; font-family: 'Cairo', sans-serif;">
        <!-- Modal Header -->
        <div style="background: #1e293b; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="eess-report-modal-title" style="margin: 0; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-analytics"></span>        No
            </h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button onclick="window.print()" class="sm-btn" style="background: #475569; color: white; border: none; font-size: 11px; padding: 4px 12px; height: auto; cursor:pointer;">️ Print </button>
                <button type="button" onclick="document.getElementById('eess-prep-report-modal').style.display='none'" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
        </div>

        <!-- Modal Body -->
        <div style="padding: 20px; overflow-y: auto; flex: 1;">

            <!-- Report 1: Submitted -->
            <div id="rep-submitted" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">    </h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-submitted" style="width: 100%;">
                        <thead>
                            <tr><th></th><th> </th><th>Sport Activity</th><th>Training Group </th><th> </th><th> No</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_submitted)): ?>
                                <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No     .</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_submitted as $p): ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?php echo esc_html($p->teacher_name); ?></td>
                                        <td><?php echo esc_html($p->title); ?></td>
                                        <td><?php echo esc_html($p->subject); ?></td>
                                        <td><?php echo esc_html($p->grade_level); ?> (<?php echo esc_html($p->class_section); ?>)</td>
                                        <td style="font-weight: bold;"><?php echo esc_html($p->lesson_date); ?></td>
                                        <td><span style="background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;"><?php echo esc_html($p->status); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 2: Not Submitted -->
            <div id="rep-not_submitted" class="eess-report-section" style="display: none;">
                <h3 style="margin: 0 0 10px 0; color: #881337; font-weight: 900; text-align: center; font-size: 18px; border-bottom: 2px solid #881337; padding-bottom: 10px;">          </h3>

                <?php
                $tab_acad_anchor = strtotime('2026-08-30 00:00:00');
                $tab_curr_ts = current_time('timestamp');
                if ($tab_curr_ts >= $tab_acad_anchor) {
                    $tab_curr_week = intval(floor(($tab_curr_ts - $tab_acad_anchor) / (7 * 86400))) + 1;
                } else {
                    $tab_curr_week = 1;
                }
                $tab_curr_week = max(1, min(16, $tab_curr_week));

                $tab_arabic_week_names = array(
                    1  => ' ',
                    2  => ' ',
                    3  => ' ',
                    4  => ' ',
                    5  => ' ',
                    6  => ' ',
                    7  => ' ',
                    8  => ' ',
                    9  => ' ',
                    10 => ' ',
                    11 => '  ',
                    12 => '  ',
                    13 => '  ',
                    14 => '  ',
                    15 => '  ',
                    16 => '  '
                );

                $tab_range_end_title = $tab_arabic_week_names[$tab_curr_week] ?? (' ' . $tab_curr_week);

                $tab_non_submitters = array();
                $tab_compliant_teachers = array();

                $all_preps_batch = $wpdb->get_results("SELECT teacher_id, lesson_date, created_at FROM {$wpdb->prefix}sm_lesson_preps WHERE status IN ('submitted', 'approved', 'resubmitted', 'late')");
                $preps_by_teacher = array();
                foreach ($all_preps_batch as $ap) {
                    $preps_by_teacher[$ap->teacher_id][] = $ap;
                }

                foreach ($prep_report_teachers as $t) {
                    $t_preps = $preps_by_teacher[$t->ID] ?? array();

                    $sub_weeks = array();
                    foreach ($t_preps as $p) {
                        $p_date = (!empty($p->lesson_date) && $p->lesson_date !== '0000-00-00') ? $p->lesson_date : ($p->created_at);
                        if (!empty($p_date) && $p_date !== '0000-00-00 00:00:00') {
                            $p_ts = strtotime($p_date);
                            if ($p_ts >= $tab_acad_anchor) {
                                $wn = intval(floor(($p_ts - $tab_acad_anchor) / (7 * 86400))) + 1;
                                $sub_weeks[$wn] = true;
                            } else {
                                $sub_weeks[1] = true;
                            }
                        }
                    }

                    $m_weeks = array();
                    for ($w = 1; $w <= $tab_curr_week; $w++) {
                        if (!isset($sub_weeks[$w])) {
                            $m_weeks[] = $w;
                        }
                    }

                    $tab_entry = array(
                        'user'          => $t,
                        'emp_number'    => get_user_meta($t->ID, 'eess_employee_number', true) ?: ($t->ID),
                        'school_name'   => get_user_meta($t->ID, 'eess_school_name', true) ?: 'Organization  Home',
                        'grades_taught' => EESS_Org_Helper::format_assigned_grades($t->ID),
                        'subject'       => get_user_meta($t->ID, 'sm_specialization', true) ?: '',
                        'total_missing' => count($m_weeks),
                        'missing_weeks' => $m_weeks
                    );

                    if (!empty($m_weeks)) {
                        $tab_non_submitters[] = $tab_entry;
                    } else {
                        $tab_compliant_teachers[] = $tab_entry;
                    }
                }

                usort($tab_non_submitters, function($a, $b) {
                    return $b['total_missing'] <=> $a['total_missing'];
                });
                ?>

                <div style="text-align: center; font-size: 12px; color: #334155; font-weight: 700; margin-bottom: 12px;">
                    <div style="font-weight: 900; color: #0f172a;"> Academy  </div>
                    <div style="font-weight: 900; color: #881337; font-size: 13px;">   <?php echo esc_html($tab_range_end_title); ?></div>
                </div>

                <div style="background: #f8fafc; border-right: 4px solid #881337; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; color: #1e293b; line-height: 1.8; font-weight: 700;">
                    <p style="margin: 0 0 8px 0;">                         Academy          <?php echo esc_html($tab_range_end_title); ?>.        No             .</p>
                    <p style="margin: 0;">                      .</p>
                </div>

                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-not-submitted" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 32px; text-align: center;">#</th>
                                <th style="width: 35%;">  / </th>
                                <th style="width: 30%;">Academy  Training Groups  </th>
                                <th style="width: 35%;">   </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tab_non_submitters)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #16a34a; font-weight: bold; padding: 20px;">
                                                   Academy  !
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($tab_non_submitters as $idx => $ns):
                                    $m_cnt = $ns['total_missing'];
                                    if ($m_cnt === 1) {
                                        $capsule_style = 'background: #fef2f2; color: #991b1b; border: 1px solid #fecdd3;';
                                    } elseif ($m_cnt === 2) {
                                        $capsule_style = 'background: #fee2e2; color: #881337; border: 1px solid #fca5a5;';
                                    } else {
                                        $capsule_style = 'background: #fecdd3; color: #701a2b; border: 1px solid #f87171;';
                                    }
                            ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold;"><?php echo ($idx + 1); ?></td>
                                    <td>
                                        <div style="font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?php echo esc_html($ns['user']->display_name); ?></div>
                                        <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                            <span style="background: #881337; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800; font-family: monospace;"><?php echo esc_html($ns['emp_number']); ?></span>
                                            <span style="background: #dc2626; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800;"><?php echo esc_html($ns['subject']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; color: #0f172a; font-size: 11.5px;"><?php echo esc_html($ns['school_name']); ?></div>
                                        <div style="color: #475569; font-size: 10.5px; font-weight: 700; margin-top: 2px;">Training Groups: <?php echo esc_html($ns['grades_taught']); ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        foreach ($ns['missing_weeks'] as $mw) {
                                            $w_label = $tab_arabic_week_names[$mw] ?? (' ' . $mw);
                                            echo '<span style="display: inline-block; padding: 3px 9px; margin: 2px 3px; border-radius: 9999px; font-weight: 800; font-size: 10.5px; ' . $capsule_style . '">' . esc_html($w_label) . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TAB SECOND SECTION: COMPLIANT TEACHERS -->
                <h4 style="margin: 30px 0 6px 0; color: #15803d; font-weight: 900; font-size: 16px; text-align: center; border-bottom: 2px solid #16a34a; padding-bottom: 6px;">

                </h4>

                <div style="text-align: center; font-size: 12px; color: #334155; font-weight: 700; margin-bottom: 15px;">
                    <div style="font-weight: 900; color: #0f172a; font-size: 12px;"> Academy  </div>
                    <div style="font-weight: 900; color: #15803d; font-size: 13px; margin-top: 2px;">   <?php echo esc_html($tab_range_end_title); ?></div>
                </div>

                <div class="sm-table-container">
                    <table class="sm-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 32px; text-align: center;">#</th>
                                <th style="width: 35%;">  / </th>
                                <th style="width: 30%;">Academy  Training Groups  </th>
                                <th style="width: 35%; text-align: center;"> No </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tab_compliant_teachers)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748b; padding: 15px;">
                                        No      .
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($tab_compliant_teachers as $idx => $cs):
                            ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold;"><?php echo ($idx + 1); ?></td>
                                    <td style="text-align: right;">
                                        <div style="font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?php echo esc_html($cs['user']->display_name); ?></div>
                                        <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                            <span style="background: #881337; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800; font-family: monospace;"><?php echo esc_html($cs['emp_number']); ?></span>
                                            <span style="background: #dc2626; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800;"><?php echo esc_html($cs['subject']); ?></span>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="font-weight: 800; color: #0f172a; font-size: 11.5px;"><?php echo esc_html($cs['school_name']); ?></div>
                                        <div style="color: #475569; font-size: 10.5px; font-weight: 700; margin-top: 2px;">Training Groups: <?php echo esc_html($cs['grades_taught']); ?></div>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <span style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 4px 14px; border-radius: 9999px; background: #dcfce7; color: #15803d; border: 1px solid #86efac; font-weight: 900; font-size: 11px;">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border-radius: 50%; background: #16a34a; color: #ffffff; font-size: 10px; font-weight: 900; line-height: 1;">✓</span>
                                            <span>  </span>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 3: By Institution -->
            <div id="rep-by_institution" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">    Organization  </h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-by-institution" style="width: 100%;">
                        <thead>
                            <tr><th> Organization  / Academy </th><th>  </th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_inst)): ?>
                                <tr><td colspan="2" style="text-align: center; color: #94a3b8;">No data found .</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_inst as $inst): ?>
                                    <tr><td style="font-weight: 700;"><?php echo esc_html($inst->inst); ?></td><td style="font-weight: bold; font-family: monospace; color: var(--sm-primary-color);"><?php echo $inst->cnt; ?> </td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 4: By Department -->
            <div id="rep-by_department" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">     </h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-by-department" style="width: 100%;">
                        <thead>
                            <tr><th> / </th><th>  </th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_dept)): ?>
                                <tr><td colspan="2" style="text-align: center; color: #94a3b8;">No data found .</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_dept as $dept): ?>
                                    <tr><td style="font-weight: 700;"><?php echo esc_html($dept->dept); ?></td><td style="font-weight: bold; font-family: monospace; color: var(--sm-primary-color);"><?php echo $dept->cnt; ?> </td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 5: By Subject -->
            <div id="rep-by_subject" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">    Sports Activities </h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-by-subject" style="width: 100%;">
                        <thead>
                            <tr><th>Sport Activity </th><th>  </th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_subject)): ?>
                                <tr><td colspan="2" style="text-align: center; color: #94a3b8;">No data found   .</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_subject as $sub): ?>
                                    <tr><td style="font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($sub->name); ?></td><td style="font-weight: bold; font-family: monospace;"><?php echo $sub->cnt; ?> </td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 6: Periodical -->
            <div id="rep-periodical" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">    </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">  </span>
                        <strong style="font-size: 28px; color: #1e293b; font-family: monospace;"><?php echo $prep_report_daily; ?></strong>
                    </div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">  </span>
                        <strong style="font-size: 28px; color: #1e293b; font-family: monospace;"><?php echo $prep_report_weekly; ?></strong>
                    </div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">  </span>
                        <strong style="font-size: 28px; color: #1e293b; font-family: monospace;"><?php echo $prep_report_monthly; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Report 7: Ranking -->
            <div id="rep-ranking" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">      (  )</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-ranking" style="width: 100%;">
                        <thead>
                            <tr><th> </th><th> </th><th>  </th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_ranking)): ?>
                                <tr><td colspan="3" style="text-align: center; color: #94a3b8;">No     .</td></tr>
                            <?php else: ?>
                                <?php $rank = 1; foreach ($prep_report_ranking as $teacher): ?>
                                    <tr>
                                        <td style="font-weight: 800; color: #b7791f;">⭐  <?php echo $rank++; ?></td>
                                        <td style="font-weight: 700;"><?php echo esc_html($teacher->display_name); ?></td>
                                        <td style="font-weight: bold; font-family: monospace; color: #16a34a;"><?php echo $teacher->approved_count; ?>   <?php echo $teacher->total; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 8: Compliance -->
            <div id="rep-compliance" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;"> Intermediate No   Annual </h4>
                <div style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1; text-align: center; max-width: 500px; margin: 0 auto;">
                    <span style="font-size: 15px; color: #475569; font-weight: bold; display: block; margin-bottom: 10px;"> Intermediate    </span>
                    <strong style="font-size: 3.5rem; color: #16a34a; font-family: monospace;"><?php echo $submission_pct; ?>%</strong>
                    <p style="margin: 15px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.6;">                Active .</p>
                </div>
            </div>

            <!-- Report 9: Late Statistics -->
            <div id="rep-late_stats" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">⏱️     </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">Intermediate   </span>
                        <strong style="font-size: 26px; color: #dc2626; font-family: monospace;"><?php echo round($prep_report_avg_late); ?> </strong>
                    </div>
                    <div style="background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">  </span>
                        <strong style="font-size: 26px; color: #dc2626; font-family: monospace;"><?php echo $prep_report_total_late; ?> </strong>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
