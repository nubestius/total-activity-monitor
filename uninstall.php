<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get the option value for whether to delete data on uninstall
// This option should be added to your settings page in the future
$remove_data = get_option('wp_total_monitor_remove_on_uninstall', false);

// If set to remove data, clear all plugin data
if ($remove_data) {
    // Delete the logs table
    global $wpdb;
    $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    // Delete all plugin options
    delete_option('wp_total_monitor_retention');
    delete_option('wp_total_monitor_remove_on_uninstall');
}
