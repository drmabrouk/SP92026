<?php

class SM_Settings {
    public static function get_violation_types() {
        $default = array(
            'behavior' => '',
            'lateness' => '',
            'absence' => '',
            'other' => ''
        );
        return get_option('sm_violation_types', $default);
    }

    public static function get_severities() {
        return array(
            'low' => '',
            'medium' => 'Intermediate',
            'high' => ''
        );
    }

    public static function save_violation_types($types) {
        update_option('sm_violation_types', $types);
    }

    public static function get_appearance() {
        $default = array(
            // Primary Brand Colors
            'primary_color' => '#8B0000',      // Primary Dark Red
            'primary_hover' => '#6F0000',      // Dark Red Hover
            'danger_color'  => '#C62828',      // Global Danger Red
            'danger_hover'  => '#A61B1B',
            'black_color'   => '#000000',
            'white_color'   => '#FFFFFF',

            // Complete Grayscale Palette
            'gray_50'  => '#F8F8F8',
            'gray_100' => '#F5F5F5',
            'gray_200' => '#EEEEEE',
            'gray_300' => '#E0E0E0',
            'gray_400' => '#BDBDBD',
            'gray_500' => '#9E9E9E',
            'gray_600' => '#757575',
            'gray_700' => '#424242',
            'gray_800' => '#212121',
            'gray_900' => '#000000',

            // Pastel Status Colors
            'pastel_red_bg'     => '#FDECEC',
            'pastel_red_text'   => '#C62828',
            'pastel_green_bg'   => '#EAF7EE',
            'pastel_green_text' => '#2E7D32',
            'pastel_blue_bg'    => '#EAF3FB',
            'pastel_blue_text'  => '#1565C0',
            'pastel_yellow_bg'  => '#FFF8E1',
            'pastel_yellow_text'=> '#B77900',
            'pastel_gray_bg'    => '#F5F5F5',
            'pastel_gray_text'  => '#616161',

            // Global Component Radii
            'button_radius' => '9999px',
            'card_radius'   => '20px',
            'field_radius'  => '9999px',
            'modal_radius'  => '20px',

            // Legacy Fallbacks
            'font_size'       => '15px',
            'secondary_color' => '#424242',
            'accent_color'    => '#8B0000',
            'dark_color'      => '#212121',
            'border_radius'   => '12px'
        );
        return wp_parse_args(get_option('sm_appearance', array()), $default);
    }

    public static function save_appearance($data) {
        update_option('sm_appearance', $data);
    }

    public static function get_notifications() {
        $default = array(
            'email_subject' => '   No: {student_name}',
            'email_template' => "  No  No: {student_name}\n : {type}\n: {severity}\n: {details}\n : {action_taken}",
            'whatsapp_template' => "  Academy :   No   No {student_name}.  No: {type}. : {details}. : {action_taken}",
            'internal_template' => " :    {type} No {student_name}.    No."
        );
        return get_option('sm_notification_settings', $default);
    }

    public static function save_notifications($data) {
        update_option('sm_notification_settings', $data);
    }

    public static function get_school_info() {
        $default = array(
            'school_name' => 'Sportedia Sports Management System',
            'school_principal_name' => ' ',
            'school_logo' => '',
            'address' => '   ',
            'email' => 'info@eess.online',
            'phone' => '0123456789',
            'working_schedule' => array(
                'staff' => array('mon', 'tue', 'wed', 'thu', 'fri'),
                'students' => array('mon', 'tue', 'wed', 'thu')
            ),
            'map_link' => '',
            'extra_details' => ''
        );
        return get_option('sm_school_info', $default);
    }

    public static function save_school_info($data) {
        update_option('sm_school_info', $data);
    }

    public static function get_academic_structure() {
        $default = array(
            'academic_year' => '2027/2026',
            'terms_count' => 3,
            'term_dates' => array(
                'term1' => array('start' => '', 'end' => '', 'deadline' => ''),
                'term2' => array('start' => '', 'end' => '', 'deadline' => ''),
                'term3' => array('start' => '', 'end' => '', 'deadline' => '')
            ),
            'grades_count' => 12,
            'active_grades' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
            'grade_sections' => array(), // Per-grade sections: [grade_num => [count => 5, letters => ", ..."]]
            'sections_count' => 5,
            'section_letters' => ", , , , ",
            'academic_stages' => array(
                array('name' => ' No', 'start' => 1, 'end' => 4),
                array('name' => ' Intermediate', 'start' => 5, 'end' => 8),
                array('name' => ' ', 'start' => 9, 'end' => 12)
            )
        );
        return wp_parse_args(get_option('sm_academic_structure', array()), $default);
    }

    public static function save_academic_structure($data) {
        update_option('sm_academic_structure', $data);
    }

    /**
     * Standardized Naming for Grades and Sections
     */
    public static function format_grade_name($grade, $section = '', $format = 'full') {
        if (empty($grade)) return '---';

        // Remove "Training Group" prefix if it exists in data
        $grade_num = str_replace('Training Group ', '', $grade);

        if ($format === 'short') {
            return trim($grade_num . ' ' . $section);
        }

        // Full format: "Grade + Number + Section"
        $output = 'Training Group ' . $grade_num;
        if (!empty($section)) {
            $output .= '  ' . $section;
        }
        return $output;
    }

    public static function get_retention_settings() {
        $default = array(
            'message_retention_days' => 90
        );
        return get_option('sm_retention_settings', $default);
    }

    public static function save_retention_settings($data) {
        update_option('sm_retention_settings', $data);
    }

    public static function record_backup_download() {
        update_option('sm_last_backup_download', current_time('mysql'));
    }

    public static function record_backup_import() {
        update_option('sm_last_backup_import', current_time('mysql'));
    }

    public static function get_last_backup_info() {
        return array(
            'export' => get_option('sm_last_backup_download', '  Export '),
            'import' => get_option('sm_last_backup_import', '  Import ')
        );
    }

    public static function get_subjects() {
        if (class_exists('SM_DB')) {
            $subjs = SM_DB::get_subjects();
            $out = array();
            if (!empty($subjs) && is_array($subjs)) {
                foreach ($subjs as $s) {
                    if (isset($s->id) && isset($s->name)) {
                        $out[$s->id] = $s->name;
                    }
                }
            }
            if (!empty($out)) return $out;
        }
        return array(
            'arabic' => ' ',
            'english' => ' ',
            'math' => '',
            'science' => '',
            'islamic' => ' No',
            'social' => ' No',
            'pe' => '  ',
            'art' => ' ',
            'music' => ' ',
            'computer' => ' '
        );
    }

    public static function get_departments() {
        return array(
            'academic' => ' Academy ',
            'hr' => '   (HR)',
            'student_affairs' => 'Player Affairs No',
            'activities' => 'Active  ',
            'finance' => '  ',
            'services' => '  ',
            'medical' => '  '
        );
    }

    public static function get_suggested_actions() {
        $default = array(
            'low' => " \n No\n ",
            'medium' => " \n  \n  ",
            'high' => " \n \n  "
        );
        return get_option('sm_suggested_actions', $default);
    }

    public static function save_suggested_actions($actions) {
        update_option('sm_suggested_actions', $actions);
    }

    public static function get_disciplinary_actions() {
        return array(
            1 => ' ',
            2 => ' ',
            3 => ' ',
            4 => '  ',
            5 => '  ',
            6 => '   ',
            7 => ' ',
            8 => ' '
        );
    }

    public static function get_hierarchical_violations() {
        $default = array(
            1 => array(
                '1.1' => array('name' => '   ', 'points' => 1, 'action' => ' '),
                '1.2' => array('name' => '    ', 'points' => 1, 'action' => ' '),
                '1.3' => array('name' => ' No    ', 'points' => 2, 'action' => ' No'),
                '1.4' => array('name' => '     ', 'points' => 2, 'action' => ' No'),
                '1.5' => array('name' => '     ', 'points' => 1, 'action' => ' '),
                '1.6' => array('name' => '   Training Group', 'points' => 2, 'action' => ' '),
                '1.7' => array('name' => '   ', 'points' => 1, 'action' => ' '),
                '1.8' => array('name' => '     ', 'points' => 1, 'action' => ' '),
                '1.9' => array('name' => '    ', 'points' => 3, 'action' => ' Sport Activity'),
                '1.10' => array('name' => '   ', 'points' => 2, 'action' => ' '),
                '1.11' => array('name' => '     ', 'points' => 2, 'action' => ' '),
            ),
            2 => array(
                '2.1' => array('name' => '  Academy    ', 'points' => 4, 'action' => '    '),
                '2.2' => array('name' => '    Training Group  ', 'points' => 3, 'action' => ' '),
                '2.3' => array('name' => '  Active  ', 'points' => 3, 'action' => ' '),
                '2.4' => array('name' => '    ', 'points' => 5, 'action' => '   '),
                '2.5' => array('name' => '     Academy ', 'points' => 4, 'action' => '   No'),
                '2.6' => array('name' => '     ', 'points' => 5, 'action' => 'No   '),
                '2.7' => array('name' => '   No  No', 'points' => 4, 'action' => '  '),
            ),
            3 => array(
                '3.1' => array('name' => '   /', 'points' => 10, 'action' => '   '),
                '3.2' => array('name' => '  No   ', 'points' => 8, 'action' => 'Cancel   '),
                '3.3' => array('name' => '  Academy    ', 'points' => 12, 'action' => '    '),
                '3.6' => array('name' => '  Academy   ', 'points' => 15, 'action' => '    '),
                '3.7' => array('name' => ' No No   ', 'points' => 15, 'action' => '    '),
                '3.8' => array('name' => '     ', 'points' => 12, 'action' => '   '),
                '3.9' => array('name' => '   (  )', 'points' => 10, 'action' => ' Sport Activity  '),
                '3.10' => array('name' => '  Academy   ', 'points' => 10, 'action' => 'Delete   '),
                '3.11' => array('name' => '     ', 'points' => 10, 'action' => '    '),
            ),
            4 => array(
                '4.1' => array('name' => 'No    ', 'points' => 20, 'action' => '    '),
                '4.2' => array('name' => '      ', 'points' => 25, 'action' => '   '),
                '4.3' => array('name' => ' No   ', 'points' => 25, 'action' => '   '),
                '4.4' => array('name' => '  NoNo   ', 'points' => 20, 'action' => '   '),
                '4.5' => array('name' => '    Academy ', 'points' => 20, 'action' => 'Upload   '),
                '4.6' => array('name' => 'No    No  ', 'points' => 25, 'action' => '    '),
                '4.7' => array('name' => '    ', 'points' => 30, 'action' => '   '),
                '4.10' => array('name' => '    ', 'points' => 30, 'action' => '    '),
                '4.11' => array('name' => '    ', 'points' => 20, 'action' => '   '),
                '4.12' => array('name' => '    No ', 'points' => 15, 'action' => '   '),
                '4.13' => array('name' => '     ', 'points' => 30, 'action' => '  No '),
                '4.14' => array('name' => '     Academy ', 'points' => 30, 'action' => '    '),
            )
        );
        return get_option('sm_hierarchical_violations', $default);
    }

    public static function save_hierarchical_violations($data) {
        update_option('sm_hierarchical_violations', $data);
    }

    /**
     * Fetch Regulation Dynamic Info by Code
     */
    public static function get_regulation_by_code($code) {
        if (empty($code)) return false;
        $h_violations = self::get_hierarchical_violations();
        foreach ($h_violations as $level => $items) {
            if (isset($items[$code])) {
                return $items[$code];
            }
        }
        return false;
    }

    public static function get_class_security_codes() {
        $codes = get_option('sm_class_security_codes', array());
        return $codes;
    }

    public static function get_class_security_code($grade, $section) {
        $codes = self::get_class_security_codes();
        $key = $grade . '|' . $section;

        if (!isset($codes[$key])) {
            return self::reset_class_security_code($grade, $section);
        }

        return $codes[$key];
    }

    public static function reset_class_security_code($grade, $section) {
        $codes = self::get_class_security_codes();
        $key = $grade . '|' . $section;
        $new_code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $codes[$key] = $new_code;
        update_option('sm_class_security_codes', $codes);
        return $new_code;
    }

    public static function get_sections_from_db() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT DISTINCT class_name, section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY class_name ASC, section ASC");

        $structure = array();
        foreach ($results as $row) {
            $grade_num = (int)str_replace('Training Group ', '', $row->class_name);
            if (!isset($structure[$grade_num])) {
                $structure[$grade_num] = array();
            }
            if (!in_array($row->section, $structure[$grade_num])) {
                $structure[$grade_num][] = $row->section;
            }
        }

        // Sort sections alphabetically for each grade
        foreach ($structure as $grade => $sections) {
            sort($structure[$grade]);
        }

        return $structure;
    }

    public static function get_timetable_settings() {
        $default = array(
            'periods' => 8,
            'days' => array('sun', 'mon', 'tue', 'wed', 'thu')
        );
        return get_option('sm_timetable_settings', $default);
    }

    public static function save_timetable_settings($data) {
        update_option('sm_timetable_settings', $data);
    }

    public static function get_system_modules() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        // Educational staff role check: Teacher, Supervisor, Head of Department, Activities Supervisor
        $is_educational_role = in_array('sm_teacher', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles);

        $modules = array(
            'summary' => array(
                'label' => ' ',
                'dashicon' => 'dashicons-dashboard',
                'tab' => 'summary',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => true,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => true,
                )
            ),
            'lesson-plans' => array(
                'label' => '     ',
                'dashicon' => 'dashicons-welcome-write-blog',
                'tab' => 'lesson-plans',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'term-plans' => array(
                'label' => ' Training Group Annual',
                'dashicon' => 'dashicons-calendar-alt',
                'tab' => 'term-plans',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'attendance' => array(
                'label' => '  ',
                'dashicon' => 'dashicons-calendar-alt',
                'tab' => 'attendance',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'students' => array(
                'label' => 'Player Affairs',
                'dashicon' => 'dashicons-groups',
                'tab' => 'students',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'stats' => array(
                'label' => '  No',
                'dashicon' => 'dashicons-list-view',
                'tab' => 'stats',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => true,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => false,
                )
            ),
            'grades' => array(
                'label' => '  ',
                'dashicon' => 'dashicons-welcome-learn-more',
                'tab' => 'grades',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => true,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'teachers' => array(
                'label' => '  ',
                'dashicon' => 'dashicons-admin-users',
                'tab' => 'teachers',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => false,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'hr-evaluation' => array(
                'label' => '    ',
                'dashicon' => 'dashicons-awards',
                'tab' => 'hr-evaluation',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => true,
                )
            ),
            'hr-management' => array(
                'label' => '  ',
                'dashicon' => 'dashicons-id-alt',
                'tab' => 'hr-management',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => false,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => true,
                )
            ),
            'documents' => array(
                'label' => '  ',
                'dashicon' => 'dashicons-media-document',
                'tab' => 'documents',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => false,
                )
            ),
            'clinic' => array(
                'label' => ' ',
                'dashicon' => 'dashicons-heart',
                'tab' => 'clinic',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'global-settings' => array(
                'label' => 'System Settings',
                'dashicon' => 'dashicons-admin-generic',
                'tab' => 'global-settings',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => false,
                    'sm_supervisor' => false,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'employee-profile' => array(
                'label' => ' ',
                'dashicon' => 'dashicons-businessman',
                'tab' => 'employee-profile',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => true,
                )
            ),
        );

        return $modules;
    }

    public static function user_has_module_capability($key, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || user_can($user_id, 'manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);
        $is_student = in_array('sm_student', $roles);
        $is_parent = in_array('sm_parent', $roles);
        $is_clinic = in_array('sm_clinic', $roles);

        if ($is_admin || $is_sys_admin) {
            return true;
        }

        if ($key === 'global-settings') {
            return false;
        }

        // The "Customize Sidebar Section Visibility by Role" settings are the central source of truth.
        return self::is_section_visible($key, $user_id);
    }

    public static function is_section_visible($section, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $roles = (array) $user->roles;
        if (in_array('administrator', $roles)) {
            return true;
        }

        $role = !empty($roles) ? $roles[0] : '';
        $visibility = self::get_sidebar_visibility();

        if (isset($visibility[$role][$section])) {
            return (bool) $visibility[$role][$section];
        }

        // Fallback to default
        $modules = self::get_system_modules();
        if (isset($modules[$section]['default'][$role])) {
            return (bool) $modules[$section]['default'][$role];
        }

        return false;
    }

    public static function is_ajax_action_allowed($action, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Strip prefix if any
        $clean_action = str_replace(array('nopriv_'), '', $action);

        // Public/nopriv actions allowed for non-logged-in users
        $public_actions = array(
            'sm_get_students_attendance_ajax',
            'sm_save_attendance_ajax',
            'sm_save_attendance_batch_ajax',
            'eess_forgot_verify_identity',
            'eess_forgot_set_password',
            'eess_register_submit',
            'sm_verify_employee_id',
            'sm_submit_mobile_lesson',
            'sm_get_pending_announcements',
            'sm_public_search_student',
            'sm_public_verify_student',
            'sm_public_update_student_missing_data',
            'sm_public_submit_exit_card',
            'sm_submit_exit_card_request',
            'sm_public_check_previous_request',
            'sm_public_submit_complaint',
            'sm_public_check_complaint_status',
            'sm_public_submit_sports_registration',
            'sm_public_verify_portal_password',
            'sm_public_upload_student_photo',
            'sm_public_submit_exit_card_instant',
            'sm_public_withdraw_exit_card_request'
        );

        if (in_array($clean_action, $public_actions)) {
            return true;
        }

        if (!$user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $roles = (array) $user->roles;
        if (in_array('administrator', $roles) || in_array('sm_system_admin', $roles)) {
            return true;
        }

        // Actions allowed for all authenticated users
        $universal_authenticated_actions = array(
            'sm_get_pending_announcements',
            'sm_mark_announcement_viewed',
            'sm_mark_announcement_closed',
            'eess_submit_support_request',
            'eess_update_support_status',
            'eess_delete_support_request',
            'sm_update_profile_ajax',
            'sm_get_counts_ajax',
            'sm_refresh_dashboard'
        );
        if (in_array($clean_action, $universal_authenticated_actions)) {
            return true;
        }

        // Map AJAX actions to section keys
        $action_map = array(
            // Students
            'sm_get_student' => 'students',
            'sm_search_students' => 'students',
            'sm_get_student_intelligence' => 'students',
            'sm_add_student_ajax' => 'students',
            'sm_update_student_ajax' => 'students',
            'sm_delete_student_ajax' => 'students',
            'sm_bulk_delete_students_ajax' => 'students',
            'sm_upload_import_csv' => 'students',
            'sm_process_import_chunk' => 'students',
            'sm_export_students_csv' => 'students',
            'sm_download_student_import_template' => 'students',
            'sm_print_student_full_report' => 'students',
            'sm_update_student_photo' => 'students',
            'sm_update_exit_card_request_status' => 'students',
            'sm_get_exit_card_request_details' => 'students',
            'sm_save_exit_card_settings' => 'students',

            // Behavior / Stats
            'sm_filter_violations' => 'stats',
            'sm_mark_contacted' => 'stats',
            'sm_export_violations_csv' => 'stats',
            'sm_save_record_ajax' => 'stats',
            'sm_update_record_status' => 'stats',
            'sm_delete_record_ajax' => 'stats',
            'sm_submit_behavior_referral' => 'stats',

            // Teachers / Users
            'sm_add_user_ajax' => 'teachers',
            'sm_update_generic_user_ajax' => 'teachers',
            'sm_add_teacher_ajax' => 'teachers',
            'sm_update_teacher_ajax' => 'teachers',
            'sm_bulk_delete_users_ajax' => 'teachers',
            'eess_approve_user' => 'teachers',
            'eess_reject_user' => 'teachers',
            'eess_save_user_notes' => 'teachers',
            'sm_export_users_csv' => 'teachers',
            'eess_check_user_uniqueness' => 'teachers',
            'eess_get_user_unified' => 'teachers',
            'eess_save_user_unified' => 'teachers',
            'eess_get_user_assignments' => 'teachers',

            // Parents
            'sm_add_parent_ajax' => 'parents',
            'eess_send_quick_parent_note' => 'parents',

            // Grades
            'sm_save_grade_ajax' => 'grades',
            'eess_import_grades_ajax' => 'grades',
            'sm_get_student_grades_ajax' => 'grades',
            'sm_delete_grade_ajax' => 'grades',
            'sm_add_subject' => 'grades',
            'sm_delete_subject' => 'grades',
            'sm_get_subjects' => 'grades',
            'sm_save_class_grades' => 'grades',
            'sm_export_grades_csv' => 'grades',
            'sm_import_grades_csv' => 'grades',

            // Attendance
            'sm_get_students_attendance_ajax' => 'attendance',
            'sm_save_attendance_ajax' => 'attendance',
            'sm_save_attendance_batch_ajax' => 'attendance',
            'sm_reset_class_code_ajax' => 'attendance',
            'sm_toggle_attendance_status_ajax' => 'attendance',

            // HR Management
            'eess_hr_add_employee' => 'hr-management',
            'eess_bulk_import_employees_ajax' => 'hr-management',
            'sm_verify_employee_id' => 'hr-management',

            // Lesson plans
            'sm_download_plans_zip' => 'lesson-plans',
            'eess_quick_approve_prep' => 'lesson-plans',
            'eess_bulk_lesson_action' => 'lesson-plans',
            'sm_submit_mobile_lesson' => 'lesson-plans',
            'sm_get_educational_suggestions' => 'lesson-plans',
            'sm_save_educational_input' => 'lesson-plans',

            // Term plans
            'sm_save_term_plan' => 'term-plans',
            'sm_review_term_plan' => 'term-plans',
            'sm_delete_term_plan' => 'term-plans',

            // Assignments
            'sm_approve_plan_ajax' => 'assignments',

            // Documents
            'sm_add_document_ajax' => 'documents',
            'sm_update_document_ajax' => 'documents',
            'sm_delete_document_ajax' => 'documents',
            'sm_print' => 'documents',

            // Clinic
            'sm_add_clinic_referral' => 'clinic',
            'sm_confirm_clinic_arrival' => 'clinic',
            'sm_update_clinic_record' => 'clinic',
            'sm_get_clinic_reports' => 'clinic',

            // Global Settings
            'sm_save_regulation_settings_ajax' => 'global-settings',
            'sm_save_hierarchical_violations_ajax' => 'global-settings',
            'sm_delete_all_logs_ajax' => 'global-settings',
            'sm_delete_log_ajax' => 'global-settings',
            'sm_rollback_log_ajax' => 'global-settings',
            'sm_initialize_system_ajax' => 'global-settings',
            'sm_bulk_delete_ajax' => 'global-settings',
            'sm_refresh_system_cache_ajax' => 'global-settings',
            'sm_create_system_announcement' => 'global-settings',
            'sm_disable_system_announcement' => 'global-settings',
            'sm_delete_system_announcement' => 'global-settings',
            'sm_reset_user_announcement' => 'global-settings',
            'sm_delete_user_announcement_log' => 'global-settings',
        );

        if (isset($action_map[$clean_action])) {
            $section = $action_map[$clean_action];
            return self::is_section_visible($section, $user_id) && self::user_has_module_capability($section, $user_id);
        }

        return true; // Unmapped action, allow by default
    }

    public static function get_access_restricted_html() {
        ob_start();
        ?>
        <div class="sm-container" style="padding:60px 20px; text-align:center; max-width:550px; margin: 0 auto; font-family: 'Cairo', sans-serif;" dir="rtl">
            <div style="background:#ffffff; padding:45px 30px; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 10px 15px -3px rgba(0,0,0,0.05);">
                <div style="font-size:75px; color:#ea580c; line-height:1; margin-bottom:20px;"></div>
                <h2 style="margin:0 0 10px 0; font-weight:800; color:#0f172a; font-size:1.6rem;">  Unauthorized </h2>
                <p style="margin:0 0 30px 0; font-size:14px; color:#64748b; line-height:1.7;">   No  Permissions     .          System Management.</p>
                <a href="<?php echo home_url('/sm-admin'); ?>" class="sm-btn" style="width:100%; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; color:white !important; background-color:#000000 !important; border:1px solid #000000;">   Home</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function get_sidebar_visibility() {
        $modules = self::get_system_modules();
        $roles = array(
            'sm_system_admin',
            'sm_principal',
            'sm_supervisor',
            'sm_coordinator',
            'sm_hod',
            'sm_teacher',
            'sm_student',
            'sm_parent',
            'sm_discipline_supervisor',
            'sm_activities_supervisor',
            'sm_transportation_supervisor',
            'sm_bus_supervisor',
            'sm_hr'
        );

        $default = array();
        foreach ($roles as $role) {
            $default[$role] = array();
            foreach ($modules as $sec_key => $sec_data) {
                $default[$role][$sec_key] = $sec_data['default'][$role] ?? false;
            }
        }

        $saved = get_option('sm_sidebar_visibility');
        if ($saved === false) {
            return $default;
        }

        // Merge saved settings role by role to ensure persistence
        foreach ($default as $role => $sections) {
            if (isset($saved[$role])) {
                $default[$role] = array_merge($sections, $saved[$role]);
            }
        }
        return $default;
    }

    public static function save_sidebar_visibility($data) {
        update_option('sm_sidebar_visibility', $data);
    }

    public static function change_user_role($user_id, $new_role, $additional_data = array()) {
        $user = new WP_User($user_id);
        if (!$user || empty($user->ID)) {
            return false;
        }

        // Permanent protections for the root System Administrator (info@eess.online)
        if ($user->user_email === 'info@eess.online') {
            return false;
        }

        // Restrict System Administrator role to info@eess.online only
        if ($new_role === 'sm_system_admin' || $new_role === 'administrator') {
            return false;
        }

        // 1. Set authoritative WordPress role (which strips old roles)
        $user->set_role($new_role);

        // 2. Map role back to the Arabic label
        $role_map = array(
            'administrator' => '  ()',
            'sm_system_admin' => ' ',
            'sm_principal' => ' Academy ',
            'sm_supervisor' => ' ',
            'sm_coordinator' => ' ',
            'sm_hod' => ' ',
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
        $role_label = $role_map[$new_role] ?? $new_role;

        // 3. Synchronize metadata and legacy role/job title fields
        update_user_meta($user_id, 'sm_job_title', $role_label);
        update_user_meta($user_id, 'sm_job_title_ar', $role_label);

        // Update user meta roles or statuses
        if ($new_role === 'sm_student') {
            update_user_meta($user_id, 'sm_account_status', 'active');
        } elseif ($new_role === 'sm_parent') {
            update_user_meta($user_id, 'sm_account_status', 'active');
        }

        // Handle subject assignment
        if (isset($additional_data['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($additional_data['specialization']));
        }

        // 4. In Employee Profile and Human Resources, synchronize immediately.
        $hr_status = get_user_meta($user_id, 'eess_hr_employment_status', true);
        if (empty($hr_status)) {
            update_user_meta($user_id, 'eess_hr_employment_status', 'active');
        }

        // Add a timeline event to the Employee Profile
        $timeline = get_user_meta($user_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) {
            $timeline = array();
        }
        $actor_user = wp_get_current_user();
        $actor = ($actor_user && $actor_user->display_name) ? $actor_user->display_name : '';
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => '  /  ',
            'actor' => $actor,
            'details' => "    : $role_label"
        ));
        update_user_meta($user_id, 'eess_hr_activity_timeline', $timeline);

        // 5. Invalidate caches immediately so that the new role, permissions, and sidebar appear instantly
        wp_cache_flush();
        clean_user_cache($user_id);

        return true;
    }

    /**
     * Enforce UAE Phone Format (+971)
     */
    public static function format_uae_phone($phone) {
        if (empty($phone)) return false;

        // 1. Strip all non-digits
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // 2. Handle 050... -> 97150... (0 + 9 digits)
        if (strlen($digits) == 10 && strpos($digits, '0') === 0) {
            $digits = '971' . substr($digits, 1);
        }
        // 3. Handle 50... -> 97150... (9 digits)
        elseif (strlen($digits) == 9) {
            $digits = '971' . $digits;
        }
        // 4. Handle 97150... -> 97150... (12 digits)
        // Already handled if it was 12 digits.

        // Final Validation: Exactly 12 digits starting with 971
        if (strlen($digits) == 12 && strpos($digits, '971') === 0) {
            return $digits;
        }

        return false;
    }

    public static function get_subject_lesson_fields($subject) {
        $sub = mb_strtolower(trim($subject));

        if (strpos($sub, '') !== false || strpos($sub, '') !== false || strpos($sub, 'pe') !== false || strpos($sub, 'physical') !== false) {
            return array(
                'label1' => '     (Physical Preparation)',
                'placeholder1' => '       ...',
                'label2' => '    (Skill Preparation)',
                'placeholder2' => ' View     ...',
                'label3' => '   (Main Practical Activity)',
                'placeholder3' => '    ...',
                'label4' => '   No (Cool-down & Closing)',
                'placeholder4' => '      ...'
            );
        }

        if (strpos($sub, '') !== false || strpos($sub, 'math') !== false) {
            return array(
                'label1' => '    (Objectives & Concepts)',
                'placeholder1' => '      ...',
                'label2' => '    (Warm-up & Prior Knowledge)',
                'placeholder2' => '  Previous   ...',
                'label3' => '  Active  (Problem Solving & Activities)',
                'placeholder3' => '       ...',
                'label4' => '      (Assessment & Proof Check)',
                'placeholder4' => '  Level No    ...'
            );
        }

        if (strpos($sub, '') !== false || strpos($sub, '') !== false || strpos($sub, '') !== false || strpos($sub, '') !== false || strpos($sub, 'physics') !== false || strpos($sub, 'chemistry') !== false || strpos($sub, 'biology') !== false || strpos($sub, 'science') !== false) {
            return array(
                'label1' => '    (Scientific Objectives & Concepts)',
                'placeholder1' => '      ...',
                'label2' => ' No  (Warm-up & Observation)',
                'placeholder2' => '     No  ...',
                'label3' => '   No (Lab Experiment & Inquiry)',
                'placeholder3' => '   No    ...',
                'label4' => '    (Data Analysis & Evaluation)',
                'placeholder4' => '       ...'
            );
        }

        if (strpos($sub, 'No') !== false || strpos($sub, '') !== false || strpos($sub, ' No') !== false || strpos($sub, 'islamic') !== false) {
            return array(
                'label1' => '    No (Objectives & Recitation)',
                'placeholder1' => '      ...',
                'label2' => '    (Warm-up & Reflection)',
                'placeholder2' => '       ...',
                'label3' => '  Active  (Tafseer & Learning Activities)',
                'placeholder3' => '    No No ...',
                'label4' => '    (Behavioral Assessment & Closing)',
                'placeholder4' => '     ...'
            );
        }

        return array(
            'label1' => '   (Objectives)',
            'placeholder1' => '     ...',
            'label2' => '   (Warm-up)',
            'placeholder2' => '    No  ...',
            'label3' => 'No Active   (Learning Activities)',
            'placeholder3' => '  View   Active ...',
            'label4' => ' Training Group   (Evaluation & Assessment)',
            'placeholder4' => '     No No ...'
        );
    }
}

class Sportedia_Settings extends SM_Settings {}
