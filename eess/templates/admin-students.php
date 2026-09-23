<?php if (!defined('ABSPATH')) exit; ?>
<?php
$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_discipline_sup = in_array('sm_discipline_supervisor', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_admin = current_user_can('شؤون_الطلاب') || current_user_can('manage_options') || current_user_can('manage_students') || $is_discipline_sup || $is_principal;
$import_results = get_transient('sm_import_results_' . get_current_user_id());
if ($import_results) {
    delete_transient('sm_import_results_' . get_current_user_id());
}

// Query parameters & Pagination for Student Affairs (10 per page default)
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$search = isset($_GET['student_search']) ? sanitize_text_field($_GET['student_search']) : '';
$class_filter = isset($_GET['class_filter']) ? sanitize_text_field($_GET['class_filter']) : '';
$section_filter = isset($_GET['section_filter']) ? sanitize_text_field($_GET['section_filter']) : '';
$teacher_filter = isset($_GET['teacher_filter']) ? intval($_GET['teacher_filter']) : 0;

global $wpdb;
$students_list = is_array($students) ? $students : array();
$total_students_count = count($students_list);

$total_pages = max(1, ceil($total_students_count / $limit));
if ($paged > $total_pages) $paged = $total_pages;
$offset = ($paged - 1) * $limit;

// Paginated slice of students
$paginated_students = array_slice($students_list, $offset, $limit);
$from_num = $total_students_count > 0 ? $offset + 1 : 0;
$to_num = min($offset + $limit, $total_students_count);
?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important; color: #1e293b; width: 100% !important; max-width: 100% !important; box-sizing: border-box;">

    <?php if ($import_results): ?>
        <div style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,0.02);">
            <div style="background: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin:0; color: #0f172a; font-weight: 800; font-size: 15px;">تقرير استيراد الطلاب الأخير</h4>
                <span style="font-size: 12px; color: #64748b; font-weight: 700;">إجمالي السجلات المعالجة: <?php echo $import_results['total']; ?></span>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f0fff4; padding: 14px; border-radius: 12px; border: 1px solid #c6f6d5; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #2f855a;"><?php echo $import_results['success'] - ($import_results['duplicate'] ?? 0); ?></div>
                        <div style="font-size: 11.5px; color: #38a169; font-weight: 700;">سجلات جديدة</div>
                    </div>
                    <div style="background: #e6fffa; padding: 14px; border-radius: 12px; border: 1px solid #b2f5ea; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #2c7a7b;"><?php echo $import_results['generated'] ?? 0; ?></div>
                        <div style="font-size: 11.5px; color: #319795; font-weight: 700;">أكواد تم توليدها</div>
                    </div>
                    <div style="background: #ebf8ff; padding: 14px; border-radius: 12px; border: 1px solid #bee3f8; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #2b6cb0;"><?php echo $import_results['duplicate'] ?? 0; ?></div>
                        <div style="font-size: 11.5px; color: #3182ce; font-weight: 700;">سجلات مكررة</div>
                    </div>
                    <div style="background: #fffaf0; padding: 14px; border-radius: 12px; border: 1px solid #feebc8; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #c05621;"><?php echo $import_results['warning']; ?></div>
                        <div style="font-size: 11.5px; color: #dd6b20; font-weight: 700;">تنبيهات</div>
                    </div>
                    <div style="background: #fff5f5; padding: 14px; border-radius: 12px; border: 1px solid #fed7d7; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #c53030;"><?php echo $import_results['error']; ?></div>
                        <div style="font-size: 11.5px; color: #e53e3e; font-weight: 700;">أخطاء</div>
                    </div>
                </div>

                <?php if (!empty($import_results['details'])): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; max-height: 220px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: right;">
                            <thead>
                                <tr style="background: #edf2f7; position: sticky; top: 0;">
                                    <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e0; width: 80px;">النوع</th>
                                    <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e0;">التفاصيل والسبب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_results['details'] as $detail): ?>
                                    <tr>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">
                                            <?php if ($detail['type'] == 'error'): ?>
                                                <span style="color: #e53e3e; font-weight: 700;">خطأ</span>
                                            <?php elseif ($detail['type'] == 'info'): ?>
                                                <span style="color: #3182ce; font-weight: 700;">تكرار</span>
                                            <?php else: ?>
                                                <span style="color: #dd6b20; font-weight: 700;">تنبيه</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0; color: #4a5568;"><?php echo esc_html($detail['msg']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 1. Header Banner Card (Wine Red / Red Pastel Theme) -->
    <div style="background: #ffffff; padding: 14px 18px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 14px; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.02); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-groups" style="font-size: 22px; width: 22px; height: 22px; line-height: 1;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 2px 0; font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px;">
                    إدارة شؤون الطلاب
                </h2>
                <p style="margin: 0; font-size: 11.5px; color: #64748b; font-weight: 500;">
                    المركز الرئيسي لإدارة بيانات الطلاب، الملفات الأكاديمية والشخصية، السجلات المدرسية، واستيراد وتصدير ملفات البيانات المعتمدة
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <!-- Single Unified Import / Export Dropdown Button -->
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="const d = document.getElementById('eess-students-import-export-dropdown'); d.style.display = d.style.display === 'none' ? 'block' : 'none'; event.stopPropagation();" class="eess-hdr-btn" style="background: #f8fafc !important; color: #1e293b !important; border: 1px solid #cbd5e1 !important; border-radius: 8px; padding: 0 12px; height: 34px; font-weight: 800; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                    <span class="dashicons dashicons-database" style="font-size: 16px; width: 16px; height: 16px; color: #881337;"></span>
                    <span>استيراد / تصدير</span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; color: #475569;"></span>
                </button>

                <div id="eess-students-import-export-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 270px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <div style="padding: 6px 16px; font-size: 11px; color: #94a3b8; font-weight: 800; border-bottom: 1px solid #f1f5f9;">عمليات استيراد وتصدير بيانات الطلاب</div>
                    <a href="javascript:void(0)" onclick="const f=document.getElementById('csv-import-form'); f.style.display='block'; document.getElementById('eess-students-import-export-dropdown').style.display='none';" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px; color: #0284c7;"></span>
                        <span>استيراد بيانات شؤون الطلاب (Excel/CSV)</span>
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_students_csv&nonce=' . wp_create_nonce('sm_admin_action')); ?>" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; color: #881337;"></span>
                        <span>تصدير بيانات شؤون الطلاب (Excel/CSV)</span>
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_download_student_import_template'); ?>" target="_blank" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-media-document" style="font-size: 16px; width: 16px; height: 16px; color: #16a34a;"></span>
                        <span>تحميل نموذج الاستيراد الرسمي (16 عمود)</span>
                    </a>
                    <div style="padding: 6px 16px; font-size: 11px; color: #94a3b8; font-weight: 800; border-bottom: 1px solid #f1f5f9;">تصدير التقارير والبطاقات</div>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card'); ?>" target="_blank" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #15803d; font-size: 12px; font-weight: 700; text-decoration: none; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-id" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>طباعة بطاقات تصريح الخروج للمدرسة</span>
                    </a>
                </div>
            </div>

            <!-- Global Student PDF Export Button (Print Student Data) -->
            <?php if ($is_admin || current_user_can('manage_options') || in_array('sm_system_admin', $roles)): ?>
            <button type="button" onclick="document.getElementById('eess-student-export-pdf-modal').style.display='flex'" title="طباعة بيانات الطلاب (PDF)" class="eess-hdr-btn" style="background: #ffffff !important; color: #881337 !important; border: 1px solid #fecdd3 !important; border-radius: 8px; height: 34px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                <span class="dashicons dashicons-printer" style="font-size: 18px; width: 18px; height: 18px; color: #881337;"></span>
                <span>طباعة بيانات الطلاب</span>
            </button>
            <?php endif; ?>

            <!-- Student Data Portal Icon Button (Aligned Next to Settings & Controls Icon) -->
            <a href="<?php echo esc_url(get_permalink(get_option('eess_exit_card_portal_page_id')) ?: home_url('/stu/')); ?>" target="_blank" title="بوابة بيانات وبطاقات الطلاب" class="eess-hdr-btn" style="background: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; border-radius: 8px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                <span class="dashicons dashicons-id-alt" style="font-size: 18px; width: 18px; height: 18px; color: #0f172a;"></span>
            </a>

            <!-- System Administrator Controls Gear Button -->
            <?php if ($is_admin || current_user_can('manage_options') || in_array('sm_system_admin', $roles)): ?>
            <button type="button" onclick="document.getElementById('eess-sysadmin-students-modal').style.display='flex'" title="إعدادات وضوابط مدير النظام" class="eess-hdr-btn" style="background: #0f172a !important; color: #ffffff !important; border: 1px solid #0f172a !important; border-radius: 8px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                <span class="dashicons dashicons-admin-generic" style="font-size: 18px; width: 18px; height: 18px; color: #ffffff;"></span>
            </button>
            <?php endif; ?>

            <!-- Primary Action: Add Student (Wine Red) -->
            <?php if ($is_admin): ?>
            <button type="button" onclick="openAddStudentWizard()" class="sm-btn sm-btn-custom" style="background: #881337; color: #ffffff; border: none; border-radius: 8px; padding: 0 16px; height: 34px; font-weight: 800; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 8px rgba(136, 19, 55, 0.2); transition: all 0.2s;" onmouseover="this.style.background='#700c2a'" onmouseout="this.style.background='#881337'">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px; color: #ffffff;"></span>
                <span>إضافة طالب جديد</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Search & Filtering Card (Clean Inline Placeholders, No External Labels) -->
    <div style="background: #ffffff; padding: 18px 24px; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
        <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: center;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'sm-dashboard'); ?>">
            <input type="hidden" name="sm_tab" value="students">
            <input type="hidden" name="paged" value="1">
            <input type="hidden" name="limit" value="<?php echo esc_attr($limit); ?>">

            <!-- Student Search -->
            <div style="position: relative;">
                <input type="text" name="student_search" value="<?php echo esc_attr($search); ?>" placeholder="بحث باسم الطالب، الكود، الهوية..." style="width: 100%; height: 42px; padding: 0 38px 0 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 12.5px; outline: none; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                    <span class="dashicons dashicons-search" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </span>
            </div>

            <!-- Grade Filter -->
            <div style="position: relative;">
                <select name="class_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 12.5px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    <option value="">جميع الصفوف الدراسية</option>
                    <?php
                    $academic = SM_Settings::get_academic_structure();
                    foreach ($academic['active_grades'] as $grade_num) {
                        $grade_label = 'الصف ' . $grade_num;
                        echo '<option value="' . esc_attr($grade_label) . '" ' . selected($class_filter == $grade_label, true, false) . '>' . esc_html($grade_label) . '</option>';
                    }
                    ?>
                </select>
                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                    <span class="dashicons dashicons-welcome-learn-more" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </span>
                <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
            </div>

            <!-- Section Filter -->
            <div style="position: relative;">
                <select name="section_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 12.5px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    <option value="">جميع الشعب (أ، ب، ج...)</option>
                    <option value="أ" <?php selected($section_filter, 'أ'); ?>>شعبة أ</option>
                    <option value="ب" <?php selected($section_filter, 'ب'); ?>>شعبة ب</option>
                    <option value="ج" <?php selected($section_filter, 'ج'); ?>>شعبة ج</option>
                    <option value="د" <?php selected($section_filter, 'د'); ?>>شعبة د</option>
                    <option value="هـ" <?php selected($section_filter, 'هـ'); ?>>شعبة هـ</option>
                </select>
                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                    <span class="dashicons dashicons-category" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </span>
                <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
            </div>

            <!-- Teacher Filter -->
            <div style="position: relative;">
                <select name="teacher_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 12.5px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    <option value="">جميع المعلمين والمربين</option>
                    <?php
                    $teachers = get_users(array('role' => 'sm_teacher'));
                    foreach ($teachers as $t) {
                        echo '<option value="' . $t->ID . '" ' . selected($teacher_filter == $t->ID, true, false) . '>' . esc_html($t->display_name) . '</option>';
                    }
                    ?>
                </select>
                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                    <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </span>
                <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
            </div>

            <!-- Apply Filters Button -->
            <div>
                <button type="submit" class="sm-btn" style="background: #881337; color: #ffffff; border: none; border-radius: 12px; height: 42px; padding: 0 22px; font-weight: 800; font-size: 13px; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(136, 19, 55, 0.2); transition: all 0.2s;" onmouseover="this.style.background='#700c2a'" onmouseout="this.style.background='#881337'">
                    <span class="dashicons dashicons-filter" style="font-size: 16px; width: 16px; height: 16px; color: #fff;"></span>
                    <span>تطبيق الفلترة</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Chunked File Upload Progress Form & Documentation -->
    <div id="csv-import-form" style="display:none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h4 style="margin:0; color:#0f172a; font-weight: 800; font-size: 16px;">استيراد ذكي لملف الطلاب الشامل (Excel / CSV)</h4>
            <a href="<?php echo admin_url('admin-ajax.php?action=sm_download_student_import_template'); ?>" target="_blank" class="sm-btn" style="background: #881337; color: white !important; font-size: 11px; padding: 6px 16px; width: auto; height: 32px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-download" style="font-size: 14px; width: 14px; height: 14px; color: #fff;"></span>
                <span>تحميل نموذج الاستيراد المعتمد (12 عمود)</span>
            </a>
        </div>

        <p style="font-size:12.5px; color:#64748b; line-height:1.6; margin-bottom:15px;">
            يرجى اختيار ملف الطلاب المعتمد بصيغة CSV/Excel بحجم 12 عموداً. يدعم النظام معالجة الملفات الكبيرة والتأكد من مطابقة كود المدرسة والأكواد الرقمية للصف والشعبة تلقائياً.
        </p>

        <!-- Official Column Structure Documentation Card -->
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div style="font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 10px;">📋 دليل ترتيب أعمدة نموذج استيراد الطلاب المعتمد (12 عمود بالترتيب الدقيق من A إلى L):</div>
            <div style="max-height: 250px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; text-align: right;">
                    <thead>
                        <tr style="background: #f1f5f9; position: sticky; top: 0; color: #334155;">
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1; width: 40px;">#</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1;">اسم العمود</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1; width: 90px;">الحالة</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1;">الوصف والضوابط</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1;">مثال</th>
                        </tr>
                    </thead>
                    <tbody style="color: #475569;">
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">1</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">كود المدرسة (School Code)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #dc2626; font-weight:700;">إجباري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">كود المدرسة/المؤسسة المسجل في النظام</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">1</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">2</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">كود الطالب (Student Code)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #881337; font-weight:700;">اختياري (تلقائي)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الكود الرقمي/البارکود المخصص للطالب</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">STU-1001</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">3</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الاسم الكامل (Full Name)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #dc2626; font-weight:700;">إجباري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الاسم الكامل للطالب باللغة العربية/الإنجليزية</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">أحمد علي حسن</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">4</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الهوية الوطنية (National ID)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #881337; font-weight:700;">اختياري (فريد)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">رقم الهوية الوطنية/الإماراتية (أرقام فقط)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">784199012345678</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">5</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الجنس (Gender)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">ذكر أو أنثى</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">ذكر</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">6</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">تاريخ الميلاد (Date of Birth)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">تاريخ بصيغة YYYY-MM-DD</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">2015-05-12</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">7</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الجنسية (Nationality)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">جنسية الطالب الرسمية</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الإمارات العربية المتحدة</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">8</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">إمارة الإقامة (Emirate)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">إمارة السكن والإقامة</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الشارقة</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">9</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الصف (Grade)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #dc2626; font-weight:700;">إجباري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">كود الصف (1 إلى 12) أو اسم الصف المعتمد</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">10</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">10</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الشعبة (Section)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">كود الشعبة (1، 2، أ، ب...)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">1</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">11</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">اسم ولي الأمر (Guardian Name)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الاسم الكامل لولي أمر الطالب</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">علي حسن</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">12</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">رقم هاتف ولي الأمر (Guardian Phone)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">رقم هاتف ولي الأمر مع مفتاح الدولة</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">+971501234567</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="import-selection-area">
            <input type="file" id="csv-file-input" accept=".csv" class="sm-input" style="width: auto; display: inline-block; margin-bottom:15px; font-size:12px; height:36px;">
            <button onclick="startChunkedUpload()" class="sm-btn" style="width: auto; height:36px; font-size:12px; background: #881337; border-color: #881337;">بدء الاستيراد المجدول</button>
        </div>
        <div id="import-progress-area" style="display:none; margin-top:15px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:12px; font-weight:700;">
                <span id="import-status-text">جاري تحليل ومعالجة ملف الاستيراد...</span>
                <span id="import-percentage">0%</span>
            </div>
            <div style="background:#edf2f7; border-radius:50px; height:12px; overflow:hidden; margin-bottom: 15px;">
                <div id="import-progress-bar" style="background:#881337; width:0%; height:100%; transition:0.3s;"></div>
            </div>

            <!-- Real-Time Live Import Counters -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 10px; text-align: center; font-size: 12px; font-weight: 700; margin-bottom: 15px;">
                <div style="background: #f1f5f9; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div style="font-size: 11px; color: #64748b;">إجمالي السجلات</div>
                    <div id="imp-stat-total" style="font-size: 18px; color: #0f172a; font-weight: 800;">0</div>
                </div>
                <div style="background: #f0fdf4; padding: 10px; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <div style="font-size: 11px; color: #166534;">تم استيرادها</div>
                    <div id="imp-stat-success" style="font-size: 18px; color: #16a34a; font-weight: 800;">0</div>
                </div>
                <div style="background: #eff6ff; padding: 10px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    <div style="font-size: 11px; color: #1e40af;">محدّثة</div>
                    <div id="imp-stat-duplicate" style="font-size: 18px; color: #2563eb; font-weight: 800;">0</div>
                </div>
                <div style="background: #fef2f2; padding: 10px; border-radius: 8px; border: 1px solid #fecdd3;">
                    <div style="font-size: 11px; color: #991b1b;">مستبعدة / خطأ</div>
                    <div id="imp-stat-error" style="font-size: 18px; color: #dc2626; font-weight: 800;">0</div>
                </div>
            </div>

            <!-- Detailed Log Area for Errors / Warnings -->
            <div id="imp-details-box" style="display: none; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; max-height: 180px; overflow-y: auto; font-size: 11.5px; line-height: 1.6;">
                <div style="font-weight: 800; color: #334155; margin-bottom: 6px;">سجل تفاصيل المعالجة:</div>
                <ul id="imp-details-list" style="margin: 0; padding-right: 18px; color: #475569;"></ul>
            </div>

            <div id="imp-finish-actions" style="display: none; margin-top: 15px; text-align: left;">
                <button type="button" onclick="location.reload()" class="sm-btn" style="background: #16a34a; color: white !important; font-size: 12px; padding: 8px 20px; font-weight: 800; border-radius: 9999px;">تحديث الصفحة وعرض قائمة الطلاب ←</button>
            </div>
        </div>
    </div>

    <!-- Dynamic Bulk Actions Toolbar -->
    <div id="student-bulk-actions-toolbar" style="display: none; gap: 10px; margin-bottom: 15px; align-items: center; background: #fff5f5; padding: 12px 20px; border-radius: 12px; border: 1px solid #fed7d7;">
        <span style="font-size: 12.5px; font-weight: 700; color: #c53030;">الإجراءات الجماعية للطلاب المحددين:</span>
        <button onclick="bulkPrintCardsSelected()" class="sm-btn" style="background: #1d4ed8; color: white !important; font-size: 11.5px; padding: 5px 16px; width: auto; height: 32px; border-radius: 8px; font-weight: 700;">🖨️ طباعة بطاقات الخروج للمحددين</button>
        <button onclick="bulkDeleteSelected()" class="sm-btn" style="background: #dc2626; font-size: 11.5px; padding: 5px 16px; width: auto; height: 32px; border-radius: 8px;">حذف المحدد نهائياً</button>
    </div>

    <!-- 3. Records Table Card -->
    <div style="background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 24px;">

        <!-- Table Top Control Bar -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #ffffff;">
            <!-- Left Header Info -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: 800; font-size: 15px; color: #0f172a;">قائمة الطلاب المسجلين</span>
                <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #fef2f2; color: #881337; border-radius: 12px; font-size: 12px; font-weight: 800; border: 1px solid #fecdd3;">
                    <?php echo $total_students_count; ?> طالب
                </span>
            </div>

            <!-- Page Limit Selector -->
            <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; font-weight: 600;">
                <span>عرض بالسفرة:</span>
                <select id="stu_page_limit_select" onchange="changeStudentPageLimit(this.value)" style="height: 36px; padding: 0 24px 0 10px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12.5px; color: #334155; font-weight: 700; outline: none; background: #f8fafc; cursor: pointer;">
                    <option value="10" <?php selected($limit == 10); ?>>10 طلاب</option>
                    <option value="25" <?php selected($limit == 25); ?>>25 طالب</option>
                    <option value="50" <?php selected($limit == 50); ?>>50 طالب</option>
                    <option value="100" <?php selected($limit == 100); ?>>100 طالب</option>
                </select>
            </div>
        </div>

        <!-- Table Responsive Wrapper -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="width: 40px; text-align: center; padding: 14px 10px; border-bottom: 2px solid #e2e8f0;"><input type="checkbox" onclick="toggleAllStudents(this)"></th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 32%;">بيانات الطالب والهوية والنقاط</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 24%;">التسكين الأكاديمي والمدرسة</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 24%;">المعلم المربّي / ولي الأمر</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: center; width: 20%;">الإجراءات الإدارية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginated_students)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 50px 20px; font-weight: 700;">لا يوجد طلاب يطابقون شروط البحث حالياً في قاعدة البيانات.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginated_students as $student): ?>
                            <tr id="stu-row-<?php echo $student->id; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="text-align: center; padding: 14px 10px;"><input type="checkbox" class="student-checkbox" value="<?php echo $student->id; ?>" onchange="updateStudentBulkToolbar()"></td>

                                <!-- Student Cell (Data, Identity & Points Capsules) -->
                                <td style="padding: 14px 18px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if (!empty($student->photo_url)): ?>
                                            <img src="<?php echo esc_url($student->photo_url); ?>" style="width: 44px; height: 44px; border-radius: 50% !important; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                                        <?php else: ?>
                                            <div style="background: #f1f5f9; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 1px solid #e2e8f0; font-size: 18px;">
                                                <span class="dashicons dashicons-admin-users" style="font-size: 20px; width:20px; height:20px;"></span>
                                            </div>
                                        <?php endif; ?>
                                        <div style="line-height: 1.4;">
                                            <a href="javascript:void(0)" data-student="<?php echo esc_attr(json_encode($student)); ?>" onclick="openUnifiedProfileModal(this)" style="font-weight: 800; font-size: 14px; color: #0f172a; text-decoration: none;" onmouseover="this.style.color='#881337'" onmouseout="this.style.color='#0f172a'" title="اضغط لتعديل بيانات الطالب">
                                                <?php echo esc_html($student->name); ?>
                                            </a>
                                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; flex-wrap: wrap;">
                                                <!-- Academic Code -->
                                                <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; border-radius: 6px; font-size: 11px; font-weight: 800; font-family: monospace;">
                                                    <?php echo esc_html($student->student_code); ?>
                                                </span>
                                                <?php if (!empty($student->national_id)): ?>
                                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f1f5f9; color: #475569; border-radius: 6px; font-size: 10.5px; font-weight: 700; font-family: monospace;">
                                                        <?php echo esc_html($student->national_id); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($student->nationality)): ?>
                                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #fffaf0; color: #9a3412; border: 1px solid #ffedd5; border-radius: 6px; font-size: 10.5px; font-weight: 700;">
                                                        <?php echo esc_html($student->nationality); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <!-- Behavior Points Pastel Badge -->
                                                <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 800; background: <?php echo $student->behavior_points > 10 ? '#fee2e2' : ($student->behavior_points > 4 ? '#fef3c7' : '#dcfce7'); ?>; color: <?php echo $student->behavior_points > 10 ? '#dc2626' : ($student->behavior_points > 4 ? '#d97706' : '#15803d'); ?>;">
                                                    <?php echo intval($student->behavior_points); ?> نقطة
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Academic Placement Cell (Institution / School, Grade, Section) -->
                                <td style="padding: 14px 18px;">
                                    <?php
                                    $inst_obj = !empty($student->institution_id) ? EESS_Org_Helper::get_institution_by_id($student->institution_id) : null;
                                    $sch_obj = !empty($student->school_id) ? EESS_Org_Helper::get_school_by_id($student->school_id) : null;
                                    $sch_name = $inst_obj ? $inst_obj->name : ($sch_obj ? $sch_obj->name : ($student->school_name ?? 'المؤسسة الرئيسية'));
                                    ?>
                                    <div style="font-weight: 700; font-size: 11px; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-bank" style="font-size: 13px; width: 13px; height: 13px; color: #881337;"></span>
                                        <span><?php echo esc_html($sch_name); ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                            <?php echo esc_html($student->class_name); ?>
                                        </span>
                                        <?php if (!empty($student->section)): ?>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                                شعبة <?php echo esc_html($student->section); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Homeroom Teacher & Parent/Guardian Cell -->
                                <td style="padding: 14px 18px;">
                                    <?php
                                    $teacher = $student->teacher_id ? get_userdata($student->teacher_id) : null;
                                    $parent_user = $student->parent_user_id ? get_userdata($student->parent_user_id) : null;
                                    $t_phone = $teacher ? get_user_meta($teacher->ID, 'sm_phone', true) : '';
                                    if (empty($t_phone) && $teacher) $t_phone = get_user_meta($teacher->ID, 'phone_number', true);
                                    ?>
                                    <div style="font-weight: 800; font-size: 12.5px; color: #0f172a; display: flex; align-items: center; gap: 4px;">
                                        <span style="color:#64748b; font-size:11px;">المعلم:</span>
                                        <span><?php echo $teacher ? esc_html($teacher->display_name) : '<span style="color:#cbd5e1; font-style:italic;">غير معيّن</span>'; ?></span>
                                    </div>
                                    <div style="font-weight: 700; font-size: 11.5px; color: #334155; margin-top: 3px; display: flex; align-items: center; gap: 4px;">
                                        <span style="color:#64748b; font-size:11px;">ولي الأمر:</span>
                                        <span><?php echo $parent_user ? esc_html($parent_user->display_name) : (!empty($student->parent_email) ? esc_html($student->parent_email) : '<span style="color:#cbd5e1; font-style:italic;">غير مدخل</span>'); ?></span>
                                    </div>
                                    <?php if (!empty($student->guardian_phone)): ?>
                                        <div style="font-size: 11px; color: #475569; font-weight: 700; margin-top: 3px; display: flex; align-items: center; gap: 4px;">
                                            <span style="color:#64748b; font-size:11px;">هاتف ولي الأمر (Parent Phone):</span>
                                            <span style="font-family: monospace; font-weight: 800; color: #0f172a;"><?php echo esc_html($student->guardian_phone); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Circular Action Buttons -->
                                <td style="padding: 14px 18px; text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <?php if (!empty($student->guardian_phone)):
                                            $clean_wa_phone = preg_replace('/[^0-9]/', '', $student->guardian_phone);
                                        ?>
                                            <!-- Parent WhatsApp Direct Link Button -->
                                            <a href="https://wa.me/<?php echo esc_attr($clean_wa_phone); ?>" target="_blank" title="التواصل الفوري مع ولي الأمر عبر واتساب (WhatsApp)" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                                <span class="dashicons dashicons-whatsapp" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Print Student Exit Card Button -->
                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_card&student_id=' . $student->id); ?>" target="_blank" title="طباعة بطاقة تصريح الخروج (Student Exit Card)" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #eff6ff; color: #1d4ed8; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                            <span class="dashicons dashicons-id" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                        </a>

                                        <!-- Report Full PDF Button -->
                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_student_full_report&student_id=' . $student->id); ?>" target="_blank" title="التقرير الشامل للطالب (PDF)" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #dcfce7; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                            <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                        </a>

                                        <!-- Student Account Actions Centered System Modal Trigger -->
                                        <button type="button" onclick="eessOpenStudentAccountActionsModal(<?php echo $student->id; ?>, '<?php echo esc_js($student->name); ?>', '<?php echo esc_js($student->student_status ?: 'Active'); ?>')" title="إجراءات وسجل حساب الطالب الرقمي" class="sm-action-btn" style="background: #f8fafc; color: #334155; width: 36px; height: 36px; border-radius: 50% !important; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;">
                                            <span class="dashicons dashicons-admin-network" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                        </button>

                                        <?php if ($is_admin): ?>
                                            <!-- Edit Student Button -->
                                            <button type="button" data-student="<?php echo esc_attr(json_encode($student)); ?>" onclick="openUnifiedProfileModal(this)" title="تعديل الطالب" class="sm-action-btn sm-action-btn-warning" style="width: 36px; height: 36px; border-radius: 50% !important;">
                                                <span class="dashicons dashicons-edit"></span>
                                            </button>

                                            <!-- Delete Student Button -->
                                            <button type="button" onclick="confirmDeleteStudent(<?php echo $student->id; ?>, '<?php echo esc_js($student->name); ?>')" title="حذف الطالب نهائياً" class="sm-action-btn sm-action-btn-danger" style="width: 36px; height: 36px; border-radius: 50% !important;">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Server-Side Pagination Footer matching Student Behavior Records -->
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">
                عرض <span style="color: #0f172a; font-weight: 800;"><?php echo $from_num; ?></span> - <span style="color: #0f172a; font-weight: 800;"><?php echo $to_num; ?></span> من إجمالي <span style="color: #0f172a; font-weight: 800;"><?php echo $total_students_count; ?></span> طالب
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <?php
                $prev_disabled = ($paged <= 1);
                $next_disabled = ($paged >= $total_pages);
                $base_url = remove_query_arg(['paged']);
                ?>
                <a href="<?php echo add_query_arg('paged', 1, $base_url); ?>" <?php if ($prev_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">الأولى</a>
                <a href="<?php echo add_query_arg('paged', max(1, $paged - 1), $base_url); ?>" <?php if ($prev_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">السابق</a>

                <div style="display: flex; gap: 4px;">
                    <?php
                    $start_p = max(1, $paged - 2);
                    $end_p = min($total_pages, $paged + 2);
                    for ($p = $start_p; $p <= $end_p; $p++):
                        $is_active = ($p == $paged);
                    ?>
                        <a href="<?php echo add_query_arg('paged', $p, $base_url); ?>" class="sm-btn" style="height: 36px; min-width: 36px; padding: 0 8px; border-radius: 8px; border: 1px solid <?php echo $is_active ? '#881337' : '#cbd5e1'; ?>; background: <?php echo $is_active ? '#881337' : '#ffffff'; ?>; color: <?php echo $is_active ? '#ffffff' : '#334155'; ?>; font-size: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>

                <a href="<?php echo add_query_arg('paged', min($total_pages, $paged + 1), $base_url); ?>" <?php if ($next_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">التالي</a>
                <a href="<?php echo add_query_arg('paged', $total_pages, $base_url); ?>" <?php if ($next_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">الأخيرة</a>
            </div>
        </div>
    </div>

    <!-- EXIT PERMIT REQUESTS REVIEW MODAL -->
    <div id="eess-exit-card-requests-modal" class="sm-modal-overlay" style="display: none;">
        <div class="sm-modal-content" style="max-width: 1080px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <!-- Clean White Header Box with Black Icon & Black Text -->
            <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #e2e8f0; padding: 20px 24px; background: #ffffff; color: #000000;">
                <div>
                    <h3 style="margin: 0 0 4px 0; font-weight: 900; font-size: 17px; color: #000000; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-id-alt" style="font-size: 22px; width: 22px; height: 22px; color: #000000;"></span>
                        <span>نظام إدارة ومراجعة طلبات تصاريح الاستئذان المدرسية</span>
                    </h3>
                    <p style="margin: 0; font-size: 12px; color: #64748b; font-weight: 600;">
                        متابعة وتدقيق طلبات الاستئذان المعتمدة، معاينة التوقيعات الإلكترونية لولي الأمر، وطباعة النماذج والوثائق الرسمية.
                    </p>
                </div>
                <button class="sm-modal-close" onclick="document.getElementById('eess-exit-card-requests-modal').style.display='none'" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; color: #000000; font-weight: bold; font-size: 24px; border: none; background: transparent; cursor: pointer;">&times;</button>
            </div>

            <div class="sm-modal-body" style="max-height: 80vh; overflow-y: auto; padding: 24px; background: #f8fafc;">

                <?php
                $settings = get_option('sm_exit_card_settings', array('max_requests' => 3, 'redirect_discipline' => 'yes'));
                $max_reqs_val = intval($settings['max_requests'] ?? 3);
                $redirect_val = $settings['redirect_discipline'] ?? 'yes';
                ?>
                <!-- Filter & Settings Controls -->
                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                    <div style="display: flex; gap: 10px; flex: 1; min-width: 280px;">
                        <input type="text" id="er_search_q" onkeyup="eessFilterExitCardTable()" placeholder="ابحث باسم الطالب، الكود، الرقم المرجعي، أو ولي الأمر..." style="flex: 1; height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; font-weight: 600; outline: none;">
                        <select id="er_filter_status" onchange="eessFilterExitCardTable()" style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; font-weight: 800; background: #ffffff; cursor: pointer;">
                            <option value="">جميع الحالات</option>
                            <option value="submitted">تم تقديم الطلب</option>
                            <option value="under_review">قيد المراجعة والتدقيق</option>
                            <option value="parent_confirmation">بانتظار تأكيد ولي الأمر</option>
                            <option value="approved">تمت الموافقة الرسمية</option>
                            <option value="preparing">جاري تجهيز/طباعة البطاقة</option>
                            <option value="issued">تم الإصدار والتسليم</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                    <?php if ($is_admin): ?>
                    <button type="button" onclick="document.getElementById('eess-exit-settings-box').style.display = document.getElementById('eess-exit-settings-box').style.display === 'none' ? 'block' : 'none';" class="sm-btn" style="background: #000000; color: white !important; font-size: 11.5px; padding: 0 16px; height: 40px; font-weight: 800; border-radius: 10px;">⚙️ إعدادات الضوابط والحد الأقصى</button>
                    <?php endif; ?>
                </div>

                <!-- Admin Settings Panel -->
                <div id="eess-exit-settings-box" style="display: none; background: #fffbe3; border: 1px solid #fde047; border-radius: 14px; padding: 16px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 800; color: #854d0e;">⚙️ إعدادات وضوابط تصاريح الاستئذان المدرسية</h4>
                    <form onsubmit="eessSaveExitSettings(event)" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700; color: #713f12; display: block; margin-bottom: 3px;">الحد الأقصى للطلبات المسموحة سنوياً:</label>
                            <input type="number" id="cfg_max_requests" value="<?php echo $max_reqs_val; ?>" min="1" max="20" style="width: 85px; height: 36px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 8px; font-weight: 800;">
                        </div>
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700; color: #713f12; display: block; margin-bottom: 3px;">توجيه تجاوز الحد لمكتب السلوك والانضباط:</label>
                            <select id="cfg_redirect_discipline" style="height: 36px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-weight: 800; background: white;">
                                <option value="yes" <?php selected($redirect_val, 'yes'); ?>>نعم - توجيه لمكتب الانضباط وسلوك الطلاب</option>
                                <option value="no" <?php selected($redirect_val, 'no'); ?>>لا - رفض الطلب تلقائياً</option>
                            </select>
                        </div>
                        <button type="submit" class="sm-btn" style="height: 36px; padding: 0 20px; background: #854d0e; color: white !important; font-size: 12px; font-weight: 800; border-radius: 8px; margin-top: 14px;">حفظ الضوابط</button>
                    </form>
                </div>

                <?php
                $all_exit_reqs = $wpdb->get_results("SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.photo_url FROM {$wpdb->prefix}sm_exit_card_requests r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id ORDER BY r.created_at DESC LIMIT 100");
                if (empty($all_exit_reqs)): ?>
                    <div style="text-align: center; color: #94a3b8; padding: 50px 20px; font-weight: 700; background: white; border-radius: 16px; border: 1px solid #e2e8f0;">
                        لا توجد طلبات تصاريح استئذان مسجلة حالياً.
                    </div>
                <?php else: ?>
                    <!-- Professional 2-Column Responsive Request Cards Grid -->
                    <div id="eess_exit_req_cards_grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                        <?php foreach ($all_exit_reqs as $er):
                            $ref_disp = $er->reference_no ?: ('EXT-' . date('Y') . '-' . $er->id);
                            $status_lbl = SM_Public::eess_get_exit_card_status_label($er->status);
                            $status_bg = $er->status === 'approved' || $er->status === 'issued' ? '#dcfce7' : ($er->status === 'rejected' ? '#fee2e2' : '#fef3c7');
                            $status_fg = $er->status === 'approved' || $er->status === 'issued' ? '#15803d' : ($er->status === 'rejected' ? '#dc2626' : '#d97706');
                            $search_haystack = strtolower(($er->student_name ?? '') . ' ' . $er->student_code . ' ' . $ref_disp . ' ' . ($er->parent_name ?? ''));
                            $stu_photo = !empty($er->photo_url) ? $er->photo_url : "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTMiIHN0eWxlPSJiYWNrZ3JvdW5kOiNmMWY1Zjk7IGJvcmRlci1yYWRpdXM6NTAlOyI+PHBhdGggZD0iTTEyIDEyYzIuMjEgMCA0LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg==";
                        ?>
                            <div class="er-row-item" data-search="<?php echo esc_attr($search_haystack); ?>" data-status="<?php echo esc_attr($er->status); ?>" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; gap: 12px;">

                                <!-- Card Header: Photo + Reference & Status Badges -->
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="<?php echo esc_url($stu_photo); ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e1; background: #f1f5f9;" alt="Student Photo">
                                            <div>
                                                <div style="font-family: monospace; font-weight: 900; color: #881337; font-size: 11px;"><?php echo esc_html($ref_disp); ?></div>
                                                <strong style="font-size: 14.5px; color: #0f172a; display: block; line-height: 1.3;"><?php echo esc_html($er->student_name); ?></strong>
                                                <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-top: 1px;">
                                                    كود: <span style="font-family: monospace; font-weight: 800; color: #0f172a;"><?php echo esc_html($er->student_code); ?></span> | <?php echo esc_html($er->class_name); ?> (<?php echo esc_html($er->section); ?>)
                                                </div>
                                            </div>
                                        </div>
                                        <span style="display:inline-block; padding:3px 10px; border-radius:9999px; font-size:10.5px; font-weight:800; background:<?php echo $status_bg; ?>; color:<?php echo $status_fg; ?>; flex-shrink: 0;">
                                            <?php echo esc_html($status_lbl); ?>
                                        </span>
                                    </div>

                                    <!-- Parent & Details Info Box -->
                                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 10px 12px; font-size: 11.5px; color: #334155; line-height: 1.5; margin-bottom: 4px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                            <span><strong>ولي الأمر:</strong> <?php echo esc_html($er->parent_name ?: 'غير مدخل'); ?></span>
                                            <span style="font-family: monospace; font-weight: 700; color: #0f172a;"><?php echo esc_html($er->parent_phone ?: '---'); ?></span>
                                        </div>
                                        <div><strong>تاريخ الطلب:</strong> <span style="font-family: monospace; font-weight: 700; color: #475569;"><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($er->created_at))); ?></span></div>
                                        <?php if (!empty($er->signature_data)): ?>
                                            <div style="color: #16a34a; font-weight: 800; font-size: 10.5px; margin-top: 3px;">✓ متوفر توقيع إلكتروني معتمد لولي الأمر</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Card Footer Action Controls -->
                                <div style="border-top: 1px solid #f1f5f9; padding-top: 10px; display: flex; gap: 6px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" onclick="eessInspectExitCardDetails(<?php echo $er->id; ?>)" class="sm-btn" style="background: #0f172a; color: white !important; font-size: 11px; padding: 0 10px; height: 30px; font-weight: 800; border-radius: 6px;">👁️ معاينة وتدقيق</button>
                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=exit_permit_request&request_id=' . $er->id); ?>" target="_blank" class="sm-btn" style="background: #16a34a; color: white !important; font-size: 11px; padding: 0 10px; height: 30px; font-weight: 800; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center;">📄 نموذج A4 الرسمي</a>
                                    </div>
                                    <select onchange="eessUpdateExitReqStatus(<?php echo $er->id; ?>, this.value)" style="height: 30px; font-size: 11px; font-weight: 800; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0 6px; background: white;">
                                        <option value="" disabled selected>تغيير الحالة...</option>
                                        <option value="under_review">قيد المراجعة والتدقيق</option>
                                        <option value="parent_confirmation">تأكيد ولي الأمر</option>
                                        <option value="approved">اعتماد وموافقة</option>
                                        <option value="preparing">جاري التجهيز والطباعة</option>
                                        <option value="issued">إصدار وتسليم نهائي</option>
                                        <option value="rejected">رفض الطلب</option>
                                    </select>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- EXIT CARD DETAILS INSPECTION DRAWER MODAL -->
    <div id="eess-exit-card-inspect-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.7); z-index: 100000; backdrop-filter: blur(4px);">
        <div style="max-width: 650px; width: 92%; margin: 40px auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="padding: 16px 20px; background: #0f172a; color: white; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 900; color: white;" id="insp_title">تفاصيل وسجل طلب تصريح الخروج</h3>
                <button onclick="document.getElementById('eess-exit-card-inspect-modal').style.display='none'" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 20px; max-height: 75vh; overflow-y: auto;" id="insp_body">
                <div style="text-align: center; padding: 30px; font-weight: 700; color: #64748b;">جاري جلب تفاصيل الطلب... ⏳</div>
            </div>
        </div>
    </div>

    <script>
    function eessFilterExitCardTable() {
        const q = (document.getElementById('er_search_q').value || '').toLowerCase().trim();
        const stat = (document.getElementById('er_filter_status').value || '').trim();

        document.querySelectorAll('.er-row-item').forEach(row => {
            const haystack = row.getAttribute('data-search') || '';
            const rowStat  = row.getAttribute('data-status') || '';

            const matchQ    = !q || haystack.includes(q);
            const matchStat = !stat || rowStat === stat;

            if (matchQ && matchStat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function eessInspectExitCardDetails(reqId) {
        const modal = document.getElementById('eess-exit-card-inspect-modal');
        const body  = document.getElementById('insp_body');
        modal.style.display = 'block';
        body.innerHTML = '<div style="text-align: center; padding: 30px; font-weight: 700; color: #64748b;">جاري جلب تفاصيل الطلب والتوقيع... ⏳</div>';

        const formData = new FormData();
        formData.append('action', 'sm_get_exit_card_request_details');
        formData.append('request_id', reqId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                let html = '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:12px; color:#334155; line-height:1.6; margin-bottom:14px; background:#f8fafc; padding:14px; border-radius:12px; border:1px solid #e2e8f0;">';
                html += '<div><strong>الرقم المرجعي:</strong> <span style="font-family:monospace; font-weight:900; color:#881337;">' + d.reference_no + '</span></div>';
                html += '<div><strong>الحالة الحالية:</strong> <span style="font-weight:900; color:#15803d;">' + d.status_label + '</span></div>';
                html += '<div><strong>اسم الطالب:</strong> <strong style="color:#0f172a;">' + d.student_name + '</strong></div>';
                html += '<div><strong>الكود والصف:</strong> ' + d.student_code + ' | ' + d.class_name + ' (' + d.section + ')</div>';
                html += '<div><strong>اسم ولي الأمر:</strong> ' + d.parent_name + '</div>';
                html += '<div><strong>هاتف ولي الأمر:</strong> <span style="font-family:monospace;">' + d.parent_phone + '</span></div>';
                html += '<div><strong>سبب الاستئذان:</strong> ' + d.reason + '</div>';
                html += '<div><strong>تاريخ التقديم:</strong> <span style="font-family:monospace;">' + d.created_at + '</span></div>';
                html += '</div>';

                if (d.declaration_accepted) {
                    html += '<div style="background:#f0fdf4; border:1px solid #86efac; border-radius:10px; padding:10px; font-size:11.5px; color:#166534; font-weight:700; margin-bottom:14px;">✓ تمت الموافقة الإلكترونية على الإقرار والتعهد الرسمي لولي الأمر.</div>';
                }

                if (d.signature_data) {
                    html += '<div style="margin-bottom:14px;">';
                    html += '<div style="font-size:12px; font-weight:800; color:#0f172a; margin-bottom:4px;">معاينة التوقيع الإلكتروني المعتمد لولي الأمر:</div>';
                    html += '<div style="background:white; border:1px solid #cbd5e1; border-radius:10px; padding:10px; text-align:center;">';
                    html += '<img src="' + d.signature_data + '" style="max-height:80px; object-fit:contain;" alt="Signature">';
                    html += '</div>';
                    html += '</div>';
                }

                if (d.history && d.history.length > 0) {
                    html += '<div>';
                    html += '<div style="font-size:12px; font-weight:800; color:#0f172a; margin-bottom:6px;">سجل الطلبات السابقة لنفس العام الدراسي (' + d.history.length + ' طلبات):</div>';
                    html += '<div style="display:flex; flex-direction:column; gap:6px;">';
                    d.history.forEach(h => {
                        html += '<div style="background:#f1f5f9; padding:6px 10px; border-radius:6px; font-size:11px; display:flex; justify-content:space-between;">';
                        html += '<span><strong>' + h.reference_no + '</strong> (' + h.status + ')</span>';
                        html += '<span style="font-family:monospace; color:#64748b;">' + h.created_at + '</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                    html += '</div>';
                }

                body.innerHTML = html;
            } else {
                body.innerHTML = '<div style="color:#dc2626; font-weight:800; text-align:center;">تعذر جلب تفاصيل الطلب.</div>';
            }
        });
    }

    function eessSaveExitSettings(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'sm_save_exit_card_settings');
        formData.append('max_requests', document.getElementById('cfg_max_requests').value);
        formData.append('redirect_discipline', document.getElementById('cfg_redirect_discipline').value);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (typeof smShowNotification === 'function') smShowNotification(res.data.message);
                document.getElementById('eess-exit-settings-box').style.display = 'none';
            }
        });
    }

    function eessUpdateExitReqStatus(reqId, status) {
        const formData = new FormData();
        formData.append('action', 'sm_update_exit_card_request_status');
        formData.append('request_id', reqId);
        formData.append('status', status);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (typeof smShowNotification === 'function') smShowNotification(res.data.message);
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function eessMarkRequestPrinted(reqId) {
        const formData = new FormData();
        formData.append('action', 'sm_update_exit_card_request_status');
        formData.append('request_id', reqId);
        formData.append('printing_status', 'printed');
        formData.append('status', 'preparing');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData });
    }
    </script>

    <!-- UNIFIED REUSABLE STUDENT PROFILE EDIT MODAL COMPONENT -->
    <?php if ($is_admin): ?>
        <?php include SM_PLUGIN_DIR . 'templates/partials/student-profile-edit-modal.php'; ?>
    <?php endif; ?>

    <!-- VIEW STUDENT RECORD MODAL -->
    <div id="view-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 900px; background: white;">
            <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <h3 style="margin:0; font-weight:800; font-size: 15px;">الملف السلوكي والتحليلي التفصيلي للطالب</h3>
                <div style="display:flex; gap:10px;">
                    <button id="print-full-record-btn" class="sm-btn" style="background:#15803d; width:auto; font-size:11px; height:28px; display:inline-flex; align-items:center; gap:4px;">
                        <span class="dashicons dashicons-printer" style="font-size:14px; width:14px; height:14px;"></span>
                        <span>طباعة الملف بالكامل</span>
                    </button>
                    <button class="sm-modal-close" onclick="document.getElementById('view-student-modal').style.display='none'" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center;">&times;</button>
                </div>
            </div>
            <div class="sm-modal-body" id="stu_details_content" style="max-height: 70vh; overflow-y: auto;">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- CENTERED STUDENT DIGITAL ACCOUNT ACTIONS SYSTEM MODAL -->
    <div id="eess-stu-account-actions-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
        <div class="sm-modal-content" style="max-width: 500px; width: 90%; border-radius: 20px; padding: 24px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); font-family: 'Cairo', sans-serif;" dir="rtl">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-admin-network" style="font-size: 20px; width: 20px; height: 20px;"></span>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a;">إجراءات الحساب الرقمي للطالب</h3>
                        <div id="eess_stu_actions_modal_subtitle" style="font-size: 11.5px; color: #64748b; font-weight: 700;"></div>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('eess-stu-account-actions-modal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Action 1: Restrict / Disable Account -->
                <div id="eess_act_restrict_btn" style="padding: 12px; border-radius: 12px; border: 1px solid #fee2e2; background: #fff5f5; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;" onmouseover="this.style.borderColor='#fca5a5'" onmouseout="this.style.borderColor='#fee2e2'">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="dashicons dashicons-lock" style="font-size: 18px; width: 18px; height: 18px;"></span>
                    </div>
                    <div>
                        <div style="font-size: 13px; font-weight: 800; color: #991b1b;">تقييد / تعطيل حساب الطالب</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">إيقاف صلاحيات الدخول للمنظومة مؤقتاً وحظر الحساب</div>
                    </div>
                </div>

                <!-- Action 2: Password Change Request -->
                <div id="eess_act_passreq_btn" style="padding: 12px; border-radius: 12px; border: 1px solid #dbeafe; background: #f0f9ff; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;" onmouseover="this.style.borderColor='#93c5fd'" onmouseout="this.style.borderColor='#dbeafe'">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="dashicons dashicons-key" style="font-size: 18px; width: 18px; height: 18px;"></span>
                    </div>
                    <div>
                        <div style="font-size: 13px; font-weight: 800; color: #1e40af;">طلب إعادة ضبط كلمة المرور</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">إلزام الطالب بتعيين كلمة جديدة عند الدخول القادم</div>
                    </div>
                </div>

                <!-- Action 3: Direct Message to Student -->
                <div id="eess_act_msg_btn" style="padding: 12px; border-radius: 12px; border: 1px solid #dcfce7; background: #f0fdf4; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;" onmouseover="this.style.borderColor='#86efac'" onmouseout="this.style.borderColor='#dcfce7'">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="dashicons dashicons-email-alt" style="font-size: 18px; width: 18px; height: 18px;"></span>
                    </div>
                    <div>
                        <div style="font-size: 13px; font-weight: 800; color: #166534;">إرسال رسالة توجيهية إدارية</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">إشعار إجباري بصفحة الدخول يتطلب موافقة وإقرار إلكتروني</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 18px; text-align: left;">
                <button type="button" onclick="document.getElementById('eess-stu-account-actions-modal').style.display='none'" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 36px; padding: 0 20px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer;">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- RESTRICT STUDENT ACCOUNT CONFIRMATION MODAL -->
    <div id="eess-restrict-account-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
        <div class="sm-modal-content" style="max-width: 480px; border-radius: 20px; padding: 28px 32px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); font-family: 'Cairo', sans-serif;" dir="rtl">
            <div style="text-align: center; margin-bottom: 18px;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #fee2e2; color: #dc2626; border: 1px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                    <span class="dashicons dashicons-lock" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
                <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a;">تأكيد تقييد / تعطيل حساب الطالب</h3>
                <p id="eess-restrict-modal-msg" style="margin: 0; font-size: 13px; color: #475569; font-weight: 700; line-height: 1.6;"></p>
            </div>
            <input type="hidden" id="eess_restrict_stu_id">
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button type="button" onclick="eessConfirmRestrictAccountSubmit()" class="sm-btn" style="background: #dc2626; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 8px; font-weight: 800; border: none; cursor: pointer;">تأكيد تقييد الحساب</button>
                <button type="button" onclick="document.getElementById('eess-restrict-account-modal').style.display='none'" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer;">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- REQUEST PASSWORD CHANGE CONFIRMATION MODAL -->
    <div id="eess-password-request-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
        <div class="sm-modal-content" style="max-width: 480px; border-radius: 20px; padding: 28px 32px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); font-family: 'Cairo', sans-serif;" dir="rtl">
            <div style="text-align: center; margin-bottom: 18px;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                    <span class="dashicons dashicons-key" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
                <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a;">إرسال طلب تغيير كلمة المرور</h3>
                <p id="eess-pass-req-modal-msg" style="margin: 0; font-size: 13px; color: #475569; font-weight: 700; line-height: 1.6;"></p>
            </div>
            <input type="hidden" id="eess_pass_req_stu_id">
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button type="button" onclick="eessConfirmPasswordRequestSubmit()" class="sm-btn" style="background: #2563eb; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 8px; font-weight: 800; border: none; cursor: pointer;">إرسال الطلب</button>
                <button type="button" onclick="document.getElementById('eess-password-request-modal').style.display='none'" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer;">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- SEND MESSAGE TO STUDENT MODAL -->
    <div id="eess-send-message-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
        <div class="sm-modal-content" style="max-width: 520px; border-radius: 20px; padding: 28px 32px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); font-family: 'Cairo', sans-serif;" dir="rtl">
            <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-email-alt" style="color: #16a34a;"></span>
                    <span id="eess-send-msg-modal-title">إرسال رسالة رسمية للطالب</span>
                </h3>
                <button type="button" onclick="document.getElementById('eess-send-message-modal').style.display='none'" style="background: none; border: none; font-size: 22px; color: #64748b; cursor: pointer;">&times;</button>
            </div>

            <form id="eess-student-direct-message-form">
                <input type="hidden" id="eess_send_msg_stu_id">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">نص الرسالة الرسمية: <span style="color: #dc2626;">*</span></label>
                    <textarea id="eess_stu_message_text" required rows="4" placeholder="اكتب الرسالة الموجهة للطالب هنا... ستظهر للطالب بصفحة دخوله ويلزم تأكيد قراءتها." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; box-sizing: border-box; font-family: 'Cairo', sans-serif; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="sm-btn" style="background: #16a34a; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 8px; font-weight: 800; border: none; cursor: pointer;">إرسال الرسالة</button>
                    <button type="button" onclick="document.getElementById('eess-send-message-modal').style.display='none'" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE STUDENT MODAL (CLEAN WHITE DESIGN) -->
    <div id="delete-student-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
        <div class="sm-modal-content" style="max-width: 500px; border-radius: 20px; padding: 32px 36px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); font-family: 'Cairo', sans-serif;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #fef2f2; color: #dc2626; border: 1px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <span class="dashicons dashicons-trash" style="font-size: 26px; width: 26px; height: 26px;"></span>
                </div>
                <h3 id="delete-modal-title" style="margin: 0 0 8px 0; font-size: 17px; font-weight: 800; color: #0f172a;">تأكيد حذف الطالب نهائياً</h3>
                <p id="delete-confirm-msg" style="margin: 0; font-size: 13.5px; color: #475569; font-weight: 700; line-height: 1.6;"></p>
            </div>
            <form id="delete-student-form">
                <input type="hidden" id="confirm_delete_stu_id">
                <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
                    <button type="submit" class="sm-btn" style="background: #dc2626; color: #ffffff; height: 38px; padding: 0 24px; border-radius: 8px; font-weight: 800; border: none; cursor: pointer;">نعم، حذف السجل نهائياً</button>
                    <button type="button" onclick="document.getElementById('delete-student-modal').style.display='none'" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 20px; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- STUDENT CREDENTIALS MODAL -->
    <div id="student-creds-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 450px;">
            <div class="sm-modal-header">
                <h3>بيانات الدخول الأكاديمية للطالب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('student-creds-modal').style.display='none'">&times;</button>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:20px; border-radius:12px; margin-bottom:20px; line-height:1.8;">
                <div style="font-weight:700; font-size:14px; color:#881337; border-bottom:1px solid #eee; padding-bottom:8px; margin-bottom:15px;" id="cred-stu-name"></div>
                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:10px;">
                    <span style="color:#718096;">اسم المستخدم (كود الطالب):</span>
                    <strong style="font-family:monospace;" id="cred-username"></strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:10px;">
                    <span style="color:#718096;">كلمة المرور الافتراضية:</span>
                    <strong style="font-family:monospace;" id="cred-password"></strong>
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <a id="cred-download-link" href="#" target="_blank" class="sm-btn" style="background:#881337; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; width:auto; height:36px; padding:0 20px; font-size:12px; gap:6px;">
                    <span class="dashicons dashicons-download" style="font-size:14px; width:14px; height:14px;"></span>
                    <span>تحميل بطاقة الدخول (PDF)</span>
                </a>
                <button onclick="document.getElementById('student-creds-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width:auto; height:36px; padding:0 15px; font-size:12px;">إغلاق</button>
            </div>
        </div>
    </div>

    <script>
    function changeStudentPageLimit(limitVal) {
        const url = new URL(window.location.href);
        url.searchParams.set('limit', limitVal);
        url.searchParams.set('paged', 1);
        window.location.href = url.toString();
    }

    function openAddStudentWizard() {
        // Reset form for Add Mode
        const form = document.getElementById('edit-student-form');
        if (form) form.reset();

        if (document.getElementById('edit_stu_id')) document.getElementById('edit_stu_id').value = '0';
        if (document.getElementById('edit-modal-title-text')) {
            document.getElementById('edit-modal-title-text').innerText = 'إضافة طالب جديد في المنظومة';
        }

        if (typeof goUnifiedEditStep === 'function') goUnifiedEditStep(1);
        else if (typeof goEditStep === 'function') goEditStep(1);
        const modal = document.getElementById('edit-student-modal');
        if (modal) modal.style.display = 'flex';
    }

    function openUnifiedProfileModal(btnOrObj) {
        let s = btnOrObj;
        if (btnOrObj && btnOrObj.dataset && btnOrObj.dataset.student) {
            try {
                s = JSON.parse(btnOrObj.dataset.student);
            } catch(e) {
                console.error('Failed to parse student data', e);
            }
        } else if (typeof btnOrObj === 'string') {
            try {
                s = JSON.parse(btnOrObj);
            } catch(e) {}
        }

        if (typeof window.editSmStudent === 'function') {
            window.editSmStudent(s);
        } else {
            const modal = document.getElementById('edit-student-modal');
            if (modal) modal.style.display = 'flex';
        }
    }

    function viewSmStudent(btnOrObj) {
        let student = btnOrObj;
        if (btnOrObj && btnOrObj.dataset && btnOrObj.dataset.student) {
            try {
                student = JSON.parse(btnOrObj.dataset.student);
            } catch(e) {}
        }

        const modal = document.getElementById('view-student-modal');
        const content = document.getElementById('stu_details_content');
        const printBtn = document.getElementById('print-full-record-btn');
        if (!modal || !content) return;

        content.innerHTML = '<div style="text-align:center; padding:50px;"><p style="font-weight:700; color:#718096;">جاري جلب الملف الانضباطي وتنسيقه...</p></div>';
        modal.style.display = 'flex';

        printBtn.onclick = function() {
            window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id, '_blank');
        };

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id)
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                doc.querySelectorAll('.no-print').forEach(el => el.remove());
                content.innerHTML = doc.body.innerHTML;
            });
    }

    function updateStudentBulkToolbar() {
        const selected = document.querySelectorAll('.student-checkbox:checked').length;
        const toolbar = document.getElementById('student-bulk-actions-toolbar');
        if (toolbar) {
            toolbar.style.display = selected > 0 ? 'flex' : 'none';
        }
    }

    // Chunked File Upload Progress Form
    let chunkedFile, chunkedSize, chunkedId, chunkedTotalParts, chunkedCurrentPart;
    const CHUNK_SIZE = 100 * 1024; // 100kb chunks

    window.startChunkedUpload = function() {
        const fileInput = document.getElementById('csv-file-input');
        if (fileInput.files.length === 0) {
            alert('يرجى تحديد ملف CSV أولاً.');
            return;
        }

        chunkedFile = fileInput.files[0];
        chunkedSize = chunkedFile.size;
        chunkedTotalParts = Math.ceil(chunkedSize / CHUNK_SIZE);
        chunkedCurrentPart = 0;

        document.getElementById('import-selection-area').style.display = 'none';
        document.getElementById('import-progress-area').style.display = 'block';
        updateImportProgress('جاري رفع وتحليل ملف البيانات...', 0);

        uploadNextChunk();
    };

    let totalDetectedRows = 0;

    function uploadNextChunk() {
        const start = chunkedCurrentPart * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, chunkedSize);
        const chunk = chunkedFile.slice(start, end);

        const formData = new FormData();
        formData.append('action', 'sm_upload_import_csv');
        formData.append('csv_file', chunk, chunkedFile.name);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                totalDetectedRows = res.data.total || 0;
                document.getElementById('imp-stat-total').innerText = totalDetectedRows;
                processImportChunk(res.data.file_path, 0, 0);
            } else {
                alert('فشل رفع الملف: ' + (res.data || 'خطأ غير معروف'));
                resetImportUI();
            }
        })
        .catch(err => {
            alert('حدث خطأ في الاتصال أثناء رفع الملف.');
            resetImportUI();
        });
    }

    function processImportChunk(filePath, offset, retryCount = 0) {
        const formData = new FormData();
        formData.append('action', 'sm_process_import_chunk');
        formData.append('file_path', filePath);
        formData.append('offset', offset);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const finished = res.data.finished;
                const totalRows = res.data.total_rows || totalDetectedRows;
                const processedSoFar = res.data.processed_so_far || 0;

                // Live Stats Update
                document.getElementById('imp-stat-total').innerText = totalRows;
                document.getElementById('imp-stat-success').innerText = res.data.success || 0;
                document.getElementById('imp-stat-duplicate').innerText = res.data.duplicate || 0;
                document.getElementById('imp-stat-error').innerText = res.data.error || 0;

                // Live Log Update
                if (res.data.details && res.data.details.length > 0) {
                    const box = document.getElementById('imp-details-box');
                    const list = document.getElementById('imp-details-list');
                    box.style.display = 'block';
                    list.innerHTML = '';
                    res.data.details.forEach(item => {
                        const li = document.createElement('li');
                        li.style.color = item.type === 'error' ? '#dc2626' : '#2563eb';
                        li.innerText = item.msg;
                        list.appendChild(li);
                    });
                }

                const pct = totalRows > 0 ? Math.min(100, Math.round((processedSoFar / totalRows) * 100)) : 0;

                if (finished) {
                    updateImportProgress('تم الانتهاء من استيراد ومعالجة كافة سجلات الملف!', 100);
                    document.getElementById('imp-finish-actions').style.display = 'block';
                } else {
                    updateImportProgress(`جاري معالجة الدفعة... تم معالجة ${processedSoFar} من أصل ${totalRows} طالب`, pct);
                    processImportChunk(filePath, processedSoFar, 0);
                }
            } else {
                if (retryCount < 3) {
                    updateImportProgress(`إعادة محاولة الاتصال بالخادم... (محاولة ${retryCount + 1}/3)`, Math.min(99, Math.round((offset / Math.max(1, totalDetectedRows)) * 100)));
                    setTimeout(() => processImportChunk(filePath, offset, retryCount + 1), 1500);
                } else {
                    alert('خطأ أثناء معالجة الدفعة: ' + (res.data || 'استجابة غير متوقعة من الخادم'));
                    document.getElementById('imp-finish-actions').style.display = 'block';
                }
            }
        })
        .catch(err => {
            if (retryCount < 3) {
                updateImportProgress(`خطأ مؤقت في الاتصال، جاري إعادة المحاولة... (${retryCount + 1}/3)`, Math.min(99, Math.round((offset / Math.max(1, totalDetectedRows)) * 100)));
                setTimeout(() => processImportChunk(filePath, offset, retryCount + 1), 1500);
            } else {
                alert('تعذر الاتصال بالخادم بعد عدة محاولات.');
                document.getElementById('imp-finish-actions').style.display = 'block';
            }
        });
    }

    function updateImportProgress(text, pct) {
        document.getElementById('import-status-text').innerText = text;
        document.getElementById('import-percentage').innerText = pct + '%';
        document.getElementById('import-progress-bar').style.width = pct + '%';
    }

    function resetImportUI() {
        document.getElementById('import-selection-area').style.display = 'block';
        document.getElementById('import-progress-area').style.display = 'none';
    }

    (function() {
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('eess-students-export-dropdown');
            if (dropdown && dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            }
        });

        window.showStudentCreds = function(user, pass, name, id) {
            document.getElementById('cred-username').innerText = user;
            document.getElementById('cred-password').innerText = pass;
            document.getElementById('cred-stu-name').innerText = name;
            document.getElementById('cred-download-link').href = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_credentials_card&student_id='); ?>' + id;
            document.getElementById('student-creds-modal').style.display = 'flex';
        };

        window.viewSmStudent = function(student) {
            const modal = document.getElementById('view-student-modal');
            const content = document.getElementById('stu_details_content');
            const printBtn = document.getElementById('print-full-record-btn');
            if (!modal || !content) return;
            
            content.innerHTML = '<div style="text-align:center; padding:50px;"><p style="font-weight:700; color:#718096;">جاري جلب الملف الانضباطي وتنسيقه...</p></div>';
            modal.style.display = 'flex';

            printBtn.onclick = function() {
                window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id, '_blank');
            };

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id)
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    doc.querySelectorAll('.no-print').forEach(el => el.remove());
                    content.innerHTML = doc.body.innerHTML;
                });
        };


        window.confirmDeleteStudent = function(id, name) {
            document.getElementById('confirm_delete_stu_id').value = id;
            document.getElementById('delete-confirm-msg').innerText = `تأكيد حذف الطالب "${name}" وكافة سجلاته المرتبطة نهائياً؟`;
            document.getElementById('delete-student-modal').style.display = 'flex';
        };

        const deleteForm = document.getElementById('delete-student-form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_delete_student_ajax');
                formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_student"); ?>');
                formData.append('student_id', document.getElementById('confirm_delete_stu_id').value);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم حذف الطالب');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

        // Do not override window.editSmStudent here; it is provided by student-profile-edit-modal.php

        window.toggleAllStudents = function(master) {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = master.checked);
            updateStudentBulkToolbar();
        };

        window.bulkPrintCardsSelected = function() {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert('يرجى اختيار طلاب أولاً'); return; }
            window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_card&student_ids='); ?>' + selected.join(','), '_blank');
        };

        window.bulkDeleteSelected = function() {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert('يرجى اختيار طلاب أولاً'); return; }
            if (!confirm(`هل أنت متأكد من حذف ${selected.length} طالب نهائياً؟`)) return;

            const formData = new FormData();
            formData.append('action', 'sm_bulk_delete_students_ajax');
            formData.append('student_ids', selected.join(','));
            formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_student"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification(`تم حذف ${selected.length} طالب بنجاح`);
                    setTimeout(() => location.reload(), 500);
                }
            });
        };

        window.eessToggleStudentAccountDropdown = function(e, stuId) {
            e.stopPropagation();
            document.querySelectorAll('.eess-stu-account-menu').forEach(m => {
                if (m.id !== 'eess-stu-account-menu-' + stuId) m.style.display = 'none';
            });
            const menu = document.getElementById('eess-stu-account-menu-' + stuId);
            if (menu) {
                menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
            }
        };

        document.addEventListener('click', function() {
            document.querySelectorAll('.eess-stu-account-menu').forEach(m => m.style.display = 'none');
        });

        window.eessOpenStudentAccountActionsModal = function(stuId, stuName, currentStatus) {
            const modal = document.getElementById('eess-stu-account-actions-modal');
            const subtitle = document.getElementById('eess_stu_actions_modal_subtitle');
            if (subtitle) subtitle.innerText = 'الطالب: ' + stuName + ' (' + (currentStatus || 'نشط') + ')';

            const rBtn = document.getElementById('eess_act_restrict_btn');
            const pBtn = document.getElementById('eess_act_passreq_btn');
            const mBtn = document.getElementById('eess_act_msg_btn');

            if (rBtn) rBtn.onclick = function() { modal.style.display = 'none'; eessOpenRestrictModal(stuId, stuName, currentStatus); };
            if (pBtn) pBtn.onclick = function() { modal.style.display = 'none'; eessOpenPasswordRequestModal(stuId, stuName); };
            if (mBtn) mBtn.onclick = function() { modal.style.display = 'none'; eessOpenDirectMessageModal(stuId, stuName); };

            if (modal) modal.style.display = 'flex';
        };

        window.eessOpenRestrictModal = function(stuId, stuName, currentStatus) {
            document.getElementById('eess_restrict_stu_id').value = stuId;
            document.getElementById('eess-restrict-modal-msg').innerHTML = `تأكيد تقييد / تعطيل حساب الطالب <strong>"${stuName}"</strong>؟<br><span style="font-size:11.5px; color:#64748b;">حالة الحساب الحالية: (${currentStatus})</span>`;
            document.getElementById('eess-restrict-account-modal').style.display = 'flex';
        };

        window.eessConfirmRestrictAccountSubmit = function() {
            const stuId = document.getElementById('eess_restrict_stu_id').value;
            jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'eess_restrict_student_account',
                student_id: stuId,
                nonce: '<?php echo wp_create_nonce("sm_admin_action"); ?>'
            }, function(res) {
                document.getElementById('eess-restrict-account-modal').style.display = 'none';
                if (res.success) {
                    if (typeof smShowNotification === 'function') smShowNotification('تم تقييد / تعطيل حساب الطالب بنجاح');
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('خطأ: ' + (res.data || 'فشل تقييد الحساب'));
                }
            });
        };

        window.eessOpenPasswordRequestModal = function(stuId, stuName) {
            document.getElementById('eess_pass_req_stu_id').value = stuId;
            document.getElementById('eess-pass-req-modal-msg').innerHTML = `تأكيد إصدار وإرسال طلب إجباري لتغيير كلمة المرور للطالب <strong>"${stuName}"</strong>؟<br><span style="font-size:11.5px; color:#64748b;">سيُلزم الطالب بتعيين كلمة مرور جديدة فور تسجيل الدخول القادم.</span>`;
            document.getElementById('eess-password-request-modal').style.display = 'flex';
        };

        window.eessConfirmPasswordRequestSubmit = function() {
            const stuId = document.getElementById('eess_pass_req_stu_id').value;
            jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'eess_request_student_password_change',
                student_id: stuId,
                nonce: '<?php echo wp_create_nonce("sm_admin_action"); ?>'
            }, function(res) {
                document.getElementById('eess-password-request-modal').style.display = 'none';
                if (res.success) {
                    if (typeof smShowNotification === 'function') smShowNotification('تم إصدار وإرسال طلب تغيير كلمة المرور بنجاح');
                } else {
                    alert('خطأ: ' + (res.data || 'فشل إرسال الطلب'));
                }
            });
        };

        window.eessOpenSendMessageModal = function(stuId, stuName) {
            document.getElementById('eess_send_msg_stu_id').value = stuId;
            document.getElementById('eess-send-msg-modal-title').innerText = `إرسال رسالة رسمية للطالب (${stuName})`;
            document.getElementById('eess_stu_message_text').value = '';
            document.getElementById('eess-send-message-modal').style.display = 'flex';
        };

        const msgForm = document.getElementById('eess-student-direct-message-form');
        if (msgForm) {
            msgForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const stuId = document.getElementById('eess_send_msg_stu_id').value;
                const msgText = document.getElementById('eess_stu_message_text').value.trim();

                if (!msgText) return;

                jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                    action: 'eess_send_message_to_student',
                    student_id: stuId,
                    message: msgText,
                    nonce: '<?php echo wp_create_nonce("sm_admin_action"); ?>'
                }, function(res) {
                    document.getElementById('eess-send-message-modal').style.display = 'none';
                    if (res.success) {
                        if (typeof smShowNotification === 'function') smShowNotification('تم إرسال الرسالة للطالب بنجاح وتوثيقها بصفحة دخوله');
                    } else {
                        alert('خطأ: ' + (res.data || 'فشل إرسال الرسالة'));
                    }
                });
            });
        }

        window.eessAdminSaveAcademicYear = function() {
            const yearVal = document.getElementById('sys_input_acad_year').value.trim();
            if (!yearVal) { alert('يرجى إدخال العام الدراسي.'); return; }
            const formData = new FormData();
            formData.append('action', 'eess_admin_update_academic_year');
            formData.append('academic_year', yearVal);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                alert(res.data.message || res.data);
                if (res.success) location.reload();
            });
        };

        window.eessAdminResetSequence = function() {
            const instId = document.getElementById('sys_reset_inst_id').value;
            if (!instId) { alert('يرجى اختيار المؤسسة أولاً.'); return; }
            if (!confirm('هل أنت تأكد من إعادة ضبط العداد الرقمي للطلاب الجدد لهذه المؤسسة إلى 00001؟ لن يتم تغيير أكواد الطلاب الحاليين.')) return;

            const formData = new FormData();
            formData.append('action', 'eess_admin_reset_student_sequence');
            formData.append('inst_id', instId);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                alert(res.data.message || res.data);
                if (res.success) location.reload();
            });
        };

        window.eessAdminDeleteInstitutionStudents = function() {
            const instSelect = document.getElementById('sys_del_inst_id');
            const instId = instSelect.value;
            const instName = instSelect.options[instSelect.selectedIndex].text;
            if (!instId) { alert('يرجى اختيار المؤسسة المراد مسح طلابها أولاً.'); return; }

            if (!confirm(`تحذير مؤكد وخاطر جداً:\n\nهل أنت متأكد من حذف جميع سجلات الطلاب التابعين لمؤسسة:\n(${instName})\n\nهذا الإجراء سيقوم بحذف سجلات الطلاب نهائياً ولن يمكن التراجع عنه!`)) return;

            const formData = new FormData();
            formData.append('action', 'eess_admin_delete_institution_students');
            formData.append('inst_id', instId);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                alert(res.data.message || res.data);
                if (res.success) location.reload();
            });
        };

        window.eessAdminDeleteAllStudentsGlobal = function() {
            if (!confirm('تحذير أمني شديد الخطورة:\n\nهل أنت متأكد تماماً من حذف كافة سجلات الطلاب لجميع المؤسسات بالكامل؟\n\nلن يمكن التراجع عن هذا الإجراء!')) return;

            const formData = new FormData();
            formData.append('action', 'eess_admin_delete_all_students_global');
            formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                alert(res.data.message || res.data);
                if (res.success) location.reload();
            });
        };

        window.eessAdminResetAllSequencesGlobal = function() {
            if (!confirm('هل أنت متأكد من إعادة ضبط التسلسل الرقمي لكافة المؤسسات لجميع الأعوام الدراسية إلى 00001؟')) return;

            const formData = new FormData();
            formData.append('action', 'eess_admin_reset_all_student_sequences_global');
            formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                alert(res.data.message || res.data);
                if (res.success) location.reload();
            });
        };

        window.eessTriggerExportStudentsPDF = function() {
            const schoolId = document.getElementById('pdf_exp_school_id').value;
            const classFilter = document.getElementById('pdf_exp_class_filter').value;
            const secFilter = document.getElementById('pdf_exp_section_filter').value.trim();
            const nonce = '<?php echo wp_create_nonce("sm_admin_action"); ?>';

            let url = '<?php echo admin_url('admin-ajax.php?action=eess_export_students_pdf'); ?>';
            url += '&school_id=' + encodeURIComponent(schoolId);
            url += '&class_filter=' + encodeURIComponent(classFilter);
            url += '&section_filter=' + encodeURIComponent(secFilter);
            url += '&nonce=' + encodeURIComponent(nonce);
            url += '&auto_print=1';

            window.open(url, '_blank');
        };
    })();
    </script>

<?php
$sysadmin_insts = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_institutions() : array();
$acad_struct_cur = class_exists('SM_Settings') ? SM_Settings::get_academic_structure() : array();
$current_acad_yr = $acad_struct_cur['academic_year'] ?? '2026/2027';
?>
<!-- System Administrator Controls Modal (Gear Icon - Clean White Header) -->
<div id="eess-sysadmin-students-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; width: 100%; max-width: 720px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column;">

        <!-- Clean White Header with Black Title & Black Icon -->
        <div style="background: #ffffff; color: #0f172a; padding: 20px 24px; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-generic" style="color: #0f172a; font-size: 24px; width: 24px; height: 24px;"></span>
                <h3 style="margin: 0; font-size: 17px; font-weight: 900; color: #0f172a;">إعدادات وضوابط شؤون الطلاب (مدير النظام)</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-sysadmin-students-modal').style.display='none'" style="background: none; border: none; color: #0f172a; font-size: 24px; cursor: pointer; font-weight: bold;">&times;</button>
        </div>

        <div style="padding: 24px; overflow-y: auto; max-height: 80vh; flex: 1; display: flex; flex-direction: column; gap: 20px;">

            <!-- Action 1: Academic Year Configuration -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                <div style="font-size: 13.5px; font-weight: 800; color: #0f172a; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-calendar-alt" style="color: #2563eb;"></span>
                    <span>تحديد العام الدراسي المعتمد لأكواد الطلاب</span>
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-bottom: 12px;">يحدد بادئة العام الدراسي (مثال: 2026/2027 ينتج كود 2627).</div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="sys_input_acad_year" value="<?php echo esc_attr($current_acad_yr); ?>" class="sm-input" placeholder="مثال: 2026/2027" style="height: 38px; font-size: 12.5px; font-weight: bold; width: 200px;">
                    <button type="button" onclick="eessAdminSaveAcademicYear()" class="sm-btn" style="background: #2563eb; color: #fff !important; height: 38px; font-size: 12px; font-weight: 800; border-radius: 8px;">حفظ العام الدراسي</button>
                </div>
            </div>

            <!-- Action 2: Reset Institution Serial Counter (Single & Global) -->
            <div style="background: #fffbebf8; border: 1px solid #fef3c7; border-radius: 14px; padding: 18px;">
                <div style="font-size: 13.5px; font-weight: 800; color: #92400e; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-update" style="color: #d97706;"></span>
                    <span>إعادة ضبط التسلسل الرقمي للمؤسسات (Reset Serial Numbers)</span>
                </div>
                <div style="font-size: 11.5px; color: #78350f; margin-bottom: 12px;">يعيد العداد الرقمي للطلاب الجدد للمؤسسة المحددة إلى 00001 دون مسح الطلاب الحاليين.</div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">
                    <select id="sys_reset_inst_id" class="sm-select" style="height: 38px; font-size: 12px; font-weight: 700; flex: 1;">
                        <option value="">-- اختر المؤسسة لتعديل تسلسلاها --</option>
                        <?php foreach ($sysadmin_insts as $inst): ?>
                            <option value="<?php echo esc_attr($inst->id); ?>"><?php echo esc_html($inst->name); ?> (كود: <?php echo esc_html($inst->code); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="eessAdminResetSequence()" class="sm-btn" style="background: #d97706; color: #fff !important; height: 38px; font-size: 12px; font-weight: 800; border-radius: 8px;">إعادة ضبط العداد إلى 00001</button>
                </div>
                <div style="border-top: 1px dashed #fcd34d; padding-top: 10px; margin-top: 6px;">
                    <button type="button" onclick="eessAdminResetAllSequencesGlobal()" class="sm-btn" style="background: #b45309; color: #fff !important; height: 36px; font-size: 11.5px; font-weight: 800; border-radius: 8px; width: 100%;">⚡ إعادة ضبط التسلسل الرقمي لكافة المؤسسات دفعة واحدة (Global Reset)</button>
                </div>
            </div>

            <!-- Action 3: Delete Students for Selected Institution / All Institutions -->
            <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 14px; padding: 18px;">
                <div style="font-size: 13.5px; font-weight: 800; color: #991b1b; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-trash" style="color: #dc2626;"></span>
                    <span>حذف سجلات الطلاب الحذرة (Global & Institution Deletion)</span>
                </div>
                <div style="font-size: 11.5px; color: #7f1d1d; margin-bottom: 12px;">تحذير شديد الخطورة: الإجراءات أدناه تقوم بحذف سجلات الطلاب نهائياً من قاعدة البيانات!</div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">
                    <select id="sys_del_inst_id" class="sm-select" style="height: 38px; font-size: 12px; font-weight: 700; flex: 1;">
                        <option value="">-- اختر المؤسسة المراد حذف طلابها --</option>
                        <?php foreach ($sysadmin_insts as $inst): ?>
                            <option value="<?php echo esc_attr($inst->id); ?>"><?php echo esc_html($inst->name); ?> (كود: <?php echo esc_html($inst->code); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="eessAdminDeleteInstitutionStudents()" class="sm-btn" style="background: #dc2626; color: #fff !important; height: 38px; font-size: 12px; font-weight: 800; border-radius: 8px;">حذف طلاب المؤسسة المحددة</button>
                </div>
                <div style="border-top: 1px dashed #fca5a5; padding-top: 10px; margin-top: 6px;">
                    <button type="button" onclick="eessAdminDeleteAllStudentsGlobal()" class="sm-btn" style="background: #991b1b; color: #fff !important; height: 38px; font-size: 12px; font-weight: 900; border-radius: 8px; width: 100%;">⚠️ حذف جميع سجلات الطلاب لكافة المؤسسات بالنظام (Delete All Students)</button>
                </div>
            </div>

        </div>

        <div style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: left;">
            <button type="button" onclick="document.getElementById('eess-sysadmin-students-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 36px; padding: 0 18px; border-radius: 8px;">إغلاق</button>
        </div>
    </div>
</div>

<!-- GLOBAL STUDENT DATA PRINT / PDF EXPORT MODAL -->
<div id="eess-student-export-pdf-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; width: 100%; max-width: 520px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column;">
        <div style="background: #ffffff; color: #0f172a; padding: 20px 24px; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-pdf" style="color: #881337; font-size: 24px; width: 24px; height: 24px;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;">تصدير طباعة كشوف الطلاب الرسمية (PDF)</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-student-export-pdf-modal').style.display='none'" style="background: none; border: none; color: #0f172a; font-size: 24px; cursor: pointer; font-weight: bold;">&times;</button>
        </div>

        <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label style="font-size: 12.5px; font-weight: 800; color: #0f172a; display: block; margin-bottom: 6px;">المدرسة / المؤسسة التعليمية:</label>
                <select id="pdf_exp_school_id" class="sm-select" style="width: 100%; height: 40px; font-size: 12.5px; font-weight: 700; border-radius: 10px;">
                    <option value="0">جميع المدارس والمؤسسات</option>
                    <?php foreach ($sysadmin_insts as $inst): ?>
                        <option value="<?php echo esc_attr($inst->id); ?>"><?php echo esc_html($inst->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size: 12.5px; font-weight: 800; color: #0f172a; display: block; margin-bottom: 6px;">الصف الدراسي:</label>
                <select id="pdf_exp_class_filter" class="sm-select" style="width: 100%; height: 40px; font-size: 12.5px; font-weight: 700; border-radius: 10px;">
                    <option value="">جميع الصفوف الدراسية (1 → 12)</option>
                    <?php
                    $academic = SM_Settings::get_academic_structure();
                    foreach ($academic['active_grades'] as $grade_num) {
                        $grade_label = 'الصف ' . $grade_num;
                        echo '<option value="' . esc_attr($grade_label) . '">' . esc_html($grade_label) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div>
                <label style="font-size: 12.5px; font-weight: 800; color: #0f172a; display: block; margin-bottom: 6px;">الشعبة / الفصل:</label>
                <input type="text" id="pdf_exp_section_filter" placeholder="مثال: أ أو جميع الشعب..." style="width: 100%; height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; font-weight: 700; box-sizing: border-box;">
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 11.5px; color: #64748b; line-height: 1.5;">
                يتضمن التقرير ترويسة رسمية لمؤسسة الشعلة، الشعار، الأكواد، الأسماء الكاملة، مرتبة تسلسلياً حسب الصف الدراسي والشعب.
            </div>

            <button type="button" onclick="eessTriggerExportStudentsPDF()" style="height: 44px; background: #881337; color: white; border: none; border-radius: 10px; font-weight: 900; font-size: 13.5px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span class="dashicons dashicons-pdf"></span>
                <span>توليد وتصدير كشف الطباعة (PDF) ➔</span>
            </button>
        </div>
    </div>
</div>
</div>
