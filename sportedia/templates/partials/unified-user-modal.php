<?php
if (!defined('ABSPATH')) exit;

$institutions = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_institutions() : array();
$all_schools  = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_all_schools() : array();
$subjects     = class_exists('EESS_Org_Helper') ? array_column(EESS_Org_Helper::get_official_subjects(), 'name', 'code') : array();
$departments_objs = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_departments_by_institution(1) : array();
$departments = array();
if (!empty($departments_objs)) {
    foreach ($departments_objs as $d_obj) {
        if (!empty($d_obj->name)) {
            $departments[$d_obj->code ?: $d_obj->id] = $d_obj->name;
        }
    }
}
if (empty($departments) && class_exists('EESS_Org_Helper')) {
    $departments = array_column(EESS_Org_Helper::get_official_departments(), 'name', 'code');
}
?>

<!-- UNIFIED USER & EMPLOYEE MANAGEMENT MODAL -->
<div id="unified-user-modal" class="sm-modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 999999; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); position: fixed; inset: 0; padding: 20px; box-sizing: border-box;">
    <div class="sm-modal-content" style="background: #ffffff; border-radius: 20px; width: 100%; max-width: 980px; max-height: 94vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); border: 1px solid #cbd5e1; font-family: 'Cairo', sans-serif; box-sizing: border-box; display: flex; flex-direction: column;" dir="rtl">

        <!-- Modal Header (Clean White Style) -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; color: #000000; border-bottom: 1px solid #e2e8f0; box-sizing: border-box; width: 100%; margin: 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="u_modal_icon" class="dashicons dashicons-admin-users" style="font-size: 24px; width: 24px; height: 24px; color: #000000; margin: 0;"></span>
                <h3 id="u_modal_title" style="margin: 0; font-size: 16px; font-weight: 800; color: #000000; font-family: 'Cairo', sans-serif;">Edit    </h3>
            </div>
            <button type="button" class="sm-modal-close" onclick="eessCloseUnifiedUserModal()" style="background: transparent; border: none; font-size: 26px; color: #000000; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- 5-Step Pastel Indicator Bar -->
        <div style="padding: 14px 20px; background: #ffffff; border-bottom: 1px solid #e2e8f0; position: relative;">
            <div style="position: absolute; top: 50%; left: 5%; right: 5%; height: 2px; background: #e2e8f0; z-index: 1; transform: translateY(-50%);"></div>
            <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2; width: 100%; gap: 6px; flex-wrap: wrap;">
                <div id="u_indicator_step1" class="u-step-indicator active" style="background: #fce7f3; color: #9f1239; border: 1px solid #fbcfe8; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <span style="background: #9f1239; color: white; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900;">1</span>
                    <span>Personal Information</span>
                </div>
                <div id="u_indicator_step2" class="u-step-indicator" style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900;">2</span>
                    <span> </span>
                </div>
                <div id="u_indicator_step3" class="u-step-indicator" style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900;">3</span>
                    <span>  </span>
                </div>
                <div id="u_indicator_step4" class="u-step-indicator" style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900;">4</span>
                    <span> </span>
                </div>
                <div id="u_indicator_step5" class="u-step-indicator" style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900;">5</span>
                    <span>Confirm Save </span>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <form id="eess-unified-user-form" enctype="multipart/form-data" style="padding: 24px; flex: 1; overflow-y: auto;" onsubmit="return false;">
            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
            <input type="hidden" name="user_id" id="u_user_id" value="0">
            <input type="hidden" name="form_mode" id="u_form_mode" value="add_user">

            <!-- STEP 1: PERSONAL INFORMATION -->
            <div id="u_step_1_container">
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 20px; margin-bottom: 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="width: 110px; height: 110px; border-radius: 12px; border: 2px solid #cbd5e1; background: #ffffff; overflow: hidden; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        <img id="u_photo_preview" src="" alt=" " style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2394a3b8\'><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>'">
                    </div>
                    <input type="file" name="profile_photo" id="u_profile_photo" accept="image/*" style="font-size: 12px; margin-bottom: 6px;" onchange="eessPreviewAvatar(this)">
                    <p style="margin: 0; font-size: 12px; font-weight: 700; color: #881337;">      .</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; margin-top: 10px;">
                    <div class="eess-float-group">
                        <input type="text" name="first_name" id="u_first_name" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                        <label for="u_first_name" class="eess-float-label">No  <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_first_name" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  No .</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="last_name" id="u_last_name" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                        <label for="u_last_name" class="eess-float-label">  /  <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_last_name" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">   .</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="date" name="dob" id="u_dob" class="sm-input eess-float-input" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 12.5px;" oninput="eessValidateField(this)">
                        <label for="u_dob" class="eess-float-label">Date of Birth <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_dob" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  Date of Birth.</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="nationality" id="u_nationality" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                        <label for="u_nationality" class="eess-float-label">Gender <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_nationality" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  Gender.</span>
                    </div>

                    <div class="eess-float-group">
                        <select name="gender" id="u_gender" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                            <option value="">--  Gender --</option>
                            <option value="male">Male (Male)</option>
                            <option value="female">Female (Female)</option>
                        </select>
                        <label for="u_gender" class="eess-float-label">Gender</label>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="civil_id" id="u_civil_id" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                        <label for="u_civil_id" class="eess-float-label">  / National ID</label>
                    </div>
                </div>
            </div>

            <!-- STEP 2: CONTACT & RESIDENCE -->
            <div id="u_step_2_container" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; margin-top: 10px;">
                    <div class="eess-float-group">
                        <input type="email" name="user_email" id="u_user_email" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; direction: ltr; text-align: right;" onblur="eessCheckUniqueness('email')" oninput="eessValidateField(this)">
                        <label for="u_user_email" class="eess-float-label">Email Address  <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_user_email" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">    .</span>
                    </div>

                    <div id="u_phone_wrapper">
                        <div style="display: flex; gap: 8px; direction: ltr;">
                            <select name="country_code" id="u_country_code" class="sm-select" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; font-size: 12.5px; width: 130px; font-weight: bold;">
                                <option value="+971"> +971 ()</option>
                                <option value="+966"> +966 ()</option>
                                <option value="+965"> +965 ()</option>
                                <option value="+974"> +974 ()</option>
                                <option value="+973"> +973 ()</option>
                                <option value="+968"> +968 ()</option>
                            </select>
                            <div class="eess-float-group" style="flex: 1;">
                                <input type="text" name="phone_number" id="u_phone_number" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; text-align: left; direction: ltr;" oninput="eessValidateField(this)">
                                <label for="u_phone_number" class="eess-float-label">Mobile Number  <span style="color:#ef4444;">*</span></label>
                            </div>
                        </div>
                        <span class="eess-field-error" id="err_u_phone_number" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  Mobile Number .</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="country_residence" id="u_country_residence" value="United Arab Emirates" readonly class="sm-input eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; background: #f8fafc; font-weight: 800; color: #0f172a;">
                        <label for="u_country_residence" class="eess-float-label">  <span style="color:#ef4444;">*</span></label>
                    </div>

                    <div class="eess-float-group">
                        <select name="emirate" id="u_emirate" class="sm-select eess-float-input" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" onchange="eessValidateField(this)">
                            <option value="" selected> (Dubai)</option>
                            <option value=""> (Abu Dhabi)</option>
                            <option value=""> (Sharjah)</option>
                            <option value=""> (Ajman)</option>
                            <option value=" ">  (Umm Al Quwain)</option>
                            <option value=" ">  (Ras Al Khaimah)</option>
                            <option value=""> (Fujairah)</option>
                        </select>
                        <label for="u_emirate" class="eess-float-label"> <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_emirate" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  .</span>
                    </div>
                </div>
            </div>

            <!-- STEP 3: PROFESSIONAL & ACADEMIC ASSIGNMENT -->
            <div id="u_step_3_container" style="display: none;">
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">  </h4>

                    <div style="display: flex; flex-direction: column; gap: 18px; margin-top: 10px;">
                        <!-- Row 1: School Selection & Employee Number -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div id="u_institution_wrapper" class="eess-float-group">
                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Academy  / Organization   <span style="color:#ef4444;">*</span></label>
                                <select name="institution_id" id="u_institution_id" class="sm-select eess-float-input" onchange="eessOnInstitutionChanged()" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; width: 100%;">
                                    <option value="">--  Academy  / Organization   --</option>
                                    <?php foreach ($institutions as $inst): ?>
                                        <option value="<?php echo esc_attr($inst->id); ?>"><?php echo esc_html($inst->name); ?> — <?php echo esc_html(intval($inst->code ?: $inst->id)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="eess-field-error" id="err_u_institution_id" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  Organization .</span>
                            </div>

                            <div id="u_empid_wrapper" class="eess-float-group">
                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">  (Employee ID) <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="employee_id" id="u_employee_id" class="sm-input eess-float-input" placeholder=": 2001" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold; direction: ltr; text-align: right; width: 100%;" oninput="eessSyncUsername(this)" onblur="eessCheckUniqueness('employee_id')">
                                <input type="hidden" name="username" id="u_username">
                                <span class="eess-field-error" id="err_u_employee_id" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">   .</span>
                            </div>
                        </div>

                        <!-- Row 2: Role & Department -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="eess-float-group">
                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;"> /   <span style="color:#ef4444;">*</span></label>
                                <select name="user_role" id="u_user_role" class="sm-select eess-float-input" onchange="eessOnRoleChanged()" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold; width: 100%;">
                                    <option value="">--   --</option>
                                    <option value="sm_teacher"> /   </option>
                                    <option value="sm_hod">  (HOD)</option>
                                    <option value="sm_principal">  /  </option>
                                    <option value="sm_supervisor"> /  </option>
                                    <option value="sm_activities_supervisor"> Active </option>
                                    <option value="sm_clinic"> /   </option>
                                    <option value="administrator">  (System Administrator)</option>
                                </select>
                                <span class="eess-field-error" id="err_u_user_role" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">   .</span>
                            </div>

                            <div id="u_department_wrapper" class="eess-float-group">
                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;"> /  <span style="color:#ef4444;">*</span></label>
                                <select name="department" id="u_department" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; width: 100%;">
                                    <option value="">--   --</option>
                                    <?php foreach ($departments as $dept_key => $dept_lbl): ?>
                                        <option value="<?php echo esc_attr($dept_lbl); ?>"><?php echo esc_html($dept_lbl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Row 3: Subject & Assigned Grade Levels -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div id="u_subject_wrapper" class="eess-float-group">
                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Sport Activity /  <span style="color:#ef4444;">*</span></label>
                                <select name="specialization" id="u_specialization" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; width: 100%;">
                                    <option value="">--  Sport Activity --</option>
                                    <?php foreach ($subjects as $subj_code => $subj_name): ?>
                                        <option value="<?php echo esc_attr($subj_name); ?>"><?php echo esc_html($subj_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="eess-field-error" id="err_u_specialization" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">  Sport Activity .</span>
                            </div>

                            <div id="u_grades_wrapper">
                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Training Groups   (No ):</label>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; background: #ffffff; padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1; max-height: 120px; overflow-y: auto;">
                                    <?php foreach (EESS_Org_Helper::get_official_grades() as $g_item): ?>
                                        <label class="u-grade-capsule-label" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 11.5px; font-weight: 700; color: #334155; cursor: pointer; user-select: none;">
                                            <input type="checkbox" name="assigned_grades[]" value="<?php echo esc_attr($g_item['name']); ?>" onchange="eessToggleGradeCapsule(this)" style="display: none;">
                                            <span><?php echo esc_html($g_item['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: LOGIN DATA & PASSWORDS -->
            <div id="u_step_4_container" style="display: none;">
                <div style="background: transparent; border: none; padding: 10px 0; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">   </h4>

                    <!-- First Row: Email and Username (Read-Only) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="eess-float-group">
                            <input type="email" id="u_login_email_display" readonly class="sm-input eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; background: #f8fafc; font-weight: bold; cursor: not-allowed; direction: ltr; text-align: right;">
                            <label class="eess-float-label">Email Address (View )</label>
                        </div>

                        <div class="eess-float-group">
                            <input type="text" id="u_login_username_display" readonly class="sm-input eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; background: #f8fafc; font-weight: bold; cursor: not-allowed; direction: ltr; text-align: right;">
                            <label class="eess-float-label">Username /   (View )</label>
                        </div>
                    </div>

                    <!-- Second Row: Passwords & Status -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="eess-float-group">
                            <input type="password" name="user_pass" id="u_user_pass" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                            <label for="u_user_pass" class="eess-float-label">Password <span style="color:#ef4444;">*</span></label>
                            <span class="eess-field-error" id="err_u_user_pass" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">Password    8         .</span>
                        </div>

                        <div class="eess-float-group">
                            <input type="password" name="user_pass_confirm" id="u_user_pass_confirm" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                            <label for="u_user_pass_confirm" class="eess-float-label">Confirm Password <span style="color:#ef4444;">*</span></label>
                            <span class="eess-field-error" id="err_u_user_pass_confirm" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">   .</span>
                        </div>

                        <div class="eess-float-group" style="grid-column: span 2;">
                            <select name="user_status" id="u_user_status" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold;">
                                <option value="active">Active  (Active)</option>
                                <option value="suspended">  (Suspended)</option>
                                <option value="pending"> No (Pending Approval)</option>
                            </select>
                            <label for="u_user_status" class="eess-float-label">   <span style="color:#ef4444;">*</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 5: FINAL CONFIRMATION & REVIEW SUMMARY -->
            <div id="u_step_5_container" style="display: none;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">Confirm Save  </h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 13px; color: #334155;">
                        <div><strong>Full Name:</strong> <span id="rev_u_fullname">-</span></div>
                        <div><strong>Gender Date of Birth:</strong> <span id="rev_u_nat_dob">-</span></div>
                        <div><strong>Gender :</strong> <span id="rev_u_gender">-</span></div>
                        <div><strong>Email Address:</strong> <span id="rev_u_email" style="font-family: monospace;">-</span></div>
                        <div><strong>Mobile Number :</strong> <span id="rev_u_phone" style="font-family: monospace;">-</span></div>
                        <div><strong>  :</strong> <span id="rev_u_location">-</span></div>
                        <div><strong>Address :</strong> <span id="rev_u_address_info">-</span></div>
                        <div><strong>  :</strong> <span id="rev_u_role_id" style="color: #881337; font-weight: 800;">-</span></div>
                        <div id="rev_u_inst_container"><strong>Organization  :</strong> <span id="rev_u_inst_subj" style="color: #0284c7; font-weight: 800;">-</span></div>
                        <div id="rev_u_grades_container"><strong>Training Groups  :</strong> <span id="rev_u_grades">-</span></div>
                    </div>
                </div>
            </div>

            <!-- Footer Navigation Controls -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 20px;">
                <button type="button" onclick="eessCloseUnifiedUserModal()" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 20px; font-size: 13px; color: #64748b; border-radius: 9999px !important;">Cancel</button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="u_btn_prev" onclick="eessGoToStep(eessCurrentStep - 1)" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 22px; font-size: 13px; border-radius: 9999px !important; display: none;">Previous</button>
                    <button type="button" id="u_btn_next" onclick="eessGoToStep(eessCurrentStep + 1)" class="sm-btn" style="height: 40px; padding: 0 24px; font-size: 13px; font-weight: 800; background: #881337; color: white !important; border: none; border-radius: 9999px !important; cursor: pointer;">Next</button>
                    <button type="button" id="u_btn_save" onclick="eessSubmitUnifiedUserForm()" class="sm-btn" style="height: 40px; padding: 0 28px; font-size: 13px; font-weight: 800; background: #000000; color: white !important; border: none; border-radius: 9999px !important; cursor: pointer; display: none;">Save  </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.eess-float-group {
    position: relative;
    width: 100%;
}
.eess-float-input {
    width: 100%;
    box-sizing: border-box;
    transition: all 0.2s ease;
}
.eess-float-label {
    position: absolute;
    top: -10px;
    right: 14px;
    background: #ffffff;
    padding: 0 6px;
    font-size: 11px;
    font-weight: 800;
    color: #881337;
    pointer-events: none;
    border-radius: 4px;
    z-index: 5;
    transition: all 0.2s ease;
}
</style>

<script src="<?php echo SM_PLUGIN_URL . 'public/js/unified-user-modal.js'; ?>"></script>
