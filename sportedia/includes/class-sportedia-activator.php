<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sportedia_Activator {

    public static function activate() {
        global $wpdb;

        if (version_compare(PHP_VERSION, '7.4', '<')) {
            return;
        }

        if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $charset_collate = method_exists($wpdb, 'get_charset_collate') ? $wpdb->get_charset_collate() : 'DEFAULT CHARACTER SET utf8mb4';

        // 1. Members / Players Table
        $table_members = "{$wpdb->prefix}sportedia_members";
        $sql_members = "CREATE TABLE $table_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            member_code varchar(50) NOT NULL,
            barcode varchar(100) DEFAULT NULL,
            qr_code text DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            full_name varchar(255) NOT NULL,
            email varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            photo_url text DEFAULT NULL,
            gender varchar(20) DEFAULT NULL,
            dob date DEFAULT NULL,
            national_id varchar(100) DEFAULT NULL,
            emergency_contact_name varchar(100) DEFAULT NULL,
            emergency_contact_phone varchar(50) DEFAULT NULL,
            branch_id bigint(20) DEFAULT 1,
            current_program_id bigint(20) DEFAULT NULL,
            subscription_status varchar(50) DEFAULT 'Active',
            joined_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY member_code (member_code),
            KEY branch_id (branch_id),
            KEY subscription_status (subscription_status)
        ) $charset_collate;";

        // 2. Employees Table
        $table_employees = "{$wpdb->prefix}sportedia_employees";
        $sql_employees = "CREATE TABLE $table_employees (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            employee_code varchar(50) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            full_name varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            branch_id bigint(20) DEFAULT 1,
            role_slug varchar(50) DEFAULT 'sportedia_staff',
            rank_position varchar(100) DEFAULT 'Staff',
            status varchar(50) DEFAULT 'Active',
            joined_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY employee_code (employee_code),
            KEY branch_id (branch_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // 3. Branches Table
        $table_branches = "{$wpdb->prefix}sportedia_branches";
        $sql_branches = "CREATE TABLE $table_branches (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            branch_name varchar(255) NOT NULL,
            branch_code varchar(50) NOT NULL,
            address text DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            capacity int(11) DEFAULT 500,
            status varchar(50) DEFAULT 'Active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY branch_code (branch_code)
        ) $charset_collate;";

        // 4. Programs Table
        $table_programs = "{$wpdb->prefix}sportedia_programs";
        $sql_programs = "CREATE TABLE $table_programs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            program_name varchar(255) NOT NULL,
            sport varchar(100) DEFAULT 'General',
            description text DEFAULT NULL,
            duration_days int(11) DEFAULT 30,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            capacity int(11) DEFAULT 50,
            branch_id bigint(20) DEFAULT NULL,
            status varchar(50) DEFAULT 'Active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 5. Subscriptions Table
        $table_subscriptions = "{$wpdb->prefix}sportedia_subscriptions";
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            member_id bigint(20) NOT NULL,
            program_id bigint(20) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            price decimal(10,2) NOT NULL,
            status varchar(50) DEFAULT 'Active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY program_id (program_id),
            KEY status (status)
        ) $charset_collate;";

        // 6. Invoices Table
        $table_invoices = "{$wpdb->prefix}sportedia_invoices";
        $sql_invoices = "CREATE TABLE $table_invoices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_no varchar(50) NOT NULL,
            member_id bigint(20) NOT NULL,
            subscription_id bigint(20) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            payment_type varchar(50) DEFAULT 'Cash',
            payment_status varchar(50) DEFAULT 'Paid',
            invoice_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_no (invoice_no),
            KEY member_id (member_id)
        ) $charset_collate;";

        // 7. Payments Table
        $table_payments = "{$wpdb->prefix}sportedia_payments";
        $sql_payments = "CREATE TABLE $table_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_no varchar(50) NOT NULL,
            player_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_type varchar(50) DEFAULT 'Cash',
            subscription_package varchar(100) DEFAULT NULL,
            sessions_added int(11) DEFAULT 0,
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'paid',
            notes text DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY player_id (player_id),
            KEY invoice_no (invoice_no)
        ) $charset_collate;";

        // 8. Attendance Table
        $table_attendance = "{$wpdb->prefix}sportedia_attendance";
        $sql_attendance = "CREATE TABLE $table_attendance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            employee_id bigint(20) DEFAULT NULL,
            member_id bigint(20) DEFAULT NULL,
            branch_id bigint(20) DEFAULT 1,
            scan_date date NOT NULL,
            check_in_time datetime DEFAULT NULL,
            check_out_time datetime DEFAULT NULL,
            status varchar(50) DEFAULT 'Check-In',
            duration_minutes int(11) DEFAULT 0,
            token_used varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY employee_id (employee_id),
            KEY member_id (member_id),
            KEY scan_date (scan_date),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 9. Attendance Tokens Table
        $table_tokens = "{$wpdb->prefix}sportedia_attendance_tokens";
        $sql_tokens = "CREATE TABLE $table_tokens (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            token_hash varchar(255) NOT NULL,
            timestamp_str varchar(20) NOT NULL,
            expires_at int(11) NOT NULL,
            used_status tinyint(1) DEFAULT 0,
            used_by_employee_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // 10. Working Hours Schedule
        $table_schedules = "{$wpdb->prefix}sportedia_working_hours";
        $sql_schedules = "CREATE TABLE $table_schedules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) DEFAULT NULL,
            employee_id bigint(20) DEFAULT NULL,
            day_of_week varchar(20) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id),
            KEY employee_id (employee_id)
        ) $charset_collate;";

        // 11. Backups Table
        $table_backups = "{$wpdb->prefix}sportedia_backups";
        $sql_backups = "CREATE TABLE $table_backups (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            file_path text NOT NULL,
            file_size bigint(20) NOT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Legacy Players Table
        $table_players = "{$wpdb->prefix}sportedia_players";
        $sql_players = "CREATE TABLE $table_players (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_code varchar(50) NOT NULL,
            barcode varchar(100) DEFAULT NULL,
            qr_code text DEFAULT NULL,
            name varchar(255) NOT NULL,
            photo_url text DEFAULT NULL,
            nationality varchar(100) DEFAULT NULL,
            national_id varchar(100) DEFAULT NULL,
            dob date DEFAULT NULL,
            gender varchar(20) DEFAULT NULL,
            guardian_name varchar(255) DEFAULT NULL,
            guardian_relationship varchar(100) DEFAULT NULL,
            guardian_phone varchar(50) DEFAULT NULL,
            guardian_email varchar(100) DEFAULT NULL,
            whatsapp_number varchar(50) DEFAULT NULL,
            emergency_contact varchar(100) DEFAULT NULL,
            sport_type varchar(100) DEFAULT 'Football',
            training_group varchar(100) DEFAULT NULL,
            coach_id bigint(20) DEFAULT NULL,
            sports_level varchar(50) DEFAULT 'Beginner',
            achievements text DEFAULT NULL,
            performance_notes text DEFAULT NULL,
            attendance_rate decimal(5,2) DEFAULT '100.00',
            height_cm decimal(5,2) DEFAULT NULL,
            weight_kg decimal(5,2) DEFAULT NULL,
            bmi decimal(5,2) DEFAULT NULL,
            blood_type varchar(10) DEFAULT NULL,
            allergies text DEFAULT NULL,
            medical_conditions text DEFAULT NULL,
            injuries text DEFAULT NULL,
            surgeries text DEFAULT NULL,
            medications text DEFAULT NULL,
            fitness_notes text DEFAULT NULL,
            medical_clearance tinyint(1) DEFAULT 1,
            subscription_type varchar(100) DEFAULT 'Monthly',
            package_name varchar(100) DEFAULT 'Basic Package',
            total_sessions int(11) DEFAULT 12,
            used_sessions int(11) DEFAULT 0,
            remaining_sessions int(11) DEFAULT 12,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            status varchar(50) DEFAULT 'Active',
            freeze_periods text DEFAULT NULL,
            renewal_history text DEFAULT NULL,
            institution_id bigint(20) DEFAULT 1,
            branch_id bigint(20) DEFAULT 1,
            school_id bigint(20) DEFAULT 1,
            class_name varchar(100) DEFAULT NULL,
            section varchar(100) DEFAULT NULL,
            parent_user_id bigint(20) DEFAULT NULL,
            teacher_id bigint(20) DEFAULT NULL,
            registration_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY student_code (student_code),
            KEY branch_id (branch_id),
            KEY status (status)
        ) $charset_collate;";

        if (function_exists('dbDelta')) {
            dbDelta($sql_members);
            dbDelta($sql_employees);
            dbDelta($sql_branches);
            dbDelta($sql_programs);
            dbDelta($sql_subscriptions);
            dbDelta($sql_invoices);
            dbDelta($sql_payments);
            dbDelta($sql_attendance);
            dbDelta($sql_tokens);
            dbDelta($sql_schedules);
            dbDelta($sql_backups);
            dbDelta($sql_players);
        }

        // Default Main Branch Creation
        $default_branch = $wpdb->get_var("SELECT id FROM {$table_branches} WHERE branch_code = 'MAIN'");
        if (!$default_branch) {
            $wpdb->insert($table_branches, array(
                'branch_name' => 'Main Branch',
                'branch_code' => 'MAIN',
                'capacity'    => 1000,
                'status'      => 'Active'
            ));
        }

        if (file_exists(SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-roles.php')) {
            require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-roles.php';
            Sportedia_Roles::init_roles();
        }

        if (file_exists(SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-page-generator.php')) {
            require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-page-generator.php';
            Sportedia_Page_Generator::generate_pages();
        }

        self::migrate_legacy_data();
    }

    public static function migrate_legacy_data() {
        global $wpdb;

        $legacy_students = "{$wpdb->prefix}sm_students";
        $table_members  = "{$wpdb->prefix}sportedia_members";
        $table_players  = "{$wpdb->prefix}sportedia_players";

        if ($wpdb->get_var("SHOW TABLES LIKE '$legacy_students'") === $legacy_students) {
            $students = $wpdb->get_results("SELECT * FROM {$legacy_students}");
            if (!empty($students)) {
                foreach ($students as $s) {
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_members} WHERE member_code = %s", $s->student_code));
                    if (!$exists) {
                        $wpdb->insert($table_members, array(
                            'member_code'         => $s->student_code,
                            'first_name'          => $s->name ?? 'Member',
                            'last_name'           => '',
                            'full_name'           => $s->name ?? 'Member',
                            'phone'               => $s->guardian_phone ?? '',
                            'email'               => $s->parent_email ?? '',
                            'national_id'         => $s->national_id ?? '',
                            'joined_date'         => $s->registration_date ?? current_time('mysql'),
                            'subscription_status' => 'Active'
                        ));
                    }
                }
            }
        }
    }
}

class SM_Activator extends Sportedia_Activator {}
