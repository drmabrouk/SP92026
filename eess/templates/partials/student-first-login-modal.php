<?php if (!defined('ABSPATH')) exit;
$current_user_id = get_current_user_id();
if ($current_user_id && get_user_meta($current_user_id, 'eess_must_change_password', true) === '1'):
    $user_obj = wp_get_current_user();
    $roles = (array) $user_obj->roles;
    if (in_array('sm_student', $roles) || in_array('sm_parent', $roles)):
?>
<div id="eess-student-first-login-modal" class="sm-modal-overlay" style="display: flex; z-index: 9999999; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px);">
    <div class="sm-modal-content" style="max-width: 520px; width: 92vw; border-radius: 20px; padding: 30px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); font-family: 'Cairo', sans-serif; text-align: right;" dir="rtl">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #fff1f2; color: #881337; border: 2px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 28px;">
                🔐
            </div>
            <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 900; color: #0f172a;">مرحباً بك في المنظومة التعليمية الرقمية</h3>
            <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 700; line-height: 1.6;">
                لحماية حسابك وبياناتك الأكاديمية، يتوجب عليك تعيين كلمة مرور جديدة وخاصة بك قبل المتابعة لاستخدام المنظومة.
            </p>
        </div>

        <form id="eess-student-change-pass-form">
            <?php wp_nonce_field('eess_student_password_action', 'eess_student_password_nonce'); ?>

            <div style="background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #cbd5e1; margin-bottom: 20px;">
                <div class="sm-form-group" style="margin-bottom: 14px;">
                    <label class="sm-label" style="font-size: 12px; font-weight: 800; color: #334155;">كلمة المرور الجديدة: <span style="color: #dc2626;">*</span></label>
                    <input type="password" name="new_password" id="eess_stu_new_pass" class="sm-input" required minlength="8" maxlength="30" placeholder="••••••••" style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
                </div>

                <div class="sm-form-group" style="margin-bottom: 14px;">
                    <label class="sm-label" style="font-size: 12px; font-weight: 800; color: #334155;">تأكيد كلمة المرور الجديدة: <span style="color: #dc2626;">*</span></label>
                    <input type="password" name="confirm_password" id="eess_stu_confirm_pass" class="sm-input" required minlength="8" maxlength="30" placeholder="••••••••" style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
                </div>

                <div style="font-size: 11px; color: #475569; background: #ffffff; padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; line-height: 1.6;">
                    💡 <strong>اشتراطات كلمة المرور المعتمدة:</strong><br>
                    • الطول بين 8 و 30 حرفاً.<br>
                    • تحتوي على حرف كبير واحد على الأقل (A-Z).<br>
                    • تحتوي على حرف صغير واحد على الأقل (a-z).<br>
                    • تحتوي على رقم واحد على الأقل (0-9).
                </div>
            </div>

            <div id="eess_stu_pass_msg" style="display: none; padding: 10px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; margin-bottom: 16px;"></div>

            <button type="submit" id="eess_stu_pass_submit_btn" class="sm-btn" style="width: 100%; background: #881337; color: #ffffff; height: 42px; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; border: none; box-shadow: 0 4px 12px rgba(136, 19, 55, 0.25);">
                اعتماد كلمة المرور الجديدة والدخول للمنظومة
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('eess-student-change-pass-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const newPass = document.getElementById('eess_stu_new_pass').value.trim();
            const confirmPass = document.getElementById('eess_stu_confirm_pass').value.trim();
            const msgBox = document.getElementById('eess_stu_pass_msg');
            const submitBtn = document.getElementById('eess_stu_pass_submit_btn');

            msgBox.style.display = 'none';

            if (newPass !== confirmPass) {
                msgBox.style.background = '#fef2f2'; msgBox.style.color = '#dc2626'; msgBox.style.border = '1px solid #fecdd3';
                msgBox.innerText = 'كلمتا المرور غير متطابقتين.';
                msgBox.style.display = 'block';
                return;
            }

            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,30}$/;
            if (!regex.test(newPass)) {
                msgBox.style.background = '#fef2f2'; msgBox.style.color = '#dc2626'; msgBox.style.border = '1px solid #fecdd3';
                msgBox.innerText = 'كلمة المرور لا تستوفي الشروط (حرف كبير، حرف صغير، رقم، وطول 8-30).';
                msgBox.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerText = 'جاري الحفظ والاعتماد...';

            const formData = new FormData(this);
            formData.append('action', 'eess_student_change_password');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    msgBox.style.background = '#f0fdf4'; msgBox.style.color = '#166534'; msgBox.style.border = '1px solid #bbf7d0';
                    msgBox.innerText = '✓ ' + (res.data || 'تم تغيير كلمة المرور بنجاح.');
                    msgBox.style.display = 'block';
                    setTimeout(() => location.reload(), 800);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'اعتماد كلمة المرور الجديدة والدخول للمنظومة';
                    msgBox.style.background = '#fef2f2'; msgBox.style.color = '#dc2626'; msgBox.style.border = '1px solid #fecdd3';
                    msgBox.innerText = 'خطأ: ' + (res.data || 'تعذر حفظ كلمة المرور.');
                    msgBox.style.display = 'block';
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'اعتماد كلمة المرور الجديدة والدخول للمنظومة';
                msgBox.style.background = '#fef2f2'; msgBox.style.color = '#dc2626'; msgBox.style.border = '1px solid #fecdd3';
                msgBox.innerText = 'حدث خطأ في الاتصال بالسيرفر.';
                msgBox.style.display = 'block';
            });
        });
    }
});
</script>
<?php endif; endif; ?>

<?php
// Student Unread Messages Modal on Login
if ($current_user_id):
    global $wpdb;
    $unread_messages = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as sender_name FROM {$wpdb->prefix}sm_messages m LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID WHERE m.receiver_id = %d AND m.status = 'unread' ORDER BY m.created_at ASC",
        $current_user_id
    ));
    if (!empty($unread_messages)):
        $first_msg = $unread_messages[0];
?>
<div id="eess-student-message-login-modal" class="sm-modal-overlay" style="display: flex; z-index: 999999; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px);">
    <div class="sm-modal-content" style="max-width: 520px; width: 92vw; border-radius: 20px; padding: 28px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); font-family: 'Cairo', sans-serif; text-align: right;" dir="rtl">
        <div style="text-align: center; margin-bottom: 18px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #f0fdf4; color: #16a34a; border: 2px solid #bbf7d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px; font-size: 26px;">
                📩
            </div>
            <h3 style="margin: 0 0 6px 0; font-size: 17px; font-weight: 900; color: #0f172a;">لديك رسالة رسمية جديدة من إدارة المدرسة</h3>
            <p style="margin: 0; font-size: 12px; color: #64748b; font-weight: 700;">من: <?php echo esc_html($first_msg->sender_name ?: 'إدارة الشؤون الطلابية'); ?> | التاريخ: <?php echo esc_html(date('Y-m-d H:i', strtotime($first_msg->created_at))); ?></p>
        </div>

        <div style="background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #cbd5e1; margin-bottom: 20px; font-size: 13.5px; color: #1e293b; font-weight: 700; line-height: 1.7; white-space: pre-wrap;">
            <?php echo esc_html($first_msg->message); ?>
        </div>

        <button type="button" onclick="eessMarkStudentMessageRead(<?php echo $first_msg->id; ?>)" id="eess_msg_read_btn" class="sm-btn" style="width: 100%; background: #16a34a; color: #ffffff; height: 42px; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; border: none; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);">
            تحديد كـ "تمت القراءة" والمتابعة
        </button>
    </div>
</div>

<script>
function eessMarkStudentMessageRead(msgId) {
    const btn = document.getElementById('eess_msg_read_btn');
    if (btn) { btn.disabled = true; btn.innerText = 'جاري التوثيق...'; }

    const formData = new FormData();
    formData.append('action', 'eess_mark_message_read');
    formData.append('message_id', msgId);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        const modal = document.getElementById('eess-student-message-login-modal');
        if (modal) modal.style.display = 'none';
        location.reload();
    })
    .catch(() => {
        const modal = document.getElementById('eess-student-message-login-modal');
        if (modal) modal.style.display = 'none';
    });
}
</script>
<?php endif; endif; ?>
