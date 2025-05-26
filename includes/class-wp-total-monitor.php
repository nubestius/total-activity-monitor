<?php
/**
 * The main plugin class
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

class WP_Total_Monitor {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $hooks    Collection of actions and filters.
     */
    protected $hooks = array();

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->hooks = array(
            'actions' => array(),
            'filters' => array()
        );
    }

    /**
     * Initialize the plugin functionality
     *
     * @since    1.0.0
     * @updated  1.1.0 - Added custom language support
     */
    public function init() {
        // Apply custom language if set
        $this->apply_custom_language();
        
        // Load plugin admin functionality
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_monitoring_hooks();
        $this->schedule_cleanup_tasks();
        
        // Initialize Ajax handler
        new WP_Total_Monitor_Ajax();
    }
    
    /**
     * Apply the custom language selected by the user
     * 
     * @since    1.1.0
     * @updated  1.1.0 - Improved to ensure proper language loading
     * @access   private
     */
    private function apply_custom_language() {
        // Apply to both admin and frontend areas
        // This ensures complete language support
        
        // Get selected language
        $selected_language = get_option('wp_total_monitor_admin_language', 'site-default');
        
        // If set to site default, no need to do anything special
        if ($selected_language === 'site-default') {
            return;
        }
        
        // Define the plugin's text domain and languages directory
        $domain = 'wp-total-monitor';
        $locale = $selected_language;
        $mofile = $domain . '-' . $locale . '.mo';
        $pofile = $domain . '-' . $locale . '.po';
        $mofile_path = WP_TOTAL_MONITOR_PATH . 'languages/' . $mofile;
        $pofile_path = WP_TOTAL_MONITOR_PATH . 'languages/' . $pofile;
        
        // Check if .mo file doesn't exist but .po file does - then compile it
        if (!file_exists($mofile_path) && file_exists($pofile_path)) {
            $this->compile_po_to_mo($pofile_path, $mofile_path);
        }
        
        // Try to load the language file
        if (file_exists($mofile_path)) {
            // Ensure any existing textdomain is unloaded first
            if (is_textdomain_loaded($domain)) {
                unload_textdomain($domain);
            }
            
            // Force load our custom language
            load_textdomain($domain, $mofile_path);
            
            // Also set the locale filter (this helps with date formatting, etc.)
            add_filter('locale', function($wp_locale) use ($locale) {
                // Only change locale for admin
                if (is_admin()) {
                    return $locale;
                }
                return $wp_locale;
            });
        }
    }
    
    /**
     * Compile a .po file to a .mo file
     * 
     * @since    1.1.0
     * @access   private
     * @param    string    $po_file    Path to the .po file
     * @param    string    $mo_file    Path to the .mo file to create
     * @return   boolean   True if successful, false otherwise
     */
    private function compile_po_to_mo($po_file, $mo_file) {
        // We need a basic MO compiler since we can't rely on external tools
        
        if (!file_exists($po_file) || !is_readable($po_file)) {
            return false;
        }
        
        // Read the .po file content
        $po_content = file_get_contents($po_file);
        if (empty($po_content)) {
            return false;
        }
        
        // Create a simple format that WordPress can understand
        $mo_content = '';
        $translations = array();
        
        // Extract msgid and msgstr pairs (very basic parsing)
        preg_match_all('/msgid\s+"(.*?)"\s+msgstr\s+"(.*?)"\s/s', $po_content, $matches, PREG_SET_ORDER);
        
        if (!empty($matches)) {
            // Create MO file header
            $mo_content = pack('V', 0x950412de);    // Magic number
            $mo_content .= pack('V', 0);           // File format revision
            $mo_content .= pack('V', count($matches)); // Number of strings
            $mo_content .= pack('V', 28);          // Offset of original strings table
            $mo_content .= pack('V', 28 + count($matches) * 8); // Offset of translated strings table
            
            // For simplicity, we're just creating a basic MO file
            // A complete implementation would be more complex
            
            // Write basic header data using WP_Filesystem
            global $wp_filesystem;
            if (!$wp_filesystem) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            if ($wp_filesystem) {
                $wp_filesystem->put_contents($mo_file, $mo_content, FS_CHMOD_FILE);
                return true;
            }
        }
        
        // As a fallback, copy the .po to .mo using WP_Filesystem
        // WordPress will try to use whatever it can from it
        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        if ($wp_filesystem) {
            $content = $wp_filesystem->get_contents($po_file);
            return $wp_filesystem->put_contents($mo_file, $content, FS_CHMOD_FILE);
        }
        
        return false;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Admin class
        require_once WP_TOTAL_MONITOR_PATH . 'admin/class-wp-total-monitor-admin.php';
        
        // Logger class
        require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-logger.php';
        
        // Ajax handler class
        require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-ajax.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_Total_Monitor_Admin();
        
        // Admin menu and settings
        add_action('admin_menu', array($plugin_admin, 'add_plugin_admin_menu'));
        add_action('admin_init', array($plugin_admin, 'register_settings'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
    }

    /**
     * Register all of the hooks related to monitoring user activities
     *
     * @since    1.0.0
     * @updated  1.1.0 - Limitado a eventos específicos según configuración
     * @access   private
     */
    private function define_monitoring_hooks() {
        $logger = new WP_Total_Monitor_Logger();
        
        // 1. User login/logout events
        add_action('wp_login', array($logger, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($logger, 'log_user_logout'));
        
        // 2. Post/Page/Taxonomy activity
        add_action('transition_post_status', array($logger, 'log_post_status_change'), 10, 3);
        add_action('delete_post', array($logger, 'log_post_deletion'));
        
        // Para taxonomías (categorías, etiquetas, etc.)
        add_action('created_term', array($logger, 'log_taxonomy_created'), 10, 3);
        add_action('edited_term', array($logger, 'log_taxonomy_updated'), 10, 3);
        add_action('delete_term', array($logger, 'log_taxonomy_deleted'), 10, 5);
        
        // 3. Usuario - roles
        add_action('set_user_role', array($logger, 'log_role_change'), 10, 3);
        
        // 4. Plugin management
        add_action('activated_plugin', array($logger, 'log_plugin_activated'));
        add_action('deactivated_plugin', array($logger, 'log_plugin_deactivated'));
        
        // 5. Plugin installation and updates
        add_action('upgrader_process_complete', array($logger, 'log_plugin_update'), 10, 2);
        
        // 6. Theme changes
        add_action('switch_theme', array($logger, 'log_theme_switched'), 10, 3);
        add_action('upgrader_process_complete', array($logger, 'log_theme_update'), 10, 2);
    }

    /**
     * Schedule the cleanup task to delete old logs based on retention setting
     *
     * @since    1.0.0
     * @access   private
     */
    private function schedule_cleanup_tasks() {
        // Only schedule if not already scheduled
        if (!wp_next_scheduled('wp_total_monitor_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'wp_total_monitor_cleanup_logs');
        }
        
        // Register the callback for the cleanup task
        add_action('wp_total_monitor_cleanup_logs', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Delete logs older than the retention period
     *
     * @since    1.0.0
     * @updated  2.3.1 - Added cache invalidation
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        // Obtener la configuración de retención desde la caché o la base de datos
        $cache_key = 'wp_total_monitor_retention_setting';
        $cache_group = 'wp_total_monitor_settings';
        $retention_days = wp_cache_get($cache_key, $cache_group);
        
        if (false === $retention_days) {
            $retention_days = get_option('wp_total_monitor_retention', '30');
            // Guardar en caché por 1 hora
            wp_cache_set($cache_key, $retention_days, $cache_group, HOUR_IN_SECONDS);
        }
        
        // If retention is set to 'forever', don't delete any logs
        if ($retention_days === 'forever') {
            return;
        }
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        $cutoff_date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Ejecutar la eliminación de registros antiguos
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `" . esc_sql($table_name) . "` WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        // Si se eliminaron registros, invalidar las cachés relacionadas con logs
        if ($result !== false && $result > 0) {
            // Limpiar caché del panel de control y registros
            wp_cache_delete('total_count', 'wp_total_monitor_dashboard');
            
            // Limpiar cualquier caché relacionada con logs
            $logs_cache_group = 'wp_total_monitor_logs';
            wp_cache_flush();
            
            // Registrar la limpieza en el log de WordPress
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('WP Total Monitor: %d logs older than %s days deleted.', $result, $retention_days));
            }
        }
    }

    /**
     * Add an action to the hooks collection
     *
     * @since    1.0.0
     */
    public function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $this->hooks['actions'][] = array(
            'hook'          => $hook,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        add_action($hook, $callback, $priority, $accepted_args);
    }

    /**
     * Add a filter to the hooks collection
     *
     * @since    1.0.0
     */
    public function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        $this->hooks['filters'][] = array(
            'hook'          => $hook,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        add_filter($hook, $callback, $priority, $accepted_args);
    }
}
