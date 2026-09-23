<?php

if (!defined('ABSPATH')) exit;

class Sportedia_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_menu_pages() {
        $access_cap = current_user_can('sportedia_access') ? 'sportedia_access' : 'read';
        $players_cap = current_user_can('sportedia_view_players') ? 'sportedia_view_players' : 'read';
        $coaches_cap = current_user_can('sportedia_manage_coaches') ? 'sportedia_manage_coaches' : 'read';
        $payments_cap = current_user_can('sportedia_view_payments') ? 'sportedia_view_payments' : 'read';
        $settings_cap = current_user_can('sportedia_manage_settings') ? 'sportedia_manage_settings' : 'manage_options';

        add_menu_page(
            'Sportedia – إدارة الأكاديميات والأندية الرياضية',
            'Sportedia',
            $access_cap,
            'sportedia-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-sports',
            6
        );

        add_submenu_page(
            'sportedia-dashboard',
            'لوحة التحكم',
            'لوحة التحكم',
            $access_cap,
            'sportedia-dashboard',
            array($this, 'display_dashboard')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'شؤون اللاعبين',
            'شؤون اللاعبين',
            $players_cap,
            'sportedia-players',
            array($this, 'display_players')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'المدربون والأنشطة',
            'المدربون والأنشطة',
            $coaches_cap,
            'sportedia-coaches',
            array($this, 'display_coaches')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'الاشتراكات والمدفوعات',
            'الاشتراكات والمدفوعات',
            $payments_cap,
            'sportedia-payments',
            array($this, 'display_payments')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'إعدادات المنظمة الرياضية',
            'إعدادات المنظمة',
            $settings_cap,
            'sportedia-settings',
            array($this, 'display_settings')
        );
    }

    public function enqueue_styles($hook = '') {
        wp_enqueue_style('google-font-cairo', 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap', array(), null);
        wp_enqueue_style($this->plugin_name, SPORTEDIA_PLUGIN_URL . 'assets/css/sm-admin.css', array(), $this->version, 'all');
    }

    public function display_dashboard() {
        $_GET['sm_tab'] = 'summary';
        $this->display_settings();
    }

    public function display_players() {
        $_GET['sm_tab'] = 'students';
        $this->display_settings();
    }

    public function display_coaches() {
        $_GET['sm_tab'] = 'teachers';
        $this->display_settings();
    }

    public function display_payments() {
        $_GET['sm_tab'] = 'payments';
        $this->display_settings();
    }

    public function display_settings() {
        if (!current_user_can('read') && !current_user_can('sportedia_access') && !current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'sportedia'));
        }

        $student_filters = array();
        $stats = Sportedia_DB::get_statistics();
        $records = array();
        $students = Sportedia_DB::get_students();
        include SPORTEDIA_PLUGIN_DIR . 'templates/public-admin-panel.php';
    }
}

class SM_Admin extends Sportedia_Admin {}
