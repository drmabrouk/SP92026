<?php

class SM_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_students = $wpdb->prefix . 'sm_students';
        $table_records = $wpdb->prefix . 'sm_records';
        $table_logs = $wpdb->prefix . 'sm_logs';

        $sql = "CREATE TABLE $table_students (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            class_name varchar(100) NOT NULL,
            section varchar(50) DEFAULT '',
            parent_email varchar(100),
            guardian_phone varchar(50) DEFAULT '',
            nationality varchar(100) DEFAULT '',
            registration_date date DEFAULT NULL,
            student_code varchar(50),
            parent_user_id bigint(20) DEFAULT NULL,
            teacher_id bigint(20) DEFAULT NULL,
            photo_url varchar(255) DEFAULT '',
            national_id varchar(50) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            behavior_points int(11) DEFAULT 0,
            case_file_active tinyint(1) DEFAULT 0,
            institution_id bigint(20) DEFAULT NULL,
            school_id bigint(20) DEFAULT NULL,
            grade_id bigint(20) DEFAULT NULL,
            class_id bigint(20) DEFAULT NULL,
            department_id bigint(20) DEFAULT NULL,
            gender varchar(20) DEFAULT '',
            dob date DEFAULT NULL,
            guardian_name varchar(255) DEFAULT '',
            guardian_relationship varchar(100) DEFAULT '',
            student_status varchar(50) DEFAULT 'Active',
            enrollment_status varchar(50) DEFAULT 'Enrolled',
            enrollment_date date DEFAULT NULL,
            emirate varchar(100) DEFAULT 'أبوظبي',
            address text DEFAULT NULL,
            academic_level varchar(100) DEFAULT '',
            special_needs tinyint(1) DEFAULT 0,
            health_status text DEFAULT NULL,
            allergies text DEFAULT NULL,
            fee_status varchar(50) DEFAULT 'Unpaid',
            total_tuition_fees decimal(10,2) DEFAULT '0.00',
            amount_paid decimal(10,2) DEFAULT '0.00',
            outstanding_balance decimal(10,2) DEFAULT '0.00',
            payment_status varchar(100) DEFAULT 'Pending',
            PRIMARY KEY  (id),
            KEY student_code (student_code),
            KEY teacher_id (teacher_id),
            KEY sort_order (sort_order),
            KEY institution_id (institution_id),
            KEY school_id (school_id),
            KEY grade_id (grade_id),
            KEY department_id (department_id),
            KEY class_name (class_name),
            UNIQUE KEY national_id (national_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_institutions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code int(11) DEFAULT 1 NOT NULL,
            name varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_schools (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            institution_id bigint(20) NOT NULL,
            school_code int(11) DEFAULT 1 NOT NULL,
            name varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY institution_id (institution_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_grades (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            school_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY school_id (school_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_classes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            grade_id bigint(20) NOT NULL,
            name varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY grade_id (grade_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_departments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            institution_id bigint(20) DEFAULT NULL,
            school_id bigint(20) DEFAULT NULL,
            code varchar(50) DEFAULT '' NOT NULL,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            status varchar(50) DEFAULT 'active' NOT NULL,
            head_user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY institution_id (institution_id),
            KEY school_id (school_id),
            KEY code (code),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_subjects (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            institution_id bigint(20) DEFAULT NULL,
            school_id bigint(20) DEFAULT NULL,
            department_id bigint(20) DEFAULT NULL,
            code varchar(50) DEFAULT '' NOT NULL,
            name varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'active' NOT NULL,
            hod_user_id bigint(20) DEFAULT NULL,
            coordinator_user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY institution_id (institution_id),
            KEY school_id (school_id),
            KEY department_id (department_id),
            KEY code (code),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_subject_grades (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subject_id bigint(20) NOT NULL,
            grade_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY subject_id (subject_id),
            KEY grade_id (grade_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_subject_schools (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subject_id bigint(20) NOT NULL,
            school_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY subject_id (subject_id),
            KEY school_id (school_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_id_counters (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            institution_id bigint(20) NOT NULL,
            counter_type varchar(50) NOT NULL,
            last_sequence bigint(20) DEFAULT 0 NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY inst_counter (institution_id, counter_type)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}eess_user_assignments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            institution_id bigint(20) DEFAULT NULL,
            school_id bigint(20) DEFAULT NULL,
            grade_id bigint(20) DEFAULT NULL,
            class_id bigint(20) DEFAULT NULL,
            subject_id bigint(20) DEFAULT NULL,
            department_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY institution_id (institution_id),
            KEY school_id (school_id),
            KEY department_id (department_id),
            KEY subject_id (subject_id)
        ) $charset_collate;

        CREATE TABLE $table_records (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            teacher_id bigint(20) NOT NULL,
            type varchar(100) NOT NULL,
            classification varchar(100) DEFAULT 'general',
            severity varchar(50) NOT NULL,
            degree int(11) DEFAULT 1,
            violation_code varchar(50) DEFAULT '',
            points int(11) DEFAULT 0,
            recurrence_count int(11) DEFAULT 1,
            details text NOT NULL,
            action_taken text,
            reward_penalty text,
            contacted tinyint(1) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'accepted' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY teacher_id (teacher_id),
            KEY status (status),
            KEY degree (degree),
            KEY violation_code (violation_code)
        ) $charset_collate;

        CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action text NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_attendance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL,
            date date NOT NULL,
            teacher_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY date (date)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_assignments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            student_id bigint(20) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text,
            file_url varchar(255) DEFAULT '',
            type varchar(50) DEFAULT 'assignment',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_clinic (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            referrer_id bigint(20) NOT NULL,
            arrival_confirmed tinyint(1) DEFAULT 0,
            health_condition text,
            action_taken text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            arrival_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY referrer_id (referrer_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_grades (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            subject varchar(100) NOT NULL,
            term varchar(50) NOT NULL,
            grade_val varchar(20) NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY student_id (student_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_subjects (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            grade_id int(11) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_student_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY meta_key (meta_key)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_documents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            file_url varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'published',
            category varchar(100) DEFAULT 'الوثائق الإدارية',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_lesson_preps (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) NOT NULL,
            supervisor_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            subject varchar(100) NOT NULL,
            grade_level varchar(50) NOT NULL,
            class_section varchar(50) NOT NULL,
            lesson_date date NOT NULL,
            submission_time datetime DEFAULT NULL,
            status varchar(50) DEFAULT 'draft' NOT NULL,
            delay_seconds int(11) DEFAULT 0 NOT NULL,
            lesson_data longtext,
            version int(11) DEFAULT 1 NOT NULL,
            parent_id bigint(20) DEFAULT 0 NOT NULL,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            review_notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY teacher_id (teacher_id),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_lesson_comments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            prep_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            comment_text text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY prep_id (prep_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_term_plans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) NOT NULL,
            academic_year varchar(50) NOT NULL,
            subject varchar(100) NOT NULL,
            grade varchar(255) NOT NULL,
            weekly_lessons int(11) DEFAULT 1 NOT NULL,
            num_terms int(11) DEFAULT 3 NOT NULL,
            term_number int(11) DEFAULT 1 NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            total_weeks int(11) DEFAULT 0 NOT NULL,
            weeks_data longtext,
            plan_file_url text DEFAULT NULL,
            planning_method varchar(50) DEFAULT 'create' NOT NULL,
            completion_pct int(11) DEFAULT 0 NOT NULL,
            status varchar(50) DEFAULT 'draft' NOT NULL,
            review_notes text,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY teacher_id (teacher_id),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_educational_inputs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subject varchar(100) NOT NULL,
            input_type varchar(50) DEFAULT 'title' NOT NULL,
            content text NOT NULL,
            usage_count int(11) DEFAULT 1 NOT NULL,
            is_approved tinyint(1) DEFAULT 1 NOT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY subject (subject),
            KEY input_type (input_type),
            KEY is_approved (is_approved)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_asset_catalog (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            item_name varchar(255) NOT NULL,
            category varchar(100) NOT NULL,
            unit varchar(50) DEFAULT 'قطعة' NOT NULL,
            description text,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_asset_inventories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            institution_id bigint(20) DEFAULT 0 NOT NULL,
            school_name varchar(255) NOT NULL,
            department varchar(100) DEFAULT 'التربية البدنية والصحية' NOT NULL,
            academic_year varchar(50) DEFAULT '2027/2026' NOT NULL,
            responsible_user_id bigint(20) NOT NULL,
            status varchar(50) DEFAULT 'submitted' NOT NULL,
            review_notes text,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY school_name (school_name),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_asset_inventory_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            inventory_id bigint(20) NOT NULL,
            catalog_id bigint(20) DEFAULT 0 NOT NULL,
            item_name varchar(255) NOT NULL,
            category varchar(100) NOT NULL,
            qty_total int(11) DEFAULT 0 NOT NULL,
            qty_usable int(11) DEFAULT 0 NOT NULL,
            qty_consumed int(11) DEFAULT 0 NOT NULL,
            qty_damaged int(11) DEFAULT 0 NOT NULL,
            qty_missing int(11) DEFAULT 0 NOT NULL,
            qty_replacement int(11) DEFAULT 0 NOT NULL,
            location varchar(150) DEFAULT 'مخزن التربية البدنية' NOT NULL,
            condition_status varchar(50) DEFAULT 'good' NOT NULL,
            item_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY inventory_id (inventory_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_asset_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            institution_id bigint(20) DEFAULT 0 NOT NULL,
            school_name varchar(255) NOT NULL,
            department varchar(100) DEFAULT 'التربية البدنية والصحية' NOT NULL,
            requester_user_id bigint(20) NOT NULL,
            request_reason varchar(255) DEFAULT 'استبدال معدات تالفة' NOT NULL,
            priority varchar(50) DEFAULT 'normal' NOT NULL,
            status varchar(50) DEFAULT 'submitted' NOT NULL,
            notes text,
            review_notes text,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY school_name (school_name),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_asset_request_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_id bigint(20) NOT NULL,
            catalog_id bigint(20) DEFAULT 0 NOT NULL,
            item_name varchar(255) NOT NULL,
            qty_usable int(11) DEFAULT 0 NOT NULL,
            qty_damaged int(11) DEFAULT 0 NOT NULL,
            qty_missing int(11) DEFAULT 0 NOT NULL,
            qty_requested int(11) DEFAULT 1 NOT NULL,
            item_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY request_id (request_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_asset_audit_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            student_id bigint(20) DEFAULT NULL,
            message text NOT NULL,
            status varchar(20) DEFAULT 'unread' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY student_id (student_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_confiscated_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            teacher_id bigint(20) NOT NULL,
            item_name varchar(255) NOT NULL,
            incident_date date DEFAULT NULL,
            status varchar(50) DEFAULT 'confiscated' NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY teacher_id (teacher_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_parent_summons (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            parent_user_id bigint(20) DEFAULT NULL,
            reason varchar(255) NOT NULL,
            summons_date date NOT NULL,
            summons_time time DEFAULT NULL,
            department_requester varchar(100) DEFAULT 'شؤون الطلاب' NOT NULL,
            notes text DEFAULT NULL,
            status varchar(50) DEFAULT 'draft' NOT NULL,
            actual_visit_date datetime DEFAULT NULL,
            visit_notes text DEFAULT NULL,
            discussion_summary text DEFAULT NULL,
            staff_handler_id bigint(20) DEFAULT NULL,
            parent_cooperation varchar(50) DEFAULT NULL,
            evaluation_comments text DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY status (status)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sm_exit_card_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reference_no varchar(50) DEFAULT NULL,
            student_id bigint(20) NOT NULL,
            parent_user_id bigint(20) DEFAULT NULL,
            parent_name varchar(255) DEFAULT NULL,
            parent_phone varchar(50) DEFAULT NULL,
            academic_year varchar(50) DEFAULT '2025/2026' NOT NULL,
            reason varchar(255) DEFAULT 'طلب تصريح خروج طالب' NOT NULL,
            requested_date date DEFAULT NULL,
            notes text DEFAULT NULL,
            declaration_accepted tinyint(1) DEFAULT 0 NOT NULL,
            signature_data longtext DEFAULT NULL,
            status varchar(50) DEFAULT 'submitted' NOT NULL,
            printing_status varchar(50) DEFAULT 'pending' NOT NULL,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            review_notes text DEFAULT NULL,
            admin_notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY reference_no (reference_no),
            KEY student_id (student_id),
            KEY academic_year (academic_year),
            KEY status (status)
        ) $charset_collate;";

        // System Announcements Table
        $sql_announcements = "CREATE TABLE {$wpdb->prefix}sm_system_announcements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            details text NOT NULL,
            target_roles text NOT NULL,
            type varchar(50) DEFAULT 'info' NOT NULL,
            display_duration int(11) DEFAULT 10 NOT NULL,
            display_frequency int(11) DEFAULT 1 NOT NULL,
            is_welcome int(11) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";

        // User Announcement Activity Logs Table
        $sql_user_announcements = "CREATE TABLE {$wpdb->prefix}sm_user_announcements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            announcement_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            view_count int(11) DEFAULT 0 NOT NULL,
            viewed_at datetime DEFAULT NULL,
            closed_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY announcement_id (announcement_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Technical Support & Feedback Requests Table
        $sql_support_requests = "CREATE TABLE {$wpdb->prefix}sm_support_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            category varchar(50) NOT NULL,
            title varchar(255) DEFAULT '' NOT NULL,
            details text DEFAULT NULL,
            attachment_url varchar(255) DEFAULT '' NOT NULL,
            rating_stars int(11) DEFAULT 0 NOT NULL,
            status varchar(50) DEFAULT 'new' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY category (category),
            KEY status (status)
        ) $charset_collate;";

        // Evaluation Engine Tables
        $sql_eval_templates = "CREATE TABLE {$wpdb->prefix}sm_eval_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            role_key varchar(50) NOT NULL,
            department_id bigint(20) DEFAULT 0,
            subject varchar(100) DEFAULT '',
            total_questions int(11) DEFAULT 10,
            category_name varchar(100) DEFAULT 'تقييم الأداء العام',
            passing_threshold int(11) DEFAULT 60,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY role_key (role_key)
        ) $charset_collate;";

        $sql_eval_questions = "CREATE TABLE {$wpdb->prefix}sm_eval_questions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_id bigint(20) NOT NULL,
            question_text text NOT NULL,
            category_name varchar(100) DEFAULT 'الانضباط والالتزام',
            display_order int(11) DEFAULT 1,
            max_score int(11) DEFAULT 10,
            weight float DEFAULT 1.0,
            is_required tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY template_id (template_id)
        ) $charset_collate;";

        $sql_evaluations = "CREATE TABLE {$wpdb->prefix}sm_evaluations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            employee_id bigint(20) NOT NULL,
            evaluator_id bigint(20) NOT NULL,
            template_id bigint(20) DEFAULT 0,
            academic_year varchar(20) DEFAULT '2025/2026',
            category_name varchar(100) DEFAULT 'تقييم شامل',
            answers_json longtext DEFAULT NULL,
            subjective_score float DEFAULT 0,
            system_score float DEFAULT 0,
            total_score float DEFAULT 0,
            average_pct float DEFAULT 0,
            comments text DEFAULT NULL,
            status varchar(20) DEFAULT 'submitted',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY employee_id (employee_id),
            KEY evaluator_id (evaluator_id),
            KEY academic_year (academic_year)
        ) $charset_collate;";

        if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
        dbDelta($sql_announcements);
        dbDelta($sql_user_announcements);
        dbDelta($sql_support_requests);
        dbDelta($sql_eval_templates);
        dbDelta($sql_eval_questions);
        dbDelta($sql_evaluations);

        self::add_custom_roles();
        self::remove_demo_data();
        self::create_default_pages();
        self::cleanup_legacy_pages();
        self::migrate_old_roles();

        // Delegate structure seeding and migration to EESS_Org_Helper
        if (class_exists('EESS_Org_Helper')) {
            EESS_Org_Helper::seed_default_structure();
        }
    }

    private static function cleanup_legacy_pages() {
        $legacy_page = get_page_by_path('sm-system');
        if ($legacy_page) {
            wp_delete_post($legacy_page->ID, true);
        }
    }

    private static function create_default_pages() {
        $pages = array(
            'sm-login' => array(
                'title'   => 'تسجيل الدخول',
                'content' => '[sm_login]',
            ),
            'sm-admin' => array(
                'title'   => 'لوحة التحكم المدرسية',
                'content' => '[sm_admin]',
            ),
            'attendance' => array(
                'title'   => 'تسجيل حضور الفصول',
                'content' => '[sm_class_attendance]',
            ),
            'stu' => array(
                'title'   => 'بوابة خدمات الطلاب والبطاقات',
                'content' => '[stu]',
            ),
        );

        foreach ($pages as $slug => $page_data) {
            $page_exists = get_page_by_path($slug);
            if (!$page_exists) {
                $page_id = wp_insert_post(array(
                    'post_title'   => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_name'    => $slug,
                ));
                if ($slug === 'stu' && $page_id && !is_wp_error($page_id)) {
                    update_option('eess_exit_card_portal_page_id', $page_id);
                }
            } else {
                if ($slug === 'stu' && $page_exists->ID) {
                    update_option('eess_exit_card_portal_page_id', $page_exists->ID);
                }
            }
        }
    }

    public static function add_custom_roles() {
        // Remove old roles
        $old_roles = array(
            'school_admin', 'discipline_officer', 'sm_school_admin',
            'sm_discipline_officer', 'sm_teacher', 'sm_parent'
        );
        foreach ($old_roles as $role) {
            remove_role($role);
        }

        // Capabilities
        $caps = array(
            'manage_system' => 'إدارة_النظام',
            'manage_clinic' => 'إدارة_العيادة',
            'manage_users' => 'إدارة_المستخدمين',
            'manage_students' => 'إدارة_الطلاب',
            'manage_teachers' => 'إدارة_المعلمين',
            'manage_violations' => 'إدارة_المخالفات',
            'add_violation' => 'تسجيل_مخالفة',
            'print_reports' => 'طباعة_التقارير',
            'review_plans' => 'مراجعة_التحضير',
            'manage_grades' => 'إدارة_الدرجات',
            'manage_assignments' => 'إدارة_الواجبات',
            'manage_parents' => 'إدارة_أولياء_الأمور',
            'view_own_data' => 'عرض_بياناتي',
            'submit_complaint' => 'تقديم_شكوى',
            'manage_hr' => 'إدارة_الموارد_البشرية'
        );

        // Add Caps to Administrator
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }

        // 1. مدير النظام (System Administrator) - Access to all, including settings
        add_role('sm_system_admin', 'مدير النظام', array('read' => true));
        $sys_admin = get_role('sm_system_admin');
        if ($sys_admin) {
            foreach ($caps as $cap) $sys_admin->add_cap($cap);
        }

        // 2. مدير المدرسة (School Principal) - All except settings
        add_role('sm_principal', 'مدير المدرسة', array('read' => true));
        $principal = get_role('sm_principal');
        if ($principal) {
            foreach ($caps as $key => $cap) {
                if ($key !== 'manage_system') $principal->add_cap($cap);
            }
        }

        // 3. مشرف تربوي (Educational Supervisor) - Same as Principal (filtered by classes in logic)
        add_role('sm_supervisor', 'مشرف تربوي', array('read' => true));
        $supervisor = get_role('sm_supervisor');
        if ($supervisor) {
            foreach ($caps as $key => $cap) {
                if ($key !== 'manage_system') $supervisor->add_cap($cap);
            }
        }

        // 4. منسق المادة (Subject Coordinator) - Review lesson plans
        add_role('sm_coordinator', 'منسق المادة', array('read' => true));
        $coordinator = get_role('sm_coordinator');
        if ($coordinator) {
            $coordinator->add_cap($caps['review_plans']);
            $coordinator->add_cap($caps['manage_grades']);
            $coordinator->add_cap('read');
        }

        // 4.5. رئيس قسم (Head of Department) - Review lesson plans, manage department grades & teachers
        add_role('sm_hod', 'رئيس قسم', array('read' => true));
        $hod = get_role('sm_hod');
        if ($hod) {
            $hod->add_cap($caps['review_plans']);
            $hod->add_cap($caps['manage_grades']);
            $hod->add_cap($caps['manage_teachers']);
            $hod->add_cap($caps['manage_students']);
            $hod->add_cap('read');
        }

        // 5. معلم (Teacher) - Complaints, search all students, assignments, assigned sections
        add_role('sm_teacher', 'معلم', array('read' => true));
        $teacher = get_role('sm_teacher');
        if ($teacher) {
            $teacher->add_cap($caps['add_violation']);
            $teacher->add_cap($caps['submit_complaint']);
            $teacher->add_cap($caps['manage_assignments']);
            $teacher->add_cap($caps['manage_students']);
            $teacher->add_cap($caps['manage_grades']);
        }

        // 6. ولي أمر (Parent) - View own children's data
        add_role('sm_parent', 'ولي أمر', array('read' => true));
        $parent = get_role('sm_parent');
        if ($parent) {
            $parent->add_cap($caps['view_own_data']);
            $parent->add_cap('read');
        }

        // 7. مشرف سلوك / انضباط (Discipline Supervisor) *(New)*
        add_role('sm_discipline_supervisor', 'مشرف سلوك / انضباط', array('read' => true));
        $discipline_sup = get_role('sm_discipline_supervisor');
        if ($discipline_sup) {
            $discipline_sup->add_cap($caps['add_violation']);
            $discipline_sup->add_cap($caps['manage_violations']);
            $discipline_sup->add_cap($caps['manage_students']);
            $discipline_sup->add_cap($caps['manage_parents']);
            $discipline_sup->add_cap('read');
        }

        // 8. مشرف أنشطة (Activities Supervisor) *(New)*
        add_role('sm_activities_supervisor', 'مشرف أنشطة', array('read' => true));
        $activities_sup = get_role('sm_activities_supervisor');
        if ($activities_sup) {
            $activities_sup->add_cap($caps['view_own_data']);
            $activities_sup->add_cap('read');
        }

        // 9. مشرف نقل ومواصلات (Transportation Supervisor) *(New)*
        add_role('sm_transportation_supervisor', 'مشرف نقل ومواصلات', array('read' => true));
        $trans_sup = get_role('sm_transportation_supervisor');
        if ($trans_sup) {
            $trans_sup->add_cap($caps['view_own_data']);
            $trans_sup->add_cap('read');
        }

        // 10. مشرف حافلة (Bus Supervisor) *(New)*
        add_role('sm_bus_supervisor', 'مشرف حافلة', array('read' => true));
        $bus_sup = get_role('sm_bus_supervisor');
        if ($bus_sup) {
            $bus_sup->add_cap($caps['add_violation']);
            $bus_sup->add_cap($caps['view_own_data']);
            $bus_sup->add_cap('read');
        }

        // Keep fallback roles for compatibility
        add_role('sm_student', 'طالب', array('read' => true));
        $student = get_role('sm_student');
        if ($student) {
            $student->add_cap($caps['view_own_data']);
            $student->add_cap($caps['manage_assignments']);
        }
        add_role('sm_clinic', 'العيادة المدرسية', array('read' => true));
        $clinic = get_role('sm_clinic');
        if ($clinic) {
            $clinic->add_cap($caps['manage_clinic']);
            $clinic->add_cap('read');
        }

        // 11. الموارد البشرية (Human Resources) *(New)*
        add_role('sm_hr', 'الموارد البشرية', array('read' => true));
        $hr_role = get_role('sm_hr');
        if ($hr_role) {
            $hr_role->add_cap('إدارة_الموارد_البشرية');
            $hr_role->add_cap('read');
        }
    }

    private static function seed_default_subjects() {
        global $wpdb;
        $table_subjects = $wpdb->prefix . 'sm_subjects';

        $required_subjects = array(
            'التربية البدنية والصحية',
            'العلوم الصحية',
            'الكيمياء',
            'الرياضيات',
            'التربية الإسلامية',
            'اللغة العربية',
            'اللغة الإنجليزية',
            'الفيزياء',
            'الدراسات الاجتماعية',
            'علوم الحاسوب',
            'العلوم العامة',
            'الأحياء',
            'التربية الموسيقية',
            'الفنون البصرية'
        );

        foreach ($required_subjects as $subject_name) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_subjects WHERE name = %s", $subject_name));
            if (!$exists) {
                // Insert with a default grade ID of 1
                $wpdb->insert($table_subjects, array('name' => $subject_name, 'grade_id' => 1));
            }
        }
    }

    public static function translate_subjects_to_arabic() {
        global $wpdb;
        $table_subjects = $wpdb->prefix . 'sm_subjects';

        $translation_map = array(
            'Physical Education & Health' => 'التربية البدنية والصحية',
            'Health Sciences' => 'العلوم الصحية',
            'Chemistry' => 'الكيمياء',
            'Mathematics' => 'الرياضيات',
            'Islamic Studies' => 'التربية الإسلامية',
            'Arabic Language' => 'اللغة العربية',
            'English Language' => 'اللغة الإنجليزية',
            'Physics' => 'الفيزياء',
            'Social Studies' => 'الدراسات الاجتماعية',
            'Computer Science' => 'علوم الحاسوب',
            'General Science' => 'العلوم العامة',
            'Biology' => 'الأحياء',
            'Music Education' => 'التربية الموسيقية',
            'Visual Arts' => 'الفنون البصرية'
        );

        // 1. Update existing subjects in DB
        foreach ($translation_map as $eng => $ar) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_subjects SET name = %s WHERE name = %s",
                $ar, $eng
            ));
        }

        // 2. Update existing user metadata sm_specialization
        foreach ($translation_map as $eng => $ar) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->usermeta} SET meta_value = %s WHERE meta_key = 'sm_specialization' AND meta_value = %s",
                $ar, $eng
            ));
        }
    }

    public static function migrate_old_roles() {
        $migration_map = array(
            'discipline_officer'    => 'sm_supervisor',
            'school_admin'          => 'sm_principal',
            'sm_discipline_officer' => 'sm_supervisor',
            'sm_school_admin'       => 'sm_principal'
        );

        foreach ($migration_map as $old_slug => $new_slug) {
            $users = get_users(array('role' => $old_slug));
            foreach ($users as $user) {
                $user->remove_role($old_slug);
                $user->add_role($new_slug);
            }
        }
    }

    private static function remove_demo_data() {
        global $wpdb;
        $table_students = $wpdb->prefix . 'sm_students';
        $demo_codes = array('STU001', 'STU002', 'STU003', 'STU004', 'STU005', 'STU006', 'STU007', 'STU008', 'STU009', 'STU010');
        $in_clause = "'" . implode("','", $demo_codes) . "'";
        $wpdb->query("DELETE FROM $table_students WHERE student_code IN ($in_clause) OR parent_email LIKE 'parent%@example.com'");
    }
}
