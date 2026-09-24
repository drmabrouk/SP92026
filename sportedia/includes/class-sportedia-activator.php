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

        // 1. Sports Table
        $table_sports = "{$wpdb->prefix}sportedia_sports";
        $sql_sports = "CREATE TABLE $table_sports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sport_name varchar(100) NOT NULL,
            sport_code varchar(50) NOT NULL,
            description text DEFAULT NULL,
            status varchar(50) DEFAULT 'Active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY sport_code (sport_code)
        ) $charset_collate;";

        // 2. Programs Table (linked to Sport)
        $table_programs = "{$wpdb->prefix}sportedia_programs";
        $sql_programs = "CREATE TABLE $table_programs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sport_id bigint(20) DEFAULT NULL,
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
            KEY sport_id (sport_id),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 3. Members Table
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

        // 4. System Users Table
        $table_users = "{$wpdb->prefix}sportedia_users";
        $sql_users = "CREATE TABLE $table_users (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_code varchar(50) NOT NULL,
            wp_user_id bigint(20) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            full_name varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            branch_id bigint(20) DEFAULT 1,
            role_slug varchar(50) DEFAULT 'sportedia_coach',
            status varchar(50) DEFAULT 'Active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_code (user_code),
            KEY wp_user_id (wp_user_id),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 5. Employees Table
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

        // 6. Branches Table
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

        // 7. Subscriptions Table
        $table_subscriptions = "{$wpdb->prefix}sportedia_subscriptions";
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            member_id bigint(20) NOT NULL,
            program_id bigint(20) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            price decimal(10,2) NOT NULL,
            status varchar(50) DEFAULT 'Active',
            invoice_id bigint(20) DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY program_id (program_id),
            KEY status (status)
        ) $charset_collate;";

        // 8. Invoices Table
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

        // 9. Attendance Table
        $table_attendance = "{$wpdb->prefix}sportedia_attendance";
        $sql_attendance = "CREATE TABLE $table_attendance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
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
            KEY user_id (user_id),
            KEY employee_id (employee_id),
            KEY scan_date (scan_date),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 10. Attendance Tokens Table
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

        // 11. Branch Schedules Table
        $table_schedules = "{$wpdb->prefix}sportedia_branch_schedules";
        $sql_schedules = "CREATE TABLE $table_schedules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) NOT NULL,
            working_days text DEFAULT NULL,
            open_time time DEFAULT '08:00:00',
            close_time time DEFAULT '22:00:00',
            late_threshold_mins int(11) DEFAULT 15,
            deduction_amount decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset_collate;";

        // 12. Backups Table
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

        if (function_exists('dbDelta')) {
            dbDelta($sql_sports);
            dbDelta($sql_programs);
            dbDelta($sql_members);
            dbDelta($sql_users);
            dbDelta($sql_employees);
            dbDelta($sql_branches);
            dbDelta($sql_subscriptions);
            dbDelta($sql_invoices);
            dbDelta($sql_attendance);
            dbDelta($sql_tokens);
            dbDelta($sql_schedules);
            dbDelta($sql_backups);
        }

        // Seed initial sports
        $count_sports = $wpdb->get_var("SELECT COUNT(*) FROM {$table_sports}");
        if (!$count_sports) {
            $wpdb->insert($table_sports, array('sport_name' => 'Swimming', 'sport_code' => 'SWIM'));
            $wpdb->insert($table_sports, array('sport_name' => 'Football', 'sport_code' => 'FOOT'));
            $wpdb->insert($table_sports, array('sport_name' => 'Basketball', 'sport_code' => 'BASKET'));
        }

        // Default Main Branch
        $default_branch = $wpdb->get_var("SELECT id FROM {$table_branches} WHERE branch_code = 'MAIN'");
        if (!$default_branch) {
            $wpdb->insert($table_branches, array(
                'branch_name' => 'Main Branch',
                'branch_code' => 'MAIN',
                'capacity'    => 1000,
                'status'      => 'Active'
            ));
        }

        self::update_subscription_expirations();

        if (file_exists(SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-roles.php')) {
            require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-roles.php';
            Sportedia_Roles::init_roles();
        }

        if (file_exists(SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-page-generator.php')) {
            require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-page-generator.php';
            Sportedia_Page_Generator::generate_pages();
        }
    }

    public static function update_subscription_expirations() {
        global $wpdb;
        $table_subscriptions = "{$wpdb->prefix}sportedia_subscriptions";
        $table_members       = "{$wpdb->prefix}sportedia_members";
        $today               = date('Y-m-d');

        // Mark subscriptions expired
        $wpdb->query($wpdb->prepare("UPDATE {$table_subscriptions} SET status = 'Expired' WHERE end_date < %s AND status = 'Active'", $today));

        // Sync member status
        $wpdb->query("UPDATE {$table_members} m SET subscription_status = 'Expired' WHERE id IN (SELECT member_id FROM {$table_subscriptions} WHERE status = 'Expired')");
    }
}

class SM_Activator extends Sportedia_Activator {}
