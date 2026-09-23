<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Org_Helper {

    private static $cache = array();

    public static function flush_cache() {
        self::$cache = array();
    }

    /**
     * Canonical Role-to-Department Mapping Matrix
     */
    public static function get_role_department_mapping() {
        return array(
            'sportedia_coach'              => 'الأقسام التدريبية - الأنشطة الرياضية',
            'sm_teacher'                   => 'الأقسام التدريبية - الأنشطة الرياضية',
            'sm_coordinator'               => 'الأقسام التدريبية - الأنشطة الرياضية',
            'sm_hod'                       => 'الأقسام التدريبية - الأنشطة الرياضية',
            'sm_hr'                        => 'إدارة الموارد البشرية (HR)',
            'sportedia_sports_supervisor'  => 'شؤون اللاعبين والانضباط الرياضي',
            'sportedia_player'             => 'شؤون اللاعبين والانضباط الرياضي',
            'sm_student'                   => 'شؤون اللاعبين والانضباط الرياضي',
            'sm_parent'                    => 'شؤون اللاعبين والانضباط الرياضي',
            'sm_activities_supervisor'     => 'الأنشطة المدرسية والفعاليات',
            'sm_finance'                   => 'المالية والحسابات',
            'sm_bus_supervisor'            => 'الخدمات المساندة والنقل',
            'sm_transportation_supervisor' => 'الخدمات المساندة والنقل',
            'sportedia_branch_manager'     => 'الإدارة العليا للفروع',
            'sm_principal'                 => 'الإدارة العليا للفروع',
            'sm_supervisor'                => 'الإدارة العليا للفروع',
            'sm_system_admin'              => 'الدعم الفني والتقني',
            'subscriber'                   => 'الدعم الفني والتقني'
        );
    }

    /**
     * Official Central Sports Activities Registry
     */
    public static function get_official_subjects() {
        return array(
            1  => array('code' => 1,  'name' => 'كرة القدم',                'dept_code' => 10),
            2  => array('code' => 2,  'name' => 'كرة السلة',                'dept_code' => 10),
            3  => array('code' => 3,  'name' => 'السباحة',                  'dept_code' => 10),
            4  => array('code' => 4,  'name' => 'التايكوندو والكاراتيه',    'dept_code' => 10),
            5  => array('code' => 5,  'name' => 'الجمباز',                  'dept_code' => 10),
            6  => array('code' => 6,  'name' => 'التنس الأرضي وطاولة',      'dept_code' => 10),
            7  => array('code' => 7,  'name' => 'كرة الطائرة',             'dept_code' => 10),
            8  => array('code' => 8,  'name' => 'ألعاب القوى',               'dept_code' => 10),
            9  => array('code' => 9,  'name' => 'الرماية والفروسية',         'dept_code' => 10),
            10 => array('code' => 10, 'name' => 'التربية البدنية واللياقة',   'dept_code' => 10)
        );
    }

    /**
     * Official Central Departments Registry
     */
    public static function get_official_departments() {
        return array(
            1  => array('code' => 1,  'name' => 'إدارة الأكاديميات والفروع'),
            2  => array('code' => 2,  'name' => 'الشؤون التدريبية والرياضية'),
            3  => array('code' => 3,  'name' => 'شؤون اللاعبين الاشتراكات'),
            4  => array('code' => 4,  'name' => 'الشؤون الإدارية'),
            5  => array('code' => 5,  'name' => 'الموارد البشرية'),
            6  => array('code' => 6,  'name' => 'الشؤون المالية والإيرادات'),
            10 => array('code' => 10, 'name' => 'قسم التدريب والتطوير الرياضي'),
            18 => array('code' => 18, 'name' => 'قسم البطولات والأنشطة الخارجية'),
            20 => array('code' => 20, 'name' => 'قسم الأنظمة والتقنية')
        );
    }

    /**
     * Official Training Groups Registry
     */
    public static function get_official_grades() {
        return array(
            1  => array('code' => 1,  'name' => 'مجموعة براعم (Under 8)'),
            2  => array('code' => 2,  'name' => 'مجموعة ناشئين (Under 12)'),
            3  => array('code' => 3,  'name' => 'مجموعة شباب (Under 16)'),
            4  => array('code' => 4,  'name' => 'مجموعة الفريق الأول (Senior)'),
            5  => array('code' => 5,  'name' => 'مجموعة السباحة المتقدمة'),
            6  => array('code' => 6,  'name' => 'مجموعة اللياقة والتخسيس')
        );
    }

    /**
     * Official Sections/Sub-groups Registry
     */
    public static function get_official_sections() {
        return array(
            1  => array('code' => 1,  'ar' => 'أ',  'en' => 'A'),
            2  => array('code' => 2,  'ar' => 'ب',  'en' => 'B'),
            3  => array('code' => 3,  'ar' => 'ج',  'en' => 'C'),
            4  => array('code' => 4,  'ar' => 'د',  'en' => 'D')
        );
    }

    public static function seed_mandatory_institutions() {
        global $wpdb;
        self::ensure_institutions_columns_exist();

        $parent_db_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE code = 1 LIMIT 1");
        if (!$parent_db_id) {
            $wpdb->insert("{$wpdb->prefix}eess_institutions", array(
                'code'      => 1,
                'parent_id' => null,
                'name'      => 'منظمة سبورتيديا الرياضية المركزية',
                'type'      => 'منظمة رياضية',
                'emirate'   => 'دبي',
                'status'    => 'active'
            ));
            $parent_db_id = $wpdb->insert_id;
        }

        // Ensure central sports branches
        $branches = array(
            2 => array('name' => 'أكاديمية سبورتيديا - فرع دبي الرئيسي', 'emirate' => 'دبي'),
            3 => array('name' => 'أكاديمية سبورتيديا - فرع أبوظبي', 'emirate' => 'أبوظبي'),
            4 => array('name' => 'نادي سبورتيديا - فرع الشارقة', 'emirate' => 'الشارقة')
        );

        foreach ($branches as $code => $info) {
            $b_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE code = %d LIMIT 1", $code));
            if (!$b_id) {
                $wpdb->insert("{$wpdb->prefix}eess_institutions", array(
                    'code'      => $code,
                    'parent_id' => $parent_db_id,
                    'name'      => $info['name'],
                    'type'      => 'فرع أود أكاديمية',
                    'emirate'   => $info['emirate'],
                    'status'    => 'active'
                ));
            }
        }
    }

    public static function get_user_scope($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        if (isset(self::$cache['scope_' . $user_id])) {
            return self::$cache['scope_' . $user_id];
        }
        global $wpdb;

        $user = get_userdata($user_id);
        if (!$user) return array('unrestricted' => false, 'institutions' => array(), 'schools' => array(), 'grades' => array(), 'classes' => array(), 'sections' => array(), 'subjects' => array(), 'departments' => array());

        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || in_array('sm_system_admin', $roles);

        if ($is_admin) {
            $all_schools = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_schools WHERE status='active'");
            $all_insts   = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE status='active'");
            return array(
                'unrestricted' => true,
                'institutions' => $all_insts,
                'schools' => $all_schools,
                'grades' => array(),
                'classes' => array(),
                'sections' => array(),
                'subjects' => array(),
                'departments' => array()
            );
        }

        $meta_inst_id = get_user_meta($user_id, 'eess_institution_id', true) ?: get_user_meta($user_id, 'institution_id', true);
        $user_inst_id = intval($meta_inst_id);

        if ($user_inst_id === 1) {
            $all_schools = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_schools WHERE status='active'");
            $all_insts   = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_institutions WHERE status='active'");
            return array(
                'unrestricted' => true,
                'institutions' => $all_insts,
                'schools' => $all_schools,
                'grades' => array(),
                'classes' => array(),
                'sections' => array(),
                'subjects' => array(),
                'departments' => array()
            );
        }

        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d",
            $user_id
        ));

        $institutions = array();
        $schools = array();
        if ($user_inst_id > 0) $institutions[] = $user_inst_id;

        foreach ($assignments as $asn) {
            if ($asn->institution_id) $institutions[] = intval($asn->institution_id);
            if ($asn->school_id) $schools[] = intval($asn->school_id);
        }

        $scope_res = array(
            'unrestricted' => false,
            'institutions' => array_unique(array_filter($institutions)),
            'schools' => array_unique(array_filter($schools)),
            'grades' => array(),
            'classes' => array(),
            'sections' => array(),
            'subjects' => array(),
            'departments' => array()
        );
        self::$cache['scope_' . $user_id] = $scope_res;
        return $scope_res;
    }

    public static function filter_students_query($query_alias = '') {
        $scope = self::get_user_scope();
        if ($scope['unrestricted']) return " 1=1 ";

        $prefix = !empty($query_alias) ? $query_alias . '.' : '';
        $inst_ids = !empty($scope['institutions']) ? implode(',', array_map('intval', $scope['institutions'])) : '0';
        $school_ids = !empty($scope['schools']) ? implode(',', array_map('intval', $scope['schools'])) : '0';

        return " ({$prefix}institution_id IN ($inst_ids) OR {$prefix}school_id IN ($school_ids)) ";
    }

    public static function get_schools() {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        return $wpdb->get_results("SELECT s.*, i.name as institution_name FROM {$wpdb->prefix}eess_schools s LEFT JOIN {$wpdb->prefix}eess_institutions i ON s.institution_id = i.id WHERE (s.status = 'active' OR s.status IS NULL) ORDER BY s.name ASC");
    }

    public static function ensure_institutions_columns_exist() {
        global $wpdb;
        $table = "{$wpdb->prefix}eess_institutions";
        $cols = array(
            'code' => "INT(11) DEFAULT 1 NOT NULL",
            'parent_id' => "BIGINT(20) DEFAULT NULL",
            'type' => "VARCHAR(100) DEFAULT 'فرع رياضية' NOT NULL",
            'logo_url' => "VARCHAR(255) DEFAULT '' NOT NULL",
            'country' => "VARCHAR(100) DEFAULT 'الإمارات العربية المتحدة' NOT NULL",
            'emirate' => "VARCHAR(100) DEFAULT 'دبي' NOT NULL",
            'address' => "TEXT DEFAULT NULL",
            'phone' => "VARCHAR(50) DEFAULT '' NOT NULL",
            'email' => "VARCHAR(100) DEFAULT '' NOT NULL",
            'manager_id' => "BIGINT(20) DEFAULT NULL"
        );

        foreach ($cols as $col => $def) {
            $check = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$col'");
            if (empty($check)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN $col $def");
            }
        }
    }

    public static function resolve_student_org_ids($student_id, $class_name, $section, $school_name = '') {
        global $wpdb;
        if (empty($school_name)) {
            $school_name = 'فرع سبورتيديا الرئيسي';
        }

        $school_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE name = %s", $school_name));
        if (!$school_id) {
            $wpdb->insert("{$wpdb->prefix}eess_schools", array(
                'institution_id' => 1,
                'name' => $school_name,
                'status' => 'active'
            ));
            $school_id = $wpdb->insert_id;
        }

        $wpdb->update("{$wpdb->prefix}sm_students", array(
            'institution_id' => 1,
            'school_id'      => $school_id
        ), array('id' => $student_id));

        return array('school_id' => $school_id);
    }
}

class EESS_Org_Helper extends Sportedia_Org_Helper {}
