<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @updated    2.3.1 - Improved uninstallation process
 * @package    WP_Total_Monitor
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Class to handle clean uninstallation of the plugin
 * Using a class ensures better encapsulation and follows WordPress best practices
 * 
 * @since 2.3.1
 */
class WP_Total_Monitor_Uninstaller {
    /**
     * Run the uninstaller
     */
    public static function uninstall() {
        // Check if we should remove data
        if (self::should_remove_data()) {
            self::delete_tables();
            self::delete_options();
            self::clear_scheduled_events();
            self::clear_cache();
        }
    }
    
    /**
     * Check if we should remove plugin data
     * 
     * @return bool Whether to remove data
     */
    private static function should_remove_data() {
        return (bool) get_option('wp_total_monitor_remove_on_uninstall', false);
    }
    
    /**
     * Delete custom database tables
     */
    private static function delete_tables() {
        global $wpdb;
        $wpdb->hide_errors();
        
        // Usar función query_multiple de WP para esquemas
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        
        // Usar dbDelta con consulta DROP para mayor compatibilidad
        $sql = "DROP TABLE IF EXISTS `" . esc_sql($table_name) . "`;";
        dbDelta($sql);
    }
    
    /**
     * Delete all plugin options
     */
    private static function delete_options() {
        // Lista de todas las opciones del plugin
        $options = [
            'wp_total_monitor_retention',
            'wp_total_monitor_remove_on_uninstall',
            'wp_total_monitor_admin_language'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Clear any scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('wp_total_monitor_cleanup_logs');
    }
    
    /**
     * Clear plugin caches
     */
    private static function clear_cache() {
        // Grupos de caché utilizados por el plugin
        $cache_groups = [
            'wp_total_monitor_dashboard',
            'wp_total_monitor_logs',
            'wp_total_monitor_settings'
        ];
        
        // Intentar limpiar cachés específicas del plugin
        foreach ($cache_groups as $group) {
            wp_cache_delete('total_count', $group);
        }
        
        // Como precaución adicional, vaciar todas las cachés
        wp_cache_flush();
    }
}

// Ejecutar el desinstalador
WP_Total_Monitor_Uninstaller::uninstall();
