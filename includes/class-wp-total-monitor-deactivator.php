<?php
/**
 * Class responsible for plugin deactivation
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

class WP_Total_Monitor_Deactivator {

    /**
     * Plugin deactivation tasks
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Currently we don't remove the database table on deactivation
        // This ensures logs are preserved if the plugin is temporarily deactivated
        
        // If you want to cleanup scheduled events
        wp_clear_scheduled_hook('wp_total_monitor_cleanup_logs');
    }
}
