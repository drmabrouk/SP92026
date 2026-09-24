<?php

class SM_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public static function enforce_system_admin_protections() {
        // Safe protection placeholder: Never demote administrators or lock out users
    }

    public function prevent_system_admin_deletion($user_id) {
        $u = get_userdata($user_id);
        if ($u && in_array('administrator', (array)$u->roles) && count(get_users(array('role' => 'administrator'))) <= 1) {
            wp_die(' No  Delete      .');
        }
    }

    public function hide_admin_bar_for_non_admins($show) {
        if (current_user_can('manage_options') || current_user_can('sportedia_access')) {
            return $show;
        }
        return false;
    }

    public function custom_user_avatar($avatar, $id_or_email, $args = null) {
        $user_id = 0;
        if (is_numeric($id_or_email)) {
            $user_id = (int)$id_or_email;
        } elseif (is_object($id_or_email)) {
            if (isset($id_or_email->user_id)) {
                $user_id = (int)$id_or_email->user_id;
            } elseif (isset($id_or_email->ID)) {
                $user_id = (int)$id_or_email->ID;
            }
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if ($user) {
                $user_id = $user->ID;
            } else {
                $user = get_user_by('login', $id_or_email);
                if ($user) $user_id = $user->ID;
            }
        }

        if ($user_id) {
            $custom_avatar = get_user_meta($user_id, 'sm_profile_photo_url', true) ?: get_user_meta($user_id, 'eess_profile_photo', true);
            if (!empty($custom_avatar) && strpos($custom_avatar, 'data:') !== 0 && strpos($custom_avatar, '?v=') === false) {
                $custom_avatar = add_query_arg('v', time(), $custom_avatar);
            }
            if (empty($custom_avatar)) {
                $custom_avatar = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTMiIHN0eWxlPSJiYWNrZ3JvdW5kOiNmMWY1Zjk7IGJvcmRlci1yYWRpdXM6NTAlOyI+PHBhdGggZD0iTTEyIDEyYzIuMjEgMCA0LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg==";
            }

            $size = 96;
            $class_val = '';
            $style = '';

            if (is_array($args)) {
                $size = isset($args['size']) ? (int)$args['size'] : (isset($args['width']) ? (int)$args['width'] : 96);
                if (isset($args['class'])) {
                    $class_val = is_array($args['class']) ? implode(' ', $args['class']) : $args['class'];
                }
                if (isset($args['style'])) {
                    $style = $args['style'];
                }
            } elseif (is_numeric($args)) {
                $size = (int)$args;
            }

            $width = $size;
            $height = $size;

            $style_rules = array(
                'width' => $width . 'px !important',
                'height' => $height . 'px !important',
                'min-width' => $width . 'px !important',
                'min-height' => $height . 'px !important',
                'max-width' => $width . 'px !important',
                'max-height' => $height . 'px !important',
                'border-radius' => '50% !important',
                'object-fit' => 'cover !important',
                'display' => 'inline-block !important',
                'vertical-align' => 'middle !important',
                'margin' => '0 !important',
                'padding' => '0 !important',
                'box-sizing' => 'border-box !important'
            );

            $custom_styles = '';
            foreach ($style_rules as $prop => $val) {
                $custom_styles .= esc_attr($prop) . ': ' . $val . '; ';
            }
            if (!empty($style)) {
                $custom_styles .= $style;
            }

            $is_data_uri = (strpos($custom_avatar, 'data:') === 0);
            $avatar_src = $is_data_uri ? $custom_avatar : esc_url($custom_avatar);

            $avatar = sprintf(
                "<img src=\"%s\" class=\"%s\" style=\"%s\" width=\"%d\" height=\"%d\" />",
                $avatar_src,
                esc_attr($class_val),
                esc_attr($custom_styles),
                $width,
                $height
            );
        }
        return $avatar;
    }

    public function intercept_ajax_requests() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
            if (!empty($action) && (strpos($action, 'sm_') === 0 || strpos($action, 'eess_') === 0)) {
                if (!SM_Settings::is_ajax_action_allowed($action)) {
                    wp_send_json_error('  Unauthorized    (Access Restricted).');
                }
            }
        }
    }

    public function restrict_admin_access() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $status = get_user_meta($user->ID, 'sm_account_status', true);
            if ($status === 'restricted') {
                wp_logout();
                wp_redirect(home_url('/sm-login?login=failed'));
                exit;
            }
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-cairo', 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap', array(), null);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_script('html5-qrcode', SM_PLUGIN_URL . 'assets/js/html5-qrcode.min.js', array(), '2.3.8', true);
        wp_enqueue_style($this->plugin_name, SM_PLUGIN_URL . 'assets/css/sm-public.css', array('dashicons'), $this->version, 'all');

        $app = SM_Settings::get_appearance();
        $custom_css = "
            :root {
                /* System Design Tokens */
                --color-primary: {$app['primary_color']};
                --color-primary-hover: {$app['primary_hover']};
                --color-danger: {$app['danger_color']};
                --color-danger-hover: {$app['danger_hover']};
                --color-black: {$app['black_color']};
                --color-white: {$app['white_color']};

                --color-gray-50: {$app['gray_50']};
                --color-gray-100: {$app['gray_100']};
                --color-gray-200: {$app['gray_200']};
                --color-gray-300: {$app['gray_300']};
                --color-gray-400: {$app['gray_400']};
                --color-gray-500: {$app['gray_500']};
                --color-gray-600: {$app['gray_600']};
                --color-gray-700: {$app['gray_700']};
                --color-gray-800: {$app['gray_800']};
                --color-gray-900: {$app['gray_900']};

                --color-pastel-red-bg: {$app['pastel_red_bg']};
                --color-pastel-red-text: {$app['pastel_red_text']};
                --color-pastel-green-bg: {$app['pastel_green_bg']};
                --color-pastel-green-text: {$app['pastel_green_text']};
                --color-pastel-blue-bg: {$app['pastel_blue_bg']};
                --color-pastel-blue-text: {$app['pastel_blue_text']};
                --color-pastel-yellow-bg: {$app['pastel_yellow_bg']};
                --color-pastel-yellow-text: {$app['pastel_yellow_text']};
                --color-pastel-gray-bg: {$app['pastel_gray_bg']};
                --color-pastel-gray-text: {$app['pastel_gray_text']};

                --radius-button: {$app['button_radius']};
                --radius-card: {$app['card_radius']};
                --radius-field: {$app['field_radius']};
                --radius-modal: {$app['modal_radius']};

                /* Legacy Compatibility Variables */
                --sm-primary-color: {$app['primary_color']};
                --sm-secondary-color: {$app['gray_700']};
                --sm-accent-color: {$app['primary_color']};
                --sm-dark-color: {$app['gray_800']};
                --sm-radius: {$app['card_radius']};
            }
            .sm-content-wrapper, .sm-admin-dashboard, .sm-container,
            .sm-content-wrapper *:not(.dashicons), .sm-admin-dashboard *:not(.dashicons), .sm-container *:not(.dashicons) {
                font-family: 'Cairo', 'Noto Kufi Arabic', sans-serif !important;
            }
            .sm-admin-dashboard { font-size: calc({$app['font_size']} * 0.93); }

            /* SYSTEM-WIDE BUTTON RULE: ALL BUTTONS ARE FULLY ROUNDED PILLS */
            .sm-btn, button.sm-btn, a.sm-btn, input[type='submit'].sm-btn,
            .sm-btn-custom, .eess-hdr-btn, .pag-btn, .sm-tab-btn {
                border-radius: {$app['button_radius']} !important;
            }

            /* GLOBAL SAVE BUTTON RULE: BLACK & WHITE INVERTED INTERACTION */
            .sm-btn-save, button[name*='save'], button[name*='update'], button[type='submit']:not(.sm-btn-custom):not(.sm-btn-danger) {
                background-color: var(--color-black) !important;
                color: var(--color-white) !important;
                border: 1px solid var(--color-black) !important;
                border-radius: var(--radius-button) !important;
            }
            .sm-btn-save:hover, button[name*='save']:hover, button[name*='update']:hover {
                background-color: var(--color-white) !important;
                color: var(--color-black) !important;
                border: 1px solid var(--color-black) !important;
            }

            /* GLOBAL DELETE BUTTON RULE: DANGER RED */
            .sm-btn-danger, .sm-btn-delete, button[name*='delete'], button[onclick*='delete'], button[onclick*='Delete'] {
                background-color: var(--color-danger) !important;
                color: var(--color-white) !important;
                border: none !important;
                border-radius: var(--radius-button) !important;
            }
            .sm-btn-danger:hover, .sm-btn-delete:hover, button[name*='delete']:hover, button[onclick*='delete']:hover, button[onclick*='Delete']:hover {
                background-color: var(--color-danger-hover) !important;
            }

            /* GLOBAL UNIFIED TABLE HEADERS */
            .sm-table th, table th, thead tr th {
                background-color: var(--color-gray-800) !important;
                color: var(--color-white) !important;
            }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        if (isset($_GET['sm_action']) && $_GET['sm_action'] === 'logout') {
            wp_logout();
            wp_redirect(home_url('/sm-login'));
            exit;
        }

        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sm_class_attendance', array($this, 'shortcode_class_attendance'));
        add_shortcode('stu', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('card', array($this, 'shortcode_public_card_wizard'));

        // Sportedia Public & Player Portal Shortcodes
        add_shortcode('sportedia_dashboard', array($this, 'shortcode_sportedia_dashboard'));
        add_shortcode('sportedia_registration', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_renewal', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_players', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_player', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_attendance', array($this, 'shortcode_sportedia_attendance'));
        add_shortcode('sportedia_payments', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_invoice', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_reports', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sportedia_coach_dashboard', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sportedia_branch_dashboard', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('sportedia_player_portal', array($this, 'shortcode_public_card_wizard'));
        add_shortcode('sportedia_player_card', array($this, 'shortcode_public_card_wizard'));
    }

    public function eess_render_mobile_lesson_prep() {
        $all_subjects = SM_DB::get_subjects() ?: array();
        $unique_subjects = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$all_subjects)));
        $nonce = wp_create_nonce('sm_mobile_prep_nonce');
        $ajax_url = admin_url('admin-ajax.php');

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $is_supervisor = is_user_logged_in() && (
            in_array('administrator', $user_roles) ||
            in_array('sm_system_admin', $user_roles) ||
            in_array('sm_principal', $user_roles) ||
            in_array('sm_supervisor', $user_roles) ||
            in_array('sm_coordinator', $user_roles) ||
            in_array('sm_hod', $user_roles) ||
            in_array('sm_activities_supervisor', $user_roles) ||
            current_user_can('manage_options')
        );

        global $wpdb;
        $mobile_submissions = array();
        $mobile_term_plans   = array();
        $teacher_own_preps  = array();

        if (is_user_logged_in()) {
            $teacher_own_preps = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 30",
                $user->ID
            ));
        }

        if ($is_supervisor) {
            $mobile_submissions = $wpdb->get_results("SELECT p.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->users} u ON p.teacher_id = u.ID ORDER BY p.created_at DESC LIMIT 50");
            $mobile_term_plans   = $wpdb->get_results("SELECT tp.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_term_plans tp LEFT JOIN {$wpdb->users} u ON tp.teacher_id = u.ID ORDER BY tp.updated_at DESC LIMIT 50");
        }

        ob_start();
        ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <style>
            .eess-mobile-prep-app input, .eess-mobile-prep-app textarea, .eess-mobile-prep-app select {
                font-family: 'Cairo', sans-serif !important;
            }
        </style>
        <div class="eess-mobile-prep-app" style="max-width: 500px; margin: 0 auto; background: #ffffff; min-height: 100vh; font-family: 'Cairo', sans-serif; direction: rtl; padding: 15px; box-sizing: border-box; color: #1e293b;">

            <!-- Centered Floating Confirmation Notification Toast -->
            <div id="m-floating-toast" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 99999; background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(8px); color: #ffffff; padding: 18px 24px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); text-align: center; max-width: 340px; width: 88%; border: 1px solid rgba(255, 255, 255, 0.15);">
                <div style="width: 44px; height: 44px; background: #16a34a; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; margin-bottom: 10px;">
                    <span class="dashicons dashicons-yes" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
                <div id="m-floating-toast-msg" style="font-weight: 800; font-size: 13.5px; line-height: 1.5; color: #ffffff;"> Send Save  </div>
            </div>

            <script>
            function eessShowMobileToast(message, duration) {
                const toast = document.getElementById('m-floating-toast');
                const msgBox = document.getElementById('m-floating-toast-msg');
                if (toast && msgBox) {
                    msgBox.innerText = message || ' Send Save  ';
                    toast.style.display = 'block';
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, duration || 3000);
                }
            }
            </script>

            <?php if (is_user_logged_in()):
                $m_school_info = SM_Settings::get_school_info();
                $m_sys_logo = !empty($m_school_info['school_logo']) ? $m_school_info['school_logo'] : (!empty($m_school_info['logo_url']) ? $m_school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
                $m_inst_name = get_user_meta($user->ID, 'eess_school_name', true) ?: ($m_school_info['name'] ?? ' EESS ');

                $role_labels = array(
                    'administrator' => '  ',
                    'sm_system_admin' => '  ',
                    'sm_principal' => ' Academy ',
                    'sm_supervisor' => ' ',
                    'sm_coordinator' => ' ',
                    'sm_teacher' => '',
                    'sm_discipline_supervisor' => '  / ',
                    'sm_activities_supervisor' => ' Active',
                    'sm_clinic' => ' ',
                    'sm_hr' => '  (HR)'
                );
                $primary_role_key = reset($user_roles) ?: 'sm_teacher';
                $m_role_display = $role_labels[$primary_role_key] ?? '';
                $m_dept_display = get_user_meta($user->ID, 'eess_department', true) ?: (get_user_meta($user->ID, 'department', true) ?: '   ');
            ?>
            <!-- Solid Black Mobile Header Banner (Sticky Fixed Top) -->
            <div style="position: sticky; top: 0; z-index: 999999; background: #000000; color: #ffffff; padding: 12px 16px; border-radius: 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 14px rgba(0,0,0,0.25); width: 100%; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                    <div style="width: 32px; height: 32px; border-radius: 6px; background: #ffffff; padding: 2px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.2);">
                        <img src="<?php echo esc_url($m_sys_logo); ?>" style="width: 100%; height: 100%; object-fit: contain; border-radius: 4px;" alt="Logo">
                    </div>
                    <div style="min-width: 0; flex: 1; text-align: right;">
                        <div style="font-size: 13.5px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25;"><?php echo esc_html($m_inst_name); ?></div>
                        <div style="font-size: 10.5px; color: #cbd5e1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px;"><?php echo esc_html($m_role_display . ' · ' . $m_dept_display); ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                    <button type="button" onclick="window.location.reload();" title="Update " style="width: 34px !important; min-width: 34px !important; max-width: 34px !important; height: 34px !important; border-radius: 9999px !important; padding: 0 !important; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255,255,255,0.25); display: inline-flex !important; align-items: center !important; justify-content: center !important; cursor: pointer; flex-shrink: 0; box-sizing: border-box;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: block; margin: 0 auto;">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                    </button>
                    <a href="<?php echo wp_logout_url(home_url('/sm-login')); ?>" title="Logout" style="width: 34px !important; min-width: 34px !important; max-width: 34px !important; height: 34px !important; border-radius: 9999px !important; padding: 0 !important; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255,255,255,0.25); display: inline-flex !important; align-items: center !important; justify-content: center !important; text-decoration: none; flex-shrink: 0; box-sizing: border-box;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display: block; margin: 0 auto;">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <!-- DEDICATED 4-BUTTON MOBILE MAIN DASHBOARD -->
            <?php if (is_user_logged_in()): ?>
            <!-- Clean Centered Welcome Area with Dynamic Role & Subject Capsules and Interactive Profile Photo -->
            <?php
            $role_map = array(
                'administrator' => '  ',
                'sm_system_admin' => '  ',
                'sm_principal' => ' Academy ',
                'sm_supervisor' => ' ',
                'sm_coordinator' => ' ',
                'sm_teacher' => '',
                'sm_discipline_supervisor' => ' ',
                'sm_activities_supervisor' => ' Active'
            );
            $primary_role = reset($user_roles) ?: 'sm_teacher';
            $m_role_label = $role_map[$primary_role] ?? '';
            $m_user_subject = get_user_meta($user->ID, 'sm_specialization', true) ?: (get_user_meta($user->ID, 'specialization', true) ?: (get_user_meta($user->ID, 'eess_department', true) ?: '  '));
            $m_custom_avatar = get_user_meta($user->ID, 'sm_profile_photo_url', true) ?: get_user_meta($user->ID, 'eess_profile_photo', true);
            $has_no_photo = empty($m_custom_avatar);
            $m_avatar_src = $m_custom_avatar ?: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTMiIHN0eWxlPSJiYWNrZ3JvdW5kOiNmMWY1Zjk7IGJvcmRlci1yYWRpdXM6NTAlOyI+PHBhdGggZD0iTTEyIDEyYzIuMjEgMCA4LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg==";
            ?>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 16px; margin-bottom: 18px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02); position: relative; overflow: hidden;">
                <!-- Elegant Multi-Tone Gray Gradient Wavy Watermark Background -->
                <svg style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0.06; pointer-events: none; z-index: 0;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="eessWatermarkGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0f172a" stop-opacity="0.8"/>
                            <stop offset="50%" stop-color="#475569" stop-opacity="0.5"/>
                            <stop offset="100%" stop-color="#94a3b8" stop-opacity="0.2"/>
                        </linearGradient>
                    </defs>
                    <path fill="url(#eessWatermarkGrad)" d="M0,192L48,176C96,160,192,144,288,160C384,176,480,224,576,218.7C672,213,768,155,864,138.7C960,122,1056,149,1152,165.3C1248,182,1344,187,1392,184L1440,180L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    <path fill="url(#eessWatermarkGrad)" d="M0,64L48,90.7C96,117,192,171,288,181.3C384,192,480,160,576,133.3C672,107,768,85,864,101.3C960,117,1056,171,1152,186.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
                <!-- Interactive Profile Avatar Click to Change -->
                <div onclick="document.getElementById('m_profile_photo_file').click()" style="position: relative; width: 68px; height: 68px; margin: 0 auto 8px auto; cursor: pointer;" title="   ">
                    <img id="m_header_avatar_img" src="<?php echo esc_url($m_avatar_src); ?>" style="width: 68px; height: 68px; border-radius: 50%; object-fit: cover; border: 2.5px solid #cbd5e1; background: #f1f5f9; display: block;" alt="Profile Avatar">
                    <div style="position: absolute; bottom: 0; left: 0; background: #0f172a; color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1.5px solid #ffffff;">
                        <span class="dashicons dashicons-camera" style="font-size: 12px; width: 12px; height: 12px;"></span>
                    </div>
                </div>
                <input type="file" id="m_profile_photo_file" accept="image/*" style="display: none;" onchange="eessUploadMobileAvatar(this)">

                <?php if ($has_no_photo): ?>
                <!-- 10-Second Guidance Pastel Capsule -->
                <div id="m_photo_guidance_capsule" style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 9999px; display: inline-block; margin-bottom: 8px; transition: opacity 0.5s ease;">

                </div>
                <script>
                setTimeout(function() {
                    var cap = document.getElementById('m_photo_guidance_capsule');
                    if (cap) {
                        cap.style.opacity = '0';
                        setTimeout(function() { cap.style.display = 'none'; }, 500);
                    }
                }, 15000);
                </script>
                <?php endif; ?>

                <h2 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 800; color: #0f172a;">Welcome . <?php echo esc_html($user->display_name); ?></h2>
                <!-- Dynamic Role & Subject Capsules (Values Only, Without Prefixes or Icons) -->
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <span style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 9999px;">
                        <?php echo esc_html($m_role_label); ?>
                    </span>
                    <span style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 800; padding: 3px 12px; border-radius: 9999px;">
                        <?php echo esc_html($m_user_subject); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $can_record_violation = is_user_logged_in() && (
                in_array('administrator', $user_roles) ||
                in_array('sm_system_admin', $user_roles) ||
                in_array('sm_principal', $user_roles) ||
                in_array('sm_supervisor', $user_roles) ||
                in_array('sm_discipline_supervisor', $user_roles)
            );
            $can_access_inquiry = is_user_logged_in() && (
                $can_record_violation ||
                in_array('sm_teacher', $user_roles) ||
                in_array('sm_coordinator', $user_roles) ||
                in_array('sm_hod', $user_roles) ||
                in_array('sm_activities_supervisor', $user_roles) ||
                in_array('sm_clinic', $user_roles) ||
                in_array('sm_hr', $user_roles)
            );
            if ($can_access_inquiry):
            ?>
            <!-- MOBILE DASHBOARD PRIMARY ACTION BOXES -->
            <div id="m-admin-dashboard" style="margin-bottom: 20px;">

                <!-- Action Boxes Container (Dynamic Grid) -->
                <div style="display: grid; grid-template-columns: <?php echo $can_record_violation ? '1fr 1fr' : '1fr 1fr'; ?>; gap: 12px; margin-bottom: 16px;">
                    <!-- BOX 1: STUDENT INQUIRY (NAME / CODE / BARCODE CAMERA SCAN) -->
                    <button type="button" onclick="eessOpenAdminMobileBox('student_info')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #2563eb; margin-bottom: 10px;">
                            <span class="dashicons dashicons-search" style="font-size: 24px; width: 24px; height: 24px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13.5px; color: #0f172a; margin-bottom: 4px;">No  No</span>
                        <span style="font-size: 10.5px; color: #64748b;"> /  /  </span>
                    </button>

                    <!-- BOX 2: BARCODE ATTENDANCE (FAST CAMERA SCANNING) -->
                    <button type="button" onclick="eessOpenAdminMobileBox('barcode_attendance')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #f0fdf4; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #16a34a; margin-bottom: 10px;">
                            <span class="dashicons dashicons-clock" style="font-size: 24px; width: 24px; height: 24px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13.5px; color: #0f172a; margin-bottom: 4px;">  </span>
                        <span style="font-size: 10.5px; color: #64748b;">   </span>
                    </button>

                    <?php if ($can_record_violation): ?>
                    <!-- BOX 3: RECORD VIOLATION (AUTHORIZED ADMIN/SUPERVISOR ROLES ONLY) -->
                    <button type="button" onclick="eessOpenAdminMobileBox('record_violation')" style="grid-column: span 2; background: #ffffff; border: 1.5px solid #fecdd3; border-radius: 16px; padding: 16px 12px; display: flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 38px; height: 38px; background: #fef2f2; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; flex-shrink: 0;">
                            <span class="dashicons dashicons-warning" style="font-size: 20px; width: 20px; height: 20px;"></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; font-size: 13.5px; color: #991b1b; display: block;">  </span>
                            <span style="font-size: 10.5px; color: #64748b;">  Academy    </span>
                        </div>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- BOX 1 CONTAINER: STUDENT INQUIRY (NAME / CODE / BARCODE CAMERA SCAN) -->
                <div id="m-box-student-info" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #1e40af;">NoNo   No</h4>
                        <button type="button" onclick="eessCloseAdminMobileBox()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ Close</button>
                    </div>

                    <!-- Method 1: Camera Barcode Scan -->
                    <div style="margin-bottom: 14px;">
                        <button type="button" onclick="eessStartMobileInfoCamera()" style="width: 100%; height: 42px; background: #1e40af; color: white !important; border: none; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 6px rgba(30,64,175,0.2);">
                            <span class="dashicons dashicons-camera" style="font-size: 18px; width: 18px; height: 18px;"></span>
                            <span>   No  </span>
                        </button>
                        <div id="m-info-camera-reader" style="display: none; margin-top: 10px; border-radius: 12px; overflow: hidden; border: 2px solid #2563eb;"></div>
                    </div>

                    <!-- Methods 2 & 3: Search by Name or Code -->
                    <div style="margin-bottom: 14px; position: relative;">
                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Search Player Name  No  National ID:</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="m_info_search_input" onkeyup="eessMobileInquiryLiveSearch()" placeholder=" Player Name  No  ..." style="flex: 1; height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; box-sizing: border-box;">
                            <button type="button" onclick="eessSearchStudentInfoByCode()" style="height: 42px; padding: 0 18px; background: #0f172a; color: white !important; border: none; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer;">Search / Confirm</button>
                        </div>
                        <div id="m_info_name_results" style="display: none; position: absolute; top: 100%; right: 0; left: 0; z-index: 9999; background: white; border: 1px solid #cbd5e1; border-radius: 10px; max-height: 200px; overflow-y: auto; box-shadow: 0 10px 20px rgba(0,0,0,0.15);"></div>
                    </div>

                    <!-- Comprehensive Mobile Profile Result Container -->
                    <div id="m-student-info-result" style="display: none; margin-top: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px;"></div>
                </div>

                <!-- BOX 2 CONTAINER: MOBILE BARCODE ATTENDANCE (HIGH-SPEED SCANNING) -->
                <div id="m-box-barcode-attendance" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #15803d;">    </h4>
                        <button type="button" onclick="eessCloseAdminMobileBox()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ Close</button>
                    </div>

                    <!-- Dynamic Grade & Section Assignment Selector -->
                    <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 800; color: #334155; display: block; margin-bottom: 3px;">Training Group :</label>
                                <select id="m_att_grade_select" onchange="eessUpdateMobileAttendanceSections()" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 12px; padding: 0 8px; font-family: 'Cairo';">
                                    <option value="">--  Training Group --</option>
                                    <?php
                                    $m_db_sections = SM_Settings::get_sections_from_db();
                                    foreach ($m_db_sections as $m_g_num => $m_secs): ?>
                                        <option value="Training Group <?php echo $m_g_num; ?>" data-gnum="<?php echo $m_g_num; ?>">Training Group <?php echo $m_g_num; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 800; color: #334155; display: block; margin-bottom: 3px;">Training Group / Training Group:</label>
                                <select id="m_att_section_select" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 12px; padding: 0 8px; font-family: 'Cairo';" disabled>
                                    <option value="">--  Training Group --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Camera Scanner Toggle Button -->
                        <button type="button" id="m_att_cam_toggle_btn" onclick="eessToggleMobileAttendanceCamera()" style="width: 100%; height: 42px; background: #16a34a; color: white !important; border: none; border-radius: 10px; font-weight: 800; font-size: 12.5px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 6px rgba(22,163,74,0.2);">
                            <span class="dashicons dashicons-camera" style="font-size: 18px; width: 18px; height: 18px;"></span>
                            <span>     </span>
                        </button>
                    </div>

                    <!-- Camera Viewfinder Box -->
                    <div id="m-att-camera-reader" style="display: none; border-radius: 12px; overflow: hidden; border: 2px solid #16a34a; margin-bottom: 14px; position: relative;"></div>

                    <!-- Rapid Scan Session Summary Pill -->
                    <div id="m-att-scan-summary" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; border-radius: 10px; padding: 8px 12px; font-size: 11.5px; font-weight: 800; text-align: center; margin-bottom: 10px;">
                            <span id="m_att_scan_count">0</span> No
                    </div>
                </div>

                <!-- BOX 2 CONTAINER: VIOLATION RECORDING -->
                <div id="m-box-record-violation" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #991b1b;">    </h4>
                        <button type="button" onclick="eessCloseAdminMobileBox()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ Close</button>
                    </div>

                    <!-- Unified Same-Row Search & Camera Barcode Scanner (Upload Barcode Functionality Completely Removed) -->
                    <div style="margin-bottom: 14px; position: relative;">
                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Search  No    :</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="text" id="m_viol_unified_input" onkeyup="eessMobileSearchStudentUnified()" placeholder="Player Name  No  ..." style="flex: 1; height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; box-sizing: border-box;">
                            <button type="button" onclick="eessStartMobileViolCamera()" style="height: 42px; padding: 0 14px; background: #dc2626; color: white !important; border: none; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; flex-shrink: 0;" title="   No ">
                                <span class="dashicons dashicons-camera" style="font-size: 18px; width: 18px; height: 18px; margin: 0;"></span>
                                <span> </span>
                            </button>
                            <button type="button" onclick="eessMobileConfirmSearchStudentByCode()" style="height: 42px; padding: 0 14px; background: #0f172a; color: white !important; border: none; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer; flex-shrink: 0;">Confirm</button>
                        </div>
                        <div id="m-viol-camera-reader" style="display: none; margin-top: 10px; border-radius: 12px; overflow: hidden; border: 2px solid #dc2626;"></div>
                        <div id="m_viol_name_results" style="display: none; position: absolute; top: 100%; right: 0; left: 0; z-index: 9999; background: white; border: 1px solid #cbd5e1; border-radius: 10px; max-height: 180px; overflow-y: auto; box-shadow: 0 10px 20px rgba(0,0,0,0.15);"></div>
                    </div>

                    <!-- Selected Students Capsules Container (Multi-Student Continuous Barcode Accumulation) -->
                    <div id="m-selected-student-box" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px; margin-bottom: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #15803d; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span> No   :</span>
                            <span id="m_sel_stu_count" style="background: #15803d; color: white; padding: 2px 8px; border-radius: 9999px; font-size: 10.5px;">0</span>
                        </div>
                        <div id="m_sel_stu_capsules_list" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
                    </div>

                    <!-- Violation Details Form -->
                    <form id="eess_mobile_violation_form" onsubmit="eessSubmitMobileViolation(event)" style="display: none;">
                        <input type="hidden" id="m_viol_student_id" name="student_ids">
                        <input type="hidden" name="sm_nonce" value="<?php echo wp_create_nonce('sm_record_action'); ?>">

                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;"> /    <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="type" required placeholder=":    /   ..." style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; box-sizing: border-box;">
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">  <span style="color:#ef4444;">*</span></label>
                            <select name="degree" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; box-sizing: border-box;">
                                <option value="1">  ( )</option>
                                <option value="2">  ( Intermediate)</option>
                                <option value="3">  ( )</option>
                                <option value="4">  ( )</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;"> Notes </label>
                            <textarea name="details" rows="3" placeholder="    ..." style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 12px; box-sizing: border-box; resize: vertical;"></textarea>
                        </div>

                        <button type="submit" id="m_viol_submit_btn" style="width: 100%; height: 44px; background: #dc2626; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer;">
                            Save
                        </button>
                    </form>
                </div>

                <?php
                $is_principal = in_array('sm_principal', $user_roles) || in_array('administrator', $user_roles);
                $is_act_supervisor = in_array('sm_activities_supervisor', $user_roles);

                if ($is_principal || $is_act_supervisor):
                    $user_school_id = get_user_meta($user->ID, 'eess_school_id', true) ?: get_user_meta($user->ID, 'sm_school_id', true);

                    // Filter teachers based on scope
                    if ($is_act_supervisor) {
                        // Scope to Physical & Health Education teachers only
                        $all_school_teachers = get_users(array(
                            'role' => 'sm_teacher',
                            'number' => 100
                        ));
                        $scoped_teachers = array();
                        foreach ($all_school_teachers as $st) {
                            $spec = get_user_meta($st->ID, 'sm_specialization', true) ?: (get_user_meta($st->ID, 'specialization', true) ?: get_user_meta($st->ID, 'eess_department', true));
                            if (strpos($spec, '') !== false || strpos($spec, '') !== false || strpos($spec, 'Active') !== false) {
                                $scoped_teachers[] = $st;
                            }
                        }
                        $all_school_teachers = $scoped_teachers;
                    } else {
                        // Principal scope
                        $all_school_teachers = get_users(array(
                            'role' => 'sm_teacher',
                            'meta_key' => 'eess_school_id',
                            'meta_value' => $user_school_id
                        ));
                        if (empty($all_school_teachers)) {
                            $all_school_teachers = get_users(array('role' => 'sm_teacher', 'number' => 50));
                        }
                    }

                    $teacher_count = count($all_school_teachers);

                    // Submitted lesson preps synchronized with desktop module calculation logic
                    global $wpdb;
                    $school_teacher_ids = array_map(function($t) { return $t->ID; }, $all_school_teachers);

                    if (!empty($school_teacher_ids)) {
                        $placeholders = implode(',', array_fill(0, count($school_teacher_ids), '%d'));
                        $submitted_teacher_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT teacher_id FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id IN ($placeholders) AND status IN ('submitted', 'approved', 'resubmitted', 'late')", ...$school_teacher_ids));
                        $m_late_count = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id IN ($placeholders) AND status = 'late'", ...$school_teacher_ids)));
                        $m_approved_count = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id IN ($placeholders) AND status = 'approved'", ...$school_teacher_ids)));
                    } else {
                        $submitted_teacher_ids = array();
                        $m_late_count = 0;
                        $m_approved_count = 0;
                    }

                    $submitted_teachers = array();
                    $pending_teachers = array();

                    foreach ($all_school_teachers as $st) {
                        if (in_array($st->ID, $submitted_teacher_ids)) {
                            $submitted_teachers[] = $st->display_name;
                        } else {
                            $pending_teachers[] = $st->display_name;
                        }
                    }

                    $m_total_submitted = count($submitted_teachers);
                    $m_missing_count   = max(0, $teacher_count - $m_total_submitted);
                    $m_compliance_rate = $teacher_count > 0 ? round(($m_total_submitted / $teacher_count) * 100) : 0;
                ?>
                <!-- Administrative Statistics Panel -->
                <div style="background: #ffffff; border-radius: 16px; padding: 16px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 900; color: #16a34a;"><?php echo $m_total_submitted; ?> / <?php echo $teacher_count; ?></div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #15803d; margin-top: 2px;">  (<?php echo $m_compliance_rate; ?>%)</div>
                        </div>
                        <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 10px; text-align: center;">
                            <div style="font-size: 18px; font-weight: 900; color: #dc2626;"><?php echo $m_missing_count; ?></div>
                            <div style="font-size: 10.5px; font-weight: 700; color: #991b1b; margin-top: 2px;">  </div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                        <div style="background: #fefce8; border: 1px solid #fef08a; border-radius: 10px; padding: 8px; text-align: center;">
                            <div style="font-size: 15px; font-weight: 900; color: #ca8a04;"><?php echo $m_late_count; ?></div>
                            <div style="font-size: 10px; font-weight: 700; color: #a16207; margin-top: 1px;"> </div>
                        </div>
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 8px; text-align: center;">
                            <div style="font-size: 15px; font-weight: 900; color: #2563eb;"><?php echo $m_approved_count; ?></div>
                            <div style="font-size: 10px; font-weight: 700; color: #1d4ed8; margin-top: 1px;"> </div>
                        </div>
                    </div>
                    <?php if (!empty($pending_teachers)): ?>
                        <div style="background: #f8fafc; border-radius: 8px; padding: 10px; border: 1px solid #e2e8f0; font-size: 11px; color: #475569;">
                            <strong style="color: #991b1b; display: block; margin-bottom: 4px;">     :</strong>
                            <?php echo esc_html(implode('  ', array_slice($pending_teachers, 0, 10))); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <script>
            function eessOpenAdminMobileBox(boxKey) {
                var infoBox = document.getElementById('m-box-student-info');
                var attBox  = document.getElementById('m-box-barcode-attendance');
                var violBox = document.getElementById('m-box-record-violation');

                if (infoBox) infoBox.style.display = (boxKey === 'student_info') ? 'block' : 'none';
                if (attBox)  attBox.style.display  = (boxKey === 'barcode_attendance') ? 'block' : 'none';
                if (violBox) violBox.style.display = (boxKey === 'record_violation') ? 'block' : 'none';

                var targetId = (boxKey === 'student_info') ? 'm-box-student-info' : ((boxKey === 'barcode_attendance') ? 'm-box-barcode-attendance' : 'm-box-record-violation');
                var target = document.getElementById(targetId);
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            }

            function eessCloseAdminMobileBox() {
                var infoBox = document.getElementById('m-box-student-info');
                var attBox  = document.getElementById('m-box-barcode-attendance');
                var violBox = document.getElementById('m-box-record-violation');

                if (infoBox) infoBox.style.display = 'none';
                if (attBox)  attBox.style.display  = 'none';
                if (violBox) violBox.style.display = 'none';

                if (mAttScannerInstance) {
                    mAttScannerInstance.stop().catch(function(){}).then(function(){ mAttScannerInstance = null; });
                }
            }

            function eessUpdateMobileAttendanceSections() {
                var gradeSelect = document.getElementById('m_att_grade_select');
                var sectionSelect = document.getElementById('m_att_section_select');
                if (!gradeSelect || !sectionSelect) return;

                var opt = gradeSelect.options[gradeSelect.selectedIndex];
                var gNum = opt ? opt.getAttribute('data-gnum') : null;

                sectionSelect.innerHTML = '<option value="">--  Training Group --</option>';
                if (!gNum) {
                    sectionSelect.disabled = true;
                    return;
                }

                var dbSections = <?php echo json_encode(SM_Settings::get_sections_from_db()); ?>;
                var secs = dbSections[gNum] || ['', '', '', ''];
                secs.forEach(function(s) {
                    var o = document.createElement('option');
                    o.value = s;
                    o.innerText = ' ' + s;
                    sectionSelect.appendChild(o);
                });
                sectionSelect.disabled = false;
            }

            let mAttScannerInstance = null;
            let mAttScannedCount = 0;
            let mAttLastScannedCode = '';
            let mAttLastScanTime = 0;

            function eessToggleMobileAttendanceCamera() {
                var reader = document.getElementById('m-att-camera-reader');
                var btn = document.getElementById('m_att_cam_toggle_btn');
                if (!reader) return;

                if (mAttScannerInstance) {
                    mAttScannerInstance.stop().then(function() {
                        mAttScannerInstance = null;
                        reader.style.display = 'none';
                        if (btn) btn.innerText = '     ';
                    }).catch(function() {
                        mAttScannerInstance = null;
                        reader.style.display = 'none';
                    });
                    return;
                }

                reader.style.display = 'block';
                if (btn) btn.innerText = '  ';

                function startAttCam() {
                    if (typeof Html5Qrcode !== 'undefined') {
                        if (!mAttScannerInstance) {
                            mAttScannerInstance = new Html5Qrcode("m-att-camera-reader");
                        }
                        var formats = (typeof Html5QrcodeSupportedFormats !== 'undefined') ? [
                            Html5QrcodeSupportedFormats.CODE_128,
                            Html5QrcodeSupportedFormats.CODE_39,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.QR_CODE
                        ] : undefined;

                        var config = { fps: 20, qrbox: 250, formatsToSupport: formats };

                        var onScan = function(decodedText) {
                            var code = decodedText.trim();
                            var now = Date.now();
                            if (code && (code !== mAttLastScannedCode || now - mAttLastScanTime > 1500)) {
                                mAttLastScannedCode = code;
                                mAttLastScanTime = now;
                                eessProcessMobileBarcodeAttendance(code);
                            }
                        };

                        mAttScannerInstance.start({ facingMode: "environment" }, config, onScan).catch(function(err) {
                            if (typeof Html5Qrcode.getCameras === 'function') {
                                Html5Qrcode.getCameras().then(function(cams) {
                                    if (cams && cams.length > 0) {
                                        mAttScannerInstance.start(cams[cams.length - 1].id, config, onScan).catch(function(e) {
                                            eessShowMobileToast('  : ' + e, 'error');
                                            reader.style.display = 'none';
                                            if (btn) btn.innerText = '     ';
                                        });
                                    } else {
                                        eessShowMobileToast('      .', 'error');
                                        reader.style.display = 'none';
                                    }
                                }).catch(function(e) {
                                    eessShowMobileToast('  : ' + err, 'error');
                                    reader.style.display = 'none';
                                });
                            } else {
                                eessShowMobileToast('  : ' + err, 'error');
                                reader.style.display = 'none';
                            }
                        });
                    } else {
                        eessShowMobileToast('   ...', 'info');
                        setTimeout(startAttCam, 400);
                    }
                }

                startAttCam();
            }

            function eessProcessMobileBarcodeAttendance(barcode) {
                var date = new Date().toISOString().split('T')[0];
                var formData = new FormData();
                formData.append('action', 'sm_save_attendance_ajax');
                formData.append('student_barcode', barcode);
                formData.append('status', 'present');
                formData.append('date', date);
                formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        var stuName = (res.data && res.data.student_name) ? res.data.student_name : 'No';
                        if (res.data && res.data.already_recorded) {
                            eessShowMobileToast('⚠️     ' + stuName, 900);
                        } else {
                            mAttScannedCount++;
                            var countEl = document.getElementById('m_att_scan_count');
                            var sumBox  = document.getElementById('m-att-scan-summary');
                            if (countEl) countEl.innerText = mAttScannedCount;
                            if (sumBox)  sumBox.style.display = 'block';

                            eessShowMobileToast('✅   : ' + stuName, 900);
                        }
                    } else {
                        eessShowMobileToast('❌ ' + (res.data || '  '), 900);
                    }
                })
                .catch(function() {
                    eessShowMobileToast('❌   No ', 900);
                });
            }

            let mInquiryTimer = null;
            function eessMobileInquiryLiveSearch() {
                var val = document.getElementById('m_info_search_input').value.trim();
                var resultsBox = document.getElementById('m_info_name_results');

                if (mInquiryTimer) clearTimeout(mInquiryTimer);
                if (val.length < 2) {
                    resultsBox.style.display = 'none';
                    resultsBox.innerHTML = '';
                    return;
                }

                mInquiryTimer = setTimeout(function() {
                    jQuery.post('<?php echo $ajax_url; ?>', {
                        action: 'sm_search_students',
                        query: val
                    }, function(res) {
                        if (res.success && res.data && res.data.length > 0) {
                            var html = '';
                            res.data.forEach(function(s) {
                                var codeVal = s.student_code || s.national_id || s.id;
                                html += '<div onclick="eessSelectMobileInquiryStudent(\'' + codeVal + '\', \'' + s.name.replace(/'/g, "\\'") + '\')" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: right;">' +
                                        '<div style="font-weight: 800; font-size: 13px; color: #0f172a;">' + s.name + '</div>' +
                                        '<div style="font-size: 11px; color: #64748b;">Training Group: ' + (s.class_name || ' ') + ' (' + (s.section || '') + ') · : ' + codeVal + '</div>' +
                                        '</div>';
                            });
                            resultsBox.innerHTML = html;
                            resultsBox.style.display = 'block';
                        } else {
                            resultsBox.innerHTML = '<div style="padding: 10px; font-size: 11.5px; color: #94a3b8; text-align: center;">    No .</div>';
                            resultsBox.style.display = 'block';
                        }
                    });
                }, 250);
            }

            function eessSelectMobileInquiryStudent(codeVal, nameVal) {
                document.getElementById('m_info_search_input').value = nameVal;
                document.getElementById('m_info_name_results').style.display = 'none';
                eessSearchStudentInfoByCode(codeVal);
            }

            function eessSearchStudentInfoByCode(overrideCode) {
                var code = overrideCode || document.getElementById('m_info_search_input').value.trim();
                if (!code) return;

                var resBox = document.getElementById('m-student-info-result');
                resBox.style.display = 'block';
                resBox.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b; font-weight:700;">     No... ⏳</div>';

                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_get_student',
                    code: code
                }, function(res) {
                    if (res.success && res.data) {
                        var st = res.data;
                        // Fetch Intelligence & Timeline
                        jQuery.post('<?php echo $ajax_url; ?>', {
                            action: 'sm_get_student_intelligence',
                            student_id: st.id
                        }, function(intelRes) {
                            var intel = (intelRes.success && intelRes.data) ? intelRes.data : {};
                            var timelineHtml = '';

                            if (intel.timeline && intel.timeline.length > 0) {
                                intel.timeline.forEach(function(t) {
                                    var icon = (t.module === 'clinic') ? '' : ((t.module === 'attendance') ? '⏰' : '⚠️');
                                    timelineHtml += '<div style="position: relative; padding-right: 20px; border-right: 2px solid #cbd5e1; margin-bottom: 12px;">' +
                                                    '<div style="position: absolute; right: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #2563eb;"></div>' +
                                                    '<div style="font-weight: 800; font-size: 12px; color: #0f172a;">' + icon + ' ' + t.title + '</div>' +
                                                    '<div style="font-size: 10.5px; color: #64748b; margin: 2px 0;">' + t.date + '</div>' +
                                                    '<div style="font-size: 11px; color: #334155;">' + t.details + '</div>' +
                                                    '</div>';
                                });
                            } else {
                                timelineHtml = '<div style="font-size: 11.5px; color: #94a3b8; text-align: center; padding: 10px;">No          .</div>';
                            }

                            // Medical Alerts Box
                            var healthHtml = '';
                            if (st.health_status || st.allergies || st.special_needs) {
                                healthHtml = '<div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 12px; margin-bottom: 14px;">' +
                                             '<div style="font-weight: 800; font-size: 12.5px; color: #991b1b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">   No </div>' +
                                             '<div style="font-size: 11.5px; color: #7f1d1d; line-height: 1.5;">' +
                                             '<strong>Status :</strong> ' + (st.health_status || '') + '<br>' +
                                             '<strong>Allergies :</strong> ' + (st.allergies || 'No  Allergies ') + '<br>' +
                                             '<strong> :</strong> ' + (st.special_needs ? 'Yes' : 'No') +
                                             '</div></div>';
                            }

                            var avatarSrc = st.photo_url || intel.photo_url || '';
                            var avatarHtml = avatarSrc ? '<img src="' + avatarSrc + '" style="width: 60px; height: 68px; border-radius: 12px; object-fit: cover; border: 2px solid #2563eb; flex-shrink: 0;">' :
                                                         '<div style="width: 60px; height: 68px; border-radius: 12px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #64748b; flex-shrink: 0;"></div>';

                            // Normalize Guardian WhatsApp Number
                            var rawPhone = (st.guardian_phone || '').replace(/[^0-9+]/g, '');
                            var waPhone = '';
                            if (rawPhone) {
                                if (rawPhone.indexOf('+') === 0) {
                                    waPhone = rawPhone.replace('+', '');
                                } else if (rawPhone.indexOf('00') === 0) {
                                    waPhone = rawPhone.substring(2);
                                } else if (rawPhone.indexOf('0') === 0) {
                                    waPhone = '971' + rawPhone.substring(1);
                                } else {
                                    waPhone = '971' + rawPhone;
                                }
                            }

                            var whatsappBtn = waPhone ? '<a href="https://wa.me/' + waPhone + '" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; background: #25d366; color: white !important; font-size: 10.5px; font-weight: 800; padding: 2px 10px; border-radius: 9999px; text-decoration: none; margin-right: 6px;" title="  ">' +
                                                        '<span></span></a>' : '';

                            resBox.innerHTML = '<div style="display: flex; gap: 14px; align-items: center; background: #ffffff; padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 14px;">' +
                                               avatarHtml +
                                               '<div>' +
                                               '<h3 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: #0f172a;">' + st.name + '</h3>' +
                                               '<div style="font-size: 11.5px; color: #475569; font-weight: 700;">' + (st.class_name || '') + ' (' + (st.section || '') + ') · : ' + (st.student_code || st.id) + '</div>' +
                                               '<div style="font-size: 11px; color: #64748b; margin-top: 2px;">: ' + (st.national_id || ' ') + '</div>' +
                                               '</div></div>' +

                                               healthHtml +

                                               '<!-- Basic & Guardian Info Card -->' +
                                               '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin-bottom: 14px; font-size: 12px; color: #334155; line-height: 1.6;">' +
                                               '<div style="font-weight: 800; font-size: 12.5px; color: #0f172a; margin-bottom: 6px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px;">   </div>' +
                                               '<strong>Organization  / Academy :</strong> ' + (st.institution_name || 'Academy  Home') + '<br>' +
                                               '<strong> Mother:</strong> ' + (st.guardian_name || ' ') + ' (' + (st.guardian_relationship || '') + ')<br>' +
                                               '<strong> :</strong> ' + (st.guardian_phone || ' ') + whatsappBtn + '<br>' +
                                               '<strong>Email Address:</strong> ' + (st.parent_email || ' ') +
                                               '</div>' +

                                               '<!-- Chronological Activity Timeline -->' +
                                               '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 12px; color: #334155;">' +
                                               '<div style="font-weight: 800; font-size: 12.5px; color: #0f172a; margin-bottom: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px;">   Active  (Academic Year Timeline)</div>' +
                                               timelineHtml +
                                               '</div>';
                        });
                    } else {
                        resBox.innerHTML = '<div style="color:#dc2626; font-weight:800; text-align:center; padding: 15px;">    No  Search.</div>';
                    }
                });
            }

            let mInfoScannerInstance = null;
            function eessStartMobileInfoCamera() {
                var reader = document.getElementById('m-info-camera-reader');
                if (!reader) return;
                reader.style.display = 'block';

                function startInfoCam() {
                    if (typeof Html5Qrcode !== 'undefined') {
                        if (!mInfoScannerInstance) {
                            mInfoScannerInstance = new Html5Qrcode("m-info-camera-reader");
                        }
                        var formats = (typeof Html5QrcodeSupportedFormats !== 'undefined') ? [
                            Html5QrcodeSupportedFormats.CODE_128,
                            Html5QrcodeSupportedFormats.CODE_39,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.QR_CODE
                        ] : undefined;

                        var config = { fps: 20, qrbox: 250, formatsToSupport: formats };

                        var onScan = function(decodedText) {
                            var cleanCode = decodedText.trim();
                            if (mInfoScannerInstance) {
                                mInfoScannerInstance.stop().then(function() {
                                    mInfoScannerInstance = null;
                                    reader.style.display = 'none';
                                    var searchInp = document.getElementById('m_info_search_input');
                                    if (searchInp) searchInp.value = cleanCode;
                                    eessSearchStudentInfoByCode(cleanCode);
                                }).catch(function() {
                                    mInfoScannerInstance = null;
                                    reader.style.display = 'none';
                                    eessSearchStudentInfoByCode(cleanCode);
                                });
                            } else {
                                reader.style.display = 'none';
                                eessSearchStudentInfoByCode(cleanCode);
                            }
                        };

                        mInfoScannerInstance.start({ facingMode: "environment" }, config, onScan).catch(function(err) {
                            if (typeof Html5Qrcode.getCameras === 'function') {
                                Html5Qrcode.getCameras().then(function(cams) {
                                    if (cams && cams.length > 0) {
                                        mInfoScannerInstance.start(cams[cams.length - 1].id, config, onScan).catch(function(e) {
                                            eessShowMobileToast('  : ' + e, 'error');
                                            reader.style.display = 'none';
                                        });
                                    } else {
                                        eessShowMobileToast('     .', 'error');
                                        reader.style.display = 'none';
                                    }
                                }).catch(function(e) {
                                    eessShowMobileToast('  : ' + err, 'error');
                                    reader.style.display = 'none';
                                });
                            } else {
                                eessShowMobileToast('  : ' + err, 'error');
                                reader.style.display = 'none';
                            }
                        });
                    } else {
                        eessShowMobileToast('   ...', 'info');
                        setTimeout(startInfoCam, 400);
                    }
                }

                startInfoCam();
            }

            function eessSwitchMobileIdentMethod(method, btn) {
                document.querySelectorAll('.m-ident-tab').forEach(b => {
                    b.style.background = 'white'; b.style.color = '#475569'; b.style.border = '1px solid #cbd5e1';
                });
                btn.style.background = '#dc2626'; btn.style.color = 'white'; btn.style.border = 'none';

                document.getElementById('m-ident-panel-camera').style.display = (method === 'camera') ? 'block' : 'none';
                document.getElementById('m-ident-panel-search').style.display = (method === 'search') ? 'block' : 'none';
            }

            let mViolScannerInstance = null;
            let lastViolScannedCode = '';
            let lastViolScanTime = 0;
            let scannedMobileStudentCodes = [];

            function eessMobileScanBarcodeImage(input) {
                if (!input.files || !input.files[0]) return;
                var file = input.files[0];
                var hiddenDiv = document.getElementById('m-reader-file-temp');
                if (!hiddenDiv) {
                    hiddenDiv = document.createElement('div');
                    hiddenDiv.id = 'm-reader-file-temp';
                    hiddenDiv.style.display = 'none';
                    document.body.appendChild(hiddenDiv);
                }
                if (typeof Html5Qrcode !== 'undefined') {
                    var html5QrCode = new Html5Qrcode("m-reader-file-temp");
                    html5QrCode.scanFile(file, true).then(function(decodedText) {
                        const code = decodedText.trim();
                        if (scannedMobileStudentCodes.includes(code)) {
                            eessShowMobileToast('    No ', 1000);
                            return;
                        }
                        eessResolveMobileViolStudent(code);
                    }).catch(function(err) {
                        eessShowMobileToast('    ', 'error');
                    }).finally(function() {
                        input.value = '';
                    });
                }
            }

            function eessStartMobileViolCamera() {
                var reader = document.getElementById('m-viol-camera-reader');
                if (!reader) return;

                if (mViolScannerInstance) {
                    mViolScannerInstance.stop().then(function() {
                        mViolScannerInstance = null;
                        reader.style.display = 'none';
                    }).catch(function() {
                        mViolScannerInstance = null;
                        reader.style.display = 'none';
                    });
                    return;
                }

                reader.style.display = 'block';

                function startViolCam() {
                    if (typeof Html5Qrcode !== 'undefined') {
                        if (!mViolScannerInstance) {
                            mViolScannerInstance = new Html5Qrcode("m-viol-camera-reader");
                        }
                        var formats = (typeof Html5QrcodeSupportedFormats !== 'undefined') ? [
                            Html5QrcodeSupportedFormats.CODE_128,
                            Html5QrcodeSupportedFormats.CODE_39,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.QR_CODE
                        ] : undefined;

                        var config = { fps: 20, qrbox: { width: 260, height: 160 }, formatsToSupport: formats };

                        var onScan = function(decodedText) {
                            const code = decodedText.trim();
                            const now = Date.now();

                            if (now - lastViolScanTime < 1000 && code === lastViolScannedCode) {
                                return;
                            }
                            lastViolScanTime = now;
                            lastViolScannedCode = code;

                            if (scannedMobileStudentCodes.includes(code)) {
                                eessShowMobileToast('    No ', 1000);
                                return;
                            }

                            eessResolveMobileViolStudent(code);
                        };

                        mViolScannerInstance.start({ facingMode: "environment" }, config, onScan).catch(function(err) {
                            if (typeof Html5Qrcode.getCameras === 'function') {
                                Html5Qrcode.getCameras().then(function(cams) {
                                    if (cams && cams.length > 0) {
                                        mViolScannerInstance.start(cams[cams.length - 1].id, config, onScan).catch(function(e) {
                                            eessShowMobileToast('  : ' + e, 'error');
                                            reader.style.display = 'none';
                                        });
                                    } else {
                                        eessShowMobileToast('      .', 'error');
                                        reader.style.display = 'none';
                                    }
                                }).catch(function(e) {
                                    eessShowMobileToast('  : ' + err, 'error');
                                    reader.style.display = 'none';
                                });
                            } else {
                                eessShowMobileToast('  : ' + err, 'error');
                                reader.style.display = 'none';
                            }
                        });
                    } else {
                        eessShowMobileToast('  ...', 'info');
                        setTimeout(startViolCam, 400);
                    }
                }

                startViolCam();
            }

            function eessMobileSearchStudentUnified() {
                var q = document.getElementById('m_viol_unified_input').value.trim();
                var resDiv = document.getElementById('m_viol_name_results');
                if (q.length < 2) { resDiv.style.display = 'none'; return; }

                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_search_students',
                    query: q
                }, function(res) {
                    if (res.success && res.data && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(st => {
                            var codeMeta = st.student_code ? (' | : ' + st.student_code) : '';
                            html += '<div onclick="eessSelectMobileViolStudent(' + st.id + ', \'' + st.name.replace(/'/g, "\\'") + '\', \'' + (st.class_name || '') + '\')" style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; font-weight: 700; cursor: pointer;">' + st.name + ' (' + (st.class_name || '') + codeMeta + ')</div>';
                        });
                        resDiv.innerHTML = html;
                        resDiv.style.display = 'block';
                    } else {
                        resDiv.style.display = 'none';
                    }
                });
            }

            function eessMobileConfirmSearchStudentByCode() {
                var val = document.getElementById('m_viol_unified_input').value.trim();
                if (val) eessResolveMobileViolStudent(val);
            }

            function eessResolveMobileViolStudent(code) {
                if (scannedMobileStudentCodes.includes(code)) {
                    eessShowMobileToast('    No ', 1000);
                    return;
                }
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_get_student',
                    code: code
                }, function(res) {
                    if (res.success && res.data) {
                        eessSelectMobileViolStudent(res.data.id, res.data.name, res.data.class_name, code);
                    } else {
                        alert('     No  : ' + code);
                    }
                });
            }

            let selectedMobileViolStudents = []; // Array of objects { id, name, code }

            function eessSelectMobileViolStudent(id, name, className, codeVal) {
                var exists = selectedMobileViolStudents.some(function(s) { return String(s.id) === String(id); });
                if (exists) {
                    eessShowMobileToast('No   ', 1000);
                    return;
                }

                selectedMobileViolStudents.push({ id: id, name: name, code: codeVal });
                eessRenderMobileViolStudentCapsules();

                document.getElementById('m-selected-student-box').style.display = 'block';
                document.getElementById('eess_mobile_violation_form').style.display = 'block';
                document.getElementById('m_viol_name_results').style.display = 'none';

                if (codeVal && !scannedMobileStudentCodes.includes(codeVal)) {
                    scannedMobileStudentCodes.push(codeVal);
                }
            }

            function eessRemoveMobileViolStudent(id) {
                selectedMobileViolStudents = selectedMobileViolStudents.filter(function(s) { return String(s.id) !== String(id); });
                eessRenderMobileViolStudentCapsules();
                if (selectedMobileViolStudents.length === 0) {
                    document.getElementById('m-selected-student-box').style.display = 'none';
                    document.getElementById('eess_mobile_violation_form').style.display = 'none';
                }
            }

            function eessRenderMobileViolStudentCapsules() {
                var listContainer = document.getElementById('m_sel_stu_capsules_list');
                var countBadge = document.getElementById('m_sel_stu_count');
                var hiddenInput = document.getElementById('m_viol_student_id');

                if (countBadge) countBadge.innerText = selectedMobileViolStudents.length;
                if (hiddenInput) {
                    hiddenInput.value = selectedMobileViolStudents.map(function(s) { return s.id; }).join(',');
                }

                if (!listContainer) return;
                var html = '';
                selectedMobileViolStudents.forEach(function(s) {
                    html += '<div style="background: #ffffff; border: 1px solid #86efac; color: #15803d; padding: 4px 10px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">' +
                            '<span>' + s.name + '</span>' +
                            '<span onclick="eessRemoveMobileViolStudent(' + s.id + ')" style="cursor: pointer; font-size: 14px; line-height: 1; color: #dc2626; font-weight: 900;">&times;</span>' +
                            '</div>';
                });
                listContainer.innerHTML = html;
            }

            let mViolSubmitting = false;
            function eessSubmitMobileViolation(e) {
                e.preventDefault();
                if (mViolSubmitting) return;

                var btn = document.getElementById('m_viol_submit_btn');
                mViolSubmitting = true;
                btn.disabled = true;
                btn.innerText = ' Save  ... ⏳';

                var formData = jQuery('#eess_mobile_violation_form').serialize() + '&action=sm_save_record_ajax';

                jQuery.post('<?php echo $ajax_url; ?>', formData, function(res) {
                    mViolSubmitting = false;
                    btn.disabled = false;
                    btn.innerText = 'Save   ';

                    if (res.success) {
                        eessShowMobileToast('✓  Save    !');
                        document.getElementById('eess_mobile_violation_form').reset();
                        document.getElementById('m-selected-student-box').style.display = 'none';
                        document.getElementById('eess_mobile_violation_form').style.display = 'none';
                    } else {
                        alert('An error occurred  Save : ' + (res.data || ' Save '));
                    }
                });
            }
            </script>
            <?php endif; ?>

            <?php if ($is_supervisor && !$is_admin_supervisor): ?>
            <!-- MOBILE SUPERVISOR MONITORING & REVIEW DASHBOARD -->
            <div id="m-supervisor-app" style="display: block;">

                <!-- Sub-Tabs: Plans vs. Lesson Preps -->
                <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                    <button type="button" onclick="eessSwitchMobileSupTab('preps', this)" class="m-sup-tab-btn active" style="flex: 1; height: 38px; border-radius: 9999px; border: none; background: #881337; color: white; font-weight: 800; font-size: 12px; cursor: pointer;">
                              (<?php echo count($mobile_submissions); ?>)
                    </button>
                    <button type="button" onclick="eessSwitchMobileSupTab('plans', this)" class="m-sup-tab-btn" style="flex: 1; height: 38px; border-radius: 9999px; border: 1px solid #cbd5e1; background: white; color: #475569; font-weight: 800; font-size: 12px; cursor: pointer;">
                         Training Group (<?php echo count($mobile_term_plans); ?>)
                    </button>
                </div>

                <!-- Search Input Bar -->
                <div style="margin-bottom: 16px;">
                    <input type="text" id="m_sup_search_input" onkeyup="eessFilterMobileSupCards()" placeholder="Search   Sport Activity   ..." style="width: 100%; height: 40px; border-radius: 9999px; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 12.5px; box-sizing: border-box; background: #ffffff;">
                </div>

                <!-- Lesson Preps Container -->
                <div id="m-sup-panel-preps" style="display: block;">
                    <?php if (empty($mobile_submissions)): ?>
                        <div style="background: white; border-radius: 12px; padding: 30px; text-align: center; color: #94a3b8; font-weight: 700; font-size: 13px;">No      .</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($mobile_submissions as $ms):
                                $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = '';
                                if ($ms->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = ' '; }
                                elseif ($ms->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = ' '; }
                                elseif ($ms->status === 'revision_required' || $ms->status === 'returned') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = ' Edit'; }
                            ?>
                            <div class="m-sup-card" data-search="<?php echo esc_attr(strtolower(($ms->teacher_name ?? '') . ' ' . $ms->subject . ' ' . $ms->title)); ?>" style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a;"><?php echo esc_html($ms->teacher_name ?: '  '); ?></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><span class="dashicons dashicons-book" style="font-size: 14px; width: 14px; height: 14px; color: #881337; vertical-align: middle;"></span> <?php echo esc_html($ms->subject); ?> | Training Group: <?php echo esc_html($ms->grade_level); ?></div>
                                    </div>
                                    <span style="font-size: 10.5px; padding: 3px 10px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800;"><?php echo $s_lbl; ?></span>
                                </div>
                                <div style="font-weight: 800; font-size: 13px; color: #1e293b; margin-bottom: 10px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                                    <span class="dashicons dashicons-editor-contract" style="font-size: 15px; width: 15px; height: 15px; color: #881337; vertical-align: middle;"></span> <?php echo esc_html($ms->title); ?>
                                </div>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button type="button" onclick="eessMobileApprovePrep(<?php echo $ms->id; ?>)" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #16a34a; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;"></button>
                                    <button type="button" onclick="eessMobileReturnPrep(<?php echo $ms->id; ?>)" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #dc2626; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;"> Edit</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Term Plans Container -->
                <div id="m-sup-panel-plans" style="display: none;">
                    <?php if (empty($mobile_term_plans)): ?>
                        <div style="background: white; border-radius: 12px; padding: 30px; text-align: center; color: #94a3b8; font-weight: 700; font-size: 13px;">No      .</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($mobile_term_plans as $mtp):
                                $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = '';
                                if ($mtp->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = ' '; }
                                elseif ($mtp->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = ' '; }
                                elseif ($mtp->status === 'returned' || $mtp->status === 'rejected') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = ' Edit'; }
                            ?>
                            <div class="m-sup-card" data-search="<?php echo esc_attr(strtolower(($mtp->teacher_name ?? '') . ' ' . $mtp->subject . ' ' . $mtp->grade)); ?>" style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a;"><?php echo esc_html($mtp->teacher_name ?: '  '); ?></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;"> <?php echo esc_html($mtp->subject); ?> | Training Group: <?php echo esc_html($mtp->grade); ?></div>
                                    </div>
                                    <span style="font-size: 10.5px; padding: 3px 10px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800;"><?php echo $s_lbl; ?></span>
                                </div>
                                <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 10px;">
                                    <button type="button" onclick="eessDirectReviewPlan(<?php echo $mtp->id; ?>, 'approved')" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #16a34a; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;"> </button>
                                    <button type="button" onclick="eessDirectReviewPlan(<?php echo $mtp->id; ?>, 'returned')" style="height: 32px; padding: 0 14px; border-radius: 9999px; background: #dc2626; color: white; border: none; font-weight: 800; font-size: 11.5px; cursor: pointer;"> Edit</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function eessSwitchMobileSupTab(type, btn) {
                document.querySelectorAll('.m-sup-tab-btn').forEach(b => {
                    b.style.background = 'white';
                    b.style.color = '#475569';
                    b.style.border = '1px solid #cbd5e1';
                });
                btn.style.background = '#881337';
                btn.style.color = 'white';
                btn.style.border = 'none';

                document.getElementById('m-sup-panel-preps').style.display = (type === 'preps') ? 'block' : 'none';
                document.getElementById('m-sup-panel-plans').style.display = (type === 'plans') ? 'block' : 'none';
            }

            function eessFilterMobileSupCards() {
                const q = document.getElementById('m_sup_search_input').value.toLowerCase().trim();
                document.querySelectorAll('.m-sup-card').forEach(c => {
                    const text = c.getAttribute('data-search') || '';
                    if (!q || text.includes(q)) c.style.display = 'block';
                    else c.style.display = 'none';
                });
            }

            function eessMobileApprovePrep(id) {
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_review_term_plan',
                    plan_id: id,
                    review_status: 'approved',
                    sm_nonce: '<?php echo wp_create_nonce("sm_term_plan_action"); ?>'
                }, function(res) {
                    if (typeof smShowNotification === 'function') smShowNotification('   ');
                    setTimeout(() => location.reload(), 600);
                });
            }

            function eessMobileReturnPrep(id) {
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_review_term_plan',
                    plan_id: id,
                    review_status: 'returned',
                    sm_nonce: '<?php echo wp_create_nonce("sm_term_plan_action"); ?>'
                }, function(res) {
                    if (typeof smShowNotification === 'function') smShowNotification('    Edit');
                    setTimeout(() => location.reload(), 600);
                });
            }
            </script>
            <?php endif; ?>

            <?php if (!is_user_logged_in()):
                $m_school_info = SM_Settings::get_school_info();
                $m_login_sys_logo = !empty($m_school_info['school_logo']) ? $m_school_info['school_logo'] : (!empty($m_school_info['logo_url']) ? $m_school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
            ?>
            <!-- Single-Viewport Mobile Login Container (No Vertical or Horizontal Scrolling) -->
            <div style="height: 100vh; height: 100dvh; max-height: 100vh; max-height: 100dvh; width: 100%; max-width: 100vw; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px 16px; box-sizing: border-box; overflow-x: hidden; overflow-y: hidden; font-family: 'Cairo', sans-serif; gap: 12px;">

                <!-- Centered Authentication Box with Integrated Branding -->
                <div id="m-step-verify" style="background: #ffffff; border-radius: 20px; padding: 18px 20px; border: 1px solid #e2e8f0; box-shadow: 0 12px 28px -5px rgba(15, 23, 42, 0.08); width: 100%; max-width: 380px; box-sizing: border-box; flex-shrink: 0;">

                    <!-- System Branding & Logo Area inside Login Box replacing Welcome Header -->
                    <div style="text-align: center; margin-bottom: 14px; display: flex; flex-direction: column; align-items: center; gap: 3px;">
                        <div style="width: 68px; height: 68px; border-radius: 16px; background: #ffffff; padding: 4px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; margin-bottom: 2px;">
                            <img src="<?php echo esc_url($m_login_sys_logo); ?>" style="width: 100%; height: 100%; object-fit: contain; border-radius: 12px;" alt="EESS Logo">
                        </div>
                        <h1 style="margin: 0; font-size: 21px; font-weight: 900; color: #0f172a; line-height: 1.2;">  </h1>
                        <p style="margin: 0; font-size: 11px; color: #64748b; font-weight: 600;">    </p>
                    </div>

                    <div style="margin-bottom: 10px; position: relative;">
                        <div class="eess-float-container" style="position: relative; width: 100%;">
                            <input type="text" id="m_emp_id_input" class="eess-float-input" placeholder=" " style="width: 100%; height: 42px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; font-weight: 700; color: #0f172a; box-sizing: border-box; outline: none; transition: all 0.2s ease;">
                            <label for="m_emp_id_input" class="eess-float-label">National ID /   /  *</label>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px; position: relative;">
                        <div class="eess-float-container eess-password-wrapper" style="position: relative; width: 100%;">
                            <input type="password" id="m_password_input" class="eess-float-input" placeholder=" " style="width: 100%; height: 42px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px 0 38px; font-size: 13.5px; font-weight: 700; color: #0f172a; box-sizing: border-box; outline: none; transition: all 0.2s ease;">
                            <label for="m_password_input" class="eess-float-label">Password *</label>
                            <button type="button" onclick="const p = document.getElementById('m_password_input'); p.type = p.type === 'password' ? 'text' : 'password';" title=" /  Password" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; font-size: 11.5px; color: #475569;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" id="m_remember_me" checked style="width: 15px; height: 15px; border-radius: 4px;">
                            <span>Male   Active</span>
                        </label>
                    </div>

                    <div id="m_verify_msg" style="display: none; margin-bottom: 10px; padding: 8px 10px; border-radius: 8px; font-size: 11.5px; font-weight: 700;"></div>

                    <div style="display: flex; justify-content: flex-start;">
                        <button type="button" onclick="eessVerifyMobileEmp()" id="m_btn_verify" style="height: 42px; padding: 0 26px; background: #000000; color: #ffffff !important; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s ease;">
                            <span>Login</span>
                        </button>
                    </div>
                </div>

                <!-- Computer Access Notice directly beneath Login Box -->
                <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 8px 12px; width: 100%; max-width: 380px; box-sizing: border-box; display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                    <span class="dashicons dashicons-desktop" style="color: #991b1b; font-size: 16px; width: 16px; height: 16px; flex-shrink: 0;"></span>
                    <div style="font-size: 11px; color: #991b1b; line-height: 1.4; font-weight: 700;">
                             Previous     .
                    </div>
                </div>

                <!-- Footer Branding with 2016 All Rights Reserved Attribution -->
                <div style="font-size: 10.5px; color: #94a3b8; text-align: center; margin-bottom: 4px; font-weight: 700; letter-spacing: 0.3px; flex-shrink: 0; font-family: monospace;">
                    © 2016 EESS Educational Systems Solutions. All Rights Reserved.
                </div>

            </div>
            <?php endif; ?>


            <?php if (is_user_logged_in() && in_array('sm_teacher', (array)$user->roles)): ?>

            <script>
            function eessUploadMobileAvatar(input) {
                if (!input.files || !input.files[0]) return;
                var file = input.files[0];
                var formData = new FormData();
                formData.append('action', 'eess_upload_mobile_profile_photo');
                formData.append('profile_photo', file);
                formData.append('nonce', '<?php echo wp_create_nonce("sm_user_action"); ?>');

                eessShowMobileToast('  Update  ... ⏳');

                jQuery.ajax({
                    url: '<?php echo esc_url(admin_url("admin-ajax.php")); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.success && res.data && res.data.photo_url) {
                            var cacheBusted = res.data.photo_url + '?v=' + new Date().getTime();
                            document.getElementById('m_header_avatar_img').src = cacheBusted;
                            eessShowMobileToast('✓  Update   !');
                            var cap = document.getElementById('m_photo_guidance_capsule');
                            if (cap) cap.style.display = 'none';
                        } else {
                            alert('  : ' + (res.data || 'An error occurred  '));
                        }
                    },
                    error: function() {
                        alert('An error occurred  No    .');
                    }
                });
            }
            </script>

            <div id="m-teacher-dashboard-overview" style="margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <!-- ACTION 1: UPLOAD LESSON PREP (RIGHT) -->
                    <button type="button" onclick="eessOpenMobileScreen('upload_prep')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #0f172a; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; margin-bottom: 10px;">
                            <span class="dashicons dashicons-upload" style="font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">  </span>
                        <span style="font-size: 10.5px; color: #64748b;">   (PDF)</span>
                    </button>

                    <!-- ACTION 2: SUBMIT SEMESTER PLAN (LEFT) -->
                    <button type="button" onclick="eessOpenMobileScreen('submit_plan')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 18px 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="width: 46px; height: 46px; background: #e0f2fe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0284c7; margin-bottom: 10px;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">  </span>
                        <span style="font-size: 10.5px; color: #64748b;">  Training Group </span>
                    </button>
                </div>

                <!-- DEDICATED SCREEN 2: UPLOAD LESSON PREPARATION -->
                <div id="m-screen-upload-prep" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0f172a;">   </h4>
                        <button type="button" onclick="eessBackToMobileMenu()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ </button>
                    </div>
                    <form id="eess_mobile_prep_upload_form" onsubmit="eessSubmitMobileUpload(event, 'prep')" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="sm_submit_mobile_lesson">
                        <?php wp_nonce_field('sm_mobile_prep_nonce', 'sm_nonce'); ?>
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">  <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="lesson_title" required class="sm-input" placeholder="  ..." style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">   ( PDF ) <span style="color:#ef4444;">*</span></label>
                            <input type="file" id="m_prep_file_input" name="lesson_file" accept=".pdf" required onchange="eessMobileHandleFileSelect(this, 'prep')" style="width: 100%; font-size: 12px;">
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">:       PDF  .</div>
                        </div>
                        <div id="m_prep_file_status" style="display: none; margin-top: 10px; margin-bottom: 12px; padding: 12px; border-radius: 10px; font-size: 12px;"></div>
                        <button type="submit" id="m_prep_submit_btn" disabled class="sm-btn" style="width: 100%; height: 42px; background: #0f172a; color: white !important; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: not-allowed; opacity: 0.5;"> Send </button>
                    </form>
                </div>

                <!-- DEDICATED SCREEN 3: SUBMIT SEMESTER PLAN -->
                <div id="m-screen-submit-plan" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0284c7;">   </h4>
                        <button type="button" onclick="eessBackToMobileMenu()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ </button>
                    </div>
                    <form id="eess_mobile_plan_upload_form" onsubmit="eessSubmitMobileUpload(event, 'plan')" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="sm_save_term_plan">
                        <input type="hidden" name="planning_method" value="upload">
                        <input type="hidden" name="status" value="submitted">
                        <?php wp_nonce_field('sm_term_plan_action', 'sm_nonce'); ?>
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">  / Sport Activity <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="subject" required class="sm-input" placeholder=" Sport Activity ..." style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">  Training Group ( PDF ) <span style="color:#ef4444;">*</span></label>
                            <input type="file" id="m_plan_file_input" name="plan_document_file" accept=".pdf" required onchange="eessMobileHandleFileSelect(this, 'plan')" style="width: 100%; font-size: 12px;">
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">:     Training Group  PDF  .</div>
                        </div>
                        <div id="m_plan_file_status" style="display: none; margin-top: 10px; margin-bottom: 12px; padding: 12px; border-radius: 10px; font-size: 12px;"></div>
                        <button type="submit" id="m_plan_submit_btn" disabled class="sm-btn" style="width: 100%; height: 42px; background: #0284c7; color: white !important; border-radius: 10px; font-weight: 800; font-size: 13px; border: none; cursor: not-allowed; opacity: 0.5;"> Send  Training Group</button>
                    </form>
                </div>

                <!-- DEDICATED SCREEN 4: VIEW SEMESTER PLAN HISTORY -->
                <div id="m-screen-view-plans" style="display: none; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #cbd5e1; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #15803d;">View   Training Group</h4>
                        <button type="button" onclick="eessBackToMobileMenu()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 4px 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; cursor: pointer;">➔ </button>
                    </div>
                    <?php if (empty($teacher_own_preps)): ?>
                        <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; font-weight: 700;">No     .</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($teacher_own_preps as $top): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 12px;">
                                    <div style="font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?php echo esc_html($top->title); ?></div>
                                    <div style="color: #64748b; font-size: 11px;">Sport Activity: <?php echo esc_html($top->subject); ?> | : <?php echo esc_html($top->lesson_date); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function eessOpenMobileScreen(screenKey) {
                document.getElementById('m-teacher-dashboard-overview').style.display = 'block';
                document.getElementById('m-screen-upload-prep').style.display = (screenKey === 'upload_prep') ? 'block' : 'none';
                document.getElementById('m-screen-submit-plan').style.display = (screenKey === 'submit_plan') ? 'block' : 'none';
                document.getElementById('m-screen-view-plans').style.display = (screenKey === 'view_plans') ? 'block' : 'none';

                if (screenKey === 'create_prep') {
                    document.getElementById('m-step-form').style.display = 'block';
                    document.getElementById('m-step-form').scrollIntoView({ behavior: 'smooth' });
                } else {
                    document.getElementById('m-step-form').style.display = 'none';
                    var target = document.getElementById('m-screen-' + screenKey.replace('_', '-'));
                    if (target) target.scrollIntoView({ behavior: 'smooth' });
                }
            }

            function eessBackToMobileMenu() {
                document.getElementById('m-screen-upload-prep').style.display = 'none';
                document.getElementById('m-screen-submit-plan').style.display = 'none';
                document.getElementById('m-screen-view-plans').style.display = 'none';
                document.getElementById('m-step-form').style.display = 'none';
                document.getElementById('m-teacher-dashboard-overview').scrollIntoView({ behavior: 'smooth' });
            }

            function eessMobileHandleFileSelect(input, mode) {
                var statusBox = document.getElementById(mode === 'prep' ? 'm_prep_file_status' : 'm_plan_file_status');
                var submitBtn = document.getElementById(mode === 'prep' ? 'm_prep_submit_btn' : 'm_plan_submit_btn');

                if (!input.files || !input.files[0]) {
                    statusBox.style.display = 'none';
                    statusBox.innerHTML = '';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                    return;
                }

                var file = input.files[0];
                var ext = file.name.split('.').pop().toLowerCase();

                if (ext !== 'pdf') {
                    statusBox.style.display = 'block';
                    statusBox.style.background = '#fef2f2';
                    statusBox.style.border = '1px solid #fca5a5';
                    statusBox.style.color = '#991b1b';
                    statusBox.innerHTML = '<div style="font-weight: 800; margin-bottom: 2px;">✕    </div>' +
                                          '<div style="font-size: 11px;">    PDF  (.pdf).     .</div>';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                    return;
                }

                var fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                statusBox.style.display = 'block';
                statusBox.style.background = '#f0fdf4';
                statusBox.style.border = '1px solid #86efac';
                statusBox.style.color = '#166534';
                statusBox.innerHTML = '<div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 12.5px; margin-bottom: 4px;">' +
                                      '<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #22c55e; color: white; border-radius: 50%; font-size: 12px; font-weight: 900;">✓</span>' +
                                      '<span>  Confirm  </span>' +
                                      '</div>' +
                                      '<div style="font-size: 11px; color: #15803d; line-height: 1.4;">' +
                                      '<strong> :</strong> ' + file.name + '<br>' +
                                      '<strong>:</strong> ' + fileSizeMB + ' ' +
                                      '</div>';

                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }

            function eessSubmitMobileUpload(e, mode) {
                e.preventDefault();
                var form = e.target;
                var submitBtn = document.getElementById(mode === 'prep' ? 'm_prep_submit_btn' : 'm_plan_submit_btn');
                var fileInput = document.getElementById(mode === 'prep' ? 'm_prep_file_input' : 'm_plan_file_input');

                if (!fileInput.files || !fileInput.files[0]) {
                    eessShowMobileToast('    No  Send.', 'error');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.innerHTML = ' Upload Save... ⏳';

                var formData = new FormData(form);

                jQuery.ajax({
                    url: '<?php echo esc_url(admin_url("admin-ajax.php")); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.innerHTML = (mode === 'prep') ? ' Send ' : ' Send  Training Group';

                        if (res.success) {
                            eessShowMobileToast('✓ ' + (res.data && res.data.message ? res.data.message : '  Send  !'), 'success');
                            form.reset();
                            eessMobileHandleFileSelect(fileInput, mode);

                            setTimeout(function() {
                                window.location.reload();
                            }, 1200);
                        } else {
                            eessShowMobileToast('✕ ' + (res.data || 'An error occurred      No.'), 'error');
                        }
                    },
                    error: function() {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.innerHTML = (mode === 'prep') ? ' Send ' : ' Send  Training Group';
                        eessShowMobileToast('✕  No     No  .', 'error');
                    }
                });
            }
            </script>
            <?php endif; ?>

            <!-- TEACHER PAST PREPARATIONS ARCHIVE CARD -->
            <div id="m-teacher-history-card" style="display: <?php echo (!empty($teacher_own_preps)) ? 'block' : 'none'; ?>; background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #e2e8f0; margin-top: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-portfolio" style="color: #881337;"></span>
                        <span>   Previous</span>
                    </h3>
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;"><?php echo count($teacher_own_preps); ?> </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto;">
                    <?php foreach ($teacher_own_preps as $top):
                        $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = '';
                        if ($top->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = ' '; }
                        elseif ($top->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = ' '; }
                        elseif ($top->status === 'revision_required' || $top->status === 'returned') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = ' Edit'; }
                    ?>
                        <div onclick="eessMobileReopenOwnPrep(<?php echo htmlspecialchars(json_encode($top)); ?>)" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                <div style="font-weight: 800; font-size: 13px; color: #0f172a;"><?php echo esc_html($top->title); ?></div>
                                <span style="font-size: 10px; padding: 2px 8px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800; white-space: nowrap;"><?php echo $s_lbl; ?></span>
                            </div>
                            <div style="font-size: 11px; color: #64748b; display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                <span><span class="dashicons dashicons-book" style="font-size: 13px; width: 13px; height: 13px; color: #881337; vertical-align: middle;"></span> <?php echo esc_html($top->subject); ?> (<?php echo esc_html($top->grade_level); ?>)</span>
                                <span style="font-family: monospace; font-size: 10.5px; font-weight: 700; color: #475569;"><?php echo esc_html($top->lesson_date); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 3: Mobile Lesson Preparation Form -->
            <div id="m-step-form" style="display: none; margin-top: 15px;">
                <form id="eess_mobile_prep_form" onsubmit="eessSubmitMobileLesson(event)">
                    <input type="hidden" id="m_form_teacher_id" name="teacher_id">
                    <input type="hidden" id="m_form_emp_id" name="emp_id">
                    <input type="hidden" name="sm_nonce" value="<?php echo esc_attr($nonce); ?>">

                    <!-- Basic Info Box -->
                    <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">1.   </h4>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">  <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="m_title" name="title" required placeholder="  " style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Sport Activity  <span style="color:#ef4444;">*</span></label>
                            <select id="m_subject" name="subject" required style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                                <?php foreach($unique_subjects as $s_name): ?>
                                    <option value="<?php echo esc_attr($s_name); ?>"><?php echo esc_html($s_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Training Group <span style="color:#ef4444;">*</span></label>
                                <input type="text" id="m_grade" name="grade_level" required placeholder="Training Group " style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Training Group / Training Group</label>
                                <input type="text" id="m_section" name="class_section" placeholder=" / 1" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;"> </label>
                            <input type="date" id="m_date" name="lesson_date" value="<?php echo current_time('Y-m-d'); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Academic Content Box (Fully Synchronized with Desktop) -->
                    <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">2.    </h4>

                        <!-- Objectives (150-350 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">    <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_objectives" name="objectives" maxlength="350" oninput="eessUpdateMobileCharBounds(this, 150, 350, 'm_cnt_objectives')" placeholder="   (150 – 350 )..." style="width: 100%; height: 90px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_objectives" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 350 </div>
                        </div>

                        <!-- Warmup (150-350 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">1.    (Warm-Up) <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_warmup" name="warmup" maxlength="350" oninput="eessUpdateMobileCharBounds(this, 150, 350, 'm_cnt_warmup')" placeholder="   (150 – 350 )..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_warmup" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 350 </div>
                        </div>

                        <!-- Physical Prep (150-400 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">2.     <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_physical_prep" name="physical_prep" maxlength="400" oninput="eessUpdateMobileCharBounds(this, 150, 400, 'm_cnt_physical_prep')" placeholder="    (150 – 400 )..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_physical_prep" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 400 </div>
                        </div>

                        <!-- Skill Prep (150-400 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">3.    <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_skill_prep" name="skill_prep" maxlength="400" oninput="eessUpdateMobileCharBounds(this, 150, 400, 'm_cnt_skill_prep')" placeholder="    (150 – 400 )..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_skill_prep" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 400 </div>
                        </div>

                        <!-- Conclusion (150-350 chars) -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">4.   No <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_conclusion" name="conclusion" maxlength="350" oninput="eessUpdateMobileCharBounds(this, 150, 350, 'm_cnt_conclusion')" placeholder="   (150 – 350 )..." style="width: 100%; height: 85px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                            <div id="m_cnt_conclusion" style="text-align: left; font-size: 10.5px; font-weight: 700; color: #dc2626; font-family: monospace; margin-top: 2px;">0 / 150 - 350 </div>
                        </div>

                        <!-- National Agenda -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">    Country <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_national_agenda" name="national_agenda" placeholder="   ..." style="width: 100%; height: 75px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <!-- Cross Subject -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;"> Sports Activities   <span style="color:#ef4444;">*</span></label>
                            <textarea id="m_cross_subject" name="cross_subject" placeholder="  Sports Activities ..." style="width: 100%; height: 75px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <!-- Notes & Guidance -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">Notes  No </label>
                            <textarea id="m_notes" name="notes" placeholder=" Notes  No  ..." style="width: 100%; height: 75px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 12.5px; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <script>
                        function eessUpdateMobileCharBounds(input, minLen, maxLen, badgeId) {
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
                        </script>
                    </div>

                    <div id="m_submit_status" style="display: none; margin-bottom: 15px; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-align: center;"></div>

                    <button type="submit" id="m_btn_submit" style="width: 100%; height: 48px; background: #2563eb; color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>Send  </span>
                    </button>
                </form>
            </div>
        </div>

        <script>
            let currentTeacherData = null;

            function eessVerifyMobileEmp() {
                const empId = document.getElementById('m_emp_id_input').value.trim();
                const passVal = document.getElementById('m_password_input') ? document.getElementById('m_password_input').value : '';
                const msgBox = document.getElementById('m_verify_msg');
                const btn = document.getElementById('m_btn_verify');

                if (!empId) {
                    msgBox.style.display = 'block';
                    msgBox.style.background = '#fef2f2';
                    msgBox.style.color = '#991b1b';
                    msgBox.innerText = '     Mobile Number No.';
                    return;
                }

                btn.disabled = true;
                btn.innerText = '  ...';
                msgBox.style.display = 'none';

                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'sm_verify_employee_id',
                    emp_id: empId,
                    password: passVal
                }, function(res) {
                    btn.disabled = false;
                    btn.innerText = 'Login ';

                    if (res.success) {
                        location.reload();
                    } else {
                        msgBox.style.display = 'block';
                        msgBox.style.background = '#fef2f2';
                        msgBox.style.color = '#991b1b';
                        msgBox.innerText = res.data || '       .       Phone Number.';
                    }
                });
            }

            function eessConfirmMobileIdentity() {
                if (!currentTeacherData) return;
                document.getElementById('m_form_teacher_id').value = currentTeacherData.teacher_id;
                document.getElementById('m_form_emp_id').value = currentTeacherData.emp_id;

                if (currentTeacherData.subject && currentTeacherData.subject !== '') {
                    const sel = document.getElementById('m_subject');
                    for (let i = 0; i < sel.options.length; i++) {
                        if (sel.options[i].value === currentTeacherData.subject) {
                            sel.selectedIndex = i;
                            break;
                        }
                    }
                }

                if (currentTeacherData.grade) {
                    document.getElementById('m_grade').value = currentTeacherData.grade;
                }
                if (currentTeacherData.section) {
                    document.getElementById('m_section').value = currentTeacherData.section;
                }

                // Auto-restore LocalStorage draft if available
                eessRestoreMobileDraft(currentTeacherData.teacher_id);

                document.getElementById('m-step-form').style.display = 'block';
                document.getElementById('m-step-form').scrollIntoView({ behavior: 'smooth' });
            }

            function eessSaveMobileDraftAuto() {
                if (!currentTeacherData) return;
                const draft = {
                    title: document.getElementById('m_title').value,
                    subject: document.getElementById('m_subject').value,
                    grade: document.getElementById('m_grade').value,
                    section: document.getElementById('m_section').value,
                    objectives: document.getElementById('m_objectives').value,
                    warmup: document.getElementById('m_warmup').value,
                    activities: document.getElementById('m_activities').value,
                    evaluation: document.getElementById('m_evaluation').value
                };
                try {
                    localStorage.setItem('eess_mobile_draft_' + currentTeacherData.teacher_id, JSON.stringify(draft));
                } catch(e) {}
            }

            function eessRestoreMobileDraft(teacherId) {
                try {
                    const raw = localStorage.getItem('eess_mobile_draft_' + teacherId);
                    if (raw) {
                        const d = JSON.parse(raw);
                        if (d.title && !document.getElementById('m_title').value) document.getElementById('m_title').value = d.title;
                        if (d.grade && !document.getElementById('m_grade').value) document.getElementById('m_grade').value = d.grade;
                        if (d.section && !document.getElementById('m_section').value) document.getElementById('m_section').value = d.section;
                        if (d.objectives) document.getElementById('m_objectives').value = d.objectives;
                        if (d.warmup) document.getElementById('m_warmup').value = d.warmup;
                        if (d.activities) document.getElementById('m_activities').value = d.activities;
                        if (d.evaluation) document.getElementById('m_evaluation').value = d.evaluation;
                    }
                } catch(e) {}
            }

            function eessMobileReopenOwnPrep(prep) {
                if (!prep) return;
                document.getElementById('m_title').value = prep.title || '';
                if (prep.subject) document.getElementById('m_subject').value = prep.subject;
                if (prep.grade_level) document.getElementById('m_grade').value = prep.grade_level;
                if (prep.class_section) document.getElementById('m_section').value = prep.class_section;
                if (prep.lesson_date) document.getElementById('m_date').value = prep.lesson_date;

                try {
                    const parsed = typeof prep.lesson_data === 'string' ? JSON.parse(prep.lesson_data) : prep.lesson_data;
                    if (parsed) {
                        if (parsed.objectives) document.getElementById('m_objectives').value = parsed.objectives;
                        if (parsed.warmup) document.getElementById('m_warmup').value = parsed.warmup;
                        if (parsed.activities) document.getElementById('m_activities').value = parsed.activities;
                        if (parsed.evaluation) document.getElementById('m_evaluation').value = parsed.evaluation;
                    }
                } catch(e) {}

                document.getElementById('m-step-form').style.display = 'block';
                document.getElementById('m-step-form').scrollIntoView({ behavior: 'smooth' });
            }

            // Bind input listeners for auto-saving drafts
            jQuery(document).on('input change', '#eess_mobile_prep_form input, #eess_mobile_prep_form textarea, #eess_mobile_prep_form select', function() {
                eessSaveMobileDraftAuto();
            });

            function eessSubmitMobileLesson(e) {
                e.preventDefault();
                const btn = document.getElementById('m_btn_submit');
                const statusBox = document.getElementById('m_submit_status');

                btn.disabled = true;
                btn.innerText = ' Send ...';
                statusBox.style.display = 'none';

                const formData = jQuery('#eess_mobile_prep_form').serialize() + '&action=sm_submit_mobile_lesson';

                jQuery.post('<?php echo $ajax_url; ?>', formData, function(res) {
                    btn.disabled = false;
                    btn.innerText = 'Send  ';

                    if (res.success) {
                        eessShowMobileToast(res.data.message || ' Save Send     !');
                        document.getElementById('eess_mobile_prep_form').reset();
                        setTimeout(() => {
                            location.reload();
                        }, 1200);
                    } else {
                        statusBox.style.display = 'block';
                        statusBox.style.background = '#fef2f2';
                        statusBox.style.color = '#991b1b';
                        statusBox.style.border = '1px solid #fecaca';
                        statusBox.innerText = res.data || 'An error occurred  Save .';
                    }
                });
            }
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_lesson_prep() {
        if (!$this->eess_is_mobile_device()) {
            if (!is_user_logged_in()) {
                wp_safe_redirect(add_query_arg('redirect_to', urlencode(home_url('/lesson-prep')), home_url('/sm-login')));
                exit;
            }
        // Logged in on Desktop: Render normal desktop lesson prep view
        ob_start();
        include SM_PLUGIN_DIR . 'templates/admin-lesson-prep.php';
        return ob_get_clean();
        }

        return $this->eess_render_mobile_lesson_prep();

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);

        ob_start();
        include SM_PLUGIN_DIR . 'templates/admin-lesson-prep.php';
        return ob_get_clean();
    }

    public function eess_is_mobile_device() {
        if (wp_is_mobile()) {
            return true;
        }
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        if (empty($user_agent)) {
            return false;
        }
        return (bool) preg_match('/(android|bb\d+|meego).+mobile|blackberry|iphone|ipad|ipod|opera mini|iemobile|mobile|palm|phone|pocket|psp|symbian|up\.browser|up\.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|silk|mobile)/i', $user_agent);
    }

    public function eess_render_mobile_restriction_screen() {
        return '
        <div class="eess-mobile-blocked-container" style="position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 999999; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: \'Cairo\', sans-serif; direction: rtl; text-align: center; box-sizing: border-box;">
            <div style="background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 25px; max-width: 480px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); backdrop-filter: blur(10px);">
                <div style="width: 80px; height: 80px; margin: 0 auto 25px auto; background: rgba(239, 68, 68, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(239, 68, 68, 0.3);">
                    <svg style="width: 42px; height: 42px; fill: none; stroke: #ef4444; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                        <line x1="2" y1="2" x2="22" y2="22" stroke="#ef4444" stroke-width="2.5"></line>
                    </svg>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; color: #ffffff; margin: 0 0 15px 0; line-height: 1.4;">      </h2>
                <div style="background: rgba(239, 68, 68, 0.1); border-right: 4px solid #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: right;">
                    <p style="margin: 0; font-size: 14px; color: #fca5a5; font-weight: 700; line-height: 1.6;">
                        This system is available on desktop devices only. Please use a desktop or laptop computer to access the system.
                    </p>
                </div>
                <p style="font-size: 14px; color: #94a3b8; line-height: 1.7; margin-bottom: 30px;">
                                  .         (Desktop)   (Laptop).
                </p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="' . esc_url(home_url('/lesson-prep')) . '" style="background: #2563eb; color: #ffffff; text-decoration: none; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>No        </span>
                    </a>
                    <a href="' . esc_url(home_url('/class-attendance')) . '" style="background: rgba(255, 255, 255, 0.08); color: #cbd5e1; text-decoration: none; padding: 12px; border-radius: 12px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>No    </span>
                    </a>
                </div>
            </div>
        </div>
        <script>
            // Client-side hard safety enforcement
            (function() {
                if (window.innerWidth <= 1024 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    document.body.style.overflow = "hidden";
                }
            })();
        </script>
        ';
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }

        if ($this->eess_is_mobile_device()) {
            return $this->eess_render_mobile_lesson_prep();
        }

        $output = '
        <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap\');

        .eess-login-page-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 99999;
            overflow-y: auto;
            background: #ffffff;
            font-family: \'Cairo\', \'Noto Kufi Arabic\', sans-serif !important;
            direction: rtl;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .eess-login-split-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Left Panel (Branding) */
        .eess-login-left-panel {
            width: 50%;
            background-color: #0d0d0d;
            background-image: linear-gradient(135deg, #0d0d0d 0%, #16161a 100%);
            color: #ffffff;
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            direction: ltr !important;
            text-align: left !important;
        }

        /* Modal Overlays and dialogs */
        .eess-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .eess-modal-dialog {
            background: #ffffff;
            border-radius: 12px;
            max-width: 520px;
            width: 100%;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: eessFadeIn 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-align: right;
            box-sizing: border-box;
        }

        @keyframes eessFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .eess-modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .eess-modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        .eess-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            margin: 0;
            transition: color 0.15s ease;
        }

        .eess-modal-close:hover {
            color: #475569;
        }

        .eess-modal-body {
            padding: 24px;
            box-sizing: border-box;
        }

        /* Steps progress indicator */
        .eess-step-progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            position: relative;
        }

        .eess-step-progress-bar::before {
            content: \'\';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }

        .eess-step-node {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: #64748b;
            z-index: 2;
            transition: all 0.25s ease;
        }

        .eess-step-node.active {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
        }

        .eess-step-node.completed {
            border-color: #10b981;
            background: #10b981;
            color: #ffffff;
        }

        .eess-wizard-step {
            display: none;
        }

        .eess-wizard-step.active {
            display: block;
        }

        .eess-modal-msg {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: none;
        }

        .eess-modal-msg.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .eess-modal-msg.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        /* Official Website Badge */
        .eess-official-badge-container {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }
        .eess-official-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 6px 14px;
            color: #e2e8f0;
            text-decoration: none !important;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .eess-official-badge:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .eess-official-badge .badge-icon-globe {
            margin-left: 8px;
            font-size: 0.9rem;
            color: #ef4444;
        }
        .eess-official-badge .badge-text-main {
            opacity: 0.8;
            margin-left: 5px;
        }
        .eess-official-badge .badge-text-domain {
            font-weight: bold;
            color: #ffffff;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.4);
            margin-left: 5px;
        }

        /* Branding Logo */
        .eess-branding-header {
            margin-top: 10px;
            position: relative;
            z-index: 2;
        }
        .eess-branding-logo-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
        }
        .eess-logo-text-col {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        .eess-logo-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 1px;
            line-height: 1;
            color: #ffffff;
            font-family: sans-serif;
        }
        .eess-logo-subtitle {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
            font-family: sans-serif;
            color: #cbd5e1;
        }
        .eess-logo-icon-col {
            background-color: #8b1e1e;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mortarboard-svg {
            width: 26px;
            height: 26px;
            fill: #ffffff;
        }
        .eess-branding-divider {
            height: 4px;
            width: 60px;
            background-color: #8b1e1e;
            margin-top: 15px;
            margin-left: 0 !important;
            margin-right: auto !important;
        }

        /* Main Headline */
        .eess-main-headline {
            font-size: 2.3rem;
            font-weight: 800;
            line-height: 1.45;
            margin: 40px 0;
            z-index: 2;
            color: #ffffff;
        }
        .underline-red {
            border-bottom: 3px solid #8b1e1e;
            padding-bottom: 2px;
        }

        /* About Box */
        .eess-about-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px 24px;
            margin-top: auto;
            position: relative;
            z-index: 2;
            direction: rtl !important;
            text-align: right !important;
        }
        .eess-about-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .eess-about-desc {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #cbd5e1;
            margin: 0;
        }
        .eess-about-desc a {
            color: #ffffff;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Giant Watermark */
        .eess-watermark {
            position: absolute;
            bottom: -30px;
            left: -20px;
            font-size: 11rem;
            font-weight: 900;
            font-family: sans-serif;
            color: #ffffff;
            opacity: 0.03;
            pointer-events: none;
            line-height: 1;
            user-select: none;
        }

        /* Right Panel (Form) */
        .eess-login-right-panel {
            width: 50%;
            background: #ffffff;
            padding: 40px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-sizing: border-box;
        }
        .eess-login-form-inner {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }

        .eess-login-form-title {
            font-size: 1.9rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .eess-login-form-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0 0 25px 0;
            font-weight: 400;
        }

        /* Floating label container styling */
        .eess-form-group {
            margin-bottom: 16px;
            position: relative;
        }
        .eess-float-container {
            position: relative;
            width: 100%;
        }
        .eess-float-input {
            width: 100%;
            height: 42px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.88rem;
            color: #0f172a;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .eess-float-input:focus {
            outline: none;
            border-color: #000000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        }
        .eess-float-label {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
            pointer-events: none;
            transition: all 0.2s ease;
            background: transparent;
            padding: 0 4px;
        }
        .eess-float-input:focus ~ .eess-float-label,
        .eess-float-input:not(:placeholder-shown) ~ .eess-float-label {
            top: 0;
            transform: translateY(-50%) scale(0.85);
            background: #ffffff;
            color: #0f172a;
            font-weight: 700;
        }

        /* Eye Icon Inside Password Fields */
        .eess-password-wrapper {
            position: relative;
            width: 100%;
        }
        .eess-password-wrapper .eess-float-input {
            padding-left: 40px !important;
        }
        .eess-toggle-eye {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin: 0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        .eess-toggle-eye:hover {
            color: #0f172a;
        }
        .eess-toggle-eye svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .eess-form-input {
            width: 100%;
            height: 42px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 14px;
            font-size: 0.9rem;
            color: #0f172a;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .eess-form-input:focus {
            outline: none;
            border-color: #000000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        }

        .eess-lost-pwd-link {
            font-size: 0.8rem;
            font-weight: 700;
            color: #8b1e1e !important;
            text-decoration: underline !important;
        }
        .eess-lost-pwd-link:hover {
            color: #b91c1c !important;
        }

        /* Remember me styling */
        .eess-form-row-remember {
            margin: 10px 0 15px 0;
        }
        .eess-remember-checkbox-label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .eess-remember-checkbox-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .eess-checkbox-custom {
            height: 18px;
            width: 18px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-left: 8px;
            position: relative;
            transition: all 0.15s ease;
        }
        .eess-remember-checkbox-label:hover input ~ .eess-checkbox-custom {
            border-color: #94a3b8;
        }
        .eess-remember-checkbox-label input:checked ~ .eess-checkbox-custom {
            background-color: #000000;
            border-color: #000000;
        }
        .eess-checkbox-custom:after {
            content: "";
            position: absolute;
            display: none;
        }
        .eess-remember-checkbox-label input:checked ~ .eess-checkbox-custom:after {
            display: block;
        }
        .eess-remember-checkbox-label .eess-checkbox-custom:after {
            left: 6px;
            top: 2px;
            width: 4px;
            height: 9px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .eess-checkbox-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }

        /* Main Login Button (Dark Black background) */
        .eess-btn-login {
            width: 100%;
            height: 44px;
            background-color: #000000 !important;
            color: #ffffff !important;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .eess-btn-login:hover {
            background-color: #1e1e1e !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.15);
        }

        /* Password Reset Card */
        .eess-reset-pwd-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            box-sizing: border-box;
        }
        .eess-reset-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .eess-reset-card-icon {
            font-size: 1rem;
            color: #8b1e1e;
        }
        .eess-reset-card-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }
        .eess-reset-card-desc {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0 0 12px 0;
        }
        /* Reset Password button - Dark Red background */
        .eess-btn-reset-pwd {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 38px;
            background-color: #8b1e1e !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(139, 30, 30, 0.1);
        }
        .eess-btn-reset-pwd:hover {
            background-color: #a82525 !important;
            transform: translateY(-1px);
        }

        /* Error notice */
        .eess-error-notice {
            background: #fff5f5;
            color: #c53030;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #feb2b2;
            margin-bottom: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Footer elements */
        .eess-login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .eess-footer-left {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .eess-footer-left .lock-icon {
            font-size: 0.85rem;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .eess-login-left-panel {
                width: 40%;
                padding: 30px 40px;
            }
            .eess-login-right-panel {
                width: 60%;
                padding: 30px 50px;
            }
            .eess-main-headline {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .eess-login-split-layout {
                flex-direction: column;
            }
            .eess-login-left-panel {
                width: 100%;
                min-height: auto;
                padding: 30px 24px;
            }
            .eess-login-right-panel {
                width: 100%;
                min-height: auto;
                padding: 40px 24px;
            }
            .eess-main-headline {
                font-size: 1.6rem;
                margin: 20px 0;
            }
            .eess-about-box {
                margin-top: 20px;
            }
            .eess-watermark {
                display: none;
            }
        }
        .eess-modal-overlay, .eess-modal-overlay * {
            font-family: \'Cairo\', \'Noto Kufi Arabic\', sans-serif !important;
        }
        </style>

        <div class="eess-login-page-container">
            <div class="eess-login-split-layout">
                <!-- Right Side (Form - Light) -->
                <div class="eess-login-right-panel">
                    <div class="eess-login-form-inner">
                        <!-- Title & Subtitle -->
                        <h1 class="eess-login-form-title">Login</h1>
                        <p class="eess-login-form-subtitle">  No     Dashboard.</p>

                        <!-- Error notice if failed -->
                        ';
                        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                            $output .= '<div class="eess-error-notice">  Username  Password.    .</div>';
                        }
                        $output .= '

                        <!-- Custom login form with Floating Labels -->
                        <form name="loginform" id="sm_login_form" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post">
                            <!-- Email / Acad ID field -->
                            <div class="eess-form-group">
                                <div class="eess-float-container">
                                    <input type="text" name="log" id="user_login" class="eess-float-input" placeholder=" " required>
                                    <label for="user_login" class="eess-float-label">Email Address /   *</label>
                                </div>
                            </div>

                            <!-- Password field with Eye Toggle -->
                            <div class="eess-form-group">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" name="pwd" id="user_pass" class="eess-float-input" placeholder=" " required>
                                    <label for="user_pass" class="eess-float-label">Password *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'user_pass\', this)" title=" /  Password">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember me row (compact) -->
                            <div class="eess-form-row-remember" style="margin: 8px 0 15px 0;">
                                <label class="eess-remember-checkbox-label" style="font-size: 0.8rem;">
                                    <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                                    <span class="eess-checkbox-custom" style="height:16px; width:16px;"></span>
                                    <span class="eess-checkbox-text" style="font-size: 0.8rem; color: #64748b;">Male    </span>
                                </label>
                            </div>

                            <!-- Login Submit Button (Compact, aligned right) -->
                            <div class="eess-form-group" style="display: flex; justify-content: flex-end; margin-top: 15px;">
                                <button type="submit" name="wp-submit" id="wp-submit" class="eess-btn-login" style="width: auto; min-width: 140px; height: 38px; padding: 0 20px; font-size: 0.88rem;">
                                    <span> </span>
                                    <span style="margin-right: 6px;">←</span>
                                </button>
                            </div>

                            <input type="hidden" name="redirect_to" value="' . esc_url(isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url('/sm-admin')) . '">
                        </form>

                        <!-- Unified Helper Services Card -->
                        <div class="eess-reset-pwd-card" style="margin-top: 15px; border-color: #cbd5e1;">
                            <div class="eess-reset-card-header" style="margin-bottom: 10px;">
                                <span class="eess-reset-card-icon">⚙️</span>
                                <span class="eess-reset-card-title">   </span>
                            </div>
                            <p class="eess-reset-card-desc" style="margin-bottom: 12px; font-size: 12px; color: #64748b;">   Next No Password       :</p>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" onclick="eessOpenForgotModal()" class="eess-btn-reset-pwd" style="flex: 1; font-size: 11px; height: 36px; background-color: #8b1e1e !important;">
                                     Password
                                </button>
                                <button type="button" onclick="eessOpenRegisterModal()" class="eess-btn-reset-pwd" style="flex: 1; font-size: 11px; height: 36px; background-color: #000000 !important;">

                                </button>
                            </div>
                        </div>

                        <!-- Footer under right panel -->
                        <div class="eess-login-footer">
                            <div class="eess-footer-left">
                                <span class="lock-icon"></span>
                                <span>  bit-256</span>
                                <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                                <a href="javascript:void(0)" onclick="eessOpenSupportModal()" style="color: #8b1e1e !important; text-decoration: underline !important; font-weight: bold; cursor: pointer;">  </a>
                            </div>
                            <div class="eess-footer-right">
                                <span>© 2026 EESS. All Rights Reserved</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Side (Branding - Dark) -->
                <div class="eess-login-left-panel">
                    <!-- Official website link badge -->
                    <div class="eess-official-badge-container">
                        <a href="https://eess.online" target="_blank" class="eess-official-badge">
                            <span class="badge-icon-globe">
                                <svg style="width: 14px; height: 14px; fill: currentColor; margin-left: 6px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.53c-.26-.81-1-1.4-1.9-1.4h-1v-3c0-.55-.45-1-1-1h-6v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.4z"/>
                                </svg>
                            </span>
                            <span class="badge-text-main">  :</span>
                            <span class="badge-text-domain">eess.online</span>
                            <span class="badge-icon-link">
                                <svg style="width: 12px; height: 12px; fill: currentColor; margin-right: 6px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                </svg>
                            </span>
                        </a>
                    </div>

                    <!-- Branding logo/icon/text -->
                    <div class="eess-branding-header">
                        <div class="eess-branding-logo-box">
                            <div class="eess-logo-icon-col">
                                <svg class="mortarboard-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                    <path d="M5 13.18v4c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-4l-7 3.82-7-3.82z"/>
                                </svg>
                            </div>
                            <div class="eess-logo-text-col">
                                <span class="eess-logo-title">EESS</span>
                                <span class="eess-logo-subtitle">Educational Electronic Systems Services</span>
                            </div>
                        </div>
                        <div class="eess-branding-divider"></div>
                    </div>

                    <!-- Big Title -->
                    <div class="eess-main-headline">
                          <span class="underline-red"></span><br>  <span class="underline-red"></span>
                    </div>

                    <!-- About EESS box -->
                    <div class="eess-about-box">
                        <div class="eess-about-title">   EESS:</div>
                        <p class="eess-about-desc">
                             EESS         Academy                 <a href="https://eess.online" target="_blank">eess.online</a>.
                        </p>
                    </div>

                    <!-- Huge Watermark EESS -->
                    <div class="eess-watermark">EESS</div>
                </div>
            </div>
        </div>

        <!-- Multi-Step Password Recovery Modal Without OTP -->
        ' . (function() {
            $schools_list = SM_DB::get_schools() ?: array();
            $subjects_list = SM_DB::get_subjects() ?: array();
            $unique_subjects = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$subjects_list)));
            $nationalities = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ' ');

            $schools_options = '';
            foreach ((array)$schools_list as $sch) {
                $sch_name = is_object($sch) ? $sch->name : (is_array($sch) ? ($sch['name'] ?? '') : (string)$sch);
                if ($sch_name) {
                    $schools_options .= '<option value="' . esc_attr($sch_name) . '">' . esc_html($sch_name) . '</option>';
                }
            }

            $subject_options = '';
            foreach ($unique_subjects as $subj) {
                if ($subj) {
                    $subject_options .= '<option value="' . esc_attr($subj) . '">' . esc_html($subj) . '</option>';
                }
            }

            $nationality_options = '';
            foreach ($nationalities as $nat) {
                $nationality_options .= '<option value="' . esc_attr($nat) . '">' . esc_html($nat) . '</option>';
            }

            return '
        <div id="eess-forgot-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 500px;">
                <div class="eess-modal-header">
                    <h3>     Password</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseForgotModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <!-- Progress Bar Header -->
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 6px;">
                            <span id="eess-forgot-step-label"> 1  8: Email Address</span>
                            <span id="eess-forgot-step-pct">12%</span>
                        </div>
                        <div style="background: #e2e8f0; height: 6px; border-radius: 50px; overflow: hidden;">
                            <div id="eess-forgot-progress-bar" style="background: #2563eb; width: 12.5%; height: 100%; transition: width 0.3s ease;"></div>
                        </div>
                    </div>

                    <div id="eess-forgot-msg" class="eess-modal-msg"></div>

                    <!-- Step 1: Email -->
                    <div id="eess-forgot-step-1" class="eess-wizard-step active">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :  Email Address     .</p>
                        <div class="eess-form-group">
                            <div class="eess-float-container">
                                <input type="email" id="eess-forgot-email" class="eess-float-input" placeholder=" ">
                                <label for="eess-forgot-email" class="eess-float-label">Email Address  *</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
                            <button type="button" onclick="eessNextForgotStep(2)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 2: Employee ID -->
                    <div id="eess-forgot-step-2" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :    /      .</p>
                        <div class="eess-form-group">
                            <div class="eess-float-container">
                                <input type="text" id="eess-forgot-empid" class="eess-float-input" placeholder=" ">
                                <label for="eess-forgot-empid" class="eess-float-label">  / Job Number *</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(1)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessNextForgotStep(3)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 3: Institution / School -->
                    <div id="eess-forgot-step-3" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :   Organization   Academy      .</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-institution" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">--  Academy  / Organization  --</option>
                                <option value="Sportedia Sports Management System">Sportedia Sports Management System</option>
                                ' . $schools_options . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(2)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessNextForgotStep(4)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 4: Nationality -->
                    <div id="eess-forgot-step-4" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :     .</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-nationality" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">--  Gender --</option>
                                ' . $nationality_options . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(3)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessNextForgotStep(5)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 5: Role -->
                    <div id="eess-forgot-step-5" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :      .</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-role" class="eess-float-input" onchange="eessCheckRoleSubjectNeed()" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">--    --</option>
                                <option value="sm_teacher"> (Teacher)</option>
                                <option value="sm_coordinator">  (Subject Coordinator)</option>
                                <option value="sm_hod">  (Department Head)</option>
                                <option value="sm_supervisor">  (Educational Supervisor)</option>
                                <option value="sm_principal"> Academy  (School Manager)</option>
                                <option value="sm_discipline_supervisor">  / </option>
                                <option value="sm_activities_supervisor"> Active</option>
                                <option value="sm_transportation_supervisor">  No</option>
                                <option value="sm_system_admin">  (System Admin)</option>
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(4)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessNextForgotStep(6)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 6: Subject (Auto-skipped if not applicable) -->
                    <div id="eess-forgot-step-6" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :  Sport Activity   .</p>
                        <div class="eess-form-group">
                            <select id="eess-forgot-subject" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                                <option value="">--  Sport Activity  --</option>
                                ' . $subject_options . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(5)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessNextForgotStep(7)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 7: Date of Birth -->
                    <div id="eess-forgot-step-7" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :  Date of Birth     .</p>
                        <div class="eess-form-group">
                            <input type="date" id="eess-forgot-dob" class="eess-float-input" style="height: 44px; padding: 0 12px; font-size: 13px; font-weight: 700;">
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <button type="button" onclick="eessPrevForgotStep(6)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessVerifyIdentityFull()" id="btn-verify-identity" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem; background: #16a34a !important;"> Mother   &larr;</button>
                        </div>
                    </div>

                    <!-- Step 8: Create New Password -->
                    <div id="eess-forgot-step-8" class="eess-wizard-step">
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                            <h4 id="eess-forgot-welcome-msg" style="margin: 0; color: #166534; font-weight: 800; font-size: 13px;"> Confirm  !</h4>
                            <p style="margin: 4px 0 0 0; font-size: 11px; color: #15803d; line-height: 1.5;"> Password      .</p>
                        </div>

                        <!-- Live Password Rules List -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 15px; font-size: 11px;">
                            <div style="font-weight: 800; color: #334155; margin-bottom: 4px;"> Password :</div>
                            <div id="pwd-rule-len" style="color: #64748b;">•   8  40 </div>
                            <div id="pwd-rule-upper" style="color: #64748b;">•    (A-Z)   </div>
                            <div id="pwd-rule-lower" style="color: #64748b;">•    (a-z)   </div>
                            <div id="pwd-rule-num" style="color: #64748b;">•  (0-9)   </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px;">
                            <div class="eess-form-group" style="margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-forgot-pass" class="eess-float-input" placeholder=" " maxlength="40" oninput="eessLiveCheckPassword()">
                                    <label for="eess-forgot-pass" class="eess-float-label">Password  *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-forgot-pass\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="eess-form-group" style="margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-forgot-pass-conf" class="eess-float-input" placeholder=" " maxlength="40" oninput="eessLiveCheckPassword()">
                                    <label for="eess-forgot-pass-conf" class="eess-float-label">Confirm Password *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-forgot-pass-conf\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="button" onclick="eessSetNewPasswordAndLogin()" id="btn-save-new-pass" class="eess-btn-login" style="width: 100%; height: 42px; font-size: 0.9rem; background: #2563eb !important;">Save Password   </button>
                        </div>
                    </div>

                    <!-- Support Card Inside Modal -->
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; line-height: 1.6;">
                                   No     EESS    <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; text-decoration: underline;">info@eess.online</a>.
                    </div>
                </div>
            </div>
        </div>';
        })() . '

        <!-- Help & Support Modal -->
        <div id="eess-support-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 450px;">
                <div class="eess-modal-header">
                    <h3>  </h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseSupportModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin-top: 0;">           Password       Email Address  :</p>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0;">
                        <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; font-size: 16px; text-decoration: none;">info@eess.online</a>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.6;">            .</p>
                </div>
            </div>
        </div>

        <!-- Registration Wizard Modal (Without OTP) -->
        ' . (function() {
            $insts_list = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_institutions() : array();
            $subjects_list = SM_DB::get_subjects() ?: array();
            $unique_subjects = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$subjects_list)));
            $nationalities = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ' ');

            $schools_opts = '';
            foreach ((array)$insts_list as $inst_item) {
                $inst_name = is_object($inst_item) ? $inst_item->name : (is_array($inst_item) ? ($inst_item['name'] ?? '') : (string)$inst_item);
                if ($inst_name) {
                    $schools_opts .= '<option value="' . esc_attr($inst_name) . '">' . esc_html($inst_name) . '</option>';
                }
            }

            $subject_opts = '';
            foreach ($unique_subjects as $subj) {
                if ($subj) {
                    $subject_opts .= '<option value="' . esc_attr($subj) . '">' . esc_html($subj) . '</option>';
                }
            }

            $nat_opts = '';
            foreach ($nationalities as $nat) {
                $nat_opts .= '<option value="' . esc_attr($nat) . '">' . esc_html($nat) . '</option>';
            }

            return '
        <div id="eess-register-modal" class="eess-modal-overlay">
            <div class="eess-modal-dialog" style="max-width: 520px;">
                <div class="eess-modal-header">
                    <h3>    (  )</h3>
                    <button type="button" class="eess-modal-close" onclick="eessCloseRegisterModal()">&times;</button>
                </div>
                <div class="eess-modal-body">
                    <!-- Step Progress Bar -->
                    <div class="eess-step-progress-bar">
                        <div class="eess-step-node active" id="node-1">1</div>
                        <div class="eess-step-node" id="node-2">2</div>
                        <div class="eess-step-node" id="node-3">3</div>
                        <div class="eess-step-node" id="node-4">4</div>
                        <div class="eess-step-node" id="node-5">5</div>
                    </div>

                    <div id="eess-register-msg" class="eess-modal-msg"></div>

                    <!-- Step 1: Personal Info -->
                    <div id="eess-reg-step-1" class="eess-wizard-step active">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :  No No Date of Birth Gender.</p>
                        <div style="display: flex; gap: 12px; margin-bottom: 14px;">
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container">
                                    <input type="text" id="eess-reg-first-name" class="eess-float-input" placeholder=" ">
                                    <label for="eess-reg-first-name" class="eess-float-label">No  *</label>
                                </div>
                            </div>
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container">
                                    <input type="text" id="eess-reg-last-name" class="eess-float-input" placeholder=" ">
                                    <label for="eess-reg-last-name" class="eess-float-label">  *</label>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-bottom: 14px;">
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">Date of Birth *</label>
                                <input type="date" id="eess-reg-dob" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                            </div>
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">Gender *</label>
                                <select id="eess-reg-nationality" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                    <option value="">--  Gender --</option>
                                    ' . $nat_opts . '
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(2)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 2: Contact Info -->
                    <div id="eess-reg-step-2" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :     Phone Number.</p>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <div class="eess-float-container">
                                <input type="email" id="eess-reg-email" class="eess-float-input" placeholder=" ">
                                <label for="eess-reg-email" class="eess-float-label">Email Address  *</label>
                            </div>
                        </div>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <div class="eess-float-container">
                                <input type="text" id="eess-reg-phone" class="eess-float-input" placeholder=" ">
                                <label for="eess-reg-phone" class="eess-float-label">Phone Number  *</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(1)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessGoToRegStep(3)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 3: Employment & Institution -->
                    <div id="eess-reg-step-3" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :     Organization /Academy .</p>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <div class="eess-float-container">
                                <input type="text" id="eess-reg-emp-num" class="eess-float-input" placeholder=" ">
                                <label for="eess-reg-emp-num" class="eess-float-label">  /   *</label>
                            </div>
                        </div>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">Organization  / Academy  *</label>
                            <select id="eess-reg-institution" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                <option value="Sportedia Sports Management System">Sportedia Sports Management System</option>
                                ' . $schools_opts . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(2)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessGoToRegStep(4)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">  Next &larr;</button>
                        </div>
                    </div>

                    <!-- Step 4: Role & Subject -->
                    <div id="eess-reg-step-4" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :    Sport Activity .</p>
                        <div class="eess-form-group" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">    *</label>
                            <select id="eess-reg-role" class="eess-form-input" onchange="eessOnRegRoleChange()" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                <option value="sm_teacher"> (Teacher)</option>
                                <option value="sm_coordinator">  (Subject Coordinator)</option>
                                <option value="sm_hod">  (Department Head)</option>
                                <option value="sm_supervisor">  (Educational Supervisor)</option>
                                <option value="sm_principal"> Academy  (School Manager)</option>
                                <option value="sm_discipline_supervisor">  / </option>
                                <option value="sm_activities_supervisor"> Active</option>
                                <option value="sm_transportation_supervisor">  No</option>
                                <option value="sm_bus_supervisor"> </option>
                                <option value="sm_clinic"> </option>
                                <option value="sm_parent">  (Parent)</option>
                            </select>
                        </div>
                        <div class="eess-form-group" id="eess-reg-subject-box" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px;">Sport Activity   *</label>
                            <select id="eess-reg-subject" class="eess-form-input" style="height: 42px; padding: 0 10px; font-size: 13px; font-weight: 700;">
                                <option value="">--  Sport Activity --</option>
                                ' . $subject_opts . '
                            </select>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(3)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" onclick="eessGoToRegStep(5)" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem;">   &larr;</button>
                        </div>
                    </div>

                    <!-- Step 5: Review & Password Creation -->
                    <div id="eess-reg-step-5" class="eess-wizard-step">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6;"> :  Password    Send.</p>

                        <!-- Summary Card -->
                        <div id="eess-reg-summary-card" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 11px; line-height: 1.7; color: #334155;"></div>

                        <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-reg-pass" class="eess-float-input" placeholder=" " maxlength="40">
                                    <label for="eess-reg-pass" class="eess-float-label">Password *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-reg-pass\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="eess-form-group" style="flex: 1; margin-bottom: 0;">
                                <div class="eess-float-container eess-password-wrapper">
                                    <input type="password" id="eess-reg-pass-conf" class="eess-float-input" placeholder=" " maxlength="40">
                                    <label for="eess-reg-pass-conf" class="eess-float-label">Confirm Password *</label>
                                    <button type="button" class="eess-toggle-eye" onclick="eessTogglePassVisibility(\'eess-reg-pass-conf\', this)">
                                        <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <button type="button" onclick="eessGoToRegStep(4)" class="eess-btn-reset-pwd" style="width: auto; height: 38px; padding: 0 16px; font-size: 0.85rem; background: #64748b !important;">&rarr; Previous</button>
                            <button type="button" id="btn-submit-reg-final" onclick="eessRegisterSubmitFinal()" class="eess-btn-login" style="width: auto; height: 38px; padding: 0 20px; font-size: 0.85rem; background: #16a34a !important;">Send   </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        })() . '

        <!-- Custom JS Client logic for Password Recovery and Registration -->
        <script>
        function eessShowForgotMsg(text, isError) {
            const el = document.getElementById(\'eess-forgot-msg\');
            el.innerText = text;
            el.style.display = \'block\';
            if (isError) {
                el.className = \'eess-modal-msg error\';
            } else {
                el.className = \'eess-modal-msg success\';
            }
        }

        function eessShowRegMsg(text, isError) {
            const el = document.getElementById(\'eess-register-msg\');
            el.innerText = text;
            el.style.display = \'block\';
            if (isError) {
                el.className = \'eess-modal-msg error\';
            } else {
                el.className = \'eess-modal-msg success\';
            }
        }

        // Modal triggers
        function eessOpenForgotModal() {
            document.getElementById(\'eess-forgot-modal\').style.display = \'flex\';
            eessGoToForgotStep(1);
        }
        function eessCloseForgotModal() {
            document.getElementById(\'eess-forgot-modal\').style.display = \'none\';
        }

        function eessOpenSupportModal() {
            document.getElementById(\'eess-support-modal\').style.display = \'flex\';
        }
        function eessCloseSupportModal() {
            document.getElementById(\'eess-support-modal\').style.display = \'none\';
        }

        function eessOpenRegisterModal() {
            document.getElementById(\'eess-register-modal\').style.display = \'flex\';
            eessGoToRegStep(1);
        }
        function eessCloseRegisterModal() {
            document.getElementById(\'eess-register-modal\').style.display = \'none\';
        }

        let eessVerifiedResetToken = \'\';

        // Recovery Step Wizard Navigation & Validation
        function eessCheckRoleSubjectNeed() {
            const role = document.getElementById(\'eess-forgot-role\').value;
            // Role requires subject if teacher, coordinator, HOD
            return (role === \'sm_teacher\' || role === \'sm_coordinator\' || role === \'sm_hod\');
        }

        function eessGoToForgotStep(stepNum) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';

            // Auto skip step 6 (Subject) if role does not require subject
            if (stepNum === 6 && !eessCheckRoleSubjectNeed()) {
                stepNum = 7;
            }

            for (let i = 1; i <= 8; i++) {
                const el = document.getElementById(\'eess-forgot-step-\' + i);
                if (el) el.style.display = (i === stepNum) ? \'block\' : \'none\';
            }

            // Update Progress Bar & Header
            const stepLabels = {
                1: \' 1  8: Email Address\',
                2: \' 2  8:  \',
                3: \' 3  8: Organization  / Academy \',
                4: \' 4  8: Gender\',
                5: \' 5  8:  \',
                6: \' 6  8: Sport Activity \',
                7: \' 7  8: Date of Birth\',
                8: \' 8  8:  Password \'
            };

            const pct = Math.round((stepNum / 8) * 100);
            document.getElementById(\'eess-forgot-step-label\').innerText = stepLabels[stepNum] || \'\';
            document.getElementById(\'eess-forgot-step-pct\').innerText = pct + \'%\';
            document.getElementById(\'eess-forgot-progress-bar\').style.width = pct + \'%\';
        }

        function eessNextForgotStep(nextStep) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';

            // Validate Current Step Before Advancing
            if (nextStep === 2) {
                const email = document.getElementById(\'eess-forgot-email\').value.trim();
                if (!email || !email.includes(\'@\')) {
                    eessShowForgotMsg(\'    .\', true);
                    return;
                }
            } else if (nextStep === 3) {
                const empId = document.getElementById(\'eess-forgot-empid\').value.trim();
                if (!empId) {
                    eessShowForgotMsg(\'     .\', true);
                    return;
                }
            } else if (nextStep === 4) {
                const inst = document.getElementById(\'eess-forgot-institution\').value;
                if (!inst) {
                    eessShowForgotMsg(\'  Organization   Academy   .\', true);
                    return;
                }
            } else if (nextStep === 5) {
                const nat = document.getElementById(\'eess-forgot-nationality\').value;
                if (!nat) {
                    eessShowForgotMsg(\'  Gender .\', true);
                    return;
                }
            } else if (nextStep === 6) {
                const role = document.getElementById(\'eess-forgot-role\').value;
                if (!role) {
                    eessShowForgotMsg(\'   .\', true);
                    return;
                }
            } else if (nextStep === 7) {
                if (eessCheckRoleSubjectNeed()) {
                    const subj = document.getElementById(\'eess-forgot-subject\').value;
                    if (!subj) {
                        eessShowForgotMsg(\'  Sport Activity   .\', true);
                        return;
                    }
                }
            }

            eessGoToForgotStep(nextStep);
        }

        function eessPrevForgotStep(prevStep) {
            document.getElementById(\'eess-forgot-msg\').style.display = \'none\';
            if (prevStep === 6 && !eessCheckRoleSubjectNeed()) {
                prevStep = 5;
            }
            eessGoToForgotStep(prevStep);
        }

        // Complete Verification Without OTP
        function eessVerifyIdentityFull() {
            const dob = document.getElementById(\'eess-forgot-dob\').value;
            if (!dob) {
                eessShowForgotMsg(\'  Date of Birth  .\', true);
                return;
            }

            const btn = document.getElementById(\'btn-verify-identity\');
            btn.disabled = true;
            btn.innerText = \'  Mother...\';

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_verify_identity\');
            data.append(\'email\', document.getElementById(\'eess-forgot-email\').value.trim());
            data.append(\'emp_id\', document.getElementById(\'eess-forgot-empid\').value.trim());
            data.append(\'institution\', document.getElementById(\'eess-forgot-institution\').value);
            data.append(\'nationality\', document.getElementById(\'eess-forgot-nationality\').value);
            data.append(\'role\', document.getElementById(\'eess-forgot-role\').value);
            data.append(\'subject\', document.getElementById(\'eess-forgot-subject\').value);
            data.append(\'dob\', dob);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                btn.disabled = false;
                btn.innerText = \' Mother   ←\';

                if (res.success) {
                    eessVerifiedResetToken = res.data.reset_token;
                    document.getElementById(\'eess-forgot-welcome-msg\').innerText = \'Welcome  \' + res.data.display_name + \'!\';
                    eessGoToForgotStep(8);
                } else {
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Live Password Rules Check
        function eessLiveCheckPassword() {
            const pass = document.getElementById(\'eess-forgot-pass\').value;

            const lenOk = pass.length >= 8 && pass.length <= 40;
            const upperOk = /[A-Z]/.test(pass);
            const lowerOk = /[a-z]/.test(pass);
            const numOk = /[0-9]/.test(pass);

            document.getElementById(\'pwd-rule-len\').style.color = lenOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-len\').innerText = (lenOk ? \'✓ \' : \'• \') + \'  8  40 \';

            document.getElementById(\'pwd-rule-upper\').style.color = upperOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-upper\').innerText = (upperOk ? \'✓ \' : \'• \') + \'   (A-Z)   \';

            document.getElementById(\'pwd-rule-lower\').style.color = lowerOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-lower\').innerText = (lowerOk ? \'✓ \' : \'• \') + \'   (a-z)   \';

            document.getElementById(\'pwd-rule-num\').style.color = numOk ? \'#16a34a\' : \'#64748b\';
            document.getElementById(\'pwd-rule-num\').innerText = (numOk ? \'✓ \' : \'• \') + \' (0-9)   \';
        }

        // Set Password & Auto-login
        function eessSetNewPasswordAndLogin() {
            const pass = document.getElementById(\'eess-forgot-pass\').value;
            const conf = document.getElementById(\'eess-forgot-pass-conf\').value;

            if (!pass || !conf) {
                eessShowForgotMsg(\'  Password Confirm.\', true);
                return;
            }

            if (pass !== conf) {
                eessShowForgotMsg(\'   .\', true);
                return;
            }

            const btn = document.getElementById(\'btn-save-new-pass\');
            btn.disabled = true;
            btn.innerText = \' Save  ...\';

            const data = new FormData();
            data.append(\'action\', \'eess_forgot_set_password\');
            data.append(\'reset_token\', eessVerifiedResetToken);
            data.append(\'password\', pass);
            data.append(\'password_conf\', conf);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessShowForgotMsg(res.data.message || \' Save   !\', false);
                    setTimeout(() => {
                        window.location.href = res.data.redirect_url || \'' . home_url('/sm-admin') . '\';
                    }, 1000);
                } else {
                    btn.disabled = false;
                    btn.innerText = \'Save Password   \';
                    eessShowForgotMsg(res.data, true);
                }
            });
        }

        // Password Visibility Toggle
        function eessTogglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            if (input.type === \'password\') {
                input.type = \'text\';
                btn.style.color = \'#000000\';
            } else {
                input.type = \'password\';
                btn.style.color = \'#64748b\';
            }
        }

        function eessOnRegRoleChange() {
            const role = document.getElementById(\'eess-reg-role\').value;
            const subBox = document.getElementById(\'eess-reg-subject-box\');
            if (role === \'sm_teacher\' || role === \'sm_coordinator\' || role === \'sm_hod\') {
                subBox.style.display = \'block\';
            } else {
                subBox.style.display = \'none\';
            }
        }

        // Registration Wizard navigation
        function eessGoToRegStep(stepNum) {
            document.getElementById(\'eess-register-msg\').style.display = \'none\';

            // Validation per step
            if (stepNum > 1) {
                const fn = document.getElementById(\'eess-reg-first-name\').value.trim();
                const ln = document.getElementById(\'eess-reg-last-name\').value.trim();
                const dob = document.getElementById(\'eess-reg-dob\').value;
                const nat = document.getElementById(\'eess-reg-nationality\').value;
                if (!fn || !ln || !dob || !nat) {
                    eessShowRegMsg(\'    Personal Information (No No Gender).\', true);
                    return;
                }
            }

            if (stepNum > 2) {
                const email = document.getElementById(\'eess-reg-email\').value.trim();
                const phone = document.getElementById(\'eess-reg-phone\').value.trim();
                if (!email || !email.includes(\'@\') || !phone) {
                    eessShowRegMsg(\'     Phone Number.\', true);
                    return;
                }
            }

            if (stepNum > 3) {
                const empNum = document.getElementById(\'eess-reg-emp-num\').value.trim();
                const inst = document.getElementById(\'eess-reg-institution\').value;
                if (!empNum || !inst) {
                    eessShowRegMsg(\'     Organization /Academy .\', true);
                    return;
                }
            }

            if (stepNum > 4) {
                const role = document.getElementById(\'eess-reg-role\').value;
                const subj = document.getElementById(\'eess-reg-subject\').value;
                if (!role) {
                    eessShowRegMsg(\'   .\', true);
                    return;
                }
                if ((role === \'sm_teacher\' || role === \'sm_coordinator\' || role === \'sm_hod\') && !subj) {
                    eessShowRegMsg(\'  Sport Activity  .\', true);
                    return;
                }
            }

            // Update Summary on Step 5
            if (stepNum === 5) {
                const fn = document.getElementById(\'eess-reg-first-name\').value.trim();
                const ln = document.getElementById(\'eess-reg-last-name\').value.trim();
                const email = document.getElementById(\'eess-reg-email\').value.trim();
                const empNum = document.getElementById(\'eess-reg-emp-num\').value.trim();
                const inst = document.getElementById(\'eess-reg-institution\').value;
                const roleText = document.getElementById(\'eess-reg-role\').options[document.getElementById(\'eess-reg-role\').selectedIndex].text;
                const subj = document.getElementById(\'eess-reg-subject\').value;

                document.getElementById(\'eess-reg-summary-card\').innerHTML = `
                    <div><strong>Full Name:</strong> ${fn} ${ln}</div>
                    <div><strong>Email Address:</strong> ${email}</div>
                    <div><strong> :</strong> ${empNum}</div>
                    <div><strong>Organization /Academy :</strong> ${inst}</div>
                    <div><strong> :</strong> ${roleText} ${subj ? \' (: \' + subj + \')\' : \'\'}</div>
                `;
            }

            for (let i = 1; i <= 5; i++) {
                document.getElementById(\'eess-reg-step-\' + i).className = i === stepNum ? \'eess-wizard-step active\' : \'eess-wizard-step\';

                const node = document.getElementById(\'node-\' + i);
                if (node) {
                    node.className = \'eess-step-node\';
                    if (i === stepNum) {
                        node.classList.add(\'active\');
                    } else if (i < stepNum) {
                        node.classList.add(\'completed\');
                        node.innerText = \'✓\';
                    } else {
                        node.innerText = i;
                    }
                }
            }
        }

        // Final Submit
        function eessRegisterSubmitFinal() {
            const firstName = document.getElementById(\'eess-reg-first-name\').value.trim();
            const lastName = document.getElementById(\'eess-reg-last-name\').value.trim();
            const dob = document.getElementById(\'eess-reg-dob\').value;
            const nationality = document.getElementById(\'eess-reg-nationality\').value;
            const email = document.getElementById(\'eess-reg-email\').value.trim();
            const phone = document.getElementById(\'eess-reg-phone\').value.trim();
            const empNum = document.getElementById(\'eess-reg-emp-num\').value.trim();
            const institution = document.getElementById(\'eess-reg-institution\').value;
            const role = document.getElementById(\'eess-reg-role\').value;
            const subject = document.getElementById(\'eess-reg-subject\').value;
            const pass = document.getElementById(\'eess-reg-pass\').value;
            const conf = document.getElementById(\'eess-reg-pass-conf\').value;

            if (!pass || !conf) {
                eessShowRegMsg(\'  Password Confirm.\', true);
                return;
            }
            if (pass !== conf) {
                eessShowRegMsg(\'   .\', true);
                return;
            }

            const btn = document.getElementById(\'btn-submit-reg-final\');
            btn.disabled = true;
            btn.innerText = \' Send  ...\';

            const data = new FormData();
            data.append(\'action\', \'eess_register_submit\');
            data.append(\'first_name\', firstName);
            data.append(\'last_name\', lastName);
            data.append(\'dob\', dob);
            data.append(\'nationality\', nationality);
            data.append(\'email\', email);
            data.append(\'phone\', phone);
            data.append(\'emp_num\', empNum);
            data.append(\'institution\', institution);
            data.append(\'school\', institution);
            data.append(\'role\', role);
            data.append(\'subject\', subject);
            data.append(\'password\', pass);
            data.append(\'password_conf\', conf);

            fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'POST\', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    eessShowRegMsg(res.data || \' Send         .\', false);
                    setTimeout(() => {
                        eessCloseRegisterModal();
                        location.reload();
                    }, 2500);
                } else {
                    btn.disabled = false;
                    btn.innerText = \'Send   \';
                    eessShowRegMsg(res.data, true);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerText = \'Send   \';
                eessShowRegMsg(\'An error occurred  No .\', true);
            });
        }
        </script>
        ';
        return $output;
    }


    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        if ($this->eess_is_mobile_device()) {
            return $this->eess_render_mobile_lesson_prep();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';

        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');

        // Centralized security & visibility control check for active tab
        if (!$is_admin) {
            $is_allowed = SM_Settings::is_section_visible($active_tab) && SM_Settings::user_has_module_capability($active_tab);
            if (!$is_allowed) {
                return SM_Settings::get_access_restricted_html();
            }
        }

        // Data Preparation based on tab
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);
        $is_student = in_array('sm_student', $roles);
        $is_parent = in_array('sm_parent', $roles);

        // Security / Capability check for tabs - synchronize with Central Sidebar Section Visibility
        if (!$is_admin && !SM_Settings::is_section_visible($active_tab)) {
            $active_tab = 'summary';
        }

        // Fetch data based on tab
        switch ($active_tab) {
            case 'summary':
                if ($is_student) {
                    $student = SM_DB::get_student_by_parent($user->ID);
                    $student_id = $student ? $student->id : 0;
                    $stats = SM_DB::get_student_stats($student_id);
                    $student_assignments = SM_DB::get_assignments($user->ID);

                    // Find assigned supervisor
                    $supervisor = null;
                    if ($student) {
                        $supervisors = get_users(array('role' => 'sm_supervisor'));
                        foreach ($supervisors as $s) {
                            $supervised = get_user_meta($s->ID, 'sm_supervised_classes', true);
                            if (is_array($supervised) && in_array($student->class_name . '|' . $student->section, $supervised)) {
                                $supervisor = $s;
                                break;
                            }
                        }
                    }
                } else {
                    $stats = SM_DB::get_statistics($is_teacher && !$is_admin ? ['teacher_id' => $user->ID] : []);
                }
                break;

            case 'students':
                $args = array();
                if (isset($_GET['student_search'])) $args['search'] = sanitize_text_field($_GET['student_search']);
                if (isset($_GET['class_filter'])) $args['class_name'] = sanitize_text_field($_GET['class_filter']);
                if (isset($_GET['section_filter'])) $args['section'] = sanitize_text_field($_GET['section_filter']);
                if (isset($_GET['teacher_filter']) && !empty($_GET['teacher_filter'])) $args['teacher_id'] = intval($_GET['teacher_filter']);
                if ($is_teacher && !$is_admin) $args['teacher_id'] = $user->ID;
                $students = SM_DB::get_students($args);
                break;

            case 'stats':
                $filters = array();
                if ($is_parent || $is_student) {
                    $my_stu = SM_DB::get_students_by_parent($user->ID);
                    $filters['student_id'] = isset($_GET['student_id']) ? intval($_GET['student_id']) : ($my_stu[0]->id ?? 0);
                } else {
                    if (isset($_GET['student_filter'])) $filters['student_id'] = intval($_GET['student_filter']);
                    if ($is_teacher && !$is_admin) $filters['teacher_id'] = $user->ID;

                    if (isset($_GET['class_filter'])) $filters['class_name'] = sanitize_text_field($_GET['class_filter']);
                    if (isset($_GET['section_filter'])) $filters['section'] = sanitize_text_field($_GET['section_filter']);
                    if (isset($_GET['student_search'])) $filters['search'] = sanitize_text_field($_GET['student_search']);
                }
                if (isset($_GET['start_date'])) $filters['start_date'] = sanitize_text_field($_GET['start_date']);
                if (isset($_GET['end_date'])) $filters['end_date'] = sanitize_text_field($_GET['end_date']);
                if (isset($_GET['type_filter'])) $filters['type'] = sanitize_text_field($_GET['type_filter']);

                // If no filters are applied, limit to latest 20 for quick access
                $is_filtering = !empty($_GET['student_search']) || !empty($_GET['class_filter']) || !empty($_GET['section_filter']) || !empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['type_filter']);
                if (!$is_filtering && !$is_parent) {
                    $filters['limit'] = 20;
                }

                $records = SM_DB::get_records($filters);
                break;

            case 'reports':
                $stats = SM_DB::get_statistics();
                $records = SM_DB::get_records();
                break;

            case 'teacher-reports':
                $records = SM_DB::get_records(array('status' => 'pending'));
                break;

            case 'confiscated':
                $records = SM_DB::get_confiscated_items();
                break;

            case 'attendance':
                $attendance_date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : current_time('Y-m-d');
                $attendance_summary = SM_DB::get_attendance_summary($attendance_date);
                break;
        }

        ob_start();
        include SM_PLUGIN_DIR . 'templates/public-admin-panel.php';
        return ob_get_clean();
    }

    public function login_failed($username) {
        SM_Logger::log(' Login', "   : $username");
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function log_successful_login($user_login, $user) {
        SM_Logger::log('  ', ": $user_login (ID: {$user->ID})");
    }

    public function ajax_get_student() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        $code = sanitize_text_field($_POST['code']);
        $student = SM_DB::get_student_by_code($code);
        if ($student) {
            wp_send_json_success($student);
        } else {
            wp_send_json_error('Student not found');
        }
    }

    public function ajax_search_students() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        $query = sanitize_text_field($_POST['query']);
        if (strlen($query) < 2) wp_send_json_success(array());

        $args = array('search' => $query);
        // Teachers can search all students as per new requirements
        $students = SM_DB::get_students($args);
        wp_send_json_success($students);
    }

    public function ajax_get_student_intelligence() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $student_id = intval($_POST['student_id']);
        if (!$student_id) wp_send_json_error('Invalid ID');

        $stats = SM_DB::get_student_stats($student_id);
        $records = SM_DB::get_records(array('student_id' => $student_id));
        $latest = array_slice($records, 0, 3); // Get 3 latest records
        $student = SM_DB::get_student_by_id($student_id);

        // Fetch Clinic Records for Student Timeline
        $clinic_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_clinic WHERE student_id = %d ORDER BY created_at DESC LIMIT 10",
            $student_id
        ));

        // Fetch Attendance Summary for Student Timeline
        $attendance_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_attendance WHERE student_id = %d ORDER BY date DESC LIMIT 10",
            $student_id
        ));

        // Build Chronological Academic Year Student Activity Timeline
        $timeline = array();

        if (!empty($clinic_records)) {
            foreach ($clinic_records as $c) {
                $timeline[] = array(
                    'module'  => 'clinic',
                    'title'   => '  : ' . ($c->diagnosis ?: $c->symptoms ?: ' '),
                    'date'    => $c->created_at ?: current_time('mysql'),
                    'details' => ' : ' . ($c->action_taken ?: ' ') . ' · : ' . ($c->temperature ?: '')
                );
            }
        }

        if (!empty($records)) {
            foreach ($records as $r) {
                $timeline[] = array(
                    'module'  => 'behavior',
                    'title'   => ' No : ' . ($r->type ?: ' '),
                    'date'    => $r->created_at ?: current_time('mysql'),
                    'details' => ': ' . ($r->degree ?: 1) . ' · : ' . ($r->details ?: 'No   ')
                );
            }
        }

        if (!empty($attendance_records)) {
            foreach ($attendance_records as $att) {
                if ($att->status !== 'present') {
                    $status_lbl = ($att->status === 'absent') ? ' ' : (($att->status === 'late') ? ' ' : ' ');
                    $timeline[] = array(
                        'module'  => 'attendance',
                        'title'   => '  : ' . $status_lbl,
                        'date'    => $att->date . ' 08:00:00',
                        'details' => ': ' . $att->date . ' · Status : ' . $status_lbl
                    );
                }
            }
        }

        // Sort timeline descending by date
        usort($timeline, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $actions = SM_Settings::get_disciplinary_actions();
        $last_action_index = 0;
        if (!empty($stats['last_action'])) {
            $last_action_index = array_search($stats['last_action'], $actions);
            if ($last_action_index === false) $last_action_index = 0;
        }

        wp_send_json_success(array(
            'student'              => $student,
            'stats'                => $stats,
            'recent'               => $latest,
            'clinic'               => $clinic_records,
            'timeline'             => array_slice($timeline, 0, 15),
            'labels'               => SM_Settings::get_violation_types(),
            'disciplinary_actions' => $actions,
            'last_action_index'    => (int)$last_action_index,
            'is_admin'             => current_user_can('manage_options') || current_user_can('_'),
            'photo_url'            => $student ? $student->photo_url : ''
        ));
    }

    public function ajax_refresh_dashboard() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $stats = SM_DB::get_statistics();
        $records = SM_DB::get_records();
        $logs = SM_Logger::get_logs(50);

        wp_send_json_success(array(
            'stats' => $stats,
            'records' => $records,
            'logs' => $logs,
            'unread_messages' => 0,
            'violation_labels' => SM_Settings::get_violation_types(),
            'severity_labels' => SM_Settings::get_severities()
        ));
    }

    public function ajax_save_record() {
        if (!is_user_logged_in() || (!current_user_can('_') && !current_user_can('manage_options'))) {
            wp_send_json_error(' No  No  .');
        }
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_record_action')) {
            wp_send_json_error('  Mother .');
        }

        $raw_student_ids = sanitize_text_field($_POST['student_ids'] ?? '');
        $student_ids = array_filter(array_map('intval', explode(',', $raw_student_ids)));

        if (empty($student_ids)) {
            wp_send_json_error('  No   .');
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $last_record_id = 0;
        $count = 0;

        foreach ($student_ids as $sid) {
            $data = $_POST;
            $data['student_id'] = $sid;
            $rid = SM_DB::add_record($data, true); // Skip individual logs
            if ($rid) {
                $last_record_id = $rid;
                $count++;
                if (class_exists('SM_Notifications')) {
                    SM_Notifications::send_violation_alert($rid);
                }
            }
        }

        if ($count > 0) {
            $wpdb->query('COMMIT');
            SM_Logger::log('  ', "    ($count)  No .");
            wp_send_json_success(array(
                'count' => $count,
                'record_id' => $last_record_id,
                'message' => "     ($count)  No .",
                'print_url' => admin_url('admin-ajax.php?action=sm_print&print_type=single_violation&record_id=' . $last_record_id)
            ));
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('    .');
        }
    }

    public function ajax_update_student_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_photo_nonce'], 'sm_photo_action')) wp_send_json_error('Security check failed');

        $user_id = get_current_user_id();
        $student_id = intval($_POST['student_id']);

        // Security: Parent can only update their children, Admin can update anyone
        if (!current_user_can('_No')) {
            $my_children = SM_DB::get_students_by_parent($user_id);
            $is_mine = false;
            foreach ($my_children as $child) {
                if ($child->id == $student_id) $is_mine = true;
            }
            if (!$is_mine) wp_send_json_error('Permission denied');
        }

        if (empty($_FILES['student_photo'])) wp_send_json_error('No file provided');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('student_photo', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        $photo_url = wp_get_attachment_url($attachment_id);
        $student_id = intval($_POST['student_id']);

        SM_DB::update_student_photo($student_id, $photo_url);
        wp_send_json_success(array('photo_url' => $photo_url));
    }



    public function ajax_update_record_status() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $record_id = intval($_POST['record_id']);
        $status = sanitize_text_field($_POST['status']);

        if (SM_DB::update_record_status($record_id, $status)) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }


    public function ajax_add_student() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_students = current_user_can('_No') || current_user_can('manage_options') || current_user_can('manage_students') || in_array('sm_principal', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$can_manage_students) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_add_student') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) wp_send_json_error('Security check failed');

        $saved_id = EESS_Student_Data_Service::process_and_save_student($_POST);
        if (is_wp_error($saved_id)) {
            wp_send_json_error($saved_id->get_error_message());
        } else {
            wp_send_json_success($saved_id);
        }
    }

    public function ajax_update_student() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_students = current_user_can('_No') || current_user_can('manage_options') || current_user_can('manage_students') || in_array('sm_principal', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$can_manage_students) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_add_student') && !wp_verify_nonce($nonce, 'sm_photo_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id'] ?? ($_POST['id'] ?? 0));
        if ($student_id > 0) {
            $user_scope = EESS_Org_Helper::get_user_scope();
            if (!$user_scope['unrestricted']) {
                global $wpdb;
                $st_sch = $wpdb->get_var($wpdb->prepare("SELECT school_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
                if ($st_sch && !in_array(intval($st_sch), $user_scope['schools'], true)) {
                    wp_send_json_error(' No  No Edit  No   .');
                }
            }
        }

        $saved_id = EESS_Student_Data_Service::process_and_save_student($_POST);
        if (is_wp_error($saved_id)) {
            wp_send_json_error($saved_id->get_error_message());
        } else {
            wp_send_json_success($saved_id);
        }
    }

    public function ajax_delete_student() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_students = current_user_can('_No') || current_user_can('manage_options') || current_user_can('manage_students') || in_array('sm_principal', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$can_manage_students) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_delete_student')) wp_send_json_error('Security check failed');

        $student_id = intval($_POST['student_id']);
        $student = SM_DB::get_student_by_id($student_id);

        if ($student && SM_DB::delete_student($student_id)) {
            SM_Logger::log('Delete No', " Delete No: {$student->name} (: {$student->student_code})");
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }


    public function ajax_delete_record() {
        $user_roles = (array) wp_get_current_user()->roles;
        $can_delete = current_user_can('manage_options') || current_user_can('_') || in_array('sm_principal', $user_roles) || in_array('sm_system_admin', $user_roles);
        if (!$can_delete) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check failed');

        $record_id = intval($_POST['record_id']);
        $record = SM_DB::get_record_by_id($record_id);

        if ($record && SM_DB::delete_record($record_id)) {
            SM_Logger::log('Delete ', " Delete  ID: $record_id No ID: {$record->student_id}");
            wp_send_json_success('Deleted');
        } else {
            wp_send_json_error('Failed to delete');
        }
    }

    public function ajax_get_counts() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(array(
            'pending_reports' => intval(SM_DB::get_pending_reports_count()),
            'expired_items' => intval(SM_DB::get_expired_items_count())
        ));
    }

    public function ajax_add_parent() {
        if (!current_user_can('_Users')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) wp_send_json_error('Security check failed');

        $raw_uname = $_POST['user_login'] ?? ($_POST['employee_number'] ?? ($_POST['employee_id'] ?? ($_POST['username'] ?? '')));
        $username = sanitize_user($raw_uname);
        $email = sanitize_email($_POST['user_email']);
        if (empty($email)) $email = $username . '@parent.local';

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => 'sm_parent'
        ));

        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
        else {
            SM_Logger::log('Add  ', "     : {$_POST['display_name']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_add_user() {
        if (!current_user_can('_Users')) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_user_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local');

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => sanitize_text_field($_POST['user_role'])
        );
        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
        else {
            if (!empty($_POST['specialization'])) {
                update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
            }
            $emp_num = sanitize_text_field($_POST['employee_number'] ?? '');
            if (empty($emp_num) && class_exists('EESS_ID_Code_Service')) {
                $inst_id = !empty($_POST['institution_id']) ? intval($_POST['institution_id']) : 1;
                $emp_num = EESS_ID_Code_Service::generate_employee_code($inst_id);
            }
            if (!empty($emp_num)) {
                update_user_meta($user_id, 'eess_employee_number', $emp_num);
            }
            if (isset($_POST['department'])) {
                update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
            }
            if (isset($_POST['appointment_year'])) {
                $app_yr = intval($_POST['appointment_year']);
                if ($app_yr >= 1970 && $app_yr <= intval(date('Y'))) {
                    update_user_meta($user_id, 'eess_appointment_year', $app_yr);
                    update_user_meta($user_id, 'sm_appointment_year', $app_yr);
                }
            }

            $inst_id = !empty($_POST['institution_id']) ? intval($_POST['institution_id']) : (!empty($_POST['institution']) ? intval($_POST['institution']) : 0);
            if ($inst_id > 0) {
                global $wpdb;
                $inst_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_institutions WHERE id = %d", $inst_id));
                if ($inst_name) {
                    update_user_meta($user_id, 'eess_school_name', $inst_name);
                    update_user_meta($user_id, 'eess_institution_id', $inst_id);
                    $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                    $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                        'user_id' => $user_id,
                        'institution_id' => $inst_id,
                        'school_id' => null
                    ));
                }
            }

            if (!empty($_FILES['profile_photo']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('profile_photo', 0);
                if (!is_wp_error($attachment_id)) {
                    $photo_url = wp_get_attachment_url($attachment_id);
                    update_user_meta($user_id, 'eess_profile_photo', $photo_url);
                }
            }
            clean_user_cache($user_id);
            wp_cache_flush();
            SM_Logger::log('Add  ', "   : {$_POST['display_name']} : {$_POST['user_role']}");
            wp_send_json_success($user_id);
        }
    }

    public function ajax_hr_add_employee() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

        if (!$is_admin && !$is_sys_admin && !$is_hr) {
            wp_send_json_error('Unauthorized  .');
        }

        if (!wp_verify_nonce($_POST['sm_nonce'], 'eess_hr_add_employee_nonce')) {
            wp_send_json_error(' No   Update .');
        }

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local');

        if (username_exists($username)) {
            wp_send_json_error('Username    .');
        }

        if (email_exists($email)) {
            wp_send_json_error('Email Address    .');
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $_POST['user_pass'],
            'role' => sanitize_text_field($_POST['user_role'])
        );

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Save as pending!
        update_user_meta($user_id, 'eess_approval_status', 'pending');
        update_user_meta($user_id, 'eess_employee_number', sanitize_text_field($_POST['employee_number']));
        update_user_meta($user_id, 'eess_school_name', sanitize_text_field($_POST['institution']));
        update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        // Handle profile photo upload if provided
        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
            }
        }

        clean_user_cache($user_id);
        wp_cache_flush();

        SM_Logger::log('Add  Pending', "    Pending : {$_POST['display_name']} : {$_POST['user_role']}");
        wp_send_json_success(array('user_id' => $user_id));
    }

    public function ajax_export_employees_excel() {
        if (!is_user_logged_in()) wp_die('Unauthorized');
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_hr = current_user_can('manage_options') || current_user_can('manage_hr') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_hr', $user_roles);
        if (!$can_manage_hr) wp_die('Unauthorized permissions');

        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'eess_hr_add_employee_nonce')) {
            wp_die('Security check failed');
        }

        $role_map = array(
            'administrator' => '  ()',
            'sm_system_admin' => '  ',
            'sm_principal' => ' Academy ',
            'sm_supervisor' => ' ',
            'sm_coordinator' => ' ',
            'sm_hod' => ' ',
            'sm_teacher' => '',
            'sm_discipline_supervisor' => '  / ',
            'sm_activities_supervisor' => ' Active',
            'sm_transportation_supervisor' => '  No',
            'sm_bus_supervisor' => ' ',
            'sm_clinic' => ' ',
            'sm_hr' => '  (HR)'
        );

        $employees = get_users();
        $employees = array_filter($employees, function($u) {
            $user_roles = (array) $u->roles;
            $primary_role = !empty($user_roles) ? $user_roles[0] : '';
            return !in_array('administrator', $user_roles) && !in_array('sm_system_admin', $user_roles) && $primary_role !== 'sm_student' && $primary_role !== 'sm_parent';
        });

        $filename = 'EESS_Employees_Export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel Arabic compatibility
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, array(' ', 'Full Name', 'Username', 'Email Address', 'Phone Number', ' ', ' / ', 'Sport Activity / ', 'Organization  / Academy ', 'Status '));

        foreach ($employees as $emp) {
            $e_num   = get_user_meta($emp->ID, 'eess_employee_number', true) ?: '';
            $e_role  = !empty($emp->roles) ? $emp->roles[0] : '';
            $e_role_txt = $role_map[$e_role] ?? $e_role;
            $e_dept  = get_user_meta($emp->ID, 'eess_department', true) ?: (get_user_meta($emp->ID, 'department', true) ?: '');
            $e_spec  = get_user_meta($emp->ID, 'sm_specialization', true) ?: (get_user_meta($emp->ID, 'specialization', true) ?: '');
            $e_sch   = get_user_meta($emp->ID, 'eess_school_name', true) ?: '';
            $e_phone = get_user_meta($emp->ID, 'sm_phone', true) ?: '';
            $e_stat  = get_user_meta($emp->ID, 'eess_hr_employment_status', true) ?: 'active';
            $e_stat_txt = ($e_stat === 'active') ? 'Active ' : (($e_stat === 'restricted') ? ' ' : ' Active');

            fputcsv($output, array($e_num, $emp->display_name, $emp->user_login, $emp->user_email, $e_phone, $e_role_txt, $e_dept, $e_spec, $e_sch, $e_stat_txt));
        }

        fclose($output);
        exit;
    }

    public function ajax_bulk_import_employees() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

        if (!$is_admin && !$is_sys_admin && !$is_hr) {
            wp_send_json_error('Unauthorized  .');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'eess_hr_add_employee_nonce')) {
            wp_send_json_error(' No   Update .');
        }

        $records = isset($_POST['records']) ? json_decode(stripslashes($_POST['records']), true) : array();
        if (empty($records) || !is_array($records)) {
            wp_send_json_error('No  No  .');
        }

        $success_count = 0;
        $updated_count = 0;

        foreach ($records as $row) {
            $name = sanitize_text_field($row['name']);
            $email = sanitize_email($row['email']);
            $emp_num = sanitize_text_field($row['emp_num']);
            $dept = sanitize_text_field($row['dept']);
            $spec = sanitize_text_field($row['specialization']);
            $phone = sanitize_text_field($row['phone']);
            $role = sanitize_text_field($row['role']);
            $school = sanitize_text_field($row['school']);

            // Validate mandatory fields
            if (empty($name) || empty($email) || empty($role)) {
                continue;
            }

            // Check if user exists by email
            if (email_exists($email)) {
                $duplicate_count++;
                continue;
            }

            // Generate unique username
            $username = strstr($email, '@', true);
            if (empty($username)) $username = 'emp_' . rand(1000, 9999);
            while (username_exists($username)) {
                $username .= rand(0, 9);
            }

            $password = wp_generate_password(12, false);

            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $email,
                'display_name' => $name,
                'user_pass' => $password,
                'role' => $role
            ));

            if (is_wp_error($user_id)) {
                continue;
            }

            // Set approved approval status for bulk-imported employees
            update_user_meta($user_id, 'eess_approval_status', 'approved');
            update_user_meta($user_id, 'eess_employee_number', $emp_num);
            update_user_meta($user_id, 'eess_department', $dept);
            update_user_meta($user_id, 'sm_specialization', $spec);
            update_user_meta($user_id, 'sm_phone', $phone);
            update_user_meta($user_id, 'eess_school_name', $school);
            update_user_meta($user_id, 'eess_hr_employment_status', 'active');
            update_user_meta($user_id, 'sm_temp_pass', $password);

            // Synchronize with organizational assignments
            global $wpdb;
            if (!empty($school)) {
                $school_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE name = %s", $school));
                if ($school_id) {
                    update_user_meta($user_id, 'eess_school_id', $school_id);
                    $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                    $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                        'user_id' => $user_id,
                        'institution_id' => 1,
                        'school_id' => $school_id
                    ));
                }
            }

            clean_user_cache($user_id);
            $success_count++;
        }

        wp_cache_flush();

        SM_Logger::log('Import  ', " Import ($success_count)    ($duplicate_count)   .");

        wp_send_json_success(array(
            'imported' => $success_count,
            'duplicates' => $duplicate_count
        ));
    }

    public function ajax_update_generic_user() {
        if (!current_user_can('_Users')) wp_send_json_error('Unauthorized');
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_user_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_user_id']);

        $target_user = get_userdata($user_id);
        if ($target_user && $target_user->user_email === 'info@eess.online') {
            wp_send_json_error(' No  Edit        .');
        }

        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_email']) && is_email($_POST['user_email'])) {
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
        }
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }
        if (isset($_POST['employee_number'])) {
            update_user_meta($user_id, 'eess_employee_number', sanitize_text_field($_POST['employee_number']));
        }
        if (isset($_POST['department'])) {
            update_user_meta($user_id, 'eess_department', sanitize_text_field($_POST['department']));
        }

        $school_id = isset($_POST['institution']) ? intval($_POST['institution']) : 0;
        if ($school_id) {
            global $wpdb;
            $school_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_schools WHERE id = %d", $school_id));
            if ($school_name) {
                update_user_meta($user_id, 'eess_school_name', $school_name);
                update_user_meta($user_id, 'eess_school_id', $school_id);
                $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                    'user_id' => $user_id,
                    'institution_id' => 1,
                    'school_id' => $school_id
                ));
            }
        }

        SM_Settings::change_user_role($user_id, sanitize_text_field($_POST['user_role']), $_POST);

        if (isset($_POST['delete_photo_flag']) && $_POST['delete_photo_flag'] === '1') {
            delete_user_meta($user_id, 'eess_profile_photo');
        }

        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
            }
        }

        clean_user_cache($user_id);
        wp_cache_flush();

        SM_Logger::log('Edit  ', " Update  : {$_POST['display_name']} (ID: $user_id)");
        wp_send_json_success('Updated');
    }

    public function ajax_add_teacher() {
        if (!current_user_can('_Users')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $pass = $_POST['user_pass'];
        if (empty($pass)) {
            $pass = '';
            for($i=0; $i<10; $i++) $pass .= rand(0,9);
        }

        $username = sanitize_user($_POST['user_login']);
        $email = (!empty($_POST['user_email']) && is_email($_POST['user_email'])) ? sanitize_email($_POST['user_email']) : ($username . '@school-system.local'); // Automated

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_pass' => $pass,
            'role' => sanitize_text_field($_POST['role'] ?: 'sm_teacher')
        );
        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());

        update_user_meta($user_id, 'sm_temp_pass', $pass);
        update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        if (isset($_POST['assigned'])) {
            $assigned = array_map('sanitize_text_field', $_POST['assigned']);
            if ($_POST['role'] === 'sm_teacher') {
                update_user_meta($user_id, 'sm_assigned_sections', $assigned);
            } elseif ($_POST['role'] === 'sm_supervisor') {
                update_user_meta($user_id, 'sm_supervised_classes', $assigned);
            }
        }

        wp_send_json_success($user_id);
    }

    public function ajax_trigger_force_password_reset() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_hr = current_user_can('manage_options') || current_user_can('manage_hr') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_hr', $user_roles);
        if (!$can_manage_hr) wp_send_json_error('Unauthorized permissions');

        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eess_photo_approval')) {
            wp_send_json_error('Security check failed');
        }

        $emp_id = intval($_POST['employee_id']);
        if ($emp_id > 0) {
            update_user_meta($emp_id, 'eess_force_pass_reset', 'yes');
            clean_user_cache($emp_id);
            wp_cache_flush();
            wp_send_json_success('Triggered');
        }

        wp_send_json_error('Invalid user');
    }

    public function ajax_manage_direct_profile_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $user_roles = (array) wp_get_current_user()->roles;
        $can_manage_hr = current_user_can('manage_options') || current_user_can('manage_hr') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_hr', $user_roles);
        if (!$can_manage_hr) wp_send_json_error('Unauthorized permissions');

        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eess_photo_approval')) {
            wp_send_json_error('Security check failed');
        }

        $emp_id = intval($_POST['employee_id']);
        $sub_action = sanitize_text_field($_POST['sub_action'] ?? '');

        if ($sub_action === 'remove') {
            delete_user_meta($emp_id, 'eess_profile_photo');
            delete_user_meta($emp_id, 'eess_pending_profile_photo');
            clean_user_cache($emp_id);
            wp_cache_flush();
            wp_send_json_success('Removed');
        }

        if ($sub_action === 'upload') {
            if (empty($_FILES['profile_photo'])) {
                wp_send_json_error('    .');
            }

            $file = $_FILES['profile_photo'];

            // Enforce 3MB Server-Side Maximum Size Validation
            if ($file['size'] > 3 * 1024 * 1024) {
                wp_send_json_error('       (3 ).');
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $upload = wp_handle_upload($file, array('test_form' => false));
            if (isset($upload['error'])) {
                wp_send_json_error($upload['error']);
            }

            $photo_url = $upload['url'];
            update_user_meta($emp_id, 'eess_profile_photo', $photo_url);
            delete_user_meta($emp_id, 'eess_pending_profile_photo');

            clean_user_cache($emp_id);
            wp_cache_flush();

            wp_send_json_success(array('photo_url' => $photo_url));
        }

        wp_send_json_error('Invalid action');
    }

    public function ajax_upload_mobile_profile_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $user_id = get_current_user_id();

        if (empty($_FILES['profile_photo']['name'])) {
            wp_send_json_error('    .');
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('profile_photo', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error('  : ' . $attachment_id->get_error_message());
        }

        $photo_url = wp_get_attachment_url($attachment_id);
        update_user_meta($user_id, 'sm_profile_photo_id', $attachment_id);
        update_user_meta($user_id, 'sm_profile_photo_url', $photo_url);
        update_user_meta($user_id, 'eess_profile_photo', $photo_url);

        SM_Logger::log('Update   ', "  ID: $user_id Update   .");

        wp_send_json_success(array('photo_url' => $photo_url));
    }

    public function ajax_update_profile() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_profile_action')) wp_send_json_error('Security check failed');

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $is_restricted = in_array('sm_student', (array)$user->roles) || in_array('sm_parent', (array)$user->roles);

        $user_data = array(
            'ID' => $user_id
        );

        if (!$is_restricted) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
        }

        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']); // Store as visible
        }

        if (count($user_data) <= 1) {
            wp_send_json_error('No data to update');
        }

        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        else wp_send_json_success('Profile updated');
    }

    public function ajax_bulk_delete() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $type = sanitize_text_field($_POST['delete_type']);
        $count = 0;

        switch ($type) {
            case 'students':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
                SM_Logger::log('  No No', ' ');
                break;
            case 'teachers':
                $teachers = get_users(array('role' => 'sm_teacher'));
                foreach ($teachers as $t) {
                    wp_delete_user($t->ID);
                    $count++;
                }
                SM_Logger::log('  ', ' ');
                break;
            case 'parents':
                $parents = get_users(array('role' => 'sm_parent'));
                foreach ($parents as $p) {
                    wp_delete_user($p->ID);
                    $count++;
                }
                SM_Logger::log('   Mother ', ' ');
                break;
            case 'records':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
                SM_Logger::log('  ', ' ');
                break;
        }

        wp_send_json_success('   ');
    }

    public function ajax_eess_restrict_student_account() {
        if (!is_user_logged_in() || (!current_user_can('_No') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error(' No  No   No.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        if (!$student_id) wp_send_json_error(' No  .');

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('No  .');

        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_students", array('status' => 'inactive'), array('id' => $student_id));

        // Sync WP user meta status if linked account exists
        $national_id = $student->national_id;
        $student_code = $student->student_code;
        $user_id = 0;
        if (!empty($national_id)) $user_id = username_exists($national_id);
        if (!$user_id && !empty($student_code)) $user_id = username_exists($student_code);

        if ($user_id) {
            update_user_meta($user_id, 'sm_account_status', 'restricted');
            update_user_meta($user_id, 'eess_account_status', 'restricted');
        }

        SM_Logger::log('  No', " /  No: {$student->name} (ID: $student_id)");
        wp_send_json_success(array('message' => '  /   No .'));
    }

    public function ajax_eess_request_student_password_change() {
        if (!is_user_logged_in() || (!current_user_can('_No') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error(' No  No Send   Password.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        if (!$student_id) wp_send_json_error(' No  .');

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('No  .');

        $national_id = $student->national_id;
        $student_code = $student->student_code;
        $user_id = 0;
        if (!empty($national_id)) $user_id = username_exists($national_id);
        if (!$user_id && !empty($student_code)) $user_id = username_exists($student_code);

        if ($user_id) {
            update_user_meta($user_id, 'eess_must_change_password', '1');
            SM_Logger::log('Issue    ', " Issue    Password   No: {$student->name}");
            wp_send_json_success(array('message' => ' Send    Password  No  Login .'));
        } else {
            wp_send_json_error('        No.');
        }
    }

    public function ajax_eess_send_message_to_student() {
        if (!is_user_logged_in() || (!current_user_can('_No') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error(' No  No Send  No.');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$student_id || empty($message)) {
            wp_send_json_error('  No   .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('No  .');

        $national_id = $student->national_id;
        $student_code = $student->student_code;
        $receiver_id = 0;
        if (!empty($national_id)) $receiver_id = username_exists($national_id);
        if (!$receiver_id && !empty($student_code)) $receiver_id = username_exists($student_code);

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_messages", array(
            'sender_id'   => get_current_user_id(),
            'receiver_id' => $receiver_id ?: 0,
            'student_id'  => $student_id,
            'message'     => $message,
            'status'      => 'unread',
            'created_at'  => current_time('mysql')
        ));

        if ($inserted) {
            SM_Logger::log('Send   No', " Send  No: {$student->name} (ID: $student_id)");
            wp_send_json_success(array('message' => ' Send  No    .'));
        } else {
            wp_send_json_error(' Save    .');
        }
    }

    public function ajax_eess_mark_message_read() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $msg_id = intval($_POST['message_id'] ?? 0);
        if (!$msg_id) wp_send_json_error('   .');

        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}sm_messages",
            array('status' => 'read'),
            array('id' => $msg_id, 'receiver_id' => get_current_user_id())
        );

        wp_send_json_success();
    }

    public function ajax_get_students_attendance() {
        $class_name = sanitize_text_field($_POST['class_name'] ?? '');
        $section = sanitize_text_field($_POST['section'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));
        $code = sanitize_text_field($_POST['security_code'] ?? '');

        // Security Check: Either Staff or Valid Class Code
        $is_staff = is_user_logged_in() && current_user_can('_No');

        if (!$is_staff) {
            if (empty($code)) wp_send_json_error('Security code required');

            if (empty($class_name) || empty($section)) {
                // Visitor mode: Search for class by code
                $all_codes = SM_Settings::get_class_security_codes();
                $found_key = array_search($code, $all_codes);
                if (!$found_key) wp_send_json_error('Invalid security code');

                list($class_name, $section) = explode('|', $found_key);
            } else {
                $valid_code = (SM_Settings::get_class_security_code($class_name, $section) === $code);
                if (!$valid_code) wp_send_json_error('Invalid security code');
            }
        }

        if (empty($class_name) || empty($section)) wp_send_json_error('Missing class information');

        $students = SM_DB::get_students_attendance($class_name, $section, $date);
        wp_send_json_success($students);
    }

    public function shortcode_class_attendance() {
        wp_enqueue_script('html5-qrcode');
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        if (!$is_admin && (!SM_Settings::is_section_visible('attendance') || !SM_Settings::user_has_module_capability('attendance'))) {
            return SM_Settings::get_access_restricted_html();
        }
        ob_start();
        include SM_PLUGIN_DIR . 'templates/shortcode-class-attendance.php';
        return ob_get_clean();
    }

    public function shortcode_sportedia_dashboard() {
        ob_start();
        include SPORTEDIA_PLUGIN_DIR . 'modules/dashboard/view-dashboard.php';
        return ob_get_clean();
    }

    public function shortcode_sportedia_attendance() {
        ob_start();
        include SPORTEDIA_PLUGIN_DIR . 'modules/attendance/view-attendance.php';
        return ob_get_clean();
    }

    public function ajax_save_attendance() {
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $date = !empty($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
        $status = !empty($_POST['status']) ? sanitize_text_field($_POST['status']) : 'present';
        $barcode = sanitize_text_field($_POST['student_barcode'] ?? '');
        $student_id = intval($_POST['student_id'] ?? 0);

        // Authoritative Student Barcode Recognition Logic matching Student Affairs & Discipline modules
        $student = null;
        if (!empty($barcode)) {
            $student = SM_DB::get_student_by_code($barcode);
            if ($student) {
                $student_id = intval($student->id);
            }
        }

        if (!$student && $student_id > 0) {
            $student = SM_DB::get_student_by_id($student_id);
        }
        if (!$student) wp_send_json_error('     No    .');

        // Server-side school scope check
        $user_scope = EESS_Org_Helper::get_user_scope();
        if (!$user_scope['unrestricted'] && !empty($user_scope['schools'])) {
            $st_sch = intval($student->institution_id ?: $student->school_id);
            if ($st_sch > 0 && !in_array($st_sch, $user_scope['schools'], true)) {
                wp_send_json_error(' No  No   No   .');
            }
        }

        // Duplicate Check for the same date/session
        $already = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_attendance WHERE student_id = %d AND date = %s",
            $student_id, $date
        ));

        if ($already && !empty($barcode)) {
            wp_send_json_success(array(
                'already_recorded' => true,
                'student_id'       => $student_id,
                'student_name'     => $student->name,
                'message'          => '    No '
            ));
        }

        $teacher_id = get_current_user_id();

        if (SM_DB::save_attendance($student_id, $status, $date, $teacher_id)) {
            wp_send_json_success(array(
                'already_recorded' => false,
                'student_id'       => $student_id,
                'student_name'     => $student->name,
                'class_name'       => $student->class_name,
                'section'          => $student->section,
                'status'           => $status
            ));
        } else {
            wp_send_json_error('    .');
        }
    }

    public function ajax_save_attendance_batch() {
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $batch = json_decode(stripslashes($_POST['batch'] ?? '[]'), true);
        if (empty($batch)) wp_send_json_error('Empty batch');

        $first_sid = intval($batch[0]['student_id']);
        $student = SM_DB::get_student_by_id($first_sid);
        if (!$student) wp_send_json_error('Student not found');

        $code = sanitize_text_field($_POST['security_code'] ?? '');
        $is_staff = is_user_logged_in() && current_user_can('_No');
        $valid_code = (SM_Settings::get_class_security_code($student->class_name, $student->section) === $code);

        if (!$is_staff && !$valid_code) {
            wp_send_json_error('Unauthorized');
        }

        $date = sanitize_text_field($_POST['date']);
        $teacher_id = get_current_user_id();

        if (!is_array($batch)) wp_send_json_error('Invalid batch data');

        $success_count = 0;
        foreach ($batch as $item) {
            if (SM_DB::save_attendance(intval($item['student_id']), sanitize_text_field($item['status']), $date, $teacher_id)) {
                $success_count++;
            }
        }

        wp_send_json_success($success_count);
    }

    public function ajax_reset_class_code() {
        if (!is_user_logged_in() || !current_user_can('_No')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $grade = sanitize_text_field($_POST['grade']);
        $section = sanitize_text_field($_POST['section']);

        $new_code = SM_Settings::reset_class_security_code($grade, $section);
        wp_send_json_success($new_code);
    }

    public function ajax_toggle_attendance_status() {
        if (!is_user_logged_in() || !current_user_can('_No')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_attendance_action')) wp_send_json_error('Security check failed');

        $status = sanitize_text_field($_POST['status']);
        update_option('sm_attendance_manual_status', $status);
        wp_send_json_success();
    }

    public function ajax_filter_violations() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $filters = array();
        if (isset($_POST['student_search'])) $filters['search'] = sanitize_text_field($_POST['student_search']);
        if (isset($_POST['class_filter'])) $filters['class_name'] = sanitize_text_field($_POST['class_filter']);
        if (isset($_POST['section_filter'])) $filters['section'] = sanitize_text_field($_POST['section_filter']);
        if (isset($_POST['type_filter'])) $filters['type'] = sanitize_text_field($_POST['type_filter']);

        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $limit = isset($_POST['limit']) ? max(1, intval($_POST['limit'])) : 10;
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'created_at';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';

        $total_count = SM_DB::get_records_count($filters);
        $total_pages = max(1, ceil($total_count / $limit));
        if ($paged > $total_pages) {
            $paged = $total_pages;
        }
        $offset = ($paged - 1) * $limit;

        $query_filters = array_merge($filters, array(
            'limit' => $limit,
            'offset' => $offset,
            'orderby' => $orderby,
            'order' => $order
        ));

        $records = SM_DB::get_records($query_filters);

        ob_start();
        include SM_PLUGIN_DIR . 'templates/partials/violations-table-rows.php';
        $rows_html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $rows_html,
            'total' => $total_count,
            'paged' => $paged,
            'limit' => $limit,
            'total_pages' => $total_pages,
            'from' => $total_count > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $total_count)
        ));
    }

    public function ajax_mark_contacted() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_record_action')) wp_send_json_error('Security check');

        $record_id = intval($_POST['record_id']);
        if (SM_DB::mark_record_contacted($record_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update status');
        }
    }

    public function ajax_add_document() {
        if (!is_user_logged_in()) wp_send_json_error('  Login.');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_admin_action')) wp_send_json_error('  Mother .');

        $title    = sanitize_text_field($_POST['title'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $file_url = esc_url_raw($_POST['file_url'] ?? '');
        $is_general = !empty($_POST['is_general']);

        if (empty($title) || empty($category)) {
            wp_send_json_error('    .');
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $can_upload_general = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || current_user_can('manage_options');

        if ($is_general && !$can_upload_general) {
            wp_send_json_error('     No  Academy       .');
        }

        // Validate File Size (max 5MB) and Allowed Extensions (PDF, DOC/DOCX, XLS/XLSX)
        if (!empty($_FILES['doc_file']['name'])) {
            $file = $_FILES['doc_file'];
            if ($file['size'] > 5 * 1024 * 1024) {
                wp_send_json_error('       (5 ).');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = array('pdf', 'doc', 'docx', 'xls', 'xlsx');
            if (!in_array($ext, $allowed_exts)) {
                wp_send_json_error('    .    PDF, DOC, DOCX, XLS, XLSX.');
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('doc_file', 0);
            if (is_wp_error($attachment_id)) {
                wp_send_json_error('  : ' . $attachment_id->get_error_message());
            }
            $file_url = wp_get_attachment_url($attachment_id);
        }

        if (empty($file_url)) {
            wp_send_json_error('      .');
        }

        global $wpdb;
        $result = $wpdb->insert("{$wpdb->prefix}sm_documents", array(
            'title'       => $title,
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'file_url'    => $file_url,
            'status'      => sanitize_text_field($_POST['status'] ?? 'published'),
            'category'    => $category,
            'created_by'  => get_current_user_id()
        ));

        if ($result) wp_send_json_success();
        else wp_send_json_error(' Save   .');
    }

    public function ajax_update_document() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->update("{$wpdb->prefix}sm_documents", array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'file_url' => esc_url_raw($_POST['file_url']),
            'status' => sanitize_text_field($_POST['status']),
            'category' => sanitize_text_field($_POST['category'] ?? ' ')
        ), array('id' => intval($_POST['doc_id'])));

        if ($result !== false) wp_send_json_success();
        else wp_send_json_error('Failed to update');
    }

    public function ajax_delete_document() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $result = $wpdb->delete("{$wpdb->prefix}sm_documents", array('id' => intval($_POST['doc_id'])));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete');
    }

    public function ajax_save_regulation_settings() {
        if (!current_user_can('manage_options') && !current_user_can('sm_principal') && !current_user_can('sm_supervisor')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
        $types = array();
        foreach ($types_raw as $line) {
            $parts = explode("|", $line);
            if (count($parts) == 2) {
                $types[trim($parts[0])] = trim($parts[1]);
            }
        }
        if (!empty($types)) {
            SM_Settings::save_violation_types($types);
        }
        SM_Settings::save_suggested_actions(array(
            'low' => sanitize_textarea_field($_POST['suggested_low']),
            'medium' => sanitize_textarea_field($_POST['suggested_medium']),
            'high' => sanitize_textarea_field($_POST['suggested_high'])
        ));

        SM_Logger::log('Update  No', '  Update     Actions.');

        wp_send_json_success();
    }

    public function ajax_save_hierarchical_violations() {
        if (!current_user_can('manage_options') && !current_user_can('sm_principal') && !current_user_can('sm_supervisor')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $processed = array();
        if (isset($_POST['h_viol']) && is_array($_POST['h_viol'])) {
            foreach ($_POST['h_viol'] as $level => $items) {
                $processed[$level] = array();
                foreach ($items as $item) {
                    if (!empty($item['name'])) {
                        $code = !empty($item['code']) ? $item['code'] : 'V'.rand(100,999);
                        $processed[$level][$code] = array(
                            'name' => sanitize_text_field($item['name']),
                            'points' => intval($item['points']),
                            'action' => sanitize_text_field($item['action'])
                        );
                    }
                }
            }
        }
        SM_Settings::save_hierarchical_violations($processed);
        SM_Logger::log('Update No  ', ' Update  No  Actions  .');

        wp_send_json_success();
    }

    public function ajax_delete_log() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $log_id = intval($_POST['log_id']);
        $result = $wpdb->delete("{$wpdb->prefix}sm_logs", array('id' => $log_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete log');
    }

    public function ajax_delete_all_logs() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");

        if ($result !== false) {
            SM_Logger::log('  ', '     ');
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete logs');
        }
    }

    public function ajax_rollback_log() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        $log_id = intval($_POST['log_id']);
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_logs WHERE id = %d", $log_id));

        if (!$log || strpos($log->details, 'ROLLBACK_DATA:') !== 0) {
            wp_send_json_error('No    ');
        }

        $json = substr($log->details, strlen('ROLLBACK_DATA:'));
        $data_obj = json_decode($json, true);

        if (!$data_obj || !isset($data_obj['table']) || !isset($data_obj['data'])) {
            wp_send_json_error(' No ');
        }

        $table = $data_obj['table'];
        $data = $data_obj['data'];

        // Remove 'id' if we want to insert as new, or keep if we want to restore exact ID (risky if ID taken)
        // For students/records, restoring exact ID is better for relations.

        $table_name = $wpdb->prefix . 'sm_' . $table;

        // Check if ID already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $data['id']));
        if ($exists) {
            wp_send_json_error('      ');
        }

        $result = $wpdb->insert($table_name, $data);

        if ($result) {
            $wpdb->delete("{$wpdb->prefix}sm_logs", array('id' => $log_id)); // Remove log after rollback
            SM_Logger::log('  ', ": $table  : {$data['id']}");
            wp_send_json_success(' No ');
        } else {
            wp_send_json_error('  No   ');
        }
    }

    public function ajax_republish_system_announcement() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sm_announcement_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $anc_id = intval($_POST['announcement_id']);

        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_system_announcements",
            array('status' => 'active'),
            array('id' => $anc_id)
        );

        if ($updated !== false) {
            SM_Logger::log('   ', "    (ID: $anc_id)  ");
            wp_send_json_success('     .');
        } else {
            wp_send_json_error('  .');
        }
    }

    public function ajax_bulk_delete_read_stats() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sm_announcement_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $deleted = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_user_announcements");

        if ($deleted !== false) {
            SM_Logger::log('   ', '   No     ');
            wp_send_json_success('    .');
        } else {
            wp_send_json_error('   .');
        }
    }

    public function ajax_initialize_system() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        if ($_POST['confirm_code'] !== '1011996') {
            wp_send_json_error(' Confirm  ');
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_records");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_confiscated_items");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");

        $teachers = get_users(array('role' => 'sm_teacher'));
        foreach ($teachers as $t) wp_delete_user($t->ID);

        $parents = get_users(array('role' => 'sm_parent'));
        foreach ($parents as $p) wp_delete_user($p->ID);

        SM_Logger::log('  ', '    ');
        wp_send_json_success('    ');
    }

    public function ajax_update_teacher() {
        if (!current_user_can('_Users')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_teacher_id']);

        $target_user = get_userdata($user_id);
        if ($target_user && $target_user->user_email === 'info@eess.online') {
            wp_send_json_error(' No  Edit        .');
        }

        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'])
        );
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']);
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());

        $role = sanitize_text_field($_POST['role']);
        SM_Settings::change_user_role($user_id, $role, $_POST);

        // Employee Number Handling with Manual Correction Support
        $emp_num = sanitize_text_field($_POST['employee_number'] ?? ($_POST['teacher_id'] ?? ''));
        if (!empty($emp_num)) {
            global $wpdb;
            $duplicate_check = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'eess_employee_number' AND meta_value = %s AND user_id != %d LIMIT 1",
                $emp_num,
                $user_id
            ));
            if (!$duplicate_check) {
                update_user_meta($user_id, 'eess_employee_number', $emp_num);
            }
        }

        update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'sm_account_status', sanitize_text_field($_POST['account_status']));

        if (!empty($_POST['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($_POST['specialization']));
        }

        // Clean old assignments
        delete_user_meta($user_id, 'sm_assigned_sections');
        delete_user_meta($user_id, 'sm_supervised_classes');

        if (isset($_POST['assigned'])) {
            $assigned = array_map('sanitize_text_field', $_POST['assigned']);
            if ($role === 'sm_teacher') {
                update_user_meta($user_id, 'sm_assigned_sections', $assigned);
            } elseif ($role === 'sm_supervisor') {
                update_user_meta($user_id, 'sm_supervised_classes', $assigned);
            }
        }

        wp_send_json_success('Updated');
    }


    public function ajax_approve_plan() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_assignment_action')) wp_send_json_error('Security check');

        global $wpdb;
        $plan_id = intval($_POST['plan_id']);
        $result = $wpdb->update("{$wpdb->prefix}sm_assignments",
            array('receiver_id' => get_current_user_id()), // Mark as approved by current coordinator
            array('id' => $plan_id, 'type' => 'lesson_plan')
        );

        if ($result) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_bulk_delete_users() {
        if (!current_user_can('_Users')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_teacher_action')) wp_send_json_error('Security check');

        $ids = array_map('intval', explode(',', $_POST['user_ids']));
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $count = 0;
        foreach ($ids as $id) {
            if ($id != get_current_user_id()) {
                if (wp_delete_user($id)) $count++;
            }
        }
        SM_Logger::log('Delete  ()', " Delete  ($count)   .");
        wp_send_json_success();
    }

    public function ajax_add_clinic_referral() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $student_id = intval($_POST['student_id']);
        $referrer_id = get_current_user_id();

        $result = $wpdb->insert("{$wpdb->prefix}sm_clinic", array(
            'student_id' => $student_id,
            'referrer_id' => $referrer_id,
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            $student = SM_DB::get_student_by_id($student_id);
            SM_Logger::log(' ', "  No: {$student->name} ");
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to add referral');
        }
    }

    public function ajax_confirm_clinic_arrival() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $referral_id = intval($_POST['referral_id']);

        $result = $wpdb->update("{$wpdb->prefix}sm_clinic", array(
            'arrival_confirmed' => 1,
            'arrival_at' => current_time('mysql')
        ), array('id' => $referral_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to confirm arrival');
    }

    public function ajax_update_clinic_record() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_clinic_action')) wp_send_json_error('Security check');

        global $wpdb;
        $referral_id = intval($_POST['referral_id']);
        $health_condition = sanitize_textarea_field($_POST['health_condition']);
        $action_taken = sanitize_textarea_field($_POST['action_taken']);

        $result = $wpdb->update("{$wpdb->prefix}sm_clinic", array(
            'health_condition' => $health_condition,
            'action_taken' => $action_taken
        ), array('id' => $referral_id));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to update record');
    }

    public function ajax_get_clinic_reports() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_clinic_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $type = sanitize_text_field($_GET['report_type']); // day, week, month, term, year
        $start_date = '';
        $end_date = current_time('Y-m-d') . ' 23:59:59';

        switch ($type) {
            case 'day': $start_date = current_time('Y-m-d') . ' 00:00:00'; break;
            case 'week': $start_date = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'; break;
            case 'month': $start_date = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'; break;
            case 'term':
                $academic = SM_Settings::get_academic_structure();
                $today = current_time('Y-m-d');
                foreach ($academic['term_dates'] as $t) {
                    if ($today >= $t['start'] && $today <= $t['end']) {
                        $start_date = $t['start'] . ' 00:00:00';
                        $end_date = $t['end'] . ' 23:59:59';
                        break;
                    }
                }
                if (empty($start_date)) $start_date = date('Y-m-01') . ' 00:00:00';
                break;
            case 'year': $start_date = date('Y-01-01') . ' 00:00:00'; break;
        }

        $query = "SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
                  FROM {$wpdb->prefix}sm_clinic c
                  JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
                  JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
                  WHERE c.created_at BETWEEN %s AND %s
                  ORDER BY c.created_at DESC";

        $records = $wpdb->get_results($wpdb->prepare($query, $start_date, $end_date));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clinic_report_'.$type.'_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
        fputcsv($output, array('', 'Player Name', 'Training Group', 'Training Group', '', 'Confirm ', 'Status ', ' '));

        foreach ($records as $r) {
            fputcsv($output, array(
                $r->created_at,
                $r->student_name,
                $r->class_name,
                $r->section,
                $r->referrer_name,
                $r->arrival_confirmed ? 'Yes' : 'No',
                $r->health_condition,
                $r->action_taken
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_save_grade_ajax() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security check failed');

        $subject = sanitize_text_field($_POST['subject']);
        $user = wp_get_current_user();
        if (in_array('sm_teacher', (array)$user->roles) && !current_user_can('manage_options')) {
            $spec = get_user_meta($user->ID, 'sm_specialization', true);
            if ($spec && $spec !== $subject) {
                wp_send_json_error('        .');
            }
        }

        global $wpdb;
        $result = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
            'student_id' => intval($_POST['student_id']),
            'subject' => $subject,
            'term' => sanitize_text_field($_POST['term']),
            'grade_val' => sanitize_text_field($_POST['grade_val']),
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            SM_Logger::log(' ', "   No ID: {$_POST['student_id']}   $subject");
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save grade');
        }
    }

    public function ajax_import_grades() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) {
            wp_send_json_error('Security check failed');
        }

        $records = isset($_POST['records']) ? json_decode(stripslashes($_POST['records']), true) : array();
        if (empty($records) || !is_array($records)) {
            wp_send_json_error('No  No  .');
        }

        global $wpdb;
        $success_count = 0;
        $duplicate_count = 0;

        foreach ($records as $row) {
            $student_code = sanitize_text_field($row['student_code']);
            $subject = sanitize_text_field($row['subject']);
            $term = sanitize_text_field($row['term']);
            $grade_val = sanitize_text_field($row['grade_val']);

            // Find student by student_code
            $student_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $student_code));
            if (!$student_id) {
                // If not found by student_code, try by name
                $student_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE name = %s", $student_code));
            }

            if (!$student_id) {
                continue;
            }

            // Check if this result is already recorded (duplicate check)
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_grades WHERE student_id = %d AND subject = %s AND term = %s AND grade_val = %s",
                $student_id, $subject, $term, $grade_val
            ));

            if ($existing) {
                $duplicate_count++;
                continue;
            }

            $result = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
                'student_id' => $student_id,
                'subject' => $subject,
                'term' => $term,
                'grade_val' => $grade_val,
                'created_at' => current_time('mysql')
            ));

            if ($result) {
                $success_count++;
            }
        }

        SM_Logger::log('Import  ', " Import ($success_count)    ($duplicate_count)  .");

        wp_send_json_success(array(
            'imported' => $success_count,
            'duplicates' => $duplicate_count
        ));
    }

    public function ajax_get_student_grades_ajax() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_grade_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) wp_send_json_error('Security');

        global $wpdb;
        $student_id = intval($_POST['student_id']);

        // Security check: if student, can only see own. If staff, can see all.
        if (in_array('sm_student', (array)wp_get_current_user()->roles)) {
            $student = SM_DB::get_student_by_parent(get_current_user_id());
            if (!$student || $student->id != $student_id) wp_send_json_error('Unauthorized access to grades');
        }

        $grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_grades WHERE student_id = %d ORDER BY created_at DESC", $student_id));
        wp_send_json_success($grades);
    }

    public function ajax_delete_grade_ajax() {
        if (!is_user_logged_in() || !current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $result = $wpdb->delete("{$wpdb->prefix}sm_grades", array('id' => intval($_POST['grade_id'])));

        if ($result) wp_send_json_success();
        else wp_send_json_error('Failed to delete grade');
    }

    public function ajax_add_subject() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        $name = sanitize_text_field($_POST['name']);
        $grade_ids = isset($_POST['grade_ids']) ? array_map('intval', $_POST['grade_ids']) : array();

        if (empty($grade_ids) && isset($_POST['grade_id'])) {
            $grade_ids = array(intval($_POST['grade_id']));
        }

        if (SM_DB::add_subject($name, $grade_ids)) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_delete_subject() {
        if (!current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security');

        if (SM_DB::delete_subject(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error();
    }

    public function ajax_get_subjects() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $grade_id = isset($_GET['grade_id']) ? intval($_GET['grade_id']) : null;
        wp_send_json_success(SM_DB::get_subjects($grade_id));
    }

    public function ajax_save_class_grades() {
        if (!current_user_can('manage_grades')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_grade_action')) wp_send_json_error('Security');

        $subject = sanitize_text_field($_POST['subject']);
        $user = wp_get_current_user();
        if (in_array('sm_teacher', (array)$user->roles) && !current_user_can('manage_options')) {
            $spec = get_user_meta($user->ID, 'sm_specialization', true);
            if ($spec && $spec !== $subject) {
                wp_send_json_error('        .');
            }
        }

        $term = sanitize_text_field($_POST['term']);
        $grades = json_decode(stripslashes($_POST['grades']), true);

        global $wpdb;
        $success = 0;
        foreach ($grades as $student_id => $val) {
            if ($val === '') continue;
            $res = $wpdb->insert("{$wpdb->prefix}sm_grades", array(
                'student_id' => intval($student_id),
                'subject' => $subject,
                'term' => $term,
                'grade_val' => sanitize_text_field($val),
                'created_at' => current_time('mysql')
            ));
            if ($res) $success++;
        }
        wp_send_json_success($success);
    }

    public function ajax_bulk_delete_students() {
        if (!current_user_can('_No')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_delete_student')) wp_send_json_error('Security');

        $ids = array_map('intval', explode(',', $_POST['student_ids']));
        $count = 0;
        foreach ($ids as $id) {
            if (SM_DB::delete_student($id)) $count++;
        }
        SM_Logger::log('Delete No ()', " Delete  ($count) No  .");
        wp_send_json_success($count);
    }


    public function ajax_download_plans_zip() {
        if (!current_user_can('manage_options') && !in_array('sm_principal', (array)wp_get_current_user()->roles) && !in_array('sm_coordinator', (array)wp_get_current_user()->roles)) {
            wp_die('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'], 'sm_admin_action')) wp_die('Security');

        global $wpdb;
        $plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_assignments WHERE type = 'lesson_plan'");

        if (empty($plans)) wp_die('No plans to download');

        if (!class_exists('ZipArchive')) {
            wp_die('ZipArchive extension not enabled on this server.');
        }

        $zip = new ZipArchive();
        $zip_name = 'lesson_plans_' . date('Y-m-d') . '.zip';
        $upload_dir = wp_upload_dir();
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die('Could not create zip file');
        }

        foreach ($plans as $p) {
            if (empty($p->file_url)) continue;

            // Try to get local path
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $p->file_url);
            if (file_exists($file_path)) {
                $zip->addFile($file_path, basename($file_path));
            }
        }

        $zip->close();

        if (file_exists($zip_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name . '"');
            header('Content-Length: ' . filesize($zip_path));
            readfile($zip_path);
            unlink($zip_path);
            exit;
        } else {
            wp_die('Failed to generate zip');
        }
    }

    public function ajax_submit_behavior_referral() {
        if (!is_user_logged_in()) {
            wp_send_json_error('  Login   .');
        }

        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sm_record_action')) {
            wp_send_json_error(' No .   .');
        }

        $student_id     = intval($_POST['student_id'] ?? 0);
        $title          = sanitize_text_field($_POST['title'] ?? '');
        $classification = sanitize_text_field($_POST['classification'] ?? 'inside_class');
        $degree         = intval($_POST['degree'] ?? 1);
        $details        = sanitize_textarea_field($_POST['details'] ?? '');

        if (!$student_id || empty($title) || empty($details)) {
            wp_send_json_error('   .');
        }

        $user = wp_get_current_user();

        // Save referral record
        $record_id = SM_DB::add_record(array(
            'student_id'     => $student_id,
            'teacher_id'     => $user->ID,
            'type'           => $title,
            'classification' => $classification,
            'severity'       => ($degree == 3) ? 'high' : (($degree == 2) ? 'medium' : 'low'),
            'degree'         => $degree,
            'details'        => $details,
            'status'         => 'submitted' // Under review by Discipline Supervisor
        ));

        if ($record_id) {
            SM_Logger::log('   No', "  {$user->display_name}   No ID: $student_id : $title");
            wp_send_json_success(array('record_id' => $record_id, 'message' => '        Confirm  .'));
        } else {
            wp_send_json_error('An error occurred  Save  .');
        }
    }

    public function ajax_refresh_system() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        // 1. Delete all plugin transients from options table
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");

        // 2. Clear general WordPress cache
        wp_cache_flush();

        // 3. Clear user capability transients or meta caches
        $users = get_users(array('fields' => array('ID')));
        foreach ($users as $u) {
            clean_user_cache($u->ID);
        }

        // 4. Force reload settings & metrics
        $school_info = SM_Settings::get_school_info();
        $stats = SM_DB::get_statistics();

        wp_send_json_success(array(
            'message' => ' Update            .',
            'school_name' => $school_info['school_name'],
            'stats' => $stats
        ));
    }

    public function ajax_export_users_csv() {
        if (!is_user_logged_in() || !current_user_can('_Users')) {
            wp_send_json_error('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        $all_users = get_users();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_export_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        fputcsv($output, array('Username', 'Email Address', 'Full Name', ' / ', 'Phone Number', 'Password', '  ', 'Sport Activity ', ' Organization ', ' Academy ', ' ', ' Sport Activity'));

        foreach ($all_users as $u) {
            $role = reset($u->roles);
            $phone = get_user_meta($u->ID, 'sm_phone', true);
            $password = get_user_meta($u->ID, 'sm_temp_pass', true) ?: '';
            $photo = get_user_meta($u->ID, 'eess_profile_photo', true) ?: '';
            $specialization = get_user_meta($u->ID, 'sm_specialization', true) ?: '';

            // Fetch user assignments for org export
            global $wpdb;
            $asn = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d LIMIT 1", $u->ID));
            $inst_code = $asn ? $asn->institution_id : '';
            $school_code = $asn ? $asn->school_id : '';
            $dept_code = $asn ? $asn->department_id : '';
            $subj_code = $asn ? $asn->subject_id : '';

            fputcsv($output, array(
                $u->user_login,
                $u->user_email,
                $u->display_name,
                $role,
                $phone,
                $password,
                $photo,
                $specialization,
                $inst_code,
                $school_code,
                $dept_code,
                $subj_code
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_export_violations_csv() {
        if (!is_user_logged_in() || !current_user_can('_')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_export_action')) wp_send_json_error('Security check failed');

        global $wpdb;
        $range = sanitize_text_field($_GET['range']); // today, week, month, all
        $start_date = '';
        $end_date = current_time('Y-m-d') . ' 23:59:59';
        $student_code = $_GET['student_code'] ?? '';

        if ($range !== 'all') {
            switch ($range) {
                case 'today': $start_date = current_time('Y-m-d') . ' 00:00:00'; break;
                case 'week': $start_date = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'; break;
                case 'month': $start_date = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'; break;
            }
        }

        $query = "SELECT r.*, s.name as student_name, s.class_name, s.section, s.student_code
                  FROM {$wpdb->prefix}sm_records r
                  JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id
                  WHERE 1=1";

        $params = array();
        if ($start_date) {
            $query .= " AND r.created_at BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        if ($student_code) {
            $query .= " AND s.student_code = %s";
            $params[] = $student_code;
        }

        $query .= " ORDER BY r.created_at DESC";

        $records = empty($params) ? $wpdb->get_results($query) : $wpdb->get_results($wpdb->prepare($query, $params));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=violations_'.$range.'_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, array('', 'Player Name', ' No', 'Training Group', 'Training Group', '', '', '', '', '', ' '));

        foreach ($records as $r) {
            // Dynamic Linking
            $reg = SM_Settings::get_regulation_by_code($r->violation_code);
            $display_type = $reg ? $reg['name'] : $r->type;
            $display_action = $reg ? $reg['action'] : $r->action_taken;

            fputcsv($output, array(
                $r->created_at,
                $r->student_name,
                $r->student_code,
                $r->class_name,
                $r->section,
                $display_type,
                $r->severity,
                $r->degree,
                $r->points,
                $r->details,
                $display_action
            ));
        }
        fclose($output);
        exit;
    }

    public function handle_form_submission() {
        static $processed = false;
        if ($processed) {
            return;
        }
        $processed = true;

        // Handle Global Central Subject CRUD
        if (isset($_POST['eess_save_global_subject']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                $inst_id = !empty($_POST['inst_id']) ? intval($_POST['inst_id']) : 1;
                $res = EESS_Org_Helper::save_subject($inst_id, array(
                    'id'                  => intval($_POST['sub_id'] ?? 0),
                    'name'                => sanitize_text_field($_POST['sub_name'] ?? ''),
                    'code'                => sanitize_text_field($_POST['sub_code'] ?? ''),
                    'department_id'       => intval($_POST['sub_department_id'] ?? 0),
                    'hod_user_id'         => intval($_POST['sub_hod_user_id'] ?? 0),
                    'coordinator_user_id' => intval($_POST['sub_coordinator_user_id'] ?? 0),
                    'status'              => sanitize_text_field($_POST['sub_status'] ?? 'active'),
                    'grade_ids'           => !empty($_POST['sub_grade_ids']) ? array_map('intval', (array)$_POST['sub_grade_ids']) : array(),
                    'school_ids'          => !empty($_POST['sub_school_ids']) ? array_map('intval', (array)$_POST['sub_school_ids']) : array()
                ));
                if (is_wp_error($res)) {
                    wp_die('  Save Sport Activity : ' . $res->get_error_message());
                }
                wp_redirect(add_query_arg(array('sm_admin_msg' => 'settings_saved'), wp_get_referer()));
                exit;
            }
        }

        // Handle Single-Level Institution Model CRUD and Staff Assignments
        if (isset($_POST['eess_save_single_inst']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('manage_options') || current_user_can('_')) {
                $action = sanitize_text_field($_POST['inst_action'] ?? '');
                $inst_id = intval($_POST['inst_id'] ?? 0);

                if ($action === 'add' || $action === 'edit') {
                    global $wpdb;
                    $code_val = intval($_POST['inst_code'] ?? 0);

                    // Unique Numeric Code Validation
                    if ($code_val > 0) {
                        $duplicate_check = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}eess_institutions WHERE code = %d AND id != %d LIMIT 1",
                            $code_val,
                            $inst_id
                        ));
                        if ($duplicate_check) {
                            wp_die('  Organization  (' . $code_val . ')    .     .');
                        }
                    }

                    $inst_data = array(
                        'code'          => $code_val > 0 ? $code_val : $inst_id,
                        'parent_id'     => !empty($_POST['inst_parent_id']) ? intval($_POST['inst_parent_id']) : null,
                        'name'          => sanitize_text_field($_POST['inst_name'] ?? ''),
                        'type'          => sanitize_text_field($_POST['inst_type'] ?? ''),
                        'country'       => sanitize_text_field($_POST['inst_country'] ?? 'United Arab Emirates'),
                        'emirate'       => sanitize_text_field($_POST['inst_emirate'] ?? ''),
                        'manager_id'    => !empty($_POST['inst_manager_id']) ? intval($_POST['inst_manager_id']) : null,
                        'director_name' => sanitize_text_field($_POST['inst_director_name'] ?? ''),
                        'phone'         => sanitize_text_field($_POST['inst_phone'] ?? ''),
                        'logo_url'      => esc_url_raw($_POST['inst_logo_url'] ?? ''),
                        'address'       => sanitize_textarea_field($_POST['inst_address'] ?? '')
                    );

                    if ($action === 'add') {
                        $inst_id = EESS_Org_Helper::add_institution($inst_data);
                    } else {
                        EESS_Org_Helper::update_institution($inst_id, $inst_data);
                    }

                    // Save integrated staff assignments for this institution
                    if ($inst_id > 0) {
                        $assigned_staff = isset($_POST['assigned_staff_ids']) ? array_map('intval', (array)$_POST['assigned_staff_ids']) : array();

                        // Get existing users currently assigned to this institution
                        global $wpdb;
                        $currently_assigned = $wpdb->get_col($wpdb->prepare(
                            "SELECT DISTINCT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE institution_id = %d",
                            $inst_id
                        ));

                        // Unassign users no longer checked
                        foreach ($currently_assigned as $c_uid) {
                            if (!in_array(intval($c_uid), $assigned_staff)) {
                                $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $c_uid, 'institution_id' => $inst_id));
                            }
                        }

                        // Assign newly checked staff
                        foreach ($assigned_staff as $s_uid) {
                            $exists = $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d AND institution_id = %d",
                                $s_uid, $inst_id
                            ));
                            if (!$exists) {
                                $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                                    'user_id' => $s_uid,
                                    'institution_id' => $inst_id
                                ));
                            }
                            // Also update profile meta for institution display name
                            update_user_meta($s_uid, 'eess_school_name', $inst_data['name']);
                            update_user_meta($s_uid, 'eess_school_id', $inst_id);
                        }
                    }
                } elseif ($action === 'delete' && $inst_id > 0) {
                    EESS_Org_Helper::delete_institution($inst_id);
                }

                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Centralized Institutional Data Imports (CSV Parser)
        if (isset($_POST['eess_import_org_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_') && !empty($_FILES['csv_file']['tmp_name'])) {
                $school_id = intval($_POST['target_school_id']);
                $import_type = sanitize_text_field($_POST['import_type']);

                global $wpdb;
                $school_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_schools WHERE id = %d", $school_id));
                if (!$school_name) {
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }

                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle);
                if (!$header) {
                    fclose($handle);
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }

                // Clean and normalize headers to find indexes
                $headers = array();
                foreach ($header as $idx => $h) {
                    $h_norm = preg_replace('/[\x{FEFF}\x{200B}]/u', '', $h);
                    $headers[trim(strtolower($h_norm))] = $idx;
                }

                // Helper to find column index by multiple candidate names (Arabic/English)
                $find_col = function($candidates) use ($headers) {
                    foreach ($candidates as $c) {
                        $c_clean = trim(strtolower($c));
                        if (isset($headers[$c_clean])) {
                            return $headers[$c_clean];
                        }
                    }
                    return -1;
                };

                // Find candidate columns based on import type
                $col_name = $find_col(['No', 'Full Name', 'name', 'student name', 'display_name', '']);
                $col_username = $find_col(['Username', 'username', 'login', 'user_login']);
                $col_email = $find_col(['', 'Email Address', 'email', 'user_email', 'parent_email', '  Mother']);
                $col_phone = $find_col(['', 'Phone Number', 'phone', 'sm_phone', 'guardian_phone', '  Mother', 'Mobile Number']);
                $col_password = $find_col(['Password', 'password', 'pass', ' ']);
                $col_grade = $find_col(['Training Group', 'Training Group ', 'grade', 'class_name']);
                $col_section = $find_col(['Training Group', 'Training Group', 'section', 'class', '']);
                $col_division = $find_col(['', '', 'division', 'cycle']);
                $col_specialization = $find_col(['', 'Sport Activity', 'specialization', 'subject']);
                $col_emp_num = $find_col([' ', 'employee_number', 'code', '', ' ', 'National ID / ']);
                $col_dept = $find_col(['', 'department', 'dept', '']);
                $col_role = $find_col(['', '', 'role', ' ']);
                $col_student_code = $find_col([' No', ' No', 'student_code', 'child_code', ' National ID / ']);

                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // Normalize data encoding
                    foreach ($data as $k => $v) {
                        $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                        if ($encoding && $encoding != 'UTF-8') {
                            $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                        }
                        $data[$k] = trim($data[$k]);
                    }

                    // Extract values dynamically based on index
                    $val_name = ($col_name !== -1 && isset($data[$col_name])) ? $data[$col_name] : '';
                    $val_username = ($col_username !== -1 && isset($data[$col_username])) ? $data[$col_username] : '';
                    $val_email = ($col_email !== -1 && isset($data[$col_email])) ? $data[$col_email] : '';
                    $val_phone = ($col_phone !== -1 && isset($data[$col_phone])) ? $data[$col_phone] : '';
                    $val_password = ($col_password !== -1 && isset($data[$col_password])) ? $data[$col_password] : wp_generate_password();
                    $val_grade = ($col_grade !== -1 && isset($data[$col_grade])) ? $data[$col_grade] : '';
                    $val_section = ($col_section !== -1 && isset($data[$col_section])) ? $data[$col_section] : '';
                    $val_division = ($col_division !== -1 && isset($data[$col_division])) ? $data[$col_division] : '';
                    $val_specialization = ($col_specialization !== -1 && isset($data[$col_specialization])) ? $data[$col_specialization] : '';
                    $val_emp_num = ($col_emp_num !== -1 && isset($data[$col_emp_num])) ? $data[$col_emp_num] : '';
                    $val_dept = ($col_dept !== -1 && isset($data[$col_dept])) ? $data[$col_dept] : '';
                    $val_role = ($col_role !== -1 && isset($data[$col_role])) ? $data[$col_role] : '';
                    $val_student_code = ($col_student_code !== -1 && isset($data[$col_student_code])) ? $data[$col_student_code] : '';

                    if ($import_type === 'students') {
                        if (empty($val_name)) continue;

                        // 1. Detect and create Division if exists in columns
                        $division_id = null;
                        if (!empty($val_division)) {
                            $division_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_divisions WHERE school_id = %d AND name = %s", $school_id, $val_division));
                            if (!$division_id) {
                                $wpdb->insert("{$wpdb->prefix}eess_divisions", array(
                                    'school_id' => $school_id,
                                    'name' => $val_division,
                                    'status' => 'active'
                                ));
                                $division_id = $wpdb->insert_id;
                            }
                        }

                        // 2. Detect and create Grade
                        $grade_id = null;
                        if (!empty($val_grade)) {
                            $grade_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_grades WHERE school_id = %d AND name = %s", $school_id, $val_grade));
                            if (!$grade_id) {
                                $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                                    'school_id' => $school_id,
                                    'name' => $val_grade,
                                    'division_id' => $division_id
                                ));
                                $grade_id = $wpdb->insert_id;
                            } else if ($division_id) {
                                // Sync division_id
                                $wpdb->update("{$wpdb->prefix}eess_grades", array('division_id' => $division_id), array('id' => $grade_id));
                            }
                        }

                        // 3. Detect and create Class
                        $class_id = null;
                        if ($grade_id && !empty($val_section)) {
                            $class_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_classes WHERE grade_id = %d AND name = %s", $grade_id, $val_section));
                            if (!$class_id) {
                                $wpdb->insert("{$wpdb->prefix}eess_classes", array(
                                    'grade_id' => $grade_id,
                                    'name' => $val_section
                                ));
                                $class_id = $wpdb->insert_id;
                            }
                        }

                        // 4. Create/update Student
                        $student_id = SM_DB::student_exists($val_name, $val_grade, $val_section, $val_emp_num);
                        if ($student_id) {
                            SM_DB::update_student($student_id, array(
                                'name' => $val_name,
                                'class_name' => $val_grade,
                                'section' => $val_section,
                                'parent_email' => $val_email,
                                'guardian_phone' => $val_phone,
                                'national_id' => $val_emp_num,
                                'school_id' => $school_id,
                                'grade_id' => $grade_id,
                                'class_id' => $class_id,
                                'institution_id' => 1
                            ));
                        } else {
                            $extra = array(
                                'guardian_phone' => $val_phone,
                                'nationality' => '',
                                'national_id' => $val_emp_num,
                                'school_id' => $school_id,
                                'grade_id' => $grade_id,
                                'class_id' => $class_id,
                                'institution_id' => 1
                            );
                            $student_id = SM_DB::add_student($val_name, $val_grade, $val_email, '', null, null, $val_section, $extra);
                        }
                        if ($student_id) $count++;
                    }

                    elseif ($import_type === 'teachers') {
                        if (empty($val_username) || empty($val_name)) continue;

                        $user = get_user_by('login', $val_username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array('ID' => $user_id, 'user_email' => $val_email, 'display_name' => $val_name));
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $val_username,
                                'user_email' => $val_email ?: ($val_username . '@school.local'),
                                'display_name' => $val_name,
                                'user_pass' => $val_password
                            ));
                        }

                        if ($user_id && !is_wp_error($user_id)) {
                            SM_Settings::change_user_role($user_id, 'sm_teacher', array('specialization' => $val_specialization));
                            update_user_meta($user_id, 'sm_phone', $val_phone);
                            update_user_meta($user_id, 'eess_employee_number', $val_emp_num);
                            update_user_meta($user_id, 'eess_department', $val_dept);
                            update_user_meta($user_id, 'eess_school_name', $school_name);
                            update_user_meta($user_id, 'eess_school_id', $school_id);

                            // Assignment
                            $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                                'user_id' => $user_id,
                                'institution_id' => 1,
                                'school_id' => $school_id
                            ));
                            $count++;
                        }
                    }

                    elseif ($import_type === 'parents') {
                        if (empty($val_username) || empty($val_name)) continue;

                        $user = get_user_by('login', $val_username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array('ID' => $user_id, 'user_email' => $val_email, 'display_name' => $val_name));
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $val_username,
                                'user_email' => $val_email ?: ($val_username . '@parent.local'),
                                'display_name' => $val_name,
                                'user_pass' => $val_password
                            ));
                        }

                        if ($user_id && !is_wp_error($user_id)) {
                            SM_Settings::change_user_role($user_id, 'sm_parent');
                            update_user_meta($user_id, 'sm_phone', $val_phone);
                            update_user_meta($user_id, 'eess_school_name', $school_name);
                            update_user_meta($user_id, 'eess_school_id', $school_id);

                            // Map to child
                            if (!empty($val_student_code)) {
                                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sm_students SET parent_user_id = %d WHERE student_code = %s", $user_id, $val_student_code));
                            }
                            $count++;
                        }
                    }

                    elseif ($import_type === 'managers' || $import_type === 'users') {
                        if (empty($val_username) || empty($val_name)) continue;

                        $role = 'sm_supervisor';
                        if (!empty($val_role)) {
                            if ($val_role === 'sm_principal' || $val_role === 'principal' || $val_role === '') {
                                $role = 'sm_principal';
                            } elseif ($val_role === 'sm_system_admin' || $val_role === 'admin' || $val_role === '') {
                                $role = 'sm_system_admin';
                            }
                        }

                        $user = get_user_by('login', $val_username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array('ID' => $user_id, 'user_email' => $val_email, 'display_name' => $val_name));
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $val_username,
                                'user_email' => $val_email ?: ($val_username . '@user.local'),
                                'display_name' => $val_name,
                                'user_pass' => $val_password
                            ));
                        }

                        if ($user_id && !is_wp_error($user_id)) {
                            SM_Settings::change_user_role($user_id, $role);
                            update_user_meta($user_id, 'sm_phone', $val_phone);
                            update_user_meta($user_id, 'eess_school_name', $school_name);
                            update_user_meta($user_id, 'eess_school_id', $school_id);

                            $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));
                            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                                'user_id' => $user_id,
                                'institution_id' => 1,
                                'school_id' => $school_id
                            ));
                            $count++;
                        }
                    }
                }
                fclose($handle);
                wp_cache_flush();
                SM_Logger::log('Import   ', " Import ($count)   : $school_name.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Hierarchical Violations Save
        if (isset($_POST['sm_save_hierarchical_violations']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                $processed = array();
                if (isset($_POST['h_viol']) && is_array($_POST['h_viol'])) {
                    foreach ($_POST['h_viol'] as $level => $items) {
                        $processed[$level] = array();
                        foreach ($items as $item) {
                            if (!empty($item['name'])) {
                                $code = !empty($item['code']) ? $item['code'] : 'V'.rand(100,999);
                                $processed[$level][$code] = array(
                                    'name' => sanitize_text_field($item['name']),
                                    'points' => intval($item['points']),
                                    'action' => sanitize_text_field($item['action'])
                                );
                            }
                        }
                    }
                }
                SM_Settings::save_hierarchical_violations($processed);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Parent Call-in Request
        if (isset($_POST['sm_send_call_in']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_message_action')) {
            if (current_user_can('__Mother')) {
                $receiver_id = intval($_POST['receiver_id']);
                $message = "   : " . sanitize_textarea_field($_POST['message']);
                SM_DB::send_message(get_current_user_id(), $receiver_id, $message);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Generic User Update
        if (isset($_POST['sm_update_generic_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('_Users')) {
                $user_id = intval($_POST['edit_user_id']);
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name'])
                );
                if (!empty($_POST['user_pass'])) {
                    $user_data['user_pass'] = $_POST['user_pass'];
                }
                wp_update_user($user_data);

                SM_Settings::change_user_role($user_id, sanitize_text_field($_POST['user_role']), $_POST);

                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Record Saving
        if (isset($_POST['sm_save_record']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) {
            $record_id = SM_DB::add_record($_POST);
            if ($record_id) {
                SM_Notifications::send_violation_alert($record_id);
                $url = add_query_arg(array('sm_msg' => 'success', 'last_id' => $record_id), $_SERVER['REQUEST_URI']);
                wp_redirect($url);
                exit;
            }
        }

        // Handle Generic User Addition
        if (isset($_POST['sm_add_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('_Users')) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['user_login']),
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'user_pass' => $_POST['user_pass'],
                    'role' => sanitize_text_field($_POST['user_role'])
                );
                wp_insert_user($user_data);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Generic User Deletion
        if (isset($_POST['sm_delete_user']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_user_action')) {
            if (current_user_can('_Users')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user(intval($_POST['delete_user_id']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher Addition from Public Admin
        if (isset($_POST['sm_add_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('_')) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['user_login']),
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'user_pass' => $_POST['user_pass'],
                    'role' => 'sm_teacher'
                );
                $user_id = wp_insert_user($user_data);
                if (!is_wp_error($user_id)) {
                    update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
                    update_user_meta($user_id, 'sm_job_title', sanitize_text_field($_POST['job_title']));
                    update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
                    wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }

        // Handle Teacher Update
        if (isset($_POST['sm_update_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('_')) {
                $user_id = intval($_POST['edit_teacher_id']);
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => sanitize_email($_POST['user_email']),
                    'display_name' => sanitize_text_field($_POST['display_name'])
                );
                if (!empty($_POST['user_pass'])) {
                    $user_data['user_pass'] = $_POST['user_pass'];
                }
                wp_update_user($user_data);
                update_user_meta($user_id, 'sm_teacher_id', sanitize_text_field($_POST['teacher_id']));
                update_user_meta($user_id, 'sm_job_title', sanitize_text_field($_POST['job_title']));
                update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher Deletion
        if (isset($_POST['sm_delete_teacher']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_teacher_action')) {
            if (current_user_can('_')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user(intval($_POST['delete_teacher_id']));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Record Update
        if (isset($_POST['sm_update_record']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_record_action')) {
            if (current_user_can('_')) {
                SM_DB::update_record(intval($_POST['record_id']), $_POST);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Addition from Public Admin
        if (isset($_POST['add_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('_No')) {
                $parent_user_id = !empty($_POST['parent_user_id']) ? intval($_POST['parent_user_id']) : null;
                $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
                SM_DB::add_student($_POST['name'], $_POST['class'], $_POST['email'], $_POST['code'], $parent_user_id, $teacher_id);
                wp_redirect(add_query_arg('sm_admin_msg', 'student_added', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Deletion from Public Admin
        if (isset($_POST['delete_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('_No')) {
                SM_DB::delete_student($_POST['delete_student_id']);
                wp_redirect(add_query_arg('sm_admin_msg', 'student_deleted', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Student Update from Public Admin
        if (isset($_POST['sm_update_student']) && wp_verify_nonce($_POST['sm_nonce'], 'sm_add_student')) {
            if (current_user_can('_No')) {
                SM_DB::update_student(intval($_POST['student_id']), $_POST);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Backup Download
        if (isset($_POST['sm_download_backup']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                if (ob_get_length()) ob_clean();
                SM_Settings::record_backup_download();
                $data = SM_DB::get_backup_data();
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="sm_backup_'.date('Y-m-d').'.json"');
                header('Pragma: no-cache');
                header('Expires: 0');
                echo $data;
                exit;
            }
        }

        // Handle Restore
        if (isset($_POST['sm_restore_backup']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_') && !empty($_FILES['backup_file']['tmp_name'])) {
                $json = file_get_contents($_FILES['backup_file']['tmp_name']);
                if (SM_DB::restore_backup($json)) {
                    SM_Settings::record_backup_import();
                    wp_redirect(add_query_arg('sm_admin_msg', 'restored', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }

        // Handle Academic Structure Save
        if (isset($_POST['sm_save_academic_structure']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_') || current_user_can('manage_options')) {
                $existing = SM_Settings::get_academic_structure();
                if (isset($_POST['academic_year'])) {
                    $existing['academic_year'] = sanitize_text_field($_POST['academic_year']);
                }
                if (isset($_POST['terms_count'])) {
                    $existing['terms_count'] = intval($_POST['terms_count']);
                }
                if (!isset($existing['term_dates'])) {
                    $existing['term_dates'] = array('term1' => array(), 'term2' => array(), 'term3' => array());
                }
                if (isset($_POST['deadline_term1'])) {
                    $existing['term_dates']['term1']['deadline'] = sanitize_text_field($_POST['deadline_term1']);
                }
                if (isset($_POST['deadline_term2'])) {
                    $existing['term_dates']['term2']['deadline'] = sanitize_text_field($_POST['deadline_term2']);
                }
                if (isset($_POST['deadline_term3'])) {
                    $existing['term_dates']['term3']['deadline'] = sanitize_text_field($_POST['deadline_term3']);
                }
                if (isset($_POST['term_dates'])) {
                    $existing['term_dates'] = array_merge($existing['term_dates'], $_POST['term_dates']);
                }
                if (isset($_POST['academic_stages'])) {
                    $existing['academic_stages'] = $_POST['academic_stages'];
                }
                if (isset($_POST['grades_count'])) {
                    $existing['grades_count'] = intval($_POST['grades_count']);
                }
                if (isset($_POST['active_grades'])) {
                    $existing['active_grades'] = array_map('intval', $_POST['active_grades']);
                }
                SM_Settings::save_academic_structure($existing);
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Unified Settings Save (School Info)
        if (isset($_POST['sm_save_settings_unified']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                $existing = SM_Settings::get_school_info();
                SM_Settings::save_school_info(array(
                    'school_name' => sanitize_text_field($_POST['school_name']),
                    'school_principal_name' => isset($_POST['school_principal_name']) ? sanitize_text_field($_POST['school_principal_name']) : ($existing['school_principal_name'] ?? ''),
                    'school_logo' => esc_url_raw($_POST['school_logo']),
                    'address' => isset($_POST['school_address']) ? sanitize_text_field($_POST['school_address']) : ($existing['address'] ?? ''),
                    'email' => sanitize_email($_POST['school_email']),
                    'phone' => sanitize_text_field($_POST['school_phone']),
                    'working_schedule' => array(
                        'staff' => isset($_POST['work_staff']) ? array_map('sanitize_text_field', $_POST['work_staff']) : ($existing['working_schedule']['staff'] ?? array()),
                        'students' => isset($_POST['work_students']) ? array_map('sanitize_text_field', $_POST['work_students']) : ($existing['working_schedule']['students'] ?? array())
                    )
                ));
                SM_Logger::log('Update  ', " Update  Academy  : {$_POST['school_name']}");
                SM_Settings::save_academic_structure(array(
                    'terms_count' => intval($_POST['terms_count']),
                    'grades_count' => intval($_POST['grades_count']),
                    'grade_options' => sanitize_text_field($_POST['grade_options']),
                    'semester_start' => sanitize_text_field($_POST['semester_start']),
                    'semester_end' => sanitize_text_field($_POST['semester_end']),
                    'academic_stages' => sanitize_text_field($_POST['academic_stages'])
                ));
                SM_Settings::save_retention_settings(array(
                    'message_retention_days' => intval($_POST['message_retention_days'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }


        // Handle Central Numbering Settings Save
        if (isset($_POST['sm_save_central_numbering']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                EESS_ID_Code_Service::save_numbering_config($_POST);
                if (!empty($_POST['reset_student_counter_val'])) {
                    EESS_ID_Code_Service::get_next_sequence(1, 'student', intval($_POST['reset_student_counter_val']));
                }
                SM_Logger::log('Update   ', ' Edit     No .');
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Violation Settings Save
        if (isset($_POST['sm_save_violation_settings']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                SM_Logger::log('Update  ', " Update   Actions .");
                $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
                $types = array();
                foreach ($types_raw as $line) {
                    $parts = explode("|", $line);
                    if (count($parts) == 2) {
                        $types[trim($parts[0])] = trim($parts[1]);
                    }
                }
                if (!empty($types)) {
                    SM_Settings::save_violation_types($types);
                }
                SM_Settings::save_suggested_actions(array(
                    'low' => sanitize_textarea_field($_POST['suggested_low']),
                    'medium' => sanitize_textarea_field($_POST['suggested_medium']),
                    'high' => sanitize_textarea_field($_POST['suggested_high'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Print Templates Save
        // Handle Sidebar Visibility Settings Save
        if (isset($_POST['sm_save_sidebar_visibility']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                $roles_to_process = array(
                    'sm_system_admin', 'sm_principal', 'sm_supervisor', 'sm_coordinator',
                    'sm_teacher', 'sm_student', 'sm_parent', 'sm_discipline_supervisor',
                    'sm_activities_supervisor', 'sm_transportation_supervisor', 'sm_bus_supervisor', 'sm_hr'
                );
                $sections_to_process = array_keys(SM_Settings::get_system_modules());

                $visibility = array();
                $input = isset($_POST['sidebar_visibility']) ? $_POST['sidebar_visibility'] : array();

                foreach ($roles_to_process as $role) {
                    $visibility[$role] = array();
                    foreach ($sections_to_process as $sec) {
                        // Explicitly save false if not checked to prevent falling back to defaults
                        $visibility[$role][$sec] = !empty($input[$role][$sec]);
                    }
                }

                SM_Settings::save_sidebar_visibility($visibility);

                // IMMEDIATELY INVALIDATE CACHES & TRANSIENTS
                global $wpdb;
                $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
                $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");
                wp_cache_flush();
                $users = get_users(array('fields' => array('ID')));
                foreach ($users as $u) {
                    clean_user_cache($u->ID);
                }

                SM_Logger::log('Update   ', '        Cancel   .');
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Notifications Settings Save
        if (isset($_POST['sm_save_notif']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                SM_Settings::save_notifications(array(
                    'email_subject' => sanitize_text_field($_POST['email_subject']),
                    'email_template' => sanitize_textarea_field($_POST['email_template']),
                    'whatsapp_template' => sanitize_textarea_field($_POST['whatsapp_template']),
                    'internal_template' => sanitize_textarea_field($_POST['internal_template'])
                ));
                wp_redirect(add_query_arg('sm_admin_msg', 'settings_saved', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Full Reset
        if (isset($_POST['sm_full_reset']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                if ($_POST['reset_password'] === '1011996') {
                    SM_DB::delete_all_data();
                    wp_redirect(add_query_arg('sm_admin_msg', 'demo_deleted', $_SERVER['REQUEST_URI']));
                    exit;
                } else {
                    wp_redirect(add_query_arg('sm_admin_msg', 'error', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }


        // Handle Unified Users CSV Import
        if (isset($_POST['sm_import_users_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_Users') && !empty($_FILES['csv_file']['tmp_name'])) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 3) {
                        $username = sanitize_user($data[0]);
                        $email = !empty($data[1]) ? sanitize_email($data[1]) : $username . '@school-system.local';
                        $display_name = sanitize_text_field($data[2]);
                        $role = isset($data[3]) ? sanitize_text_field($data[3]) : 'sm_teacher';
                        $phone = isset($data[4]) ? sanitize_text_field($data[4]) : '';
                        $password = !empty($data[5]) ? $data[5] : wp_generate_password();
                        $photo_url = isset($data[6]) ? esc_url_raw($data[6]) : '';
                        $specialization = isset($data[7]) ? sanitize_text_field($data[7]) : '';

                        // If user exists, update them; otherwise, insert them!
                        $user = get_user_by('login', $username);
                        if ($user) {
                            $user_id = $user->ID;
                            wp_update_user(array(
                                'ID' => $user_id,
                                'user_email' => $email,
                                'display_name' => $display_name,
                                'user_pass' => $password
                            ));
                            $u_obj = new WP_User($user_id);
                            $u_obj->set_role($role);
                        } else {
                            $user_id = wp_insert_user(array(
                                'user_login' => $username,
                                'user_email' => $email,
                                'display_name' => $display_name,
                                'user_pass' => $password,
                                'role' => $role
                            ));
                        }

                        if (!is_wp_error($user_id)) {
                            $count++;
                            update_user_meta($user_id, 'sm_temp_pass', $password);
                            if (!empty($phone)) {
                                update_user_meta($user_id, 'sm_phone', $phone);
                            }
                            if (!empty($photo_url)) {
                                update_user_meta($user_id, 'eess_profile_photo', $photo_url);
                            }
                            if (!empty($specialization)) {
                                update_user_meta($user_id, 'sm_specialization', $specialization);
                            }
                            clean_user_cache($user_id);
                        }
                    }
                }
                fclose($handle);
                wp_cache_flush();
                SM_Logger::log('Import  ()', " Import ($count)     CSV.");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Teacher CSV Upload
        if (isset($_POST['sm_import_teachers_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_') && !empty($_FILES['csv_file']['tmp_name'])) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                $header = fgetcsv($handle); // skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 3) {
                        // username, email, name, teacher_id, job_title, phone, pass
                        $user_id = wp_insert_user(array(
                            'user_login' => $data[0],
                            'user_email' => $data[1],
                            'display_name' => $data[2],
                            'user_pass' => isset($data[6]) ? $data[6] : wp_generate_password(),
                            'role' => 'sm_teacher'
                        ));
                        if (!is_wp_error($user_id)) {
                            $count++;
                            update_user_meta($user_id, 'sm_teacher_id', isset($data[3]) ? $data[3] : '');
                            update_user_meta($user_id, 'sm_job_title', isset($data[4]) ? $data[4] : '');
                            update_user_meta($user_id, 'sm_phone', isset($data[5]) ? $data[5] : '');
                        }
                    }
                }
                fclose($handle);
                SM_Logger::log('Import  ()', " Import ($count)  .");
                wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Handle Violation CSV/Excel Upload
        if (isset($_POST['sm_import_violations_csv']) && wp_verify_nonce($_POST['sm_admin_nonce'], 'sm_admin_action')) {
            if (current_user_can('_')) {
                if (!empty($_FILES['csv_file']['tmp_name'])) {
                    $handle = fopen($_FILES['csv_file']['tmp_name'], "r");

                    // Detect delimiter
                    $first_line = fgets($handle);
                    rewind($handle);
                    $delimiters = [',', ';', "\t", '|'];
                    $delimiter = ',';
                    $max_count = -1;
                    foreach ($delimiters as $d) {
                        $count = substr_count($first_line, $d);
                        if ($count > $max_count) {
                            $max_count = $count;
                            $delimiter = $d;
                        }
                    }

                    $header = fgetcsv($handle, 0, $delimiter); // skip header
                    $count = 0;
                    $errors_count = 0;

                    while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        if (empty($data) || (count($data) == 1 && empty($data[0]))) continue;

                        // Encoding fix
                        foreach ($data as $k => $v) {
                            $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                            if ($encoding && $encoding != 'UTF-8') {
                                $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                            }
                            $data[$k] = trim($data[$k]);
                        }

                        $student_code = $data[0] ?? '';
                        if (empty($student_code)) continue;

                        // Search Student Number in Student Management as primary relationship key
                        $student = SM_DB::get_student_by_code($student_code);
                        if (!$student) {
                            // Fallback search by Student ID or National ID or Name
                            global $wpdb;
                            $student = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}sm_students WHERE student_code = %s OR national_id = %s OR name = %s",
                                $student_code, $student_code, $student_code
                            ));
                        }

                        if (!$student) {
                            $errors_count++;
                            continue;
                        }

                        // Determine structure format (Standard 18-column vs legacy 6-column)
                        if (count($data) >= 10) {
                            // Col A: Student Number, B: Student Name, C: Nationality, D: School, E: Grade, F: Section
                            // Col G: Violation Type, H: Violation Code, I: Violation Details, J: Date
                            // Col K: Time, L: Location, M: Severity, N: Frequency, O: Action Taken, P: Status, Q: Recorded By, R: Notes
                            $v_type        = !empty($data[6]) ? $data[6] : (!empty($data[1]) ? $data[1] : '');
                            $v_code        = !empty($data[7]) ? $data[7] : '';
                            $v_details     = !empty($data[8]) ? $data[8] : (!empty($data[3]) ? $data[3] : ' ');
                            $v_date        = !empty($data[9]) ? $data[9] : '';
                            $v_severity    = !empty($data[12]) ? $data[12] : (!empty($data[2]) ? $data[2] : 'low');
                            $v_action      = !empty($data[14]) ? $data[14] : (!empty($data[4]) ? $data[4] : '');

                            // Normalize Severity
                            $sev_map = array(
                                '' => 'low', '' => 'low', 'low' => 'low',
                                'Intermediate' => 'medium', 'medium' => 'medium',
                                '' => 'high', '' => 'high', '' => 'high', 'high' => 'high', 'severe' => 'high'
                            );
                            $v_severity = $sev_map[$v_severity] ?? 'low';

                            $record_data = array(
                                'student_id'     => $student->id,
                                'type'           => $v_type,
                                'violation_code' => $v_code,
                                'severity'       => $v_severity,
                                'details'        => $v_details,
                                'action_taken'   => $v_action
                            );
                            if (!empty($v_date)) {
                                $record_data['custom_date'] = date('Y-m-d', strtotime($v_date));
                            }

                            $rid = SM_DB::add_record($record_data, true);
                            if ($rid) {
                                $count++;
                                SM_Notifications::send_violation_alert($rid);
                            }
                        } elseif (count($data) >= 4) {
                            // Legacy format: code, type, severity, details, action, reward
                            $rid = SM_DB::add_record(array(
                                'student_id'   => $student->id,
                                'type'         => $data[1],
                                'severity'     => $data[2],
                                'details'      => $data[3],
                                'action_taken' => isset($data[4]) ? $data[4] : '',
                            ), true);
                            if ($rid) {
                                $count++;
                                SM_Notifications::send_violation_alert($rid);
                            }
                        }
                    }
                    fclose($handle);
                    SM_Logger::log('Import  ()', " Import ($count)  .");
                    wp_redirect(add_query_arg('sm_admin_msg', 'csv_imported', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }
        }
    }

    public function ajax_print_student_full_report() {
        if (!is_user_logged_in() || (!current_user_can('_No') && !current_user_can('manage_options'))) {
            wp_die('Unauthorized     No.');
        }

        $student_id = intval($_GET['student_id'] ?? 0);
        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_die(' No   .');

        $school_info = SM_Settings::get_school_info();

        // Resolve actual School and Institution
        $sch_obj = !empty($student->school_id) ? EESS_Org_Helper::get_school_by_id($student->school_id) : null;
        $inst_obj = ($sch_obj && !empty($sch_obj->institution_id)) ? EESS_Org_Helper::get_institution_by_id($sch_obj->institution_id) : null;

        $inst_name = $inst_obj ? $inst_obj->name : ($school_info['school_name'] ?? '   ');
        $sch_name  = $sch_obj ? $sch_obj->name : ($school_info['school_name'] ?? '  ');
        $logo_url  = !empty($sch_obj->school_logo) ? $sch_obj->school_logo : (!empty($inst_obj->logo_url) ? $inst_obj->logo_url : ($school_info['school_logo'] ?? ''));

        global $wpdb;
        $violations = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_records WHERE student_id = %d ORDER BY created_at DESC", $student_id));
        $grades     = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_grades WHERE student_id = %d ORDER BY created_at DESC", $student_id));

        $status_labels = array(
            'Active' => 'Active ',
            'Inactive' => ' Active',
            'Graduated' => ' ',
            'Withdrawn' => ''
        );
        $enroll_labels = array(
            'Enrolled' => ' ',
            'Pending' => 'Pending',
            'Transferred' => ''
        );
        $severity_labels = array(
            'low' => ' ',
            'medium' => 'Intermediate ',
            'high' => ' '
        );
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>   No - <?php echo esc_html($student->name); ?></title>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
            <style>
                * { box-sizing: border-box; }
                body { font-family: 'Cairo', Arial, sans-serif; padding: 30px; color: #0f172a; background: #ffffff; line-height: 1.6; direction: rtl; text-align: right; }
                .report-header { border-bottom: 3px double #881337; padding-bottom: 18px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
                .header-logo { max-height: 70px; max-width: 180px; object-fit: contain; }
                .report-title-box { text-align: right; }
                .report-title { font-size: 20px; font-weight: 900; color: #881337; margin: 0 0 4px 0; }
                .report-subtitle { font-size: 12px; color: #475569; margin: 0; font-weight: 700; }

                .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; font-size: 12.5px; }
                .meta-table th, .meta-table td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: right; }
                .meta-table th { background: #f8fafc; color: #1e293b; font-weight: 800; width: 22%; }

                .section-header { font-size: 14.5px; font-weight: 900; color: #881337; background: #fff5f5; border-right: 4px solid #881337; padding: 6px 12px; margin: 24px 0 10px 0; border-radius: 4px; }
                .data-grid-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
                .data-grid-table th { background: #0f172a; color: #ffffff; padding: 8px 10px; font-weight: 800; text-align: right; }
                .data-grid-table td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: right; }
                .data-grid-table tr:nth-child(even) { background: #f8fafc; }

                .footer-sign { margin-top: 40px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 800; border-top: 1px solid #e2e8f0; padding-top: 20px; }
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 0; }
                    @page { size: A4; margin: 15mm; }
                }
            </style>
        </head>
        <body onload="window.print()">
            <div class="no-print" style="background:#f8fafc; padding:12px; border:1px solid #cbd5e1; border-radius:10px; margin-bottom:20px; text-align:center;">
                <button onclick="window.print()" style="background:#881337; color:#ffffff; border:none; padding:10px 24px; font-weight:800; border-radius:8px; cursor:pointer; font-family:'Cairo'; font-size:13px;">️ Print   No  (A4 / PDF)</button>
            </div>

            <!-- Official Header -->
            <div class="report-header">
                <div class="report-title-box">
                    <div style="font-size: 12px; color: #64748b; font-weight: 800;"><?php echo esc_html($inst_name); ?></div>
                    <h1 class="report-title"><?php echo esc_html($sch_name); ?></h1>
                    <p class="report-subtitle">   No    </p>
                </div>
                <?php if (!empty($logo_url)): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" class="header-logo" alt=" " onerror="this.style.display='none'">
                <?php else: ?>
                    <div style="font-size: 22px; font-weight: 900; color: #881337; letter-spacing: 1px;">EESS</div>
                <?php endif; ?>
            </div>

            <!-- Section 1: Personal & Identity Data -->
            <div class="section-header">1. Personal Information  No  </div>
            <table class="meta-table">
                <tr>
                    <th>Player Name :</th>
                    <td><strong><?php echo esc_html($student->name); ?></strong></td>
                    <th> No :</th>
                    <td><strong style="color:#881337;"><?php echo esc_html($student->student_code ?: $student->student_id); ?></strong></td>
                </tr>
                <tr>
                    <th>Gender:</th>
                    <td><?php echo esc_html($student->gender ?: 'Male'); ?></td>
                    <th>Date of Birth:</th>
                    <td><?php echo esc_html($student->dob ?: ' '); ?></td>
                </tr>
                <tr>
                    <th>Gender:</th>
                    <td><?php echo esc_html($student->nationality ?: ''); ?></td>
                    <th> National ID / :</th>
                    <td><?php echo esc_html($student->national_id ?: ' '); ?></td>
                </tr>
            </table>

            <!-- Section 2: Organizational Placement -->
            <div class="section-header">2.     </div>
            <table class="meta-table">
                <tr>
                    <th>Organization  Academy :</th>
                    <td><?php echo esc_html($sch_name); ?></td>
                    <th>Training Group Training Group:</th>
                    <td><?php echo esc_html(($student->class_name ?: ' ') . ' -  (' . ($student->section ?: '') . ')'); ?></td>
                </tr>
                <tr>
                    <th>Level :</th>
                    <td><?php echo esc_html($student->academic_level ?: ''); ?></td>
                    <th>Registration Date :</th>
                    <td><?php echo esc_html($student->registration_date ?: $student->enrollment_date ?: date('Y-m-d')); ?></td>
                </tr>
                <tr>
                    <th> No:</th>
                    <td><strong style="color:#166534;"><?php echo esc_html($status_labels[$student->student_status] ?? ($student->student_status ?: 'Active ')); ?></strong></td>
                    <th>  :</th>
                    <td><?php echo esc_html($enroll_labels[$student->enrollment_status] ?? ($student->enrollment_status ?: ' ')); ?></td>
                </tr>
            </table>

            <!-- Section 3: Guardian & Contact Details -->
            <div class="section-header">3.   Mother  </div>
            <table class="meta-table">
                <tr>
                    <th>  Mother:</th>
                    <td><?php echo esc_html($student->guardian_name ?: ' '); ?></td>
                    <th>Relationship:</th>
                    <td><?php echo esc_html($student->guardian_relationship ?: ''); ?></td>
                </tr>
                <tr>
                    <th>Email Address  Mother:</th>
                    <td><?php echo esc_html($student->parent_email ?: ' '); ?></td>
                    <th>   ():</th>
                    <td><?php echo esc_html($student->guardian_phone ?: ' '); ?></td>
                </tr>
                <tr>
                    <th> :</th>
                    <td><?php echo esc_html($student->emirate ?: ''); ?></td>
                    <th>  :</th>
                    <td><?php echo esc_html($student->address ?: ' '); ?></td>
                </tr>
            </table>

            <!-- Section 4: Academic Performance Record -->
            <div class="section-header">4.     </div>
            <table class="data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Sport Activity </th>
                        <th style="width: 25%;">Training Group </th>
                        <th style="width: 20%;"> </th>
                        <th style="width: 20%;">  </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b; padding:14px;">No      No.</td></tr>
                    <?php else: ?>
                        <?php foreach($grades as $g): ?>
                            <tr>
                                <td><strong><?php echo esc_html($g->subject ?: ' '); ?></strong></td>
                                <td><?php echo esc_html($g->term ?: 'Training Group '); ?></td>
                                <td><strong style="color:#881337; font-size:13px;"><?php echo esc_html($g->grade_val ?? $g->score ?? '100'); ?></strong></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($g->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Section 5: Behavioral & Discipline Record -->
            <div class="section-header">5.  No Notes </div>
            <table class="data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Registration Date</th>
                        <th style="width: 35%;"> No / </th>
                        <th style="width: 20%;">Level </th>
                        <th style="width: 25%;">  </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($violations)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#166534; padding:14px; font-weight:800;">✓  No No   No     .</td></tr>
                    <?php else: ?>
                        <?php foreach($violations as $v): ?>
                            <tr>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($v->created_at ?? $v->incident_date))); ?></td>
                                <td><strong><?php echo esc_html($v->type ?? $v->details ?? 'No '); ?></strong></td>
                                <td><?php echo esc_html($severity_labels[$v->severity] ?? ($v->severity ?: '')); ?></td>
                                <td><?php echo esc_html($v->action_taken ?: 'No  '); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Section 6: Medical & Special Needs Info -->
            <div class="section-header">6.     </div>
            <table class="meta-table">
                <tr>
                    <th>  :</th>
                    <td><?php echo esc_html((!empty($student->special_needs) && !in_array($student->special_needs, array('No', 'No', '0', 'none'), true)) ? 'Yes ( )' : 'No'); ?></td>
                    <th>Status  :</th>
                    <td><?php echo esc_html($student->health_status ?: ' '); ?></td>
                </tr>
                <tr>
                    <th>   (Allergies):</th>
                    <td colspan="3"><?php echo esc_html($student->allergies ?: 'No   '); ?></td>
                </tr>
            </table>

            <!-- Report Footer & Official Stamp -->
            <div class="footer-sign">
                <div>   : <strong><?php echo current_time('Y-m-d H:i'); ?></strong></div>
                <div>  Player Affairs: ..............................</div>
                <div> Academy  : ..............................</div>
            </div>

            <div style="margin-top: 30px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 10px; color: #64748b;">
                 Export     Sportedia Sports Management System — eess.online
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    public function ajax_download_student_import_template() {
        if (!current_user_can('_No') && !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=student_import_template_12cols.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // Standardized 12 Columns in exact specified order
        fputcsv($output, array(
            ' Academy ',
            ' No',
            'Full Name',
            'National ID',
            'Gender',
            'Date of Birth',
            'Gender',
            ' ',
            'Training Group',
            'Training Group',
            '  Mother',
            '   Mother'
        ));

        // Official Sample Rows
        fputcsv($output, array('1', 'STU-1001', '  ', '784199012345678', 'Male', '2015-05-12', 'United Arab Emirates', '', '10', '1', ' ', '+971501234567'));
        fputcsv($output, array('1', 'STU-1002', '  ', '784199298765432', 'Female', '2016-08-20', 'United Arab Emirates', '', '10', '2', ' ', '+971509876543'));

        fclose($output);
        exit;
    }

    public function ajax_export_students_csv() {
        if (!current_user_can('_No')) {
            wp_die('Unauthorized');
        }
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_GET['nonce'] ?? '', 'eess_admin_action')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);

        $where_clauses = array();
        $params = array();

        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools'])) {
                $school_ids_clean = implode(',', array_map('intval', $user_scope['schools']));
                $where_clauses[] = "(school_id IN ($school_ids_clean) OR institution_id IN ($school_ids_clean))";
            } else {
                $where_clauses[] = "1=0";
            }
        }

        $sql = "SELECT * FROM {$wpdb->prefix}sm_students";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $sql .= " ORDER BY name ASC";

        $records = $wpdb->get_results($sql);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=student_affairs_export_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // Standardized 12-Column Format matching Student Import Specification
        fputcsv($output, array(
            ' Academy  (School Code)',
            ' No (Student Code)',
            'Full Name (Full Name)',
            ' National ID (National ID)',
            'Gender (Gender)',
            'Date of Birth (Date of Birth)',
            'Gender (Nationality)',
            '  (Emirate)',
            'Training Group (Grade)',
            'Training Group (Section)',
            '  Mother (Guardian Name)',
            '   Mother (Guardian Phone)'
        ));

        // Pre-cache institutions for code lookup
        $inst_codes = $wpdb->get_results("SELECT id, code FROM {$wpdb->prefix}eess_institutions", OBJECT_K);

        foreach ($records as $r) {
            $sid = intval($r->school_id ?: $r->institution_id ?: 1);
            $school_code = isset($inst_codes[$sid]) ? $inst_codes[$sid]->code : $sid;

            fputcsv($output, array(
                $school_code,
                $r->student_code,
                $r->name,
                $r->national_id,
                $r->gender ?: 'Male',
                $r->dob ?: '',
                $r->nationality ?: 'United Arab Emirates',
                $r->emirate ?: '',
                $r->class_name,
                $r->section,
                $r->guardian_name,
                $r->guardian_phone
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_export_grades_csv() {
        $roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();
        $can_grades = in_array('sm_teacher', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_principal', $roles) || current_user_can('manage_grades') || current_user_can('manage_options');
        if (!is_user_logged_in() || !$can_grades) {
            wp_die('Unauthorized');
        }

        $user = wp_get_current_user();
        $assigned_subject = get_user_meta($user->ID, 'sm_specialization', true) ?: '';

        global $wpdb;
        $query = "SELECT s.id as student_id, s.name as student_name, s.class_name, s.section, sch.name as school_name
                  FROM {$wpdb->prefix}sm_students s
                  LEFT JOIN {$wpdb->prefix}eess_schools sch ON s.school_id = sch.id
                  ORDER BY s.class_name ASC, s.section ASC, s.name ASC";
        $students = $wpdb->get_results($query);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=grades_template_'.date('Y-m-d').'.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // 10 Columns: A-Student ID, B-Student Name, C-School, D-Grade, E-Section, F-Subject, G-Assessment Type, H-Score, I-Max Score, J-Notes
        fputcsv($output, array('Student ID', 'Student Name', 'School', 'Grade', 'Section', 'Subject', 'Assessment Type', 'Score', 'Max Score', 'Notes'));

        foreach ($students as $st) {
            fputcsv($output, array(
                $st->student_id,
                $st->student_name,
                $st->school_name ?: 'Academy  Home',
                $st->class_name,
                $st->section,
                $assigned_subject ?: '',
                'Training Group ',
                '',
                '100',
                ''
            ));
        }
        fclose($output);
        exit;
    }

    public function ajax_import_grades_csv() {
        $roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();
        $can_grades = in_array('sm_teacher', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_principal', $roles) || current_user_can('manage_grades') || current_user_can('manage_options');
        if (!is_user_logged_in() || !$can_grades) {
            wp_send_json_error('Unauthorized');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('   CSV  .');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error('   .');
        }

        // Skip BOM & Header row
        $header = fgetcsv($handle);

        global $wpdb;
        $success_count = 0;
        $error_count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (empty($data[0])) continue;

            $student_id  = intval($data[0]);
            $subject     = sanitize_text_field($data[5] ?? '');
            $term        = sanitize_text_field($data[6] ?? 'Training Group ');
            $score_val   = sanitize_text_field($data[7] ?? '');
            $notes       = sanitize_text_field($data[9] ?? '');

            if (!$student_id || $score_val === '') {
                $error_count++;
                continue;
            }

            $res = $wpdb->insert(
                "{$wpdb->prefix}sm_grades",
                array(
                    'student_id' => $student_id,
                    'subject'    => $subject,
                    'term'       => $term,
                    'grade_val'  => $score_val,
                    'created_at' => current_time('mysql')
                )
            );

            if ($res) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        fclose($handle);

        wp_send_json_success(array(
            'message' => " Import $success_count  ." . ($error_count > 0 ? " ( Import $error_count   )" : "")
        ));
    }

    public function ajax_upload_import_csv() {
        if (!current_user_can('_No') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        if (empty($_FILES['csv_file']['tmp_name'])) wp_send_json_error('No file uploaded');

        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sm_temp';
        if (!file_exists($temp_dir)) wp_mkdir_p($temp_dir);

        $job_id = 'imp_' . md5(uniqid(microtime(), true));
        $file_name = 'import_' . $job_id . '.csv';
        $file_path = $temp_dir . '/' . $file_name;

        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $file_path)) {
            // Count total rows
            $handle = fopen($file_path, "r");
            $total_rows = 0;
            while (fgetcsv($handle) !== FALSE) {
                $total_rows++;
            }
            fclose($handle);

            $job_state = array(
                'job_id'          => $job_id,
                'file_path'       => $file_path,
                'total_rows'      => max(0, $total_rows - 1),
                'processed_rows' => 0,
                'last_row_index'  => 0,
                'success'         => 0,
                'duplicate'       => 0,
                'error'           => 0,
                'details'         => array(),
                'failed_rows_log' => array()
            );

            update_option('eess_import_job_' . $job_id, $job_state, false);

            wp_send_json_success(array(
                'job_id'    => $job_id,
                'file_path' => $file_path,
                'total'     => max(0, $total_rows - 1)
            ));
        } else {
            wp_send_json_error('Failed to move uploaded file');
        }
    }

    public function ajax_process_import_chunk() {
        if (!current_user_can('_No') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_admin_action')) wp_send_json_error('Security check failed');

        $job_id = sanitize_text_field($_POST['job_id'] ?? '');
        $file_path_param = sanitize_text_field($_POST['file_path'] ?? '');

        if (empty($job_id) && !empty($file_path_param)) {
            $job_id = 'imp_' . md5($file_path_param);
        }

        $job_state = get_option('eess_import_job_' . $job_id);

        if (!$job_state) {
            if (!empty($file_path_param) && file_exists($file_path_param)) {
                $handle = fopen($file_path_param, "r");
                $total_rows = 0;
                while (fgetcsv($handle) !== FALSE) $total_rows++;
                fclose($handle);

                $job_state = array(
                    'job_id'          => $job_id,
                    'file_path'       => $file_path_param,
                    'total_rows'      => max(0, $total_rows - 1),
                    'processed_rows' => 0,
                    'last_row_index'  => 0,
                    'success'         => 0,
                    'duplicate'       => 0,
                    'error'           => 0,
                    'details'         => array(),
                    'failed_rows_log' => array()
                );
            } else {
                wp_send_json_error(' Import    .');
            }
        }

        $file_path = $job_state['file_path'];
        if (!file_exists($file_path)) {
            wp_send_json_error('      .');
        }

        $handle = fopen($file_path, "r");

        // Detect delimiter
        $first_line = fgets($handle);
        rewind($handle);
        $delimiters = [',', ';', "\t", '|'];
        $delimiter = ',';
        $max_count = -1;
        foreach ($delimiters as $d) {
            $count = substr_count($first_line, $d);
            if ($count > $max_count) {
                $max_count = $count;
                $delimiter = $d;
            }
        }

        // Skip header
        fgetcsv($handle, 0, $delimiter);

        // Fast seek to last processed row index
        $start_offset = intval($job_state['last_row_index']);
        for ($i = 0; $i < $start_offset; $i++) {
            fgetcsv($handle, 0, $delimiter);
        }

        $processed = 0;
        $chunk_size = 25;
        global $wpdb;

        // Pre-cache institutions & schools in memory for high-speed row resolution
        $cached_institutions = $wpdb->get_results("SELECT id, code, name FROM {$wpdb->prefix}eess_institutions WHERE status='active'", OBJECT_K);
        $cached_schools      = $wpdb->get_results("SELECT id, school_code as code, name FROM {$wpdb->prefix}eess_schools WHERE status='active'", OBJECT_K);

        while ($processed < $chunk_size && ($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $processed++;
            $row_index = $start_offset + $processed + 1;

            try {
                // Encoding Normalization
                foreach ($data as $k => $v) {
                    $encoding = mb_detect_encoding($v, array('UTF-8', 'ISO-8859-6', 'ISO-8859-1'), true);
                    if ($encoding && $encoding != 'UTF-8') {
                        $data[$k] = mb_convert_encoding($v, 'UTF-8', $encoding);
                    }
                }

                if (count($data) < 12) {
                    $job_state['error']++;
                    $msg = " $row_index:   " . count($data) . "   ( 12 ).";
                    $job_state['details'][] = array('type' => 'error', 'msg' => $msg);
                    $job_state['failed_rows_log'][] = array('row' => $row_index, 'data' => implode(' | ', $data), 'reason' => '    12');
                    continue;
                }

                $school_code_input   = trim($data[0] ?? '');
                $student_code_input  = trim($data[1] ?? '');
                $name_input          = trim($data[2] ?? '');
                $national_id_input   = trim($data[3] ?? '');
                $gender_input        = trim($data[4] ?? 'Male');
                $dob_input           = trim($data[5] ?? '');
                $nationality_input  = trim($data[6] ?? 'United Arab Emirates');
                $emirate_input       = trim($data[7] ?? '');
                $grade_input         = trim($data[8] ?? '');
                $section_input       = trim($data[9] ?? '');
                $guardian_name_input = trim($data[10] ?? '');
                $guardian_phone_input= trim($data[11] ?? '');

                // 1. Validate School Code
                $inst_match_id = null;
                if (!empty($school_code_input)) {
                    if (is_numeric($school_code_input)) {
                        $ic = intval($school_code_input);
                        foreach ($cached_institutions as $ci) {
                            if (intval($ci->code) === $ic || intval($ci->id) === $ic) {
                                $inst_match_id = intval($ci->id);
                                break;
                            }
                        }
                        if (!$inst_match_id) {
                            foreach ($cached_schools as $cs) {
                                if (intval($cs->code) === $ic || intval($cs->id) === $ic) {
                                    $inst_match_id = intval($cs->id);
                                    break;
                                }
                            }
                        }
                    } else {
                        foreach ($cached_institutions as $ci) {
                            if ($ci->name === $school_code_input || (string)$ci->code === $school_code_input) {
                                $inst_match_id = intval($ci->id);
                                break;
                            }
                        }
                    }
                }

                if (!empty($school_code_input) && !$inst_match_id) {
                    $job_state['error']++;
                    $msg = " $row_index:  Academy  '{$school_code_input}'      .";
                    $job_state['details'][] = array('type' => 'error', 'msg' => $msg);
                    $job_state['failed_rows_log'][] = array('row' => $row_index, 'data' => implode(' | ', $data), 'reason' => " Academy    ($school_code_input)");
                    continue;
                }

                // 2. Validate Student Full Name
                if (empty($name_input)) {
                    $job_state['error']++;
                    $msg = " $row_index: Player Name  No  Import   No.";
                    $job_state['details'][] = array('type' => 'error', 'msg' => $msg);
                    $job_state['failed_rows_log'][] = array('row' => $row_index, 'data' => implode(' | ', $data), 'reason' => "Player Name ");
                    continue;
                }

                // 3. Validate Grade Code
                if (is_numeric($grade_input)) {
                    $grade_num = intval($grade_input);
                    if ($grade_num < 1 || $grade_num > 12) {
                        $job_state['error']++;
                        $msg = " $row_index:  Training Group '{$grade_input}'   (    1  12).";
                        $job_state['details'][] = array('type' => 'error', 'msg' => $msg);
                        $job_state['failed_rows_log'][] = array('row' => $row_index, 'data' => implode(' | ', $data), 'reason' => " Training Group   ($grade_input)");
                        continue;
                    }
                }

                $row_data = array(
                    'school_id'       => $inst_match_id ?: 1,
                    'student_code'    => $student_code_input,
                    'name'            => $name_input,
                    'national_id'     => $national_id_input,
                    'gender'          => $gender_input,
                    'dob'             => $dob_input,
                    'nationality'     => $nationality_input,
                    'emirate'         => $emirate_input,
                    'class_name'      => $grade_input,
                    'section'         => $section_input,
                    'guardian_name'   => $guardian_name_input,
                    'guardian_phone'  => $guardian_phone_input
                );

                $is_existing = false;
                if (!empty($student_code_input)) {
                    $is_existing = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s LIMIT 1", $student_code_input));
                }
                if (!$is_existing && !empty($national_id_input)) {
                    $is_existing = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s LIMIT 1", $national_id_input));
                }

                $saved_id = EESS_Student_Data_Service::process_and_save_student($row_data);
                if (is_wp_error($saved_id)) {
                    $job_state['error']++;
                    $errMsg = $saved_id->get_error_message();
                    $job_state['details'][] = array('type' => 'error', 'msg' => " $row_index: " . $errMsg);
                    $job_state['failed_rows_log'][] = array('row' => $row_index, 'data' => implode(' | ', $data), 'reason' => $errMsg);
                } else {
                    $job_state['success']++;
                    if ($is_existing) {
                        $job_state['duplicate']++;
                        $job_state['details'][] = array('type' => 'info', 'msg' => " Update  ({$name_input})   $row_index");
                    }
                }
            } catch (\Throwable $ex) {
                $job_state['error']++;
                $job_state['details'][] = array('type' => 'error', 'msg' => " $row_index:    — " . $ex->getMessage());
                $job_state['failed_rows_log'][] = array('row' => $row_index, 'data' => implode(' | ', $data), 'reason' => $ex->getMessage());
                continue;
            }
        }

        fclose($handle);

        $job_state['last_row_index'] = $start_offset + $processed;
        $job_state['processed_rows'] = $job_state['last_row_index'];

        $is_finished = ($job_state['processed_rows'] >= $job_state['total_rows']) || ($processed < $chunk_size);

        if ($is_finished) {
            $job_state['finished'] = true;
            @unlink($file_path);
            SM_Logger::log('Import No ()', " No  Import {$job_state['success']} No .");
        }

        update_option('eess_import_job_' . $job_id, $job_state, false);

        wp_send_json_success(array(
            'job_id'           => $job_id,
            'processed_batch'  => $processed,
            'finished'         => $is_finished,
            'total_rows'       => $job_state['total_rows'],
            'processed_so_far' => $job_state['processed_rows'],
            'success'          => $job_state['success'],
            'duplicate'        => $job_state['duplicate'],
            'error'            => $job_state['error'],
            'details'          => array_slice($job_state['details'], -20),
            'has_failed_logs'  => !empty($job_state['failed_rows_log'])
        ));
    }

    public function ajax_download_import_error_log() {
        if (!current_user_can('_No') && !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $job_id = sanitize_text_field($_GET['job_id'] ?? '');
        $job_state = get_option('eess_import_job_' . $job_id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=import_error_log_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        fputcsv($output, array(' ', ' ', '  / '));

        if ($job_state && !empty($job_state['failed_rows_log'])) {
            foreach ($job_state['failed_rows_log'] as $item) {
                fputcsv($output, array(
                    $item['row'] ?? '',
                    $item['data'] ?? '',
                    $item['reason'] ?? ''
                ));
            }
        }

        fclose($output);
        exit;
    }

    public function ajax_eess_admin_delete_institution_students() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin = in_array('administrator', $user_roles, true) || in_array('sm_system_admin', $user_roles, true) || current_user_can('manage_options');
        if (!$is_sys_admin) wp_send_json_error('    No   .');

        check_ajax_referer('sm_admin_action', 'nonce');

        $inst_id = intval($_POST['inst_id'] ?? 0);
        if ($inst_id <= 0) wp_send_json_error('  Organization   .');

        global $wpdb;
        $inst = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_institutions WHERE id = %d LIMIT 1", $inst_id));
        if (!$inst) wp_send_json_error('Organization   .');

        // Clean up associated exit card requests for students in this institution
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id IN (SELECT id FROM {$wpdb->prefix}sm_students WHERE institution_id = %d OR school_id = %d)",
            $inst_id, $inst_id
        ));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}sm_students WHERE institution_id = %d OR school_id = %d",
            $inst_id, $inst_id
        ));

        SM_Logger::log('Delete No ', "   Delete ($deleted) No  : {$inst->name} (ID: {$inst_id})");
        wp_cache_flush();

        wp_send_json_success(array('message' => " Delete $deleted No   ({$inst->name})   ."));
    }

    public function ajax_eess_admin_delete_all_students_global() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin = in_array('administrator', $user_roles, true) || in_array('sm_system_admin', $user_roles, true) || current_user_can('manage_options');
        if (!$is_sys_admin) wp_send_json_error('    No   .');

        check_ajax_referer('sm_admin_action', 'nonce');

        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_students");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_exit_card_requests");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_students");

        SM_Logger::log('Delete  No ', "   Delete  No No   ($count No)   ");
        wp_cache_flush();

        wp_send_json_success(array('message' => " Delete  No No ($count No)     ."));
    }

    public function ajax_eess_admin_reset_all_student_sequences_global() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin = in_array('administrator', $user_roles, true) || in_array('sm_system_admin', $user_roles, true) || current_user_can('manage_options');
        if (!$is_sys_admin) wp_send_json_error('    No   .');

        check_ajax_referer('sm_admin_action', 'nonce');

        global $wpdb;
        $wpdb->query("UPDATE {$wpdb->prefix}eess_id_counters SET current_val = 0 WHERE counter_type LIKE 'student_%'");

        SM_Logger::log('Reset   ', "   Reset         00001");
        wp_cache_flush();

        wp_send_json_success(array('message' => ' Reset    No    00001 .'));
    }

    public function ajax_export_students_pdf() {
        if (!current_user_can('_No') && !current_user_can('manage_options')) {
            wp_die(' No  No Export.');
        }

        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_GET['nonce'] ?? '', 'eess_admin_action')) {
            wp_die('  Mother.');
        }

        global $wpdb;

        $school_id     = intval($_GET['school_id'] ?? 0);
        $class_filter  = sanitize_text_field($_GET['class_filter'] ?? '');
        $sec_filter    = sanitize_text_field($_GET['section_filter'] ?? '');

        $where = array();
        if ($school_id > 0) {
            $where[] = $wpdb->prepare("(school_id = %d OR institution_id = %d)", $school_id, $school_id);
        }
        if (!empty($class_filter)) {
            $where[] = $wpdb->prepare("class_name = %s", $class_filter);
        }
        if (!empty($sec_filter)) {
            $where[] = $wpdb->prepare("section = %s", $sec_filter);
        }

        $sql = "SELECT * FROM {$wpdb->prefix}sm_students";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY name ASC";

        $records = $wpdb->get_results($sql);

        // Parse Grade numbers for sequential ordering (1 -> 12)
        $get_grade_num = function($cname) {
            if (preg_match('/(\d+)/', $cname, $m)) {
                return intval($m[1]);
            }
            return 99;
        };

        // Group students by Grade (1 -> 12) and Section (A, B, C...)
        usort($records, function($a, $b) use ($get_grade_num) {
            $gA = $get_grade_num($a->class_name);
            $gB = $get_grade_num($b->class_name);
            if ($gA !== $gB) return $gA <=> $gB;

            $secA = trim($a->section);
            $secB = trim($b->section);
            if ($secA !== $secB) return strcmp($secA, $secB);

            return strcmp($a->name, $b->name);
        });

        $school_info = SM_Settings::get_school_info();
        $sys_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
        $org_title = '   ';

        $inst_name = '   ';
        if ($school_id > 0) {
            $inst_obj = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_institutions WHERE id = %d", $school_id));
            if ($inst_obj) $inst_name = $inst_obj->name;
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>    No </title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800;900&display=swap');
                body {
                    font-family: 'Cairo', sans-serif;
                    direction: rtl;
                    margin: 0;
                    padding: 20px;
                    background: #ffffff;
                    color: #0f172a;
                    font-size: 12px;
                }
                .pdf-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 2px solid #0f172a;
                    padding-bottom: 12px;
                    margin-bottom: 18px;
                }
                .pdf-header-title {
                    font-size: 18px;
                    font-weight: 900;
                    color: #0f172a;
                    margin: 0 0 4px 0;
                }
                .pdf-header-sub {
                    font-size: 12px;
                    color: #881337;
                    font-weight: 800;
                }
                .pdf-meta-box {
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    border-radius: 10px;
                    padding: 10px 14px;
                    margin-bottom: 16px;
                    display: flex;
                    justify-content: space-between;
                    font-size: 11.5px;
                    font-weight: 700;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th {
                    background-color: #0f172a;
                    color: #ffffff;
                    font-weight: 800;
                    padding: 8px 10px;
                    border: 1px solid #0f172a;
                    font-size: 11.5px;
                    text-align: center;
                }
                td {
                    padding: 7px 10px;
                    border: 1px solid #cbd5e1;
                    font-size: 11.5px;
                    text-align: center;
                    font-weight: 700;
                }
                tr:nth-child(even) {
                    background-color: #f8fafc;
                }
                .code-badge {
                    font-family: monospace;
                    font-weight: 900;
                    color: #881337;
                    font-size: 12.5px;
                }
                @media print {
                    @page {
                        size: A4 portrait;
                        margin: 12mm;
                    }
                    body { padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                <button onclick="window.print()" style="padding: 10px 24px; background: #881337; color: white; border: none; border-radius: 8px; font-weight: 800; font-size: 13px; cursor: pointer;">️ Print  (PDF)</button>
            </div>

            <div class="pdf-header">
                <div>
                    <h1 class="pdf-header-title"><?php echo esc_html($org_title); ?></h1>
                    <div class="pdf-header-sub"><?php echo esc_html($inst_name); ?></div>
                    <div style="font-size: 13px; font-weight: 900; color: #0f172a; margin-top: 4px;">    No</div>
                </div>
                <div>
                    <img src="<?php echo esc_url($sys_logo); ?>" style="max-height: 70px; object-fit: contain;" alt="Logo">
                </div>
            </div>

            <div class="pdf-meta-box">
                <div><strong>Training Group :</strong> <?php echo esc_html($class_filter ?: ' Training Groups'); ?></div>
                <div><strong>Training Group:</strong> <?php echo esc_html($sec_filter ?: ' '); ?></div>
                <div><strong> No:</strong> <?php echo count($records); ?> No</div>
                <div><strong> Print:</strong> <?php echo current_time('Y-m-d'); ?></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th style="width: 130px;"> No</th>
                        <th>Full Name No</th>
                        <th style="width: 100px;">Training Group</th>
                        <th style="width: 70px;">Training Group</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $idx = 1;
                    foreach ($records as $r):
                    ?>
                    <tr>
                        <td><?php echo $idx++; ?></td>
                        <td class="code-badge"><?php echo esc_html($r->student_code); ?></td>
                        <td style="text-align: right; padding-right: 14px;"><?php echo esc_html($r->name); ?></td>
                        <td><?php echo esc_html($r->class_name); ?></td>
                        <td><?php echo esc_html($r->section); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 30px; display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                <div>    —  Player Affairs</div>
                <div>      </div>
            </div>

            <script>
                window.onload = function() {
                    // Auto open print dialog if requested
                    if (window.location.search.indexOf('auto_print=1') !== -1) {
                        window.print();
                    }
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function ajax_eess_admin_reset_student_sequence() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin = in_array('administrator', $user_roles, true) || in_array('sm_system_admin', $user_roles, true) || current_user_can('manage_options');
        if (!$is_sys_admin) wp_send_json_error('    No   .');

        check_ajax_referer('sm_admin_action', 'nonce');

        $inst_id = intval($_POST['inst_id'] ?? 0);
        if ($inst_id <= 0) wp_send_json_error('  Organization   .');

        $acad_prefix = class_exists('EESS_ID_Code_Service') ? EESS_ID_Code_Service::get_academic_year_code() : '2627';
        $counter_type = 'student_' . $acad_prefix;

        if (class_exists('EESS_ID_Code_Service')) {
            EESS_ID_Code_Service::get_next_sequence($inst_id, $counter_type, 0);
        }

        SM_Logger::log('Reset   No', "       ID: {$inst_id}  {$acad_prefix}  00001.");
        wp_send_json_success(array('message' => ' Reset   .   No   00001.'));
    }

    public function ajax_eess_admin_update_academic_year() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin = in_array('administrator', $user_roles, true) || in_array('sm_system_admin', $user_roles, true) || current_user_can('manage_options');
        if (!$is_sys_admin) wp_send_json_error('    No   .');

        check_ajax_referer('sm_admin_action', 'nonce');

        $acad_year = sanitize_text_field($_POST['academic_year'] ?? '');
        if (empty($acad_year)) wp_send_json_error('     .');

        $struct = SM_Settings::get_academic_structure();
        $struct['academic_year'] = $acad_year;
        update_option('sm_academic_structure', $struct);

        SM_Logger::log('Update   ', "       : {$acad_year}");
        wp_cache_flush();

        wp_send_json_success(array('message' => " Update    : {$acad_year}"));
    }

    public function ajax_eess_student_change_password() {
        if (!is_user_logged_in()) {
            wp_send_json_error('    Login No.');
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $roles = (array) $user->roles;

        if (!in_array('sm_student', $roles) && !in_array('sm_parent', $roles) && !in_array('administrator', $roles)) {
            wp_send_json_error('    No  Mother  .');
        }

        if (!wp_verify_nonce($_POST['eess_student_password_nonce'] ?? ($_POST['nonce'] ?? ''), 'eess_student_password_action')) {
            wp_send_json_error('    .');
        }

        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (empty($new_pass) || empty($confirm_pass)) {
            wp_send_json_error('  Password  Confirm.');
        }

        if ($new_pass !== $confirm_pass) {
            wp_send_json_error('   .');
        }

        // Validate password complexity: Min 8, Max 30, at least 1 uppercase, 1 lowercase, 1 number
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,30}$/', $new_pass)) {
            wp_send_json_error('Password     8-30      (A-Z)   (a-z)    (0-9).');
        }

        wp_set_password($new_pass, $user_id);
        delete_user_meta($user_id, 'eess_must_change_password');
        update_user_meta($user_id, 'sm_temp_pass', $new_pass);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        SM_Logger::log('   No', "  ({$user->display_name})     .");
        wp_send_json_success('  Password  .');
    }

    // Custom mail sender filters
    public function custom_wp_mail_from($original_email_address) {
        return 'info@eess.online';
    }

    public function custom_wp_mail_from_name($original_email_from) {
        return '  - SHOLA';
    }

    // Branded EESS Email Helper
    private function send_branded_email($to, $subject, $title, $body_content) {
        add_filter('wp_mail_from', array($this, 'custom_wp_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'custom_wp_mail_from_name'));

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From:   - SHOLA <info@eess.online>');

        $html = '
        <div dir="rtl" style="font-family: \'Cairo\', \'Noto Kufi Arabic\', Arial, sans-serif; background-color: #f8fafc; padding: 40px 20px; text-align: right; direction: rtl;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #cbd5e1; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <div style="background: #0d0d0d; padding: 25px; text-align: center; border-bottom: 4px solid #8b1e1e;">
                    <h2 style="color: #ffffff; margin: 0; font-weight: 800; font-size: 1.5rem; letter-spacing: 1px;">EESS</h2>
                    <div style="color: #cbd5e1; font-size: 11px; margin-top: 5px;">Educational Electronic Systems Services</div>
                </div>
                <div style="padding: 30px; box-sizing: border-box;">
                    <h3 style="color: #0f172a; margin-top: 0; font-weight: 800; font-size: 1.2rem;">' . esc_html($title) . '</h3>
                    <div style="color: #334155; font-size: 14px; line-height: 1.8; margin-bottom: 25px;">
                        ' . $body_content . '
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 25px; font-size: 12px; color: #64748b;">
                                        Email Address : <a href="mailto:info@eess.online" style="color: #8b1e1e; font-weight: bold; text-decoration: none;">info@eess.online</a>.
                    </div>
                </div>
                <div style="background: #f1f5f9; padding: 15px 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <div>All Rights Reserved © 2026 EESS.    </div>
                    <div style="margin-top: 5px;"><a href="https://eess.online" target="_blank" style="color: #94a3b8; text-decoration: underline;">eess.online</a></div>
                </div>
            </div>
        </div>
        ';
        return wp_mail($to, $subject, $html, $headers);
    }

    // Block Pending Approval & Restricted users from logging in
    public function block_pending_users_login($user, $password) {
        if ($user instanceof WP_User) {
            $status = get_user_meta($user->ID, 'eess_approval_status', true);
            $restricted = get_user_meta($user->ID, 'eess_access_restricted', true);
            $reason = get_user_meta($user->ID, 'eess_restriction_reason', true) ?: ' ';

            if ($status === 'pending') {
                return new WP_Error(
                    'pending_approval',
                    '   . Please wait...         Users.'
                );
            }
            if ($status === 'restricted' || $restricted === 'yes') {
                return new WP_Error(
                    'restricted_access',
                    '           : ' . esc_html($reason)
                );
            }
        }
        return $user;
    }

    // Multi-Step Identity Verification Without OTP
    public function ajax_forgot_verify_identity() {
        // Rate Limiting Protection (Max 5 attempts per 15 mins)
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rate_key   = 'eess_forgot_attempts_' . md5($ip_address);
        $attempts   = (int) get_transient($rate_key);
        if ($attempts >= 5) {
            wp_send_json_error('   No No  . Please wait...  15    .');
        }

        $email       = sanitize_email($_POST['email'] ?? '');
        $emp_id      = sanitize_text_field($_POST['emp_id'] ?? '');
        $institution = sanitize_text_field($_POST['institution'] ?? '');
        $nationality = sanitize_text_field($_POST['nationality'] ?? '');
        $role        = sanitize_text_field($_POST['role'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $dob         = sanitize_text_field($_POST['dob'] ?? '');

        if (empty($email) || empty($emp_id) || empty($role) || empty($dob)) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('       Confirm.');
        }

        // 1. Verify User by Email
        $user = get_user_by('email', $email);
        if (!$user) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('       .');
        }

        // 2. Verify Employee ID
        $clean_emp_id = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', trim($emp_id)));
        $stored_emp1  = get_user_meta($user->ID, 'sm_employee_id', true);
        $stored_emp2  = get_user_meta($user->ID, 'employee_id', true);
        if ($user->user_login !== $clean_emp_id && $stored_emp1 !== $clean_emp_id && $stored_emp2 !== $clean_emp_id && $stored_emp1 !== $emp_id) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('    .');
        }

        // 3. Verify Role
        $user_roles = (array) $user->roles;
        if (!in_array($role, $user_roles) && !($role === 'sm_teacher' && in_array('sm_teacher', $user_roles))) {
            set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error('      .');
        }

        // 4. Verify Institution
        if (!empty($institution)) {
            $stored_inst1 = get_user_meta($user->ID, 'institution', true);
            $stored_inst2 = get_user_meta($user->ID, 'sm_institution', true);
            $stored_inst3 = get_user_meta($user->ID, 'eess_school_name', true);
            $school_info  = SM_Settings::get_school_info();
            $default_inst = $school_info['school_name'] ?? 'Sportedia Sports Management System';

            $inst_match = (strcasecmp($stored_inst1, $institution) === 0) ||
                         (strcasecmp($stored_inst2, $institution) === 0) ||
                         (strcasecmp($stored_inst3, $institution) === 0) ||
                         (empty($stored_inst1) && strcasecmp($default_inst, $institution) === 0);

            if (!$inst_match) {
                set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                wp_send_json_error(' Organization   Academy   .');
            }
        }

        // 5. Verify Nationality
        if (!empty($nationality)) {
            $stored_nat1 = get_user_meta($user->ID, 'nationality', true);
            $stored_nat2 = get_user_meta($user->ID, 'sm_nationality', true);
            if (!empty($stored_nat1) || !empty($stored_nat2)) {
                if (strcasecmp($stored_nat1, $nationality) !== 0 && strcasecmp($stored_nat2, $nationality) !== 0) {
                    set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                    wp_send_json_error(' Gender  .');
                }
            }
        }

        // 6. Verify Subject if applicable
        $roles_requiring_subject = array('sm_teacher', 'sm_coordinator', 'sm_hod');
        if (in_array($role, $roles_requiring_subject) && !empty($subject)) {
            $stored_sub1 = get_user_meta($user->ID, 'sm_specialization', true);
            $stored_sub2 = get_user_meta($user->ID, 'specialization', true);
            if (!empty($stored_sub1) || !empty($stored_sub2)) {
                if (strcasecmp($stored_sub1, $subject) !== 0 && strcasecmp($stored_sub2, $subject) !== 0) {
                    set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                    wp_send_json_error('Sport Activity       .');
                }
            }
        }

        // 7. Verify Date of Birth
        if (!empty($dob)) {
            $stored_dob1 = get_user_meta($user->ID, 'sm_dob', true);
            $stored_dob2 = get_user_meta($user->ID, 'dob', true);
            if (!empty($stored_dob1) || !empty($stored_dob2)) {
                if ($stored_dob1 !== $dob && $stored_dob2 !== $dob) {
                    set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                    wp_send_json_error('Date of Birth  .');
                }
            }
        }

        // Generate verified token for resetting password
        $reset_token = wp_generate_password(32, false);
        set_transient('eess_verified_reset_user_' . $reset_token, $user->ID, 15 * MINUTE_IN_SECONDS);

        delete_transient($rate_key);

        wp_send_json_success(array(
            'reset_token'  => $reset_token,
            'display_name' => $user->display_name
        ));
    }

    // Set New Password & Automatic Authentication
    public function ajax_forgot_set_password() {
        $reset_token = sanitize_text_field($_POST['reset_token'] ?? '');
        $password    = $_POST['password'] ?? '';
        $pass_conf   = $_POST['password_conf'] ?? '';

        if (empty($reset_token) || empty($password) || empty($pass_conf)) {
            wp_send_json_error('  .');
        }

        $user_id = get_transient('eess_verified_reset_user_' . $reset_token);
        if (!$user_id) {
            wp_send_json_error(' No   .      .');
        }

        if ($password !== $pass_conf) {
            wp_send_json_error('   .');
        }

        // Password Validation Rules: 8-40 chars, 1 uppercase, 1 lowercase, 1 number
        $length = mb_strlen($password);
        if ($length < 8 || $length > 40) {
            wp_send_json_error('Password     8  40 .');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            wp_send_json_error('Password        (A-Z)   .');
        }

        if (!preg_match('/[a-z]/', $password)) {
            wp_send_json_error('Password        (a-z)   .');
        }

        if (!preg_match('/[0-9]/', $password)) {
            wp_send_json_error('Password      (0-9)   .');
        }

        // Save New Password
        wp_set_password($password, $user_id);
        delete_transient('eess_verified_reset_user_' . $reset_token);

        // Automatic Authenticate & Login User
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        SM_Logger::log('  Password', "  Password     ID: $user_id");

        wp_send_json_success(array(
            'message'      => ' Save Password    !',
            'redirect_url' => home_url('/sm-admin')
        ));
    }

    // Registration Wizard Submit Without OTP
    public function ajax_register_submit() {
        $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
        $dob         = sanitize_text_field($_POST['dob'] ?? '');
        $nationality = sanitize_text_field($_POST['nationality'] ?? '');
        $email       = sanitize_email($_POST['email'] ?? '');
        $phone       = sanitize_text_field($_POST['phone'] ?? '');
        $emp_num     = sanitize_text_field($_POST['emp_num'] ?? '');
        $institution = sanitize_text_field($_POST['institution'] ?? '');
        $school      = sanitize_text_field($_POST['school'] ?? '');
        $role        = sanitize_text_field($_POST['role'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $password    = $_POST['password'] ?? '';
        $pass_conf   = $_POST['password_conf'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($dob) || empty($nationality) || empty($email) || empty($emp_num) || empty($institution) || empty($role) || empty($password)) {
            wp_send_json_error('      .');
        }

        // Validate role requiring subject
        $roles_requiring_subject = array('sm_teacher', 'sm_coordinator', 'sm_hod');
        if (in_array($role, $roles_requiring_subject) && empty($subject)) {
            wp_send_json_error('  Sport Activity   .');
        }

        if ($password !== $pass_conf) {
            wp_send_json_error('   .');
        }

        // Password Validation Rules: 8-40 chars, 1 uppercase, 1 lowercase, 1 number
        $length = mb_strlen($password);
        if ($length < 8 || $length > 40) {
            wp_send_json_error('Password     8  40 .');
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            wp_send_json_error('Password           .');
        }

        if (email_exists($email)) {
            wp_send_json_error('Email Address    .');
        }

        // Clean Employee Number to enforce Username = Employee Number
        $clean_emp_num = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', trim($emp_num)));
        if (empty($clean_emp_num)) {
            $clean_emp_num = trim($emp_num);
        }
        $username = $clean_emp_num;

        if (username_exists($username)) {
            wp_send_json_error('  (Username)    .');
        }

        $display_name = trim($first_name . ' ' . $last_name);
        if (empty($display_name)) {
            $display_name = $username;
        }

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => $role,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'display_name'=> $display_name
        ));

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Set pending status and metadata
        update_user_meta($user_id, 'eess_approval_status', 'pending');
        update_user_meta($user_id, 'eess_employee_number', $clean_emp_num);
        update_user_meta($user_id, 'sm_employee_id', $clean_emp_num);
        update_user_meta($user_id, 'employee_id', $clean_emp_num);
        update_user_meta($user_id, 'eess_school_name', $school);
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'dob', $dob);
        update_user_meta($user_id, 'sm_dob', $dob);
        update_user_meta($user_id, 'nationality', $nationality);
        update_user_meta($user_id, 'sm_nationality', $nationality);
        update_user_meta($user_id, 'institution', $institution);
        update_user_meta($user_id, 'sm_institution', $institution);
        update_user_meta($user_id, 'phone_number', $phone);
        update_user_meta($user_id, 'sm_phone', $phone);
        if (!empty($subject)) {
            update_user_meta($user_id, 'specialization', $subject);
            update_user_meta($user_id, 'sm_specialization', $subject);
        }

        delete_transient('eess_register_otp_' . md5($email));

        // Notify System User Management
        $admin_email = get_option('admin_email') ?: 'info@eess.online';
        $admin_title = '     No - EESS';
        $admin_body = '
        <p>Welcome   Users</p>
        <p> No        No.</p>
        <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border:1px solid #e2e8f0; line-height: 1.8;">
            <strong>Full Name:</strong> ' . esc_html($display_name) . '<br>
            <strong>Email Address:</strong> ' . esc_html($email) . '<br>
            <strong> :</strong> ' . esc_html($emp_num) . '<br>
            <strong> /  :</strong> ' . esc_html($role) . '<br>
            <strong>Academy   :</strong> ' . esc_html($school) . '<br>
        </div>
        <p>   Approve   Reject   No   Users Dashboard.</p>
        ';
        $this->send_branded_email($admin_email, $admin_title, '    No', $admin_body);

        wp_send_json_success('   .         No  No.');
    }

    // Admin Action: Approve user
    public function ajax_approve_user() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized    .');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$target_user_id) {
            wp_send_json_error('   .');
        }

        update_user_meta($target_user_id, 'eess_approval_status', 'approved');
        update_user_meta($target_user_id, 'sm_account_status', 'active');

        $user = get_user_by('id', $target_user_id);
        $title = '    - EESS Account Activation';

        $body = '
        <table dir="rtl" style="text-align: right; width: 100%; font-family: \'Cairo\', sans-serif; border-collapse: collapse; margin-bottom: 25px;">
            <tr>
                <td>
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0 0 10px 0; font-weight: bold;">Welcome  ' . esc_html($user->display_name ?: $user->user_email) . '</h3>
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin: 0 0 15px 0;"> No         <strong>Sportedia Sports Management System</strong>.   Active   No .</p>

                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                        <strong>  / Account Details:</strong><br>
                        • Username: <span style="font-family: monospace; font-weight: bold;">' . esc_html($user->user_login) . '</span><br>
                        • Email Address: <span style="font-family: monospace; font-weight: bold;">' . esc_html($user->user_email) . '</span><br>
                    </div>

                    <p style="font-size: 13px; color: #334155; line-height: 1.8;"><strong>  :</strong>                       .</p>
                </td>
            </tr>
        </table>

        <hr style="border: none; border-top: 1px solid #cbd5e1; margin: 25px 0;">

        <table dir="ltr" style="text-align: left; width: 100%; font-family: \'Cairo\', sans-serif; border-collapse: collapse;">
            <tr>
                <td>
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0 0 10px 0; font-weight: bold;">Welcome, ' . esc_html($user->display_name ?: $user->user_email) . ',</h3>
                    <p style="font-size: 13px; color: #334155; line-height: 1.8; margin: 0 0 15px 0;">We are pleased to inform you that your account has been reviewed and successfully approved on the <strong>Educational Electronic Systems Services (EESS)</strong> platform. Your account is now fully active and ready for use.</p>

                    <p style="font-size: 13px; color: #334155; line-height: 1.8;"><strong>Important Security Recommendations:</strong> Please ensure never to share your login credentials or OTP with anyone else, and use a strong password to ensure the security of your private data.</p>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin: 30px 0;">
            <a href="' . home_url('/sm-login') . '" style="display:inline-block; background:#000000; color:#ffffff !important; text-decoration:none; padding:12px 35px; font-weight:bold; border-radius:6px; font-size:14px; font-family:\'Cairo\', sans-serif;">Login  / Login to Platform</a>
        </div>
        ';

        $this->send_branded_email($user->user_email, $title, '  ', $body);

        wp_send_json_success('      .');
    }

    // Admin Action: Reject user
    public function ajax_reject_user() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized    .');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$target_user_id) {
            wp_send_json_error('   .');
        }

        $user = get_user_by('id', $target_user_id);
        if ($user) {
            $title = '    - EESS';
            $body = '
            <p>Welcome </p>
            <p> No   Reject       EESS    .</p>
            <p>                    .</p>
            ';
            $this->send_branded_email($user->user_email, $title, '  ', $body);

            // Delete user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($target_user_id);
        }

        wp_send_json_success(' Reject   Delete  Pending .');
    }

    // Admin Action: Save user notes
    public function ajax_save_user_notes() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'sm_teacher_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized    .');
        }

        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (!$target_user_id) {
            wp_send_json_error('   .');
        }

        update_user_meta($target_user_id, 'eess_admin_notes', $notes);
        wp_send_json_success(' Save Notes  .');
    }

    public function ajax_get_user_assignments() {
        if (!is_user_logged_in() || !current_user_can('_')) {
            wp_send_json_error('Unauthorized');
        }
        $user_id = intval($_GET['user_id']);
        $scope = EESS_Org_Helper::get_user_scope($user_id);
        wp_send_json_success($scope);
    }

    public function ajax_sm_save_asset_inventory() {
        if (!is_user_logged_in()) wp_send_json_error(' Login.');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $user_id    = get_current_user_id();
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        $qty_total  = max(1, intval($_POST['qty_total'] ?? 1));
        $qty_usable = max(0, intval($_POST['qty_usable'] ?? $qty_total));
        $qty_damaged = max(0, intval($_POST['qty_damaged'] ?? 0));
        $qty_missing = max(0, intval($_POST['qty_missing'] ?? 0));
        $location   = sanitize_text_field($_POST['location'] ?? '  ');

        $school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'Academy  Home';
        $department  = get_user_meta($user_id, 'department', true) ?: '  ';

        global $wpdb;

        // Check if institutional inventory header exists or create
        $inv_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_asset_inventories WHERE school_name = %s LIMIT 1", $school_name));
        if (!$inv_id) {
            $wpdb->insert("{$wpdb->prefix}sm_asset_inventories", array(
                'institution_id' => 1,
                'school_name'    => $school_name,
                'department'     => $department,
                'academic_year'  => '2027/2026',
                'responsible_user_id' => $user_id,
                'status'         => 'approved',
                'created_at'     => current_time('mysql'),
                'updated_at'     => current_time('mysql')
            ));
            $inv_id = $wpdb->insert_id;
        }

        // Fetch catalog item details
        $item_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_asset_catalog WHERE id = %d", $catalog_id));
        $item_name = $item_info ? $item_info->item_name : '  ';
        $category  = $item_info ? $item_info->category : ' ';

        $inserted = $wpdb->insert("{$wpdb->prefix}sm_asset_inventory_items", array(
            'inventory_id'   => $inv_id,
            'catalog_id'     => $catalog_id,
            'item_name'      => $item_name,
            'category'       => $category,
            'qty_total'      => $qty_total,
            'qty_usable'     => $qty_usable,
            'qty_consumed'   => 0,
            'qty_damaged'    => $qty_damaged,
            'qty_missing'    => $qty_missing,
            'qty_replacement' => $qty_damaged + $qty_missing,
            'location'       => $location,
            'condition_status' => 'good',
            'created_at'     => current_time('mysql')
        ));

        if ($inserted) {
            SM_Logger::log('Add  ', "    ($item_name)  $school_name   ID: $user_id");
            wp_send_json_success(array('message' => ' Save Update   .'));
        } else {
            wp_send_json_error(' Save     .');
        }
    }

    public function ajax_sm_save_asset_request() {
        if (!is_user_logged_in()) wp_send_json_error(' Login.');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $user_id       = get_current_user_id();
        $catalog_id    = intval($_POST['catalog_id'] ?? 0);
        $qty_requested = max(1, intval($_POST['qty_requested'] ?? 1));
        $reason        = sanitize_text_field($_POST['request_reason'] ?? '  ');

        $school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'Academy  Home';
        $department  = get_user_meta($user_id, 'department', true) ?: '  ';

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_asset_requests", array(
            'institution_id'   => 1,
            'school_name'      => $school_name,
            'department'       => $department,
            'requester_user_id'=> $user_id,
            'request_reason'   => $reason,
            'priority'         => 'normal',
            'status'           => 'submitted',
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql')
        ));

        if ($inserted) {
            $req_id = $wpdb->insert_id;
            $request_items = $_POST['items'] ?? array();

            if (empty($request_items) && $catalog_id > 0) {
                $request_items = array(array('catalog_id' => $catalog_id, 'qty_requested' => $qty_requested));
            }

            $added_names = array();

            foreach ($request_items as $item_entry) {
                $cat_id = intval($item_entry['catalog_id'] ?? 0);
                $qty_req = max(1, intval($item_entry['qty_requested'] ?? 1));
                if ($cat_id <= 0) continue;

                $item_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_asset_catalog WHERE id = %d", $cat_id));
                $item_name = $item_info ? $item_info->item_name : ' ';
                $added_names[] = "{$item_name} ({$qty_req})";

                $wpdb->insert("{$wpdb->prefix}sm_asset_request_items", array(
                    'request_id'    => $req_id,
                    'catalog_id'    => $cat_id,
                    'item_name'     => $item_name,
                    'qty_usable'    => 0,
                    'qty_damaged'   => 0,
                    'qty_missing'   => 0,
                    'qty_requested' => $qty_req,
                    'created_at'    => current_time('mysql')
                ));
            }

            $items_summary = implode(', ', $added_names);
            SM_Logger::log('  ', "      [$items_summary]  $school_name   ID: $user_id");
            wp_send_json_success(array('message' => ' Send     No.'));
        } else {
            wp_send_json_error(' Save  .');
        }
    }

    public function ajax_sm_mark_teacher_contacted() {
        if (!is_user_logged_in()) wp_send_json_error(' Login.');
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_term_plan_action') && !wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        $record_type = sanitize_text_field($_POST['record_type'] ?? 'general');
        $record_id   = intval($_POST['record_id'] ?? 0);

        if (!$teacher_id) wp_send_json_error('   .');

        $key = 'eess_contacted_' . $record_type . '_' . $record_id;
        if ($record_id <= 0) {
            $key = 'eess_contacted_teacher_' . $teacher_id;
        }

        update_user_meta($teacher_id, $key, current_time('mysql'));

        SM_Logger::log('  ', "       ID: $teacher_id : $record_type ($record_id)");

        wp_send_json_success(array('message' => '     .'));
    }

    public function ajax_sm_assign_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error(' Login.');
        $user = wp_get_current_user();
        $roles = (array)$user->roles;
        if (!in_array('administrator', $roles) && !in_array('sm_system_admin', $roles) && !current_user_can('manage_options')) {
            wp_send_json_error('        .');
        }
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_term_plan_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $target_uid = intval($_POST['target_user_id'] ?? 0);
        $term_num   = intval($_POST['term_number'] ?? 1);
        $subject    = sanitize_text_field($_POST['subject'] ?? '');
        $grade      = sanitize_text_field($_POST['grade'] ?? ' ');

        if (!$target_uid) wp_send_json_error('   .');

        $file_url = '';
        if (!empty($_FILES['plan_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $uploaded = wp_handle_upload($_FILES['plan_file'], array('test_form' => false));
            if (isset($uploaded['url'])) {
                $file_url = $uploaded['url'];
            }
        }

        global $wpdb;
        $acad_struct = SM_Settings::get_academic_structure();
        $academic_year = $acad_struct['academic_year'] ?? '2027/2026';

        $wpdb->insert("{$wpdb->prefix}sm_term_plans", array(
            'teacher_id'      => $target_uid,
            'academic_year'   => $academic_year,
            'term_number'     => $term_num,
            'subject'         => $subject,
            'grade'           => $grade,
            'planning_method' => 'upload',
            'plan_file_url'   => $file_url,
            'weeks_data'      => '',
            'status'          => 'submitted',
            'completion_pct'  => 100,
            'num_terms'       => $acad_struct['terms_count'] ?? 3,
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql')
        ));

        $target_user = get_userdata($target_uid);
        SM_Logger::log('  ', "      : " . ($target_user ? $target_user->display_name : $target_uid));
        wp_send_json_success(array('message' => '   Training Group  .'));
    }

    public function ajax_sm_assign_lesson_prep() {
        if (!is_user_logged_in()) wp_send_json_error(' Login.');
        $user = wp_get_current_user();
        $roles = (array)$user->roles;
        if (!in_array('administrator', $roles) && !in_array('sm_system_admin', $roles) && !current_user_can('manage_options')) {
            wp_send_json_error('        .');
        }
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'nonce')) {
            wp_send_json_error('Security check failed');
        }

        $target_uid  = intval($_POST['target_user_id'] ?? 0);
        $title       = sanitize_text_field($_POST['title'] ?? '');
        $subject     = sanitize_text_field($_POST['subject'] ?? '');
        $grade_level = sanitize_text_field($_POST['grade_level'] ?? '');
        $lesson_date = sanitize_text_field($_POST['lesson_date'] ?? current_time('Y-m-d'));

        if (!$target_uid || empty($title)) wp_send_json_error('     .');

        $file_url = '';
        if (!empty($_FILES['prep_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $uploaded = wp_handle_upload($_FILES['prep_file'], array('test_form' => false));
            if (isset($uploaded['url'])) {
                $file_url = $uploaded['url'];
            }
        }

        $lesson_data = json_encode(array(
            'objectives' => '  ',
            'file_url'   => $file_url
        ), JSON_UNESCAPED_UNICODE);

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}sm_lesson_preps", array(
            'teacher_id'      => $target_uid,
            'supervisor_id'   => $user->ID,
            'title'           => $title,
            'subject'         => $subject,
            'grade_level'     => $grade_level,
            'class_section'   => ' 1',
            'lesson_date'     => $lesson_date,
            'submission_time' => current_time('mysql'),
            'status'          => 'submitted',
            'delay_seconds'   => 0,
            'lesson_data'     => $lesson_data,
            'version'         => 1,
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql')
        ));

        $target_user = get_userdata($target_uid);
        SM_Logger::log('  ', "      : " . ($target_user ? $target_user->display_name : $target_uid));
        wp_send_json_success(array('message' => '     .'));
    }

    public function ajax_sm_copy_record() {
        if (!is_user_logged_in()) {
            wp_send_json_error('  Login   .');
        }

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $is_sys_admin = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || current_user_can('manage_options');

        if (!$is_sys_admin) {
            wp_send_json_error('         (System Administrator).');
        }

        check_ajax_referer('eess_admin_action', 'nonce');

        $record_type = isset($_POST['record_type']) ? sanitize_key($_POST['record_type']) : '';
        $record_id   = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
        $target_uid  = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : 0;

        if (!$record_id || !$target_uid || !in_array($record_type, array('lesson_prep', 'term_plan'))) {
            wp_send_json_error('        .');
        }

        $target_user = get_userdata($target_uid);
        if (!$target_user) {
            wp_send_json_error('    .');
        }

        global $wpdb;

        if ($record_type === 'lesson_prep') {
            $orig = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $record_id));
            if (!$orig) {
                wp_send_json_error('    .');
            }

            $wpdb->insert("{$wpdb->prefix}sm_lesson_preps", array(
                'teacher_id'      => $target_uid,
                'supervisor_id'   => $orig->supervisor_id,
                'title'           => $orig->title . ' ()',
                'subject'         => $orig->subject,
                'grade_level'     => $orig->grade_level,
                'class_section'   => $orig->class_section,
                'lesson_date'     => $orig->lesson_date,
                'submission_time' => current_time('mysql'),
                'status'          => 'submitted',
                'delay_seconds'   => 0,
                'lesson_data'     => $orig->lesson_data,
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            ));

            $new_id = $wpdb->insert_id;
            SM_Logger::log('  ', "      ID: $record_id : {$target_user->display_name}   ID: $new_id");
            wp_send_json_success(array('message' => "      : {$target_user->display_name}"));
        } elseif ($record_type === 'term_plan') {
            $orig = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $record_id));
            if (!$orig) {
                wp_send_json_error(' Training Group   .');
            }

            $inserted = $wpdb->insert("{$wpdb->prefix}sm_term_plans", array(
                'teacher_id'       => $target_uid,
                'academic_year'    => $orig->academic_year ?: '2027/2026',
                'subject'          => $orig->subject,
                'grade'            => $orig->grade,
                'weekly_lessons'   => intval($orig->weekly_lessons ?: 1),
                'num_terms'        => intval($orig->num_terms ?: 3),
                'term_number'      => intval($orig->term_number ?: 1),
                'start_date'       => $orig->start_date ?: current_time('Y-m-d'),
                'end_date'         => $orig->end_date ?: current_time('Y-m-d'),
                'total_weeks'      => intval($orig->total_weeks ?: 0),
                'weeks_data'       => $orig->weeks_data,
                'plan_file_url'    => $orig->plan_file_url,
                'planning_method'  => $orig->planning_method ?: 'create',
                'completion_pct'   => intval($orig->completion_pct ?: 100),
                'status'           => 'submitted',
                'review_notes'     => '',
                'reviewed_by'      => 0,
                'reviewed_at'      => null,
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql')
            ));

            if (!$inserted) {
                wp_send_json_error(' Save    : ' . $wpdb->last_error);
            }

            $new_id = $wpdb->insert_id;
            SM_Logger::log('  ', "     Training Group ID: $record_id : {$target_user->display_name}   ID: $new_id");
            wp_send_json_success(array('message' => "   Training Group   : {$target_user->display_name}"));
        }
    }

    public function eess_generate_barcode_svg($data) {
        return $this->eess_generate_qr_code_svg($data);
    }

    private function eess_generate_qr_code_svg($data) {
        $text = trim((string)$data);
        if ($text === '') $text = 'STU000';

        // Official 107 Code 128 pattern table (0-106)
        $patterns = array(
            0 => "212222", 1 => "222122", 2 => "222221", 3 => "121223", 4 => "121322",
            5 => "131222", 6 => "122213", 7 => "122312", 8 => "132212", 9 => "221213",
            10 => "221312", 11 => "231212", 12 => "112232", 13 => "122132", 14 => "122231",
            15 => "113222", 16 => "123122", 17 => "123221", 18 => "223211", 19 => "221132",
            20 => "221231", 21 => "213212", 22 => "223112", 23 => "312131", 24 => "311222",
            25 => "321122", 26 => "321221", 27 => "312212", 28 => "322112", 29 => "322211",
            30 => "212123", 31 => "212321", 32 => "232121", 33 => "111323", 34 => "131123",
            35 => "131321", 36 => "112313", 37 => "132113", 38 => "132311", 39 => "211313",
            40 => "231113", 41 => "231311", 42 => "112133", 43 => "112331", 44 => "132131",
            45 => "113123", 46 => "113321", 47 => "133121", 48 => "313121", 49 => "211331",
            50 => "231131", 51 => "213113", 52 => "213311", 53 => "213131", 54 => "311123",
            55 => "311321", 56 => "331121", 57 => "312113", 58 => "312311", 59 => "332111",
            60 => "314111", 61 => "221411", 62 => "411131", 63 => "111224", 64 => "111422",
            65 => "121124", 66 => "121421", 67 => "141122", 68 => "141221", 69 => "112214",
            70 => "112412", 71 => "122114", 72 => "122411", 73 => "142112", 74 => "142211",
            75 => "241211", 76 => "221114", 77 => "411112", 78 => "421111", 79 => "214112",
            80 => "211214", 81 => "211412", 82 => "231112", 83 => "211132", 84 => "211231",
            85 => "211321", 86 => "221131", 87 => "221211", 88 => "231121", 89 => "211114",
            90 => "211411", 91 => "211211", 92 => "211124", 93 => "211142", 94 => "211241",
            95 => "211421", 96 => "233111", 97 => "211133", 98 => "241112", 99 => "134111",
            100 => "111242", 101 => "121142", 102 => "121241", 103 => "211412", 104 => "211214",
            105 => "211232", 106 => "2331112"
        );

        $symbol_sequence = array(104);
        $checksum = 104;

        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            $ascii = ord($char);
            $val = $ascii - 32;
            if ($val < 0 || $val > 95) $val = 0;
            $symbol_sequence[] = $val;
            $checksum += $val * ($i + 1);
        }

        $check_symbol = $checksum % 103;
        $symbol_sequence[] = $check_symbol;
        $symbol_sequence[] = 106;

        $modules = array();
        foreach ($symbol_sequence as $sym_idx) {
            $pat = $patterns[$sym_idx];
            $is_bar = true;
            for ($p = 0; $p < strlen($pat); $p++) {
                $w = intval($pat[$p]);
                for ($b = 0; $b < $w; $b++) {
                    $modules[] = $is_bar ? 1 : 0;
                }
                $is_bar = !$is_bar;
            }
        }

        $quiet = 10;
        $bar_width = 2;
        $height = 65;
        $total_modules = count($modules) + ($quiet * 2);
        $width = $total_modules * $bar_width;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" width="100%" height="100%" shape-rendering="crispEdges">';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="#ffffff"/>';

        $x = $quiet * $bar_width;
        foreach ($modules as $m) {
            if ($m === 1) {
                $svg .= '<rect x="' . $x . '" y="0" width="' . $bar_width . '" height="' . $height . '" fill="#0f172a"/>';
            }
            $x += $bar_width;
        }

        $svg .= '</svg>';
        return $svg;
    }

    public function ajax_sm_print() {
        if (!is_user_logged_in()) {
            wp_die('  Login   Print  .');
        }

        $print_type = isset($_GET['print_type']) ? sanitize_key($_GET['print_type']) : '';
        if (empty($print_type)) {
            wp_die(' Print  .');
        }

        if ($print_type === 'lesson_prep') {
            $prep_id = isset($_GET['prep_id']) ? intval($_GET['prep_id']) : 0;
            global $wpdb;
            $prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            if (!$prep) {
                wp_die('  .');
            }

            // Lock down print access so only the owner, coordinators, supervisors, or administrators can view it
            $current_user_id = get_current_user_id();
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);

            if ($prep->teacher_id != $current_user_id && !$is_privileged) {
                wp_die(' No  Permissions  No  Print  .');
            }

            include SM_PLUGIN_DIR . 'templates/lesson-document-template.php';
            exit;
        } elseif ($print_type === 'term_plan' || $print_type === 'annual_plan') {
            include SM_PLUGIN_DIR . 'templates/term-plan-document-template.php';
            exit;
        } elseif ($print_type === 'non_submission_lesson_prep') {
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);
            if (!$is_privileged) {
                wp_die(' No  No           .');
            }

            global $wpdb;
            $current_time_ts = current_time('timestamp');
            $current_date_fmt = current_time('Y-m-d');

            // Academic Start Anchor: 30 August 2026
            $acad_anchor_ts = strtotime('2026-08-30 00:00:00');
            if ($current_time_ts >= $acad_anchor_ts) {
                $diff_sec = $current_time_ts - $acad_anchor_ts;
                $current_acad_week = intval(floor($diff_sec / (7 * 86400))) + 1;
            } else {
                $current_acad_week = 1;
            }
            $current_acad_week = max(1, min(16, $current_acad_week));

            // User organizational scope
            $scope = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_user_scope() : array('unrestricted' => true);

            $all_teachers_raw = get_users(array(
                'role__in' => array('sm_teacher', 'sm_coordinator', 'sm_hod'),
                'orderby'  => 'display_name',
                'order'    => 'ASC'
            ));

            $teachers = array();
            foreach ($all_teachers_raw as $t) {
                if (isset($scope['unrestricted']) && !$scope['unrestricted']) {
                    $t_inst = get_user_meta($t->ID, 'eess_institution_id', true) ?: get_user_meta($t->ID, 'institution_id', true);
                    if (!empty($scope['institutions']) && !in_array(intval($t_inst), array_map('intval', $scope['institutions']), true)) {
                        continue;
                    }
                }
                $teachers[] = $t;
            }

            // Distinct EESS Pastel Palette for Week Capsules
            $pastel_palette = array(
                1  => array('bg' => '#fef2f2', 'color' => '#881337', 'border' => '#fecdd3'),
                2  => array('bg' => '#fffbe3', 'color' => '#b45309', 'border' => '#fde68a'),
                3  => array('bg' => '#f0fdf4', 'color' => '#166534', 'border' => '#bbf7d0'),
                4  => array('bg' => '#e0f2fe', 'color' => '#0369a1', 'border' => '#bae6fd'),
                5  => array('bg' => '#f3e8ff', 'color' => '#6b21a8', 'border' => '#e9d5ff'),
                6  => array('bg' => '#fce7f3', 'color' => '#9d174d', 'border' => '#fbcfe8'),
                7  => array('bg' => '#ffedd5', 'color' => '#c2410c', 'border' => '#fed7aa'),
                8  => array('bg' => '#ecfdf5', 'color' => '#047857', 'border' => '#a7f3d0'),
                9  => array('bg' => '#f1f5f9', 'color' => '#334155', 'border' => '#cbd5e1'),
                10 => array('bg' => '#fee2e2', 'color' => '#991b1b', 'border' => '#fca5a5'),
                11 => array('bg' => '#e0e7ff', 'color' => '#3730a3', 'border' => '#c7d2fe'),
                12 => array('bg' => '#fae8ff', 'color' => '#86198f', 'border' => '#f5d0fe'),
                13 => array('bg' => '#ccfbf1', 'color' => '#0f766e', 'border' => '#99f6e4'),
                14 => array('bg' => '#fef9c3', 'color' => '#854d0e', 'border' => '#fef08a'),
                15 => array('bg' => '#f1f5f9', 'color' => '#1e293b', 'border' => '#cbd5e1'),
                16 => array('bg' => '#fee2e2', 'color' => '#b91c1c', 'border' => '#fca5a5')
            );

            $arabic_week_names = array(
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

            $range_end_title = $arabic_week_names[$current_acad_week] ?? (' ' . $current_acad_week);

            $non_submitters = array();
            $compliant_teachers = array();
            $total_missing_preps = 0;

            foreach ($teachers as $t) {
                $preps = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d AND status IN ('submitted', 'approved', 'resubmitted', 'late')",
                    $t->ID
                ));

                $submitted_weeks = array();
                foreach ($preps as $p) {
                    $p_date_raw = (!empty($p->lesson_date) && $p->lesson_date !== '0000-00-00') ? $p->lesson_date : (!empty($p->submission_time) ? $p->submission_time : $p->created_at);
                    if (!empty($p_date_raw) && $p_date_raw !== '0000-00-00' && $p_date_raw !== '0000-00-00 00:00:00') {
                        $p_ts = strtotime($p_date_raw);
                        if ($p_ts >= $acad_anchor_ts) {
                            $w_num = intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1;
                            $submitted_weeks[$w_num] = true;
                        } else {
                            $submitted_weeks[1] = true;
                        }
                    }
                }

                $missing_weeks = array();
                for ($w = 1; $w <= $current_acad_week; $w++) {
                    if (!isset($submitted_weeks[$w])) {
                        $missing_weeks[] = $w;
                    }
                }

                $emp_number    = get_user_meta($t->ID, 'eess_employee_number', true) ?: ($t->ID);
                $sch_name      = get_user_meta($t->ID, 'eess_school_name', true) ?: 'Organization  Home';
                $grades_taught = EESS_Org_Helper::format_assigned_grades($t->ID);
                $subject       = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: '');

                $t_entry = array(
                    'user'          => $t,
                    'emp_number'    => $emp_number,
                    'school_name'   => $sch_name,
                    'grades_taught' => $grades_taught,
                    'subject'       => $subject,
                    'total_missing' => count($missing_weeks),
                    'missing_weeks' => $missing_weeks
                );

                if (!empty($missing_weeks)) {
                    $non_submitters[] = $t_entry;
                    $total_missing_preps += count($missing_weeks);
                } else {
                    $compliant_teachers[] = $t_entry;
                }
            }

            usort($non_submitters, function($a, $b) {
                return $b['total_missing'] <=> $a['total_missing'];
            });

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : '');
            $school_name = !empty($school_info['school_name']) ? $school_info['school_name'] : (!empty($school_info['name']) ? $school_info['name'] : '   ');

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>          </title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; line-height: 1.6; }
                    .report-header { border-bottom: 2px solid #881337; padding-bottom: 12px; margin-bottom: 15px; }
                    .top-brand-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; direction: rtl; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 48px; height: 48px; object-fit: contain; flex-shrink: 0; }
                    .main-report-title { font-size: 18px; font-weight: 900; color: #881337; text-align: center; margin: 12px 0 6px 0; }
                    .main-report-date-box { text-align: center; font-size: 11.5px; color: #334155; font-weight: 700; }
                    .main-report-date-title { font-weight: 900; color: #0f172a; margin-top: 2px; }
                    .main-report-range { font-weight: 900; color: #881337; font-size: 12.5px; margin-top: 1px; }
                    .intro-box { background: #f8fafc; border-right: 4px solid #881337; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; color: #1e293b; line-height: 1.8; font-weight: 700; }
                    .intro-box p { margin: 0 0 8px 0; }
                    .intro-box p:last-child { margin-bottom: 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; page-break-inside: auto; }
                    thead { display: table-header-group; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                    th { background: #1e293b; color: #fff; padding: 8px 10px; font-weight: 800; text-align: right; border: 1px solid #1e293b; }
                    td { padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    tr:nth-child(even) { background: #f8fafc; }
                    .pill-emp { background: #881337; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800; display: inline-block; font-family: monospace; }
                    .pill-subj { background: #dc2626; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800; display: inline-block; margin-right: 4px; }
                    .week-capsule { display: inline-block; padding: 3px 9px; margin: 2px 3px; border-radius: 9999px; font-weight: 800; font-size: 10.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #881337; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px; box-shadow: 0 4px 12px rgba(136,19,55,0.2);">️ Print Export PDF  A4</button>
                </div>

                <div class="report-header">
                    <div class="top-brand-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; direction: rtl;">
                        <div class="brand-box" style="display: flex; align-items: center; gap: 12px;">
                            <?php if ($school_logo): ?>
                                <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo" style="width: 48px; height: 48px; object-fit: contain; flex-shrink: 0; display: block;">
                            <?php endif; ?>
                            <div style="text-align: right;">
                                <div style="font-size: 14px; font-weight: 900; color: #0f172a; line-height: 1.2;"><?php echo esc_html($school_name); ?></div>
                                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-top: 2px;">    Academy</div>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-align: left;">
                             Issue: <?php echo esc_html($current_date_fmt); ?>
                        </div>
                    </div>

                    <h1 class="main-report-title" style="font-size: 18px; font-weight: 900; color: #881337; text-align: center; margin: 12px 0 6px 0;">          </h1>

                    <div class="main-report-date-box" style="text-align: center; font-size: 12px; color: #334155; font-weight: 700;">
                        <div style="font-weight: 900; color: #0f172a;"> Academy  </div>
                        <div style="font-weight: 900; color: #881337; font-size: 13px; margin-top: 1px;">   <?php echo esc_html($range_end_title); ?></div>
                    </div>
                </div>

                <div class="intro-box">
                    <p>                         Academy          <?php echo esc_html($range_end_title); ?>.        No             .</p>
                    <p>                      .</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 32px; text-align: center;">#</th>
                            <th style="width: 35%;">  / </th>
                            <th style="width: 30%;">Academy  Training Groups  </th>
                            <th style="width: 35%;">   </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($non_submitters)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #16a34a; padding: 25px; font-weight: 800; font-size: 13px;">
                                               Academy  !
                                </td>
                            </tr>
                        <?php else:
                            foreach ($non_submitters as $idx => $ns):
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
                                        <span class="pill-emp"><?php echo esc_html($ns['emp_number']); ?></span>
                                        <span class="pill-subj"><?php echo esc_html($ns['subject']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 11.5px;"><?php echo esc_html($ns['school_name']); ?></div>
                                    <div style="color: #475569; font-size: 10.5px; font-weight: 700; margin-top: 2px;">Training Groups: <?php echo esc_html($ns['grades_taught']); ?></div>
                                </td>
                                <td>
                                    <?php
                                    foreach ($ns['missing_weeks'] as $mw) {
                                        $w_label = $arabic_week_names[$mw] ?? (' ' . $mw);
                                        echo '<span class="week-capsule" style="' . $capsule_style . '">' . esc_html($w_label) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>

                <!-- SECOND SEPARATE SECTION: COMPLIANT TEACHERS -->
                <h2 style="font-size: 16px; font-weight: 900; color: #15803d; text-align: center; margin: 35px 0 6px 0; border-bottom: 2px solid #16a34a; padding-bottom: 8px;">

                </h2>

                <div style="text-align: center; font-size: 12px; color: #334155; font-weight: 700; margin-bottom: 15px;">
                    <div style="font-weight: 900; color: #0f172a; font-size: 12px;"> Academy  </div>
                    <div style="font-weight: 900; color: #15803d; font-size: 13px; margin-top: 2px;">   <?php echo esc_html($range_end_title); ?></div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 32px; text-align: center;">#</th>
                            <th style="width: 35%;">  / </th>
                            <th style="width: 30%;">Academy  Training Groups  </th>
                            <th style="width: 35%; text-align: center;"> No </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compliant_teachers)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">
                                    No      .
                                </td>
                            </tr>
                        <?php else:
                            foreach ($compliant_teachers as $idx => $cs):
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold;"><?php echo ($idx + 1); ?></td>
                                <td style="text-align: right;">
                                    <div style="font-size: 12.5px; font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?php echo esc_html($cs['user']->display_name); ?></div>
                                    <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                        <span class="pill-emp"><?php echo esc_html($cs['emp_number']); ?></span>
                                        <span class="pill-subj"><?php echo esc_html($cs['subject']); ?></span>
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

                <!-- BOTTOM SIGNATURE SECTION -->
                <div class="signature-section" style="margin-top: 45px; page-break-inside: avoid; display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 40px; font-family: 'Cairo', sans-serif;">
                    <div style="text-align: center; width: 180px;">
                        <div style="font-size: 13px; font-weight: 900; color: #0f172a; margin-bottom: 30px;"></div>
                        <div style="border-bottom: 1.5px dashed #64748b; width: 100%; margin: 0 auto;"></div>
                    </div>
                    <div style="text-align: center; width: 180px;">
                        <div style="font-size: 13px; font-weight: 900; color: #0f172a; margin-bottom: 30px;"> Academy </div>
                        <div style="border-bottom: 1.5px dashed #64748b; width: 100%; margin: 0 auto;"></div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'non_submission_term_plans') {
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);
            if (!$is_privileged) {
                wp_die(' No  No       Training Group.');
            }

            $term_num = isset($_GET['term_number']) ? intval($_GET['term_number']) : 1;
            if ($term_num < 1 || $term_num > 3) $term_num = 1;
            $filter_week_num = isset($_GET['week_num']) ? intval($_GET['week_num']) : 0;

            global $wpdb;
            $acad_struct = SM_Settings::get_academic_structure();
            $acad_year = $acad_struct['academic_year'] ?? '2027/2026';

            // Get configured deadline for the selected term
            $term_key = 'term' . $term_num;
            $raw_deadline = $acad_struct['term_dates'][$term_key]['deadline'] ?? '';
            $configured_deadline = !empty($raw_deadline) ? date('Y-m-d H:i:s', strtotime($raw_deadline)) : date('Y-m-d 23:59:59');

            // Fetch all active teachers strictly filtered by PE & Health specialization scope
            $all_teachers_raw = get_users(array('role' => 'sm_teacher', 'orderby' => 'display_name', 'order' => 'ASC'));
            $pe_teachers = array_filter($all_teachers_raw, function($t) {
                $spec = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: ''));
                return (mb_strpos($spec, '') !== false || mb_strpos($spec, '') !== false || mb_strpos($spec, 'Health') !== false || mb_strpos($spec, 'Physical') !== false);
            });
            $teachers = !empty($pe_teachers) ? array_values($pe_teachers) : $all_teachers_raw;

            // Sort teachers consecutively by school name
            usort($teachers, function($a, $b) {
                $schA = get_user_meta($a->ID, 'eess_school_name', true) ?: 'Academy  Home';
                $schB = get_user_meta($b->ID, 'eess_school_name', true) ?: 'Academy  Home';
                return strcmp($schA, $schB);
            });

            // Fetch all plans for target term number
            $plans_raw = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE term_number = %d", $term_num));
            $plans_by_teacher = array();
            foreach ($plans_raw as $p) {
                $plans_by_teacher[$p->teacher_id] = $p;
            }

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';
            $school_name = !empty($school_info['name']) ? $school_info['name'] : ' EESS    ';

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>     Training Group — Training Group <?php echo $term_num; ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 48px; height: 48px; object-fit: contain; }
                    .report-title { font-size: 16px; font-weight: 800; color: #881337; margin: 0 0 2px 0; }
                    .report-subtitle { font-size: 11px; color: #475569; font-weight: 700; margin: 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
                    th { background: #1e293b; color: #fff; padding: 7px 10px; font-weight: 800; text-align: right; border: 1px solid #1e293b; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    tr:nth-child(even) { background: #f8fafc; }
                    .status-pill { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-weight: 800; font-size: 10px; text-align: center; }
                    .pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
                    .pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
                    .pill-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecdd3; }
                    .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 15px; }
                    .summary-card { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 10px; border-radius: 8px; text-align: center; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #881337; color: #fff; border: none; padding: 7px 18px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">️ Print   A4</button>
                </div>
                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 700;">  No </div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">    Training Group (Training Group <?php echo $term_num; ?>)</h1>
                        <p class="report-subtitle"> : <?php echo esc_html($acad_year); ?> |  : <?php echo date_i18n('Y-m-d H:i', strtotime($configured_deadline)); ?></p>
                    </div>
                </div>

                <?php
                $total_teachers = count($teachers);
                $on_time_count = 0;
                $late_count = 0;
                $missing_count = 0;
                $rows_html = '';

                foreach ($teachers as $idx => $t) {
                    $emp_id = get_user_meta($t->ID, 'eess_employee_number', true) ?: ('EMP-' . $t->ID);
                    $t_school = get_user_meta($t->ID, 'eess_school_name', true) ?: 'Academy  Home';
                    $t_subj = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: ''));

                    $p = $plans_by_teacher[$t->ID] ?? null;
                    if ($p && in_array($p->status, array('submitted', 'approved'))) {
                        $sub_created = $p->updated_at ?: $p->created_at;
                        $sub_time = date_i18n('Y-m-d H:i', strtotime($sub_created));

                        if (strtotime($sub_created) <= strtotime($configured_deadline)) {
                            $on_time_count++;
                            $st_class = 'pill-success';
                            $st_text = '✓   ';
                            $delay_txt = '  ';
                        } else {
                            $late_count++;
                            $st_class = 'pill-warning';
                            $st_text = '⏱️  ';
                            $diff_sec = strtotime($sub_created) - strtotime($configured_deadline);
                            $diff_hours = round($diff_sec / 3600, 1);
                            $delay_txt = ' ' . $diff_hours . ' ';
                        }
                    } else {
                        $missing_count++;
                        $st_class = 'pill-danger';
                        $st_text = '⚠️   ';
                        $sub_time = '---';
                        $delay_txt = '---';
                    }

                    $rows_html .= '<tr>';
                    $rows_html .= '<td style="text-align:center;">' . ($idx + 1) . '</td>';
                    $rows_html .= '<td><strong>' . esc_html($t->display_name) . '</strong> <span style="color:#64748b; font-family:monospace; font-size:10px;">(' . esc_html($emp_id) . ')</span></td>';
                    $rows_html .= '<td>' . esc_html($t_school) . '</td>';
                    $rows_html .= '<td>' . esc_html($t_subj) . '</td>';
                    $rows_html .= '<td style="text-align:center;"><span class="status-pill ' . $st_class . '">' . $st_text . '</span></td>';
                    $rows_html .= '<td>' . $sub_time . '</td>';
                    $rows_html .= '<td>' . $delay_txt . '</td>';
                    $rows_html .= '</tr>';
                }

                $compliance_rate = $total_teachers > 0 ? round(($on_time_count / $total_teachers) * 100) : 0;
                ?>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #64748b; font-weight: 700;"> </div>
                        <div style="font-size: 16px; font-weight: 900; color: #0f172a;"><?php echo $total_teachers; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #166534; font-weight: 700;"> </div>
                        <div style="font-size: 16px; font-weight: 900; color: #15803d;"><?php echo $on_time_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #b45309; font-weight: 700;">  </div>
                        <div style="font-size: 16px; font-weight: 900; color: #d97706;"><?php echo $late_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #991b1b; font-weight: 700;">  </div>
                        <div style="font-size: 16px; font-weight: 900; color: #dc2626;"><?php echo $missing_count; ?></div>
                    </div>
                    <div class="summary-card">
                        <div style="font-size: 10px; color: #0369a1; font-weight: 700;"> No</div>
                        <div style="font-size: 16px; font-weight: 900; color: #0284c7;"><?php echo $compliance_rate; ?>%</div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align:center;">#</th>
                            <th>   </th>
                            <th>Organization  / Academy </th>
                            <th> / Sport Activity</th>
                            <th style="text-align:center;"> </th>
                            <th>  </th>
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $rows_html; ?>
                    </tbody>
                </table>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'school_lesson_prep_report' || $print_type === 'school_term_plans_report') {
            $user_roles = (array) wp_get_current_user()->roles;
            $is_privileged = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_activities_supervisor', $user_roles);
            if (!$is_privileged) {
                wp_die(' No  No    Academy  .');
            }

            $target_school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;
            $target_school = 'Academy  Home';
            if ($target_school_id > 0 && class_exists('EESS_Org_Helper')) {
                $sch_obj = EESS_Org_Helper::get_school_by_id($target_school_id);
                if ($sch_obj) $target_school = $sch_obj->name;
            } elseif (isset($_GET['school_name'])) {
                $target_school = sanitize_text_field($_GET['school_name']);
            }

            $term_num = isset($_GET['term_number']) ? intval($_GET['term_number']) : 1;
            if ($term_num < 1 || $term_num > 3) $term_num = 1;

            global $wpdb;
            $current_time_ts = current_time('timestamp');
            $current_date_fmt = current_time('Y-m-d');

            // Academic Start Anchor: 30 August 2026
            $acad_anchor_ts = strtotime('2026-08-30 00:00:00');
            if ($current_time_ts >= $acad_anchor_ts) {
                $diff_sec = $current_time_ts - $acad_anchor_ts;
                $current_acad_week = intval(floor($diff_sec / (7 * 86400))) + 1;
            } else {
                $current_acad_week = 1;
            }
            $current_acad_week = max(1, min(16, $current_acad_week));

            // Filter teachers strictly by target school ID or Name for dynamic school isolation
            $all_teachers_raw = get_users(array(
                'role__in' => array('sm_teacher', 'sm_coordinator', 'sm_hod'),
                'orderby'  => 'display_name',
                'order'    => 'ASC'
            ));

            $teachers = array_filter($all_teachers_raw, function($t) use ($target_school_id, $target_school) {
                if ($target_school_id > 0) {
                    $u_sch_id = get_user_meta($t->ID, 'eess_school_id', true) ?: get_user_meta($t->ID, 'sm_school_id', true);
                    if ($u_sch_id && intval($u_sch_id) === $target_school_id) return true;
                }
                $sch = get_user_meta($t->ID, 'eess_school_name', true) ?: 'Academy  Home';
                return (trim($sch) === trim($target_school));
            });
            $teachers = array_values($teachers);

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : '');
            $school_name = !empty($school_info['school_name']) ? $school_info['school_name'] : '   ';

            $arabic_week_names = array(
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
            $range_end_title = $arabic_week_names[$current_acad_week] ?? (' ' . $current_acad_week);

            $report_title = '           — ' . $target_school;

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title><?php echo esc_html($report_title); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; line-height: 1.6; }
                    .report-header { border-bottom: 2px solid #881337; padding-bottom: 12px; margin-bottom: 15px; }
                    .top-brand-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; direction: rtl; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 48px; height: 48px; object-fit: contain; flex-shrink: 0; }
                    .main-report-title { font-size: 18px; font-weight: 900; color: #881337; text-align: center; margin: 12px 0 6px 0; }
                    .main-report-date-box { text-align: center; font-size: 11.5px; color: #334155; font-weight: 700; }
                    .intro-box { background: #f8fafc; border-right: 4px solid #881337; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; color: #1e293b; line-height: 1.8; font-weight: 700; }
                    .intro-box p { margin: 0 0 8px 0; }
                    .intro-box p:last-child { margin-bottom: 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; page-break-inside: auto; }
                    thead { display: table-header-group; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                    th { background: #1e293b; color: #fff; padding: 8px 10px; font-weight: 800; text-align: right; border: 1px solid #1e293b; }
                    td { padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    tr:nth-child(even) { background: #f8fafc; }
                    .pill-emp { background: #881337; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800; display: inline-block; font-family: monospace; }
                    .pill-subj { background: #dc2626; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 800; display: inline-block; margin-right: 4px; }
                    .week-capsule { display: inline-block; padding: 3px 9px; margin: 2px 3px; border-radius: 9999px; font-weight: 800; font-size: 10.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #881337; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px; box-shadow: 0 4px 12px rgba(136,19,55,0.2);">️ Print Export PDF   A4</button>
                </div>

                <div class="report-header">
                    <div class="top-brand-bar">
                        <div class="brand-box">
                            <?php if ($school_logo): ?>
                                <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                            <?php endif; ?>
                            <div>
                                <div style="font-size: 14px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?> — <?php echo esc_html($target_school); ?></div>
                                <div style="font-size: 11px; color: #64748b; font-weight: 700;">    Academy</div>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-align: left;">
                             Issue: <?php echo esc_html($current_date_fmt); ?>
                        </div>
                    </div>

                    <h1 class="main-report-title">           — <?php echo esc_html($target_school); ?></h1>

                    <div class="main-report-date-box">
                        <div style="font-weight: 900; color: #0f172a;"> Academy  </div>
                        <div style="font-weight: 900; color: #881337; font-size: 12.5px; margin-top: 1px;">   <?php echo esc_html($range_end_title); ?></div>
                    </div>
                </div>

                <?php
                // Build Non-Submission Engine for School Scope
                $non_submitters_list = array();
                $compliant_teachers = array();

                foreach ($teachers as $t) {
                    $raw_emp = get_user_meta($t->ID, 'eess_employee_number', true) ?: (get_user_meta($t->ID, 'sm_employee_id', true) ?: $t->user_login);
                    $clean_emp_id = trim(str_replace('#', '', (string)$raw_emp));

                    $t_subj = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: ''));
                    $t_sch  = get_user_meta($t->ID, 'eess_school_name', true) ?: (get_user_meta($t->ID, 'sm_school_name', true) ?: $target_school);

                    $raw_grades = get_user_meta($t->ID, 'sm_assigned_grades', true) ?: (get_user_meta($t->ID, 'eess_assigned_grades', true) ?: (get_user_meta($t->ID, 'sm_grade_level', true) ?: ''));
                    $grades_clean = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::format_assigned_grades($raw_grades) : (is_array($raw_grades) ? implode(' ', $raw_grades) : (string)$raw_grades);
                    if (empty($grades_clean)) {
                        $grades_clean = 'Training Group 11 Training Group 12';
                    }

                    $teacher_preps = $wpdb->get_results($wpdb->prepare(
                        "SELECT id, lesson_date, created_at, submission_time, status FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d AND status IN ('submitted', 'approved', 'resubmitted', 'late')",
                        $t->ID
                    ));

                    $submitted_weeks = array();
                    $acad_anchor_ts = strtotime('2026-08-30 00:00:00');

                    foreach ($teacher_preps as $p) {
                        $p_date = (!empty($p->lesson_date) && $p->lesson_date !== '0000-00-00') ? $p->lesson_date : ($p->submission_time ?: $p->created_at);
                        if (!empty($p_date) && $p_date !== '0000-00-00 00:00:00') {
                            $p_ts = strtotime($p_date);
                            if ($p_ts >= $acad_anchor_ts) {
                                $diff_s = $p_ts - $acad_anchor_ts;
                                $w_num = intval(floor($diff_s / (7 * 86400))) + 1;
                            } else {
                                $w_num = 1;
                            }
                            if ($w_num >= 1 && $w_num <= $current_acad_week) {
                                $submitted_weeks[$w_num] = true;
                            }
                        }
                    }

                    $missing_weeks = array();
                    for ($w = 1; $w <= $current_acad_week; $w++) {
                        if (!isset($submitted_weeks[$w])) {
                            $missing_weeks[] = $w;
                        }
                    }

                    if (!empty($missing_weeks)) {
                        $non_submitters_list[] = array(
                            'teacher_obj'    => $t,
                            'emp_id'         => $clean_emp_id,
                            'teacher_name'   => $t->display_name,
                            'subject'        => $t_subj,
                            'school_name'    => $t_sch,
                            'assigned_grades' => $grades_clean,
                            'missing_weeks'  => $missing_weeks,
                            'missing_count'  => count($missing_weeks)
                        );
                    } else {
                        $compliant_teachers[] = array(
                            'teacher_obj'    => $t,
                            'emp_id'         => $clean_emp_id,
                            'teacher_name'   => $t->display_name,
                            'subject'        => $t_subj,
                            'school_name'    => $t_sch,
                            'assigned_grades' => $grades_clean
                        );
                    }
                }

                usort($non_submitters_list, function($a, $b) {
                    if ($a['missing_count'] === $b['missing_count']) {
                        return strcmp($a['teacher_name'], $b['teacher_name']);
                    }
                    return ($b['missing_count'] - $a['missing_count']);
                });
                ?>

                <!-- Introductory Explanatory Box -->
                <div class="intro-box">
                    <p>                  .   No                     .</p>
                    <p>  Next   Academy      Academy  (      )   Academy    Actions    No.</p>
                </div>

                <!-- TABLE 1: NON-SUBMITTING TEACHERS -->
                <h2 style="font-size: 14px; font-weight: 900; color: #881337; margin: 18px 0 8px 0; border-right: 4px solid #881337; padding-right: 8px;">
                    No:       (    )
                </h2>

                <?php if (empty($non_submitters_list)): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; font-weight: 800; font-size: 12px; margin-bottom: 20px;">
                        ✓   Academy     Academy   .
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 32px; text-align: center;">#</th>
                                <th style="width: 32%;">  / </th>
                                <th style="width: 25%;">Academy  / Training Groups  </th>
                                <th style="width: 12%; text-align: center;">  </th>
                                <th style="width: 31%;">   </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($non_submitters_list as $index => $row):
                                $m_count = $row['missing_count'];
                                if ($m_count >= 5) {
                                    $badge_bg = '#fee2e2'; $badge_txt = '#991b1b'; $badge_bd = '#fecdd3';
                                } elseif ($m_count >= 3) {
                                    $badge_bg = '#ffedd5'; $badge_txt = '#9a3412'; $badge_bd = '#fed7aa';
                                } else {
                                    $badge_bg = '#fef3c7'; $badge_txt = '#92400e'; $badge_bd = '#fde68a';
                                }
                            ?>
                                <tr>
                                    <td style="text-align: center; font-weight: 800; color: #64748b;"><?php echo ($index + 1); ?></td>
                                    <td>
                                        <div style="font-weight: 900; color: #0f172a; font-size: 12px; margin-bottom: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span><?php echo esc_html($row['teacher_name']); ?></span>
                                            <span class="pill-emp"><?php echo esc_html($row['emp_id']); ?></span>
                                            <span class="pill-subj"><?php echo esc_html($row['subject']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; color: #0f172a; font-size: 11.5px;"><?php echo esc_html($row['school_name']); ?></div>
                                        <div style="font-size: 10.5px; color: #475569; font-weight: 700; margin-top: 2px;">
                                            <?php echo esc_html($row['assigned_grades']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_txt; ?>; border: 1px solid <?php echo $badge_bd; ?>; padding: 4px 10px; border-radius: 9999px; font-weight: 900; font-size: 11px; display: inline-block;">
                                            <?php echo $m_count; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-wrap: wrap; gap: 3px;">
                                            <?php foreach ($row['missing_weeks'] as $mw):
                                                $w_name = $arabic_week_names[$mw] ?? (' ' . $mw);
                                                if ($m_count >= 5) {
                                                    $c_bg = '#fecdd3'; $c_txt = '#881337';
                                                } elseif ($m_count >= 3) {
                                                    $c_bg = '#fed7aa'; $c_txt = '#7c2d12';
                                                } else {
                                                    $c_bg = '#fef08a'; $c_txt = '#713f12';
                                                }
                                            ?>
                                                <span class="week-capsule" style="background: <?php echo $c_bg; ?>; color: <?php echo $c_txt; ?>;">
                                                    <?php echo esc_html($w_name); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- TABLE 2: COMPLIANT TEACHERS -->
                <h2 style="font-size: 14px; font-weight: 900; color: #166534; margin: 24px 0 8px 0; border-right: 4px solid #166534; padding-right: 8px;">
                    :       (  )
                </h2>

                <?php if (empty($compliant_teachers)): ?>
                    <div style="background: #fff1f2; border: 1px solid #fecdd3; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-weight: 800; font-size: 12px; margin-bottom: 20px;">
                        ⚠️ No       .
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 32px; text-align: center; background: #064e3b;">#</th>
                                <th style="width: 35%; background: #064e3b;">  / </th>
                                <th style="width: 33%; background: #064e3b;">Academy  / Training Groups  </th>
                                <th style="width: 32%; text-align: center; background: #064e3b;"> No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compliant_teachers as $c_idx => $c_row): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: 800; color: #64748b;"><?php echo ($c_idx + 1); ?></td>
                                    <td>
                                        <div style="font-weight: 900; color: #0f172a; font-size: 12px; margin-bottom: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span><?php echo esc_html($c_row['teacher_name']); ?></span>
                                            <span class="pill-emp" style="background: #166534;"><?php echo esc_html($c_row['emp_id']); ?></span>
                                            <span class="pill-subj" style="background: #15803d;"><?php echo esc_html($c_row['subject']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; color: #0f172a; font-size: 11.5px;"><?php echo esc_html($c_row['school_name']); ?></div>
                                        <div style="font-size: 10.5px; color: #475569; font-weight: 700; margin-top: 2px;">
                                            <?php echo esc_html($c_row['assigned_grades']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 4px 12px; border-radius: 9999px; font-weight: 900; font-size: 11px; display: inline-flex; align-items: center; gap: 5px;">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; background: #16a34a; color: white; border-radius: 50%; font-size: 10px; font-weight: 900;">✓</span>
                                            <span>   ( <?php echo esc_html($range_end_title); ?>)</span>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Official Bottom Signature Section -->
                <div style="margin-top: 40px; page-break-inside: avoid; border-top: 2px solid #cbd5e1; padding-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 0 30px;">
                        <div style="text-align: center; width: 220px;">
                            <div style="font-size: 13px; font-weight: 900; color: #0f172a; margin-bottom: 8px;">  / </div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 35px;"> No</div>
                            <div style="border-bottom: 1.5px dashed #94a3b8; width: 100%;"></div>
                        </div>

                        <div style="text-align: center; width: 220px;">
                            <div style="font-size: 13px; font-weight: 900; color: #0f172a; margin-bottom: 8px;"> Academy </div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 35px;">  </div>
                            <div style="border-bottom: 1.5px dashed #94a3b8; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'teacher_profile_report') {
            $t_id = intval($_GET['teacher_id'] ?? 0);
            $user = get_userdata($t_id);
            if (!$user) wp_die('   .');

            global $wpdb;
            $roles = (array) $user->roles;
            $primary_role = !empty($roles) ? reset($roles) : 'sm_teacher';
            $role_labels = array(
                'administrator' => '  ',
                'sm_system_admin' => '  ',
                'sm_principal' => ' Academy ',
                'sm_supervisor' => ' ',
                'sm_coordinator' => ' ',
                'sm_teacher' => '',
                'sm_student' => 'No',
                'sm_parent' => ' ',
                'sm_discipline_supervisor' => '  / ',
                'sm_activities_supervisor' => ' Active',
                'sm_transportation_supervisor' => '  No',
                'sm_bus_supervisor' => ' ',
                'sm_clinic' => ' ',
                'sm_hr' => '  (HR)'
            );

            $emp_number = get_user_meta($t_id, 'eess_employee_number', true) ?: (get_user_meta($t_id, 'sm_employee_id', true) ?: $user->user_login);
            $school_name = get_user_meta($t_id, 'eess_school_name', true) ?: 'Academy  Home';
            $department = get_user_meta($t_id, 'eess_department', true) ?: (get_user_meta($t_id, 'department', true) ?: get_user_meta($t_id, 'sm_department', true) ?: '   ');
            $subject = get_user_meta($t_id, 'sm_specialization', true) ?: (get_user_meta($t_id, 'specialization', true) ?: '  ');

            $assigned_grades_raw = get_user_meta($t_id, 'sm_assigned_grades', true) ?: (get_user_meta($t_id, 'eess_assigned_grades', true) ?: (get_user_meta($t_id, 'sm_grade_level', true) ?: 'Training Group '));
            if (is_array($assigned_grades_raw)) $assigned_grades_raw = implode(', ', $assigned_grades_raw);
            $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);

            $assigned_sections_raw = get_user_meta($t_id, 'sm_assigned_sections', true) ?: (get_user_meta($t_id, 'eess_assigned_sections', true) ?: ' 1  2');
            if (is_array($assigned_sections_raw)) $assigned_sections_raw = implode(', ', $assigned_sections_raw);

            $phone = get_user_meta($t_id, 'phone_number', true) ?: (get_user_meta($t_id, 'sm_phone', true) ?: '---');
            $civil_id = get_user_meta($t_id, 'eess_civil_id', true) ?: (get_user_meta($t_id, 'civil_id', true) ?: '---');
            $nationality = get_user_meta($t_id, 'nationality', true) ?: (get_user_meta($t_id, 'sm_nationality', true) ?: 'United Arab Emirates');
            $gender = get_user_meta($t_id, 'gender', true) ?: (get_user_meta($t_id, 'eess_gender', true) ?: 'Male');
            $dob = get_user_meta($t_id, 'dob', true) ?: (get_user_meta($t_id, 'sm_dob', true) ?: '---');
            $emirate = get_user_meta($t_id, 'eess_emirate', true) ?: '';
            $appoint_year = get_user_meta($t_id, 'eess_appointment_year', true) ?: (get_user_meta($t_id, 'appointment_year', true) ?: '2022');
            $photo_url = get_avatar_url($t_id, array('size' => 120));

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';

            // Activity Queries
            $term_plans = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d ORDER BY term_number ASC", $t_id));
            $lesson_preps = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 15", $t_id));

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>   — <?php echo esc_html($user->display_name); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 52px; height: 52px; object-fit: contain; }
                    .report-title { font-size: 15px; font-weight: 900; color: #881337; margin: 0 0 3px 0; }
                    .report-subtitle { font-size: 11px; color: #475569; font-weight: 700; margin: 0; }
                    .card { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 15px; margin-bottom: 14px; }
                    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                    th { background: #0f172a; color: #fff; padding: 6px 10px; font-weight: 800; text-align: right; border: 1px solid #0f172a; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">️ Print  PDF</button>
                </div>

                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 15px; font-weight: 900; color: #0f172a;">   </div>
                            <div style="font-size: 12px; color: #0284c7; font-weight: 800; margin-top: 2px;"><?php echo esc_html($school_name); ?></div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">   </h1>
                        <p class="report-subtitle"> : <?php echo current_time('Y-m-d H:i'); ?></p>
                    </div>
                </div>

                <!-- Teacher Header Identity -->
                <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="<?php echo esc_url($photo_url); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #0284c7;">
                        <div>
                            <h2 style="margin:0 0 4px 0; font-size: 16px; font-weight: 900; color: #0f172a;"><?php echo esc_html($user->display_name); ?></h2>
                            <div style="font-size: 11px; color: #475569; font-weight: 700;">
                                <strong>:</strong> <?php echo esc_html($role_labels[$primary_role] ?? ''); ?> |
                                <strong>:</strong> <?php echo esc_html($emp_number); ?> |
                                <strong>Academy :</strong> <?php echo esc_html($school_name); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal & Academic Assignment Card -->
                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #881337; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">Personal Information   </h4>
                    <div class="grid-2">
                        <div><strong> / Sport Activity:</strong> <?php echo esc_html($department . ' | ' . $subject); ?></div>
                        <div><strong>Training Groups :</strong> <?php echo esc_html($assigned_grades_clean ?: ''); ?></div>
                        <div><strong> :</strong> <?php echo esc_html($assigned_sections_raw ?: ''); ?></div>
                        <div><strong> :</strong> <?php echo esc_html($phone . ' | ' . $user->user_email); ?></div>
                        <div><strong>  :</strong> <?php echo esc_html($appoint_year . ' — ' . $emirate); ?></div>
                        <div><strong>Gender Gender:</strong> <?php echo esc_html($nationality . ' — ' . $gender); ?></div>
                        <div><strong>Date of Birth:</strong> <?php echo esc_html($dob); ?></div>
                        <div><strong>National ID:</strong> <?php echo esc_html($civil_id); ?></div>
                    </div>
                </div>

                <!-- Term Plans Summary Card -->
                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #0284c7; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">   Training Group Annual (<?php echo count($term_plans); ?> )</h4>
                    <?php if (empty($term_plans)): ?>
                        <div style="color: #64748b;">No     .</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Training Group </th>
                                    <th>Sport Activity Training Group</th>
                                    <th> </th>
                                    <th>Status </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($term_plans as $tp):
                                    $st = '';
                                    if ($tp->status === 'submitted') $st = ' ';
                                    elseif ($tp->status === 'approved') $st = ' ';
                                    elseif ($tp->status === 'returned') $st = ' Edit';
                                ?>
                                    <tr>
                                        <td>Training Group  <?php echo intval($tp->term_number); ?></td>
                                        <td><?php echo esc_html($tp->subject . ' (' . $tp->grade . ')'); ?></td>
                                        <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($tp->updated_at ?: $tp->created_at))); ?></td>
                                        <td><strong><?php echo $st; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Lesson Prep Summary Card -->
                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #16a34a; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">        ( <?php echo count($lesson_preps); ?> )</h4>
                    <?php if (empty($lesson_preps)): ?>
                        <div style="color: #64748b;">No     .</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th> </th>
                                    <th>Sport Activity Training Group</th>
                                    <th>  </th>
                                    <th>Status </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
                                foreach ($lesson_preps as $lp):
                                    $dt = $lp->updated_at ?: $lp->created_at;
                                    $p_ts = strtotime($dt);
                                    $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
                                    $st = '';
                                    if ($lp->status === 'submitted') $st = ' ';
                                    elseif ($lp->status === 'approved') $st = ' ';
                                    elseif ($lp->status === 'revision_required' || $lp->status === 'returned') $st = ' Edit';
                                ?>
                                    <tr>
                                        <td> <?php echo $cw; ?>: <?php echo esc_html($lp->title ?: ' '); ?></td>
                                        <td><?php echo esc_html($lp->subject . ' (' . $lp->grade_level . ')'); ?></td>
                                        <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($dt))); ?></td>
                                        <td><strong><?php echo $st; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'eval_report') {
            $eval_id = intval($_GET['eval_id'] ?? 0);
            global $wpdb;

            $eval = $wpdb->get_row($wpdb->prepare("
                SELECT e.*, u_emp.display_name as emp_name, u_eval.display_name as evaluator_name
                FROM {$wpdb->prefix}sm_evaluations e
                JOIN {$wpdb->prefix}users u_emp ON e.employee_id = u_emp.ID
                JOIN {$wpdb->prefix}users u_eval ON e.evaluator_id = u_eval.ID
                WHERE e.id = %d
            ", $eval_id));

            if (!$eval) wp_die('    .');

            $emp_num = get_user_meta($eval->employee_id, 'eess_employee_number', true) ?: $eval->employee_id;
            $school_name = get_user_meta($eval->employee_id, 'eess_school_name', true) ?: 'Academy  Home';
            $department = get_user_meta($eval->employee_id, 'eess_department', true) ?: (get_user_meta($eval->employee_id, 'department', true) ?: '   ');
            $subject = get_user_meta($eval->employee_id, 'sm_specialization', true) ?: '';
            $photo_url = get_avatar_url($eval->employee_id, array('size' => 100));

            $school_info = SM_Settings::get_school_info();
            $school_logo = !empty($school_info['logo_url']) ? $school_info['logo_url'] : '';

            $answers = json_decode($eval->answers_json ?? '[]', true);

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>    — <?php echo esc_html($eval->emp_name); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 15px; direction: rtl; font-size: 11px; }
                    .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
                    .brand-box { display: flex; align-items: center; gap: 12px; }
                    .brand-logo { width: 52px; height: 52px; object-fit: contain; }
                    .report-title { font-size: 15px; font-weight: 900; color: #881337; margin: 0 0 3px 0; }
                    .card { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 15px; margin-bottom: 14px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                    th { background: #0f172a; color: #fff; padding: 6px 10px; font-weight: 800; text-align: right; border: 1px solid #0f172a; }
                    td { padding: 6px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; }
                    @media print { body { padding: 0; } .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="margin-bottom: 15px; text-align: left;">
                    <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-family: 'Cairo'; font-weight: 800; border-radius: 9999px; cursor: pointer; font-size: 12px;">️ Print  PDF</button>
                </div>

                <div class="report-header">
                    <div class="brand-box">
                        <?php if ($school_logo): ?>
                            <img src="<?php echo esc_url($school_logo); ?>" class="brand-logo" alt="Logo">
                        <?php endif; ?>
                        <div>
                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;">   </div>
                            <div style="font-size: 11.5px; color: #0284c7; font-weight: 800;"><?php echo esc_html($school_name); ?></div>
                        </div>
                    </div>
                    <div style="text-align: left;">
                        <h1 class="report-title">   Annual </h1>
                        <p style="margin:0; font-size:11px; color:#64748b;"> : <?php echo esc_html($eval->academic_year); ?></p>
                    </div>
                </div>

                <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="<?php echo esc_url($photo_url); ?>" style="width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid #881337;">
                        <div>
                            <h2 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 900; color: #0f172a;"><?php echo esc_html($eval->emp_name); ?></h2>
                            <div style="font-size: 11px; color: #475569;"> : <strong><?php echo esc_html($emp_num); ?></strong> | : <strong><?php echo esc_html($department); ?></strong></div>
                        </div>
                    </div>
                    <div style="text-align: center; background: #ffffff; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        <div style="font-size: 10px; color: #64748b; font-weight: bold;"> </div>
                        <div style="font-size: 20px; font-weight: 900; color: #16a34a;"><?php echo intval($eval->average_pct); ?>%</div>
                    </div>
                </div>

                <div class="card">
                    <h4 style="margin:0 0 8px 0; font-size: 12.5px; font-weight: 800; color: #881337; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">    (0–10)</h4>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 30px; text-align:center;">#</th>
                                <th> /  </th>
                                <th style="width: 100px; text-align:center;"> </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($answers)): foreach ($answers as $i => $ans): ?>
                                <tr>
                                    <td style="text-align:center; font-weight:bold;"><?php echo ($i + 1); ?></td>
                                    <td><?php echo esc_html($ans['question_text'] ?? '-'); ?></td>
                                    <td style="text-align:center; font-weight:bold; color:#881337;"><?php echo floatval($ans['score'] ?? 0); ?> / 10</td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($eval->comments)): ?>
                <div class="card">
                    <h4 style="margin:0 0 6px 0; font-size: 12px; font-weight: 800; color: #0f172a;">Notes  :</h4>
                    <p style="margin:0; font-size: 11.5px; color: #334155; white-space: pre-line;"><?php echo esc_html($eval->comments); ?></p>
                </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 15px; border-top: 1px solid #cbd5e1; font-size: 11px;">
                    <div> : <strong><?php echo esc_html($eval->evaluator_name); ?></strong></div>
                    <div> No: <strong><?php echo date_i18n('Y-m-d H:i', strtotime($eval->created_at)); ?></strong></div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'parent_summons') {
            $summons_id = intval($_GET['summons_id'] ?? 0);
            global $wpdb;
            $summons = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_parent_summons WHERE id = %d", $summons_id));
            if (!$summons) wp_die(' No  .');

            $student = SM_DB::get_student_by_id($summons->student_id);
            if (!$student) wp_die('No   .');

            $school_info = SM_Settings::get_school_info();
            $sch_obj = $student->school_id ? EESS_Org_Helper::get_school_by_id($student->school_id) : null;
            $school_name = $sch_obj ? $sch_obj->name : ($school_info['school_name'] ?? ' EESS ');

            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>    — <?php echo esc_html($student->name); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    body { font-family: 'Cairo', sans-serif; padding: 40px; color: #0f172a; background: white; line-height: 1.8; direction: rtl; text-align: right; }
                    .document-card { max-width: 800px; margin: 0 auto; border: 2px solid #0f172a; border-radius: 16px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #881337; padding-bottom: 20px; margin-bottom: 25px; }
                    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                    .meta-table th, .meta-table td { border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 13px; text-align: right; }
                    .meta-table th { background: #f8fafc; font-weight: bold; width: 25%; color: #334155; }
                    .footer-sig { display: flex; justify-content: space-between; margin-top: 50px; text-align: center; }
                    @media print { .no-print { display: none !important; } body { padding: 0; } .document-card { border: none; box-shadow: none; width: 100%; } }
                </style>
            </head>
            <body onload="window.print()">
                <div class="no-print" style="text-align: center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="background: #881337; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; font-family: 'Cairo';">️ Print  No  A4</button>
                </div>
                <div class="document-card">
                    <div class="header">
                        <div>
                            <h2 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></h2>
                            <div style="font-size: 13px; color: #881337; font-weight: 800; margin-top: 4px;"><?php echo esc_html($summons->department_requester); ?> —     </div>
                        </div>
                        <img src="<?php echo esc_url($school_info['logo_url'] ?? SM_PLUGIN_URL . 'assets/images/logo.png'); ?>" style="height: 50px; max-width: 120px; object-fit: contain;" onerror="this.style.display='none'">
                    </div>

                    <div style="background: #fff8f8; border: 1px solid #fecdd3; padding: 15px 20px; border-radius: 12px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 6px 0; color: #881337; font-size: 15px; font-weight: 800;">   No / No: <?php echo esc_html($student->name); ?></h4>
                        <p style="margin: 0; font-size: 13px; color: #334155;">        Academy        No.</p>
                    </div>

                    <table class="meta-table">
                        <tr><th>Player Name:</th><td><strong><?php echo esc_html($student->name); ?></strong></td><th> / Training Group:</th><td><?php echo esc_html($student->student_code . ' | ' . $student->class_name . ' (' . ($student->section ?: '') . ')'); ?></td></tr>
                        <tr><th> :</th><td><strong><?php echo esc_html($summons->summons_date); ?></strong></td><th> :</th><td><strong><?php echo esc_html($summons->summons_time ?: '10:00 '); ?></strong></td></tr>
                        <tr><th> No:</th><td><?php echo esc_html($summons->department_requester); ?></td><th> No:</th><td><strong><?php echo esc_html($summons->reason); ?></strong></td></tr>
                        <?php if (!empty($summons->notes)): ?>
                            <tr><th>Notes :</th><td colspan="3"><?php echo esc_html($summons->notes); ?></td></tr>
                        <?php endif; ?>
                    </table>

                    <div class="footer-sig">
                        <div>
                            <div>  No</div>
                            <div style="margin-top: 35px; font-weight: 800;"><?php echo esc_html($summons->department_requester); ?></div>
                        </div>
                        <div>
                            <div>   Academy </div>
                            <div style="margin-top: 35px; font-weight: 800;"><?php echo esc_html($school_name); ?></div>
                        </div>
                    </div>

                    <div style="margin-top: 40px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 10px; font-size: 10px; color: #64748b;">Powered by Educational Electronic Systems Solutions (EESS) — eess.online</div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'id_card' || $print_type === 'student_card') {
            global $wpdb;
            $stu_ids = array();
            if (!empty($_GET['student_id'])) {
                $stu_ids[] = intval($_GET['student_id']);
            } elseif (!empty($_GET['student_ids'])) {
                $stu_ids = array_map('intval', explode(',', $_GET['student_ids']));
            } else {
                $stu_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}sm_students ORDER BY name ASC LIMIT 50");
            }

            if (empty($stu_ids)) {
                wp_die('    No Print.');
            }

            // Fetch all student records first for bulk photo filtering
            $all_students = array();
            foreach ($stu_ids as $sid) {
                $st = SM_DB::get_student_by_id($sid);
                if ($st) {
                    $all_students[] = $st;
                }
            }

            $excluded_students = array();
            $printable_students = array();

            if (count($all_students) > 1) {
                foreach ($all_students as $st) {
                    if (empty($st->photo_url)) {
                        $excluded_students[] = $st;
                    } else {
                        $printable_students[] = $st;
                    }
                }
                // If all selected students lack photos, fallback to printing all with notice placeholders
                if (empty($printable_students)) {
                    $printable_students = $all_students;
                    $excluded_students = array();
                }
            } else {
                $printable_students = $all_students;
            }

            $school_info = SM_Settings::get_school_info();
            $system_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
            $acad_struct = SM_Settings::get_academic_structure();
            $acad_year = $acad_struct['academic_year'] ?? '2026/2027';
            $expiry_date = '30/06/' . (substr($acad_year, -4) ?: '2027');

            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>Student Exit ID Card —    </title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: 'Cairo', sans-serif; background: #e2e8f0; color: #0f172a; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .cards-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }

                    /* Standard CR80 Personal ID Card Dimensions: 85.6mm x 53.98mm with realistic plastic rounded corners (3.18mm) */
                    .id-card {
                        width: 85.6mm;
                        height: 53.98mm;
                        background: #ffffff;
                        border: 1px solid #cbd5e1;
                        border-radius: 3.18mm;
                        position: relative;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                        page-break-inside: avoid;
                        overflow: hidden;
                    }

                    /* Header spanning full width edge-to-edge */
                    .card-header {
                        width: 100%;
                        background: linear-gradient(135deg, #881337 0%, #4c0519 100%);
                        color: #ffffff;
                        padding: 4px 10px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid #e11d48;
                    }
                    .card-header-right { display: flex; align-items: center; gap: 8px; }

                    /* Compact Rounded Square Logo Box */
                    .card-logo-box {
                        width: 26px;
                        height: 26px;
                        border-radius: 6px;
                        background: #ffffff;
                        padding: 2px;
                        border: 1px solid rgba(255, 255, 255, 0.4);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
                    }
                    .card-sys-logo { width: 100%; height: 100%; object-fit: contain; border-radius: 4px; }

                    .card-header-titles { line-height: 1.15; }
                    .card-title-main { font-size: 11.5px; font-weight: 900; color: #ffffff; letter-spacing: -0.2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 48mm; }
                    .card-school-name { font-size: 8.5px; font-weight: 700; color: #fecdd3; }
                    .card-acad-year-text { font-size: 10px; color: #ffffff; font-weight: 900; text-align: left; letter-spacing: 0.5px; }

                    .card-body {
                        display: flex;
                        gap: 6px;
                        align-items: center;
                        padding: 4px 7px;
                        flex: 1;
                        position: relative;
                        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 150' preserveAspectRatio='none'><path d='M0,30 Q125,110 250,30 T500,30 L500,150 L0,150 Z' fill='%23f1f5f9' opacity='0.25'/><path d='M0,60 C150,140 350,-20 500,70 L500,150 L0,150 Z' fill='%23ffe4e6' opacity='0.12'/><path d='M0,90 C180,10 320,130 500,40' fill='none' stroke='%23881337' stroke-width='1.5' opacity='0.06'/><path d='M0,110 C140,40 360,120 500,70' fill='none' stroke='%23e11d48' stroke-width='1.5' opacity='0.05'/><path d='M0,50 C160,120 340,20 500,90' fill='none' stroke='%2394a3b8' stroke-width='1.5' opacity='0.08'/></svg>");
                        background-repeat: no-repeat;
                        background-size: cover;
                        background-position: center;
                    }

                    /* Photo Wrapper & Blurred Missing Photo Placeholder */
                    .card-photo-box {
                        width: 22mm;
                        height: 28mm;
                        border-radius: 4px;
                        border: 1.5px solid #0f172a;
                        background: #f1f5f9;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        flex-shrink: 0;
                        overflow: hidden;
                        position: relative;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .card-photo {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .card-photo-missing {
                        width: 100%;
                        height: 100%;
                        background: #cbd5e1;
                        filter: blur(0.5px);
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                        padding: 2px;
                        color: #475569;
                    }
                    .card-photo-missing-text {
                        font-size: 6.5px;
                        font-weight: 800;
                        line-height: 1.15;
                        color: #1e293b;
                        background: rgba(255, 255, 255, 0.85);
                        padding: 3px 2px;
                        border-radius: 3px;
                        border: 1px solid #94a3b8;
                    }

                    /* Student Info Layout — Tight, Balanced & Professional Typography */
                    .card-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; padding-right: 2px; }
                    .card-stu-name { font-weight: 900; color: #0f172a; margin-bottom: 2px; white-space: nowrap; line-height: 1.25; }
                    .card-field { font-size: 8.5px; color: #334155; font-weight: 700; margin-bottom: 1px; display: flex; align-items: center; gap: 2px; }
                    .card-field-label { color: #64748b; font-weight: 700; width: 36px; min-width: 36px; flex-shrink: 0; }
                    .card-field-val { color: #0f172a; font-weight: 900; white-space: nowrap; }

                    /* Barcode Stack Aligned Left Above Bottom Strip */
                    .card-qr-stack { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; width: 33mm; flex-shrink: 0; text-align: center; margin-top: auto; }
                    .card-qr-box { width: 33mm; height: 16.5mm; border: none; border-radius: 0; padding: 0; background: transparent; box-shadow: none; margin-top: 3px; }
                    .card-qr-box svg { width: 100%; height: 100%; display: block; }
                    .card-qr-box svg rect:first-child { fill: transparent !important; }
                    .card-barcode-code-label { font-size: 7px; font-weight: 900; color: #0f172a; font-family: monospace, sans-serif; letter-spacing: 1.5px; text-align: center; width: 100%; margin-top: -1px; display: block; white-space: nowrap; }
                    .card-serial-text { display: none !important; }

                    /* Footer Area */
                    .card-footer {
                        width: 100%;
                        background: #f8fafc;
                        border-top: 1px solid #e2e8f0;
                        padding: 2px 8px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        font-size: 6.5px;
                        color: #64748b;
                        font-weight: 700;
                    }
                    .card-footer-auth { color: #166534; font-weight: 900; }

                    @media print {
                        @page { size: A4; margin: 10mm; }
                        body { background: none; padding: 0; }
                        .no-print { display: none !important; }
                        .cards-container { gap: 8mm; }
                        .id-card { box-shadow: none; border: 1px solid #94a3b8; page-break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
                <div class="no-print" style="text-align: center; margin-bottom: 15px;">
                    <button onclick="window.print()" style="background: #881337; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; font-family: 'Cairo'; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">️ Print    (ID Card / A4)</button>
                </div>

                <?php if (!empty($excluded_students)): ?>
                <div class="no-print" style="max-width: 800px; margin: 0 auto 15px auto; background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 10px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-align: right; line-height: 1.5;">
                    ⚠️ :   (<?php echo count($excluded_students); ?>) No   Print      :
                    <strong><?php echo esc_html(implode(' ', array_map(function($s) { return $s->name; }, $excluded_students))); ?></strong>.
                </div>
                <?php endif; ?>

                <div class="cards-container">
                    <?php foreach ($printable_students as $st):
                        $inst_obj = $st->institution_id ? EESS_Org_Helper::get_institution_by_id($st->institution_id) : ($st->school_id ? EESS_Org_Helper::get_institution_by_id($st->school_id) : null);
                        $sch_obj  = $st->school_id ? EESS_Org_Helper::get_school_by_id($st->school_id) : null;
                        $s_name   = $inst_obj ? $inst_obj->name : ($sch_obj ? $sch_obj->name : ($school_info['school_name'] ?? ' EESS '));
                        $s_logo   = ($inst_obj && !empty($inst_obj->logo_url)) ? esc_url($inst_obj->logo_url) : (($sch_obj && !empty($sch_obj->logo_url)) ? esc_url($sch_obj->logo_url) : $system_logo);

                        $serial = !empty($st->student_code) ? $st->student_code : ('STU-' . $st->id);
                        $barcode_identity = $serial;
                        $qr_svg = $this->eess_generate_qr_code_svg($barcode_identity);
                        $has_photo = !empty($st->photo_url);
                        $photo_src = $has_photo ? esc_url($st->photo_url) : '';

                        // Dynamic single-line font scaling for student name
                        $name_len = mb_strlen($st->name);
                        $name_font_size = $name_len > 35 ? '8px' : ($name_len > 28 ? '9px' : ($name_len > 22 ? '10px' : '11px'));

                        // Clean non-duplicated values (Strip duplicated 'Training Group' or '' labels)
                        $clean_class = trim(preg_replace('/^(Training Group||Grade|grade)\s*:?\s*/u', '', $st->class_name ?: ''));
                        $clean_section = trim(preg_replace('/^(Training Group||Section|section)\s*:?\s*/u', '', $st->section ?: ''));

                        // Academic stage color indicator resolution from Grade ID / Code
                        $grade_num = intval($st->grade_id);
                        if ($grade_num <= 0) {
                            preg_match('/(\d+)/', $st->class_name ?: '', $m_g);
                            if (!empty($m_g[1])) {
                                $grade_num = intval($m_g[1]);
                            } else {
                                $g_map = array(''=>1, ''=>2, ''=>3, ''=>4, ''=>5, ''=>6, ''=>7, ''=>8, ''=>9, ''=>10, ' '=>11, ' '=>12);
                                foreach ($g_map as $g_txt => $g_val) {
                                    if (mb_strpos($st->class_name ?: '', $g_txt) !== false) {
                                        $grade_num = $g_val;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($grade_num >= 10) {
                            $stage_color = '#d97706'; // Secondary Stage (Amber/Gold)
                            $stage_label = ' ';
                        } elseif ($grade_num >= 6) {
                            $stage_color = '#7c3aed'; // Middle Stage (Purple)
                            $stage_label = ' Intermediate';
                        } else {
                            $stage_color = '#0284c7'; // Primary Stage (Ocean Blue)
                            $stage_label = ' No';
                        }
                    ?>
                    <div class="id-card">
                        <div class="card-header">
                            <div class="card-header-right">
                                <div class="card-logo-box">
                                    <img src="<?php echo esc_url($s_logo); ?>" class="card-sys-logo" alt="Logo" onerror="this.style.display='none'">
                                </div>
                                <div class="card-header-titles">
                                    <div class="card-title-main" title="<?php echo esc_attr($s_name); ?>"><?php echo esc_html($s_name); ?></div>
                                    <div class="card-school-name">  No  </div>
                                </div>
                            </div>
                            <div class="card-acad-year-text">
                                <?php echo esc_html($acad_year); ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="card-photo-box">
                                <?php if ($has_photo): ?>
                                    <img src="<?php echo $photo_src; ?>" class="card-photo" alt="Student Photo">
                                <?php else: ?>
                                    <div class="card-photo-missing">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#64748b" style="margin-bottom: 2px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        <div class="card-photo-missing-text"> <br> </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-info">
                                <div class="card-stu-name" style="font-size: <?php echo $name_font_size; ?>;" title="<?php echo esc_attr($st->name); ?>"><?php echo esc_html($st->name); ?></div>
                                <div class="card-field">
                                    <span class="card-field-label">Training Group:</span>
                                    <span class="card-field-val" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <?php echo esc_html($clean_class ?: ''); ?>
                                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: <?php echo $stage_color; ?>; flex-shrink: 0;" title="<?php echo esc_attr($stage_label); ?>"></span>
                                    </span>
                                </div>
                                <div class="card-field">
                                    <span class="card-field-label">Training Group:</span>
                                    <span class="card-field-val"><?php echo esc_html($clean_section ?: ''); ?></span>
                                </div>
                                <div class="card-field">
                                    <span class="card-field-label">:</span>
                                    <span class="card-field-val" style="color: #881337;"><?php echo esc_html($serial); ?></span>
                                </div>
                            </div>
                            <div class="card-qr-stack">
                                <div class="card-qr-box" title="<?php echo esc_attr($barcode_identity); ?>"><?php echo $qr_svg; ?></div>
                                <span class="card-barcode-code-label"><?php echo esc_html($barcode_identity); ?></span>
                            </div>
                        </div>

                        <div class="card-footer">
                            <span class="card-footer-auth" style="display: inline-flex; align-items: center; gap: 3px; color: #166534; font-weight: 900;"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>  </span>
                            <span style="font-size: 5.5px; opacity: 0.85; font-family: monospace, sans-serif;">© 2026 Issued via eess.online - Verified Digital Pass</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'exit_permit_request') {
            global $wpdb;
            $req_id = intval($_GET['request_id'] ?? 0);
            if (!$req_id) {
                wp_die('    .');
            }

            $req = $wpdb->get_row($wpdb->prepare(
                "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.national_id, s.photo_url FROM {$wpdb->prefix}sm_exit_card_requests r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE r.id = %d",
                $req_id
            ));

            if (!$req) {
                wp_die('    .');
            }

            $ref_disp   = $req->reference_no ?: ('EXT-' . date('Y') . '-' . $req->id);
            $status_lbl = self::eess_get_exit_card_status_label($req->status);
            $stu_photo  = !empty($req->photo_url) ? $req->photo_url : "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTMiIHN0eWxlPSJiYWNrZ3JvdW5kOiNmMWY1Zjk7IGJvcmRlci1yYWRpdXM6NTAlOyI+PHBhdGggZD0iTTEyIDEyYzIuMjEgMCA4LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg==";
            $school_info = SM_Settings::get_school_info();
            $sys_logo   = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>     - <?php echo esc_html($ref_disp); ?></title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    @page { size: A4 portrait; margin: 12mm 15mm; }
                    body { font-family: 'Cairo', sans-serif; direction: rtl; color: #0f172a; background: #f8fafc; margin: 0; padding: 20px; font-size: 13px; line-height: 1.6; }
                    .a4-doc-container { width: 100%; max-width: 210mm; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.05); box-sizing: border-box; }
                    .doc-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
                    .doc-title-box h1 { margin: 0 0 4px 0; font-size: 18px; font-weight: 900; color: #0f172a; }
                    .doc-title-box div { font-size: 12px; color: #881337; font-weight: 800; }
                    .doc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
                    .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; }
                    .info-card h4 { margin: 0 0 10px 0; font-size: 13px; font-weight: 900; color: #0f172a; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
                    .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; }
                    .info-label { color: #64748b; font-weight: 700; }
                    .info-val { color: #0f172a; font-weight: 800; }
                    .dec-box { background: #fffbe3; border: 1px solid #fde047; border-radius: 10px; padding: 14px; color: #854d0e; font-size: 12px; margin-bottom: 20px; line-height: 1.7; }
                    .sig-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-top: 2px solid #e2e8f0; padding-top: 20px; margin-top: 20px; }
                    .sig-box { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; text-align: center; }
                    @media print {
                        body { background: white; padding: 0; }
                        .no-print { display: none !important; }
                        .a4-doc-container { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    }
                </style>
            </head>
            <body>

                <div class="no-print" style="text-align: center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="background: #0f172a; color: white; border: none; padding: 10px 28px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 14px; font-family: 'Cairo';">️ Print  A4 </button>
                </div>

                <div class="a4-doc-container">

                    <!-- Document Official Header -->
                    <div class="doc-header">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <img src="<?php echo esc_url($sys_logo); ?>" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0;" alt="School Logo">
                            <div class="doc-title-box">
                                <h1>   </h1>
                                <div>      No </div>
                            </div>
                        </div>
                        <div style="text-align: left; font-size: 11.5px; color: #64748b;">
                            <div><strong> :</strong> <span style="font-family: monospace; font-weight: 900; color: #881337; font-size: 13px;"><?php echo esc_html($ref_disp); ?></span></div>
                            <div><strong> Issue:</strong> <?php echo date_i18n('Y-m-d H:i'); ?></div>
                            <div><strong>Status :</strong> <span style="font-weight: 800; color: #16a34a;"><?php echo esc_html($status_lbl); ?></span></div>
                        </div>
                    </div>

                    <!-- Student & Request Details Grid -->
                    <div class="doc-grid">

                        <!-- Student Information Box -->
                        <div class="info-card">
                            <h4> No  </h4>
                            <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 12px;">
                                <img src="<?php echo esc_url($stu_photo); ?>" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #0f172a; flex-shrink: 0;" alt="Student">
                                <div style="flex: 1;">
                                    <div style="font-size: 15px; font-weight: 900; color: #0f172a; margin-bottom: 2px;"><?php echo esc_html($req->student_name); ?></div>
                                    <div style="font-size: 11.5px; color: #64748b; font-weight: 700;">Training Group: <?php echo esc_html($req->class_name); ?> (<?php echo esc_html($req->section); ?>)</div>
                                </div>
                            </div>
                            <div class="info-row"><span class="info-label"> No :</span> <span class="info-val" style="font-family: monospace;"><?php echo esc_html($req->student_code); ?></span></div>
                            <div class="info-row"><span class="info-label"> National ID:</span> <span class="info-val" style="font-family: monospace;"><?php echo esc_html($req->national_id ?: ' '); ?></span></div>
                        </div>

                        <!-- Request Details Box -->
                        <div class="info-card">
                            <h4>  No </h4>
                            <div class="info-row"><span class="info-label">  Mother :</span> <span class="info-val"><?php echo esc_html($req->parent_name ?: ' '); ?></span></div>
                            <div class="info-row"><span class="info-label">  :</span> <span class="info-val" style="font-family: monospace;"><?php echo esc_html($req->parent_phone ?: '---'); ?></span></div>
                            <div class="info-row"><span class="info-label">  No:</span> <span class="info-val"><?php echo esc_html($req->reason); ?></span></div>
                            <div class="info-row"><span class="info-label">  :</span> <span class="info-val" style="font-family: monospace;"><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($req->created_at))); ?></span></div>
                            <div class="info-row"><span class="info-label"> :</span> <span class="info-val"><?php echo esc_html($req->academic_year); ?></span></div>
                        </div>

                    </div>

                    <!-- Parent Declaration Box -->
                    <div class="dec-box">
                        <strong>   Mother   :</strong><br>
                            No  No   Issue    No  No.      No          Academy .     .
                    </div>

                    <!-- Signatures Area -->
                    <div class="sig-section">
                        <div class="sig-box">
                            <div style="font-weight: 800; font-size: 12px; color: #0f172a; margin-bottom: 6px;">   Mother :</div>
                            <?php if (!empty($req->signature_data)): ?>
                                <img src="<?php echo $req->signature_data; ?>" style="max-height: 70px; object-fit: contain;" alt="Parent Signature">
                            <?php else: ?>
                                <div style="color: #94a3b8; font-size: 11px; padding: 20px;"> </div>
                            <?php endif; ?>
                        </div>

                        <div class="sig-box" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 100px;">
                            <div style="font-weight: 800; font-size: 12px; color: #0f172a;">  Player Affairs  Academy :</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: auto;">  : ...................................</div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="border-top: 1px solid #cbd5e1; margin-top: 24px; padding-top: 10px; font-size: 10px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center;">
                        <span> Print: <?php echo date_i18n('Y-m-d H:i'); ?></span>
                        <span>    -     </span>
                    </div>

                </div>

            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'complaint_doc') {
            global $wpdb;
            $cmp_id = intval($_GET['complaint_id'] ?? 0);
            if (!$cmp_id) wp_die('   .');

            $cmp = $wpdb->get_row($wpdb->prepare(
                "SELECT c.*, s.name as student_name, s.student_code, s.class_name, s.section, s.national_id, s.guardian_phone
                 FROM {$wpdb->prefix}sm_complaints c
                 JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
                 WHERE c.id = %d",
                $cmp_id
            ));

            if (!$cmp) wp_die('    .');

            $school_info = SM_Settings::get_school_info();
            $system_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
            $ref_disp = $cmp->reference_no;
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>  -  No</title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: 'Cairo', sans-serif; background: #ffffff; color: #0f172a; padding: 30px; direction: rtl; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .doc-card { max-width: 800px; margin: 0 auto; border: 2px solid #0f172a; border-radius: 16px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
                    .hdr { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #881337; padding-bottom: 16px; margin-bottom: 24px; }
                    .logo { width: 64px; height: 64px; object-fit: contain; }
                    .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 16px; border-radius: 12px; font-size: 13px; line-height: 1.8; margin-bottom: 20px; }
                    .box-text { background: #ffffff; border: 1.5px solid #0f172a; border-radius: 12px; padding: 18px; font-size: 13.5px; line-height: 1.8; margin-bottom: 20px; white-space: pre-wrap; }
                    @media print { body { padding: 0; } .no-print { display: none !important; } .doc-card { border: 1px solid #0f172a; box-shadow: none; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="text-align: center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="background: #881337; color: white; border: none; padding: 10px 28px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 14px;">️ Print   (A4)</button>
                </div>
                <div class="doc-card">
                    <div class="hdr">
                        <div>
                            <h2 style="font-size: 20px; font-weight: 900; color: #0f172a;">   </h2>
                            <div style="font-size: 13px; color: #881337; font-weight: 800;">    </div>
                        </div>
                        <img src="<?php echo esc_url($system_logo); ?>" class="logo" alt="Logo">
                    </div>

                    <div class="grid-info">
                        <div><strong> :</strong> <span style="font-family: monospace; font-weight: 900; color: #881337;"><?php echo esc_html($ref_disp); ?></span></div>
                        <div><strong> :</strong> <?php echo date_i18n('Y-m-d H:i', strtotime($cmp->created_at)); ?></div>
                        <div><strong>Player Name:</strong> <strong><?php echo esc_html($cmp->student_name); ?></strong></div>
                        <div><strong> Training Group:</strong> <?php echo esc_html($cmp->student_code); ?> | <?php echo esc_html($cmp->class_name); ?> (<?php echo esc_html($cmp->section); ?>)</div>
                        <div><strong>National ID:</strong> <?php echo esc_html($cmp->national_id ?: ' '); ?></div>
                        <div><strong> :</strong> <?php echo esc_html($cmp->guardian_phone ?: ' '); ?></div>
                    </div>

                    <div style="font-weight: 900; font-size: 15px; color: #0f172a; margin-bottom: 8px;"> : <?php echo esc_html($cmp->title); ?></div>
                    <div class="box-text">
                        <strong>  :</strong><br>
                        <?php echo esc_html($cmp->details); ?>
                    </div>

                    <?php if (!empty($cmp->admin_notes)): ?>
                        <div style="background: #fffbe3; border: 1px solid #fde047; border-radius: 12px; padding: 14px; font-size: 12.5px; color: #854d0e; line-height: 1.6; margin-bottom: 20px;">
                            <strong> Notes :</strong><br>
                            <?php echo esc_html($cmp->admin_notes); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; font-size: 12px; font-weight: 800; color: #334155;">
                        <div>  : .......................</div>
                        <div style="text-align: center;">  Academy   <br><br>....................................................</div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'teacher_card' || $print_type === 'teacher_id_card') {
            $emp_ids = array();
            if (!empty($_GET['employee_id'])) {
                $emp_ids[] = intval($_GET['employee_id']);
            } elseif (!empty($_GET['employee_ids'])) {
                $emp_ids = array_map('intval', explode(',', $_GET['employee_ids']));
            }

            if (empty($emp_ids)) {
                wp_die('       Print.');
            }

            $school_info = SM_Settings::get_school_info();
            $sys_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');

            $role_map = array(
                'administrator' => ' ',
                'sm_system_admin' => '  ',
                'sm_principal' => ' Academy ',
                'sm_supervisor' => ' ',
                'sm_coordinator' => ' ',
                'sm_hod' => ' ',
                'sm_teacher' => ' ',
                'sm_discipline_supervisor' => '  / ',
                'sm_activities_supervisor' => ' Active',
                'sm_transportation_supervisor' => '  No',
                'sm_bus_supervisor' => ' ',
                'sm_clinic' => ' ',
                'sm_hr' => '  (HR)'
            );
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>      (Teacher ID)</title>
                <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                    * { box-sizing: border-box; }
                    body {
                        font-family: 'Cairo', sans-serif;
                        direction: rtl;
                        background: #e2e8f0;
                        margin: 0;
                        padding: 20px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 20px;
                    }
                    .cards-container {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 20px;
                        justify-content: center;
                    }
                    /* Vertical ID Card Layout (58mm x 92mm Standard Vertical Ratio) */
                    .vertical-id-card {
                        width: 58mm;
                        height: 92mm;
                        background: #ffffff;
                        border-radius: 12px;
                        border: 1px solid #cbd5e1;
                        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
                        position: relative;
                        overflow: hidden;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    .vcard-watermark {
                        position: absolute;
                        inset: 0;
                        width: 100%;
                        height: 100%;
                        opacity: 0.05;
                        pointer-events: none;
                        z-index: 0;
                    }
                    .vcard-top-bar {
                        background: #0f172a;
                        color: #ffffff;
                        padding: 7px 10px;
                        text-align: center;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        border-bottom: 2px solid #881337;
                        position: relative;
                        z-index: 2;
                    }
                    .vcard-logo {
                        width: 22px;
                        height: 22px;
                        object-fit: contain;
                        background: white;
                        border-radius: 4px;
                        padding: 2px;
                    }
                    .vcard-inst-title {
                        font-size: 8.5px;
                        font-weight: 900;
                        line-height: 1.2;
                        color: #ffffff;
                    }
                    .vcard-body {
                        padding: 6px 10px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        text-align: center;
                        flex: 1;
                        justify-content: space-between;
                        position: relative;
                        z-index: 2;
                    }
                    .vcard-org-banner {
                        font-size: 8px;
                        font-weight: 900;
                        color: #881337;
                        margin-bottom: 3px;
                        letter-spacing: -0.2px;
                    }
                    .vcard-photo-box {
                        width: 28mm;
                        height: 32mm;
                        border-radius: 8px;
                        overflow: hidden;
                        border: 2px solid #0f172a;
                        margin: 2px auto 4px auto;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
                        background: #f1f5f9;
                    }
                    .vcard-photo {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        display: block;
                    }
                    .vcard-emp-name {
                        font-size: 11px;
                        font-weight: 900;
                        color: #0f172a;
                        margin-bottom: 2px;
                        line-height: 1.25;
                    }
                    .vcard-role-badge {
                        display: inline-block;
                        padding: 1.5px 8px;
                        background: #f1f5f9;
                        color: #881337;
                        border: 1px solid #fecdd3;
                        border-radius: 9999px;
                        font-size: 8px;
                        font-weight: 800;
                        margin-bottom: 3px;
                    }
                    .vcard-meta-line {
                        font-size: 7.5px;
                        color: #475569;
                        font-weight: 700;
                        margin-bottom: 1.5px;
                        line-height: 1.25;
                    }
                    .vcard-barcode-box {
                        width: 44mm;
                        height: 11mm;
                        margin: 4px auto 2px auto;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                    }
                    .vcard-barcode-box svg {
                        width: 100%;
                        height: 100%;
                        display: block;
                    }
                    .vcard-footer {
                        background: #f8fafc;
                        border-top: 1px solid #e2e8f0;
                        padding: 3px 8px;
                        font-size: 6.5px;
                        color: #64748b;
                        font-weight: 800;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        position: relative;
                        z-index: 2;
                    }
                    @media print {
                        @page { size: A4 portrait; margin: 10mm; }
                        body { background: white; padding: 0; }
                        .no-print { display: none !important; }
                        .cards-container { gap: 6mm; justify-content: flex-start; }
                        .vertical-id-card { box-shadow: none; border: 1px solid #94a3b8; page-break-inside: avoid; }
                    }
                </style>
            </head>
            <body>

                <div class="no-print" style="text-align: center; margin-bottom: 10px;">
                    <button onclick="window.print()" style="background: #881337; color: white; border: none; padding: 10px 26px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 14px; font-family: 'Cairo';">️ Print    (Vertical Teacher ID)</button>
                </div>

                <div class="cards-container">
                    <?php foreach ($emp_ids as $eid):
                        $emp = get_userdata($eid);
                        if (!$emp) continue;

                        $emp_num   = get_user_meta($eid, 'eess_employee_number', true) ?: (get_user_meta($eid, 'sm_employee_id', true) ?: $emp->user_login);
                        $emp_role  = !empty($emp->roles) ? $emp->roles[0] : 'sm_teacher';
                        $emp_role_lbl = $role_map[$emp_role] ?? ' ';
                        $emp_spec  = get_user_meta($eid, 'sm_specialization', true) ?: (get_user_meta($eid, 'specialization', true) ?: '');
                        $emp_dept  = get_user_meta($eid, 'eess_department', true) ?: (get_user_meta($eid, 'department', true) ?: '');
                        if (empty($emp_dept) && !empty($emp_spec) && class_exists('EESS_Org_Helper')) {
                            $emp_dept = EESS_Org_Helper::get_department_name_for_subject($emp_spec);
                        }
                        $emp_school = get_user_meta($eid, 'eess_school_name', true) ?: '   ';

                        $custom_photo = get_user_meta($eid, 'sm_profile_photo_url', true) ?: get_user_meta($eid, 'eess_profile_photo', true);
                        $photo_src    = $custom_photo ?: get_avatar_url($eid, array('size' => 180));

                        // Re-use exact same barcode technology & SVG generator as Student ID
                        $barcode_svg = $this->eess_generate_qr_code_svg($emp_num);
                    ?>
                        <div class="vertical-id-card">
                            <!-- Subtle Official Watermark Background -->
                            <svg class="vcard-watermark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
                                <path fill="#0f172a" d="M0,192L48,176C96,160,192,144,288,160C384,176,480,224,576,218.7C672,213,768,155,864,138.7C960,122,1056,149,1152,165.3C1248,182,1344,187,1392,184L1440,180L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                            </svg>

                            <div class="vcard-top-bar">
                                <img src="<?php echo esc_url($sys_logo); ?>" class="vcard-logo" alt="Logo" onerror="this.style.display='none'">
                                <div class="vcard-inst-title"><?php echo esc_html($emp_school); ?></div>
                            </div>

                            <div class="vcard-body">
                                <!-- Organization Name Prominently Displayed Above Teacher's Name -->
                                <div class="vcard-org-banner">   </div>

                                <div class="vcard-photo-box">
                                    <img src="<?php echo esc_url($photo_src); ?>" class="vcard-photo" alt="Photo">
                                </div>

                                <div>
                                    <div class="vcard-emp-name"><?php echo esc_html($emp->display_name); ?></div>
                                    <div class="vcard-role-badge"><?php echo esc_html($emp_role_lbl); ?></div>
                                    <div class="vcard-meta-line"><strong>:</strong> <?php echo esc_html($emp_dept ?: ' '); ?></div>
                                    <?php if (!empty($emp_spec)): ?>
                                        <div class="vcard-meta-line"><strong>:</strong> <?php echo esc_html($emp_spec); ?></div>
                                    <?php endif; ?>
                                    <div class="vcard-meta-line"><strong> :</strong> <span style="font-family: monospace; font-weight: 900; color: #881337;"><?php echo esc_html($emp_num); ?></span></div>
                                </div>

                                <!-- Perfectly Centered & Positioned Scannable Barcode SVG -->
                                <div class="vcard-barcode-box" title="<?php echo esc_attr($emp_num); ?>">
                                    <?php echo $barcode_svg; ?>
                                </div>
                            </div>

                            <div class="vcard-footer">
                                <span>   </span>
                                <span style="font-family: monospace;">EESS VERIFIED STAFF</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </body>
            </html>
            <?php
            exit;
        } elseif ($print_type === 'exit_permit_request' || $print_type === 'exit_request_doc') {
            global $wpdb;
            $req_id = intval($_GET['request_id'] ?? 0);
            if (!$req_id) wp_die('    .');

            $req = $wpdb->get_row($wpdb->prepare(
                "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.national_id, s.photo_url FROM {$wpdb->prefix}sm_exit_card_requests r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE r.id = %d",
                $req_id
            ));

            if (!$req) wp_die('    .');

            $school_info = SM_Settings::get_school_info();
            $sys_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
            $org_title = '   ';

            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>     No  - <?php echo esc_html($req->reference_no); ?></title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800;900&display=swap');
                    body { font-family: 'Cairo', sans-serif; direction: rtl; margin: 0; padding: 25px; background: #fff; color: #0f172a; font-size: 13px; line-height: 1.6; }
                    .doc-box { max-width: 750px; margin: 0 auto; border: 2px solid #0f172a; border-radius: 16px; padding: 24px; box-sizing: border-box; }
                    .doc-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 20px; }
                    .doc-title { font-size: 19px; font-weight: 900; color: #0f172a; margin: 0 0 4px 0; }
                    .doc-sub { font-size: 13px; color: #881337; font-weight: 800; }
                    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; margin-bottom: 20px; }
                    .decl-box { background: #fffbe3; border: 1px solid #fde047; border-radius: 12px; padding: 16px; font-size: 12px; color: #854d0e; margin-bottom: 20px; line-height: 1.7; }
                    .sig-area { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; border-top: 1px solid #cbd5e1; padding-top: 16px; }
                    @media print { @page { size: A4 portrait; margin: 15mm; } body { padding: 0; } .no-print { display: none !important; } }
                </style>
            </head>
            <body>
                <div class="no-print" style="text-align: center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="padding: 10px 28px; background: #881337; color: white; border: none; border-radius: 8px; font-weight: 900; font-size: 14px; cursor: pointer;">️ Print    (A4 Document)</button>
                </div>

                <div class="doc-box">
                    <div class="doc-header">
                        <div>
                            <h1 class="doc-title"><?php echo esc_html($org_title); ?></h1>
                            <div class="doc-sub">     No </div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-top: 4px;"> : <span style="font-family: monospace; color: #0f172a; font-weight: 900;"><?php echo esc_html($req->reference_no); ?></span></div>
                        </div>
                        <div>
                            <img src="<?php echo esc_url($sys_logo); ?>" style="max-height: 70px; object-fit: contain;" alt="Logo">
                        </div>
                    </div>

                    <div class="info-grid">
                        <div><strong>Player Name :</strong> <?php echo esc_html($req->student_name); ?></div>
                        <div><strong> No:</strong> <span style="font-family: monospace; color: #881337; font-weight: 900;"><?php echo esc_html($req->student_code); ?></span></div>
                        <div><strong>Training Group Training Group:</strong> <?php echo esc_html($req->class_name); ?> (<?php echo esc_html($req->section); ?>)</div>
                        <div><strong> National ID:</strong> <?php echo esc_html($req->national_id ?: ' '); ?></div>
                        <div><strong>  Mother:</strong> <?php echo esc_html($req->parent_name); ?></div>
                        <div><strong> :</strong> <?php echo esc_html($req->parent_phone); ?></div>
                        <div><strong> :</strong> <?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($req->created_at))); ?></div>
                        <div><strong> :</strong> <?php echo esc_html($req->academic_year); ?></div>
                    </div>

                    <div class="decl-box">
                        <strong>   Mother   :</strong><br>
                            No/ / No   Issue    .      No/   Academy      No   Academy       .
                    </div>

                    <div class="sig-area">
                        <div>
                            <strong>    Mother:</strong><br>
                            <?php if (!empty($req->signature_data)): ?>
                                <img src="<?php echo esc_url($req->signature_data); ?>" style="max-height: 60px; object-fit: contain; border: 1px solid #cbd5e1; border-radius: 8px; padding: 4px; margin-top: 6px;" alt="Signature">
                            <?php else: ?>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">    </div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: left;">
                            <div><strong>  Player Affairs:</strong></div>
                            <div style="margin-top: 10px; font-weight: 900; color: #166534;">✓   No  </div>
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 4px;"> No: <?php echo date_i18n('Y-m-d'); ?></div>
                        </div>
                    </div>
                </div>

                <script>
                    if (window.location.search.indexOf('auto_print=1') !== -1) {
                        window.print();
                    }
                </script>
            </body>
            </html>
            <?php
            exit;
        } else {
            wp_die(' Print  .');
        }
    }

    /**
     * UNIFIED USER & EMPLOYEE MODAL AJAX HANDLERS
     */
    public function ajax_check_user_uniqueness() {
        check_ajax_referer('sm_user_action', 'sm_nonce');
        if (!current_user_can('manage_options') && !current_user_can('edit_users')) {
            wp_send_json_error(' No  No  .');
        }

        $field   = sanitize_text_field($_POST['field'] ?? '');
        $value   = sanitize_text_field($_POST['value'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);

        if (empty($field) || empty($value)) {
            wp_send_json_success(array('exists' => false));
        }

        if ($field === 'username') {
            $user = get_user_by('login', $value);
            if ($user && $user->ID !== $user_id) {
                wp_send_json_success(array('exists' => true, 'message' => 'Username    .'));
            }
        } elseif ($field === 'email') {
            $user = get_user_by('email', $value);
            if ($user && $user->ID !== $user_id) {
                wp_send_json_success(array('exists' => true, 'message' => 'Email Address   .'));
            }
        } elseif ($field === 'employee_id') {
            $existing = get_users(array(
                'meta_key'     => 'sm_employee_id',
                'meta_value'   => $value,
                'number'       => 1,
                'exclude'      => array($user_id),
                'fields'       => 'ID'
            ));
            if (!empty($existing)) {
                wp_send_json_success(array('exists' => true, 'message' => '  (ID)   .'));
            }
        }

        wp_send_json_success(array('exists' => false));
    }

    public function ajax_search_teachers_autocomplete() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (mb_strlen($query) < 3) {
            wp_send_json_success(array());
        }

        $all_teachers = get_users(array(
            'role'    => 'sm_teacher',
            'number'  => 30,
            'orderby' => 'display_name',
            'order'   => 'ASC'
        ));

        $results = array();
        $q_lower = mb_strtolower($query);

        foreach ($all_teachers as $u) {
            $emp_num = get_user_meta($u->ID, 'eess_employee_number', true) ?: (get_user_meta($u->ID, 'sm_employee_id', true) ?: $u->user_login);
            $name = $u->display_name;

            if (mb_strpos(mb_strtolower($name), $q_lower) !== false || mb_strpos(mb_strtolower($emp_num), $q_lower) !== false) {
                $results[] = array(
                    'id'              => $u->ID,
                    'name'            => $name,
                    'employee_number' => $emp_num
                );
            }
        }

        wp_send_json_success($results);
    }

    public function ajax_search_employees_for_eval() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $query = sanitize_text_field($_POST['query'] ?? '');
        $all_users = get_users(array(
            'number' => 100,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        $results = array();
        $q_lower = mb_strtolower($query);

        foreach ($all_users as $u) {
            $roles = (array) $u->roles;
            if (in_array('sm_student', $roles) || in_array('sm_parent', $roles)) continue;

            $emp_num = get_user_meta($u->ID, 'eess_employee_number', true) ?: (get_user_meta($u->ID, 'sm_employee_id', true) ?: $u->user_login);
            $name = $u->display_name;

            if ($query && mb_strpos(mb_strtolower($name), $q_lower) === false && mb_strpos(mb_strtolower($emp_num), $q_lower) === false) {
                continue;
            }

            $role_primary = reset($roles) ?: 'sm_teacher';
            $school_name = get_user_meta($u->ID, 'eess_school_name', true) ?: 'Academy  Home';
            $department = get_user_meta($u->ID, 'eess_department', true) ?: (get_user_meta($u->ID, 'department', true) ?: '   ');
            $subject = get_user_meta($u->ID, 'sm_specialization', true) ?: (get_user_meta($u->ID, 'specialization', true) ?: '');

            $assigned_grades_raw = get_user_meta($u->ID, 'sm_assigned_grades', true) ?: (get_user_meta($u->ID, 'eess_assigned_grades', true) ?: 'Training Group ');
            if (is_array($assigned_grades_raw)) $assigned_grades_raw = implode(', ', $assigned_grades_raw);
            $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);

            $results[] = array(
                'id' => $u->ID,
                'name' => $name,
                'employee_number' => $emp_num,
                'role_key' => $role_primary,
                'school_name' => $school_name,
                'department' => $department,
                'subject' => $subject,
                'assigned_grades' => $assigned_grades_clean,
                'photo_url' => get_avatar_url($u->ID, array('size' => 80))
            );
        }

        wp_send_json_success($results);
    }

    public function ajax_get_employee_system_performance_indicators() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) wp_send_json_error('Invalid ID');

        global $wpdb;

        // Lesson Prep Stats
        $prep_stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_preps,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_preps,
                SUM(CASE WHEN delay_seconds > 0 THEN 1 ELSE 0 END) as late_preps
            FROM {$wpdb->prefix}sm_lesson_preps
            WHERE teacher_id = %d
        ", $user_id));

        $total_preps = intval($prep_stats->total_preps ?? 0);
        $late_preps = intval($prep_stats->late_preps ?? 0);
        $ontime_preps = max(0, $total_preps - $late_preps);
        $prep_compliance = $total_preps > 0 ? round(($ontime_preps / $total_preps) * 100) : 100;

        // Term Plans Stats
        $plan_stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_plans,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_plans
            FROM {$wpdb->prefix}sm_term_plans
            WHERE teacher_id = %d
        ", $user_id));

        $total_plans = intval($plan_stats->total_plans ?? 0);
        $approved_plans = intval($plan_stats->approved_plans ?? 0);
        $plan_compliance = $total_plans > 0 ? round(($approved_plans / $total_plans) * 100) : 100;

        $overall_system_score = round(($prep_compliance + $plan_compliance) / 2, 1);

        wp_send_json_success(array(
            'prep_total' => $total_preps,
            'prep_ontime' => $ontime_preps,
            'prep_late' => $late_preps,
            'prep_compliance_pct' => $prep_compliance,
            'plan_total' => $total_plans,
            'plan_approved' => $approved_plans,
            'plan_compliance_pct' => $plan_compliance,
            'overall_system_score' => $overall_system_score
        ));
    }

    public function ajax_get_eval_template_for_role() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $role_key = sanitize_text_field($_POST['role_key'] ?? 'sm_teacher');
        global $wpdb;

        // Fetch custom template from DB if exists
        $tmpl = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}sm_eval_templates WHERE role_key = %s AND is_active = 1 ORDER BY id DESC LIMIT 1
        ", $role_key));

        if ($tmpl) {
            $questions = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}sm_eval_questions WHERE template_id = %d ORDER BY display_order ASC
            ", $tmpl->id));

            if (!empty($questions)) {
                $q_list = array();
                foreach ($questions as $q) {
                    $q_list[] = array(
                        'id' => $q->id,
                        'text' => $q->question_text,
                        'category' => $q->category_name ?: '  ',
                        'max_score' => intval($q->max_score ?: 10)
                    );
                }

                wp_send_json_success(array(
                    'template_id' => $tmpl->id,
                    'title' => $tmpl->title,
                    'questions' => $q_list
                ));
                return;
            }
        }

        // Default 10 Standard 0-10 Evaluation Questions
        $default_questions = array(
            array('id' => 1, 'text' => 'No  No    ', 'category' => ' No ', 'max_score' => 10),
            array('id' => 2, 'text' => 'No      Mother ', 'category' => ' No ', 'max_score' => 10),
            array('id' => 3, 'text' => 'No           No', 'category' => '  ', 'max_score' => 10),
            array('id' => 4, 'text' => 'No     No  ', 'category' => '  ', 'max_score' => 10),
            array('id' => 5, 'text' => '  Training Group   No  ', 'category' => '  ', 'max_score' => 10),
            array('id' => 6, 'text' => '     Mother   Academy     ', 'category' => '  ', 'max_score' => 10),
            array('id' => 7, 'text' => '   Active    No', 'category' => '  ', 'max_score' => 10),
            array('id' => 8, 'text' => 'No        ', 'category' => 'No ', 'max_score' => 10),
            array('id' => 9, 'text' => '       ', 'category' => 'No ', 'max_score' => 10),
            array('id' => 10, 'text' => '       Academy  No', 'category' => 'No ', 'max_score' => 10)
        );

        wp_send_json_success(array(
            'template_id' => 0,
            'title' => '     ',
            'questions' => $default_questions
        ));
    }

    public function ajax_save_evaluation_submission() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $evaluator_id  = get_current_user_id();
        $employee_id   = intval($_POST['employee_id'] ?? 0);
        $academic_year = sanitize_text_field($_POST['academic_year'] ?? '2025/2026');
        $comments      = sanitize_textarea_field($_POST['comments'] ?? '');

        // Scores array submitted per model: [1 => score_1, 2 => score_2, 3 => score_3, 4 => score_4]
        $scores_raw = $_POST['model_scores'] ?? array();
        $answers_raw = $_POST['answers'] ?? array();

        if ($employee_id <= 0 || empty($scores_raw)) {
            wp_send_json_error('      .');
        }

        $gen1 = floatval($scores_raw[1] ?? 0);
        $gen2 = floatval($scores_raw[2] ?? 0);
        $gen3 = floatval($scores_raw[3] ?? 0);
        $spec = isset($scores_raw[4]) ? floatval($scores_raw[4]) : null;

        $general_score = round(($gen1 + $gen2 + $gen3) / 3, 1);

        if ($spec !== null) {
            $final_score = round(($general_score * 0.60) + ($spec * 0.40), 1);
        } else {
            $final_score = $general_score;
        }

        // Automatic Performance Classification
        if ($final_score >= 90) {
            $classification = '';
        } elseif ($final_score >= 80) {
            $classification = ' ';
        } elseif ($final_score >= 70) {
            $classification = '';
        } elseif ($final_score >= 60) {
            $classification = '';
        } else {
            $classification = '  ';
        }

        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}sm_evaluations",
            array(
                'employee_id' => $employee_id,
                'evaluator_id' => $evaluator_id,
                'template_id' => 1,
                'academic_year' => $academic_year,
                'category_name' => '   ',
                'answers_json' => wp_json_encode($answers_raw),
                'subjective_score' => $general_score,
                'system_score' => $spec !== null ? $spec : 0,
                'total_score' => $final_score,
                'average_pct' => $final_score,
                'comments' => " : {$classification}. " . $comments,
                'status' => 'submitted',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );

        SM_Logger::log('save_evaluation', " Save    #{$employee_id}   {$final_score}% ({$classification})");

        wp_send_json_success(array(
            'eval_id' => $wpdb->insert_id,
            'general_score' => $general_score,
            'specialty_score' => $spec,
            'final_score' => $final_score,
            'classification' => $classification
        ));
    }

    public function ajax_get_evaluations_archive() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        global $wpdb;
        $acad_year = sanitize_text_field($_POST['academic_year'] ?? '');
        $query_text = sanitize_text_field($_POST['query'] ?? '');

        $sql = "SELECT e.*, u_emp.display_name as emp_name, u_eval.display_name as evaluator_name
                FROM {$wpdb->prefix}sm_evaluations e
                JOIN {$wpdb->prefix}users u_emp ON e.employee_id = u_emp.ID
                JOIN {$wpdb->prefix}users u_eval ON e.evaluator_id = u_eval.ID
                WHERE 1=1";
        $params = array();

        if ($acad_year) {
            $sql .= " AND e.academic_year = %s";
            $params[] = $acad_year;
        }

        if ($query_text) {
            $sql .= " AND (u_emp.display_name LIKE %s OR u_eval.display_name LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($query_text) . '%';
            $params[] = '%' . $wpdb->esc_like($query_text) . '%';
        }

        $sql .= " ORDER BY e.id DESC LIMIT 100";

        $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
        $results = array();

        foreach ($rows as $r) {
            $emp_num = get_user_meta($r->employee_id, 'eess_employee_number', true) ?: $r->employee_id;
            $results[] = array(
                'id' => $r->id,
                'employee_name' => $r->emp_name,
                'employee_number' => $emp_num,
                'evaluator_name' => $r->evaluator_name,
                'academic_year' => $r->academic_year,
                'category_name' => $r->category_name,
                'total_score' => $r->total_score,
                'average_pct' => $r->average_pct,
                'status' => $r->status,
                'comments' => $r->comments,
                'date' => date_i18n('Y-m-d H:i', strtotime($r->created_at))
            );
        }

        wp_send_json_success($results);
    }

    public function ajax_save_eval_template() {
        $user_roles = (array) wp_get_current_user()->roles;
        $is_auth = current_user_can('manage_options') || current_user_can('manage_hr') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_hod', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);
        if (!$is_auth) {
            wp_send_json_error('Unauthorized    .');
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $role_key = sanitize_text_field($_POST['role_key'] ?? 'sm_teacher');
        $questions_raw = $_POST['questions'] ?? array();

        if (empty($title) || empty($questions_raw)) {
            wp_send_json_error('    Add    .');
        }

        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}sm_eval_templates",
            array(
                'title' => $title,
                'role_key' => $role_key,
                'total_questions' => count($questions_raw),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );

        $tmpl_id = $wpdb->insert_id;

        foreach ($questions_raw as $idx => $q_text) {
            $q_clean = sanitize_text_field($q_text);
            if (!empty($q_clean)) {
                $wpdb->insert(
                    "{$wpdb->prefix}sm_eval_questions",
                    array(
                        'template_id' => $tmpl_id,
                        'question_text' => $q_clean,
                        'category_name' => '  ',
                        'display_order' => ($idx + 1),
                        'max_score' => 10,
                        'created_at' => current_time('mysql')
                    )
                );
            }
        }

        wp_send_json_success(array('tmpl_id' => $tmpl_id, 'message' => ' Save    '));
    }

    public function ajax_get_teacher_profile_summary() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized  .');
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            wp_send_json_error('   .');
        }

        clean_user_cache($user_id);
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('   .');
        }

        global $wpdb;

        $roles = (array) $user->roles;
        $primary_role = !empty($roles) ? reset($roles) : 'sm_teacher';
        $role_labels = array(
            'administrator' => '  ',
            'sm_system_admin' => '  ',
            'sm_principal' => ' Academy ',
            'sm_supervisor' => ' ',
            'sm_coordinator' => ' ',
            'sm_teacher' => '',
            'sm_student' => 'No',
            'sm_parent' => ' ',
            'sm_discipline_supervisor' => '  / ',
            'sm_activities_supervisor' => ' Active',
            'sm_transportation_supervisor' => '  No',
            'sm_bus_supervisor' => ' ',
            'sm_clinic' => ' ',
            'sm_hr' => '  (HR)'
        );

        $emp_number = get_user_meta($user_id, 'eess_employee_number', true) ?: (get_user_meta($user_id, 'sm_employee_id', true) ?: $user->user_login);
        $school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'Academy  Home';
        $department = get_user_meta($user_id, 'eess_department', true) ?: (get_user_meta($user_id, 'department', true) ?: get_user_meta($user_id, 'sm_department', true) ?: '   ');
        $subject = get_user_meta($user_id, 'sm_specialization', true) ?: (get_user_meta($user_id, 'specialization', true) ?: '  ');

        $assigned_grades_raw = get_user_meta($user_id, 'sm_assigned_grades', true) ?: (get_user_meta($user_id, 'eess_assigned_grades', true) ?: (get_user_meta($user_id, 'sm_grade_level', true) ?: 'Training Group '));
        if (is_array($assigned_grades_raw)) $assigned_grades_raw = implode(', ', $assigned_grades_raw);
        $assigned_grades_clean = str_replace(array('[', ']', '"', "'", '\\'), '', (string)$assigned_grades_raw);

        $assigned_sections_raw = get_user_meta($user_id, 'sm_assigned_sections', true) ?: (get_user_meta($user_id, 'eess_assigned_sections', true) ?: ' 1  2');
        if (is_array($assigned_sections_raw)) $assigned_sections_raw = implode(', ', $assigned_sections_raw);

        $phone = get_user_meta($user_id, 'phone_number', true) ?: (get_user_meta($user_id, 'sm_phone', true) ?: '---');
        $civil_id = get_user_meta($user_id, 'eess_civil_id', true) ?: (get_user_meta($user_id, 'civil_id', true) ?: '---');
        $nationality = get_user_meta($user_id, 'nationality', true) ?: (get_user_meta($user_id, 'sm_nationality', true) ?: 'United Arab Emirates');
        $gender = get_user_meta($user_id, 'gender', true) ?: (get_user_meta($user_id, 'eess_gender', true) ?: 'Male');
        $dob = get_user_meta($user_id, 'dob', true) ?: (get_user_meta($user_id, 'sm_dob', true) ?: '---');
        $emirate = get_user_meta($user_id, 'eess_emirate', true) ?: '';
        $appoint_year = get_user_meta($user_id, 'eess_appointment_year', true) ?: (get_user_meta($user_id, 'appointment_year', true) ?: '2022');
        $photo_url = get_avatar_url($user_id, array('size' => 120));

        // Fetch Term Plans Activity
        $term_plans = $wpdb->get_results($wpdb->prepare(
            "SELECT id, term_number, subject, grade, status, updated_at, created_at, plan_file_url FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d ORDER BY term_number ASC, updated_at DESC",
            $user_id
        ));
        $term_plans_count = count($term_plans);
        $term_plans_summary = array();
        $latest_term_plan_date = '---';

        foreach ($term_plans as $tp) {
            $dt = $tp->updated_at ?: $tp->created_at;
            if ($latest_term_plan_date === '---' && !empty($dt)) {
                $latest_term_plan_date = date_i18n('Y-m-d H:i', strtotime($dt));
            }
            $term_plans_summary[] = array(
                'id' => $tp->id,
                'term_number' => $tp->term_number,
                'subject' => $tp->subject,
                'grade' => $tp->grade,
                'status' => $tp->status,
                'date' => date_i18n('Y-m-d H:i', strtotime($dt)),
                'file_url' => $tp->plan_file_url ?: ''
            );
        }

        // Fetch Lesson Preparation Activity
        $lesson_preps = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, subject, grade_level, status, created_at, updated_at, file_url FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d ORDER BY created_at DESC LIMIT 10",
            $user_id
        ));
        $total_lesson_preps_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d",
            $user_id
        )) ?: 0;

        $lesson_preps_summary = array();
        $latest_lesson_prep_date = '---';
        $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
        $weeks_set = array();

        foreach ($lesson_preps as $lp) {
            $dt = $lp->updated_at ?: $lp->created_at;
            if ($latest_lesson_prep_date === '---' && !empty($dt)) {
                $latest_lesson_prep_date = date_i18n('Y-m-d H:i', strtotime($dt));
            }
            $p_ts = strtotime($dt);
            $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
            $weeks_set[$cw] = true;

            // Calculate lateness indicator (Friday 00:00 -> Monday 09:30 submission window)
            $is_late = false;
            if (!empty($lp->delay_seconds) && $lp->delay_seconds > 0) {
                $is_late = true;
            }

            $lesson_preps_summary[] = array(
                'id' => $lp->id,
                'title' => $lp->title ?: ' ',
                'subject' => $lp->subject,
                'grade' => $lp->grade_level,
                'status' => $lp->status,
                'is_late' => $is_late,
                'week' => $cw,
                'date' => date_i18n('Y-m-d H:i', strtotime($dt)),
                'file_url' => $lp->file_url ?: ''
            );
        }

        wp_send_json_success(array(
            'user_id' => $user_id,
            'full_name' => $user->display_name,
            'employee_number' => $emp_number,
            'user_email' => $user->user_email,
            'role_label' => $role_labels[$primary_role] ?? ' ',
            'school_name' => $school_name,
            'department' => $department,
            'subject' => $subject,
            'assigned_grades' => $assigned_grades_clean ?: '',
            'assigned_sections' => $assigned_sections_raw ?: '',
            'phone' => $phone,
            'civil_id' => $civil_id,
            'nationality' => $nationality,
            'gender' => $gender,
            'dob' => $dob,
            'emirate' => $emirate,
            'appointment_year' => $appoint_year,
            'photo_url' => $photo_url,
            'term_plans_count' => $term_plans_count,
            'latest_term_plan_date' => $latest_term_plan_date,
            'term_plans_summary' => $term_plans_summary,
            'lesson_preps_count' => $total_lesson_preps_count,
            'weeks_covered_count' => count($weeks_set),
            'latest_lesson_prep_date' => $latest_lesson_prep_date,
            'lesson_preps_summary' => $lesson_preps_summary
        ));
    }

    public function ajax_get_user_unified() {
        check_ajax_referer('sm_user_action', 'sm_nonce');
        if (!current_user_can('manage_options') && !current_user_can('edit_users')) {
            wp_send_json_error('Unauthorized  View   .');
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('  .');
        }

        $roles = $user->roles;
        $role  = !empty($roles) ? reset($roles) : 'teachers';

        $first_name   = get_user_meta($user_id, 'first_name', true) ?: $user->first_name;
        $last_name    = get_user_meta($user_id, 'last_name', true) ?: $user->last_name;
        $country_code = get_user_meta($user_id, 'eess_country_code', true) ?: '+971';
        $full_phone   = get_user_meta($user_id, 'sm_phone', true) ?: get_user_meta($user_id, 'phone_number', true);
        $phone_number = $full_phone;
        if (!empty($full_phone)) {
            foreach (array('+971', '+966', '+965', '+974', '+973', '+968', '+20') as $code) {
                if (strpos($full_phone, $code) === 0) {
                    $country_code = $code;
                    $phone_number = trim(substr($full_phone, strlen($code)));
                    break;
                }
            }
        }

        $employee_id  = get_user_meta($user_id, 'sm_employee_id', true) ?: get_user_meta($user_id, 'employee_id', true);
        if (empty($employee_id)) {
            $employee_id = $user->user_login;
        }
        $employee_id = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', $employee_id));
        $user_status   = get_user_meta($user_id, 'sm_user_status', true) ?: 'active';
        $civil_id      = get_user_meta($user_id, 'eess_civil_id', true);
        $dob           = get_user_meta($user_id, 'dob', true) ?: get_user_meta($user_id, 'sm_dob', true);
        $nationality   = get_user_meta($user_id, 'nationality', true) ?: get_user_meta($user_id, 'sm_nationality', true);
        $gender        = get_user_meta($user_id, 'gender', true) ?: get_user_meta($user_id, 'eess_gender', true);
        $address       = get_user_meta($user_id, 'address', true) ?: get_user_meta($user_id, 'eess_address', true);
        $building_info = get_user_meta($user_id, 'eess_building_info', true);
        $emirate       = get_user_meta($user_id, 'eess_emirate', true) ?: '';
        $access_scope  = get_user_meta($user_id, 'eess_access_scope', true) ?: 'school';

        $institution_id = get_user_meta($user_id, 'eess_institution_id', true) ?: get_user_meta($user_id, 'eess_school_id', true);
        $school_id      = get_user_meta($user_id, 'eess_school_id', true) ?: get_user_meta($user_id, 'sm_school_id', true);
        $school_name    = get_user_meta($user_id, 'eess_school_name', true);
        if (empty($school_name) && $institution_id > 0 && class_exists('EESS_Org_Helper')) {
            $inst_obj = EESS_Org_Helper::get_institution_by_id($institution_id);
            if ($inst_obj && !empty($inst_obj->name)) {
                $school_name = $inst_obj->name;
            }
        }
        if (empty($school_name)) $school_name = 'Academy  Home';
        $department     = get_user_meta($user_id, 'department', true) ?: get_user_meta($user_id, 'sm_department', true);
        $admin_section  = get_user_meta($user_id, 'eess_admin_section', true);
        $specialization = get_user_meta($user_id, 'specialization', true) ?: get_user_meta($user_id, 'sm_specialization', true);
        $assigned_sections = get_user_meta($user_id, 'eess_assigned_sections', true) ?: '';

        $assigned_grades = get_user_meta($user_id, 'eess_assigned_grades', true);
        if (!is_array($assigned_grades)) {
            $assigned_grades = json_decode($assigned_grades, true) ?: array();
        }

        $photo_url = get_user_meta($user_id, 'eess_profile_photo', true) ?: get_user_meta($user_id, 'sm_profile_photo_url', true);
        if (!$photo_url) {
            $photo_url = get_avatar_url($user_id);
        }

        wp_send_json_success(array(
            'id'                => $user_id,
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'user_login'        => $user->user_login,
            'user_email'        => $user->user_email,
            'country_code'      => $country_code,
            'phone_number'      => $phone_number,
            'employee_id'       => $employee_id,
            'user_status'       => $user_status,
            'civil_id'          => $civil_id,
            'dob'               => $dob,
            'nationality'       => $nationality,
            'gender'            => $gender,
            'address'           => $address,
            'building_info'     => $building_info,
            'emirate'           => $emirate,
            'role'              => $role,
            'access_scope'      => $access_scope,
            'institution_id'    => $institution_id,
            'school_id'         => $school_id,
            'school_name'       => $school_name,
            'department'        => $department,
            'admin_section'     => $admin_section,
            'specialization'    => $specialization,
            'assigned_grades'   => $assigned_grades,
            'assigned_sections' => $assigned_sections,
            'appointment_year'  => get_user_meta($user_id, 'eess_appointment_year', true) ?: (get_user_meta($user_id, 'sm_appointment_year', true) ?: date('Y')),
            'job_rank'          => get_user_meta($user_id, 'eess_job_rank', true) ?: 'teacher',
            'photo_url'         => $photo_url,
        ));
    }

    public function ajax_get_departments_by_school() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $institution_id = intval($_POST['institution_id'] ?? 0);
        if ($institution_id <= 0) {
            $school_id = intval($_POST['school_id'] ?? 0);
            if ($school_id > 0) {
                $institution_id = $school_id;
            }
        }

        $departments = array();
        if ($institution_id > 0 && class_exists('EESS_Org_Helper')) {
            $dept_objs = EESS_Org_Helper::get_departments_by_institution($institution_id);
            if (!empty($dept_objs)) {
                foreach ($dept_objs as $d) {
                    if (!empty($d->name)) {
                        $departments[] = array(
                            'id' => $d->id,
                            'name' => $d->name
                        );
                    }
                }
            }
        }

        if (empty($departments) && class_exists('SM_Settings')) {
            $fallback = SM_Settings::get_departments();
            foreach ($fallback as $fk => $fv) {
                $departments[] = array('id' => $fk, 'name' => $fv);
            }
        }

        wp_send_json_success($departments);
    }

    public function ajax_save_user_unified() {
        check_ajax_referer('sm_user_action', 'sm_nonce');

        $user_id     = intval($_POST['user_id'] ?? 0);
        $curr_user_id = get_current_user_id();
        $is_system_admin = current_user_can('manage_options') || current_user_can('edit_users');

        // Allow users to edit their own basic profile details OR admins to edit any account
        if (!$is_system_admin && ($user_id !== $curr_user_id || $user_id === 0)) {
            wp_send_json_error(' No  No Edit  Add .');
        }

        $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
        $raw_emp_id  = sanitize_text_field($_POST['employee_id'] ?? '');
        $country_code = sanitize_text_field($_POST['country_code'] ?? '+971');
        $raw_phone   = sanitize_text_field($_POST['phone_number'] ?? '');
        $email       = sanitize_email($_POST['user_email'] ?? '');
        $user_pass   = $_POST['user_pass'] ?? '';
        $user_status = sanitize_text_field($_POST['user_status'] ?? 'active');
        $civil_id    = sanitize_text_field($_POST['civil_id'] ?? '');

        // Strip prefixes from employee number (e.g., 'EMP-00025' -> '00025')
        $clean_emp_id = trim(preg_replace('/^(EMP|EMP-|_)+/i', '', trim($raw_emp_id)));
        if (empty($clean_emp_id)) {
            $clean_emp_id = trim($raw_emp_id);
        }

        // Rule: Username MUST EQUAL Employee Number
        $username    = $clean_emp_id;
        $employee_id = $clean_emp_id;

        // Combine country code and phone number
        $clean_phone_body = ltrim($raw_phone, '0');
        $full_phone = $country_code . ' ' . $clean_phone_body;

        $user_role      = sanitize_text_field($_POST['user_role'] ?? 'sm_teacher');
        if ($user_role === 'teachers') $user_role = 'sm_teacher';
        if ($user_role === 'school_manager') $user_role = 'sm_principal';
        if ($user_role === 'educational_supervisor') $user_role = 'sm_supervisor';
        if ($user_role === 'clinic') $user_role = 'sm_clinic';
        if ($user_role === 'accountant') $user_role = 'sm_accountant';

        // Security: Non-admin users editing their own profile MUST retain their existing role
        if (!$is_system_admin && $user_id > 0) {
            $existing_user = get_userdata($user_id);
            if ($existing_user && !empty($existing_user->roles)) {
                $user_role = $existing_user->roles[0];
            }
        }
        $access_scope   = sanitize_text_field($_POST['access_scope'] ?? 'school');
        $institution_id = intval($_POST['institution_id'] ?? 0);
        $school_id      = intval($_POST['school_id'] ?? 0);
        $department     = sanitize_text_field($_POST['department'] ?? '');
        $specialization = sanitize_text_field($_POST['specialization'] ?? '');
        if (!empty($specialization) && class_exists('EESS_Org_Helper')) {
            $auto_dept = EESS_Org_Helper::get_department_name_for_subject($specialization);
            if (!empty($auto_dept)) {
                $department = $auto_dept;
            }
        }
        $nationality    = sanitize_text_field($_POST['nationality'] ?? '');
        $dob            = sanitize_text_field($_POST['dob'] ?? '');
        $gender         = sanitize_text_field($_POST['gender'] ?? '');
        $address        = sanitize_text_field($_POST['address'] ?? '');
        $building_info  = sanitize_text_field($_POST['building_info'] ?? '');
        $emirate        = sanitize_text_field($_POST['emirate'] ?? '');
        $country_res    = sanitize_text_field($_POST['country_residence'] ?? 'United Arab Emirates');
        $admin_section  = sanitize_text_field($_POST['admin_section'] ?? '');
        $sections       = sanitize_text_field($_POST['assigned_sections'] ?? '');
        $grades         = isset($_POST['assigned_grades']) ? array_map('sanitize_text_field', (array)$_POST['assigned_grades']) : array();
        $appointment_year = intval($_POST['appointment_year'] ?? date('Y'));
        if ($appointment_year < 1970 || $appointment_year > intval(date('Y'))) {
            $appointment_year = intval(date('Y'));
        }
        $job_rank = sanitize_text_field($_POST['job_rank'] ?? 'teacher');

        if (empty($first_name) || empty($last_name) || empty($email) || empty($raw_phone)) {
            wp_send_json_error('     .');
        }

        if ($user_role !== 'administrator' && empty($employee_id)) {
            wp_send_json_error('  .');
        }

        $display_name = trim($first_name . ' ' . $last_name);

        if ($user_id > 0) {
            // Edit User
            $user_data = array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'user_email'   => $email,
            );

            if (!empty($user_pass)) {
                if (strlen($user_pass) < 8 || !preg_match('/[A-Z]/', $user_pass) || !preg_match('/[a-z]/', $user_pass) || !preg_match('/[0-9]/', $user_pass)) {
                    wp_send_json_error('Password    8         .');
                }
                $user_data['user_pass'] = $user_pass;
            }

            // Protect root admin account and sync username with employee number
            $target_user = get_userdata($user_id);
            if ($target_user && ($target_user->user_email === 'info@eess.online' || $target_user->user_login === '00000')) {
                $user_role = 'administrator';
            } else if ($target_user && $target_user->user_login !== $clean_emp_id) {
                global $wpdb;
                $wpdb->update($wpdb->users, array('user_login' => $clean_emp_id), array('ID' => $user_id));
            }

            $updated = wp_update_user($user_data);
            if (is_wp_error($updated)) {
                wp_send_json_error($updated->get_error_message());
            }
        } else {
            // New User
            if (empty($username) || empty($user_pass)) {
                wp_send_json_error('  Username Password  .');
            }
            if (strlen($user_pass) < 8 || !preg_match('/[A-Z]/', $user_pass) || !preg_match('/[a-z]/', $user_pass) || !preg_match('/[0-9]/', $user_pass)) {
                wp_send_json_error('Password    8         .');
            }
            if (username_exists($username)) {
                wp_send_json_error('Username    .');
            }
            if (email_exists($email)) {
                wp_send_json_error('Email Address    .');
            }

            $user_id = wp_create_user($username, $user_pass, $email);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
            }

            wp_update_user(array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
            ));
        }

        // Set Role (Role field modification strictly restricted to System Administrators)
        $current_user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin_editor = in_array('administrator', $current_user_roles) || in_array('sm_system_admin', $current_user_roles) || current_user_can('manage_options');
        $u = new WP_User($user_id);
        if ($u && ($u->user_email === 'info@eess.online' || $u->user_login === '00000')) {
            $user_role = 'administrator';
        }
        if ($is_sys_admin_editor && !empty($user_role)) {
            $u->set_role($user_role);
        }

        // Derive school name from institution lookup for system-wide synchronization
        $school_name = 'Academy  Home';
        if ($institution_id > 0 && class_exists('EESS_Org_Helper')) {
            $inst_obj = EESS_Org_Helper::get_institution_by_id($institution_id);
            if ($inst_obj && !empty($inst_obj->name)) {
                $school_name = $inst_obj->name;
            }
        }

        // Synchronize Metadata Across WP Metas and EESS Tables
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'eess_country_code', $country_code);
        update_user_meta($user_id, 'sm_phone', $full_phone);
        update_user_meta($user_id, 'phone_number', $full_phone);
        update_user_meta($user_id, 'sm_employee_id', $clean_emp_id);
        update_user_meta($user_id, 'employee_id', $clean_emp_id);
        update_user_meta($user_id, 'eess_employee_number', $clean_emp_id);
        update_user_meta($user_id, 'sm_user_status', $user_status);
        update_user_meta($user_id, 'eess_civil_id', $civil_id);
        update_user_meta($user_id, 'eess_emirate', $emirate);
        update_user_meta($user_id, 'gender', $gender);
        update_user_meta($user_id, 'eess_gender', $gender);
        update_user_meta($user_id, 'address', $address);
        update_user_meta($user_id, 'eess_address', $address);
        update_user_meta($user_id, 'eess_building_info', $building_info);
        if (!empty($dob)) {
            update_user_meta($user_id, 'dob', $dob);
            update_user_meta($user_id, 'sm_dob', $dob);
        }
        if (!empty($nationality)) {
            update_user_meta($user_id, 'nationality', $nationality);
            update_user_meta($user_id, 'sm_nationality', $nationality);
        }
        update_user_meta($user_id, 'eess_access_scope', $access_scope);
        update_user_meta($user_id, 'eess_institution_id', $institution_id);
        update_user_meta($user_id, 'eess_school_id', $school_id);
        update_user_meta($user_id, 'sm_school_id', $school_id);
        update_user_meta($user_id, 'eess_school_name', $school_name);
        update_user_meta($user_id, 'sm_school_name', $school_name);
        update_user_meta($user_id, 'eess_department', $department);
        update_user_meta($user_id, 'department', $department);
        update_user_meta($user_id, 'sm_department', $department);
        update_user_meta($user_id, 'eess_admin_section', $admin_section);
        update_user_meta($user_id, 'specialization', $specialization);
        update_user_meta($user_id, 'sm_specialization', $specialization);
        update_user_meta($user_id, 'eess_assigned_grades', json_encode($grades, JSON_UNESCAPED_UNICODE));
        update_user_meta($user_id, 'eess_assigned_sections', $sections);
        update_user_meta($user_id, 'eess_appointment_year', $appointment_year);
        update_user_meta($user_id, 'sm_appointment_year', $appointment_year);
        update_user_meta($user_id, 'eess_job_rank', $job_rank);

        // Handle Profile Photo Upload if present
        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'sm_profile_photo_id', $attachment_id);
                update_user_meta($user_id, 'sm_profile_photo_url', $photo_url);
            }
        }

        SM_Logger::log('Save   ', " Save     $display_name (ID: $user_id)");

        wp_send_json_success(array(
            'message' => ' Save         .',
            'user_id' => $user_id
        ));
    }

    public function ajax_quick_approve_prep() {
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error(' No  No   .');
        }

        $prep_id = intval($_POST['prep_id'] ?? 0);
        if ($prep_id <= 0) {
            wp_send_json_error('   .');
        }

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'status' => 'approved',
                'reviewed_by' => $user_id,
                'reviewed_at' => current_time('mysql'),
                'review_notes' => ' No   / '
            ),
            array('id' => $prep_id)
        );

        if ($updated !== false) {
            $prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            SM_Logger::log('  ', "  : " . ($prep->title ?? '') . " (ID: $prep_id)   ID: $user_id");
            wp_send_json_success(array('message' => '    .', 'prep_id' => $prep_id));
        } else {
            wp_send_json_error('    No .');
        }
    }

    public function ajax_update_prep_status_and_time() {
        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error(' No  No  Edit   .');
        }

        $prep_id = intval($_POST['prep_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'submitted');
        $raw_dt = sanitize_text_field($_POST['submission_time'] ?? '');

        if ($prep_id <= 0) {
            wp_send_json_error('    .');
        }

        global $wpdb;
        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );

        if (!empty($raw_dt)) {
            $formatted_dt = date('Y-m-d H:i:s', strtotime($raw_dt));
            $update_data['submission_time'] = $formatted_dt;
            $update_data['created_at'] = $formatted_dt;
        }

        if ($status !== 'late') {
            $update_data['delay_seconds'] = 0;
        } else {
            $subj = $wpdb->get_var($wpdb->prepare("SELECT subject FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            $sub_ts = !empty($raw_dt) ? strtotime($raw_dt) : time();
            $calc = EESS_Org_Helper::calculate_lesson_prep_status($subj, $sub_ts);
            $update_data['delay_seconds'] = $calc['delay_seconds'];
        }

        $updated = $wpdb->update("{$wpdb->prefix}sm_lesson_preps", $update_data, array('id' => $prep_id));

        if ($updated !== false) {
            SM_Logger::log('Update   ', " Edit  ID: $prep_id  Status ($status) Edit    ID: $user_id");
            wp_send_json_success(array('message' => ' Update     .'));
        } else {
            wp_send_json_error(' Update    .');
        }
    }

    public function ajax_reject_lesson_prep() {
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error(' No  No Reject  Notes      .');
        }

        $prep_id = intval($_POST['prep_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if ($prep_id <= 0 || empty($notes)) {
            wp_send_json_error('    Notes  .');
        }

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'status' => 'revision_required',
                'reviewed_by' => $user_id,
                'reviewed_at' => current_time('mysql'),
                'review_notes' => $notes
            ),
            array('id' => $prep_id)
        );

        if ($updated !== false) {
            // Save comment to sm_lesson_comments
            $wpdb->insert(
                "{$wpdb->prefix}sm_lesson_comments",
                array(
                    'prep_id' => $prep_id,
                    'user_id' => $user_id,
                    'comment_text' => $notes,
                    'created_at' => current_time('mysql')
                )
            );

            $prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            SM_Logger::log('Reject    Edit', "   (ID: $prep_id) Edit   ID: $user_id Notes: $notes");
            wp_send_json_success(array('message' => '  Notes Update   Edit .', 'prep_id' => $prep_id));
        } else {
            wp_send_json_error(' Save  Reject   .');
        }
    }

    public function ajax_bulk_lesson_action() {
        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'eess_lesson_prep_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        $user_id = get_current_user_id();
        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $prep_ids = !empty($_POST['prep_ids']) ? array_map('intval', (array)$_POST['prep_ids']) : array();

        if (empty($prep_ids) || empty($bulk_action)) {
            wp_send_json_error('     .');
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($prep_ids), '%d'));

        if ($bulk_action === 'approve') {
            if (!$can_review) {
                wp_send_json_error(' No  No No .');
            }
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sm_lesson_preps SET status = 'approved', reviewed_by = %d, reviewed_at = %s WHERE id IN ($placeholders)", array_merge(array($user_id, current_time('mysql')), $prep_ids)));
            wp_send_json_success(array('message' => '    .'));
        } elseif ($bulk_action === 'delete') {
            if (!$can_review) {
                // If not reviewer/admin, ensure all requested prep IDs belong to current teacher
                $owner_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE id IN ($placeholders) AND teacher_id = %d", array_merge($prep_ids, array($user_id))));
                if ($owner_count < count($prep_ids)) {
                    wp_send_json_error(' No  No Delete     .');
                }
            }
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sm_lesson_preps WHERE id IN ($placeholders)", $prep_ids));
            wp_send_json_success(array('message' => ' Delete   .'));
        }
    }

    public function ajax_save_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) wp_send_json_error('Security check failed');

        $user_id = get_current_user_id();
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $planning_method = sanitize_text_field($_POST['planning_method'] ?? 'create');

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device_cat = 'computer';
        if (preg_match('/(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/i', $user_agent)) {
            $device_cat = 'tablet';
        } elseif (preg_match('/(mobi|ipod|phone|blackberry|opera mini|fennec|minimo|symbian|psp|nokia|samsung|android)/i', $user_agent)) {
            $device_cat = 'mobile';
        }

        if (strpos($planning_method, 'mobile') === false && strpos($planning_method, 'tablet') === false && strpos($planning_method, 'computer') === false) {
            $planning_method .= '_' . $device_cat;
        }
        $academic_year = sanitize_text_field($_POST['academic_year'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');

        $plan_grades = isset($_POST['assigned_plan_grades']) ? array_map('sanitize_text_field', (array)$_POST['assigned_plan_grades']) : array();
        $grade = !empty($plan_grades) ? implode(' ', $plan_grades) : sanitize_text_field($_POST['grade'] ?? '');

        $weekly_lessons = max(1, intval($_POST['weekly_lessons'] ?? 1));
        $num_terms = max(2, min(3, intval($_POST['num_terms'] ?? 3)));
        $term_number = max(1, min(3, intval($_POST['term_number'] ?? 1)));
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'draft');

        // Handle uploaded document file if present
        $uploaded_file_url = '';
        if (!empty($_FILES['plan_document_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_info = wp_check_filetype_and_ext($_FILES['plan_document_file']['tmp_name'], $_FILES['plan_document_file']['name']);

            // Non-administrators are strictly limited to PDF format only
            $is_admin_user = current_user_can('administrator') || in_array('sm_system_admin', (array) wp_get_current_user()->roles);
            if ($is_admin_user) {
                $allowed_mimes = array('pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            } else {
                $allowed_mimes = array('pdf' => 'application/pdf');
            }

            if (!in_array($file_info['type'], $allowed_mimes) && !array_key_exists($file_info['ext'], $allowed_mimes)) {
                if ($is_admin_user) {
                    wp_send_json_error('      .    PDF  Word .');
                } else {
                    wp_send_json_error('        PDF    Users.');
                }
            }

            $attachment_id = media_handle_upload('plan_document_file', 0);
            if (!is_wp_error($attachment_id)) {
                $uploaded_file_url = wp_get_attachment_url($attachment_id);
            } else {
                wp_send_json_error('     : ' . $attachment_id->get_error_message());
            }
        }

        if (!in_array($status, array('draft', 'submitted', 'approved', 'returned'))) {
            $status = 'draft';
        }

        // Calculate weeks automatically
        $total_weeks = 0;
        if (!empty($start_date) && !empty($end_date)) {
            $t_start = strtotime($start_date);
            $t_end = strtotime($end_date);
            if ($t_end >= $t_start) {
                $days = floor(($t_end - $t_start) / (60 * 60 * 24));
                $total_weeks = max(1, ceil($days / 7));
            }
        }

        // Process weekly titles and summaries JSON
        $raw_weeks = $_POST['weeks'] ?? array();
        $weeks_data = array();
        if (is_array($raw_weeks)) {
            foreach ($raw_weeks as $w_num => $w_val) {
                $w_i = intval($w_num);
                $title = sanitize_text_field($w_val['title'] ?? '');
                $summary = sanitize_textarea_field($w_val['summary'] ?? '');
                $completed = (!empty($title) || !empty($summary)) ? 1 : 0;
                $weeks_data[$w_i] = array(
                    'title' => $title,
                    'summary' => $summary,
                    'completed' => $completed
                );
            }
        }

        // Compute completion percentage
        $completed_count = 0;
        if ($total_weeks > 0) {
            for ($i = 1; $i <= $total_weeks; $i++) {
                if (!empty($weeks_data[$i]['completed'])) {
                    $completed_count++;
                }
            }
            $completion_pct = round(($completed_count / $total_weeks) * 100);
        } else {
            $completion_pct = 0;
        }

        global $wpdb;

        // Ensure columns exist on database table dynamically using safe INFORMATION_SCHEMA checks
        $tbl_plans = "{$wpdb->prefix}sm_term_plans";
        $check_pf = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tbl_plans' AND COLUMN_NAME = 'plan_file_url'");
        if (empty($check_pf)) {
            $wpdb->query("ALTER TABLE {$tbl_plans} ADD COLUMN plan_file_url text DEFAULT NULL");
        }
        $check_pm = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tbl_plans' AND COLUMN_NAME = 'planning_method'");
        if (empty($check_pm)) {
            $wpdb->query("ALTER TABLE {$tbl_plans} ADD COLUMN planning_method varchar(50) DEFAULT 'create' NOT NULL");
        }

        $data_fields = array(
            'teacher_id' => $user_id,
            'academic_year' => $academic_year,
            'subject' => $subject,
            'grade' => $grade,
            'weekly_lessons' => $weekly_lessons,
            'num_terms' => $num_terms,
            'term_number' => $term_number,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_weeks' => $total_weeks,
            'weeks_data' => wp_json_encode($weeks_data),
            'planning_method' => $planning_method,
            'completion_pct' => $completion_pct,
            'status' => $status
        );

        if (!empty($uploaded_file_url)) {
            $data_fields['plan_file_url'] = $uploaded_file_url;
        }

        if ($plan_id > 0) {
            // Ensure owner or admin
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $plan_id));
            if (!$existing) wp_send_json_error('  .');
            if ($existing->teacher_id != $user_id && !current_user_can('manage_options')) {
                wp_send_json_error(' No  No Edit  .');
            }

            $wpdb->update("{$wpdb->prefix}sm_term_plans", $data_fields, array('id' => $plan_id));
            SM_Logger::log('Save  Training Group', " Save  (ID: $plan_id) : $status  $completion_pct%");
            wp_send_json_success(array('plan_id' => $plan_id, 'status' => $status, 'completion_pct' => $completion_pct, 'total_weeks' => $total_weeks));
        } else {
            $inserted = $wpdb->insert("{$wpdb->prefix}sm_term_plans", $data_fields);
            if ($inserted) {
                $new_id = $wpdb->insert_id;
                SM_Logger::log('  ', "     (ID: $new_id)");
                wp_send_json_success(array(
                    'plan_id' => $new_id,
                    'status' => $status,
                    'completion_pct' => $completion_pct,
                    'total_weeks' => $total_weeks,
                    'message' => '  Send  Training Group     .'
                ));
            } else {
                wp_send_json_error(' Save    .');
            }
        }
    }

    public function ajax_review_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) wp_send_json_error('Security check failed');

        $roles = (array) wp_get_current_user()->roles;
        $can_review = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles) || current_user_can('manage_options');

        if (!$can_review) {
            wp_send_json_error(' No  No  .');
        }

        $plan_id = intval($_POST['plan_id'] ?? 0);
        $review_status = sanitize_text_field($_POST['review_status'] ?? '');
        $review_notes = sanitize_textarea_field($_POST['review_notes'] ?? '');

        if (!in_array($review_status, array('approved', 'returned', 'rejected'))) {
            wp_send_json_error('   .');
        }

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_term_plans",
            array(
                'status' => $review_status,
                'review_notes' => $review_notes,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $plan_id)
        );

        if ($updated !== false) {
            SM_Logger::log('  ', "   ID: $plan_id  No : $review_status");
            wp_send_json_success(array('plan_id' => $plan_id, 'status' => $review_status));
        } else {
            wp_send_json_error(' Update  .');
        }
    }

    public function ajax_delete_term_plan() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) wp_send_json_error('Security check failed');

        $plan_id = intval($_POST['plan_id'] ?? 0);
        if (!$plan_id) {
            wp_send_json_error('   .');
        }

        global $wpdb;
        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $plan_id));
        if (!$plan) {
            wp_send_json_error('     Delete .');
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $roles = (array) $current_user->roles;

        $can_delete = in_array('administrator', $roles) ||
                      in_array('sm_system_admin', $roles) ||
                      in_array('sm_principal', $roles) ||
                      in_array('sm_supervisor', $roles) ||
                      in_array('sm_coordinator', $roles) ||
                      in_array('sm_hod', $roles) ||
                      in_array('sm_activities_supervisor', $roles) ||
                      current_user_can('manage_options') ||
                      (intval($plan->teacher_id) === $user_id);

        if (!$can_delete) {
            wp_send_json_error(' No  No  Delete  .');
        }

        $deleted = $wpdb->delete("{$wpdb->prefix}sm_term_plans", array('id' => $plan_id));

        if ($deleted) {
            SM_Logger::log('Delete  ', " Delete  (ID: $plan_id)   {$current_user->display_name}");
            wp_send_json_success(array('plan_id' => $plan_id, 'message' => ' Delete  .'));
        } else {
            wp_send_json_error(' Delete    .');
        }
    }

    public function ajax_verify_employee_id() {
        $emp_id   = isset($_POST['emp_id']) ? sanitize_text_field($_POST['emp_id']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($emp_id) || empty($password)) {
            wp_send_json_error('   /Mobile Number Password  .');
        }

        $teacher = SM_DB::get_teacher_by_employee_id_or_phone($emp_id);
        if (!$teacher) {
            wp_send_json_error('       .       Phone Number.');
        }

        if (!wp_check_password($password, $teacher->user_pass, $teacher->ID)) {
            wp_send_json_error('Password   .   .');
        }

        wp_set_current_user($teacher->ID);
        wp_set_auth_cookie($teacher->ID, true);

        $subject = get_user_meta($teacher->ID, 'sm_specialization', true) ?: (get_user_meta($teacher->ID, 'specialization', true) ?: (get_user_meta($teacher->ID, 'subject', true) ?: ''));
        $school  = get_user_meta($teacher->ID, 'eess_school_name', true) ?: (get_user_meta($teacher->ID, 'sm_school_name', true) ?: 'Academy  Home');
        $dept    = get_user_meta($teacher->ID, 'eess_department', true) ?: ' Sport Activity';
        $classes = get_user_meta($teacher->ID, 'sm_assigned_classes', true) ?: array();
        $grade   = get_user_meta($teacher->ID, 'sm_grade_level', true) ?: (get_user_meta($teacher->ID, 'grade', true) ?: '');
        $section = get_user_meta($teacher->ID, 'sm_class_section', true) ?: (get_user_meta($teacher->ID, 'section', true) ?: '');
        $emp_code= get_user_meta($teacher->ID, 'eess_employee_number', true) ?: (get_user_meta($teacher->ID, 'sm_employee_id', true) ?: $emp_id);

        $fresh_nonce = wp_create_nonce('sm_mobile_prep_nonce');

        wp_send_json_success(array(
            'teacher_id'   => $teacher->ID,
            'emp_id'       => $emp_code,
            'teacher_name' => $teacher->display_name,
            'school'       => $school,
            'department'   => $dept,
            'subject'      => $subject,
            'grade'        => $grade,
            'section'      => $section,
            'classes'      => $classes,
            'fresh_nonce'  => $fresh_nonce
        ));
    }

    public function ajax_submit_mobile_lesson() {
        if (!wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_mobile_prep_nonce') && !wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_mobile_prep_action') && !wp_verify_nonce($_POST['sm_nonce'] ?? '', 'sm_term_plan_action')) {
            wp_send_json_error('  Mother .');
        }

        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            wp_send_json_error('  No .  Login   .');
        }

        $teacher = get_userdata($current_user_id);
        if (!$teacher) {
            wp_send_json_error('  Mother  .');
        }

        $title         = sanitize_text_field($_POST['title'] ?? ($_POST['lesson_title'] ?? ''));
        $subject       = sanitize_text_field($_POST['subject'] ?? '');
        $grade_level   = sanitize_text_field($_POST['grade_level'] ?? '');
        $class_section = sanitize_text_field($_POST['class_section'] ?? '');
        $lesson_date   = sanitize_text_field($_POST['lesson_date'] ?? current_time('Y-m-d'));

        if (empty($title)) {
            wp_send_json_error('      .');
        }

        $file_url = '';
        if (!empty($_FILES['lesson_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_info = wp_check_filetype_and_ext($_FILES['lesson_file']['tmp_name'], $_FILES['lesson_file']['name']);
            $allowed_mimes = array('pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

            if (!in_array($file_info['type'], $allowed_mimes) && !array_key_exists($file_info['ext'], $allowed_mimes)) {
                wp_send_json_error('     .    PDF  Word .');
            }

            $attachment_id = media_handle_upload('lesson_file', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            } else {
                wp_send_json_error('    : ' . $attachment_id->get_error_message());
            }
        }

        $lesson_data = array(
            'objectives'      => sanitize_textarea_field($_POST['objectives'] ?? ''),
            'warmup'          => sanitize_textarea_field($_POST['warmup'] ?? ''),
            'physical_prep'   => sanitize_textarea_field($_POST['physical_prep'] ?? ''),
            'skill_prep'      => sanitize_textarea_field($_POST['skill_prep'] ?? ''),
            'conclusion'      => sanitize_textarea_field($_POST['conclusion'] ?? ''),
            'national_agenda' => sanitize_textarea_field($_POST['national_agenda'] ?? ''),
            'cross_subject'   => sanitize_textarea_field($_POST['cross_subject'] ?? ''),
            'activities'      => sanitize_textarea_field($_POST['activities'] ?? ''),
            'evaluation'      => sanitize_textarea_field($_POST['evaluation'] ?? ''),
            'homework'        => sanitize_textarea_field($_POST['homework'] ?? ''),
            'notes'           => sanitize_textarea_field($_POST['notes'] ?? ''),
            'file_url'        => $file_url,
            'submitted_via'   => 'mobile_app'
        );

        $supervisors = get_users(array('role__in' => array('sm_supervisor', 'sm_principal', 'administrator')));
        $supervisor_id = !empty($supervisors) ? $supervisors[0]->ID : 1;

        // Calculate Late Submission Status & Delay Seconds using authoritative EESS_Org_Helper deadline calculator
        $calc_res      = EESS_Org_Helper::calculate_lesson_prep_status($subject);
        $status        = $calc_res['status'];
        $delay_seconds = $calc_res['delay_seconds'];
        $sub_time      = $calc_res['submission_time'];

        global $wpdb;
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'teacher_id'      => $teacher->ID,
                'supervisor_id'   => $supervisor_id,
                'title'           => $title,
                'subject'         => $subject,
                'grade_level'     => $grade_level,
                'class_section'   => $class_section,
                'lesson_date'     => $lesson_date,
                'submission_time' => $sub_time,
                'status'          => $status,
                'delay_seconds'   => $delay_seconds,
                'lesson_data'     => json_encode($lesson_data),
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            )
        );

        if ($inserted) {
            $prep_id = $wpdb->insert_id;
            SM_Logger::log('   ', " Add   (ID: $prep_id)   : {$teacher->display_name}");
            wp_send_json_success(array('prep_id' => $prep_id, 'message' => ' Save Send     .'));
        } else {
            wp_send_json_error('An error occurred  Save   .');
        }
    }

    public function ajax_create_system_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(' Unauthorized   No .');
        }

        $title            = sanitize_text_field($_POST['title'] ?? '');
        $details          = sanitize_textarea_field($_POST['details'] ?? '');
        $type             = sanitize_text_field($_POST['type'] ?? 'info');
        $display_duration = intval($_POST['display_duration'] ?? 10);
        $display_freq     = intval($_POST['display_frequency'] ?? 1);
        $roles            = isset($_POST['target_roles']) ? array_map('sanitize_text_field', (array)$_POST['target_roles']) : array();

        if (empty($title) || empty($details) || empty($roles)) {
            wp_send_json_error('       .');
        }

        if (mb_strlen($details) > 500) {
            wp_send_json_error('   No  500 .');
        }

        global $wpdb;
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}sm_system_announcements",
            array(
                'title'             => $title,
                'details'           => $details,
                'target_roles'      => json_encode($roles),
                'type'              => $type,
                'display_duration'  => $display_duration,
                'display_frequency' => $display_freq,
                'status'            => 'active',
                'created_by'        => get_current_user_id(),
                'created_at'        => current_time('mysql')
            )
        );

        if ($inserted) {
            $anc_id = $wpdb->insert_id;
            SM_Logger::log('  ', "     (ID: $anc_id) : $title");
            wp_send_json_success(array('announcement_id' => $anc_id, 'message' => '    .'));
        } else {
            wp_send_json_error('An error occurred   .');
        }
    }

    private function eess_ensure_first_login_welcome($user_id) {
        if (!$user_id) return;

        global $wpdb;
        // Ensure master welcome template row exists in sm_system_announcements with placeholder {user_name}
        $welcome_template = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sm_system_announcements WHERE is_welcome = 1 LIMIT 1");
        if (!$welcome_template) {
            $wpdb->insert(
                "{$wpdb->prefix}sm_system_announcements",
                array(
                    'title'             => 'Welcome {user_name}',
                    'details'           => 'Welcome  .                 .',
                    'target_roles'      => json_encode(array('all_users')),
                    'type'              => 'success',
                    'display_duration'  => 12,
                    'display_frequency' => 1,
                    'is_welcome'        => 1,
                    'status'            => 'active',
                    'created_by'        => 1,
                    'created_at'        => current_time('mysql')
                )
            );
        }
    }

    public function ajax_get_pending_announcements() {
        if (!is_user_logged_in()) {
            wp_send_json_success(array());
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_name = $user->display_name ?: $user->first_name ?: $user->user_login;

        $this->eess_ensure_first_login_welcome($user_id);

        $user_roles = (array) $user->roles;

        global $wpdb;
        $announcements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_system_announcements WHERE status = 'active' ORDER BY id ASC");

        $pending = array();

        foreach ($announcements as $anc) {
            $target_roles = json_decode($anc->target_roles, true) ?: array();
            $matches_role = in_array('all_users', $target_roles) || in_array('administrator', $user_roles);
            if (!$matches_role) {
                foreach ($user_roles as $r) {
                    if (in_array($r, $target_roles)) {
                        $matches_role = true;
                        break;
                    }
                }
            }

            if (!$matches_role) continue;

            $activity = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND user_id = %d",
                $anc->id, $user_id
            ));

            if ($activity && $activity->status === 'closed') {
                continue;
            }

            if (!$activity || $activity->status === 'pending' || ($activity->status === 'viewed' && $activity->view_count < $anc->display_frequency)) {
                $title_text = str_replace('{user_name}', $user_name, $anc->title);
                $details_text = str_replace('{user_name}', $user_name, $anc->details);

                $pending[] = array(
                    'id'               => intval($anc->id),
                    'title'            => $title_text,
                    'details'          => $details_text,
                    'type'             => $anc->type,
                    'display_duration' => intval($anc->display_duration),
                    'display_frequency'=> intval($anc->display_frequency)
                );
            }
        }

        wp_send_json_success($pending);
    }

    public function ajax_mark_announcement_viewed() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $anc_id = intval($_POST['announcement_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$anc_id) wp_send_json_error('ID  ');

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND user_id = %d",
            $anc_id, $user_id
        ));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'status'     => 'viewed',
                    'view_count' => $existing->view_count + 1,
                    'viewed_at'  => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'announcement_id' => $anc_id,
                    'user_id'         => $user_id,
                    'status'          => 'viewed',
                    'view_count'      => 1,
                    'viewed_at'       => current_time('mysql')
                )
            );
        }

        wp_send_json_success();
    }

    public function ajax_mark_announcement_closed() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $anc_id = intval($_POST['announcement_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$anc_id) wp_send_json_error('ID  ');

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND user_id = %d",
            $anc_id, $user_id
        ));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'status'    => 'closed',
                    'closed_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}sm_user_announcements",
                array(
                    'announcement_id' => $anc_id,
                    'user_id'         => $user_id,
                    'status'          => 'closed',
                    'view_count'      => 1,
                    'closed_at'       => current_time('mysql')
                )
            );
        }

        wp_send_json_success();
    }

    public function ajax_reset_user_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(' Unauthorized   .');
        }

        $anc_id  = intval($_POST['announcement_id'] ?? 0);
        $target_user_id = intval($_POST['user_id'] ?? 0);

        if (!$anc_id || !$target_user_id) wp_send_json_error('     .');

        global $wpdb;
        $deleted = $wpdb->delete(
            "{$wpdb->prefix}sm_user_announcements",
            array('announcement_id' => $anc_id, 'user_id' => $target_user_id)
        );

        if ($deleted !== false) {
            SM_Logger::log('  ', "    (ID: $anc_id)  ID: $target_user_id");
            wp_send_json_success();
        } else {
            wp_send_json_error(' Reset .');
        }
    }

    public function ajax_disable_system_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(' Unauthorized   .');
        }

        $anc_id = intval($_POST['announcement_id'] ?? 0);
        if (!$anc_id) wp_send_json_error('ID   .');

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}sm_system_announcements",
            array('status' => 'disabled'),
            array('id' => $anc_id)
        );

        if ($updated !== false) {
            SM_Logger::log('  ', "    (ID: $anc_id)   Disabled");
            wp_send_json_success(array('announcement_id' => $anc_id, 'message' => '      .'));
        } else {
            wp_send_json_error('An error occurred   .');
        }
    }

    public function ajax_delete_system_announcement() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(' Unauthorized   Delete No .');
        }

        $anc_id = intval($_POST['announcement_id'] ?? 0);
        if (!$anc_id) wp_send_json_error('ID   .');

        global $wpdb;
        // Delete announcement record
        $wpdb->delete("{$wpdb->prefix}sm_system_announcements", array('id' => $anc_id));
        // Delete all associated user reading interaction logs
        $wpdb->delete("{$wpdb->prefix}sm_user_announcements", array('announcement_id' => $anc_id));

        SM_Logger::log('Delete   ', " Delete  ID: $anc_id  No  ");
        wp_send_json_success(array('message' => ' Delete   No    .'));
    }

    public function ajax_delete_user_announcement_log() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(' Unauthorized   .');
        }

        $log_id = intval($_POST['log_id'] ?? 0);
        if (!$log_id) wp_send_json_error('ID   .');

        global $wpdb;
        $deleted = $wpdb->delete("{$wpdb->prefix}sm_user_announcements", array('id' => $log_id));

        if ($deleted) {
            SM_Logger::log('Delete   ', " Delete    (ID: $log_id) ");
            wp_send_json_success(array('message' => ' Delete    .'));
        } else {
            wp_send_json_error('An error occurred  Delete .');
        }
    }

    // Technical Support & Help Capsule AJAX Endpoints
    public function ajax_submit_support_request() {
        if (!is_user_logged_in()) {
            wp_send_json_error('  Login    .');
        }

        $user_id  = get_current_user_id();
        $category = sanitize_text_field($_POST['category'] ?? '');

        if (!in_array($category, array('suggestion', 'technical_issue', 'rating'))) {
            wp_send_json_error('   .');
        }

        if ($category === 'suggestion') {
            $title   = sanitize_text_field($_POST['title'] ?? '');
            $details = sanitize_textarea_field($_POST['details'] ?? '');

            if (empty($title) || empty($details)) {
                wp_send_json_error('   Send .');
            }

            if (mb_strlen($details) > 1000) {
                wp_send_json_error('       (1000 ).');
            }

            $req_id = SM_DB::add_support_request(array(
                'user_id'  => $user_id,
                'category' => 'suggestion',
                'title'    => $title,
                'details'  => $details,
                'status'   => 'new'
            ));

            if ($req_id) {
                SM_Logger::log(' ', "    (ID: $req_id) : $title");
                wp_send_json_success(array('message' => '     !  No   .'));
            } else {
                wp_send_json_error('An error occurred  Save .');
            }

        } elseif ($category === 'technical_issue') {
            $title   = sanitize_text_field($_POST['title'] ?? '');
            $details = sanitize_textarea_field($_POST['details'] ?? '');
            $attachment_url = '';

            if (empty($title) || empty($details)) {
                wp_send_json_error('    .');
            }

            if (!empty($_FILES['screenshot']['name'])) {
                $file = $_FILES['screenshot'];

                // File validation: type and size (max 5MB)
                $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                if (!in_array($file['type'], $allowed_mimes)) {
                    wp_send_json_error('   .    (JPG, PNG, GIF, WEBP).');
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    wp_send_json_error('       (5 ).');
                }

                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('screenshot', 0);
                if (is_wp_error($attachment_id)) {
                    wp_send_json_error('   : ' . $attachment_id->get_error_message());
                }
                $attachment_url = wp_get_attachment_url($attachment_id);
            }

            $req_id = SM_DB::add_support_request(array(
                'user_id'        => $user_id,
                'category'       => 'technical_issue',
                'title'          => $title,
                'details'        => $details,
                'attachment_url' => $attachment_url,
                'status'         => 'new'
            ));

            if ($req_id) {
                SM_Logger::log('No   ', " No    (ID: $req_id) : $title");
                wp_send_json_success(array('message' => ' Send No   .       .'));
            } else {
                wp_send_json_error('An error occurred  Send No .');
            }

        } elseif ($category === 'rating') {
            $stars   = intval($_POST['rating_stars'] ?? 5);
            $comment = sanitize_textarea_field($_POST['comment'] ?? '');

            if ($stars < 1 || $stars > 5) {
                wp_send_json_error('    1  5 .');
            }

            if (mb_strlen($comment) > 250) {
                wp_send_json_error('      (250 ).');
            }

            $req_id = SM_DB::add_support_request(array(
                'user_id'      => $user_id,
                'category'     => 'rating',
                'title'        => '  (' . $stars . ' )',
                'details'      => $comment,
                'rating_stars' => $stars,
                'status'       => 'new'
            ));

            if ($req_id) {
                SM_Logger::log('  ', " Send  ($stars )   ID: $user_id");
                wp_send_json_success(array('message' => ' No   !     .'));
            } else {
                wp_send_json_error('An error occurred  Save .');
            }
        }
    }

    public function ajax_update_support_status() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized     .');
        }

        $id     = intval($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'new');

        if (!$id) wp_send_json_error('   .');

        if (SM_DB::update_support_request_status($id, $status)) {
            SM_Logger::log('Update   ', "    ID: $id : $status");
            wp_send_json_success(array('message' => ' Update   .'));
        } else {
            wp_send_json_error(' Update  .');
        }
    }

    public function ajax_delete_support_request() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized  Delete No .');
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error('   .');

        if (SM_DB::delete_support_request($id)) {
            SM_Logger::log('Delete   ', " Delete  / ID: $id    ");
            wp_send_json_success(array('message' => ' Delete     .'));
        } else {
            wp_send_json_error('An error occurred  Delete .');
        }
    }

    public function ajax_send_quick_parent_note() {
        if (!is_user_logged_in()) {
            wp_send_json_error('  Login      Mother.');
        }

        $user = wp_get_current_user();
        $student_id = intval($_POST['student_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');

        if (!$student_id || empty($note)) {
            wp_send_json_error('  No   No.');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error('No   .');
        }

        // Add record log
        SM_Logger::log('No   Mother', "  {$user->display_name} No   No {$student->name}: $note");

        wp_send_json_success(array('message' => ' Send No     No ' . $student->name));
    }

    public function ajax_create_parent_summons() {
        if (!is_user_logged_in() || (!current_user_can('__Mother') && !current_user_can('manage_options'))) {
            wp_send_json_error(' No  Permissions  Issue   .');
        }

        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $reason     = sanitize_text_field($_POST['reason'] ?? '');
        $date       = sanitize_text_field($_POST['summons_date'] ?? current_time('Y-m-d'));
        $time       = sanitize_text_field($_POST['summons_time'] ?? '10:00');
        $dept       = sanitize_text_field($_POST['department_requester'] ?? 'Player Affairs');
        $notes      = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$student_id || empty($reason)) {
            wp_send_json_error('  No   No.');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('No  .');

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_parent_summons", array(
            'student_id'           => $student_id,
            'parent_user_id'       => $student->parent_user_id,
            'reason'               => $reason,
            'summons_date'         => $date,
            'summons_time'         => $time,
            'department_requester' => $dept,
            'notes'                => $notes,
            'status'               => 'sent',
            'created_by'           => get_current_user_id(),
            'created_at'           => current_time('mysql')
        ));

        if ($inserted) {
            $summons_id = $wpdb->insert_id;
            SM_Logger::log('Issue   ', " Issue    No: {$student->name} (ID: $student_id) : $reason");
            wp_send_json_success(array('summons_id' => $summons_id, 'message' => '  Issue   Mother .'));
        } else {
            wp_send_json_error(' Save  No   .');
        }
    }

    public function ajax_convert_summons_visit() {
        if (!is_user_logged_in() || (!current_user_can('__Mother') && !current_user_can('manage_options'))) {
            wp_send_json_error(' No  Permissions    .');
        }

        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $summons_id    = intval($_POST['summons_id'] ?? 0);
        $summary       = sanitize_textarea_field($_POST['discussion_summary'] ?? '');
        $visit_notes   = sanitize_textarea_field($_POST['visit_notes'] ?? '');
        $cooperation   = sanitize_text_field($_POST['parent_cooperation'] ?? '');
        $eval_comments = sanitize_textarea_field($_POST['evaluation_comments'] ?? '');

        if (!$summons_id) wp_send_json_error(' No  .');

        global $wpdb;
        $updated = $wpdb->update("{$wpdb->prefix}sm_parent_summons", array(
            'status'               => 'Attended',
            'actual_visit_date'    => current_time('mysql'),
            'discussion_summary'   => $summary,
            'visit_notes'          => $visit_notes,
            'staff_handler_id'     => get_current_user_id(),
            'parent_cooperation'   => $cooperation,
            'evaluation_comments' => $eval_comments
        ), array('id' => $summons_id));

        if ($updated !== false) {
            SM_Logger::log('   ', "  No ID: $summons_id      : $cooperation");
            wp_send_json_success(array('message' => '    Mother   .'));
        } else {
            wp_send_json_error(' Save  .');
        }
    }

    public function ajax_submit_exit_card_request() {
        if (!is_user_logged_in()) {
            wp_send_json_error('  Login     .');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_user_action') && !wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action')) {
            wp_send_json_error('     (Invalid Nonce)');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $reason     = sanitize_text_field($_POST['reason'] ?? '    No');
        $req_date   = sanitize_text_field($_POST['requested_date'] ?? current_time('Y-m-d'));
        $notes      = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$student_id) {
            $current_uid = get_current_user_id();
            $st = SM_DB::get_student_by_parent($current_uid);
            if ($st) $student_id = $st->id;
        }

        if (!$student_id) {
            wp_send_json_error('  No    .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error('No   .');

        global $wpdb;
        $inserted = $wpdb->insert("{$wpdb->prefix}sm_exit_card_requests", array(
            'student_id'      => $student_id,
            'parent_user_id'  => get_current_user_id(),
            'reason'          => $reason,
            'requested_date'  => $req_date,
            'notes'           => $notes,
            'status'          => 'submitted',
            'printing_status' => 'pending',
            'created_at'      => current_time('mysql')
        ));

        if ($inserted) {
            $req_id = $wpdb->insert_id;
            SM_Logger::log('   ', " No/ Mother     No: {$student->name} (ID: $student_id)");
            wp_send_json_success(array('request_id' => $req_id, 'message' => '           .'));
        } else {
            wp_send_json_error(' Save   .');
        }
    }

    public function ajax_update_exit_card_request_status() {
        if (!is_user_logged_in() || (!current_user_can('_No') && !current_user_can('manage_options') && !current_user_can('manage_students'))) {
            wp_send_json_error(' No  No .');
        }

        $nonce = $_POST['nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_admin_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_user_action')) {
            wp_send_json_error('     (Invalid Nonce)');
        }

        $req_id      = intval($_POST['request_id'] ?? 0);
        $new_status  = sanitize_text_field($_POST['status'] ?? '');
        $print_stat  = sanitize_text_field($_POST['printing_status'] ?? '');
        $review_note = sanitize_textarea_field($_POST['review_notes'] ?? '');

        if (!$req_id) wp_send_json_error('   .');

        global $wpdb;
        $update_data = array(
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql')
        );

        if (!empty($new_status)) $update_data['status'] = $new_status;
        if (!empty($print_stat)) $update_data['printing_status'] = $print_stat;
        if (!empty($review_note)) $update_data['review_notes'] = $review_note;

        $updated = $wpdb->update("{$wpdb->prefix}sm_exit_card_requests", $update_data, array('id' => $req_id));

        if ($updated !== false) {
            wp_send_json_success(array('message' => ' Update     .'));
        } else {
            wp_send_json_error(' Update  .');
        }
    }

    public function ajax_update_summons_status() {
        if (!is_user_logged_in() || (!current_user_can('__Mother') && !current_user_can('manage_options'))) {
            wp_send_json_error(' No  Permissions .');
        }

        $nonce = $_POST['sm_nonce'] ?? ($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'eess_admin_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $summons_id = intval($_POST['summons_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? 'sent');

        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_parent_summons", array('status' => $new_status), array('id' => $summons_id));
        wp_send_json_success(array('message' => ' Update  No .'));
    }

    public function ajax_send_message() {
        if (!is_user_logged_in()) {
            wp_send_json_error('  Login   .');
        }

        $nonce = $_POST['sm_message_nonce'] ?? ($_POST['sm_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'sm_message_action') && !wp_verify_nonce($nonce, 'sm_admin_action')) {
            wp_send_json_error('  Mother.');
        }

        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $student_id  = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
        $message     = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($message)) {
            wp_send_json_error('   No.');
        }

        $sent = SM_DB::send_message(get_current_user_id(), $receiver_id, $message, $student_id);
        if ($sent) {
            wp_send_json_success(array('message' => ' Send  '));
        } else {
            wp_send_json_error(' Send No   No.');
        }
    }

    public function ajax_get_educational_suggestions() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $query      = sanitize_text_field($_POST['query'] ?? '');
        $subject    = sanitize_text_field($_POST['subject'] ?? '');
        $input_type = sanitize_text_field($_POST['input_type'] ?? 'title');

        if (empty($query) || mb_strlen($query) < 2) {
            wp_send_json_success(array());
        }

        global $wpdb;
        $sql = "SELECT id, subject, input_type, content, usage_count FROM {$wpdb->prefix}sm_educational_inputs WHERE is_approved = 1 AND input_type = %s";
        $params = array($input_type);

        if (!empty($subject)) {
            $sql .= " AND subject = %s";
            $params[] = $subject;
        }

        $sql .= " AND content LIKE %s ORDER BY usage_count DESC, id DESC LIMIT 10";
        $params[] = '%' . $wpdb->esc_like($query) . '%';

        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        wp_send_json_success($results ?: array());
    }

    public function ajax_save_educational_input() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

        $subject    = sanitize_text_field($_POST['subject'] ?? '');
        $input_type = sanitize_text_field($_POST['input_type'] ?? 'title');
        $content    = sanitize_textarea_field($_POST['content'] ?? '');

        if (empty($subject) || empty($content) || mb_strlen($content) < 3) {
            wp_send_json_error('  Sport Activity   .');
        }

        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, usage_count FROM {$wpdb->prefix}sm_educational_inputs WHERE subject = %s AND input_type = %s AND content = %s LIMIT 1",
            $subject, $input_type, $content
        ));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}sm_educational_inputs",
                array('usage_count' => $existing->usage_count + 1),
                array('id' => $existing->id)
            );
            wp_send_json_success(array('id' => $existing->id, 'usage_count' => $existing->usage_count + 1));
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}sm_educational_inputs",
                array(
                    'subject'     => $subject,
                    'input_type'  => $input_type,
                    'content'     => $content,
                    'usage_count' => 1,
                    'is_approved' => 1,
                    'created_by'  => get_current_user_id(),
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => current_time('mysql')
                )
            );
            wp_send_json_success(array('id' => $wpdb->insert_id, 'usage_count' => 1));
        }
    }

    public function ajax_get_school_prep_weeks() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized access');
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $school_id = intval($_REQUEST['school_id'] ?? 0);
        if ($school_id <= 0) {
            wp_send_json_error('Invalid school ID');
        }

        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);
        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools']) && !in_array($school_id, $user_scope['schools'])) {
                wp_send_json_error('Unauthorized    Academy .');
            }
        }

        $query = "SELECT DISTINCT p.created_at, p.updated_at
                  FROM {$wpdb->prefix}sm_lesson_preps p
                  JOIN {$wpdb->prefix}eess_user_assignments ua ON p.teacher_id = ua.user_id
                  WHERE ua.school_id = %d AND p.status IN ('submitted', 'approved', 'returned', 'resubmitted', 'rejected')";

        $rows = $wpdb->get_results($wpdb->prepare($query, $school_id));

        $arabic_weeks = array(
            1 => ' ', 2 => ' ', 3 => ' ', 4 => ' ',
            5 => ' ', 6 => ' ', 7 => ' ', 8 => ' ',
            9 => ' ', 10 => ' ', 11 => '  ', 12 => '  ',
            13 => '  ', 14 => '  ', 15 => '  ', 16 => '  '
        );

        $existing_weeks = array();
        $acad_anchor_ts = strtotime('2026-08-28 00:00:00');

        foreach ($rows as $r) {
            $ts = strtotime($r->updated_at ?: $r->created_at);
            $w = ($ts >= $acad_anchor_ts) ? (intval(floor(($ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
            if ($w > 0 && !in_array($w, $existing_weeks, true)) {
                $existing_weeks[] = $w;
            }
        }

        sort($existing_weeks, SORT_NUMERIC);

        $formatted = array();
        foreach ($existing_weeks as $w_num) {
            $formatted[] = array(
                'week_num' => $w_num,
                'week_name' => ($arabic_weeks[$w_num] ?? (' ' . $w_num)) . ' (Week ' . $w_num . ')'
            );
        }

        wp_send_json_success(array('weeks' => $formatted));
    }

    public function ajax_bulk_download_lesson_preps() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized access');
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        if (!class_exists('ZipArchive')) {
            wp_send_json_error(' ZipArchive     .');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);

        $download_scope = sanitize_text_field($_REQUEST['scope_type'] ?? 'all');
        $week_num      = intval($_REQUEST['week_num'] ?? 0);
        $month_val     = sanitize_text_field($_REQUEST['month_val'] ?? '');
        $date_val      = sanitize_text_field($_REQUEST['date_val'] ?? '');
        $date_from     = sanitize_text_field($_REQUEST['date_from'] ?? '');
        $date_to       = sanitize_text_field($_REQUEST['date_to'] ?? '');

        $query = "SELECT p.*, u.display_name as teacher_name
                  FROM {$wpdb->prefix}sm_lesson_preps p
                  JOIN {$wpdb->prefix}users u ON p.teacher_id = u.ID
                  WHERE p.status IN ('submitted', 'approved', 'returned', 'resubmitted', 'rejected')";
        $params = array();

        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools'])) {
                $placeholders = implode(',', array_fill(0, count($user_scope['schools']), '%d'));
                $query .= " AND (p.teacher_id IN (SELECT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE school_id IN ($placeholders)) OR p.teacher_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('eess_school_id', 'sm_school_id') AND meta_value IN ($placeholders)))";
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
            } else {
                $query .= " AND p.teacher_id = %d";
                $params[] = $user_id;
            }
        }

        if ($download_scope === 'month' && !empty($month_val)) {
            $query .= " AND DATE_FORMAT(p.lesson_date, '%%Y-%%m') = %s";
            $params[] = $month_val;
        } elseif ($download_scope === 'date' && !empty($date_val)) {
            $query .= " AND p.lesson_date = %s";
            $params[] = $date_val;
        } elseif ($download_scope === 'range' && !empty($date_from) && !empty($date_to)) {
            $query .= " AND p.lesson_date BETWEEN %s AND %s";
            $params[] = $date_from;
            $params[] = $date_to;
        }

        $query .= " ORDER BY p.created_at DESC, p.id DESC";

        $raw_records = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

        // Filter by Academic Week using 30 August 2026 anchor logic
        $acad_anchor_ts = strtotime('2026-08-28 00:00:00');
        $records = array();

        foreach ($raw_records as $rec) {
            if ($download_scope === 'week' || $week_num > 0) {
                if ($week_num > 0) {
                    $p_ts = strtotime($rec->updated_at ?: $rec->created_at);
                    $cw = ($p_ts >= $acad_anchor_ts) ? (intval(floor(($p_ts - $acad_anchor_ts) / (7 * 86400))) + 1) : 1;
                    if ($cw !== $week_num) {
                        continue;
                    }
                }
            }
            $records[] = $rec;
        }

        if (empty($records)) {
            wp_send_json_error('No        .');
        }

        $upload_dir = wp_upload_dir();
        $zip_name = 'Lesson_Preparations_Archive_' . date('Y-m-d_His') . '.zip';
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_send_json_error('    .');
        }

        $added_count = 0;
        $skipped_count = 0;
        $added_inner_paths = array();

        foreach ($records as $rec) {
            $file_url = !empty($rec->file_url) ? $rec->file_url : '';
            if (empty($file_url) && !empty($rec->lesson_data)) {
                $parsed = json_decode($rec->lesson_data, true);
                $file_url = $parsed['file_url'] ?? '';
            }

            if (empty($file_url)) {
                $skipped_count++;
                continue;
            }

            // Enhanced multi-strategy local file path resolution
            $local_path = '';
            if (strpos($file_url, $upload_dir['baseurl']) !== false) {
                $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
            } elseif (strpos($file_url, '/wp-content/uploads/') !== false) {
                $rel_path = substr($file_url, strpos($file_url, '/wp-content/uploads/') + 19);
                $local_path = $upload_dir['basedir'] . '/' . ltrim($rel_path, '/');
            } elseif (file_exists($file_url)) {
                $local_path = $file_url;
            } else {
                $parsed_path = wp_parse_url($file_url, PHP_URL_PATH);
                if ($parsed_path && file_exists(ABSPATH . ltrim($parsed_path, '/'))) {
                    $local_path = ABSPATH . ltrim($parsed_path, '/');
                }
            }

            if (empty($local_path) || !file_exists($local_path)) {
                $skipped_count++;
                continue;
            }

            $rec->file_url = $file_url;
            $std_filename = EESS_File_Naming_Service::generate_lesson_prep_filename($rec);
            $zip_folder = sanitize_file_name($rec->teacher_name ?: '');
            $zip_inner_path = $zip_folder . '/' . $std_filename;

            // Ensure unique inner path inside ZIP to prevent collisions
            if (in_array($zip_inner_path, $added_inner_paths, true)) {
                $ext = pathinfo($std_filename, PATHINFO_EXTENSION);
                $base = pathinfo($std_filename, PATHINFO_FILENAME);
                $zip_inner_path = $zip_folder . '/' . $base . '_' . $rec->id . ($ext ? '.' . $ext : '');
            }

            $zip->addFile($local_path, $zip_inner_path);
            $added_inner_paths[] = $zip_inner_path;
            $added_count++;
        }

        $zip->close();

        if ($added_count === 0) {
            if (file_exists($zip_path)) @unlink($zip_path);
            wp_send_json_error(' No        No     .');
        }

        SM_Logger::log('bulk_download', "   Download    ({$added_count} )");

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zip_path);
        if (file_exists($zip_path)) {
            @unlink($zip_path);
        }
        exit;
    }

    public function ajax_bulk_download_term_plans() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized access');
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }

        if (!class_exists('ZipArchive')) {
            wp_send_json_error(' ZipArchive     .');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_scope = EESS_Org_Helper::get_user_scope($user_id);

        $term_number = intval($_REQUEST['term_number'] ?? 0);

        $query = "SELECT tp.*, u.display_name as teacher_name
                  FROM {$wpdb->prefix}sm_term_plans tp
                  JOIN {$wpdb->prefix}users u ON tp.teacher_id = u.ID
                  WHERE tp.status IN ('submitted', 'approved', 'returned', 'resubmitted', 'rejected')";
        $params = array();

        if (!$user_scope['unrestricted']) {
            if (!empty($user_scope['schools'])) {
                $placeholders = implode(',', array_fill(0, count($user_scope['schools']), '%d'));
                $query .= " AND (tp.teacher_id IN (SELECT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE school_id IN ($placeholders)) OR tp.teacher_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('eess_school_id', 'sm_school_id') AND meta_value IN ($placeholders)))";
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
                foreach ($user_scope['schools'] as $sch_id) $params[] = $sch_id;
            } else {
                $query .= " AND tp.teacher_id = %d";
                $params[] = $user_id;
            }
        }

        if ($term_number > 0) {
            $query .= " AND tp.term_number = %d";
            $params[] = $term_number;
        }

        $query .= " ORDER BY tp.term_number ASC, tp.id DESC";

        $records = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

        if (empty($records)) {
            wp_send_json_error('No   /Annual    Download .');
        }

        $upload_dir = wp_upload_dir();
        $zip_name = 'Quarterly_Plans_Term_' . ($term_number ?: 'All') . '_' . date('Y-m-d_His') . '.zip';
        $zip_path = $upload_dir['path'] . '/' . $zip_name;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_send_json_error('    .');
        }

        $added_count = 0;
        $skipped_count = 0;

        foreach ($records as $rec) {
            $file_url = !empty($rec->plan_file_url) ? $rec->plan_file_url : ($rec->file_url ?? '');
            if (empty($file_url)) {
                $skipped_count++;
                continue;
            }

            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
            if (!file_exists($local_path)) {
                // Try relative path or absolute path if upload_dir replacement differed
                $parsed_path = wp_parse_url($file_url, PHP_URL_PATH);
                if ($parsed_path && file_exists(ABSPATH . ltrim($parsed_path, '/'))) {
                    $local_path = ABSPATH . ltrim($parsed_path, '/');
                }
            }

            if (!file_exists($local_path)) {
                $skipped_count++;
                continue;
            }

            // Sync property for Naming Service
            $rec->file_url = $file_url;
            $std_filename = EESS_File_Naming_Service::generate_term_plan_filename($rec);
            $zip_folder = sanitize_file_name($rec->teacher_name ?: '');
            $zip_inner_path = $zip_folder . '/' . $std_filename;

            $zip->addFile($local_path, $zip_inner_path);
            $added_count++;
        }

        $zip->close();

        if ($added_count === 0) {
            if (file_exists($zip_path)) @unlink($zip_path);
            wp_send_json_error(' No  Training Group  No     .');
        }

        SM_Logger::log('bulk_download', "   Download    Training Group Annual ({$added_count} )");

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zip_path);
        if (file_exists($zip_path)) {
            @unlink($zip_path);
        }
        exit;
    }

    public function ajax_eess_save_department() {
        if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('_'))) {
            wp_send_json_error('Unauthorized    Edit');
        }
        check_ajax_referer('sm_admin_action', 'nonce');

        $dept_id = intval($_POST['dept_id'] ?? 0);
        $name    = sanitize_text_field($_POST['name'] ?? '');
        $code    = trim(sanitize_text_field($_POST['code'] ?? ''));

        if (empty($name)) {
            wp_send_json_error('  ');
        }

        if (!ctype_digit($code)) {
            wp_send_json_error('           .');
        }

        $data = array(
            'name' => $name,
            'code' => $code
        );

        if ($dept_id > 0) {
            $res = EESS_Org_Helper::update_department($dept_id, $data);
        } else {
            $res = EESS_Org_Helper::add_department(1, $data);
        }

        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(array('message' => ' Save    '));
    }

    public function ajax_eess_delete_department() {
        if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('_'))) {
            wp_send_json_error('Unauthorized    Edit');
        }
        check_ajax_referer('sm_admin_action', 'nonce');

        $dept_id = intval($_POST['dept_id'] ?? 0);
        if ($dept_id <= 0) {
            wp_send_json_error('   ');
        }

        $res = EESS_Org_Helper::delete_department($dept_id);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(array('message' => ' Delete  '));
    }

    public function ajax_eess_save_subject() {
        if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('_'))) {
            wp_send_json_error('Unauthorized    Edit');
        }
        check_ajax_referer('sm_admin_action', 'nonce');

        $inst_id = intval($_POST['inst_id'] ?? 1);
        $sub_id  = intval($_POST['sub_id'] ?? 0);
        $name    = sanitize_text_field($_POST['name'] ?? '');

        if (empty($name)) {
            wp_send_json_error(' Sport Activity  ');
        }

        $data = array(
            'id'                  => $sub_id,
            'name'                => $name,
            'code'                => sanitize_text_field($_POST['code'] ?? ''),
            'department_id'       => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
            'hod_user_id'         => !empty($_POST['hod_user_id']) ? intval($_POST['hod_user_id']) : null,
            'coordinator_user_id' => !empty($_POST['coordinator_user_id']) ? intval($_POST['coordinator_user_id']) : null,
            'status'              => sanitize_text_field($_POST['status'] ?? 'active'),
            'grade_ids'           => !empty($_POST['grade_ids']) ? array_map('intval', (array)$_POST['grade_ids']) : array(),
            'school_ids'          => !empty($_POST['school_ids']) ? array_map('intval', (array)$_POST['school_ids']) : array()
        );

        $res = EESS_Org_Helper::save_subject($inst_id, $data);

        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(array('message' => ' Save Sport Activity  '));
    }

    public function ajax_eess_save_grade() {
        if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('_'))) {
            wp_send_json_error('Unauthorized    Edit');
        }
        check_ajax_referer('sm_admin_action', 'nonce');

        $grade_id  = intval($_POST['grade_id'] ?? 0);
        $school_id = intval($_POST['school_id'] ?? 1);
        $name      = sanitize_text_field($_POST['name'] ?? '');

        if (empty($name)) {
            wp_send_json_error(' Training Group  ');
        }

        if ($grade_id > 0) {
            $res = EESS_Org_Helper::update_grade($grade_id, $name, $school_id);
        } else {
            $res = EESS_Org_Helper::add_grade($school_id, $name);
        }

        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(array('message' => ' Save Training Group  '));
    }

    /*
     * PUBLIC STUDENT EXIT CARD WIZARD & SHORTCODE [card]
     */
    public function shortcode_public_card_wizard() {
        ob_start();
        include SM_PLUGIN_DIR . 'templates/public-card-wizard.php';
        return ob_get_clean();
    }

    public static function normalize_arabic_str($str) {
        if (empty($str)) return '';
        $str = trim($str);
        $str = preg_replace('/[\x{064B}-\x{0652}]/u', '', $str);
        $str = preg_replace('/[]/u', '', $str);
        $str = preg_replace('//u', '', $str);
        $str = preg_replace('//u', '', $str);
        $str = preg_replace('/\s+/u', ' ', $str);
        return mb_strtolower($str, 'UTF-8');
    }

    public function ajax_public_search_student() {
        $name_query = sanitize_text_field($_POST['name_query'] ?? '');
        $clean_query = trim($name_query);
        $words = array_values(array_filter(explode(' ', $clean_query)));

        // Only search when at least 5 characters are typed
        if (mb_strlen($clean_query) < 5) {
            wp_send_json_error('  5     Search .');
        }

        global $wpdb;

        $where = array();
        $params = array();

        foreach ($words as $word) {
            if (mb_strlen($word) >= 2) {
                $where[] = "name LIKE %s";
                $params[] = '%' . $wpdb->esc_like($word) . '%';
            }
        }

        if (empty($where)) {
            $sql = "SELECT id, name, student_code, class_name, section, photo_url FROM {$wpdb->prefix}sm_students WHERE name LIKE %s ORDER BY name ASC LIMIT 15";
            $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($clean_query) . '%'));
        } else {
            $sql = "SELECT id, name, student_code, class_name, section, photo_url FROM {$wpdb->prefix}sm_students WHERE " . implode(" AND ", $where) . " ORDER BY name ASC LIMIT 15";
            $results = $wpdb->get_results($wpdb->prepare($sql, $params));
            if (empty($results)) {
                $sql = "SELECT id, name, student_code, class_name, section, photo_url FROM {$wpdb->prefix}sm_students WHERE " . implode(" OR ", $where) . " ORDER BY name ASC LIMIT 15";
                $results = $wpdb->get_results($wpdb->prepare($sql, $params));
            }
        }

        if (empty($results)) {
            wp_send_json_error('    No  No .');
        }

        $norm_query = self::normalize_arabic_str($clean_query);
        $safe_suggestions = array();
        foreach ($results as $s) {
            $norm_name = self::normalize_arabic_str($s->name);
            $exact_match = ($norm_name === $norm_query || strpos($norm_name, $norm_query) === 0);

            $safe_suggestions[] = array(
                'id'           => $s->id,
                'name'         => $s->name,
                'display_name' => $s->name,
                'student_code' => $s->student_code ?: ('STU-' . $s->id),
                'class_name'   => $s->class_name ?: 'Training Group ',
                'section'      => $s->section ?: '',
                'photo_url'    => $s->photo_url ?: '',
                'exact_match'  => $exact_match
            );
        }

        usort($safe_suggestions, function($a, $b) {
            if ($a['exact_match'] && !$b['exact_match']) return -1;
            if (!$a['exact_match'] && $b['exact_match']) return 1;
            return strcmp($a['display_name'], $b['display_name']);
        });

        wp_send_json_success(array_slice($safe_suggestions, 0, 10));
    }

    public function ajax_public_verify_student() {
        $student_id  = intval($_POST['student_id'] ?? 0);
        $verify_code = sanitize_text_field($_POST['verify_code'] ?? '');
        $service     = sanitize_text_field($_POST['service'] ?? ($_POST['active_service'] ?? ''));
        $dob_input   = sanitize_text_field($_POST['dob'] ?? '');

        if (!$student_id) {
            wp_send_json_error('   .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error(' No      .');
        }

        // Service 1: Data Update mode - No pre-verification by code/national_id required
        if ($service === 'update_data' || $verify_code === 'NAME_ONLY') {
            $matched = true;
        } else {
            if (empty($verify_code)) {
                wp_send_json_error('   No   National ID .');
            }
            $clean_input = strtolower(trim($verify_code));
            $stu_code    = strtolower(trim($student->student_code ?: ''));
            $nat_id      = strtolower(trim($student->national_id ?: ''));

            $code_matched = (!empty($stu_code) && $clean_input === $stu_code) || (!empty($nat_id) && $clean_input === $nat_id);

            $dob_matched = true;
            if (!empty($dob_input) && !empty($student->dob) && $student->dob !== '0000-00-00') {
                $formatted_dob = date('Y-m-d', strtotime($dob_input));
                $dob_matched   = ($formatted_dob === date('Y-m-d', strtotime($student->dob)));
            }

            $matched = $code_matched && $dob_matched;

            if (!$matched) {
                if (!$code_matched) {
                    wp_send_json_error(' No   National ID     No .');
                } else {
                    wp_send_json_error('Date of Birth    No No .');
                }
            }
        }

        global $wpdb;
        $acad_year = '2025/2026';
        $active_req = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id = %d AND academic_year = %s AND status IN ('submitted', 'under_review', 'parent_confirmation', 'approved', 'preparing', 'issued') ORDER BY id DESC LIMIT 1",
            $student_id, $acad_year
        ));

        $req_history = $wpdb->get_results($wpdb->prepare(
            "SELECT id, reference_no, status, created_at FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id = %d AND academic_year = %s ORDER BY id DESC",
            $student_id, $acad_year
        ));

        $total_prev_requests = count($req_history);

        $settings = get_option('sm_exit_card_settings', array(
            'portal_mode' => 'card_application',
            'required_fields' => array('guardian_phone', 'dob'),
            'max_requests' => 3,
            'redirect_discipline' => 'yes'
        ));

        $portal_mode = $settings['portal_mode'] ?? 'card_application';
        $enabled_fields = (array) ($settings['required_fields'] ?? array('guardian_phone', 'dob'));
        $max_reqs = intval($settings['max_requests'] ?? 3);
        $exceeded_limit = ($total_prev_requests >= $max_reqs);

        // Dynamically evaluate missing required fields
        $missing_fields = array();
        $field_values = array();

        foreach ($enabled_fields as $fk) {
            $val = trim((string)($student->$fk ?? ''));
            $field_values[$fk] = $val;

            if ($fk === 'guardian_phone') {
                $digits = preg_replace('/\D/', '', $val);
                if (strlen($digits) < 8) {
                    $missing_fields[] = 'guardian_phone';
                }
            } elseif ($fk === 'dob') {
                if (empty($val) || $val === '0000-00-00') {
                    $missing_fields[] = 'dob';
                }
            } else {
                if (empty($val)) {
                    $missing_fields[] = $fk;
                }
            }
        }

        // Always enforce National ID as required field if missing, incomplete, or invalid in student record
        $clean_nat_id = preg_replace('/\D/', '', $student->national_id ?: '');
        if (empty($clean_nat_id) || strlen($clean_nat_id) < 10) {
            if (!in_array('national_id', $missing_fields)) {
                $missing_fields[] = 'national_id';
            }
        }

        $has_photo = !empty($student->photo_url);

        wp_send_json_success(array(
            'student' => array(
                'id' => $student->id,
                'name' => $student->name,
                'student_code' => $student->student_code ?: ('STU-' . $student->id),
                'class_name' => $student->class_name ?: 'Training Group ',
                'section' => $student->section ?: '',
                'photo_url' => $student->photo_url ?: '',
                'guardian_phone' => ($student->guardian_phone ?? '') ?: '',
                'dob' => ($student->dob ?? '') ?: '',
                'gender' => ($student->gender ?? '') ?: '',
                'guardian_name' => ($student->guardian_name ?? '') ?: '',
                'emirate' => ($student->emirate ?? '') ?: '',
                'address' => ($student->address ?? '') ?: '',
                'nationality' => ($student->nationality ?? '') ?: '',
                'national_id' => ($student->national_id ?? '') ?: ''
            ),
            'portal_mode' => $portal_mode,
            'enabled_fields' => $enabled_fields,
            'missing_fields' => $missing_fields,
            'has_photo' => $has_photo,
            'active_request' => $active_req ? array(
                'reference_no' => $active_req->reference_no ?: ('EXT-' . date('Y') . '-' . $active_req->id),
                'status' => $active_req->status,
                'status_label' => self::eess_get_exit_card_status_label($active_req->status),
                'status_desc' => self::eess_get_exit_card_status_desc($active_req->status),
                'created_at' => date_i18n('Y-m-d H:i', strtotime($active_req->created_at))
            ) : null,
            'total_prev_requests' => $total_prev_requests,
            'fee_required' => ($total_prev_requests >= 1),
            'fee_amount' => ($total_prev_requests >= 1) ? 10 : 0,
            'fee_notice' => ($total_prev_requests >= 1) ? ' Issue      No.      Academy     Print  (10  )   .' : '',
            'exceeded_limit' => $exceeded_limit,
            'max_allowed' => $max_reqs
        ));
    }

    public function ajax_public_update_student_missing_data() {
        $settings = get_option('sm_exit_card_settings', array());
        if (($settings['service_update_data'] ?? 'yes') === 'no') {
            wp_send_json_error(' Update  No    .');
        }

        $student_id  = intval($_POST['student_id'] ?? 0);

        if (!$student_id) {
            wp_send_json_error('  No  Update .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error(' No  .');
        }

        $update_data = array();

        // National ID Update
        if (isset($_POST['national_id'])) {
            $raw_nat_id = trim(sanitize_text_field($_POST['national_id']));
            if (!empty($raw_nat_id)) {
                $clean_nat_id = preg_replace('/\D/', '', $raw_nat_id);
                if (strlen($clean_nat_id) < 10) {
                    wp_send_json_error('         15 .');
                }
                $update_data['national_id'] = $raw_nat_id;
            }
        }

        // 1. Guardian Phone (Enforce +971 UAE Fixed Prefix)
        if (isset($_POST['guardian_phone'])) {
            $raw_phone = trim(sanitize_text_field($_POST['guardian_phone']));
            $digits = preg_replace('/\D/', '', $raw_phone);
            if (strpos($digits, '971') === 0) {
                $digits = substr($digits, 3);
            }
            $digits = ltrim($digits, '0');
            if (strlen($digits) < 7) {
                wp_send_json_error('       Mother.');
            }
            $update_data['guardian_phone'] = '+971 ' . $digits;
        }

        // 2. Date of Birth
        if (isset($_POST['dob'])) {
            $raw_dob = trim(sanitize_text_field($_POST['dob']));
            if (!empty($raw_dob)) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_dob)) {
                    wp_send_json_error(' Date of Birth  .  YYYY-MM-DD.');
                }
                $update_data['dob'] = $raw_dob;
            }
        }

        // 3. Gender
        if (isset($_POST['gender'])) {
            $g = sanitize_text_field($_POST['gender']);
            if (in_array($g, array('Male', 'Female', 'Male', 'Female'), true)) {
                $update_data['gender'] = ($g === 'Female' || $g === 'Female') ? 'Female' : 'Male';
            }
        }

        // 4. Guardian Name
        if (isset($_POST['guardian_name']) && !empty($_POST['guardian_name'])) {
            $update_data['guardian_name'] = sanitize_text_field($_POST['guardian_name']);
        }

        // 5. Emirate
        if (isset($_POST['emirate']) && !empty($_POST['emirate'])) {
            $update_data['emirate'] = sanitize_text_field($_POST['emirate']);
        }

        // 6. Address
        if (isset($_POST['address']) && !empty($_POST['address'])) {
            $update_data['address'] = sanitize_textarea_field($_POST['address']);
        }

        // 7. Nationality
        if (isset($_POST['nationality']) && !empty($_POST['nationality'])) {
            $update_data['nationality'] = sanitize_text_field($_POST['nationality']);
        }

        // 8. National ID
        if (isset($_POST['national_id']) && !empty($_POST['national_id'])) {
            $nid = sanitize_text_field($_POST['national_id']);
            global $wpdb;
            $existing_nat = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s AND id != %d", $nid, $student_id));
            if ($existing_nat) {
                wp_send_json_error(' National ID   No .');
            }
            $update_data['national_id'] = $nid;
        }

        if (!empty($update_data)) {
            global $wpdb;
            $wpdb->update("{$wpdb->prefix}sm_students", $update_data, array('id' => $student_id));
            wp_cache_flush();
        }

        // Re-read updated student
        $updated_stu = SM_DB::get_student_by_id($student_id);

        $settings = get_option('sm_exit_card_settings', array(
            'portal_mode' => 'card_application',
            'required_fields' => array('guardian_phone', 'dob')
        ));
        $enabled_fields = (array) ($settings['required_fields'] ?? array('guardian_phone', 'dob'));
        $rem_missing = array();

        foreach ($enabled_fields as $fk) {
            $val = trim((string)($updated_stu->$fk ?? ''));
            if ($fk === 'guardian_phone') {
                $digits = preg_replace('/\D/', '', $val);
                if (strlen($digits) < 8) $rem_missing[] = 'guardian_phone';
            } elseif ($fk === 'dob') {
                if (empty($val) || $val === '0000-00-00') $rem_missing[] = 'dob';
            } else {
                if (empty($val)) $rem_missing[] = $fk;
            }
        }

        $stu_code_val = !empty($updated_stu->student_code) ? $updated_stu->student_code : ('STU-' . $updated_stu->id);
        $nat_id_val   = !empty($updated_stu->national_id) ? $updated_stu->national_id : ' ';

        wp_send_json_success(array(
            'message' => ' Save Update  No .',
            'student_code' => $stu_code_val,
            'national_id'  => $nat_id_val,
            'notice' => ' :  No  No   (' . $stu_code_val . ')   National ID (' . $nat_id_val . ')          .',
            'remaining_missing' => $rem_missing,
            'student' => array(
                'id' => $updated_stu->id,
                'name' => $updated_stu->name,
                'student_code' => $stu_code_val,
                'guardian_phone' => $updated_stu->guardian_phone,
                'dob' => $updated_stu->dob,
                'gender' => $updated_stu->gender,
                'guardian_name' => $updated_stu->guardian_name,
                'emirate' => $updated_stu->emirate,
                'address' => $updated_stu->address,
                'nationality' => $updated_stu->nationality,
                'national_id' => $updated_stu->national_id
            )
        ));
    }

    public function ajax_public_submit_exit_card() {
        $settings = get_option('sm_exit_card_settings', array());
        if (($settings['service_exit_card'] ?? 'yes') === 'no') {
            wp_send_json_error('        .');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $verify_code = sanitize_text_field($_POST['verify_code'] ?? '');
        $parent_name = sanitize_text_field($_POST['parent_name'] ?? '');
        $parent_phone = sanitize_text_field($_POST['parent_phone'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '    No');
        $declaration = intval($_POST['declaration'] ?? 0);
        $sig_raw = $_POST['signature_data'] ?? '';

        if (!$student_id || empty($verify_code) || empty($parent_name) || empty($parent_phone) || !$declaration || empty($sig_raw)) {
            wp_send_json_error('       .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error(' No  .');
        }

        // Validate verification code matches student code or national ID
        $clean_input = strtolower(trim($verify_code));
        $stu_code = strtolower(trim($student->student_code ?: ''));
        $nat_id   = strtolower(trim($student->national_id ?: ''));

        $matched = false;
        if (!empty($stu_code) && $clean_input === $stu_code) {
            $matched = true;
        } elseif (!empty($nat_id) && $clean_input === $nat_id) {
            $matched = true;
        }

        if (!$matched) {
            wp_send_json_error('     No .');
        }

        // Validate signature Data URI pattern (strictly image/png, jpeg, webp base64)
        $signature_data = '';
        if (preg_match('/^data:image\/(png|jpeg|webp);base64,[A-Za-z0-9+\/=]+$/', $sig_raw)) {
            $signature_data = $sig_raw;
        } else {
            wp_send_json_error('    .');
        }

        global $wpdb;

        // Handle Mandatory Student Profile Photo Upload if missing or provided
        if (!empty($_FILES['student_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_info = wp_check_filetype_and_ext($_FILES['student_photo']['tmp_name'], $_FILES['student_photo']['name']);
            $allowed_mimes = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');

            if (!in_array($file_info['type'], $allowed_mimes) && !array_key_exists($file_info['ext'], $allowed_mimes)) {
                wp_send_json_error('     .      JPG  PNG  WEBP.');
            }

            if ($_FILES['student_photo']['size'] > 5 * 1024 * 1024) {
                wp_send_json_error('       (5 ).');
            }

            $attachment_id = media_handle_upload('student_photo', 0);
            if (!is_wp_error($attachment_id)) {
                $new_photo_url = wp_get_attachment_url($attachment_id);
                // Synchronize immediately with main student record
                $wpdb->update("{$wpdb->prefix}sm_students", array('photo_url' => $new_photo_url), array('id' => $student_id));
                $student->photo_url = $new_photo_url;

                // Sync with WP user meta if linked
                if (!empty($student->parent_user_id)) {
                    update_user_meta($student->parent_user_id, 'eess_profile_photo', $new_photo_url);
                }
            } else {
                wp_send_json_error('    No: ' . $attachment_id->get_error_message());
            }
        } elseif (empty($student->photo_url)) {
            wp_send_json_error(' :        No    .');
        }

        $acad_year = '2025/2026';

        // Check active duplicate request
        $active_req = $wpdb->get_row($wpdb->prepare(
            "SELECT id, reference_no, status FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id = %d AND academic_year = %s AND status IN ('submitted', 'under_review', 'parent_confirmation', 'approved', 'preparing') LIMIT 1",
            $student_id, $acad_year
        ));

        if ($active_req) {
            wp_send_json_error('  Active   No   (' . ($active_req->reference_no ?: $active_req->id) . ').     .');
        }

        $ref_no = 'EXT-' . date('Y') . '-' . rand(10000, 99999);

        $inserted = $wpdb->insert(
            "{$wpdb->prefix}sm_exit_card_requests",
            array(
                'reference_no' => $ref_no,
                'student_id' => $student_id,
                'parent_user_id' => get_current_user_id() ?: null,
                'parent_name' => $parent_name,
                'parent_phone' => $parent_phone,
                'academic_year' => $acad_year,
                'reason' => $reason,
                'requested_date' => current_time('Y-m-d'),
                'declaration_accepted' => 1,
                'signature_data' => $signature_data,
                'status' => 'submitted',
                'printing_status' => 'pending',
                'created_at' => current_time('mysql')
            )
        );

        if ($inserted) {
            $req_id = $wpdb->insert_id;
            SM_Logger::log('   ', "      : $ref_no No: {$student->name}");
            wp_send_json_success(array(
                'request_id' => $req_id,
                'reference_no' => $ref_no,
                'message' => '  No         .'
            ));
        } else {
            wp_send_json_error(' Save      .');
        }
    }

    public static function eess_get_exit_card_status_label($status) {
        $labels = array(
            'submitted' => '  ',
            'under_review' => '  ',
            'parent_confirmation' => ' Confirm  Mother',
            'approved' => ' Approve ',
            'preparing' => '  Print ',
            'issued' => ' Issue  ',
            'rejected' => ' Reject '
        );
        return $labels[$status] ?? ' ';
    }

    public static function eess_get_exit_card_status_desc($status) {
        $descs = array(
            'submitted' => ' No       .',
            'under_review' => '  Player Affairs   No    .',
            'parent_confirmation' => '     Academy  Confirm  No.',
            'approved' => ' Approve  Academy   Issue   .',
            'preparing' => '  Print    .',
            'issued' => ' Issue       No.',
            'rejected' => '   .         .'
        );
        return $descs[$status] ?? '    .';
    }

    public static function is_card_admin() {
        if (!is_user_logged_in()) return false;
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        return (
            current_user_can('manage_options') ||
            current_user_can('_No') ||
            current_user_can('_No') ||
            current_user_can('manage_students') ||
            in_array('administrator', $roles, true) ||
            in_array('sm_system_admin', $roles, true) ||
            in_array('sm_principal', $roles, true) ||
            in_array('sm_supervisor', $roles, true) ||
            in_array('sm_discipline_supervisor', $roles, true) ||
            in_array('sm_activities_supervisor', $roles, true)
        );
    }

    public function ajax_public_check_previous_request() {
        $query = sanitize_text_field($_POST['search_query'] ?? '');
        if (empty($query)) {
            wp_send_json_error('   National ID    .');
        }

        global $wpdb;
        $clean_q = trim($query);

        $req = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.national_id
             FROM {$wpdb->prefix}sm_exit_card_requests r
             JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id
             WHERE s.national_id = %s OR r.reference_no = %s OR r.id = %d
             ORDER BY r.id DESC LIMIT 1",
            $clean_q, $clean_q, intval($clean_q)
        ));

        if (!$req) {
            wp_send_json_error('          .');
        }

        $name_parts = explode(' ', trim($req->student_name));
        $first_name = $name_parts[0] ?? '';
        $last_name = end($name_parts);
        $display_stu_name = $first_name . ' ' . ($last_name && $last_name !== $first_name ? $last_name : '');

        wp_send_json_success(array(
            'reference_no' => $req->reference_no ?: ('EXT-' . date('Y') . '-' . $req->id),
            'student_name' => $display_stu_name ?: 'No/',
            'class_name' => $req->class_name ?: '-',
            'section' => $req->section ?: '-',
            'status' => $req->status,
            'status_label' => self::eess_get_exit_card_status_label($req->status),
            'status_desc' => self::eess_get_exit_card_status_desc($req->status),
            'created_at' => date_i18n('Y-m-d H:i', strtotime($req->created_at))
        ));
    }

    public function ajax_get_exit_card_request_details() {
        check_ajax_referer('sm_admin_action', 'nonce');
        if (!self::is_card_admin()) {
            wp_send_json_error(' No  No .');
        }

        $req_id = intval($_POST['request_id'] ?? 0);
        if (!$req_id) wp_send_json_error('   .');

        global $wpdb;
        $req = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.national_id FROM {$wpdb->prefix}sm_exit_card_requests r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE r.id = %d",
            $req_id
        ));

        if (!$req) wp_send_json_error('  .');

        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT id, reference_no, status, created_at FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id = %d AND academic_year = %s ORDER BY id DESC",
            $req->student_id, $req->academic_year
        ));

        wp_send_json_success(array(
            'id' => $req->id,
            'reference_no' => $req->reference_no ?: ('EXT-' . date('Y') . '-' . $req->id),
            'student_name' => $req->student_name,
            'student_code' => $req->student_code ?: ('STU-' . $req->student_id),
            'class_name' => $req->class_name,
            'section' => $req->section,
            'national_id' => $req->national_id ?: ' ',
            'parent_name' => $req->parent_name ?: ' ',
            'parent_phone' => $req->parent_phone ?: ' ',
            'reason' => $req->reason,
            'status' => $req->status,
            'status_label' => self::eess_get_exit_card_status_label($req->status),
            'declaration_accepted' => intval($req->declaration_accepted),
            'signature_data' => $req->signature_data ?: '',
            'admin_notes' => $req->admin_notes ?: '',
            'created_at' => date_i18n('Y-m-d H:i', strtotime($req->created_at)),
            'history' => $history
        ));
    }

    public function ajax_save_exit_card_settings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_POST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!self::is_card_admin()) {
            wp_send_json_error(' No  No .');
        }

        $portal_mode = sanitize_text_field($_POST['portal_mode'] ?? 'card_application');
        if (!in_array($portal_mode, array('card_application', 'update_only'), true)) {
            $portal_mode = 'card_application';
        }

        $verify_method = sanitize_text_field($_POST['verify_method'] ?? 'both');
        if (!in_array($verify_method, array('code', 'nat_id', 'both', 'name_only'), true)) {
            $verify_method = 'both';
        }

        $raw_req_fields = $_POST['required_fields'] ?? array('guardian_phone', 'dob');
        $required_fields = is_array($raw_req_fields) ? array_map('sanitize_text_field', $raw_req_fields) : array('guardian_phone', 'dob');

        $max_reqs = max(1, intval($_POST['max_requests'] ?? 3));
        $redirect = sanitize_text_field($_POST['redirect_discipline'] ?? 'yes');

        $service_update_data = isset($_POST['service_update_data']) ? sanitize_text_field($_POST['service_update_data']) : 'no';
        $service_exit_card   = isset($_POST['service_exit_card']) ? sanitize_text_field($_POST['service_exit_card']) : 'no';
        $service_complaint   = isset($_POST['service_complaint']) ? sanitize_text_field($_POST['service_complaint']) : 'no';
        $service_sports      = isset($_POST['service_sports']) ? sanitize_text_field($_POST['service_sports']) : 'no';

        $existing = get_option('sm_exit_card_settings', array());
        if (!is_array($existing)) $existing = array();

        $updated = array_merge($existing, array(
            'portal_mode' => $portal_mode,
            'verify_method' => $verify_method,
            'required_fields' => $required_fields,
            'max_requests' => $max_reqs,
            'redirect_discipline' => $redirect,
            'service_update_data' => $service_update_data,
            'service_exit_card' => $service_exit_card,
            'service_complaint' => $service_complaint,
            'service_sports' => $service_sports
        ));

        update_option('sm_exit_card_settings', $updated);

        wp_send_json_success(array('message' => ' Save    Update .'));
    }

    public static function eess_get_verification_status_label($vstatus) {
        $labels = array(
            'pending_verification' => '    Mother',
            'parent_confirmed'     => ' Confirm  Mother',
            'parent_not_confirmed' => '  Confirm'
        );
        return $labels[$vstatus] ?? '    Mother';
    }

    public static function normalize_uae_whatsapp_phone($phone) {
        if (empty($phone)) return '';
        $digits = preg_replace('/\D/', '', $phone);
        if (strpos($digits, '05') === 0) {
            $digits = '971' . substr($digits, 1);
        } elseif (strpos($digits, '5') === 0 && strlen($digits) === 9) {
            $digits = '971' . $digits;
        }
        return $digits;
    }

    public function ajax_manage_card_requests() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!self::is_card_admin()) {
            wp_send_json_error(' No  No .');
        }

        SM_DB::ensure_exit_card_requests_columns_exist();

        global $wpdb;
        $action_type = sanitize_text_field($_REQUEST['action_type'] ?? 'list');

        if ($action_type === 'list') {
            $requests = $wpdb->get_results(
                "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.photo_url, s.guardian_phone as stu_guardian_phone
                 FROM {$wpdb->prefix}sm_exit_card_requests r
                 LEFT JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id
                 ORDER BY r.id DESC LIMIT 50"
            );

            $formatted = array();
            foreach ($requests as $r) {
                $raw_phone = !empty($r->stu_guardian_phone) ? $r->stu_guardian_phone : $r->parent_phone;
                $wa_phone  = self::normalize_uae_whatsapp_phone($raw_phone);
                $vstatus   = !empty($r->verification_status) ? $r->verification_status : 'pending_verification';

                $formatted[] = array(
                    'id' => $r->id,
                    'reference_no' => $r->reference_no ?: ('EXT-' . date('Y') . '-' . $r->id),
                    'student_id' => $r->student_id,
                    'student_name' => $r->student_name ?: ' ',
                    'student_code' => $r->student_code ?: ('STU-' . $r->student_id),
                    'class_name' => $r->class_name ?: ' ',
                    'section' => $r->section ?: '-',
                    'photo_url' => $r->photo_url ?: '',
                    'parent_name' => $r->parent_name ?: '',
                    'parent_phone' => $raw_phone ?: '',
                    'wa_phone' => $wa_phone,
                    'status' => $r->status,
                    'status_label' => self::eess_get_exit_card_status_label($r->status),
                    'verification_status' => $vstatus,
                    'verification_label' => self::eess_get_verification_status_label($vstatus),
                    'created_at' => date_i18n('Y-m-d H:i', strtotime($r->created_at))
                );
            }
            wp_send_json_success($formatted);

        } elseif ($action_type === 'update_status') {
            $req_id = intval($_POST['request_id'] ?? 0);
            $new_status = sanitize_text_field($_POST['status'] ?? '');
            if (!$req_id || empty($new_status)) {
                wp_send_json_error('  .');
            }

            $wpdb->update("{$wpdb->prefix}sm_exit_card_requests", array('status' => $new_status), array('id' => $req_id));
            wp_send_json_success(array('message' => ' Update   .'));

        } elseif ($action_type === 'update_verification_status') {
            $req_id = intval($_POST['request_id'] ?? 0);
            $vstatus = sanitize_text_field($_POST['verification_status'] ?? 'pending_verification');
            if (!$req_id) {
                wp_send_json_error('   .');
            }

            $wpdb->update("{$wpdb->prefix}sm_exit_card_requests", array(
                'verification_status' => $vstatus,
                'verified_at' => current_time('mysql')
            ), array('id' => $req_id));

            wp_send_json_success(array(
                'message' => ' Update    Mother .',
                'verification_status' => $vstatus,
                'verification_label' => self::eess_get_verification_status_label($vstatus)
            ));

        } elseif ($action_type === 'delete') {
            $req_id = intval($_POST['request_id'] ?? 0);
            if (!$req_id) wp_send_json_error('   .');

            // Delete ONLY the request record from sm_exit_card_requests (preserving student record)
            $wpdb->delete("{$wpdb->prefix}sm_exit_card_requests", array('id' => $req_id));
            wp_send_json_success(array('message' => ' Delete         No.'));
        }

        wp_send_json_error('  .');
    }

    public function ajax_public_submit_complaint() {
        SM_DB::ensure_portal_tables_exist();

        $settings = get_option('sm_exit_card_settings', array());
        if (($settings['service_complaint'] ?? 'yes') === 'no') {
            wp_send_json_error('   No    .');
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        $title      = sanitize_text_field($_POST['title'] ?? '');
        $details    = sanitize_textarea_field($_POST['details'] ?? '');

        if (!$student_id) wp_send_json_error('  No  .');
        if (empty($title)) wp_send_json_error('   .');
        if (empty($details)) wp_send_json_error('   .');

        if (mb_strlen($details) > 1000) {
            $details = mb_substr($details, 0, 1000);
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error(' No  .');

        global $wpdb;
        $ref_no = 'CMP-' . date('Y') . '-' . rand(10000, 99999);

        $inserted = $wpdb->insert("{$wpdb->prefix}sm_complaints", array(
            'reference_no' => $ref_no,
            'student_id'   => $student_id,
            'title'        => $title,
            'details'      => $details,
            'status'       => 'submitted',
            'created_at'   => current_time('mysql')
        ));

        if ($inserted) {
            $cmp_id = $wpdb->insert_id;
            SM_Logger::log(' ', "    : $ref_no No: {$student->name}");
            wp_send_json_success(array(
                'complaint_id' => $cmp_id,
                'reference_no' => $ref_no,
                'message'      => '         .'
            ));
        } else {
            wp_send_json_error(' Save  .');
        }
    }

    public function ajax_public_check_complaint_status() {
        SM_DB::ensure_portal_tables_exist();
        $query = sanitize_text_field($_POST['search_query'] ?? '');
        if (empty($query)) {
            wp_send_json_error('   National ID   No    .');
        }

        global $wpdb;
        $clean_q = trim($query);

        $cmp = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, s.name as student_name, s.student_code, s.class_name, s.section, s.national_id
             FROM {$wpdb->prefix}sm_complaints c
             JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
             WHERE c.reference_no = %s OR s.national_id = %s OR s.student_code = %s OR c.id = %d
             ORDER BY c.id DESC LIMIT 1",
            $clean_q, $clean_q, $clean_q, intval($clean_q)
        ));

        if (!$cmp) {
            wp_send_json_error('        .');
        }

        $name_parts = explode(' ', trim($cmp->student_name));
        $first_name = $name_parts[0] ?? '';
        $last_name  = end($name_parts);
        $display_stu_name = $first_name . ' ' . ($last_name && $last_name !== $first_name ? $last_name : '');

        $status_labels = array(
            'submitted'    => '  ',
            'under_review' => '   ',
            'resolved'     => '   ',
            'rejected'     => ' Save  /  '
        );

        wp_send_json_success(array(
            'id'           => $cmp->id,
            'reference_no' => $cmp->reference_no,
            'student_name' => $display_stu_name,
            'title'        => $cmp->title,
            'details'      => $cmp->details,
            'status'       => $cmp->status,
            'status_label' => $status_labels[$cmp->status] ?? ' ',
            'admin_notes'  => $cmp->admin_notes ?: 'No  Notes    .',
            'created_at'   => date_i18n('Y-m-d H:i', strtotime($cmp->created_at))
        ));
    }

    public function ajax_manage_complaints() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!self::is_card_admin()) {
            wp_send_json_error(' No  No .');
        }

        SM_DB::ensure_portal_tables_exist();
        global $wpdb;
        $action_type = sanitize_text_field($_REQUEST['action_type'] ?? 'list');

        if ($action_type === 'list') {
            $complaints = $wpdb->get_results(
                "SELECT c.*, s.name as student_name, s.student_code, s.class_name, s.section, s.guardian_phone
                 FROM {$wpdb->prefix}sm_complaints c
                 LEFT JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
                 ORDER BY c.id DESC LIMIT 50"
            );

            $formatted = array();
            $status_labels = array(
                'submitted'    => '  ',
                'under_review' => '  ',
                'resolved'     => '  ',
                'rejected'     => ' Save / Reject'
            );

            foreach ($complaints as $c) {
                $formatted[] = array(
                    'id'           => $c->id,
                    'reference_no' => $c->reference_no,
                    'student_id'   => $c->student_id,
                    'student_name' => $c->student_name ?: ' ',
                    'student_code' => $c->student_code ?: '-',
                    'class_name'   => $c->class_name ?: '-',
                    'section'      => $c->section ?: '-',
                    'phone'        => $c->guardian_phone ?: '',
                    'title'        => $c->title,
                    'details'      => $c->details,
                    'status'       => $c->status,
                    'status_label' => $status_labels[$c->status] ?? ' ',
                    'admin_notes'  => $c->admin_notes ?: '',
                    'created_at'   => date_i18n('Y-m-d H:i', strtotime($c->created_at))
                );
            }
            wp_send_json_success($formatted);

        } elseif ($action_type === 'update_status') {
            $cmp_id = intval($_POST['complaint_id'] ?? 0);
            $new_status = sanitize_text_field($_POST['status'] ?? '');
            $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');

            if (!$cmp_id || empty($new_status)) {
                wp_send_json_error('  .');
            }

            $wpdb->update("{$wpdb->prefix}sm_complaints", array(
                'status' => $new_status,
                'admin_notes' => $admin_notes
            ), array('id' => $cmp_id));

            wp_send_json_success(array('message' => ' Update   Notes .'));

        } elseif ($action_type === 'delete') {
            $cmp_id = intval($_POST['complaint_id'] ?? 0);
            if (!$cmp_id) wp_send_json_error('   .');

            $wpdb->delete("{$wpdb->prefix}sm_complaints", array('id' => $cmp_id));
            wp_send_json_success(array('message' => ' Delete   .'));
        }

        wp_send_json_error('  .');
    }

    public function ajax_public_submit_sports_registration() {
        SM_DB::ensure_portal_tables_exist();

        $settings = get_option('sm_exit_card_settings', array());
        if (($settings['service_sports'] ?? 'yes') === 'no') {
            wp_send_json_error('  Sports Activities    .');
        }

        $student_id  = intval($_POST['student_id'] ?? 0);
        $raw_sports  = $_POST['sports'] ?? array();
        $sports      = is_array($raw_sports) ? array_map('sanitize_text_field', $raw_sports) : array();
        $verify_code = sanitize_text_field($_POST['verify_code'] ?? '');
        $dob_input   = sanitize_text_field($_POST['dob'] ?? '');

        if (!$student_id) wp_send_json_error('  No  Sports Activities.');
        if (empty($sports)) wp_send_json_error('      .');

        if (count($sports) > 2) {
            wp_send_json_error(':   No      .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) wp_send_json_error(' No  .');

        // Validate 3-factor verification if verify_code provided
        if (!empty($verify_code)) {
            $clean_input = strtolower(trim($verify_code));
            $stu_code    = strtolower(trim($student->student_code ?: ''));
            $nat_id      = strtolower(trim($student->national_id ?: ''));

            $code_matched = (!empty($stu_code) && $clean_input === $stu_code) || (!empty($nat_id) && $clean_input === $nat_id);

            $dob_matched = true;
            if (!empty($dob_input) && !empty($student->dob) && $student->dob !== '0000-00-00') {
                $formatted_dob = date('Y-m-d', strtotime($dob_input));
                $dob_matched   = ($formatted_dob === date('Y-m-d', strtotime($student->dob)));
            }

            if (!$code_matched || !$dob_matched) {
                wp_send_json_error('  (/National ID  Date of Birth)    No.');
            }
        }

        global $wpdb;
        $acad_year = '2026/2027';

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_sports_registrations WHERE student_id = %d AND academic_year = %s",
            $student_id, $acad_year
        ));

        $sports_json = json_encode($sports, JSON_UNESCAPED_UNICODE);

        if ($existing_id) {
            $wpdb->update("{$wpdb->prefix}sm_sports_registrations", array(
                'selected_sports' => $sports_json,
                'status' => 'registered'
            ), array('id' => $existing_id));
        } else {
            $wpdb->insert("{$wpdb->prefix}sm_sports_registrations", array(
                'student_id'      => $student_id,
                'academic_year'   => $acad_year,
                'selected_sports' => $sports_json,
                'status'          => 'registered',
                'created_at'      => current_time('mysql')
            ));
        }

        SM_Logger::log(' Active ', " /Update Sports Activities No: {$student->name} (" . implode(', ', $sports) . ")");
        wp_send_json_success(array('message' => '   No Sports Activities .'));
    }

    public function ajax_manage_sports_registrations() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'sm_admin_action') && !wp_verify_nonce($_REQUEST['nonce'] ?? '', 'eess_admin_action')) {
            wp_send_json_error('Security check failed');
        }
        if (!self::is_card_admin()) {
            wp_send_json_error(' No  No .');
        }

        SM_DB::ensure_portal_tables_exist();
        global $wpdb;
        $action_type = sanitize_text_field($_REQUEST['action_type'] ?? 'list');

        if ($action_type === 'list') {
            $regs = $wpdb->get_results(
                "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section
                 FROM {$wpdb->prefix}sm_sports_registrations r
                 LEFT JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id
                 ORDER BY r.id DESC LIMIT 100"
            );

            $formatted = array();
            foreach ($regs as $r) {
                $sports = json_decode($r->selected_sports, true) ?: array();
                $formatted[] = array(
                    'id'           => $r->id,
                    'student_id'   => $r->student_id,
                    'student_name' => $r->student_name ?: ' ',
                    'student_code' => $r->student_code ?: '-',
                    'class_name'   => $r->class_name ?: '-',
                    'section'      => $r->section ?: '-',
                    'sports'       => $sports,
                    'sports_label' => implode(' + ', $sports),
                    'status'       => $r->status,
                    'created_at'   => date_i18n('Y-m-d H:i', strtotime($r->created_at))
                );
            }
            wp_send_json_success($formatted);

        } elseif ($action_type === 'update_status') {
            $reg_id = intval($_POST['registration_id'] ?? 0);
            $new_status = sanitize_text_field($_POST['status'] ?? '');

            if (!$reg_id || empty($new_status)) {
                wp_send_json_error('  .');
            }

            $wpdb->update("{$wpdb->prefix}sm_sports_registrations", array('status' => $new_status), array('id' => $reg_id));
            wp_send_json_success(array('message' => ' Update   .'));

        } elseif ($action_type === 'delete') {
            $reg_id = intval($_POST['registration_id'] ?? 0);
            if (!$reg_id) wp_send_json_error('   .');

            $wpdb->delete("{$wpdb->prefix}sm_sports_registrations", array('id' => $reg_id));
            wp_send_json_success(array('message' => ' Delete   Sport Activity .'));
        }

        wp_send_json_error('  .');
    }

    public static function is_portal_token_valid($token) {
        $expected = wp_hash('eess_portal_session_202620272028_' . date('Y-m-d'));
        $expected_prev = wp_hash('eess_portal_session_202620272028_' . date('Y-m-d', strtotime('-1 day')));
        return (!empty($token) && ($token === $expected || $token === $expected_prev));
    }

    public function ajax_public_verify_portal_password() {
        $pwd = sanitize_text_field($_POST['password'] ?? '');
        if ($pwd !== '202620272028') {
            wp_send_json_error('    .');
        }

        $token = wp_hash('eess_portal_session_202620272028_' . date('Y-m-d'));
        wp_send_json_success(array(
            'message' => '       .',
            'token'   => $token
        ));
    }

    public function ajax_public_upload_student_photo() {
        $token      = sanitize_text_field($_POST['portal_token'] ?? '');
        $student_id = intval($_POST['student_id'] ?? 0);

        if (!self::is_portal_token_valid($token)) {
            wp_send_json_error('     Expired.      .');
        }

        if (!$student_id) {
            wp_send_json_error(' No  .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error(' No    .');
        }

        if (empty($_FILES['student_photo']['name'])) {
            wp_send_json_error('    No  .');
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $file_info = wp_check_filetype_and_ext($_FILES['student_photo']['tmp_name'], $_FILES['student_photo']['name']);
        $allowed_mimes = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');

        if (!in_array($file_info['type'], $allowed_mimes) && !array_key_exists($file_info['ext'], $allowed_mimes)) {
            wp_send_json_error('   .     JPG  PNG  WEBP.');
        }

        if ($_FILES['student_photo']['size'] > 5 * 1024 * 1024) {
            wp_send_json_error('       (5 ).');
        }

        $attachment_id = media_handle_upload('student_photo', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(' Save : ' . $attachment_id->get_error_message());
        }

        $attached_file = get_attached_file($attachment_id);
        if ($attached_file && file_exists($attached_file)) {
            // Compress & Resize image to max 800x800 @ 82% quality for lightweight storage & fast loading
            $editor = wp_get_image_editor($attached_file);
            if (!is_wp_error($editor)) {
                $editor->resize(800, 800, false);
                $editor->set_quality(82);
                $editor->save($attached_file);
            }
        }

        $new_photo_url = wp_get_attachment_url($attachment_id);
        $now_time      = current_time('mysql');
        $formatted_time = date_i18n('Y-m-d h:i A', strtotime($now_time));

        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_students", array('photo_url' => $new_photo_url), array('id' => $student_id));
        SM_DB::update_student_meta($student_id, 'photo_updated_at', $now_time);
        wp_cache_flush();

        if (!empty($student->parent_user_id)) {
            update_user_meta($student->parent_user_id, 'eess_profile_photo', $new_photo_url);
        }

        SM_Logger::log('  No', " /Update   No: {$student->name} (ID: {$student_id})");

        wp_send_json_success(array(
            'message'          => '  Update  No .',
            'photo_url'        => $new_photo_url,
            'photo_updated_at' => $formatted_time
        ));
    }

    public function ajax_public_submit_exit_card_instant() {
        $token      = sanitize_text_field($_POST['portal_token'] ?? '');
        $student_id = intval($_POST['student_id'] ?? 0);

        if (!self::is_portal_token_valid($token)) {
            wp_send_json_error('     Expired.      .');
        }

        if (!$student_id) {
            wp_send_json_error(' No  .');
        }

        $student = SM_DB::get_student_by_id($student_id);
        if (!$student) {
            wp_send_json_error(' No  .');
        }

        global $wpdb;
        SM_DB::ensure_portal_tables_exist();

        $acad_year = '2025/2026';

        // Check if student already has an active request
        $existing_req = $wpdb->get_row($wpdb->prepare(
            "SELECT id, reference_no, status, created_at FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id = %d AND academic_year = %s AND status NOT IN ('cancelled', 'rejected') ORDER BY id DESC LIMIT 1",
            $student_id, $acad_year
        ));

        if ($existing_req) {
            $photo_updated_at = SM_DB::get_student_meta($student_id, 'photo_updated_at', true);
            $photo_time_formatted = !empty($photo_updated_at) ? date_i18n('Y-m-d h:i A', strtotime($photo_updated_at)) : ' ';

            wp_send_json_success(array(
                'message'          => '    Active   No.',
                'reference_no'     => $existing_req->reference_no,
                'request_id'       => $existing_req->id,
                'status'           => $existing_req->status,
                'status_label'     => self::eess_get_exit_card_status_label($existing_req->status),
                'student_name'     => $student->name,
                'student_code'     => $student->student_code ?: ('STU-' . $student->id),
                'class_name'       => $student->class_name,
                'section'          => $student->section,
                'photo_url'        => $student->photo_url,
                'photo_updated_at' => $photo_time_formatted,
                'requested_at'     => date_i18n('Y-m-d h:i A', strtotime($existing_req->created_at)),
                'already_exists'   => true
            ));
        }

        $ref_no = 'EX-' . date('Y') . '-' . sprintf('%06d', rand(1000, 999999));

        $inserted = $wpdb->insert("{$wpdb->prefix}sm_exit_card_requests", array(
            'reference_no'        => $ref_no,
            'student_id'          => $student_id,
            'parent_name'         => ($student->guardian_name ?? '') ?: ($student->name . ' ( )'),
            'parent_phone'        => ($student->guardian_phone ?? '') ?: '+971500000000',
            'academic_year'       => $acad_year,
            'reason'              => '   No   ',
            'requested_date'      => current_time('Y-m-d'),
            'status'              => 'submitted',
            'verification_status' => 'verified_by_portal',
            'verified_at'         => current_time('mysql'),
            'created_at'          => current_time('mysql')
        ));

        if (!$inserted) {
            wp_send_json_error('      .');
        }

        $req_id = $wpdb->insert_id;
        $photo_updated_at = SM_DB::get_student_meta($student_id, 'photo_updated_at', true);
        $photo_time_formatted = !empty($photo_updated_at) ? date_i18n('Y-m-d h:i A', strtotime($photo_updated_at)) : '';

        SM_Logger::log('   ', "      No: {$student->name} ( : {$ref_no})");

        wp_send_json_success(array(
            'message'          => '       .',
            'reference_no'     => $ref_no,
            'request_id'       => $req_id,
            'status'           => 'submitted',
            'status_label'     => '  No',
            'student_name'     => $student->name,
            'student_code'     => $student->student_code ?: ('STU-' . $student->id),
            'class_name'       => $student->class_name,
            'section'          => $student->section,
            'photo_url'        => $student->photo_url,
            'photo_updated_at' => $photo_time_formatted,
            'requested_at'     => date_i18n('Y-m-d h:i A', strtotime(current_time('mysql'))),
            'already_exists'   => false
        ));
    }

    public function ajax_public_withdraw_exit_card_request() {
        $token      = sanitize_text_field($_POST['portal_token'] ?? '');
        $student_id = intval($_POST['student_id'] ?? 0);
        $req_id     = intval($_POST['request_id'] ?? 0);

        if (!self::is_portal_token_valid($token)) {
            wp_send_json_error('     Expired.');
        }

        if (!$student_id) {
            wp_send_json_error(' No  .');
        }

        global $wpdb;
        SM_DB::ensure_portal_tables_exist();

        if ($req_id > 0) {
            $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_exit_card_requests WHERE id = %d AND student_id = %d", $req_id, $student_id));
        } else {
            $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_exit_card_requests WHERE student_id = %d AND status NOT IN ('cancelled', 'rejected') ORDER BY id DESC LIMIT 1", $student_id));
        }

        if (!$req) {
            wp_send_json_error('       Active  No .');
        }

        // Block withdrawal if request status is printing or issued
        if (in_array($req->status, array('printing', 'issued', 'printed'))) {
            wp_send_json_error(' No    Cancel     Print Print .');
        }

        $wpdb->update("{$wpdb->prefix}sm_exit_card_requests", array('status' => 'cancelled'), array('id' => $req->id));

        $student = SM_DB::get_student_by_id($student_id);
        SM_Logger::log('   ', "  Cancel    (: {$req->reference_no}) No: " . ($student->name ?? ''));

        wp_send_json_success(array(
            'message'      => '  Cancel    .',
            'reference_no' => $req->reference_no
        ));
    }
}

class Sportedia_Public extends SM_Public {}
