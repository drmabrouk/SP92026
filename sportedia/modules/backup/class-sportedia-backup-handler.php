<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Backup_Handler {

    /**
     * Create a database backup SQL file and log metadata in DB table.
     */
    public static function create_backup() {
        global $wpdb;

        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/sportedia-backups/';

        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            // Protect backup directory with htaccess
            file_put_contents($backup_dir . '.htaccess', 'Deny from all');
            file_put_contents($backup_dir . 'index.php', '<?php // Silence');
        }

        $filename = 'sportedia-backup-' . date('Y-m-d-H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;

        // Fetch Sportedia dynamic tables
        $tables = array(
            "{$wpdb->prefix}sportedia_members",
            "{$wpdb->prefix}sportedia_employees",
            "{$wpdb->prefix}sportedia_branches",
            "{$wpdb->prefix}sportedia_programs",
            "{$wpdb->prefix}sportedia_subscriptions",
            "{$wpdb->prefix}sportedia_invoices",
            "{$wpdb->prefix}sportedia_payments",
            "{$wpdb->prefix}sportedia_attendance",
            "{$wpdb->prefix}sportedia_working_hours"
        );

        $dump = "-- Sportedia Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $table_status = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($table_status !== $table) continue;

            $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
            $dump .= "-- Table: {$table}\n";
            foreach ($rows as $row) {
                $keys = array_map('esc_sql', array_keys($row));
                $values = array_map('esc_sql', array_values($row));
                $dump .= "INSERT INTO `{$table}` (`" . implode("`, `", $keys) . "`) VALUES ('" . implode("', '", $values) . "');\n";
            }
            $dump .= "\n";
        }

        file_put_contents($filepath, $dump);
        $filesize = filesize($filepath);

        $table_backups = "{$wpdb->prefix}sportedia_backups";
        $wpdb->insert($table_backups, array(
            'filename'   => $filename,
            'file_path'  => $filepath,
            'file_size'  => $filesize,
            'created_by' => get_current_user_id()
        ));

        return array('success' => true, 'filename' => $filename);
    }
}
