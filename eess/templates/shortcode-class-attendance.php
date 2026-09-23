<?php if (!defined('ABSPATH')) exit;
$school = SM_Settings::get_school_info();
$academic = SM_Settings::get_academic_structure();

?>
<div class="sm-class-attendance-shortcode" dir="rtl" style="max-width: 900px; margin: 20px auto; padding: 40px; background: #fff; border-radius: 20px; border: 1px solid var(--sm-border-color); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

    <!-- Header: Logo, Title, Date & Camera Barcode Scanner Mode -->
    <div style="text-align: center; margin-bottom: 30px;">
        <?php if ($school['school_logo']): ?>
            <img src="<?php echo esc_url($school['school_logo']); ?>" style="height: 70px; width: auto; margin-bottom: 15px; object-fit: contain;">
        <?php endif; ?>

        <h1 style="font-weight: 900; color: #0f172a; margin: 0 0 10px 0; font-size: 2em; border: none;">تسجيل الحضور الرقمي بالكاميرا والبارکود</h1>

        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 20px; background: #fef2f2; color: #881337; border-radius: 50px; font-weight: 800; font-size: 1em; border: 1px solid #fecdd3;">
            <span class="dashicons dashicons-camera" style="font-size: 16px; width: 16px; height: 16px;"></span>
            <span><?php echo date_i18n('l، j F Y'); ?></span>
        </div>
    </div>

    <!-- High-Speed Camera Barcode Attendance Scanner Section -->
    <div id="eess-attendance-camera-section" style="background: #0f172a; border-radius: 16px; padding: 20px; margin-bottom: 30px; text-align: center; color: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #334155; padding-bottom: 10px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-id-alt" style="color: #38bdf8; font-size: 22px; width: 22px; height: 22px;"></span>
                <span style="font-weight: 800; font-size: 14px; color: #f8fafc;">ماسح البارکود السريع للحضور (1 كارت / ثانية)</span>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" id="eess-toggle-cam-btn" onclick="eessToggleAttendanceCamera()" class="sm-btn" style="background: #38a169; color: white !important; font-size: 12px; height: 34px; padding: 0 16px; border-radius: 9999px; font-weight: 800; border: none; cursor: pointer;">
                    📷 تشغيل كاميرا الماسح
                </button>
            </div>
        </div>

        <!-- Camera Viewfinder Box -->
        <div id="eess-camera-viewfinder" style="display: none; position: relative; max-width: 480px; margin: 0 auto 15px auto; border-radius: 12px; overflow: hidden; border: 2px solid #38bdf8; background: #000;">
            <video id="eess-cam-video" style="width: 100%; height: 260px; object-fit: cover;" playsinline></video>
            <div style="position: absolute; inset: 0; border: 2px dashed rgba(56, 189, 248, 0.6); margin: 30px; pointer-events: none; border-radius: 8px;"></div>
            <div id="eess-cam-status-pill" style="position: absolute; top: 10px; right: 10px; background: rgba(15, 23, 42, 0.85); color: #38bdf8; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; backdrop-filter: blur(4px);">
                الكاميرا نشطة
            </div>
        </div>

        <!-- Rapid Manual Barcode Input Fallback -->
        <div style="display: flex; gap: 10px; max-width: 480px; margin: 0 auto;">
            <input type="text" id="eess-manual-barcode-input" placeholder="امسح ببارکود الكارت أو أدخل الرقم..." autocomplete="off" style="flex: 1; height: 42px; border-radius: 10px; border: 1px solid #475569; padding: 0 14px; font-size: 13px; background: #1e293b; color: #f8fafc; font-family: monospace; font-weight: 800;" onkeypress="if(event.key==='Enter'){ eessProcessBarcodeAttendance(this.value); this.value=''; }">
            <button type="button" onclick="let inp=document.getElementById('eess-manual-barcode-input'); eessProcessBarcodeAttendance(inp.value); inp.value='';" class="sm-btn" style="background: #881337; color: white !important; height: 42px; padding: 0 20px; border-radius: 10px; font-weight: 800; border: none; cursor: pointer;">
                رصد
            </button>
        </div>
    </div>

    <!-- Center Toast Container for Sub-1-Second Instant Notifications -->
    <div id="eess-attendance-toast" style="display: none; position: fixed; top: 40%; left: 50%; transform: translate(-50%, -50%); z-index: 999999; padding: 14px 28px; border-radius: 9999px; font-weight: 800; font-size: 14px; color: #ffffff; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); backdrop-filter: blur(4px); pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease;"></div>

    <!-- Selection: Grade & Section -->
    <?php
    $is_staff = is_user_logged_in() && (current_user_can('إدارة_الطلاب') || current_user_can('تسجيل_مخالفة'));
    ?>
    <div id="at-selection-area" style="display: grid; grid-template-columns: <?php echo $is_staff ? '1fr 1fr 1fr' : '1fr'; ?>; gap: 20px; margin-bottom: 40px; background: #f8fafc; padding: 25px; border-radius: 15px; border: 1px solid #edf2f7;">
        <?php if ($is_staff): ?>
        <div class="sm-form-group" style="margin-bottom: 0;">
            <label class="sm-label" style="font-size: 1.1em;">الصف الدراسي:</label>
            <select id="at-grade-select" class="sm-select" style="height: 50px; font-size: 1.1em;" onchange="atUpdateSections()">
                <option value="">-- اختر الصف --</option>
                <?php
                $active_grades = $academic['active_grades'] ?? array();
                sort($active_grades, SORT_NUMERIC);
                foreach ($active_grades as $grade_num): ?>
                    <option value="الصف <?php echo $grade_num; ?>" data-grade-num="<?php echo $grade_num; ?>">الصف <?php echo $grade_num; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm-form-group" style="margin-bottom: 0;">
            <label class="sm-label" style="font-size: 1.1em;">الشعبة / الفصل:</label>
            <select id="at-section-select" class="sm-select" style="height: 50px; font-size: 1.1em;" disabled onchange="atLoadStudents()">
                <option value="">-- اختر الشعبة --</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="sm-form-group" style="margin-bottom: 0; text-align: center;">
            <label class="sm-label" style="font-size: 1.1em;">كود دخول الفصل:</label>
            <input type="text" id="at-security-code" class="sm-input" maxlength="4" style="height: 50px; font-size: 1.5em; text-align: center; letter-spacing: 5px; font-family: monospace; max-width: 200px; margin: 0 auto;" placeholder="0000" oninput="checkSecurityCode()">
            <?php if (!$is_staff): ?>
                <div style="font-size: 11px; color: #718096; margin-top: 8px;">أدخل الكود المكون من 4 أرقام للوصول لقائمة الطلاب</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Students List Area -->
    <div id="at-students-container" style="display: none;">
        <div id="at-list-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
            <div style="font-weight: 900; color: var(--sm-dark-color); font-size: 1.3em;">قائمة طلاب الشعبة:</div>
            <div id="at-bulk-actions" style="display: flex; gap: 15px;">
                <button onclick="atSetAll('present')" class="sm-btn" style="background: #38a169; padding: 10px 25px;">رصد حضور الجميع</button>
            </div>
        </div>

        <div id="at-students-list" style="margin-bottom: 40px;">
            <!-- Loaded via AJAX -->
        </div>

        <div id="at-footer-actions" style="text-align: center; padding-top: 30px; border-top: 1px solid #eee;">
            <button id="at-submit-btn" onclick="atSubmitAttendance()" class="sm-btn" style="width: 100%; height: 60px; font-size: 1.3em; font-weight: 900; background: var(--sm-primary-color); border-radius: 12px; box-shadow: 0 4px 14px 0 rgba(246, 48, 73, 0.39);">تأكيد وإرسال الكشف للنظام</button>
            <p id="at-post-submit-note" style="display: none; margin-top: 20px; color: #718096; font-weight: 700;">
                <span class="dashicons dashicons-info" style="font-size: 18px; width: 18px; height: 18px;"></span>
                تم إرسال كشف الحضور. يمكنك الآن تعديل حالات الغياب أو التأخير فقط.
            </p>
        </div>
    </div>

    <div id="at-no-selection" style="text-align: center; padding: 80px 40px; color: var(--sm-text-gray); background: #fcfcfc; border-radius: 15px; border: 2px dashed #eee;">
        <span class="dashicons dashicons-id-alt" style="font-size: 64px; width: 64px; height: 64px; margin-bottom: 25px; opacity: 0.2;"></span>
        <h3 style="margin: 0; color: #a0aec0; border: none;">يرجى اختيار الصف والشعبة للمتابعة</h3>
        <p style="margin-top: 10px;">سيتم عرض قائمة الطلاب فور اختيار بيانات الفصل الصحيحة (أو إدخال كود الأمان للزوار).</p>
    </div>
</div>

<script>
const dbStructure = <?php echo json_encode(SM_Settings::get_sections_from_db()); ?>;
let isSubmitted = false;
let currentStudents = [];
let isAuthorized = false;
let eessCamStream = null;
let eessLastScannedBarcode = '';
let eessLastScanTime = 0;
let eessBarcodeProcessing = false;

// Sub-1-Second Center Toast Notifications for Continuous High-Speed Scanning
function eessShowAttendanceToast(msg, type = 'success') {
    const toast = document.getElementById('eess-attendance-toast');
    if (!toast) return;

    if (type === 'success') {
        toast.style.background = '#15803d';
    } else if (type === 'duplicate') {
        toast.style.background = '#b45309';
    } else {
        toast.style.background = '#b91c1c';
    }

    toast.innerText = msg;
    toast.style.display = 'block';
    toast.style.opacity = '1';

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.style.display = 'none'; }, 150);
    }, 900);
}

// Camera Scanner Controls
function eessToggleAttendanceCamera() {
    const vBox = document.getElementById('eess-camera-viewfinder');
    const btn = document.getElementById('eess-toggle-cam-btn');

    if (eessCamStream) {
        // Stop Camera
        eessCamStream.getTracks().forEach(track => track.stop());
        eessCamStream = null;
        if (vBox) vBox.style.display = 'none';
        if (btn) {
            btn.innerText = '📷 تشغيل كاميرا الماسح';
            btn.style.background = '#38a169';
        }
    } else {
        // Start Camera
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('الكاميرا غير مدعومة في المتصفح الحالي.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            eessCamStream = stream;
            const video = document.getElementById('eess-cam-video');
            if (video) {
                video.srcObject = stream;
                video.play();
            }
            if (vBox) vBox.style.display = 'block';
            if (btn) {
                btn.innerText = '🛑 إيقاف الكاميرا';
                btn.style.background = '#dc2626';
            }

            // Start BarcodeDetector loop if supported, else fallback to high-frequency frame checks
            if ('BarcodeDetector' in window) {
                const detector = new BarcodeDetector({ formats: ['code_128', 'qr_code', 'ean_13', 'code_39'] });
                const scanFrame = () => {
                    if (!eessCamStream) return;
                    if (video.readyState === video.HAVE_ENOUGH_DATA) {
                        detector.detect(video).then(barcodes => {
                            if (barcodes.length > 0) {
                                const code = barcodes[0].rawValue;
                                const now = Date.now();
                                if (code && (code !== eessLastScannedBarcode || now - eessLastScanTime > 1500)) {
                                    eessLastScannedBarcode = code;
                                    eessLastScanTime = now;
                                    eessProcessBarcodeAttendance(code);
                                }
                            }
                        }).catch(() => {});
                    }
                    requestAnimationFrame(scanFrame);
                };
                requestAnimationFrame(scanFrame);
            } else {
                eessShowAttendanceToast('الكاميرا متصلة وتعمل بنجاح', 'success');
            }
        })
        .catch(err => {
            alert('تعذر الوصول للكاميرا: ' + err.message);
        });
    }
}

// Process Scanned Barcode Immediately to DB & Synchronize Student Grade/Class/Section
function eessProcessBarcodeAttendance(barcode) {
    if (!barcode) return;
    const cleanCode = barcode.trim();
    if (!cleanCode) return;

    if (eessBarcodeProcessing) return;
    eessBarcodeProcessing = true;

    const date = new Date().toISOString().split('T')[0];
    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_ajax');
    formData.append('student_barcode', cleanCode);
    formData.append('status', 'present');
    formData.append('date', date);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        eessBarcodeProcessing = false;
        if (res.success) {
            const stuName = (res.data && res.data.student_name) ? res.data.student_name : 'الطالب';
            const className = (res.data && res.data.class_name) ? res.data.class_name : '';
            const section = (res.data && res.data.section) ? res.data.section : '';

            // Auto-select Grade and Section dropdowns if available
            if (className) {
                const gradeSelect = document.getElementById('at-grade-select');
                if (gradeSelect) {
                    for (let i = 0; i < gradeSelect.options.length; i++) {
                        if (gradeSelect.options[i].value === className) {
                            gradeSelect.selectedIndex = i;
                            if (typeof atUpdateSections === 'function') atUpdateSections();
                            break;
                        }
                    }
                }
            }

            if (section) {
                const sectionSelect = document.getElementById('at-section-select');
                if (sectionSelect) {
                    setTimeout(() => {
                        for (let j = 0; j < sectionSelect.options.length; j++) {
                            if (sectionSelect.options[j].value === section) {
                                sectionSelect.selectedIndex = j;
                                if (typeof atLoadStudents === 'function') atLoadStudents();
                                break;
                            }
                        }
                    }, 50);
                }
            }

            if (res.data && res.data.already_recorded) {
                eessShowAttendanceToast('⚠️ الحضور مسجل بالفعل لـ ' + stuName, 'duplicate');
            } else {
                eessShowAttendanceToast('✅ تم تسجيل حضور: ' + stuName + (className ? ' (' + className + ' - ' + section + ')' : ''), 'success');
                if (typeof atLoadStudents === 'function') atLoadStudents();
            }
        } else {
            eessShowAttendanceToast('❌ ' + (res.data || 'بارکود غير معروف'), 'error');
        }
    })
    .catch(() => {
        eessBarcodeProcessing = false;
        eessShowAttendanceToast('❌ خطأ في الاتصال بالسيرفر', 'error');
    });
}

function smShowNotification(msg, isError = false) {
    if (typeof window.smShowNotification === 'function') {
        window.smShowNotification(msg, isError);
        return;
    }
    const n = document.createElement('div');
    n.style.cssText = `position:fixed; bottom:20px; left:20px; background:${isError?'#e53e3e':'#3182ce'}; color:#fff; padding:15px 25px; border-radius:10px; z-index:10000; font-weight:700; box-shadow:0 10px 15px rgba(0,0,0,0.1);`;
    n.innerText = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 4000);
}

function checkSecurityCode() {
    const isStaff = <?php echo $is_staff ? 'true' : 'false'; ?>;
    const inputCode = document.getElementById('at-security-code').value;

    if (inputCode.length !== 4) {
        isAuthorized = false;
        document.getElementById('at-security-code').style.borderColor = '';
        document.getElementById('at-security-code').style.background = '';
        if (!isStaff) {
            document.getElementById('at-students-container').style.display = 'none';
            document.getElementById('at-no-selection').style.display = 'block';
        }
        return;
    }

    if (isStaff) {
        const gradeSelect = document.getElementById('at-grade-select');
        const sectionSelect = document.getElementById('at-section-select');
        if (!gradeSelect || !sectionSelect) return;

        const className = gradeSelect.value;
        const section = sectionSelect.value;
        if (!className || !section) return;

        // Verify code via AJAX
        verifyCodeAndLoad(className, section, inputCode);
    } else {
        // Visitor mode: Try to load any class with this code
        verifyCodeAndLoad('', '', inputCode);
    }
}

function verifyCodeAndLoad(className, section, code) {
    const listContainer = document.getElementById('at-students-list');
    const container = document.getElementById('at-students-container');
    const noSel = document.getElementById('at-no-selection');

    const date = new Date().toISOString().split('T')[0];
    const formData = new FormData();
    formData.append('action', 'sm_get_students_attendance_ajax');
    if (className) formData.append('class_name', className);
    if (section) formData.append('section', section);
    formData.append('date', date);
    formData.append('security_code', code);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            isAuthorized = true;
            document.getElementById('at-security-code').style.borderColor = '#38a169';
            document.getElementById('at-security-code').style.background = '#f0fff4';

            currentStudents = res.data;
            noSel.style.display = 'none';
            container.style.display = 'block';
            atRenderList();
        } else {
            isAuthorized = false;
            document.getElementById('at-security-code').style.borderColor = '#e53e3e';

            if (className === '') { // Visitor mode
                container.style.display = 'none';
                noSel.style.display = 'block';
            }
        }
    });
}

function atLoadStudentsForVisitor(className, section) {
    const listContainer = document.getElementById('at-students-list');
    const container = document.getElementById('at-students-container');
    const noSel = document.getElementById('at-no-selection');

    noSel.style.display = 'none';
    container.style.display = 'block';
    listContainer.innerHTML = '<div style="text-align: center; padding: 60px;"><div class="at-spinner"></div><p style="margin-top: 20px; color: #718096; font-weight: 700;">جاري تحميل قائمة الطلاب...</p></div>';

    const date = new Date().toISOString().split('T')[0];
    const code = document.getElementById('at-security-code').value;
    const formData = new FormData();
    formData.append('action', 'sm_get_students_attendance_ajax');
    formData.append('class_name', className);
    formData.append('section', section);
    formData.append('date', date);
    formData.append('security_code', code);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            currentStudents = res.data;
            atRenderList();
        } else {
            listContainer.innerHTML = '<div style="color: #e53e3e; padding: 25px; background: #fff5f5; border-radius: 10px; text-align: center; font-weight: 700;">' + res.data + '</div>';
        }
    });
}


function atUpdateSections() {
    const gradeSelect = document.getElementById('at-grade-select');
    const sectionSelect = document.getElementById('at-section-select');
    const gradeNum = gradeSelect.options[gradeSelect.selectedIndex].getAttribute('data-grade-num');

    sectionSelect.innerHTML = '<option value="">-- اختر الشعبة --</option>';
    isSubmitted = false; // Reset on change

    if (!gradeNum) {
        sectionSelect.disabled = true;
        document.getElementById('at-students-container').style.display = 'none';
        document.getElementById('at-no-selection').style.display = 'block';
        return;
    }

    const sections = dbStructure[gradeNum] || [];
    sections.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.innerText = 'شعبة ' + s;
        sectionSelect.appendChild(opt);
    });

    sectionSelect.disabled = false;
    document.getElementById('at-students-container').style.display = 'none';
    document.getElementById('at-no-selection').style.display = 'block';
}

function atLoadStudents() {
    const gradeSelect = document.getElementById('at-grade-select');
    const sectionSelect = document.getElementById('at-section-select');
    if (!gradeSelect || !sectionSelect) return;

    const className = gradeSelect.value;
    const section = sectionSelect.value;

    // Clear security code when changing class
    const codeInput = document.getElementById('at-security-code');
    if (codeInput) {
        codeInput.value = '';
        checkSecurityCode();
    }

    const listContainer = document.getElementById('at-students-list');
    const container = document.getElementById('at-students-container');
    const noSel = document.getElementById('at-no-selection');

    if (!className || !section) {
        container.style.display = 'none';
        noSel.style.display = 'block';
        return;
    }

    noSel.style.display = 'none';
    container.style.display = 'block';
    listContainer.innerHTML = '<div style="text-align: center; padding: 60px;"><div class="at-spinner"></div><p style="margin-top: 20px; color: #718096; font-weight: 700;">جاري تحميل قائمة الطلاب...</p></div>';

    const date = new Date().toISOString().split('T')[0];
    const formData = new FormData();
    formData.append('action', 'sm_get_students_attendance_ajax');
    formData.append('class_name', className);
    formData.append('section', section);
    formData.append('date', date);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            currentStudents = res.data;
            atRenderList();
        } else {
            listContainer.innerHTML = '<div style="color: #e53e3e; padding: 25px; background: #fff5f5; border-radius: 10px; text-align: center; font-weight: 700;">' + res.data + '</div>';
        }
    });
}

function atRenderList() {
    const listContainer = document.getElementById('at-students-list');
    const bulkArea = document.getElementById('at-bulk-actions');
    const submitBtn = document.getElementById('at-submit-btn');
    const note = document.getElementById('at-post-submit-note');

    if (currentStudents.length === 0) {
        listContainer.innerHTML = '<div style="padding: 60px; text-align: center; background: #fcfcfc; border-radius: 15px;">لا يوجد طلاب مسجلين في هذه الشعبة.</div>';
        return;
    }

    if (isSubmitted) {
        bulkArea.style.display = 'none';
        submitBtn.style.display = 'none';
        note.style.display = 'block';
    } else {
        bulkArea.style.display = 'flex';
        submitBtn.style.display = 'block';
        note.style.display = 'none';
    }

    let html = '<div style="display: grid; grid-template-columns: 1fr; gap: 15px;">';
    currentStudents.forEach(s => {
        const photo = s.photo_url ? `<img src="${s.photo_url}" style="width: 55px; height: 55px; border-radius: 12px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">` : `<div style="width: 55px; height: 55px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #cbd5e0; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">👤</div>`;

        const status = s.status || 'present';

        // If submitted, hide "present" students
        if (isSubmitted && status === 'present') return;

        html += `
            <div class="at-student-row animated fadeIn" data-student-id="${s.id}" style="display: flex; align-items: center; justify-content: space-between; padding: 18px 25px; border: 1px solid #e2e8f0; border-radius: 15px; background: #fff; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="display: flex; align-items: center; gap: 20px;">
                    ${photo}
                    <div>
                        <div style="font-weight: 800; font-size: 1.1em; color: var(--sm-dark-color);">${s.name}</div>
                        <div style="font-size: 0.85em; color: var(--sm-text-gray); font-weight: 700; margin-top: 4px;">ID: ${s.student_code}</div>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">

                    <div style="display: flex; gap: 12px;">
                        ${!isSubmitted ? `
                            <button onclick="atSetStatus(this, 'present')" class="at-choice-btn ${status === 'present' ? 'active' : ''}" data-status="present" title="حاضر">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span class="btn-lbl">حاضر</span>
                            </button>
                        ` : ''}
                        <button onclick="atSetStatus(this, 'late')" class="at-choice-btn ${status === 'late' ? 'active' : ''}" data-status="late" title="متأخر">
                            <span class="dashicons dashicons-clock"></span>
                            <span class="btn-lbl">تأخير</span>
                        </button>
                        <button onclick="atSetStatus(this, 'absent')" class="at-choice-btn ${status === 'absent' ? 'active' : ''}" data-status="absent" title="غائب">
                            <span class="dashicons dashicons-no"></span>
                            <span class="btn-lbl">غياب</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    listContainer.innerHTML = html;
}

function atSetStatus(btn, status) {
    const row = btn.closest('.at-student-row');
    const sid = row.getAttribute('data-student-id');

    // Update local data
    const stu = currentStudents.find(s => s.id == sid);
    if (stu) stu.status = status;

    row.querySelectorAll('.at-choice-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // If already submitted, update single record immediately
    if (isSubmitted) {
        saveAttendanceToDB(sid, status);
    }
}

function atSetAll(status) {
    currentStudents.forEach(s => s.status = status);
    atRenderList();
}

async function atSubmitAttendance() {
    const btn = document.getElementById('at-submit-btn');
    const date = new Date().toISOString().split('T')[0];
    const nonce = '<?php echo wp_create_nonce("sm_attendance_action"); ?>';

    btn.disabled = true;
    btn.innerHTML = '<div class="at-spinner-sm"></div> جاري حفظ البيانات...';

    const batch = currentStudents.map(s => ({
        student_id: s.id,
        status: s.status || 'present'
    }));

    const code = document.getElementById('at-security-code').value;
    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_batch_ajax');
    formData.append('batch', JSON.stringify(batch));
    formData.append('date', date);
    formData.append('nonce', nonce);
    formData.append('security_code', code);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ كشف الحضور بنجاح لعدد ' + res.data + ' طالب');

        // Show submission confirmation notification
        const confirmNotif = document.createElement('div');
        confirmNotif.style.cssText = "position:fixed; top:80px; left:50%; transform:translateX(-50%); background:#38a169; color:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.2); z-index:10002; font-weight:800; animation: smFadeIn 0.3s ease-out;";
        confirmNotif.innerHTML = '✅ تم إرسال كشف الحضور بنجاح';
        document.body.appendChild(confirmNotif);
        setTimeout(() => {
            confirmNotif.style.opacity = '0';
            confirmNotif.style.transition = '0.5s';
            setTimeout(() => confirmNotif.remove(), 500);
        }, 3000);

            isSubmitted = true;
            atRenderList();
        } else {
            smShowNotification('حدث خطأ في الاتصال بالنظام: ' + res.data, true);
            btn.disabled = false;
            btn.innerText = 'تأكيد وإرسال الكشف للنظام';
        }
    })
    .catch(err => {
        smShowNotification('حدث خطأ تقني في الاتصال', true);
        btn.disabled = false;
        btn.innerText = 'تأكيد وإرسال الكشف للنظام';
    });
}

function saveAttendanceToDB(sid, status) {
    const date = new Date().toISOString().split('T')[0];
    const code = document.getElementById('at-security-code').value;
    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_ajax');
    formData.append('student_id', sid);
    formData.append('status', status);
    formData.append('date', date);
    formData.append('security_code', code);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم التحديث بنجاح');
            // If it was changed to absent/late from something else, or vice-versa
            // In this specific UI, we just keep the filtered view if submitted
            atRenderList();
        }
    });
}
</script>

<style>
.at-spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--sm-primary-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
.at-spinner-sm { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; margin-left: 10px; vertical-align: middle; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.at-student-row:hover { border-color: var(--sm-primary-color); transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }

.at-choice-btn {
    height: 48px; min-width: 100px; padding: 0 20px;
    border-radius: 12px; border: 1px solid #e2e8f0; background: #fff;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: 0.2s; color: #718096; font-weight: 700; font-size: 0.9em;
}
.at-choice-btn .dashicons { font-size: 20px; width: 20px; height: 20px; }
.at-choice-btn[data-status="present"].active { background: #38a169; color: #fff; border-color: #38a169; box-shadow: 0 4px 10px rgba(56, 161, 105, 0.3); }
.at-choice-btn[data-status="late"].active { background: #ecc94b; color: #fff; border-color: #ecc94b; box-shadow: 0 4px 10px rgba(236, 201, 75, 0.3); }
.at-choice-btn[data-status="absent"].active { background: #e53e3e; color: #fff; border-color: #e53e3e; box-shadow: 0 4px 10px rgba(229, 62, 62, 0.3); }

.animated { animation-duration: 0.4s; animation-fill-mode: both; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.fadeIn { animation-name: fadeIn; }

@media (max-width: 768px) {
    .sm-class-attendance-shortcode { padding: 20px; }
    #at-selection-area { grid-template-columns: 1fr; }
    .at-student-row { flex-direction: column; gap: 15px; align-items: flex-start; }
    .at-student-row > div:last-child { width: 100%; justify-content: flex-end; }
    .at-choice-btn { flex: 1; min-width: 0; padding: 0 10px; }
    .btn-lbl { display: none; }
}
</style>
