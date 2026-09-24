<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-surveys-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h3 style="margin:0;"> No </h3>
        <button class="sm-btn" onclick="smOpenNewSurveyModal()" style="width: auto;">+  No </button>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>Address</th>
                    <th> </th>
                    <th> </th>
                    <th>Status</th>
                    <th></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $surveys = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_surveys ORDER BY created_at DESC");
                foreach ($surveys as $s):
                    $questions = json_decode($s->questions, true);
                    $responses_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_survey_responses WHERE survey_id = %d", $s->id));
                ?>
                <tr>
                    <td><strong><?php echo esc_html($s->title); ?></strong></td>
                    <td>
                        <?php
                        if ($s->target_roles === 'all') echo '';
                        elseif ($s->target_roles === 'sm_student') echo 'No';
                        elseif ($s->target_roles === 'sm_teacher') echo 'Coaches';
                        elseif ($s->target_roles === 'sm_coordinator') echo '';
                        else echo esc_html($s->target_roles);
                        ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($s->created_at)); ?></td>
                    <td>
                        <span class="sm-badge" style="background: <?php echo $s->status === 'active' ? '#38a169' : '#e53e3e'; ?>;">
                            <?php echo $s->status === 'active' ? 'Active' : ''; ?>
                        </span>
                    </td>
                    <td>
                        <button class="sm-btn sm-btn-outline" onclick="smViewSurveyResults(<?php echo $s->id; ?>, '<?php echo esc_js($s->title); ?>')" style="padding: 2px 10px; font-size: 11px;">
                            <?php echo $responses_count; ?>
                        </button>
                    </td>
                    <td>
                        <?php if ($s->status === 'active'): ?>
                            <button class="sm-btn sm-btn-outline" onclick="smCancelSurvey(<?php echo $s->id; ?>)" style="color: #e53e3e; border-color: #feb2b2; padding: 2px 10px; font-size: 11px;">Cancel</button>
                        <?php endif; ?>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_survey_results&id='.$s->id); ?>" class="sm-btn sm-btn-outline" style="padding: 2px 10px; font-size: 11px;">CSV</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- NEW SURVEY MODAL -->
<div id="new-survey-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 700px;">
        <div class="sm-modal-header">
            <h3> No  </h3>
            <button class="sm-modal-close" onclick="this.closest('.sm-modal-overlay').style.display='none'">&times;</button>
        </div>
        <div class="sm-modal-body">
            <div class="sm-form-group">
                <label class="sm-label">   ():</label>
                <select id="survey_template_select" class="sm-select" onchange="smLoadSurveyTemplate(this.value)">
                    <option value="">--   --</option>
                    <option value="student_satisfaction">  No   </option>
                    <option value="teacher_feedback">    (  )</option>
                    <option value="parent_engagement">    Mother </option>
                    <option value="academic_environment">   </option>
                </select>
            </div>
            <div class="sm-form-group">
                <label class="sm-label"> NoNo:</label>
                <input type="text" id="survey_title" class="sm-input" placeholder=":   ">
            </div>
            <div class="sm-form-group">
                <label class="sm-label"> :</label>
                <select id="survey_recipients" class="sm-select">
                    <option value="all"></option>
                    <option value="sm_student">No </option>
                    <option value="sm_teacher">Coaches </option>
                    <option value="sm_coordinator"> </option>
                </select>
            </div>
            <div id="survey-questions-container">
                <label class="sm-label"> ( ):</label>
                <div class="survey-q-item" style="display:flex; gap:10px; margin-bottom:10px;">
                    <input type="text" class="sm-input survey-q-input" placeholder=" ">
                    <button class="sm-btn sm-btn-outline" style="color:red; border-color:red; width:40px;" onclick="this.parentElement.remove()">×</button>
                </div>
            </div>
            <button class="sm-btn sm-btn-outline" onclick="smAddSurveyQuestion()" style="margin-top:10px;">+ Add  </button>

            <div style="margin-top:30px; display:flex; gap:10px;">
                <button class="sm-btn" onclick="smSaveSurvey()" style="flex:1;"> NoNo</button>
                <button class="sm-btn sm-btn-outline" onclick="this.closest('.sm-modal-overlay').style.display='none'" style="flex:1;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- RESULTS MODAL -->
<div id="survey-results-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 800px;">
        <div class="sm-modal-header">
            <h3 id="res-modal-title"> NoNo</h3>
            <button class="sm-modal-close" onclick="this.closest('.sm-modal-overlay').style.display='none'">&times;</button>
        </div>
        <div id="survey-results-body" style="max-height: 500px; overflow-y: auto; padding: 20px;">
            <!-- Results will be loaded here -->
        </div>
    </div>
</div>

<script>
const surveyTemplates = {
    'student_satisfaction': {
        title: '  No   ',
        recipients: 'sm_student',
        questions: [
            '      ',
            '     ',
            '      ',
            '  Mother   ',
            '      No'
        ]
    },
    'teacher_feedback': {
        title: '   ',
        recipients: 'sm_coordinator',
        questions: [
            '     ',
            '    ',
            '   Training Group  No',
            '    No ',
            '  No  '
        ]
    },
    'parent_engagement': {
        title: '  Academy    Mother',
        recipients: 'all',
        questions: [
            '       /',
            '      ',
            '       ',
            '  No    Level No',
            '     Academy  '
        ]
    },
    'academic_environment': {
        title: '   ',
        recipients: 'sm_teacher',
        questions: [
            '      ',
            '     ',
            '    ',
            '  No   ',
            '      Academy '
        ]
    }
};

function smLoadSurveyTemplate(key) {
    if (!key || !surveyTemplates[key]) return;
    const t = surveyTemplates[key];
    document.getElementById('survey_title').value = t.title;
    document.getElementById('survey_recipients').value = t.recipients;

    const container = document.getElementById('survey-questions-container');
    container.innerHTML = '<label class="sm-label"> ( ):</label>';

    t.questions.forEach(q => {
        const div = document.createElement('div');
        div.className = 'survey-q-item';
        div.style = "display:flex; gap:10px; margin-bottom:10px;";
        div.innerHTML = `
            <input type="text" class="sm-input survey-q-input" value="${q}" placeholder=" ">
            <button class="sm-btn sm-btn-outline" style="color:red; border-color:red; width:40px;" onclick="this.parentElement.remove()">×</button>
        `;
        container.appendChild(div);
    });
}

function smOpenNewSurveyModal() {
    document.getElementById('new-survey-modal').style.display = 'flex';
}

function smAddSurveyQuestion() {
    const container = document.getElementById('survey-questions-container');
    const div = document.createElement('div');
    div.className = 'survey-q-item';
    div.style = "display:flex; gap:10px; margin-bottom:10px;";
    div.innerHTML = `
        <input type="text" class="sm-input survey-q-input" placeholder=" ">
        <button class="sm-btn sm-btn-outline" style="color:red; border-color:red; width:40px;" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(div);
}

function smSaveSurvey() {
    const title = document.getElementById('survey_title').value;
    const recipients = document.getElementById('survey_recipients').value;
    const inputs = document.querySelectorAll('.survey-q-input');
    const questions = [];
    inputs.forEach(input => {
        if (input.value.trim()) questions.push(input.value.trim());
    });

    if (!title || questions.length === 0) {
        smShowNotification('  Address    ', true);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_add_survey');
    formData.append('title', title);
    formData.append('recipients', recipients);
    formData.append('questions', JSON.stringify(questions));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('  NoNo ');
            location.reload();
        } else {
            smShowNotification(': ' + res.data, true);
        }
    });
}

function smCancelSurvey(id) {
    if (!confirm('    Cancel  NoNo        .')) return;

    const formData = new FormData();
    formData.append('action', 'sm_cancel_survey');
    formData.append('id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification(' Cancel NoNo');
            location.reload();
        }
    });
}

function smViewSurveyResults(id, title) {
    document.getElementById('res-modal-title').innerText = ': ' + title;
    const body = document.getElementById('survey-results-body');
    body.innerHTML = '<p style="text-align:center;"> Upload ...</p>';
    document.getElementById('survey-results-modal').style.display = 'flex';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_survey_results&id=' + id)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            let html = '';
            res.data.forEach(item => {
                html += `<div style="margin-bottom: 25px; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-weight: 800; margin-bottom: 15px; color: var(--sm-dark-color);">${item.question}</div>
                    <div style="display: grid; gap: 10px;">`;

                // For simplicity, showing counts of distinct answers
                for (const [ans, count] of Object.entries(item.answers)) {
                    html += `<div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 8px 15px; border-radius: 5px; border: 1px solid #edf2f7;">
                        <span>${ans}</span>
                        <span style="font-weight: 700; color: var(--sm-primary-color);">${count}</span>
                    </div>`;
                }

                if (Object.keys(item.answers).length === 0) {
                    html += '<div style="font-size: 12px; color: #718096; font-style: italic;">No   </div>';
                }

                html += `</div></div>`;
            });
            body.innerHTML = html;
        } else {
            body.innerHTML = '<p style="color:red;"> Upload </p>';
        }
    });
}
</script>
