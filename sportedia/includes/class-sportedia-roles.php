<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Roles {

    const ROLES_VERSION = '1.0.2';

    public static function get_capabilities() {
        return array(
            'sportedia_access',
            'sportedia_manage_settings',
            'sportedia_manage_organizations',
            'sportedia_manage_branches',
            'sportedia_manage_members',
            'sportedia_view_members',
            'sportedia_create_members',
            'sportedia_edit_members',
            'sportedia_delete_members',
            'sportedia_manage_employees',
            'sportedia_view_employees',
            'sportedia_create_employees',
            'sportedia_edit_employees',
            'sportedia_delete_employees',
            'sportedia_manage_players',
            'sportedia_view_players',
            'sportedia_create_players',
            'sportedia_edit_players',
            'sportedia_delete_players',
            'sportedia_manage_coaches',
            'sportedia_manage_training_groups',
            'sportedia_manage_attendance',
            'sportedia_manage_subscriptions',
            'sportedia_manage_renewals',
            'sportedia_manage_payments',
            'sportedia_view_payments',
            'sportedia_manage_invoices',
            'sportedia_print_invoices',
            'sportedia_send_invoices',
            'sportedia_view_health',
            'sportedia_manage_health',
            'sportedia_view_reports',
            'sportedia_manage_reports',
            'sportedia_manage_users',
            'sportedia_manage_roles',
            'sportedia_manage_permissions'
        );
    }

    public static function init_roles() {
        if (!function_exists('add_role') || !function_exists('get_role')) {
            return;
        }

        $all_caps = self::get_capabilities();

        // 1. Grant all Sportedia capabilities to native WordPress Administrator
        $wp_admin = get_role('administrator');
        if ($wp_admin) {
            foreach ($all_caps as $cap) {
                $wp_admin->add_cap($cap);
            }
        }

        // 2. Sportedia System Administrator
        add_role('sportedia_system_admin', 'Sportedia System Administrator', array_fill_keys($all_caps, true));

        // 3. Branch Manager
        $branch_caps = array(
            'sportedia_access'            => true,
            'sportedia_manage_branches'   => true,
            'sportedia_manage_members'    => true,
            'sportedia_view_members'      => true,
            'sportedia_create_members'    => true,
            'sportedia_edit_members'      => true,
            'sportedia_manage_employees'  => true,
            'sportedia_view_employees'    => true,
            'sportedia_manage_attendance' => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals'   => true,
            'sportedia_manage_payments'   => true,
            'sportedia_view_payments'     => true,
            'sportedia_manage_invoices'   => true,
            'sportedia_print_invoices'    => true,
            'sportedia_view_reports'      => true
        );
        add_role('sportedia_branch_manager', 'Branch Manager', $branch_caps);

        // 4. Staff / Employee
        $staff_caps = array(
            'sportedia_access'            => true,
            'sportedia_view_members'      => true,
            'sportedia_create_members'    => true,
            'sportedia_manage_attendance' => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals'   => true,
            'sportedia_manage_invoices'   => true
        );
        add_role('sportedia_staff', 'Sportedia Staff', $staff_caps);

        update_option('sportedia_roles_version', self::ROLES_VERSION);
    }
}
