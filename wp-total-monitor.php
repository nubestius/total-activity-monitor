<?php
/**
 * Plugin Name: Total Activity Monitor
 * Plugin URI: https://shieldpress.co/plugins/total-activity-monitor
 * Description: Monitor and log all user activities on your WordPress site with detailed tracking and reports.
 * Version: 2.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: ShieldPress
 * Author URI: https://shieldpress.co
 * Text Domain: wp-total-monitor
 * Note: Although the plugin name has changed, we maintain the original text domain for compatibility
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: LICENSE.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_TOTAL_MONITOR_VERSION', '2.0.0');
define('WP_TOTAL_MONITOR_FILE', __FILE__);
define('WP_TOTAL_MONITOR_PATH', plugin_dir_path(__FILE__));
define('WP_TOTAL_MONITOR_URL', plugin_dir_url(__FILE__));

// Require the main plugin class
require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor.php';

// Initialize language support early
function wp_total_monitor_load_language() {
    // Load plugin text domain for translations (default language support)
    load_plugin_textdomain(
        'wp-total-monitor',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'wp_total_monitor_load_language', 5); // Priority 5 = early

// Initialize the plugin
function wp_total_monitor_init() {
    $plugin = new WP_Total_Monitor();
    $plugin->init();
}
add_action('plugins_loaded', 'wp_total_monitor_init');

// Register activation hook
register_activation_hook(__FILE__, 'wp_total_monitor_activate');
function wp_total_monitor_activate() {
    require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-activator.php';
    WP_Total_Monitor_Activator::activate();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'wp_total_monitor_deactivate');
function wp_total_monitor_deactivate() {
    require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-deactivator.php';
    WP_Total_Monitor_Deactivator::deactivate();
}
