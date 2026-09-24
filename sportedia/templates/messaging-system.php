<?php if (!defined('ABSPATH')) exit; ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!--   /   -->
    <div style="background: var(--sm-bg-light); padding: 20px; border-radius: 8px; border: 1px solid var(--sm-border-color);">
        <h4 style="margin-top:0;"> </h4>
        <div id="sent-messages-list" style="max-height: 500px; overflow-y: auto;">
            <?php
            $sent = SM_DB::get_sent_messages(get_current_user_id());
            if (empty($sent)): ?>
                <p style="font-size: 0.9em; color: var(--sm-text-gray);">No    .</p>
            <?php else: ?>
                <?php foreach ($sent as $m): ?>
                    <div style="background: #fff; padding: 12px; border-radius: 6px; margin-bottom: 10px; border-right: 3px solid var(--sm-primary-color); font-size: 0.9em;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <strong>: <?php echo esc_html($m->receiver_name); ?></strong>
                            <span style="font-size: 0.8em; color: #999;"><?php echo date('Y-m-d', strtotime($m->created_at)); ?></span>
                        </div>
                        <p style="margin:0; color: var(--sm-text-gray);"><?php echo esc_html($m->message); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!--    -->
    <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid var(--sm-border-color);">
        <h4 style="margin-top:0;">Send  </h4>
        <form id="sm-send-message-form">
            <?php wp_nonce_field('sm_message_action', 'sm_message_nonce'); ?>
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">

            <div class="sm-form-group">
                <label class="sm-label">:</label>
                <select name="receiver_id" class="sm-select" required>
                    <?php
                    $curr_user = wp_get_current_user();
                    $is_stu = in_array('sm_student', (array)$curr_user->roles);

                    if ($is_stu) {
                        $student = SM_DB::get_student_by_parent($curr_user->ID);
                        if ($student) {
                            $staff = SM_DB::get_staff_by_section($student->class_name, $student->section);
                            echo '<optgroup label="Coaches   ">';
                            foreach($staff as $u) echo '<option value="'.$u->ID.'">'.$u->display_name.'</option>';
                            echo '</optgroup>';
                        }
                    } else {
                        $admins = get_users(array('role' => 'sm_principal'));
                        $supervisors = get_users(array('role' => 'sm_supervisor'));
                        echo '<optgroup label=" Academy ">';
                        foreach($admins as $a) echo '<option value="'.$a->ID.'">'.$a->display_name.'</option>';
                        echo '</optgroup>';
                        echo '<optgroup label="  ">';
                        foreach($supervisors as $o) echo '<option value="'.$o->ID.'">'.$o->display_name.'</option>';
                        echo '</optgroup>';
                    }
                    ?>
                </select>
            </div>

            <div class="sm-form-group">
                <label class="sm-label"> :</label>
                <textarea name="message" class="sm-textarea" rows="5" placeholder="   No ..." required></textarea>
            </div>

            <button type="submit" class="sm-btn">Send  </button>
        </form>
        <div id="message-status" style="margin-top: 15px; display: none; padding: 10px; border-radius: 5px;"></div>
    </div>
</div>

<?php if (current_user_can('_Users')): ?>
<div style="margin-top: 40px; background: #ebf8ff; padding: 30px; border-radius: 8px; border: 1px solid #bee3f8;">
    <h4 style="margin-top:0; color: #2b6cb0;">Send  /  </h4>
    <p style="font-size: 0.85em; color: #2c5282; margin-bottom: 20px;"> Send    Email Address  Users   .</p>
    <form id="sm-group-message-form">
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
            <div class="sm-form-group">
                <label class="sm-label"> :</label>
                <select name="target_role" class="sm-select">
                    <option value="sm_student"> No</option>
                    <option value="sm_teacher"> </option>
                    <option value="sm_supervisor">  </option>
                </select>
            </div>
            <div class="sm-form-group">
                <label class="sm-label"> :</label>
                <textarea name="message" class="sm-textarea" rows="3" placeholder="   ..." required></textarea>
            </div>
        </div>
        <button type="submit" class="sm-btn" style="background: #3182ce; width: auto;">Send  </button>
    </form>
    <div id="group-status" style="margin-top: 15px; display: none; padding: 10px; border-radius: 5px;"></div>
</div>
<?php endif; ?>

<script>
document.getElementById('sm-send-message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    const status = document.getElementById('message-status');

    btn.disabled = true;
    btn.innerText = ' Send...';

    const formData = new FormData(this);
    formData.append('action', 'sm_send_message_ajax');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            status.innerHTML = ' Send  .';
            status.style.background = '#f0fff4';
            status.style.color = '#22543d';
            status.style.display = 'block';
            this.reset();
            // Optional: Refresh sent messages list
        } else {
            status.innerHTML = ': ' + res.data;
            status.style.background = '#fff5f5';
            status.style.color = '#c53030';
            status.style.display = 'block';
        }
        btn.disabled = false;
        btn.innerText = 'Send  ';
    });
});

if (document.getElementById('sm-group-message-form')) {
    document.getElementById('sm-group-message-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        const status = document.getElementById('group-status');

        btn.disabled = true;
        btn.innerText = ' Send ...';

        const formData = new FormData(this);
        formData.append('action', 'sm_send_group_message_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                status.innerHTML = ' Send   Users .';
                status.style.background = '#f0fff4';
                status.style.color = '#22543d';
                status.style.display = 'block';
                this.reset();
            } else {
                status.innerHTML = ': ' + res.data;
                status.style.display = 'block';
            }
            btn.disabled = false;
            btn.innerText = 'Send  ';
        });
    });
}
</script>
