<?php
/**
 * Template Name: Public Exit Card Request Wizard & Data Update Portal
 * Shortcode: [card]
 */

if (!defined('ABSPATH')) exit;

$school_info = SM_Settings::get_school_info();
$sys_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
$school_name = '   ';
$ajax_url = admin_url('admin-ajax.php');
$admin_nonce = wp_create_nonce('sm_admin_action');

// Strict Administrative Visibility Check
$is_admin = class_exists('SM_Public') && SM_Public::is_card_admin();

$card_settings = get_option('sm_exit_card_settings', array(
    'portal_mode' => 'card_application',
    'verify_method' => 'both',
    'required_fields' => array('guardian_phone', 'dob'),
    'max_requests' => 3,
    'redirect_discipline' => 'yes',
    'service_update_data' => 'yes',
    'service_exit_card' => 'yes',
    'service_complaint' => 'yes',
    'service_sports' => 'yes'
));

$portal_mode          = $card_settings['portal_mode'] ?? 'card_application';
$verify_method        = $card_settings['verify_method'] ?? 'both';
$required_fields      = (array) ($card_settings['required_fields'] ?? array('guardian_phone', 'dob'));
$service_update_data  = $card_settings['service_update_data'] ?? 'yes';
$service_exit_card    = $card_settings['service_exit_card'] ?? 'yes';
$service_complaint    = $card_settings['service_complaint'] ?? 'yes';
$service_sports       = $card_settings['service_sports'] ?? 'yes';
?>

<style>
.eess-card-portal-wrapper {
    max-width: 1100px;
    margin: 20px auto;
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    align-items: flex-start;
    font-family: 'Cairo', sans-serif;
    direction: rtl;
    box-sizing: border-box;
    color: #0f172a;
}
.eess-portal-left-content {
    flex: 1 1 <?php echo $is_admin ? '660px' : '100%'; ?>;
    min-width: 320px;
    width: 100%;
    order: 2;
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 30px rgba(15,23,42,0.08);
    padding: 24px;
    box-sizing: border-box;
}
.eess-portal-right-sidebar {
    flex: 0 0 280px;
    width: 280px;
    max-width: 100%;
    order: 1;
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 30px rgba(15,23,42,0.08);
    padding: 18px;
    box-sizing: border-box;
}
.eess-service-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 1023px) and (min-width: 601px) {
    .eess-service-cards-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 600px) {
    .eess-service-cards-grid { grid-template-columns: repeat(2, 1fr); }
}
.eess-service-card {
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    border-radius: 16px;
    padding: 18px 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    height: 100%;
    box-sizing: border-box;
}
.eess-service-card:hover {
    border-color: #881337;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(136,19,55,0.12);
    transform: translateY(-2px);
}
.eess-req-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
.eess-req-fields-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (max-width: 820px) {
    .eess-portal-left-content { order: 2; flex: 1 1 100%; }
    .eess-portal-right-sidebar { order: 1; width: 100%; flex: 1 1 100%; }
    .eess-service-cards-grid { grid-template-columns: 1fr; }
    .eess-req-cards-grid { grid-template-columns: 1fr; }
    .eess-req-fields-grid { grid-template-columns: 1fr; }
}
.sb-nav-btn {
    width: 100%;
    text-align: right;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sb-nav-btn.active {
    background: #881337;
    color: #ffffff;
    border: none;
}
.sb-nav-btn.active svg {
    stroke: #ffffff;
}
.eess-btn-action-compact {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 30px;
    padding: 0 8px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: background 0.2s;
    box-sizing: border-box;
    text-decoration: none;
    white-space: nowrap;
}
</style>

<!-- Floating Toast Notification Component -->
<div id="eess-toast-notification" style="display: none; position: fixed; bottom: 30px; left: 30px; z-index: 999999; background: #0f172a; color: white; padding: 14px 20px; border-radius: 12px; font-weight: 800; font-size: 13px; box-shadow: 0 10px 25px rgba(0,0,0,0.25); direction: rtl; font-family: 'Cairo', sans-serif; align-items: center; gap: 10px; border-right: 5px solid #16a34a;">
    <span id="eess-toast-icon">✓</span>
    <span id="eess-toast-message"> Update .</span>
</div>

<!-- Outer Flexible Layout Wrapper -->
<div class="eess-card-portal-wrapper">

    <!-- LEFT CONTENT PANEL -->
    <div id="eess-portal-content-panel" class="eess-portal-left-content">

        <!-- VIEW 1: DEDICATED PHOTO & EXIT CARD PORTAL -->
        <div id="pv-view-wizard" style="display: block;">

            <!-- ACCESS GATE SCREEN (LOCKED UNTIL PASSWORD 202620272028 IS ENTERED) -->
            <div id="eess-portal-access-gate" style="display: block; max-width: 480px; margin: 40px auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 32px; text-align: center; direction: rtl; font-family: 'Cairo', sans-serif;">
                <div style="width: 72px; height: 72px; margin: 0 auto 14px auto; background: #ffffff; border-radius: 16px; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center;">
                    <img src="<?php echo esc_url($sys_logo); ?>" style="width: 100%; height: 100%; object-fit: contain; border-radius: 12px;" alt="Logo">
                </div>
                <h2 style="margin: 0 0 6px 0; font-size: 19px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></h2>
                <div style="font-size: 13.5px; color: #881337; font-weight: 800; margin-bottom: 20px;">     </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 800; color: #334155; display: block; margin-bottom: 8px;">     <span style="color:#ef4444;">*</span></label>
                    <input type="password" id="eess_portal_pwd_input" placeholder="Password..." onkeyup="if(event.key==='Enter') eessVerifyPortalPassword()" style="width: 100%; height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 16px; font-size: 15px; font-weight: 800; text-align: center; letter-spacing: 2px; box-sizing: border-box;">
                    <button type="button" onclick="eessVerifyPortalPassword()" id="eess_btn_unlock_portal" style="width: 100%; height: 46px; background: #881337; color: white; border: none; border-radius: 12px; font-weight: 900; font-size: 14px; cursor: pointer; margin-top: 14px; box-shadow: 0 4px 12px rgba(136,19,55,0.25);">   </button>
                </div>
                <div style="font-size: 11px; color: #94a3b8; font-weight: 600;">      No     .</div>
            </div>

            <!-- UNLOCKED PORTAL WORKFLOW (SEARCH -> PHOTO UPLOAD -> INSTANT EXIT CARD REQUEST) -->
            <div id="eess-portal-unlocked-workflow" style="display: none;">
                <!-- Header Banner with Lock Option -->
                <div style="border-bottom: 2px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <img src="<?php echo esc_url($sys_logo); ?>" style="width: 44px; height: 44px; object-fit: contain; border-radius: 10px; border: 1px solid #cbd5e1; padding: 2px;" alt="Logo">
                        <div>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></h3>
                            <div style="font-size: 12px; color: #881337; font-weight: 800;"> No   </div>
                        </div>
                    </div>
                    <button type="button" onclick="eessLockPortalSession()" style="height: 36px; padding: 0 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer;"> Close/ </button>
                </div>

                <!-- STEP 1: STUDENT SEARCH (REQUIRES >= 5 CHARACTERS) -->
                <div style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 22px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                    <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 900; color: #0f172a;">1. Search  No  :</h3>
                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #64748b; font-weight: 600;"> Player Name (  5    View No ):</p>

                    <div style="position: relative;">
                        <input type="text" id="eess_stu_search_input" oninput="eessDebounceSearchStudent()" placeholder="Search  Player Name   No..." style="width: 100%; height: 46px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 0 16px; font-size: 13.5px; font-weight: 800; box-sizing: border-box;">
                        <div id="eess_stu_search_suggestions" style="display: none; position: absolute; top: 50px; right: 0; left: 0; z-index: 99999;"></div>
                    </div>
                </div>

                <!-- STEP 2: SELECTED STUDENT & PHOTO UPLOAD CARD -->
                <div id="eess-stu-selected-card" style="display: none; background: #ffffff; border: 2px solid #881337; border-radius: 16px; padding: 22px; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(136,19,55,0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 72px; height: 84px; border-radius: 10px; border: 2px solid #cbd5e1; overflow: hidden; background: #f8fafc; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                <img id="eess_stu_current_photo_img" src="<?php echo esc_url($sys_logo); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Student Photo">
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #166534; font-weight: 800; margin-bottom: 2px;">✓ No :</div>
                                <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a;" id="eess_sel_stu_name">---</h3>
                                <div style="font-size: 12.5px; color: #881337; font-weight: 800;" id="eess_sel_stu_meta">---</div>
                                <div style="font-size: 11.5px; margin-top: 4px; font-weight: 700;" id="eess_photo_status_lbl">---</div>
                            </div>
                        </div>

                        <button type="button" onclick="eessResetSelectedStudent()" style="height: 36px; padding: 0 14px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer;">  No </button>
                    </div>

                    <!-- Direct Photo Upload Area & Requirements Guidance -->
                    <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 900; color: #166534;">2.  /    No:</h4>
                        <p style="margin: 0 0 10px 0; font-size: 12px; color: #14532d; font-weight: 600;">     Update   No:</p>

                        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; margin-bottom: 12px; font-size: 11.5px; color: #334155; line-height: 1.6; font-weight: 700;">
                            <div>    :</div>
                            <div style="color: #64748b;">•       .</div>
                            <div style="color: #64748b;">•       Academy .</div>
                            <div style="color: #64748b;">•       .</div>
                        </div>

                        <input type="file" id="eess_stu_photo_input" accept="image/jpeg,image/png,image/webp" onchange="eessUploadStudentPhoto(this)" style="width: 100%; font-size: 12.5px; background: white; padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                        <div id="eess_photo_upload_msg" style="margin-top: 8px; font-size: 12px; font-weight: 800;"></div>
                        <div id="eess_photo_last_updated_lbl" style="margin-top: 4px; font-size: 11px; color: #64748b; font-weight: 700;"></div>
                    </div>

                    <!-- 3 Independent Post-Upload Action Buttons -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <button type="button" id="eess_btn_save_photo_only" onclick="eessSavePhotoOnly()" style="height: 46px; background: #0284c7; color: white; border: none; border-radius: 10px; font-weight: 900; font-size: 13px; cursor: pointer; box-shadow: 0 4px 10px rgba(2,132,199,0.2);"> Update  </button>
                        <button type="button" id="eess_btn_instant_exit" onclick="eessSubmitInstantExitCardRequest()" style="height: 46px; background: #881337; color: white; border: none; border-radius: 10px; font-weight: 900; font-size: 13px; cursor: pointer; box-shadow: 0 4px 10px rgba(136,19,55,0.2);">  Confirm   </button>
                        <button type="button" id="eess_btn_withdraw_exit" onclick="eessPromptWithdrawExitCard()" style="height: 46px; background: #dc2626; color: white; border: none; border-radius: 10px; font-weight: 900; font-size: 13px; cursor: pointer; box-shadow: 0 4px 10px rgba(220,38,38,0.2);">  / Cancel   </button>
                    </div>
                </div>

                <!-- INSTANT SUCCESS CARD WITH DETAILED CODES & METADATA -->
                <div id="eess-instant-success-card" style="display: none; background: #ffffff; border: 2px solid #16a34a; border-radius: 18px; padding: 24px; text-align: center; margin-bottom: 20px; box-shadow: 0 8px 25px rgba(22,163,74,0.1);">
                    <div style="width: 56px; height: 56px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; color: #16a34a; font-size: 26px; font-weight: 900;">✓</div>
                    <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 900; color: #14532d;" id="eess_succ_card_title">      </h3>
                    <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #166534; font-weight: 700;" id="eess_succ_card_msg">      Home    .</p>

                    <!-- PROMINENT CODES DISPLAY GRID -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; text-align: right;">
                        <div style="background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 12px; padding: 12px;">
                            <div style="font-size: 11px; color: #0369a1; font-weight: 800; margin-bottom: 4px;">  No  (Student Code):</div>
                            <div style="font-size: 16px; font-weight: 900; color: #0284c7; font-family: monospace;" id="eess_succ_stu_code">---</div>
                        </div>

                        <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 12px;">
                            <div style="font-size: 11px; color: #15803d; font-weight: 800; margin-bottom: 4px;">     (Request No):</div>
                            <div style="font-size: 16px; font-weight: 900; color: #16a34a; font-family: monospace;" id="eess_succ_ref_no">---</div>
                        </div>
                    </div>

                    <!-- DETAILED METADATA LIST -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 20px; font-size: 12px; color: #334155; line-height: 1.8; text-align: right;">
                        <div><strong>Player Name :</strong> <span id="eess_succ_stu_name" style="font-weight: 900; color: #0f172a;">---</span></div>
                        <div><strong>Academy  / Organization :</strong> <span id="eess_succ_school" style="font-weight: 800; color: #881337;"><?php echo esc_html($school_name); ?></span></div>
                        <div><strong>Training Group Training Group:</strong> <span id="eess_succ_grade_section" style="font-weight: 800;">---</span></div>
                        <div><strong>   :</strong> <span id="eess_succ_requested_at" style="font-weight: 800;">---</span></div>
                        <div><strong>  :</strong> <span id="eess_succ_status_lbl" style="font-weight: 900; color: #16a34a;">---</span></div>
                        <div><strong> Update  :</strong> <span id="eess_succ_photo_updated_at" style="font-weight: 800;">---</span></div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" onclick="eessResetSelectedStudent()" style="height: 42px; padding: 0 24px; background: #166534; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;"> Search  No </button>
                    </div>
                </div>
            </div>

            <!-- OLD WIZARD PANEL (RETAINED FOR BACKWARD COMPATIBILITY IF NEEDED) -->
            <div id="w-panel-step-0" style="display: none;">
                <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 900; color: #0f172a; text-align: center;">   :</h3>
                <p style="margin: 0 0 20px 0; font-size: 12px; color: #64748b; text-align: center;">         No :</p>

                <div class="eess-service-cards-grid">
                    <!-- Service 1: Update Data -->
                    <?php if ($service_update_data === 'yes'): ?>
                        <div class="eess-service-card" onclick="wSelectPortalService('update_data')">
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0284c7;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;">Update  </div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.4;">   No   Academy .</div>
                        </div>
                    <?php endif; ?>

                    <!-- Service 2: Exit Card Request -->
                    <?php if ($service_exit_card === 'yes'): ?>
                        <div class="eess-service-card" onclick="wSelectPortalService('exit_card')">
                            <div style="width: 48px; height: 48px; background: #ffe4e6; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #881337;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="M7 15h0M2 9.5h20"></path></svg>
                            </div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;">   </div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.4;">Issue    No  No.</div>
                        </div>
                    <?php endif; ?>

                    <!-- Service 3: Submit Complaint -->
                    <?php if ($service_complaint === 'yes'): ?>
                        <div class="eess-service-card" onclick="wSelectPortalService('complaint')">
                            <div style="width: 48px; height: 48px; background: #fffbe3; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #854d0e;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12.01" y2="18"></line><line x1="12" y1="11" x2="12" y2="15"></line></svg>
                            </div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;">  / </div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.4;">       Academy .</div>
                        </div>
                    <?php endif; ?>

                    <!-- Service 4: Sports Registration -->
                    <?php if ($service_sports === 'yes'): ?>
                        <div class="eess-service-card" onclick="wSelectPortalService('sports')">
                            <div style="width: 48px; height: 48px; background: #f0fdf4; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #166534;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                            </div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;"> Sports Activities</div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.4;"> No Sports Activities (2  ).</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MULTI-STEP PROGRESS INDICATOR (Displayed when inside a service flow) -->
            <div id="w-progress-bar" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 24px; position: relative;">
                <div style="position: absolute; top: 50%; right: 10%; left: 10%; height: 3px; background: #e2e8f0; z-index: 1; transform: translateY(-50%);"></div>
                <div id="w-progress-line" style="position: absolute; top: 50%; right: 10%; width: 0%; height: 3px; background: #881337; z-index: 1; transform: translateY(-50%); transition: width 0.3s ease;"></div>

                <div class="w-step-item active" id="w-step-ind-1" style="position: relative; z-index: 2; text-align: center;">
                    <div class="w-step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #881337; color: white; font-weight: 900; font-size: 13px; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">1</div>
                    <div style="font-size: 11px; font-weight: 800; color: #881337;"> No</div>
                </div>

                <div class="w-step-item" id="w-step-ind-2" style="position: relative; z-index: 2; text-align: center;">
                    <div class="w-step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #cbd5e1; color: #475569; font-weight: 900; font-size: 13px; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">2</div>
                    <div style="font-size: 11px; font-weight: 800; color: #64748b;" id="w_step_2_label"> </div>
                </div>

                <div class="w-step-item" id="w-step-ind-3" style="position: relative; z-index: 2; text-align: center; display: none;">
                    <div class="w-step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #cbd5e1; color: #475569; font-weight: 900; font-size: 13px; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">3</div>
                    <div style="font-size: 11px; font-weight: 800; color: #64748b;"> </div>
                </div>

                <div class="w-step-item" id="w-step-ind-4" style="position: relative; z-index: 2; text-align: center; display: none;">
                    <div class="w-step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #cbd5e1; color: #475569; font-weight: 900; font-size: 13px; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">4</div>
                    <div style="font-size: 11px; font-weight: 800; color: #64748b;">Send </div>
                </div>
            </div>

            <!-- STEP 1: COMPLETE FULL NAME STUDENT SEARCH -->
            <div id="w-panel-step-1" style="display: none;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                        <label style="font-size: 13px; font-weight: 800; color: #0f172a; margin: 0;"> Player Name   :</label>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="wToggleCheckPreviousReqModal(true)" style="background: #0284c7; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 11.5px; cursor: pointer;">   </button>
                            <button type="button" onclick="wToggleCheckComplaintModal(true)" style="background: #854d0e; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 11.5px; cursor: pointer;">  </button>
                        </div>
                    </div>

                    <input type="text" id="w_student_name_input" onkeyup="wDebounceSearchName()" placeholder=" Player Name No/  Academy ..." style="width: 100%; height: 44px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: 700; box-sizing: border-box;">
                    <div style="font-size: 11px; color: #64748b; margin-top: 6px;">:    Player Name   No (3   )  No.</div>
                </div>

                <div id="w-search-suggestions" style="display: none; margin-bottom: 18px;"></div>

                <div id="w-selected-stu-box" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 16px; margin-bottom: 18px;">
                    <div style="font-size: 11px; color: #166534; font-weight: 800; margin-bottom: 4px;">✓   No:</div>
                    <div style="font-size: 16px; font-weight: 900; color: #14532d; margin-bottom: 4px;" id="w_sel_stu_name"></div>
                    <div style="font-size: 12px; color: #15803d; font-weight: 700;" id="w_sel_stu_class"></div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" onclick="wBackToStep0()" style="height: 42px; padding: 0 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">➔   </button>
                    <button type="button" id="w_btn_next_1" disabled onclick="wGoToStep(2)" style="height: 44px; padding: 0 28px; background: #881337; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: not-allowed; opacity: 0.5;">No  Next ➔</button>
                </div>
            </div>

            <!-- STEP 2: DYNAMIC SERVICE STEP (VERIFICATION / COMPLAINT / SPORTS / UPDATE DATA) -->
            <div id="w-panel-step-2" style="display: none;">

                <!-- IDENTITY VERIFICATION BOX (Unified 3-Factor: Full Name + Student Code / National ID + DOB) -->
                <div id="w-verify-identity-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 800; color: #0f172a;">Confirm    No</h4>
                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #64748b; font-weight: 600;">   No  National ID Date of Birth :</p>

                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;"> No   National ID <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="w_verify_code_input" placeholder="  No  National ID..." style="width: 100%; height: 44px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: 700; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Date of Birth  <span style="color:#ef4444;">*</span></label>
                        <input type="date" id="w_verify_dob_input" style="width: 100%; height: 44px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: 700; box-sizing: border-box;">
                    </div>

                    <button type="button" onclick="wVerifyStudentIdentity()" id="w_btn_verify_id" style="width: 100%; height: 42px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">Confirm   </button>
                </div>

                <div id="w-verified-status-panel" style="display: none; margin-bottom: 18px;"></div>

                <!-- DYNAMIC SERVICE SPECIFIC FORM BODIES -->
                <!-- 1. COMPLAINT FORM BODY -->
                <div id="w-service-form-complaint" style="display: none; background: #fffbe3; border: 1.5px solid #fde047; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 900; color: #854d0e;">  / No</h4>

                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 800; color: #0f172a; display: block; margin-bottom: 4px;">  <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="w_cmp_title_input" placeholder=": No    / ..." style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <label style="font-size: 12px; font-weight: 800; color: #0f172a;">  <span style="color:#ef4444;">*</span></label>
                            <span style="font-size: 11px; color: #64748b;" id="w_cmp_char_cnt">0 / 1000 </span>
                        </div>
                        <textarea id="w_cmp_details_input" maxlength="1000" onkeyup="document.getElementById('w_cmp_char_cnt').innerText = this.value.length + ' / 1000 '" placeholder="       (  1000 )..." style="width: 100%; height: 110px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 12.5px; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>

                    <button type="button" onclick="wSubmitComplaintFinal()" id="w_btn_submit_cmp" style="height: 42px; padding: 0 28px; background: #854d0e; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">Send   ✓</button>
                </div>

                <!-- 2. SPORTS REGISTRATION FORM BODY -->
                <div id="w-service-form-sports" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 900; color: #166534;"> Sports Activities  (2  )</h4>
                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #14532d;"> Sports Activities No   No    :</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                        <?php
                        $sports_list = array(' ', ' ', ' ', ' ', '  ', ' ', ' ', ' ');
                        foreach ($sports_list as $sp):
                        ?>
                            <label style="display: flex; align-items: center; gap: 8px; background: white; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font-size: 12.5px; font-weight: 800; cursor: pointer;">
                                <input type="checkbox" name="sports_activity[]" value="<?php echo esc_attr($sp); ?>" onchange="wLimitSportsCheckboxes(this)" style="width: 18px; height: 18px;">
                                <span><?php echo esc_html($sp); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" onclick="wSubmitSportsFinal()" id="w_btn_submit_spt" style="height: 42px; padding: 0 28px; background: #166534; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">Confirm  Sports Activities ✓</button>
                </div>

                <!-- 3. MISSING REQUIRED DATA FORM -->
                <div id="w-missing-data-container" style="display: none; background: #fffbe3; border: 1.5px solid #fde047; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <div style="font-size: 13.5px; font-weight: 900; color: #854d0e; margin-bottom: 8px;">⚠️    :</div>
                    <form id="w_missing_data_form" onsubmit="wSubmitMissingData(event)">
                        <div id="w_missing_fields_render_box"></div>
                        <div style="margin-top: 14px; text-align: left;">
                            <button type="submit" id="w_btn_save_missing" style="height: 42px; padding: 0 24px; background: #854d0e; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">Save Update  </button>
                        </div>
                    </form>
                </div>

                <!-- 4. OFFICIAL PHOTO UPLOAD BOX -->
                <div id="w-photo-upload-container" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <div style="font-weight: 800; font-size: 13.5px; color: #166534; margin-bottom: 8px;">     No</div>
                    <input type="file" id="w_student_photo_file" accept="image/jpeg,image/png,image/webp" onchange="wValidateStudentPhoto(this)" style="width: 100%; font-size: 12px; background: white; padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" onclick="wGoToStep(1)" style="height: 42px; padding: 0 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">➔ Previous</button>
                    <button type="button" id="w_btn_next_2" style="display: none; height: 44px; padding: 0 28px; background: #881337; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer;" onclick="wGoToStep(3)">   ➔</button>
                </div>
            </div>

            <!-- STEP 3: PARENT DECLARATION (FOR EXIT CARD ONLY) -->
            <div id="w-panel-step-3" style="display: none;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 800; color: #0f172a;">  Mother  </h4>

                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">  Mother No <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="w_parent_name" placeholder=" Full Name  Mother..." style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">   <span style="color:#ef4444;">*</span></label>
                        <div style="display: flex; align-items: center; gap: 6px; direction: ltr;">
                            <span style="background: #e2e8f0; border: 1px solid #cbd5e1; padding: 10px 12px; border-radius: 8px; font-size: 13px; font-weight: 800; color: #0f172a;">+971</span>
                            <input type="tel" id="w_parent_phone" placeholder="501234567" style="flex: 1; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; box-sizing: border-box; text-align: left;">
                        </div>
                    </div>

                    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 16px;">
                        <input type="checkbox" id="w_declaration_chk" onchange="wCheckStep3Valid()" style="width: 18px; height: 18px; margin-top: 1px;">
                        <span>     No    No   Academy  *</span>
                    </label>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="font-size: 12px; font-weight: 800; color: #0f172a;">   Mother <span style="color:#ef4444;">*</span></label>
                            <button type="button" onclick="wClearSignature()" style="background: #fee2e2; color: #991b1b; border: none; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; cursor: pointer;">  ↺</button>
                        </div>
                        <div style="border: 2px dashed #cbd5e1; border-radius: 12px; background: #ffffff; overflow: hidden; touch-action: none;">
                            <canvas id="w-signature-pad" width="600" height="150" style="width: 100%; height: 140px; display: block; cursor: crosshair;"></canvas>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" onclick="wGoToStep(2)" style="height: 42px; padding: 0 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">➔ Previous</button>
                    <button type="button" id="w_btn_next_3" disabled onclick="wGoToStep(4)" style="height: 44px; padding: 0 28px; background: #881337; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: not-allowed; opacity: 0.5;">   ➔</button>
                </div>
            </div>

            <!-- STEP 4: FINAL SUMMARY & SUBMIT (FOR EXIT CARD ONLY) -->
            <div id="w-panel-step-4" style="display: none;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 18px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 900; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;"> Confirm   </h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; color: #334155; line-height: 1.6; margin-bottom: 14px;">
                        <div><strong>Player Name:</strong> <span id="w_sum_stu_name" style="color: #0f172a; font-weight: 800;">---</span></div>
                        <div><strong>Training Group Training Group:</strong> <span id="w_sum_stu_class" style="color: #0f172a; font-weight: 800;">---</span></div>
                        <div><strong> No:</strong> <span id="w_sum_stu_code" style="color: #881337; font-weight: 800;">---</span></div>
                        <div><strong> Mother:</strong> <span id="w_sum_parent_name" style="color: #0f172a; font-weight: 800;">---</span></div>
                    </div>

                    <div style="border-top: 1px solid #e2e8f0; padding-top: 10px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #64748b; margin-bottom: 4px;">   :</div>
                        <div style="background: white; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px; text-align: center;">
                            <img id="w_sum_sig_img" src="" style="max-height: 60px; object-fit: contain;" alt="Signature Preview">
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" onclick="wGoToStep(3)" style="height: 42px; padding: 0 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">➔ Edit </button>
                    <button type="button" id="w_btn_submit_final" onclick="wSubmitExitCardFinal()" style="height: 46px; padding: 0 32px; background: #16a34a; color: white; border: none; border-radius: 10px; font-weight: 900; font-size: 14px; cursor: pointer;">Confirm Send   ✓</button>
                </div>
            </div>

            <!-- SUCCESS CONFIRMATION SCREEN -->
            <div id="w-panel-success" style="display: none; text-align: center; padding: 20px 10px;">
                <div style="width: 64px; height: 64px; background: #dcfce7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #16a34a; margin-bottom: 14px; font-size: 28px; font-weight: 900;">✓</div>
                <h3 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 900; color: #15803d;" id="w_success_title">   </h3>
                <p style="font-size: 13px; color: #475569; margin: 0 0 16px 0;" id="w_success_sub">        .</p>

                <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 14px; padding: 16px; max-width: 380px; margin: 0 auto 16px auto;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 800; margin-bottom: 2px;">  :</div>
                    <div style="font-size: 22px; font-weight: 900; color: #881337; font-family: monospace;" id="w_success_ref_no">REF-2026-00000</div>
                </div>

                <button type="button" onclick="location.reload()" style="height: 42px; padding: 0 26px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">  Home</button>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <!-- ADMIN VIEW 1: COMPLAINTS MANAGEMENT TAB -->
            <div id="pv-view-complaints-management" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a;">   No</h3>
                        <div style="font-size: 12px; color: #64748b;">  Print      Mother  No:</div>
                    </div>
                    <button type="button" onclick="wLoadComplaintsFull()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer;">Update  ↺</button>
                </div>
                <div id="pv-complaints-cards-container" class="eess-req-cards-grid">
                    <div style="text-align: center; color: #64748b; padding: 20px; font-size: 13px; grid-column: span 2;"> Upload ...</div>
                </div>
            </div>

            <!-- ADMIN VIEW 2: SPORTS REGISTRATIONS MANAGEMENT TAB -->
            <div id="pv-view-sports-management" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a;">   Sports Activities</h3>
                        <div style="font-size: 12px; color: #64748b;">  No Sports Activities No :</div>
                    </div>
                    <button type="button" onclick="wLoadSportsFull()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer;">Update  ↺</button>
                </div>
                <div id="pv-sports-cards-container" class="eess-req-cards-grid">
                    <div style="text-align: center; color: #64748b; padding: 20px; font-size: 13px; grid-column: span 2;"> Upload  ...</div>
                </div>
            </div>

            <!-- ADMIN VIEW 3: REQUIRED DATA SETTINGS -->
            <div id="pv-view-required-data" style="display: none;">
                <div style="border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px;">
                    <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a;">   Update</h3>
                    <div style="font-size: 12px; color: #64748b;">   No  No/ Mother      :</div>
                </div>

                <form onsubmit="wSavePortalSettingsFromView(event)">
                    <input type="hidden" name="portal_mode" value="<?php echo esc_attr($portal_mode); ?>">
                    <input type="hidden" name="verify_method" value="<?php echo esc_attr($verify_method); ?>">

                    <div class="eess-req-fields-grid" style="margin-bottom: 20px;">
                        <?php
                        $available_fields = array(
                            'guardian_phone' => '   Mother (+971)',
                            'dob' => 'Date of Birth',
                            'gender' => 'Gender',
                            'guardian_name' => '  Mother No',
                            'emirate' => ' ',
                            'address' => 'Address  ',
                            'nationality' => 'Gender',
                            'national_id' => ' National ID'
                        );
                        foreach ($available_fields as $fk => $flabel):
                            $chk = in_array($fk, $required_fields) ? 'checked' : '';
                        ?>
                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800; color: #0f172a;">
                                <input type="checkbox" name="required_fields[]" value="<?php echo esc_attr($fk); ?>" <?php echo $chk; ?> style="width: 18px; height: 18px;">
                                <span><?php echo esc_html($flabel); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" style="height: 44px; padding: 0 28px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">Save  </button>
                </form>
            </div>

            <!-- ADMIN VIEW 4: PORTAL OPERATING MODE & SERVICE TOGGLES -->
            <div id="pv-view-operating-mode" style="display: none;">
                <div style="border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px;">
                    <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a;">⚙️    </h3>
                    <div style="font-size: 12px; color: #64748b;">   No   :</div>
                </div>

                <form onsubmit="wSavePortalSettingsFromView(event)">
                    <?php foreach ($required_fields as $rf): ?>
                        <input type="hidden" name="required_fields[]" value="<?php echo esc_attr($rf); ?>">
                    <?php endforeach; ?>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 13px; font-weight: 900; color: #0f172a; display: block; margin-bottom: 8px;">1.   No :</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="checkbox" name="service_update_data" value="yes" <?php checked($service_update_data, 'yes'); ?> style="width: 18px; height: 18px;">
                                <span>Update  </span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="checkbox" name="service_exit_card" value="yes" <?php checked($service_exit_card, 'yes'); ?> style="width: 18px; height: 18px;">
                                <span>   </span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="checkbox" name="service_complaint" value="yes" <?php checked($service_complaint, 'yes'); ?> style="width: 18px; height: 18px;">
                                <span>  / </span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="checkbox" name="service_sports" value="yes" <?php checked($service_sports, 'yes'); ?> style="width: 18px; height: 18px;">
                                <span> Sports Activities</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 13px; font-weight: 900; color: #0f172a; display: block; margin-bottom: 8px;">2.    :</label>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="radio" name="verify_method" value="both" <?php checked($verify_method, 'both'); ?> style="width: 18px; height: 18px;">
                                <span> No   National ID (No )</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="radio" name="verify_method" value="code" <?php checked($verify_method, 'code'); ?> style="width: 18px; height: 18px;">
                                <span> No  (Student Code Only)</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 800;">
                                <input type="radio" name="verify_method" value="nat_id" <?php checked($verify_method, 'nat_id'); ?> style="width: 18px; height: 18px;">
                                <span> National ID  (National ID Only)</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 900; color: #166534;">
                                <input type="radio" name="verify_method" value="name_only" <?php checked($verify_method, 'name_only'); ?> style="width: 18px; height: 18px;">
                                <span>Full Name   (   )</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" style="height: 44px; padding: 0 28px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">Save Settings </button>
                </form>
            </div>

            <!-- ADMIN VIEW 5: EXIT CARD REQUESTS MANAGEMENT -->
            <div id="pv-view-requests-management" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a;">    </h3>
                        <div style="font-size: 12px; color: #64748b;">     :</div>
                    </div>
                    <button type="button" onclick="wLoadCardRequestsFull()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer;">Update  ↺</button>
                </div>
                <div id="pv-requests-cards-container" class="eess-req-cards-grid">
                    <div style="text-align: center; color: #64748b; padding: 20px; font-size: 13px; grid-column: span 2;"> Upload   ...</div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT NAVIGATION SIDEBAR (Admin Staff Only) -->
    <?php if ($is_admin): ?>
        <div id="eess-portal-nav-sidebar" class="eess-portal-right-sidebar">
            <div style="font-size: 14px; font-weight: 900; color: #0f172a; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">  </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button type="button" class="sb-nav-btn active" onclick="wSwitchPortalView('pv-view-wizard', this)">  /  </button>
                <button type="button" class="sb-nav-btn" onclick="wSwitchPortalView('pv-view-requests-management', this)">  </button>
                <button type="button" class="sb-nav-btn" onclick="wSwitchPortalView('pv-view-complaints-management', this)">  No</button>
                <button type="button" class="sb-nav-btn" onclick="wSwitchPortalView('pv-view-sports-management', this)"> Sports Activities</button>
                <button type="button" class="sb-nav-btn" onclick="wSwitchPortalView('pv-view-required-data', this)">  Update</button>
                <button type="button" class="sb-nav-btn" onclick="wSwitchPortalView('pv-view-operating-mode', this)">  </button>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- CHECK PREVIOUS EXIT REQUEST MODAL -->
<div id="w-check-req-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.65); z-index: 999999; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box;">
    <div style="background: white; border-radius: 20px; max-width: 480px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); direction: rtl; font-family: 'Cairo', sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;"> NoNo    </h3>
            <button type="button" onclick="wToggleCheckPreviousReqModal(false)" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <input type="text" id="w_check_query_input" placeholder="  National ID    ..." style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 0 12px; font-size: 13px; font-weight: 700; box-sizing: border-box; margin-bottom: 12px;">
        <button type="button" onclick="wExecuteCheckPreviousRequest()" style="width: 100%; height: 42px; background: #0284c7; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">No </button>
        <div id="w-check-req-result-box" style="display: none; margin-top: 14px;"></div>
    </div>
</div>

<!-- CHECK COMPLAINT STATUS MODAL -->
<div id="w-check-cmp-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.65); z-index: 999999; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box;">
    <div style="background: white; border-radius: 20px; max-width: 480px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); direction: rtl; font-family: 'Cairo', sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;"> NoNo   </h3>
            <button type="button" onclick="wToggleCheckComplaintModal(false)" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <input type="text" id="w_check_cmp_query_input" placeholder="  No      ..." style="width: 100%; height: 42px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 0 12px; font-size: 13px; font-weight: 700; box-sizing: border-box; margin-bottom: 12px;">
        <button type="button" onclick="wExecuteCheckComplaintStatus()" style="width: 100%; height: 42px; background: #854d0e; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">No  </button>
        <div id="w-check-cmp-result-box" style="display: none; margin-top: 14px;"></div>
    </div>
</div>

<!-- VIEW REQUEST DETAILS MODAL FOR ADMINS -->
<div id="w-req-view-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); z-index: 99999; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box;">
    <div style="background: white; border-radius: 18px; max-width: 520px; width: 100%; padding: 22px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); direction: rtl; font-family: 'Cairo', sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;" id="modal_req_title">   </h4>
            <button type="button" onclick="document.getElementById('w-req-view-modal').style.display='none'" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div id="modal_req_body" style="font-size: 12.5px; color: #334155; line-height: 1.7;"></div>
    </div>
</div>

<!-- Custom In-System Modal for Request Withdrawal -->
<div id="eess-withdraw-confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.65); z-index: 999999; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box;">
    <div style="background: white; border-radius: 20px; max-width: 440px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); direction: rtl; font-family: 'Cairo', sans-serif; text-align: center;">
        <div style="width: 56px; height: 56px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; color: #dc2626; font-size: 24px; font-weight: 900;">⚠️</div>
        <h3 style="margin: 0 0 6px 0; font-size: 17px; font-weight: 900; color: #0f172a;">Confirm  Cancel   </h3>
        <p style="margin: 0 0 14px 0; font-size: 13px; color: #475569; line-height: 1.5;">
                 Cancel     No <strong id="eess_withdraw_student_name_lbl" style="color: #881337;">---</strong>
        </p>
        <div style="background: #fffbe3; border: 1px solid #fde047; border-radius: 10px; padding: 10px; font-size: 11.5px; color: #854d0e; margin-bottom: 18px; line-height: 1.5; text-align: right;">
             :       Print        No   .
        </div>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="document.getElementById('eess-withdraw-confirm-modal').style.display='none'" style="height: 40px; padding: 0 22px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">Cancel</button>
            <button type="button" id="eess_btn_confirm_withdraw" onclick="eessExecuteWithdrawExitCard()" style="height: 40px; padding: 0 22px; background: #dc2626; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">Confirm  </button>
        </div>
    </div>
</div>

<!-- IN-SYSTEM DELETE CONFIRMATION MODAL -->
<div id="eess-delete-confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.65); z-index: 999999; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box;">
    <div style="background: white; border-radius: 20px; max-width: 440px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); direction: rtl; font-family: 'Cairo', sans-serif; text-align: center;">
        <div style="width: 56px; height: 56px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; color: #dc2626;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </div>
        <h3 style="margin: 0 0 6px 0; font-size: 17px; font-weight: 900; color: #0f172a;">Confirm Delete   </h3>
        <p style="margin: 0 0 14px 0; font-size: 13px; color: #475569; line-height: 1.5;">
                Delete   No <strong id="eess_del_student_name" style="color: #881337;">---</strong>
        </p>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; font-size: 11.5px; color: #64748b; margin-bottom: 18px; line-height: 1.5;">
             :  Delete      Edit  Delete  No   .
        </div>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="wCloseDeleteModal()" style="height: 40px; padding: 0 22px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">Cancel</button>
            <button type="button" id="eess_btn_confirm_delete" onclick="wExecuteConfirmDelete()" style="height: 40px; padding: 0 22px; background: #dc2626; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer;">Confirm Delete </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var token = sessionStorage.getItem('eess_portal_token');
    if (token) {
        var gate = document.getElementById('eess-portal-access-gate');
        var main = document.getElementById('eess-portal-unlocked-workflow');
        if (gate) gate.style.display = 'none';
        if (main) main.style.display = 'block';
    }
});

function eessVerifyPortalPassword() {
    var pwdInput = document.getElementById('eess_portal_pwd_input');
    var pwd = pwdInput ? pwdInput.value.trim() : '';
    if (!pwd) {
        eessShowToast('    .', 'error');
        return;
    }

    var btn = document.getElementById('eess_btn_unlock_portal');
    if (btn) { btn.disabled = true; btn.innerText = ' ...'; }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_verify_portal_password',
        password: pwd
    }, function(res) {
        if (btn) { btn.disabled = false; btn.innerText = '   '; }
        if (res.success && res.data && res.data.token) {
            sessionStorage.setItem('eess_portal_token', res.data.token);
            eessShowToast(res.data.message || '  .', 'success');
            var gate = document.getElementById('eess-portal-access-gate');
            var main = document.getElementById('eess-portal-unlocked-workflow');
            if (gate) gate.style.display = 'none';
            if (main) main.style.display = 'block';
        } else {
            eessShowToast((res && res.data) ? res.data : 'Password  .', 'error');
        }
    });
}

function eessLockPortalSession() {
    sessionStorage.removeItem('eess_portal_token');
    eessResetSelectedStudent();
    var gate = document.getElementById('eess-portal-access-gate');
    var main = document.getElementById('eess-portal-unlocked-workflow');
    if (gate) gate.style.display = 'block';
    if (main) main.style.display = 'none';
    eessShowToast('   Close .', 'success');
}

function eessDebounceSearchStudent() {
    clearTimeout(wSearchTimeout);
    wSearchTimeout = setTimeout(eessSearchStudentName, 300);
}

function eessSearchStudentName() {
    var val = document.getElementById('eess_stu_search_input').value.trim();
    var suggestions = document.getElementById('eess_stu_search_suggestions');

    // Requirement: Start searching ONLY after entering at least 5 characters
    if (val.length < 5) {
        suggestions.style.display = 'none';
        return;
    }

    suggestions.style.display = 'block';
    suggestions.innerHTML = '<div style="text-align:center; padding:12px; font-weight:700; color:#64748b; font-size:12px;"> Search  ...</div>';

    var token = sessionStorage.getItem('eess_portal_token') || '';

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_search_student',
        name_query: val,
        portal_token: token
    }, function(res) {
        if (res.success && res.data && res.data.length > 0) {
            let html = '<div style="background:#ffffff; border:1.5px solid #cbd5e1; border-radius:12px; max-height:240px; overflow-y:auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">';
            res.data.forEach(s => {
                var stuName = s.display_name || (s.name || '');
                var escName = stuName.replace(/'/g, "\\'");
                html += '<div onclick="eessSelectStudentForPhoto(' + s.id + ', \'' + escName + '\', \'' + s.class_name + '\', \'' + s.section + '\', \'' + (s.student_code || '') + '\')" style="padding:12px 16px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:13px; font-weight:800; color:#0f172a;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">';
                html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
                html += '<div>' + stuName + '</div>';
                html += '<div style="font-size:11px; color:#881337; font-family:monospace; font-weight:900;">' + (s.student_code || '  ') + '</div>';
                html += '</div>';
                html += '<div style="font-size:11.5px; color:#64748b; font-weight:700; margin-top:2px;">' + s.class_name + ' (' + s.section + ')</div>';
                html += '</div>';
            });
            html += '</div>';
            suggestions.innerHTML = html;
        } else {
            suggestions.innerHTML = '<div style="background:#fef2f2; border:1px solid #fecdd3; border-radius:12px; padding:14px; color:#991b1b; font-size:12.5px; font-weight:700; text-align:center;">    No  Search (  5   ).</div>';
        }
    });
}

function eessSelectStudentForPhoto(id, displayName, className, section, code) {
    wSelectedStudent = { id: id, name: displayName, class_name: className, section: section, code: code };
    document.getElementById('eess_sel_stu_name').innerText = displayName;
    document.getElementById('eess_sel_stu_meta').innerText = ': ' + (code || '-') + ' | Training Group: ' + className + ' (' + section + ')';

    document.getElementById('eess-stu-selected-card').style.display = 'block';
    document.getElementById('eess-instant-success-card').style.display = 'none';
    document.getElementById('eess_stu_search_suggestions').style.display = 'none';

    var token = sessionStorage.getItem('eess_portal_token') || '';
    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_verify_student',
        student_id: id,
        portal_token: token,
        service: 'update_data',
        verify_code: 'NAME_ONLY'
    }, function(res) {
        if (res.success && res.data && res.data.student) {
            var photoUrl = res.data.student.photo_url;
            var imgEl = document.getElementById('eess_stu_current_photo_img');
            if (photoUrl) {
                imgEl.src = photoUrl;
                document.getElementById('eess_photo_status_lbl').innerText = '✓    ';
                document.getElementById('eess_photo_status_lbl').style.color = '#16a34a';
            } else {
                imgEl.src = '<?php echo esc_url($sys_logo); ?>';
                document.getElementById('eess_photo_status_lbl').innerText = '⚠️ No     No ';
                document.getElementById('eess_photo_status_lbl').style.color = '#dc2626';
            }
        }
    });
}

function eessResetSelectedStudent() {
    wSelectedStudent = null;
    document.getElementById('eess_stu_search_input').value = '';
    document.getElementById('eess_stu_search_suggestions').style.display = 'none';
    document.getElementById('eess-stu-selected-card').style.display = 'none';
    document.getElementById('eess-instant-success-card').style.display = 'none';
    var photoInp = document.getElementById('eess_stu_photo_input');
    if (photoInp) photoInp.value = '';
    document.getElementById('eess_photo_upload_msg').innerText = '';
}

function eessUploadStudentPhoto(fileInput) {
    if (!wSelectedStudent || !wSelectedStudent.id) {
        eessShowToast('  No No.', 'error');
        return;
    }
    if (!fileInput || !fileInput.files || !fileInput.files[0]) return;

    var token = sessionStorage.getItem('eess_portal_token') || '';
    var formData = new FormData();
    formData.append('action', 'sm_public_upload_student_photo');
    formData.append('student_id', wSelectedStudent.id);
    formData.append('portal_token', token);
    formData.append('student_photo', fileInput.files[0]);

    var statusMsg = document.getElementById('eess_photo_upload_msg');
    statusMsg.innerText = '     ...';
    statusMsg.style.color = '#0284c7';

    jQuery.ajax({
        url: '<?php echo $ajax_url; ?>',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if (res.success && res.data && res.data.photo_url) {
                document.getElementById('eess_stu_current_photo_img').src = res.data.photo_url + '?_ts=' + Date.now();
                document.getElementById('eess_photo_status_lbl').innerText = '✓   Update   ';
                document.getElementById('eess_photo_status_lbl').style.color = '#16a34a';
                statusMsg.innerText = '✓     No !';
                statusMsg.style.color = '#16a34a';

                if (res.data.photo_updated_at) {
                    var lastUpdatedEl = document.getElementById('eess_photo_last_updated_lbl');
                    if (lastUpdatedEl) lastUpdatedEl.innerText = ' Update  : ' + res.data.photo_updated_at;
                }

                eessShowToast(' Update  No .', 'success');
            } else {
                statusMsg.innerText = '✕ ' + ((res && res.data) ? res.data : '  .');
                statusMsg.style.color = '#dc2626';
                eessShowToast((res && res.data) ? res.data : '  .', 'error');
            }
        }
    });
}

function eessSavePhotoOnly() {
    if (!wSelectedStudent || !wSelectedStudent.id) {
        eessShowToast('   No No.', 'error');
        return;
    }
    eessShowToast('  Update  No     .', 'success');
    setTimeout(function() {
        eessResetSelectedStudent();
    }, 1500);
}

function eessPromptWithdrawExitCard() {
    if (!wSelectedStudent || !wSelectedStudent.id) {
        eessShowToast('  No No    .', 'error');
        return;
    }
    document.getElementById('eess_withdraw_student_name_lbl').innerText = wSelectedStudent.name;
    document.getElementById('eess-withdraw-confirm-modal').style.display = 'flex';
}

function eessExecuteWithdrawExitCard() {
    if (!wSelectedStudent || !wSelectedStudent.id) return;

    var token = sessionStorage.getItem('eess_portal_token') || '';
    var btn = document.getElementById('eess_btn_confirm_withdraw');
    if (btn) { btn.disabled = true; btn.innerText = ' ...'; }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_withdraw_exit_card_request',
        student_id: wSelectedStudent.id,
        portal_token: token
    }, function(res) {
        if (btn) { btn.disabled = false; btn.innerText = 'Confirm  '; }
        document.getElementById('eess-withdraw-confirm-modal').style.display = 'none';

        if (res.success) {
            eessShowToast(res.data.message || '  Cancel    .', 'success');
            eessResetSelectedStudent();
        } else {
            eessShowToast(res.data || '  .', 'error');
        }
    });
}

function eessSubmitInstantExitCardRequest() {
    if (!wSelectedStudent || !wSelectedStudent.id) {
        eessShowToast('  No No.', 'error');
        return;
    }

    var token = sessionStorage.getItem('eess_portal_token') || '';
    var btn = document.getElementById('eess_btn_instant_exit');
    if (btn) {
        btn.disabled = true;
        btn.innerText = '    ...';
    }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_submit_exit_card_instant',
        student_id: wSelectedStudent.id,
        portal_token: token
    }, function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerText = '  Confirm   ';
        }

        if (res && res.success && res.data) {
            document.getElementById('eess-stu-selected-card').style.display = 'none';

            var nameEl = document.getElementById('eess_succ_stu_name');
            if (nameEl) nameEl.innerText = res.data.student_name || wSelectedStudent.name;

            var codeEl = document.getElementById('eess_succ_stu_code');
            if (codeEl) codeEl.innerText = res.data.student_code || (wSelectedStudent.code || '-');

            var refEl = document.getElementById('eess_succ_ref_no');
            if (refEl) refEl.innerText = res.data.reference_no || '-';

            var gsEl = document.getElementById('eess_succ_grade_section');
            if (gsEl) gsEl.innerText = (res.data.class_name || wSelectedStudent.class_name) + ' (' + (res.data.section || wSelectedStudent.section) + ')';

            var reqAtEl = document.getElementById('eess_succ_requested_at');
            if (reqAtEl) reqAtEl.innerText = res.data.requested_at || '';

            var statusEl = document.getElementById('eess_succ_status_lbl');
            if (statusEl) statusEl.innerText = res.data.status_label || ' No';

            var photoUpdEl = document.getElementById('eess_succ_photo_updated_at');
            if (photoUpdEl) photoUpdEl.innerText = res.data.photo_updated_at || '';

            if (res.data.already_exists) {
                var titleEl = document.getElementById('eess_succ_card_title');
                if (titleEl) titleEl.innerText = '      No';
                var msgEl = document.getElementById('eess_succ_card_msg');
                if (msgEl) msgEl.innerText = '      No    .';
            } else {
                var titleEl = document.getElementById('eess_succ_card_title');
                if (titleEl) titleEl.innerText = '      ';
                var msgEl = document.getElementById('eess_succ_card_msg');
                if (msgEl) msgEl.innerText = '      Home    .';
            }

            document.getElementById('eess-instant-success-card').style.display = 'block';
            eessShowToast(res.data.message || '   .', 'success');

            if (typeof wLoadCardRequestsFull === 'function') wLoadCardRequestsFull();
        } else {
            var errMsg = (res && res.data) ? (typeof res.data === 'string' ? res.data : (res.data.message || '   .')) : '   .';
            eessShowToast(errMsg, 'error');
        }
    }).fail(function(xhr, status, error) {
        if (btn) {
            btn.disabled = false;
            btn.innerText = '  Confirm   ';
        }
        var errDetail = '  No .';
        if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
            errDetail = typeof xhr.responseJSON.data === 'string' ? xhr.responseJSON.data : (xhr.responseJSON.data.message || errDetail);
        } else if (xhr && xhr.responseText && xhr.responseText.length < 200) {
            errDetail = xhr.responseText;
        }
        eessShowToast(errDetail, 'error');
    });
}

let wActiveService = 'update_data';
let wSelectedStudent = null;
let wVerifiedData = null;
let wSearchTimeout = null;
let wSubmitting = false;
let wPendingDeleteReqId = null;

const wVerifyMethod = '<?php echo esc_js($verify_method); ?>';

function eessShowToast(message, type) {
    const toast = document.getElementById('eess-toast-notification');
    const msgEl = document.getElementById('eess-toast-message');
    const iconEl = document.getElementById('eess-toast-icon');

    if (!toast) return;

    msgEl.innerText = message;
    if (type === 'error') {
        toast.style.borderRightColor = '#ef4444';
        iconEl.innerText = '✕';
    } else {
        toast.style.borderRightColor = '#16a34a';
        iconEl.innerText = '✓';
    }

    toast.style.display = 'flex';
    setTimeout(() => toast.style.display = 'none', 3500);
}

function wResetPortalForms() {
    wSelectedStudent = null;
    wVerifiedData = null;

    const stuInp = document.getElementById('w_student_name_input');
    if (stuInp) stuInp.value = '';

    const verInp = document.getElementById('w_verify_code_input');
    if (verInp) verInp.value = '';

    const dobInp = document.getElementById('w_verify_dob_input');
    if (dobInp) dobInp.value = '';

    const cmpTitle = document.getElementById('w_cmp_title_input');
    if (cmpTitle) cmpTitle.value = '';

    const cmpDetails = document.getElementById('w_cmp_details_input');
    if (cmpDetails) cmpDetails.value = '';

    const photoInp = document.getElementById('w_student_photo_file');
    if (photoInp) photoInp.value = '';

    const parentName = document.getElementById('w_parent_name');
    if (parentName) parentName.value = '';

    const parentPhone = document.getElementById('w_parent_phone');
    if (parentPhone) parentPhone.value = '';

    document.querySelectorAll('input[name="sports_activity[]"]').forEach(c => c.checked = false);
    const declChk = document.getElementById('w_declaration_chk');
    if (declChk) declChk.checked = false;

    if (typeof wClearSignature === 'function') wClearSignature();

    const hideIds = [
        'w-selected-stu-box', 'w-search-suggestions', 'w-verified-status-panel',
        'w-missing-data-container', 'w-photo-upload-container', 'w-fee-notice-box',
        'w-service-form-complaint', 'w-service-form-sports', 'w-check-req-result-box', 'w-check-cmp-result-box'
    ];
    hideIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    const verifyBox = document.getElementById('w-verify-identity-box');
    if (verifyBox) verifyBox.style.display = 'block';

    const btnNext1 = document.getElementById('w_btn_next_1');
    if (btnNext1) {
        btnNext1.disabled = true;
        btnNext1.style.opacity = '0.5';
        btnNext1.style.cursor = 'not-allowed';
    }

    const btnNext2 = document.getElementById('w_btn_next_2');
    if (btnNext2) btnNext2.style.display = 'none';

    const btnNext3 = document.getElementById('w_btn_next_3');
    if (btnNext3) {
        btnNext3.disabled = true;
        btnNext3.style.opacity = '0.5';
        btnNext3.style.cursor = 'not-allowed';
    }
}

function wSelectPortalService(serviceKey) {
    wResetPortalForms();
    wActiveService = serviceKey;

    document.getElementById('w-panel-step-0').style.display = 'none';
    document.getElementById('w-progress-bar').style.display = 'flex';

    const step2Label = document.getElementById('w_step_2_label');
    if (serviceKey === 'complaint') {
        if (step2Label) step2Label.innerText = '  ';
    } else if (serviceKey === 'sports') {
        if (step2Label) step2Label.innerText = '  Active';
    } else if (serviceKey === 'exit_card') {
        if (step2Label) step2Label.innerText = ' ';
    } else {
        if (step2Label) step2Label.innerText = '  ';
    }

    wGoToStep(1);
}

function wBackToStep0() {
    wResetPortalForms();
    document.querySelectorAll('#w-panel-step-1, #w-panel-step-2, #w-panel-step-3, #w-panel-step-4, #w-panel-success').forEach(el => el.style.display = 'none');
    document.getElementById('w-progress-bar').style.display = 'none';
    document.getElementById('w-panel-step-0').style.display = 'block';
}

function wSwitchPortalView(viewId, btnEl) {
    document.querySelectorAll('#pv-view-wizard, #pv-view-complaints-management, #pv-view-sports-management, #pv-view-required-data, #pv-view-operating-mode, #pv-view-requests-management').forEach(el => {
        el.style.display = 'none';
    });
    const target = document.getElementById(viewId);
    if (target) target.style.display = 'block';

    document.querySelectorAll('.sb-nav-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '#f8fafc';
        btn.style.color = '#334155';
    });
    if (btnEl) {
        btnEl.classList.add('active');
        btnEl.style.background = '#881337';
        btnEl.style.color = '#ffffff';
    }

    if (viewId === 'pv-view-requests-management') wLoadCardRequestsFull();
    if (viewId === 'pv-view-complaints-management') wLoadComplaintsFull();
    if (viewId === 'pv-view-sports-management') wLoadSportsFull();
}

function wToggleCheckPreviousReqModal(show) {
    const modal = document.getElementById('w-check-req-modal');
    if (modal) modal.style.display = show ? 'flex' : 'none';
}

function wToggleCheckComplaintModal(show) {
    const modal = document.getElementById('w-check-cmp-modal');
    if (modal) modal.style.display = show ? 'flex' : 'none';
}

function wDebounceSearchName() {
    clearTimeout(wSearchTimeout);
    wSearchTimeout = setTimeout(wSearchStudentName, 300);
}

function wSearchStudentName() {
    const val = document.getElementById('w_student_name_input').value.trim();
    const suggestions = document.getElementById('w-search-suggestions');
    const words = val.split(' ').filter(w => w.length > 0);

    if (words.length < 3) {
        suggestions.style.display = 'none';
        return;
    }

    suggestions.style.display = 'block';
    suggestions.innerHTML = '<div style="text-align:center; padding:10px; font-weight:700; color:#64748b; font-size:12px;"> Search  ...</div>';

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_search_student',
        name_query: val
    }, function(res) {
        if (res.success && res.data && res.data.length > 0) {
            let html = '<div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:10px; max-height:220px; overflow-y:auto;">';
            res.data.forEach(s => {
                html += '<div onclick="wSelectStudent(' + s.id + ', \'' + s.display_name.replace(/'/g, "\\'") + '\', \'' + s.class_name + '\', \'' + s.section + '\')" style="padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:12.5px; font-weight:800; color:#0f172a;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'white\'">';
                html += '<div>' + s.display_name + '</div>';
                html += '<div style="font-size:11px; color:#64748b; font-weight:600;">' + s.class_name + ' (' + s.section + ')</div>';
                html += '</div>';
            });
            html += '</div>';
            suggestions.innerHTML = html;
        } else {
            suggestions.innerHTML = '<div style="background:#fef2f2; border:1px solid #fecdd3; border-radius:10px; padding:12px; color:#991b1b; font-size:12px; font-weight:700; text-align:center;">    No  Full Name .</div>';
        }
    });
}

function wSelectStudent(id, displayName, className, section) {
    wSelectedStudent = { id: id, name: displayName, class_name: className, section: section };
    document.getElementById('w_sel_stu_name').innerText = displayName;
    document.getElementById('w_sel_stu_class').innerText = 'Training Group: ' + className + ' (' + section + ')';
    document.getElementById('w-selected-stu-box').style.display = 'block';
    document.getElementById('w-search-suggestions').style.display = 'none';

    const btnNext = document.getElementById('w_btn_next_1');
    btnNext.disabled = false;
    btnNext.style.opacity = '1';
    btnNext.style.cursor = 'pointer';
}

function wVerifyStudentIdentity() {
    if (wActiveService === 'update_data') {
        jQuery.post('<?php echo $ajax_url; ?>', {
            action: 'sm_public_verify_student',
            student_id: wSelectedStudent.id,
            service: 'update_data',
            verify_code: 'NAME_ONLY'
        }, function(res) {
            if (res.success && res.data) {
                wVerifiedData = res.data;
                var vBox = document.getElementById('w-verify-identity-box');
                if (vBox) vBox.style.display = 'none';
                wRenderMissingDataForm(res.data);
            } else {
                eessShowToast(res.data || '   No.', 'error');
            }
        });
        return;
    }

    const codeVal = document.getElementById('w_verify_code_input').value.trim();
    const dobVal  = document.getElementById('w_verify_dob_input').value.trim();

    if (!codeVal) {
        eessShowToast('   No   National ID.', 'error');
        return;
    }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_verify_student',
        student_id: wSelectedStudent.id,
        verify_code: codeVal,
        dob: dobVal,
        service: wActiveService
    }, function(res) {
        if (res.success && res.data) {
            wVerifiedData = res.data;
            document.getElementById('w-verify-identity-box').style.display = 'none';

            let html = '<div style="background:#f0fdf4; border:1.5px solid #86efac; border-radius:12px; padding:14px; margin-bottom:14px;">';
            html += '<div style="font-size:13px; font-weight:900; color:#166534;">✓      No: ' + res.data.student.name + '</div>';
            html += '</div>';

            const panel = document.getElementById('w-verified-status-panel');
            panel.innerHTML = html;
            panel.style.display = 'block';

            if (wActiveService === 'complaint') {
                document.getElementById('w-service-form-complaint').style.display = 'block';
            } else if (wActiveService === 'sports') {
                document.getElementById('w-service-form-sports').style.display = 'block';
            } else if (wActiveService === 'exit_card') {
                if (res.data.has_photo) {
                    document.getElementById('w-photo-upload-container').style.display = 'none';
                } else {
                    document.getElementById('w-photo-upload-container').style.display = 'block';
                }
                const btnNext2 = document.getElementById('w_btn_next_2');
                if (btnNext2) btnNext2.style.display = 'inline-block';
            }
        } else {
            eessShowToast(res.data || '   Date of Birth  .', 'error');
        }
    });
}

function wRenderMissingDataForm(data) {
    var container = document.getElementById('w-missing-data-container');
    var renderBox = document.getElementById('w_missing_fields_render_box');
    if (!container || !renderBox) return;

    var student = (data && data.student) ? data.student : {};
    var missing = (data && data.missing_fields) ? data.missing_fields : [];

    var html = '';

    // Always show National ID field if in update_data mode or if missing
    if (missing.includes('national_id') || wActiveService === 'update_data') {
        var isReq = missing.includes('national_id') || !student.national_id;
        html += '<div style="margin-bottom:12px;">';
        html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;"> National ID  (15 ) ' + (isReq ? '<span style="color:#ef4444;">*</span>' : '') + '</label>';
        html += '<input type="text" id="w_inp_national_id" value="' + (student.national_id || '') + '" placeholder="784-YYYY-XXXXXXX-X" ' + (isReq ? 'required' : '') + ' style="width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:12.5px; font-weight:700;">';
        html += '</div>';
    }

    if (missing.includes('dob') || wActiveService === 'update_data') {
        html += '<div style="margin-bottom:12px;">';
        html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;">Date of Birth</label>';
        html += '<input type="date" id="w_inp_dob" value="' + (student.dob || '') + '" style="width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:12.5px; font-weight:700;">';
        html += '</div>';
    }

    if (missing.includes('guardian_name') || wActiveService === 'update_data') {
        html += '<div style="margin-bottom:12px;">';
        html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;">  Mother No</label>';
        html += '<input type="text" id="w_inp_guardian_name" value="' + (student.guardian_name || '') + '" placeholder="Full Name  Mother..." style="width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:12.5px; font-weight:700;">';
        html += '</div>';
    }

    if (missing.includes('guardian_phone') || wActiveService === 'update_data') {
        html += '<div style="margin-bottom:12px;">';
        html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;">     Mother</label>';
        html += '<input type="tel" id="w_inp_guardian_phone" value="' + (student.guardian_phone || '') + '" placeholder="+971 50 1234567" style="width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:12.5px; font-weight:700;">';
        html += '</div>';
    }

    if (missing.includes('nationality') || missing.includes('emirate') || wActiveService === 'update_data') {
        html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">';
        html += '<div>';
        html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;">Gender</label>';
        html += '<input type="text" id="w_inp_nationality" value="' + (student.nationality || '') + '" style="width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:12.5px; font-weight:700;">';
        html += '</div>';
        html += '<div>';
        html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;"> </label>';
        html += '<input type="text" id="w_inp_emirate" value="' + (student.emirate || '') + '" style="width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:12.5px; font-weight:700;">';
        html += '</div>';
        html += '</div>';
    }

    renderBox.innerHTML = html;
    container.style.display = 'block';
}

function wSubmitMissingData(e) {
    if (e) e.preventDefault();
    if (!wSelectedStudent || !wSelectedStudent.id) return;

    var natInp = document.getElementById('w_inp_national_id');
    var dobInp = document.getElementById('w_inp_dob');
    var gNameInp = document.getElementById('w_inp_guardian_name');
    var gPhoneInp = document.getElementById('w_inp_guardian_phone');
    var natioInp = document.getElementById('w_inp_nationality');
    var emiInp = document.getElementById('w_inp_emirate');

    var postData = {
        action: 'sm_public_update_student_missing_data',
        student_id: wSelectedStudent.id,
        national_id: natInp ? natInp.value.trim() : '',
        dob: dobInp ? dobInp.value.trim() : '',
        guardian_name: gNameInp ? gNameInp.value.trim() : '',
        guardian_phone: gPhoneInp ? gPhoneInp.value.trim() : '',
        nationality: natioInp ? natioInp.value.trim() : '',
        emirate_residence: emiInp ? emiInp.value.trim() : ''
    };

    var btn = document.getElementById('w_btn_save_missing');
    if (btn) { btn.disabled = true; btn.innerText = ' Save...'; }

    jQuery.post('<?php echo $ajax_url; ?>', postData, function(res) {
        if (btn) { btn.disabled = false; btn.innerText = 'Save Update  '; }
        if (res.success) {
            eessShowToast(res.data.message || ' Update  .', 'success');
            document.getElementById('w-missing-data-container').style.display = 'none';

            if (res.data.notice) {
                var noticeHtml = '<div style="background:#fffbe3; border:1.5px solid #fde047; border-radius:12px; padding:16px; margin-top:14px; font-size:13px; font-weight:800; color:#854d0e; line-height:1.6;">' + res.data.notice + '</div>';
                document.getElementById('w-verified-status-panel').innerHTML += noticeHtml;
            }
        } else {
            eessShowToast(res.data || ' Update .', 'error');
        }
    });
}

function wLimitSportsCheckboxes(chk) {
    const checked = document.querySelectorAll('input[name="sports_activity[]"]:checked');
    if (checked.length > 2) {
        chk.checked = false;
        eessShowToast(':      .', 'error');
    }
}

function wSubmitComplaintFinal() {
    if (!wSelectedStudent || !wSelectedStudent.id) {
        eessShowToast('   No No.', 'error');
        return;
    }

    const titleEl = document.getElementById('w_cmp_title_input');
    const detailsEl = document.getElementById('w_cmp_details_input');
    const title = titleEl ? titleEl.value.trim() : '';
    const details = detailsEl ? detailsEl.value.trim() : '';

    if (!title || !details) {
        eessShowToast('    .', 'error');
        return;
    }

    const btn = document.getElementById('w_btn_submit_cmp');
    if (btn) {
        btn.disabled = true;
        btn.innerText = ' Send ...';
    }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_submit_complaint',
        student_id: wSelectedStudent.id,
        title: title,
        details: details
    }, function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Send   ✓';
        }

        if (res && res.success && res.data) {
            document.getElementById('w-panel-step-2').style.display = 'none';
            document.getElementById('w-progress-bar').style.display = 'none';
            document.getElementById('w_success_title').innerText = '   ';
            document.getElementById('w_success_sub').innerText = '     Academy    .';
            document.getElementById('w_success_ref_no').innerText = res.data.reference_no;
            document.getElementById('w-panel-success').style.display = 'block';

            if (titleEl) titleEl.value = '';
            if (detailsEl) detailsEl.value = '';
            const cntEl = document.getElementById('w_cmp_char_cnt');
            if (cntEl) cntEl.innerText = '0 / 1000 ';

            eessShowToast('   .', 'success');
        } else {
            const err = (res && res.data) ? res.data : ' Send .   No.';
            eessShowToast(err, 'error');
        }
    }).fail(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Send   ✓';
        }
        eessShowToast('An error occurred  No .   .', 'error');
    });
}

function wSubmitSportsFinal() {
    if (!wSelectedStudent || !wSelectedStudent.id) {
        eessShowToast('   No No.', 'error');
        return;
    }

    const checked = Array.from(document.querySelectorAll('input[name="sports_activity[]"]:checked')).map(c => c.value);
    if (checked.length === 0) {
        eessShowToast('      .', 'error');
        return;
    }

    const codeVal = document.getElementById('w_verify_code_input') ? document.getElementById('w_verify_code_input').value.trim() : '';
    const dobVal  = document.getElementById('w_verify_dob_input') ? document.getElementById('w_verify_dob_input').value.trim() : '';

    const btn = document.getElementById('w_btn_submit_spt');
    if (btn) {
        btn.disabled = true;
        btn.innerText = '  Active...';
    }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_submit_sports_registration',
        student_id: wSelectedStudent.id,
        sports: checked,
        verify_code: codeVal,
        dob: dobVal
    }, function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Confirm  Sports Activities ✓';
        }

        if (res && res.success) {
            document.getElementById('w-panel-step-2').style.display = 'none';
            document.getElementById('w-progress-bar').style.display = 'none';
            document.getElementById('w_success_title').innerText = '    ';
            document.getElementById('w_success_sub').innerText = '   No       .';
            document.getElementById('w_success_ref_no').innerText = 'SPT-' + Date.now().toString().slice(-6);
            document.getElementById('w-panel-success').style.display = 'block';
            eessShowToast('  Sports Activities .', 'success');
        } else {
            const err = (res && res.data) ? res.data : '  Sports Activities.';
            eessShowToast(err, 'error');
        }
    }).fail(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Confirm  Sports Activities ✓';
        }
        eessShowToast('An error occurred  No .', 'error');
    });
}

function wExecuteCheckPreviousRequest() {
    const query = document.getElementById('w_check_query_input').value.trim();
    const resultBox = document.getElementById('w-check-req-result-box');
    resultBox.style.display = 'none';

    if (!query) {
        eessShowToast('   National ID    .', 'error');
        return;
    }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_check_previous_request',
        search_query: query
    }, function(res) {
        if (res.success && res.data) {
            const d = res.data;
            let html = '<div style="background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:14px; padding:16px;">';
            html += '<div style="font-size:14px; font-weight:900; color:#15803d; margin-bottom:6px;">Welcome ' + d.student_name + ' </div>';
            html += '<div style="font-size:12px; color:#475569; margin-bottom:12px;">   :</div>';

            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:12px; background:white; padding:12px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:10px;">';
            html += '<div><strong> :</strong> <span style="font-family:monospace; font-weight:800; color:#881337;">' + d.reference_no + '</span></div>';
            html += '<div><strong> :</strong> ' + d.created_at + '</div>';
            html += '<div style="grid-column:span 2;"><strong>Status :</strong> <span style="background:#fef08a; color:#854d0e; padding:2px 8px; border-radius:6px; font-weight:800; font-size:11px;">' + d.status_label + '</span></div>';
            html += '</div>';

            html += '<div style="font-size:11.5px; color:#334155; line-height:1.5;">' + d.status_desc + '</div>';
            html += '</div>';

            resultBox.innerHTML = html;
            resultBox.style.display = 'block';
        } else {
            resultBox.innerHTML = '<div style="background:#fef2f2; border:1px solid #fecdd3; border-radius:12px; padding:12px; color:#991b1b; font-size:12px; font-weight:800; text-align:center;">' + (res.data || '         .') + '</div>';
            resultBox.style.display = 'block';
        }
    });
}

function wExecuteCheckComplaintStatus() {
    const query = document.getElementById('w_check_cmp_query_input').value.trim();
    const resultBox = document.getElementById('w-check-cmp-result-box');

    if (!query) {
        eessShowToast('   NoNo.', 'error');
        return;
    }

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_public_check_complaint_status',
        search_query: query
    }, function(res) {
        if (res.success && res.data) {
            const d = res.data;
            let html = '<div style="background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:12px; padding:14px;">';
            html += '<div style="font-size:13px; font-weight:900; color:#854d0e;"> : ' + d.reference_no + '</div>';
            html += '<div style="font-size:12px; margin:4px 0;"><strong>Player Name:</strong> ' + d.student_name + '</div>';
            html += '<div style="font-size:12px; margin:4px 0;"><strong> :</strong> ' + d.title + '</div>';
            html += '<div style="font-size:12px; margin:4px 0;"><strong>Status:</strong> <span style="background:#fef08a; padding:2px 8px; border-radius:6px; font-weight:800;">' + d.status_label + '</span></div>';
            html += '<div style="font-size:11.5px; color:#475569; margin-top:8px; border-top:1px solid #e2e8f0; padding-top:6px;"><strong> :</strong> ' + d.admin_notes + '</div>';
            html += '</div>';

            resultBox.innerHTML = html;
            resultBox.style.display = 'block';
        } else {
            resultBox.innerHTML = '<div style="background:#fef2f2; border:1px solid #fecdd3; border-radius:10px; padding:12px; color:#991b1b; text-align:center;">' + (res.data || '     .') + '</div>';
            resultBox.style.display = 'block';
        }
    });
}

function wLoadCardRequestsFull() {
    const box = document.getElementById('pv-requests-cards-container');
    if (!box) return;

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_manage_card_requests',
        action_type: 'list',
        nonce: '<?php echo $admin_nonce; ?>',
        _ts: Date.now()
    }, function(res) {
        if (res.success && res.data && res.data.length > 0) {
            let html = '';
            res.data.forEach(r => {
                let badgeBg = '#fef08a';
                let badgeColor = '#854d0e';
                if (r.status === 'approved') { badgeBg = '#dcfce7'; badgeColor = '#166534'; }
                else if (r.status === 'rejected') { badgeBg = '#fee2e2'; badgeColor = '#991b1b'; }

                let vbadgeBg = '#fef3c7';
                let vbadgeColor = '#92400e';
                if (r.verification_status === 'parent_confirmed') { vbadgeBg = '#d1fae5'; vbadgeColor = '#065f46'; }
                else if (r.verification_status === 'parent_not_confirmed') { vbadgeBg = '#fee2e2'; vbadgeColor = '#991b1b'; }

                let waText = "/   No/ /\n\n";
                waText += "      Issue    No/ " + r.student_name + "  No " + r.student_code + ".\n\n";
                waText += "  Issue       Approve  Mother    Confirm Approve     No/   Academy  No   Academy     .\n\n";
                waText += "  Approve       Next:\n\n";
                waText += '\"    No/ / No   Issue        /  Academy   No   Academy .\"\n\n';
                waText += "       Approve      .\n\n";
                waText += "   Confirm.\n\n";
                waText += "   No.";

                let waUrl = 'https://api.whatsapp.com/send?phone=' + encodeURIComponent(r.wa_phone) + '&text=' + encodeURIComponent(waText);

                html += '<div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:16px; padding:14px; box-shadow:0 4px 12px rgba(0,0,0,0.03); display:flex; flex-direction:column; justify-content:space-between; gap:10px;">';

                html += '<div>';
                html += '<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:6px; margin-bottom:6px;">';
                html += '<div>';
                html += '<div style="font-size:14px; font-weight:900; color:#0f172a; line-height:1.3;">' + r.student_name + '</div>';
                html += '<div style="font-size:11px; color:#881337; font-weight:800; margin-top:2px;"> No: ' + r.student_code + '</div>';
                html += '</div>';
                html += '<div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">';
                html += '<span style="background:' + badgeBg + '; color:' + badgeColor + '; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:800;">' + r.status_label + '</span>';
                html += '<span style="background:' + vbadgeBg + '; color:' + vbadgeColor + '; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:800;">' + r.verification_label + '</span>';
                html += '</div></div>';

                html += '<div style="font-size:11px; color:#475569; line-height:1.5; margin-bottom:4px;">';
                html += '<div><strong>Training Group:</strong> ' + r.class_name + ' (' + r.section + ') | <strong>:</strong> <span style="font-family:monospace;">' + r.reference_no + '</span></div>';
                html += '<div><strong> Mother:</strong> ' + r.parent_name + ' (' + r.parent_phone + ')</div>';
                html += '</div>';
                html += '</div>';

                html += '<div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; border-top:1px solid #e2e8f0; padding-top:10px; margin-top: auto;">';

                html += '<button type="button" class="eess-btn-action-compact" onclick="wViewCardRequestDetails(' + r.id + ')" style="background:#0f172a; color:white;">';
                html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
                html += '<span>View/</span></button>';

                if (r.wa_phone) {
                    html += '<a href="' + waUrl + '" target="_blank" class="eess-btn-action-compact" style="background:#25D366; color:white;">';
                    html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.3 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>';
                    html += '<span> </span></a>';
                } else {
                    html += '<span class="eess-btn-action-compact" style="background:#cbd5e1; color:#64748b;">No  </span>';
                }

                html += '<button type="button" class="eess-btn-action-compact" onclick="wPrintExitRequestDoc(' + r.id + ')" style="background:#0284c7; color:white;">';
                html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>';
                html += '<span>Print </span></button>';

                html += '<button type="button" class="eess-btn-action-compact" onclick="wPrintStudentCard(' + r.student_id + ')" style="background:#881337; color:white;">';
                html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>';
                html += '<span>Print </span></button>';

                html += '<button type="button" class="eess-btn-action-compact" onclick="wPromptDeleteRequest(' + r.id + ', \'' + r.student_name.replace(/'/g, "\\'") + '\')" style="background:#fee2e2; color:#991b1b; border:1px solid #fecdd3; grid-column: span 2;">';
                html += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>';
                html += '<span>Delete  </span></button>';

                html += '</div>';

                html += '</div>';
            });
            box.innerHTML = html;
        } else {
            box.innerHTML = '<div style="text-align:center; color:#64748b; padding:20px; font-size:12.5px; grid-column: span 2;">No       .</div>';
        }
    });
}

function wViewCardRequestDetails(reqId) {
    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_get_exit_card_request_details',
        request_id: reqId,
        nonce: '<?php echo $admin_nonce; ?>'
    }, function(res) {
        if (res.success && res.data) {
            const d = res.data;
            let html = '<div style="line-height:1.7;">';
            html += '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; background:#f8fafc; padding:12px; border-radius:10px; margin-bottom:12px;">';
            html += '<div><strong> :</strong> <span style="font-family:monospace; font-weight:900; color:#881337;">' + d.reference_no + '</span></div>';
            html += '<div><strong> :</strong> ' + d.created_at + '</div>';
            html += '<div><strong>Player Name:</strong> ' + d.student_name + '</div>';
            html += '<div><strong> No:</strong> <span style="font-family:monospace; font-weight:900;">' + d.student_code + '</span></div>';
            html += '<div><strong>Training Group Training Group:</strong> ' + d.class_name + ' (' + d.section + ')</div>';
            html += '<div><strong>National ID:</strong> ' + d.national_id + '</div>';
            html += '<div style="grid-column: span 2;"><strong> Mother:</strong> ' + d.parent_name + ' (' + d.parent_phone + ')</div>';
            html += '</div>';

            <?php if ($is_admin): ?>
                html += '<div style="margin-bottom:14px; background:#fffbe3; border:1px solid #fde047; padding:10px 14px; border-radius:10px;">';
                html += '<label style="font-size:12px; font-weight:800; color:#854d0e; display:block; margin-bottom:4px;">Edit   :</label>';
                html += '<select onchange="wUpdateReqStatus(' + d.id + ', this.value)" style="width:100%; height:36px; border-radius:8px; border:1px solid #cbd5e1; font-size:12px; font-weight:800;">';
                html += '<option value="submitted" ' + (d.status==='submitted'?'selected':'') + '>  </option>';
                html += '<option value="under_review" ' + (d.status==='under_review'?'selected':'') + '>  </option>';
                html += '<option value="approved" ' + (d.status==='approved'?'selected':'') + '>Approve  </option>';
                html += '<option value="preparing" ' + (d.status==='preparing'?'selected':'') + '>   </option>';
                html += '<option value="issued" ' + (d.status==='issued'?'selected':'') + '> Issue </option>';
                html += '<option value="rejected" ' + (d.status==='rejected'?'selected':'') + '>Reject </option>';
                html += '</select></div>';
            <?php endif; ?>

            if (d.signature_data) {
                html += '<div style="margin-bottom:14px;">';
                html += '<div style="font-weight:800; margin-bottom:4px; color:#0f172a;">    Mother:</div>';
                html += '<div style="background:white; border:2px dashed #cbd5e1; border-radius:10px; padding:8px; text-align:center;">';
                html += '<img src="' + d.signature_data + '" style="max-height:60px; object-fit:contain;" alt="Signature">';
                html += '</div></div>';
            }

            html += '<div style="margin-top:14px; border-top:1px solid #e2e8f0; padding-top:10px; display:flex; gap:8px; justify-content:flex-end;">';
            html += '<button type="button" onclick="wPrintExitRequestDoc(' + d.id + ')" style="background:#0284c7; color:white; border:none; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:800; cursor:pointer;">️ Print  A4</button>';
            html += '<button type="button" onclick="wPrintStudentCard(' + d.student_id + ')" style="background:#881337; color:white; border:none; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:800; cursor:pointer;"> Print </button>';
            html += '</div>';

            html += '</div>';

            document.getElementById('modal_req_body').innerHTML = html;
            document.getElementById('w-req-view-modal').style.display = 'flex';
        } else {
            eessShowToast('   .', 'error');
        }
    });
}

function wUpdateReqStatus(reqId, status) {
    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_manage_card_requests',
        action_type: 'update_status',
        request_id: reqId,
        status: status,
        nonce: '<?php echo $admin_nonce; ?>'
    }, function(res) {
        if (res.success) {
            eessShowToast(' Update   .', 'success');
            document.getElementById('w-req-view-modal').style.display = 'none';
            wLoadCardRequestsFull();
        }
    });
}

function wPromptDeleteRequest(reqId, studentName) {
    wPendingDeleteReqId = reqId;
    document.getElementById('eess_del_student_name').innerText = studentName;
    document.getElementById('eess-delete-confirm-modal').style.display = 'flex';
}

function wCloseDeleteModal() {
    wPendingDeleteReqId = null;
    document.getElementById('eess-delete-confirm-modal').style.display = 'none';
}

function wExecuteConfirmDelete() {
    if (!wPendingDeleteReqId) return;

    const btn = document.getElementById('eess_btn_confirm_delete');
    btn.disabled = true;
    btn.innerText = ' Delete...';

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_manage_card_requests',
        action_type: 'delete',
        request_id: wPendingDeleteReqId,
        nonce: '<?php echo $admin_nonce; ?>'
    }, function(res) {
        btn.disabled = false;
        btn.innerText = 'Confirm Delete ';
        wCloseDeleteModal();

        if (res.success) {
            eessShowToast(res.data.message || ' Delete         No.', 'success');
            wLoadCardRequestsFull();
        } else {
            eessShowToast(' Delete : ' + (res.data || ''), 'error');
        }
    });
}

function wPrintStudentCard(studentId) {
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_card&student_id='); ?>' + studentId, '_blank');
}

function wPrintExitRequestDoc(reqId) {
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=exit_permit_request&request_id='); ?>' + reqId + '&auto_print=1', '_blank');
}

function wGoToStep(stepNum) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('w-panel-step-' + i);
        if (el) el.style.display = (i === stepNum) ? 'block' : 'none';
    }

    if (stepNum === 2) {
        if (wActiveService === 'update_data' || wVerifyMethod === 'name_only') {
            var vBox = document.getElementById('w-verify-identity-box');
            if (vBox) vBox.style.display = 'none';
            wVerifyStudentIdentity();
        } else {
            var vBox = document.getElementById('w-verify-identity-box');
            if (vBox) vBox.style.display = 'block';
        }
    }
}

function wLoadComplaintsFull() {
    const box = document.getElementById('pv-complaints-cards-container');
    if (!box) return;

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_manage_complaints',
        action_type: 'list',
        nonce: '<?php echo $admin_nonce; ?>'
    }, function(res) {
        if (res.success && res.data && res.data.length > 0) {
            let html = '';
            res.data.forEach(c => {
                html += '<div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; padding:14px; display:flex; flex-direction:column; justify-content:space-between; gap:10px;">';
                html += '<div>';
                html += '<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">';
                html += '<div style="font-weight:900; color:#854d0e; font-size:12px; font-family:monospace;">' + c.reference_no + '</div>';
                html += '<span style="background:#fef08a; color:#854d0e; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:800;">' + c.status_label + '</span>';
                html += '</div>';
                html += '<div style="font-size:13.5px; font-weight:900; color:#0f172a; margin-bottom:4px;">' + c.title + '</div>';
                html += '<div style="font-size:11.5px; color:#475569; margin-bottom:8px;">No: <strong>' + c.student_name + '</strong> (' + c.class_name + ' - ' + c.section + ')</div>';
                html += '<div style="background:white; border:1px solid #cbd5e1; border-radius:8px; padding:8px; font-size:11.5px; color:#334155; line-height:1.5;">' + c.details + '</div>';
                html += '</div>';

                html += '<div style="display:flex; gap:6px; border-top:1px solid #e2e8f0; padding-top:8px;">';
                html += '<button type="button" onclick="wPrintComplaintDoc(' + c.id + ')" class="eess-btn-action-compact" style="background:#854d0e; color:white; flex:1;">️ Print  A4</button>';
                html += '<button type="button" onclick="wUpdateComplaintStatus(' + c.id + ')" class="eess-btn-action-compact" style="background:#0f172a; color:white; flex:1;">Edit Status</button>';
                html += '</div>';
                html += '</div>';
            });
            box.innerHTML = html;
        } else {
            box.innerHTML = '<div style="text-align:center; color:#64748b; padding:20px; font-size:12.5px; grid-column: span 2;">No     .</div>';
        }
    });
}

function wPrintComplaintDoc(cmpId) {
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=complaint_doc&complaint_id='); ?>' + cmpId, '_blank');
}

function wUpdateComplaintStatus(cmpId) {
    const status = prompt(' Status  (submitted, under_review, resolved, rejected):', 'resolved');
    if (!status) return;

    const notes = prompt(' Notes  :');

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_manage_complaints',
        action_type: 'update_status',
        complaint_id: cmpId,
        status: status,
        admin_notes: notes || '',
        nonce: '<?php echo $admin_nonce; ?>'
    }, function(res) {
        if (res.success) {
            eessShowToast(' Update   .', 'success');
            wLoadComplaintsFull();
        }
    });
}

function wLoadSportsFull() {
    const box = document.getElementById('pv-sports-cards-container');
    if (!box) return;

    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'sm_manage_sports_registrations',
        action_type: 'list',
        nonce: '<?php echo $admin_nonce; ?>'
    }, function(res) {
        if (res.success && res.data && res.data.length > 0) {
            let html = '';
            res.data.forEach(r => {
                html += '<div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; padding:14px;">';
                html += '<div style="font-size:14px; font-weight:900; color:#0f172a; margin-bottom:4px;">' + r.student_name + '</div>';
                html += '<div style="font-size:11.5px; color:#64748b; margin-bottom:8px;">' + r.class_name + ' (' + r.section + ') - : ' + r.student_code + '</div>';
                html += '<div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:10px; font-size:12.5px; font-weight:800; color:#166534; margin-bottom:8px;">';
                html += '⚽ Active : ' + r.sports_label;
                html += '</div>';
                html += '<div style="font-size:11px; color:#94a3b8;">Registration Date: ' + r.created_at + '</div>';
                html += '</div>';
            });
            box.innerHTML = html;
        } else {
            box.innerHTML = '<div style="text-align:center; color:#64748b; padding:20px; font-size:12.5px; grid-column: span 2;">No  No    .</div>';
        }
    });
}

function wSavePortalSettingsFromView(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'sm_save_exit_card_settings');
    formData.append('nonce', '<?php echo $admin_nonce; ?>');

    jQuery.ajax({
        url: '<?php echo $ajax_url; ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.success) {
                eessShowToast(res.data.message || ' Save Settings .', 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                eessShowToast(': ' + (res.data || ' Save'), 'error');
            }
        }
    });
}
</script>
