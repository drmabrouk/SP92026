<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_hod = in_array('sm_hod', $roles);
$is_activities_sup = in_array('sm_activities_supervisor', $roles);
$is_discipline_sup = in_array('sm_discipline_supervisor', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

// Access security check: Only authorized supervisors can evaluate
$can_evaluate = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_hod || $is_hr || $is_activities_sup || $is_discipline_sup;

if (!$can_evaluate) {
    echo '<div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700; font-family:\'Cairo\'; text-align:center;">🚫 عذراً، لا تمتلك الصلاحيات الكافية للوصول لصفحة تقييم الموظفين.</div>';
    return;
}

$acad_struct = SM_Settings::get_academic_structure();
$active_academic_year = $acad_struct['academic_year'] ?? '2025/2026';
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Top Banner Card -->
    <div style="background: #ffffff; padding: 14px 18px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 14px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-award" style="font-size: 22px; width: 22px; height: 22px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 2px 0; font-size: 18px; font-weight: 800; color: #0f172a;">منظومة تقييم أداء الموظفين والكادر التعليمي</h2>
                <p style="margin: 0; font-size: 11.5px; color: #64748b; font-weight: 500;">تقييم أداء الموظفين خطوة بخطوة، ربط المؤشرات الموضوعية، وإدارة أرشيف التقييمات السنوية</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="font-size: 12px; font-weight: 800; color: #881337; background: #fef2f2; border: 1px solid #fecdd3; padding: 6px 14px; border-radius: 9999px;">
                العام الدراسي المعتمد: <?php echo esc_html($active_academic_year); ?>
            </span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        <button type="button" onclick="eessSwitchEvalTab('wizard')" id="eval_tab_btn_wizard" class="sm-btn" style="background: #0f172a; color: #ffffff !important; height: 38px; border-radius: 8px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
            <span>إجراء تقييم جديد (Workflow Wizard)</span>
        </button>
        <button type="button" onclick="eessSwitchEvalTab('archive')" id="eval_tab_btn_archive" class="sm-btn sm-btn-outline" style="height: 38px; border-radius: 8px; font-weight: 700; font-size: 12.5px; border: 1px solid #cbd5e1; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <span class="dashicons dashicons-archive" style="font-size: 16px; width: 16px; height: 16px;"></span>
            <span>أرشيف التقييمات التاريخية</span>
        </button>
        <?php if ($is_admin || $is_sys_admin || $is_hr || $is_principal || $is_hod || $is_discipline_sup): ?>
            <button type="button" onclick="eessSwitchEvalTab('templates')" id="eval_tab_btn_templates" class="sm-btn sm-btn-outline" style="height: 38px; border-radius: 8px; font-weight: 700; font-size: 12.5px; border: 1px solid #cbd5e1; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>إدارة نماذج وأسئلة التقييم</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- TAB 1: EVALUATION WORKFLOW WIZARD -->
    <div id="eval_tab_wizard" style="display: block;">
        <div style="background: #ffffff; padding: 24px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">

            <!-- Stepper Track -->
            <div style="background: #f8fafc; padding: 14px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div id="ew_node_1" style="font-weight: 800; font-size: 12px; color: #881337; display: flex; align-items: center; gap: 6px;">
                    <span style="background: #881337; color: white; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">1</span>
                    <span>البحث واختيار الموظف</span>
                </div>
                <div id="ew_node_2" style="font-weight: 700; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                    <span style="background: #e2e8f0; color: #475569; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">2</span>
                    <span>المؤشرات الموضوعية وتحديد الفئة</span>
                </div>
                <div id="ew_node_3" style="font-weight: 700; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                    <span style="background: #e2e8f0; color: #475569; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">3</span>
                    <span>الإجابة على أسئلة التقييم (0–10)</span>
                </div>
                <div id="ew_node_4" style="font-weight: 700; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                    <span style="background: #e2e8f0; color: #475569; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">4</span>
                    <span>مراجعة النتيجة والحفظ للأرشيف</span>
                </div>
            </div>

            <!-- STEP 1: EMPLOYEE SEARCH -->
            <div id="ew_step_1" style="display: block;">
                <h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 800; color: #0f172a;">الخطوة 1: البحث عن الموظف واختياره من سجلات النظام</h4>
                <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b;">ادخل اسم الموظف أو الكود الوظيفي للاستدعاء المباشر من قاعدة المستخدمين المركزية:</p>

                <div style="position: relative; width: 100%; max-width: 480px; margin-bottom: 20px;">
                    <input type="text" id="ew_emp_search_input" onkeyup="eessSearchEmployeeForEval(this.value)" class="sm-input" placeholder="ابحث باسم الموظف أو الكود..." style="height: 42px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 36px 0 14px; font-size: 13px; width: 100%; box-sizing: border-box;">
                    <span class="dashicons dashicons-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></span>
                </div>

                <div id="ew_emp_results_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px;">
                    <!-- Filled dynamically via JS -->
                </div>

                <!-- Selected Employee Profile Summary Card -->
                <div id="ew_selected_emp_card" style="display: none; background: #f8fafc; border: 1.5px solid #881337; border-radius: 16px; padding: 18px; margin-top: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <img id="ew_s_photo" src="" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #881337;">
                            <div>
                                <h3 id="ew_s_name" style="margin: 0 0 4px 0; font-size: 16px; font-weight: 800; color: #0f172a;">-</h3>
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; font-size: 11px;">
                                    <span id="ew_s_emp_id" style="background: #f1f5f9; color: #334155; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 800;">-</span>
                                    <span id="ew_s_school" style="background: #f0fdf4; color: #166534; padding: 2px 8px; border-radius: 6px; font-weight: 800;">-</span>
                                    <span id="ew_s_dept" style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 6px; font-weight: 800;">-</span>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="eessProceedToEvalStep2()" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 22px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer;">
                            <span>متابعة لتحديد التقييم والمؤشرات ←</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: SYSTEM OBJECTIVE INDICATORS & CATEGORY SELECTION -->
            <div id="ew_step_2" style="display: none;">
                <h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 800; color: #0f172a;">الخطوة 2: مؤشرات الأداء الموضوعية وتحديد فئة التقييم</h4>
                <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b;">المؤشرات التلقائية المستخرجة مباشرة من سجلات تحضير الدروس والخطط الفصلية والسنوية بالمنظومة:</p>

                <!-- System Performance Indicators Card -->
                <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
                    <h5 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #0369a1; display: flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-analytics"></span>
                        <span>المؤشرات الأكاديمية الموضوعية (System Performance Indicators)</span>
                    </h5>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; text-align: center;">
                        <div style="background: #ffffff; padding: 12px; border-radius: 10px; border: 1px solid #bae6fd;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 4px;">التزام تحضير الدروس الأسبوعية</div>
                            <div id="ew_sys_prep_pct" style="font-size: 20px; font-weight: 900; color: #0284c7;">100%</div>
                            <small id="ew_sys_prep_sub" style="font-size: 10px; color: #94a3b8;">---</small>
                        </div>
                        <div style="background: #ffffff; padding: 12px; border-radius: 10px; border: 1px solid #bae6fd;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 4px;">التزام اعتماد الخطط الفصلية</div>
                            <div id="ew_sys_plan_pct" style="font-size: 20px; font-weight: 900; color: #16a34a;">100%</div>
                            <small id="ew_sys_plan_sub" style="font-size: 10px; color: #94a3b8;">---</small>
                        </div>
                        <div style="background: #ffffff; padding: 12px; border-radius: 10px; border: 1px solid #bae6fd;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 4px;">معدل الامتثال العام بالنظام</div>
                            <div id="ew_sys_overall_score" style="font-size: 20px; font-weight: 900; color: #881337;">100%</div>
                            <small style="font-size: 10px; color: #94a3b8;">مستخرج تلقائياً</small>
                        </div>
                    </div>
                </div>

                <!-- Select Category -->
                <h5 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 800; color: #0f172a;">اختر فئة التقييم المطلوبة للبدء:</h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 24px;">
                    <button type="button" onclick="eessSelectEvalCategory('تقييم الانضباط والسلوك')" class="sm-btn sm-btn-outline" style="height: 48px; border-radius: 12px; font-weight: 800; font-size: 13px; text-align: center; border: 1.5px solid #cbd5e1;">📋 تقييم الانضباط والسلوك</button>
                    <button type="button" onclick="eessSelectEvalCategory('التقييم التربوي والمهني')" class="sm-btn sm-btn-outline" style="height: 48px; border-radius: 12px; font-weight: 800; font-size: 13px; text-align: center; border: 1.5px solid #cbd5e1;">🎓 التقييم التربوي والمهني</button>
                    <button type="button" onclick="eessSelectEvalCategory('تقييم الأداء الوظيفي')" class="sm-btn sm-btn-outline" style="height: 48px; border-radius: 12px; font-weight: 800; font-size: 13px; text-align: center; border: 1.5px solid #cbd5e1;">💼 تقييم الأداء الوظيفي</button>
                    <button type="button" onclick="eessSelectEvalCategory('تقييم الالتزام والتواصل')" class="sm-btn sm-btn-outline" style="height: 48px; border-radius: 12px; font-weight: 800; font-size: 13px; text-align: center; border: 1.5px solid #cbd5e1;">🤝 تقييم التواصل والتفاعل</button>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="eessGoToEvalStep(1)" class="sm-btn sm-btn-outline" style="height: 38px; border-radius: 9999px !important; font-size: 12px; font-weight: 700;">← العودة للخطوة 1</button>
                </div>
            </div>

            <!-- STEP 3: ANSWER QUESTIONS (0-10 SCALE) -->
            <div id="ew_step_3" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <div>
                        <h4 id="ew_category_title_display" style="margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: #0f172a;">الخطوة 3: التقييم التقديري (معايير 0–10)</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">حدد درجة الموظف لكل معيار بضغطة زر واحدة (من 0 إلى 10):</p>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 800; color: #881337;">
                        المجموع التقديري: <strong id="ew_live_score_sum" style="font-size: 14px;">0 / 100</strong>
                    </div>
                </div>

                <div id="ew_questions_container" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
                    <!-- Filled dynamically via JS -->
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 800; color: #334155; margin-bottom: 6px;">ملاحظات المقيم والتوصيات الرسمية (اختياري):</label>
                    <textarea id="ew_comments_input" rows="3" class="sm-input" placeholder="اكتب أي ملاحظات إدارية، نقاط قوة، أو فرص تطوير للموظف..." style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 12.5px; box-sizing: border-box;"></textarea>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="eessGoToEvalStep(2)" class="sm-btn sm-btn-outline" style="height: 38px; border-radius: 9999px !important; font-size: 12px; font-weight: 700;">← العودة للخطوة 2</button>
                    <button type="button" onclick="eessProceedToEvalStep4()" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 24px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer;">مراجعة النتيجة الإجمالية ←</button>
                </div>
            </div>

            <!-- STEP 4: REVIEW & SUMMARY BEFORE SUBMISSION -->
            <div id="ew_step_4" style="display: none;">
                <h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 800; color: #0f172a;">الخطوة 4: مراجعة نتيجة التقييم واعتماد حفظ السجل</h4>
                <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b;">راجع تفاصيل الدرجات والنتيجة النهائية قبل التثبيت النهائي في الأرشيف التاريخي:</p>

                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; font-size: 13px; color: #334155; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                        <div>الموظف التقييم: <strong id="ew_rev_emp_name" style="color: #0f172a;">-</strong></div>
                        <div>فئة التقييم: <strong id="ew_rev_category" style="color: #881337;">-</strong></div>
                        <div>العام الدراسي: <strong style="color: #0284c7;"><?php echo esc_html($active_academic_year); ?></strong></div>
                        <div>المقيم المسجّل: <strong style="color: #0f172a;"><?php echo esc_html($current_user->display_name); ?></strong></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                        <div>
                            <span style="font-size: 12px; color: #64748b; font-weight: 700; display: block;">النتيجة النهائية والنسبة المئوية:</span>
                            <strong id="ew_rev_score_text" style="font-size: 22px; font-weight: 900; color: #16a34a;">0 / 100 (0%)</strong>
                        </div>
                        <span id="ew_rev_status_badge" style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 4px 14px; border-radius: 9999px; font-weight: 800; font-size: 12px;">ممتاز</span>
                    </div>

                    <div id="ew_rev_answers_summary" style="display: flex; flex-direction: column; gap: 8px; font-size: 12px;">
                        <!-- Filled dynamically -->
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="eessGoToEvalStep(3)" class="sm-btn sm-btn-outline" style="height: 38px; border-radius: 9999px !important; font-size: 12px; font-weight: 700;">← العودة للتعديل</button>
                    <button type="button" onclick="eessSubmitFinalEvaluation()" id="ew_final_submit_btn" class="sm-btn" style="background: #16a34a; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 28px; font-weight: 800; font-size: 13px; border: none; cursor: pointer;">
                        <span>تأكيد واعتماد التقييم رسمياً ✓</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB 2: HISTORICAL EVALUATION ARCHIVE -->
    <div id="eval_tab_archive" style="display: none;">
        <div style="background: #ffffff; padding: 24px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">سجل وأرشيف التقييمات التاريخية للموظفين</h3>

                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="eval_archive_search" onkeyup="eessLoadEvaluationsArchive()" placeholder="بحث باسم الموظف أو المقيم..." class="sm-input" style="height: 36px; border-radius: 9999px !important; border: 1px solid #cbd5e1; font-size: 12px; padding: 0 14px; width: 220px;">
                    <select id="eval_archive_year_filter" onchange="eessLoadEvaluationsArchive()" class="sm-select" style="height: 36px; border-radius: 9999px !important; border: 1px solid #cbd5e1; font-size: 12px; padding: 0 10px;">
                        <option value="">كافة الأعوام الدراسية</option>
                        <option value="2025/2026" selected>العام 2025/2026</option>
                        <option value="2024/2025">العام 2024/2025</option>
                    </select>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right; font-size: 12.5px;">
                    <thead>
                        <tr style="background: #0f172a; color: #ffffff;">
                            <th style="padding: 10px 14px; font-weight: 800; border-radius: 0 8px 0 0;">الموظف والكود</th>
                            <th style="padding: 10px 14px; font-weight: 800;">فئة التقييم والعام</th>
                            <th style="padding: 10px 14px; font-weight: 800;">المقيم والمسجل</th>
                            <th style="padding: 10px 14px; font-weight: 800; text-align: center;">النتيجة المئوية</th>
                            <th style="padding: 10px 14px; font-weight: 800; text-align: center;">تاريخ التقييم</th>
                            <th style="padding: 10px 14px; font-weight: 800; text-align: center; border-radius: 8px 0 0 0;">التقرير الرسمية</th>
                        </tr>
                    </thead>
                    <tbody id="eval_archive_tbody">
                        <!-- Filled dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: TEMPLATE & QUESTION MANAGEMENT -->
    <?php if ($is_admin || $is_sys_admin || $is_hr || $is_principal || $is_hod || $is_discipline_sup): ?>
    <div id="eval_tab_templates" style="display: none;">
        <div style="background: #ffffff; padding: 24px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
            <h3 style="margin: 0 0 14px 0; font-size: 16px; font-weight: 800; color: #0f172a;">إنشاء وتخصيص نماذج وأسئلة التقييم (0–10)</h3>
            <p style="margin: 0 0 20px 0; font-size: 12.5px; color: #64748b;">إضافة نموذج جديد وتحديد الأسئلة المخصصة لكل رتبة أو قسم بالفصل:</p>

            <form id="eess_create_template_form" onsubmit="eessSaveEvalTemplateSubmit(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 4px;">عنوان النموذج التقييمي *</label>
                        <input type="text" id="tmpl_title" required placeholder="مثال: نموذج تقييم معلمي التربية البدنية والصحية" class="sm-input" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 4px;">الرتبة المستهدفة بالتقييم *</label>
                        <select id="tmpl_role_key" class="sm-select" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;">
                            <option value="sm_teacher">معلم (sm_teacher)</option>
                            <option value="sm_coordinator">منسق مادة (sm_coordinator)</option>
                            <option value="sm_supervisor">مشرف تربوي (sm_supervisor)</option>
                            <option value="sm_hod">رئيس قسم (sm_hod)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 8px;">أسئلة ومعايير التقييم (مقياس 0 إلى 10 نقاط):</label>
                    <div id="tmpl_questions_builder" style="display: flex; flex-direction: column; gap: 10px;">
                        <input type="text" class="sm-input tmpl-q-input" placeholder="السؤال 1: الالتزام بالجدول الدراسي والمواعيد..." style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;">
                        <input type="text" class="sm-input tmpl-q-input" placeholder="السؤال 2: جودة التحضير والتخطيط الأكاديمي..." style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;">
                    </div>
                    <button type="button" onclick="eessAddTemplateQuestionRow()" class="sm-btn sm-btn-outline" style="margin-top: 10px; height: 34px; font-size: 11.5px; border-radius: 8px; font-weight: 700;">+ إضافة سؤال جديد</button>
                </div>

                <button type="submit" id="tmpl_save_btn" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 40px; border-radius: 9999px !important; padding: 0 24px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer;">حفظ واعتماد النموذج الجديد</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
var eessActiveEvalStep = 1;
var eessSelectedEmployee = null;
var eessActiveTemplate = null;
var eessEvalAnswers = {};

function eessSwitchEvalTab(tabKey) {
    document.getElementById('eval_tab_wizard').style.display = (tabKey === 'wizard') ? 'block' : 'none';
    document.getElementById('eval_tab_archive').style.display = (tabKey === 'archive') ? 'block' : 'none';
    if (document.getElementById('eval_tab_templates')) {
        document.getElementById('eval_tab_templates').style.display = (tabKey === 'templates') ? 'block' : 'none';
    }

    document.getElementById('eval_tab_btn_wizard').className = (tabKey === 'wizard') ? 'sm-btn' : 'sm-btn sm-btn-outline';
    document.getElementById('eval_tab_btn_wizard').style.background = (tabKey === 'wizard') ? '#0f172a' : '#ffffff';
    document.getElementById('eval_tab_btn_wizard').style.color = (tabKey === 'wizard') ? '#ffffff' : '#475569';

    document.getElementById('eval_tab_btn_archive').className = (tabKey === 'archive') ? 'sm-btn' : 'sm-btn sm-btn-outline';
    document.getElementById('eval_tab_btn_archive').style.background = (tabKey === 'archive') ? '#0f172a' : '#ffffff';
    document.getElementById('eval_tab_btn_archive').style.color = (tabKey === 'archive') ? '#ffffff' : '#475569';

    if (tabKey === 'archive') {
        eessLoadEvaluationsArchive();
    }
}

function eessSearchEmployeeForEval(query) {
    var grid = document.getElementById('ew_emp_results_grid');
    if (!query || query.length < 1) {
        grid.innerHTML = '<div style="color:#94a3b8; font-size:12px; grid-column: span 3;">ادخل حرفين على الأقل للبحث عن الموظف...</div>';
        return;
    }

    var formData = new FormData();
    formData.append('action', 'eess_search_employees_for_eval');
    formData.append('query', query);

    fetch((typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'), { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data && res.data.length > 0) {
            var html = '';
            res.data.forEach(function(emp) {
                html += `
                    <div onclick='eessSelectEmployeeForEval(${JSON.stringify(emp)})' style="background:#ffffff; border:1px solid #cbd5e1; border-radius:12px; padding:12px; display:flex; align-items:center; gap:12px; cursor:pointer; transition:border-color 0.2s;" onmouseover="this.style.borderColor='#881337'" onmouseout="this.style.borderColor='#cbd5e1'">
                        <img src="${emp.photo_url}" style="width:46px; height:46px; border-radius:50%; object-fit:cover; border:1px solid #881337;">
                        <div>
                            <strong style="color:#0f172a; font-size:13px; display:block;">${emp.name}</strong>
                            <small style="color:#64748b; font-size:11px;">كود: ${emp.employee_number} | ${emp.department}</small>
                        </div>
                    </div>
                `;
            });
            grid.innerHTML = html;
        } else {
            grid.innerHTML = '<div style="color:#94a3b8; font-size:12px; grid-column: span 3;">لم يتم العثور على موظفين مطابقتين.</div>';
        }
    });
}

function eessSelectEmployeeForEval(emp) {
    eessSelectedEmployee = emp;
    document.getElementById('ew_s_photo').src = emp.photo_url || '';
    document.getElementById('ew_s_name').innerText = emp.name;
    document.getElementById('ew_s_emp_id').innerText = 'كود: ' + emp.employee_number;
    document.getElementById('ew_s_school').innerText = emp.school_name;
    document.getElementById('ew_s_dept').innerText = emp.department + ' / ' + emp.subject;
    document.getElementById('ew_selected_emp_card').style.display = 'block';
}

function eessProceedToEvalStep2() {
    if (!eessSelectedEmployee) return;

    // Load Objective Performance Indicators
    var formData = new FormData();
    formData.append('action', 'eess_get_employee_system_performance_indicators');
    formData.append('user_id', eessSelectedEmployee.id);

    fetch((typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'), { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            var d = res.data;
            document.getElementById('ew_sys_prep_pct').innerText = d.prep_compliance_pct + '%';
            document.getElementById('ew_sys_prep_sub').innerText = d.prep_ontime + ' في الموعد من أصل ' + d.prep_total;
            document.getElementById('ew_sys_plan_pct').innerText = d.plan_compliance_pct + '%';
            document.getElementById('ew_sys_plan_sub').innerText = d.plan_approved + ' خطط معتمدة من أصل ' + d.plan_total;
            document.getElementById('ew_sys_overall_score').innerText = d.overall_system_score + '%';
        }
    });

    eessGoToEvalStep(2);
}

function eessSelectEvalCategory(categoryName) {
    if (!eessSelectedEmployee) return;

    var formData = new FormData();
    formData.append('action', 'eess_get_eval_template_for_role');
    formData.append('role_key', eessSelectedEmployee.role_key || 'sm_teacher');

    fetch((typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'), { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            eessActiveTemplate = res.data;
            eessActiveTemplate.category_name = categoryName;
            document.getElementById('ew_category_title_display').innerText = categoryName + ' — الموظف: ' + eessSelectedEmployee.name;
            eessRenderQuestionsContainer(eessActiveTemplate.questions);
            eessGoToEvalStep(3);
        }
    });
}

function eessRenderQuestionsContainer(questions) {
    var container = document.getElementById('ew_questions_container');
    container.innerHTML = '';
    eessEvalAnswers = {};

    questions.forEach(function(q, idx) {
        eessEvalAnswers[q.id] = { score: 10, question_text: q.text };

        var qCard = document.createElement('div');
        qCard.style.cssText = 'background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px;';

        var buttonsHtml = '';
        for (var i = 0; i <= 10; i++) {
            var activeStyle = (i === 10) ? 'background:#881337; color:#ffffff; border-color:#881337;' : 'background:#ffffff; color:#334155; border-color:#cbd5e1;';
            buttonsHtml += `<button type="button" onclick="eessSetQuestionScore(${q.id}, ${i}, this)" class="q-score-btn-${q.id}" style="width:32px; height:32px; border-radius:6px; border:1px solid #cbd5e1; font-weight:800; font-size:12px; cursor:pointer; transition:all 0.15s; ${activeStyle}">${i}</button>`;
        }

        qCard.innerHTML = `
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
                ${idx + 1}. ${q.text}
            </div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                ${buttonsHtml}
            </div>
        `;
        container.appendChild(qCard);
    });

    eessUpdateLiveScoreSum();
}

function eessSetQuestionScore(qId, score, btn) {
    eessEvalAnswers[qId].score = score;
    document.querySelectorAll('.q-score-btn-' + qId).forEach(b => {
        b.style.background = '#ffffff';
        b.style.color = '#334155';
        b.style.borderColor = '#cbd5e1';
    });
    btn.style.background = '#881337';
    btn.style.color = '#ffffff';
    btn.style.borderColor = '#881337';

    eessUpdateLiveScoreSum();
}

function eessUpdateLiveScoreSum() {
    var total = 0, count = 0;
    Object.keys(eessEvalAnswers).forEach(k => {
        total += eessEvalAnswers[k].score;
        count++;
    });
    var max = count * 10;
    document.getElementById('ew_live_score_sum').innerText = total + ' / ' + max + ' (' + (max > 0 ? Math.round((total / max) * 100) : 0) + '%)';
}

function eessProceedToEvalStep4() {
    var total = 0, count = 0;
    var answersSummaryHtml = '';

    Object.keys(eessEvalAnswers).forEach(k => {
        var item = eessEvalAnswers[k];
        total += item.score;
        count++;
        answersSummaryHtml += `
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; padding-bottom:4px;">
                <span>${item.question_text}</span>
                <strong style="color:#881337;">${item.score} / 10</strong>
            </div>
        `;
    });

    var max = count * 10;
    var pct = max > 0 ? Math.round((total / max) * 100) : 0;

    document.getElementById('ew_rev_emp_name').innerText = eessSelectedEmployee.name;
    document.getElementById('ew_rev_category').innerText = eessActiveTemplate.category_name;
    document.getElementById('ew_rev_score_text').innerText = total + ' / ' + max + ' (' + pct + '%)';
    document.getElementById('ew_rev_answers_summary').innerHTML = answersSummaryHtml;

    eessGoToEvalStep(4);
}

function eessSubmitFinalEvaluation() {
    var btn = document.getElementById('ew_final_submit_btn');
    btn.disabled = true;
    btn.innerText = 'جاري الاعتماد والحفظ...';

    var formData = new FormData();
    formData.append('action', 'eess_save_evaluation_submission');
    formData.append('employee_id', eessSelectedEmployee.id);
    formData.append('template_id', eessActiveTemplate.template_id || 0);
    formData.append('category_name', eessActiveTemplate.category_name);
    formData.append('comments', document.getElementById('ew_comments_input').value);

    Object.keys(eessEvalAnswers).forEach(qId => {
        formData.append(`answers[${qId}][score]`, eessEvalAnswers[qId].score);
        formData.append(`answers[${qId}][question_text]`, eessEvalAnswers[qId].question_text);
    });

    fetch((typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'), { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'تأكيد واعتماد التقييم رسمياً ✓';
        if (res.success) {
            if (typeof smShowNotification === 'function') smShowNotification('تم حفظ وتثبيت التقييم بالأرشيف بنجاح');
            eessSwitchEvalTab('archive');
        } else {
            alert('خطأ: ' + (res.data || 'تعذر حفظ التقييم.'));
        }
    });
}

function eessGoToEvalStep(stepNum) {
    eessActiveEvalStep = stepNum;
    for (var i = 1; i <= 4; i++) {
        document.getElementById('ew_step_' + i).style.display = (i === stepNum) ? 'block' : 'none';
        var node = document.getElementById('ew_node_' + i);
        if (node) {
            var badge = node.querySelector('span');
            if (i <= stepNum) {
                node.style.color = '#881337';
                node.style.fontWeight = '800';
                badge.style.background = '#881337';
                badge.style.color = '#ffffff';
            } else {
                node.style.color = '#94a3b8';
                node.style.fontWeight = '700';
                badge.style.background = '#e2e8f0';
                badge.style.color = '#475569';
            }
        }
    }
}

function eessLoadEvaluationsArchive() {
    var tbody = document.getElementById('eval_archive_tbody');
    var year = document.getElementById('eval_archive_year_filter').value;
    var query = document.getElementById('eval_archive_search').value;

    var formData = new FormData();
    formData.append('action', 'eess_get_evaluations_archive');
    formData.append('academic_year', year);
    formData.append('query', query);

    fetch((typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'), { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data && res.data.length > 0) {
            var html = '';
            res.data.forEach(function(row) {
                html += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 14px;"><strong>${row.employee_name}</strong> <small style="color:#64748b;">(كود: ${row.employee_number})</small></td>
                        <td style="padding:10px 14px;"><span style="color:#881337; font-weight:700;">${row.category_name}</span> (${row.academic_year})</td>
                        <td style="padding:10px 14px;">${row.evaluator_name}</td>
                        <td style="padding:10px 14px; text-align:center;"><strong style="color:#16a34a; font-size:14px;">${row.average_pct}%</strong> (${row.total_score} درجة)</td>
                        <td style="padding:10px 14px; text-align:center; font-family:monospace; color:#64748b;">${row.date}</td>
                        <td style="padding:10px 14px; text-align:center;">
                            <button type="button" onclick="window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=eval_report&eval_id='); ?>' + ${row.id}, '_blank')" class="sm-btn" style="background:#0f172a; color:#fff !important; height:30px; font-size:11px; padding:0 12px; border-radius:9999px;">🖨️ طباعة A4</button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="padding:30px; text-align:center; color:#94a3b8;">لا توجد تقييمات محفوظة بالأرشيف حالياً.</td></tr>';
        }
    });
}

function eessAddTemplateQuestionRow() {
    var builder = document.getElementById('tmpl_questions_builder');
    var count = builder.querySelectorAll('input').length + 1;
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'sm-input tmpl-q-input';
    input.placeholder = 'السؤال ' + count + ': ...';
    input.style.cssText = 'height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;';
    builder.appendChild(input);
}

function eessSaveEvalTemplateSubmit(e) {
    e.preventDefault();
    var btn = document.getElementById('tmpl_save_btn');
    btn.disabled = true;
    btn.innerText = 'جاري الحفظ...';

    var formData = new FormData();
    formData.append('action', 'eess_save_eval_template');
    formData.append('title', document.getElementById('tmpl_title').value);
    formData.append('role_key', document.getElementById('tmpl_role_key').value);

    document.querySelectorAll('.tmpl-q-input').forEach(function(inp) {
        if (inp.value.trim()) {
            formData.append('questions[]', inp.value.trim());
        }
    });

    fetch((typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'), { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'حفظ واعتماد النموذج الجديد';
        if (res.success) {
            alert('تم حفظ نموذج التقييم الجديد بنجاح');
            document.getElementById('eess_create_template_form').reset();
        } else {
            alert('خطأ: ' + (res.data || 'تعذر حفظ النموذج.'));
        }
    });
}
</script>
