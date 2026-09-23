<?php

if (!defined('ABSPATH')) exit;

class Sportedia {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'sportedia';
        $this->version = defined('SPORTEDIA_VERSION') ? SPORTEDIA_VERSION : '1.0.0';
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sm-loader.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-db.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-settings.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-logger.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-id-code-service.php';
        if (file_exists(SPORTEDIA_PLUGIN_DIR . 'includes/class-eess-file-naming-service.php')) {
            require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-eess-file-naming-service.php';
        }
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-player-data-service.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-notifications.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'admin/class-sportedia-admin.php';
        require_once SPORTEDIA_PLUGIN_DIR . 'public/class-sportedia-public.php';
        $this->loader = new SM_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Sportedia_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new SM_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}

class School_Management extends Sportedia {}
