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

        // 1. Players Table
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
            sport_type varchar(100) DEFAULT 'كرة القدم',
            training_group varchar(100) DEFAULT NULL,
            coach_id bigint(20) DEFAULT NULL,
            sports_level varchar(50) DEFAULT 'مبتدئ',
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
            subscription_type varchar(100) DEFAULT 'شهري',
            package_name varchar(100) DEFAULT 'الباقة الأساسية',
            total_sessions int(11) DEFAULT 12,
            used_sessions int(11) DEFAULT 0,
            remaining_sessions int(11) DEFAULT 12,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            status varchar(50) DEFAULT 'نشط',
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

        // 2. Payments Table
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

        if (function_exists('dbDelta')) {
            dbDelta($sql_players);
            dbDelta($sql_payments);
        }

        self::migrate_legacy_data();
    }

    public static function migrate_legacy_data() {
        global $wpdb;

        $legacy_students = "{$wpdb->prefix}sm_students";
        $table_players = "{$wpdb->prefix}sportedia_players";

        if ($wpdb->get_var("SHOW TABLES LIKE '$legacy_students'") === $legacy_students) {
            $students = $wpdb->get_results("SELECT * FROM {$legacy_students}");
            if (!empty($students)) {
                foreach ($students as $s) {
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_players} WHERE student_code = %s", $s->student_code));
                    if (!$exists) {
                        $wpdb->insert($table_players, array(
                            'student_code'       => $s->student_code,
                            'name'               => $s->name,
                            'guardian_phone'     => $s->guardian_phone ?? '',
                            'guardian_email'     => $s->parent_email ?? '',
                            'national_id'        => $s->national_id ?? '',
                            'nationality'        => $s->nationality ?? '',
                            'class_name'         => $s->class_name ?? '',
                            'section'            => $s->section ?? '',
                            'parent_user_id'     => $s->parent_user_id ?? null,
                            'registration_date'  => $s->registration_date ?? current_time('mysql')
                        ));
                    }
                }
            }
        }
    }
}

class SM_Activator extends Sportedia_Activator {}
