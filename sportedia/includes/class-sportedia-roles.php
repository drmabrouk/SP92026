<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Roles {

    const ROLES_VERSION = '1.0.0';

    public static function get_capabilities() {
        return array(
            'sportedia_access',
            'sportedia_manage_settings',
            'sportedia_manage_organizations',
            'sportedia_manage_branches',
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
            'sportedia_manage_permissions',
            'sportedia_view_audit_logs'
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

        // 3. Sports Organization Administrator
        $org_caps = array(
            'sportedia_access' => true,
            'sportedia_manage_organizations' => true,
            'sportedia_manage_branches' => true,
            'sportedia_manage_players' => true,
            'sportedia_view_players' => true,
            'sportedia_create_players' => true,
            'sportedia_edit_players' => true,
            'sportedia_manage_coaches' => true,
            'sportedia_manage_training_groups' => true,
            'sportedia_manage_attendance' => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals' => true,
            'sportedia_manage_payments' => true,
            'sportedia_view_payments' => true,
            'sportedia_manage_invoices' => true,
            'sportedia_print_invoices' => true,
            'sportedia_send_invoices' => true,
            'sportedia_view_reports' => true,
            'sportedia_manage_reports' => true,
            'sportedia_manage_users' => true
        );
        add_role('sportedia_org_admin', 'Sports Organization Administrator', $org_caps);

        // 4. Branch Manager
        $branch_caps = array(
            'sportedia_access' => true,
            'sportedia_manage_branches' => true,
            'sportedia_manage_players' => true,
            'sportedia_view_players' => true,
            'sportedia_create_players' => true,
            'sportedia_edit_players' => true,
            'sportedia_manage_coaches' => true,
            'sportedia_manage_training_groups' => true,
            'sportedia_manage_attendance' => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals' => true,
            'sportedia_manage_payments' => true,
            'sportedia_view_payments' => true,
            'sportedia_manage_invoices' => true,
            'sportedia_print_invoices' => true,
            'sportedia_send_invoices' => true,
            'sportedia_view_reports' => true
        );
        add_role('sportedia_branch_manager', 'Branch Manager', $branch_caps);

        // 5. Sports Supervisor
        $sup_caps = array(
            'sportedia_access' => true,
            'sportedia_view_players' => true,
            'sportedia_manage_training_groups' => true,
            'sportedia_manage_attendance' => true,
            'sportedia_view_reports' => true
        );
        add_role('sportedia_sports_supervisor', 'Sports Supervisor', $sup_caps);

        // 6. Coach
        $coach_caps = array(
            'sportedia_access' => true,
            'sportedia_view_players' => true,
            'sportedia_manage_attendance' => true,
            'sportedia_view_health' => true
        );
        add_role('sportedia_coach', 'Coach', $coach_caps);

        // 7. Player Affairs Officer
        $affairs_caps = array(
            'sportedia_access' => true,
            'sportedia_manage_players' => true,
            'sportedia_view_players' => true,
            'sportedia_create_players' => true,
            'sportedia_edit_players' => true,
            'sportedia_manage_subscriptions' => true,
            'sportedia_manage_renewals' => true,
            'sportedia_manage_attendance' => true
        );
        add_role('sportedia_player_affairs', 'Player Affairs Officer', $affairs_caps);

        // 8. Finance Officer
        $finance_caps = array(
            'sportedia_access' => true,
            'sportedia_manage_payments' => true,
            'sportedia_view_payments' => true,
            'sportedia_manage_invoices' => true,
            'sportedia_print_invoices' => true,
            'sportedia_send_invoices' => true,
            'sportedia_view_reports' => true
        );
        add_role('sportedia_finance_officer', 'Finance Officer', $finance_caps);

        // 9. Reception / Registration Officer
        $reception_caps = array(
            'sportedia_access' => true,
            'sportedia_view_players' => true,
            'sportedia_create_players' => true,
            'sportedia_manage_renewals' => true,
            'sportedia_view_payments' => true
        );
        add_role('sportedia_receptionist', 'Reception / Registration Officer', $reception_caps);

        // 10. Medical / Health Staff
        $medical_caps = array(
            'sportedia_access' => true,
            'sportedia_view_players' => true,
            'sportedia_view_health' => true,
            'sportedia_manage_health' => true
        );
        add_role('sportedia_medical_staff', 'Medical / Health Staff', $medical_caps);

        update_option('sportedia_roles_version', self::ROLES_VERSION);
    }
}
