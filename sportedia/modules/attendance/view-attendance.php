<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
?>
<div class="sportedia-attendance-app">
    <div class="attendance-card">
        <div class="attendance-header">
            <h2>Attendance &amp; Check-In</h2>
            <p>Scan the dynamic attendance QR code using your mobile device</p>
        </div>

        <!-- Dynamic QR Code Display -->
        <div class="qr-display-container">
            <div id="sportedia-qr-code-box" class="qr-box">
                <canvas id="sportedia-qr-canvas" width="220" height="220"></canvas>
            </div>
            <div class="qr-timer-bar">
                <div id="sportedia-qr-timer-fill" class="timer-fill"></div>
            </div>
            <div id="sportedia-qr-timestamp" class="qr-timestamp">--</div>
        </div>

        <!-- Notification Toast Container -->
        <div id="sportedia-attendance-toast" class="attendance-toast" style="display:none;"></div>

        <!-- Mobile Scanner Input Trigger -->
        <div class="attendance-scan-actions">
            <input type="text" id="sportedia-scanned-token-input" class="sportedia-floating-input" placeholder="Scan or enter token..." autocomplete="off" />
            <button type="button" id="sportedia-submit-scan-btn" class="sportedia-btn primary">Record Attendance</button>
        </div>

        <!-- User Logged In Info -->
        <div class="attendance-user-footer">
            <span>User: <?php echo esc_html($current_user->display_name ?: 'Employee'); ?></span> |
            <span>Date: <?php echo esc_html(date('Y-m-d')); ?></span>
        </div>
    </div>
</div>

<script>
(function() {
    let currentToken = '';

    // Simple local QR Code Canvas rendering (data matrix fallback)
    function drawLocalQR(text) {
        const canvas = document.getElementById('sportedia-qr-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, 220, 220);

        ctx.fillStyle = '#111827';
        // Simple visual hash representation for dynamic QR code rendering
        const size = 10;
        const hash = text.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        for (let r = 0; r < 22; r++) {
            for (let c = 0; c < 22; c++) {
                if ((r + c + hash) % 3 === 0 || (r * c + hash) % 5 === 0) {
                    ctx.fillRect(c * size, r * size, size, size);
                }
            }
        }
    }

    function fetchDynamicToken() {
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=sportedia_get_dynamic_token')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    currentToken = data.data.token;
                    drawLocalQR(currentToken);

                    const tsBox = document.getElementById('sportedia-qr-timestamp');
                    if (tsBox) tsBox.innerText = 'Code Timestamp: ' + data.data.timestamp_str;
                    resetTimerBar(data.data.expires_in || 5);
                }
            })
            .catch(err => console.error('Error fetching attendance token:', err));
    }

    function resetTimerBar(expiresIn) {
        const fill = document.getElementById('sportedia-qr-timer-fill');
        if (!fill) return;
        fill.style.transition = 'none';
        fill.style.width = '100%';
        setTimeout(() => {
            fill.style.transition = 'width ' + expiresIn + 's linear';
            fill.style.width = '0%';
        }, 50);
    }

    function showToast(message, isError = false) {
        const toast = document.getElementById('sportedia-attendance-toast');
        if (!toast) return;
        toast.style.display = 'block';
        toast.className = 'attendance-toast ' + (isError ? 'error' : 'success');
        toast.innerText = message;
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3500);
    }

    function processScan(tokenToProcess) {
        const token = tokenToProcess || document.getElementById('sportedia-scanned-token-input').value.trim() || currentToken;
        if (!token) {
            showToast('No token provided.', true);
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sportedia_process_attendance_scan');
        formData.append('attendance_token', token);
        formData.append('nonce', '<?php echo wp_create_nonce('sportedia_attendance_nonce'); ?>');

        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.data.message || 'Attendance recorded successfully.');
                document.getElementById('sportedia-scanned-token-input').value = '';
            } else {
                showToast(data.data || 'Scan failed.', true);
            }
        })
        .catch(() => showToast('Server communication error.', true));
    }

    document.addEventListener('DOMContentLoaded', function() {
        fetchDynamicToken();
        setInterval(fetchDynamicToken, 5000);

        const btn = document.getElementById('sportedia-submit-scan-btn');
        if (btn) {
            btn.addEventListener('click', function() {
                processScan();
            });
        }

        const input = document.getElementById('sportedia-scanned-token-input');
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processScan(this.value);
                }
            });
        }
    });
})();
</script>

<style>
.sportedia-attendance-app {
    max-width: 480px;
    margin: 20px auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.attendance-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.attendance-header h2 {
    margin: 0 0 8px 0;
    font-size: 20px;
    color: #111827;
}
.attendance-header p {
    margin: 0 0 20px 0;
    font-size: 13px;
    color: #6b7280;
}
.qr-display-container {
    margin: 20px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.qr-box {
    width: 220px;
    height: 220px;
    background: #ffffff;
    border: 2px solid #111827;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}
.qr-timer-bar {
    width: 220px;
    height: 4px;
    background: #e5e7eb;
    margin-top: 10px;
    border-radius: 2px;
    overflow: hidden;
}
.timer-fill {
    height: 100%;
    background: #111827;
    width: 100%;
}
.qr-timestamp {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 6px;
    font-family: monospace;
}
.attendance-toast {
    padding: 10px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 16px;
}
.attendance-toast.success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.attendance-toast.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.attendance-scan-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}
.sportedia-floating-input {
    flex: 1;
    height: 40px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0 12px;
    font-size: 14px;
}
.sportedia-btn.primary {
    height: 40px;
    background: #111827;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 0 16px;
    font-weight: 600;
    cursor: pointer;
}
.attendance-user-footer {
    font-size: 12px;
    color: #6b7280;
    border-top: 1px solid #f3f4f6;
    padding-top: 12px;
}
</style>
