<?php
/**
 * Handle Ajax requests for the plugin
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

class WP_Total_Monitor_Ajax {

    /**
     * Initialize the class and set up Ajax hooks
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action('wp_ajax_wp_total_monitor_delete_logs', array($this, 'delete_logs'));
    }
    
    /**
     * Delete logs via Ajax
     *
     * @since    1.0.0
     */
    public function delete_logs() {
        // Check for nonce security
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['nonce'])), 'wp_total_monitor_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'total-activity-monitor')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'total-activity-monitor')));
        }
        
        // Delete logs
        require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-logger.php';
        $logger = new WP_Total_Monitor_Logger();
        
        // Empty filters will delete all logs
        $deleted = $logger->delete_logs();
        
        if ($deleted !== false) {
            wp_send_json_success(array(
                'message' => sprintf(esc_html__('Successfully deleted %d logs.', 'total-activity-monitor'), $deleted)
            ));
        } else {
            wp_send_json_error(array('message' => esc_html__('Failed to delete logs.', 'total-activity-monitor')));
        }
    }
}
