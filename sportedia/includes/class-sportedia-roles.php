<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Roles {

    const ROLES_VERSION = '1.0.3';

    public static function get_capabilities() {
        return array(
            'sportedia_access',
            'sportedia_manage_settings',
            'sportedia_manage_users',
            'sportedia_view_users',
            'sportedia_create_users',
            'sportedia_edit_users',
            'sportedia_delete_users',
            'sportedia_manage_branches',
            'sportedia_manage_members',
            'sportedia_view_members',
            'sportedia_create_members',
            'sportedia_edit_members',
            'sportedia_delete_members',
            'sportedia_manage_sports',
            'sportedia_manage_programs',
            'sportedia_manage_subscriptions',
            'sportedia_manage_renewals',
            'sportedia_manage_finance',
            'sportedia_view_finance',
            'sportedia_export_finance',
            'sportedia_manage_attendance',
            'sportedia_view_attendance'
        );
    }

    public static function init_roles() {
        if (!function_exists('add_role') || !function_exists('get_role')) {
            return;
        }

        $all_caps = self::get_capabilities();

        // Native WordPress Administrator always retains full unrestricted access
        $wp_admin = get_role('administrator');
        if ($wp_admin) {
            foreach ($all_caps as $cap) {
                $wp_admin->add_cap($cap);
            }
        }

        // 1. System Administrator
        add_role('sportedia_system_admin', 'System Administrator', array_fill_keys($all_caps, true));

        // 2. General Manager
        add_role('sportedia_general_manager', 'General Manager', array_fill_keys($all_caps, true));

        // 3. Administrative Manager
        $admin_mgr_caps = array(
            'sportedia_access'             => true,
            'sportedia_manage_users'       => true,
            'sportedia_view_users'        => true,
            'sportedia_manage_branches'    => true,
            'sportedia_manage_members'     => true,
            'sportedia_view_members'       => true,
            'sportedia_manage_programs'    => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals'    => true,
            'sportedia_view_finance'       => true,
            'sportedia_manage_attendance'  => true
        );
        add_role('sportedia_admin_manager', 'Administrative Manager', $admin_mgr_caps);

        // 4. Sports Supervisor
        $supervisor_caps = array(
            'sportedia_access'             => true,
            'sportedia_manage_sports'      => true,
            'sportedia_manage_programs'    => true,
            'sportedia_view_members'       => true,
            'sportedia_manage_attendance'  => true,
            'sportedia_view_attendance'    => true
        );
        add_role('sportedia_sports_supervisor', 'Sports Supervisor', $supervisor_caps);

        // 5. Coach
        $coach_caps = array(
            'sportedia_access'             => true,
            'sportedia_view_members'       => true,
            'sportedia_view_attendance'    => true
        );
        add_role('sportedia_coach', 'Coach', $coach_caps);

        // 6. Receptionist
        $reception_caps = array(
            'sportedia_access'             => true,
            'sportedia_view_members'       => true,
            'sportedia_create_members'     => true,
            'sportedia_edit_members'       => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals'    => true
        );
        add_role('sportedia_receptionist', 'Receptionist', $reception_caps);

        // 7. Finance Officer
        $finance_caps = array(
            'sportedia_access'             => true,
            'sportedia_manage_finance'     => true,
            'sportedia_view_finance'       => true,
            'sportedia_export_finance'     => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals'    => true
        );
        add_role('sportedia_finance_officer', 'Finance Officer', $finance_caps);

        // 8. Attendance Supervisor
        $attendance_caps = array(
            'sportedia_access'             => true,
            'sportedia_manage_attendance'  => true,
            'sportedia_view_attendance'    => true
        );
        add_role('sportedia_attendance_supervisor', 'Attendance Supervisor', $attendance_caps);

        update_option('sportedia_roles_version', self::ROLES_VERSION);
    }
}
