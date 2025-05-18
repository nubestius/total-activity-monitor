<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

class WP_Total_Monitor_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Check if CSV export action is requested
        add_action('admin_init', array($this, 'maybe_export_csv'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     * @updated  1.1.0 - Added dashboard styles
     */
    public function enqueue_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wp-total-monitor') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wp-total-monitor-admin',
            WP_TOTAL_MONITOR_URL . 'admin/css/wp-total-monitor-admin.css',
            array(),
            WP_TOTAL_MONITOR_VERSION,
            'all'
        );
        
        // Dashboard styles (only on dashboard page)
        if (strpos($hook, 'wp-total-monitor-dashboard') !== false) {
            wp_enqueue_style(
                'wp-total-monitor-dashboard',
                WP_TOTAL_MONITOR_URL . 'admin/css/wp-total-monitor-dashboard.css',
                array('wp-total-monitor-admin'),
                WP_TOTAL_MONITOR_VERSION,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @updated  1.1.0 - Added Chart.js for dashboard
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wp-total-monitor') === false) {
            return;
        }
        
        wp_enqueue_script(
            'wp-total-monitor-admin',
            WP_TOTAL_MONITOR_URL . 'admin/js/wp-total-monitor-admin.js',
            array('jquery'),
            WP_TOTAL_MONITOR_VERSION,
            true
        );
        
        // Add translations/variables to script
        wp_localize_script(
            'wp-total-monitor-admin',
            'wpTotalMonitor',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_total_monitor_nonce'),
                'confirmDelete' => __('Are you sure you want to delete these logs? This action cannot be undone.', 'wp-total-monitor')
            )
        );
        
        // Dashboard scripts
        if (strpos($hook, 'wp-total-monitor-dashboard') !== false) {
            // Enqueue Chart.js from CDN (in a production plugin, you'd include the file locally)
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
        }
    }

    /**
     * Register the admin menu
     *
     * @since    1.0.0
     * @updated  1.1.0 - Added dashboard page
     */
    public function add_plugin_admin_menu() {
        // Main menu - Changed main link to point to dashboard
        add_menu_page(
            __('WP Total Monitor', 'wp-total-monitor'),
            __('Total Monitor', 'wp-total-monitor'),
            'manage_options',
            'wp-total-monitor-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-visibility',
            90
        );
        
        // Dashboard subpage
        add_submenu_page(
            'wp-total-monitor-dashboard',
            __('Dashboard', 'wp-total-monitor'),
            __('Dashboard', 'wp-total-monitor'),
            'manage_options',
            'wp-total-monitor-dashboard',
            array($this, 'display_dashboard_page')
        );
        
        // Logs subpage
        add_submenu_page(
            'wp-total-monitor-dashboard',
            __('Activity Logs', 'wp-total-monitor'),
            __('Activity Logs', 'wp-total-monitor'),
            'manage_options',
            'wp-total-monitor-logs',
            array($this, 'display_logs_page')
        );
        
        // Settings subpage
        add_submenu_page(
            'wp-total-monitor-dashboard',
            __('Settings', 'wp-total-monitor'),
            __('Settings', 'wp-total-monitor'),
            'manage_options',
            'wp-total-monitor-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Register plugin settings
     *
     * @since    1.0.0
     * @updated  1.1.0 - Added language selection option
     */
    public function register_settings() {
        // Register setting for log retention
        register_setting(
            'wp_total_monitor_settings',
            'wp_total_monitor_retention',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_retention_setting'),
                'default' => '30'
            )
        );
        
        // Register setting for admin language
        register_setting(
            'wp_total_monitor_settings',
            'wp_total_monitor_admin_language',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_language_setting'),
                'default' => 'site-default'
            )
        );
        
        // Add settings section
        add_settings_section(
            'wp_total_monitor_general_section',
            __('General Settings', 'wp-total-monitor'),
            array($this, 'render_settings_section'),
            'wp_total_monitor_settings'
        );
        
        // Add language settings section
        add_settings_section(
            'wp_total_monitor_language_section',
            __('Language Settings', 'wp-total-monitor'),
            array($this, 'render_language_section'),
            'wp_total_monitor_settings'
        );
        
        // Add settings field
        add_settings_field(
            'wp_total_monitor_retention',
            __('Log Retention Period', 'wp-total-monitor'),
            array($this, 'render_retention_field'),
            'wp_total_monitor_settings',
            'wp_total_monitor_general_section'
        );
        
        // Add language settings field
        add_settings_field(
            'wp_total_monitor_admin_language',
            __('Admin Interface Language', 'wp-total-monitor'),
            array($this, 'render_language_field'),
            'wp_total_monitor_settings',
            'wp_total_monitor_language_section'
        );
    }
    
    /**
     * Sanitize the retention setting
     *
     * @since    1.0.0
     * @param    string    $value    The value to sanitize.
     * @return   string    The sanitized value.
     */
    public function sanitize_retention_setting($value) {
        $valid_options = array('5', '10', '30', 'forever');
        
        if (!in_array($value, $valid_options)) {
            add_settings_error(
                'wp_total_monitor_retention',
                'wp_total_monitor_retention_error',
                __('Invalid log retention period', 'wp-total-monitor'),
                'error'
            );
            
            // Return the previous value if the new one is invalid
            return get_option('wp_total_monitor_retention', '30');
        }
        
        return $value;
    }
    
    /**
     * Sanitize the language setting
     *
     * @since    1.1.0
     * @param    string    $value    The value to sanitize.
     * @return   string    The sanitized value.
     */
    public function sanitize_language_setting($value) {
        $available_languages = $this->get_available_languages();
        $valid_options = array_merge(array('site-default'), array_keys($available_languages));
        
        if (!in_array($value, $valid_options)) {
            add_settings_error(
                'wp_total_monitor_admin_language',
                'wp_total_monitor_admin_language_error',
                __('Invalid language selection', 'wp-total-monitor'),
                'error'
            );
            
            // Return the previous value if the new one is invalid
            return get_option('wp_total_monitor_admin_language', 'site-default');
        }
        
        return $value;
    }
    
    /**
     * Render the settings section
     *
     * @since    1.0.0
     */
    public function render_settings_section() {
        echo '<p>' . __('Configure how long the activity logs should be retained before automatic deletion.', 'wp-total-monitor') . '</p>';
    }
    
    /**
     * Render the language settings section
     *
     * @since    1.1.0
     */
    public function render_language_section() {
        echo '<p>' . __('Choose in which language you want to display the admin interface of WP Total Monitor.', 'wp-total-monitor') . '</p>';
    }
    
    /**
     * Render the retention period field
     *
     * @since    1.0.0
     */
    public function render_retention_field() {
        $retention = get_option('wp_total_monitor_retention', '30');
        ?>
        <select name="wp_total_monitor_retention" id="wp_total_monitor_retention">
            <option value="5" <?php selected($retention, '5'); ?>><?php _e('5 days', 'wp-total-monitor'); ?></option>
            <option value="10" <?php selected($retention, '10'); ?>><?php _e('10 days', 'wp-total-monitor'); ?></option>
            <option value="30" <?php selected($retention, '30'); ?>><?php _e('30 days', 'wp-total-monitor'); ?></option>
            <option value="forever" <?php selected($retention, 'forever'); ?>><?php _e('Forever (never delete)', 'wp-total-monitor'); ?></option>
        </select>
        <p class="description"><?php _e('Select how long to keep activity logs before they are automatically deleted.', 'wp-total-monitor'); ?></p>
        <?php
    }
    
    /**
     * Display the dashboard page
     *
     * @since    1.1.0
     */
    public function display_dashboard_page() {
        require_once WP_TOTAL_MONITOR_PATH . 'admin/partials/wp-total-monitor-admin-dashboard.php';
    }
    
    /**
     * Display the logs page
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        require_once WP_TOTAL_MONITOR_PATH . 'admin/partials/wp-total-monitor-admin-logs.php';
    }
    
    /**
     * Get available languages for the plugin
     *
     * @since    1.1.0
     * @return   array    Available languages with code => name format
     */
    public function get_available_languages() {
        $languages = array(
            'en_US' => __('English (United States)', 'wp-total-monitor'),
            'es_ES' => __('Spanish (Spain)', 'wp-total-monitor'),
            'fr_FR' => __('French (France)', 'wp-total-monitor'),
            'de_DE' => __('German (Germany)', 'wp-total-monitor'),
            'it_IT' => __('Italian (Italy)', 'wp-total-monitor'),
            'pt_BR' => __('Portuguese (Brazil)', 'wp-total-monitor')
        );
        
        return $languages;
    }
    
    /**
     * Render the language field
     *
     * @since    1.1.0
     */
    public function render_language_field() {
        $language = get_option('wp_total_monitor_admin_language', 'site-default');
        $available_languages = $this->get_available_languages();
        
        echo '<select name="wp_total_monitor_admin_language" id="wp_total_monitor_admin_language">';
        echo '<option value="site-default"' . selected($language, 'site-default', false) . '>' . __('Use Site Default', 'wp-total-monitor') . '</option>';
        
        foreach ($available_languages as $code => $name) {
            echo '<option value="' . esc_attr($code) . '"' . selected($language, $code, false) . '>' . esc_html($name) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . __('Select which language to use for the plugin admin interface.', 'wp-total-monitor') . '</p>';
    }
    
    /**
     * Display the settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        require_once WP_TOTAL_MONITOR_PATH . 'admin/partials/wp-total-monitor-admin-settings.php';
    }
    
    /**
     * Check if CSV export is requested and export logs if needed
     *
     * @since    1.1.0
     */
    public function maybe_export_csv() {
        if (isset($_GET['page']) && $_GET['page'] === 'wp-total-monitor-logs' && 
            isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            
            // Verify the nonce for security
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wp_total_monitor_export_csv')) {
                wp_die(__('Security check failed. Please try again.', 'wp-total-monitor'));
            }
            
            // Permission check
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'wp-total-monitor'));
            }
            
            // Process filters
            $filters = array();
            
            // User filter
            if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
                $filters['user_id'] = intval($_GET['user_id']);
            }
            
            // Action type filter
            if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
                $filters['action_type'] = sanitize_text_field($_GET['action_type']);
            }
            
            // Date range filter
            if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                $filters['date_from'] = sanitize_text_field($_GET['date_from']);
            }
            
            if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                $filters['date_to'] = sanitize_text_field($_GET['date_to']);
            }
            
            // Get all logs for export
            require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-logger.php';
            $logger = new WP_Total_Monitor_Logger();
            $logs_data = $logger->get_logs(9999, 1, $filters); // Get a large number to essentially get all logs
            $logs = $logs_data['logs'];
            
            // Start output buffering
            ob_start();
            
            // Create a file pointer
            $output = fopen('php://output', 'w');
            
            // Set UTF-8 BOM for Excel compatibility
            fputs($output, "\xEF\xBB\xBF");
            
            // Add CSV headers
            fputcsv($output, array(
                __('Date & Time', 'wp-total-monitor'),
                __('User ID', 'wp-total-monitor'),
                __('Username', 'wp-total-monitor'),
                __('Role', 'wp-total-monitor'),
                __('IP Address', 'wp-total-monitor'),
                __('Action Type', 'wp-total-monitor'),
                __('Action Description', 'wp-total-monitor'),
                __('Object Type', 'wp-total-monitor'),
                __('Object ID', 'wp-total-monitor')
            ));
            
            // Add each log as a CSV line
            foreach ($logs as $log) {
                fputcsv($output, array(
                    $log['created_at'],
                    $log['user_id'],
                    $log['username'],
                    $log['user_role'],
                    $log['ip_address'],
                    $log['action_type'],
                    $log['action_description'],
                    $log['object_type'],
                    $log['object_id']
                ));
            }
            
            // Get the content from the buffer
            $csv_content = ob_get_clean();
            
            // Generate filename with current date
            $filename = 'wp-total-monitor-logs-' . date('Y-m-d') . '.csv';
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Output the CSV content
            echo $csv_content;
            exit;
        }
    }
    
    /**
     * Get action type options for filters
     *
     * @since    1.0.0
     * @return   array     Array of action types.
     */
    public function get_action_type_options() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        
        // Get unique action types from the database
        $query = "SELECT DISTINCT action_type FROM {$table_name} ORDER BY action_type ASC";
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $options = array();
        
        if ($results) {
            foreach ($results as $result) {
                $action_type = $result['action_type'];
                $options[$action_type] = ucwords(str_replace('_', ' ', $action_type));
            }
        }
        
        return $options;
    }
    
    /**
     * Get user options for filters
     *
     * @since    1.0.0
     * @return   array     Array of users.
     */
    public function get_user_options() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        
        // Get unique users from the database
        $query = "SELECT DISTINCT user_id, username FROM {$table_name} ORDER BY username ASC";
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $options = array();
        
        if ($results) {
            foreach ($results as $result) {
                $options[$result['user_id']] = $result['username'];
            }
        }
        
        return $options;
    }
}
