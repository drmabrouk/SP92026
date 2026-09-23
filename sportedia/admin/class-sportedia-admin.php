<?php

class Sportedia_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_menu_pages() {
        add_menu_page(
            'Sportedia – إدارة الأكاديميات والأندية الرياضية',
            'Sportedia',
            'read',
            'sportedia-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-sports',
            6
        );

        add_submenu_page(
            'sportedia-dashboard',
            'لوحة التحكم',
            'لوحة التحكم',
            'read',
            'sportedia-dashboard',
            array($this, 'display_dashboard')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'شؤون اللاعبين',
            'شؤون اللاعبين',
            'read',
            'sportedia-players',
            array($this, 'display_players')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'المدربون والأنشطة',
            'المدربون والأنشطة',
            'read',
            'sportedia-coaches',
            array($this, 'display_coaches')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'الاشتراكات والمدفوعات',
            'الاشتراكات والمدفوعات',
            'read',
            'sportedia-payments',
            array($this, 'display_payments')
        );

        add_submenu_page(
            'sportedia-dashboard',
            'إعدادات المنظمة الرياضية',
            'إعدادات المنظمة',
            'manage_options',
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
        $student_filters = array();
        $stats = Sportedia_DB::get_statistics();
        $records = array();
        $students = Sportedia_DB::get_students();
        include SPORTEDIA_PLUGIN_DIR . 'templates/public-admin-panel.php';
    }
}

class SM_Admin extends Sportedia_Admin {}
