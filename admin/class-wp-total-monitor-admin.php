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
                'confirmDelete' => __('Are you sure you want to delete these logs? This action cannot be undone.', 'total-activity-monitor')
            )
        );
        
        // Dashboard scripts
        if (strpos($hook, 'wp-total-monitor-dashboard') !== false) {
            // Enqueue Chart.js from local file instead of CDN
            wp_enqueue_script(
                'chartjs',
                WP_TOTAL_MONITOR_URL . 'admin/js/vendor/chart.min.js',
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
            __('WP Total Monitor', 'total-activity-monitor'),
            __('Total Monitor', 'total-activity-monitor'),
            'manage_options',
            'wp-total-monitor-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-visibility',
            90
        );
        
        // Dashboard subpage
        add_submenu_page(
            'wp-total-monitor-dashboard',
            __('Dashboard', 'total-activity-monitor'),
            __('Dashboard', 'total-activity-monitor'),
            'manage_options',
            'wp-total-monitor-dashboard',
            array($this, 'display_dashboard_page')
        );
        
        // Logs subpage
        add_submenu_page(
            'wp-total-monitor-dashboard',
            __('Activity Logs', 'total-activity-monitor'),
            __('Activity Logs', 'total-activity-monitor'),
            'manage_options',
            'wp-total-monitor-logs',
            array($this, 'display_logs_page')
        );
        
        // Settings subpage
        add_submenu_page(
            'wp-total-monitor-dashboard',
            __('Settings', 'total-activity-monitor'),
            __('Settings', 'total-activity-monitor'),
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
            __('General Settings', 'total-activity-monitor'),
            array($this, 'render_settings_section'),
            'wp_total_monitor_settings'
        );
        
        // Add language settings section
        add_settings_section(
            'wp_total_monitor_language_section',
            __('Language Settings', 'total-activity-monitor'),
            array($this, 'render_language_section'),
            'wp_total_monitor_settings'
        );
        
        // Add settings field
        add_settings_field(
            'wp_total_monitor_retention',
            __('Log Retention Period', 'total-activity-monitor'),
            array($this, 'render_retention_field'),
            'wp_total_monitor_settings',
            'wp_total_monitor_general_section'
        );
        
        // Add language settings field
        add_settings_field(
            'wp_total_monitor_admin_language',
            __('Admin Interface Language', 'total-activity-monitor'),
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
                __('Invalid log retention period', 'total-activity-monitor'),
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
                __('Invalid language selection', 'total-activity-monitor'),
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
        echo '<p>' . esc_html__('Configure how long the activity logs should be retained before automatic deletion.', 'total-activity-monitor') . '</p>';
    }
    
    /**
     * Render the language settings section
     *
     * @since    1.1.0
     */
    public function render_language_section() {
        echo '<p>' . esc_html__('Choose in which language you want to display the admin interface of WP Total Monitor.', 'total-activity-monitor') . '</p>';
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
            <option value="5" <?php selected($retention, '5'); ?>><?php esc_html_e('5 days', 'total-activity-monitor'); ?></option>
            <option value="10" <?php selected($retention, '10'); ?>><?php esc_html_e('10 days', 'total-activity-monitor'); ?></option>
            <option value="30" <?php selected($retention, '30'); ?>><?php esc_html_e('30 days', 'total-activity-monitor'); ?></option>
            <option value="forever" <?php selected($retention, 'forever'); ?>><?php esc_html_e('Forever (never delete)', 'total-activity-monitor'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Select how long to keep activity logs before they are automatically deleted.', 'total-activity-monitor'); ?></p>
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
            'en_US' => __('English (United States)', 'total-activity-monitor'),
            'es_ES' => __('Spanish (Spain)', 'total-activity-monitor'),
            'fr_FR' => __('French (France)', 'total-activity-monitor'),
            'de_DE' => __('German (Germany)', 'total-activity-monitor'),
            'it_IT' => __('Italian (Italy)', 'total-activity-monitor'),
            'pt_BR' => __('Portuguese (Brazil)', 'total-activity-monitor')
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
        echo '<option value="site-default"' . selected($language, 'site-default', false) . '>' . esc_html__('Use Site Default', 'total-activity-monitor') . '</option>';
        
        foreach ($available_languages as $code => $name) {
            echo '<option value="' . esc_attr($code) . '"' . selected($language, $code, false) . '>' . esc_html($name) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select which language to use for the plugin admin interface.', 'total-activity-monitor') . '</p>';
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
                wp_die(esc_html__('Security check failed. Please try again.', 'total-activity-monitor'));
            }
            
            // Permission check
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'total-activity-monitor'));
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
                __('Date & Time', 'total-activity-monitor'),
                __('User ID', 'total-activity-monitor'),
                __('Username', 'total-activity-monitor'),
                __('Role', 'total-activity-monitor'),
                __('IP Address', 'total-activity-monitor'),
                __('Action Type', 'total-activity-monitor'),
                __('Action Description', 'total-activity-monitor'),
                __('Object Type', 'total-activity-monitor'),
                __('Object ID', 'total-activity-monitor')
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
     * @updated  2.3.1 - Added caching
     * @return   array     Array of action types.
     */
    public function get_action_type_options() {
        // Check cache first
        $cache_key = 'wp_total_monitor_action_types';
        $options = wp_cache_get($cache_key);
        
        if (false === $options) {
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
            
            // Cache the results for 1 hour
            wp_cache_set($cache_key, $options, '', HOUR_IN_SECONDS);
        }
        
        return $options;
    }
    
    /**
     * Get user options for filters
     *
     * @since    1.0.0
     * @updated  2.3.1 - Added caching
     * @return   array     Array of users.
     */
    public function get_user_options() {
        // Check cache first
        $cache_key = 'wp_total_monitor_user_options';
        $options = wp_cache_get($cache_key);
        
        if (false === $options) {
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
            
            // Cache the results for 1 hour
            wp_cache_set($cache_key, $options, '', HOUR_IN_SECONDS);
        }
        
        return $options;
    }
}
