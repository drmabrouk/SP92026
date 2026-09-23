<?php

class SM_Logger {
    public static function log($action, $details = '') {
        global $wpdb;
        $user_id = get_current_user_id();

        // Ensure sm_logs table columns exist
        $table_name = "{$wpdb->prefix}sm_logs";
        $col_check = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'device_source'");
        if (empty($col_check)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN device_source VARCHAR(20) DEFAULT 'desktop'");
        }

        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        $is_mobile = wp_is_mobile() || preg_match('/(android|bb\d+|meego).+mobile|blackberry|iphone|ipad|ipod|opera mini|iemobile|mobile|palm|phone|pocket|psp|symbian|up\.browser|up\.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|silk|mobile)/i', $user_agent);
        $device_source = $is_mobile ? 'mobile' : 'desktop';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'details' => sanitize_textarea_field($details),
                'device_source' => $device_source,
                'created_at' => current_time('mysql')
            )
        );

        // Strictly retain a maximum of 100 entries, purging older records
        $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        if ($count > 100) {
            $limit = $count - 100;
            $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} ORDER BY id ASC LIMIT %d", $limit));
        }
    }

    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name, u.user_login FROM {$wpdb->prefix}sm_logs l LEFT JOIN {$wpdb->base_prefix}users u ON l.user_id = u.ID ORDER BY l.id DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        $role_map = array(
            'administrator' => 'الإدارة المركزية (المطور)',
            'sm_system_admin' => 'مدير النظام التقني',
            'sm_principal' => 'مدير المدرسة',
            'sm_supervisor' => 'مشرف تربوي',
            'sm_coordinator' => 'منسق مادة',
            'sm_hod' => 'رئيس قسم',
            'sm_teacher' => 'معلم',
            'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
            'sm_activities_supervisor' => 'مشرف أنشطة',
            'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
            'sm_bus_supervisor' => 'مشرف حافلة',
            'sm_clinic' => 'العيادة المدرسية',
            'sm_hr' => 'الموارد البشرية (HR)'
        );

        $arabic_days = array(
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        );

        if (!empty($logs)) {
            foreach ($logs as &$log) {
                $uid = intval($log->user_id);
                $u_obj = get_userdata($uid);

                if ($u_obj) {
                    $log->display_name = $u_obj->display_name;
                    $log->user_login   = $u_obj->user_login;
                    $roles             = (array) $u_obj->roles;
                    $primary_role      = reset($roles) ?: '';
                    $log->role_label   = $role_map[$primary_role] ?? $primary_role;
                } else {
                    $log->display_name = $log->display_name ?: 'مستخدم بالنظام';
                    $log->role_label   = 'إدارة المنظومة';
                }

                $log->employee_number = get_user_meta($uid, 'eess_employee_number', true) ?: (get_user_meta($uid, 'employee_id', true) ?: 'غير محدد');
                $log->department      = get_user_meta($uid, 'eess_department', true) ?: (get_user_meta($uid, 'department', true) ?: 'عام');
                $log->school_name     = get_user_meta($uid, 'eess_school_name', true) ?: 'المؤسسة الرئيسية';
                $log->profile_photo   = get_user_meta($uid, 'eess_profile_photo', true) ?: get_avatar_url($uid, array('size' => 48));

                $timestamp            = strtotime($log->created_at ?: current_time('mysql'));
                $eng_day              = date('l', $timestamp);
                $log->day_ar          = $arabic_days[$eng_day] ?? $eng_day;
                $log->formatted_date  = date('Y-m-d', $timestamp);
                $log->formatted_time  = date('h:i A', $timestamp);
                $log->device_source   = !empty($log->device_source) ? $log->device_source : 'desktop';
            }
        }

        return $logs;
    }

    public static function get_total_logs() {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_logs");
    }
}
