<?php
/**
 * Plugin Name: Sportedia
 * Plugin URI: https://sportedia.app
 * Description: Sportedia - Sports Academies & Clubs Management System
 * Version: 1.0.0
 * Author: Sportedia Platform
 * Author URI: https://sportedia.app
 * Language: ar
 * Text Domain: sportedia
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SPORTEDIA_VERSION', '1.0.0');
define('SPORTEDIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPORTEDIA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Backward compatibility constants
define('SM_VERSION', SPORTEDIA_VERSION);
define('SM_PLUGIN_DIR', SPORTEDIA_PLUGIN_DIR);
define('SM_PLUGIN_URL', SPORTEDIA_PLUGIN_URL);

/**
 * Load Centralized Organization Structure Helper
 */
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-org-helper.php';

/**
 * The code that runs during plugin activation.
 */
function activate_sportedia() {
    require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-activator.php';
    Sportedia_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_sportedia() {
    require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-deactivator.php';
    Sportedia_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_sportedia');
register_deactivation_hook(__FILE__, 'deactivate_sportedia');

/**
 * Core class used to maintain the plugin.
 */
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia.php';

function run_sportedia() {
    $plugin = new Sportedia();
    $plugin->run();
}

run_sportedia();
