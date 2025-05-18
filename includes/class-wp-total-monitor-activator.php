<?php
/**
 * Class responsible for plugin activation
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

class WP_Total_Monitor_Activator {

    /**
     * Create the database table for storing activity logs
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            username varchar(60) NOT NULL,
            user_role varchar(60) NOT NULL,
            action_type varchar(255) NOT NULL,
            action_description text NOT NULL,
            object_type varchar(255) DEFAULT '',
            object_id bigint(20) DEFAULT NULL,
            ip_address varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add the retention option (default to 30 days)
        add_option('wp_total_monitor_retention', '30');
    }
}
