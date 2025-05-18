<?php
/**
 * Class responsible for logging user activities
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

class WP_Total_Monitor_Logger {

    /**
     * Log a user activity
     *
     * @since    1.0.0
     * @param    int       $user_id              The user ID.
     * @param    string    $action_type          The type of action performed.
     * @param    string    $action_description   Description of the action.
     * @param    string    $object_type          Type of object affected (e.g., post, user, option).
     * @param    int       $object_id            ID of the object affected.
     * @return   int|false The log ID on success, false on failure.
     */
    public function log_activity($user_id, $action_type, $action_description, $object_type = '', $object_id = null) {
        global $wpdb;
        
        // If no user is logged in (e.g. cron job), use system user
        if (empty($user_id) || $user_id === 0) {
            $user_id = 0;
            $username = 'System';
            $user_role = 'system';
        } else {
            $user = get_userdata($user_id);
            if (!$user) {
                return false;
            }
            $username = $user->user_login;
            $user_role = $this->get_user_role($user);
        }
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        
        $ip_address = $this->get_client_ip();
        
        $data = array(
            'user_id' => $user_id,
            'username' => $username,
            'user_role' => $user_role,
            'action_type' => $action_type,
            'action_description' => $action_description,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'ip_address' => $ip_address,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get the client IP address
     *
     * @since    1.0.0
     * @return   string    The client IP address.
     */
    private function get_client_ip() {
        $ip_address = '';
        
        // Check for proxy forwarded IP
        $proxy_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        );
        
        foreach ($proxy_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip_list = explode(',', sanitize_text_field($_SERVER[$header]));
                $ip_address = trim($ip_list[0]);
                break;
            }
        }
        
        // If proxy headers not found, use direct remote address
        if (empty($ip_address)) {
            $ip_address = !empty($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        }
        
        return $ip_address;
    }
    
    /**
     * Get the user's primary role
     *
     * @since    1.0.0
     * @param    WP_User   $user   The user object.
     * @return   string    The user's primary role.
     */
    private function get_user_role($user) {
        if (empty($user->roles) || !is_array($user->roles)) {
            return 'unknown';
        }
        
        // Return the first role (primary role)
        return $user->roles[0];
    }
    
    /**
     * Log user login event
     *
     * @since    1.0.0
     * @param    string    $user_login  The username.
     * @param    WP_User   $user        The user object.
     */
    public function log_user_login($user_login, $user) {
        $this->log_activity(
            $user->ID,
            'login',
            sprintf('User %s logged in', $user_login),
            'user',
            $user->ID
        );
    }
    
    /**
     * Log user logout event
     *
     * @since    1.0.0
     */
    public function log_user_logout() {
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            return;
        }
        
        $user = get_userdata($user_id);
        
        $this->log_activity(
            $user_id,
            'logout',
            sprintf('User %s logged out', $user->user_login),
            'user',
            $user_id
        );
    }
    
    /**
     * Log post status changes
     *
     * @since    1.0.0
     * @param    string    $new_status   The new post status.
     * @param    string    $old_status   The old post status.
     * @param    WP_Post   $post         The post object.
     */
    public function log_post_status_change($new_status, $old_status, $post) {
        // Don't log auto-drafts and post revisions
        if ($post->post_type === 'revision' || $post->post_type === 'auto-draft') {
            return;
        }
        
        // Don't log if old and new status are the same
        if ($new_status === $old_status) {
            return;
        }
        
        $user_id = get_current_user_id();
        $post_title = $post->post_title;
        $post_type = ucfirst($post->post_type);
        
        if ($old_status === 'new' && $new_status === 'auto-draft') {
            // Don't log creation of auto-drafts
            return;
        } elseif ($old_status === 'auto-draft' && $new_status === 'draft') {
            $action_type = 'post_created';
            $action_description = sprintf('%s "%s" created', $post_type, $post_title);
        } elseif ($new_status === 'publish' && $old_status !== 'publish') {
            $action_type = 'post_published';
            $action_description = sprintf('%s "%s" published', $post_type, $post_title);
        } elseif ($old_status === 'publish' && $new_status !== 'publish') {
            $action_type = 'post_unpublished';
            $action_description = sprintf('%s "%s" unpublished', $post_type, $post_title);
        } elseif ($new_status === 'trash') {
            $action_type = 'post_trashed';
            $action_description = sprintf('%s "%s" moved to trash', $post_type, $post_title);
        } else {
            $action_type = 'post_updated';
            $action_description = sprintf('%s "%s" updated (status changed from %s to %s)', $post_type, $post_title, $old_status, $new_status);
        }
        
        $this->log_activity(
            $user_id,
            $action_type,
            $action_description,
            $post->post_type,
            $post->ID
        );
    }
    
    /**
     * Log post deletion
     *
     * @since    1.0.0
     * @param    int    $post_id   The post ID.
     */
    public function log_post_deletion($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type === 'revision') {
            return;
        }
        
        $user_id = get_current_user_id();
        $post_title = $post->post_title;
        $post_type = ucfirst($post->post_type);
        
        $this->log_activity(
            $user_id,
            'post_deleted',
            sprintf('%s "%s" permanently deleted', $post_type, $post_title),
            $post->post_type,
            $post_id
        );
    }
    
    /**
     * Log taxonomy creation
     *
     * @since    1.1.0
     * @param    int      $term_id    Term ID.
     * @param    int      $tt_id      Term taxonomy ID.
     * @param    string   $taxonomy   Taxonomy slug.
     */
    public function log_taxonomy_created($term_id, $tt_id, $taxonomy) {
        $term = get_term($term_id, $taxonomy);
        
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        $taxonomy_object = get_taxonomy($taxonomy);
        $taxonomy_label = $taxonomy_object ? $taxonomy_object->labels->singular_name : $taxonomy;
        
        $this->log_activity(
            get_current_user_id(),
            'taxonomy_created',
            sprintf('%s "%s" created', $taxonomy_label, $term->name),
            'taxonomy',
            $term_id
        );
    }
    
    /**
     * Log taxonomy update
     *
     * @since    1.1.0
     * @param    int      $term_id    Term ID.
     * @param    int      $tt_id      Term taxonomy ID.
     * @param    string   $taxonomy   Taxonomy slug.
     */
    public function log_taxonomy_updated($term_id, $tt_id, $taxonomy) {
        $term = get_term($term_id, $taxonomy);
        
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        $taxonomy_object = get_taxonomy($taxonomy);
        $taxonomy_label = $taxonomy_object ? $taxonomy_object->labels->singular_name : $taxonomy;
        
        $this->log_activity(
            get_current_user_id(),
            'taxonomy_updated',
            sprintf('%s "%s" updated', $taxonomy_label, $term->name),
            'taxonomy',
            $term_id
        );
    }
    
    /**
     * Log taxonomy deletion
     *
     * @since    1.1.0
     * @param    int      $term_id    Term ID.
     * @param    int      $tt_id      Term taxonomy ID.
     * @param    string   $taxonomy   Taxonomy slug.
     * @param    object   $deleted_term The deleted term object.
     * @param    array    $object_ids The IDs of the objects that were associated with the term.
     */
    public function log_taxonomy_deleted($term_id, $tt_id, $taxonomy, $deleted_term, $object_ids) {
        if (!$deleted_term || is_wp_error($deleted_term)) {
            return;
        }
        
        $taxonomy_object = get_taxonomy($taxonomy);
        $taxonomy_label = $taxonomy_object ? $taxonomy_object->labels->singular_name : $taxonomy;
        
        $this->log_activity(
            get_current_user_id(),
            'taxonomy_deleted',
            sprintf('%s "%s" deleted', $taxonomy_label, $deleted_term->name),
            'taxonomy',
            $term_id
        );
    }
    
    /**
     * Log user registration
     *
     * @since    1.0.0
     * @param    int    $user_id   The user ID.
     */
    public function log_user_registered($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $this->log_activity(
            get_current_user_id(), // Admin who created the user
            'user_registered',
            sprintf('New user registered: %s', $user->user_login),
            'user',
            $user_id
        );
    }
    
    /**
     * Log user deletion
     *
     * @since    1.0.0
     * @param    int    $user_id   The user ID.
     */
    public function log_user_deleted($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $this->log_activity(
            get_current_user_id(),
            'user_deleted',
            sprintf('User deleted: %s', $user->user_login),
            'user',
            $user_id
        );
    }
    
    /**
     * Log user profile update
     *
     * @since    1.0.0
     * @param    int    $user_id   The user ID.
     */
    public function log_user_updated($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $current_user_id = get_current_user_id();
        
        // If user is updating their own profile
        if ($current_user_id === $user_id) {
            $this->log_activity(
                $current_user_id,
                'profile_updated',
                sprintf('User updated their profile: %s', $user->user_login),
                'user',
                $user_id
            );
        } else {
            $this->log_activity(
                $current_user_id,
                'user_updated',
                sprintf('User profile updated by admin: %s', $user->user_login),
                'user',
                $user_id
            );
        }
    }
    
    /**
     * Log user role change
     *
     * @since    1.0.0
     * @param    int       $user_id     The user ID.
     * @param    string    $new_role    The new role.
     * @param    array     $old_roles   The old roles.
     */
    public function log_role_change($user_id, $new_role, $old_roles) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $old_role = !empty($old_roles) && is_array($old_roles) ? $old_roles[0] : 'none';
        
        $this->log_activity(
            get_current_user_id(),
            'role_changed',
            sprintf('User %s role changed from %s to %s', $user->user_login, $old_role, $new_role),
            'user',
            $user_id
        );
    }
    
    /**
     * Log plugin activation
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin path.
     */
    public function log_plugin_activated($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
        
        $this->log_activity(
            get_current_user_id(),
            'plugin_activated',
            sprintf('Plugin activated: %s', $plugin_name),
            'plugin',
            0
        );
    }
    
    /**
     * Log plugin deactivation
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin path.
     */
    public function log_plugin_deactivated($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
        
        $this->log_activity(
            get_current_user_id(),
            'plugin_deactivated',
            sprintf('Plugin deactivated: %s', $plugin_name),
            'plugin',
            0
        );
    }
    
    /**
     * Log plugin updates
     *
     * @since    1.1.0
     * @param    object   $upgrader   WP_Upgrader instance.
     * @param    array    $options    Array of plugin update options.
     */
    public function log_plugin_update($upgrader, $options) {
        // Check if this is a plugin update
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }
        
        // For bulk updates
        if (!empty($options['plugins']) && is_array($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
                $version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : '';
                
                $this->log_activity(
                    get_current_user_id(),
                    'plugin_updated',
                    sprintf('Plugin updated: %s to version %s', $plugin_name, $version),
                    'plugin',
                    0
                );
            }
        }
        // For single plugin update
        elseif (!empty($upgrader->skin->plugin)) {
            $plugin = $upgrader->skin->plugin;
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
            $version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : '';
            
            $this->log_activity(
                get_current_user_id(),
                'plugin_updated',
                sprintf('Plugin updated: %s to version %s', $plugin_name, $version),
                'plugin',
                0
            );
        }
    }
    
    /**
     * Log theme switch
     *
     * @since    1.0.0
     * @param    string    $new_name    New theme name.
     * @param    WP_Theme  $new_theme   New theme object.
     * @param    WP_Theme  $old_theme   Old theme object.
     */
    public function log_theme_switched($new_name, $new_theme, $old_theme) {
        $old_name = $old_theme->get('Name');
        
        $this->log_activity(
            get_current_user_id(),
            'theme_switched',
            sprintf('Theme switched from %s to %s', $old_name, $new_name),
            'theme',
            0
        );
    }
    
    /**
     * Log theme updates
     *
     * @since    1.1.0
     * @param    object   $upgrader   WP_Upgrader instance.
     * @param    array    $options    Array of theme update options.
     */
    public function log_theme_update($upgrader, $options) {
        // Check if this is a theme update
        if ($options['action'] !== 'update' || $options['type'] !== 'theme') {
            return;
        }
        
        // For bulk updates
        if (!empty($options['themes']) && is_array($options['themes'])) {
            foreach ($options['themes'] as $theme_slug) {
                $theme = wp_get_theme($theme_slug);
                $theme_name = $theme->exists() ? $theme->get('Name') : $theme_slug;
                $version = $theme->exists() ? $theme->get('Version') : '';
                
                $this->log_activity(
                    get_current_user_id(),
                    'theme_updated',
                    sprintf('Theme updated: %s to version %s', $theme_name, $version),
                    'theme',
                    0
                );
            }
        }
        // For single theme update
        elseif (!empty($upgrader->skin->theme)) {
            $theme_slug = $upgrader->skin->theme;
            $theme = wp_get_theme($theme_slug);
            $theme_name = $theme->exists() ? $theme->get('Name') : $theme_slug;
            $version = $theme->exists() ? $theme->get('Version') : '';
            
            $this->log_activity(
                get_current_user_id(),
                'theme_updated',
                sprintf('Theme updated: %s to version %s', $theme_name, $version),
                'theme',
                0
            );
        }
    }
    
    /**
     * Log option update
     *
     * @since    1.0.0
     * @param    string    $option_name     The option name.
     * @param    mixed     $old_value       The old option value.
     * @param    mixed     $new_value       The new option value.
     */
    public function log_option_updated($option_name, $old_value, $new_value) {
        // Skip certain options to avoid excessive logging
        $skip_options = array(
            '_transient_',
            '_site_transient_',
            'cron',
            'active_plugins',
            'wp_total_monitor_logs',
            'rewrite_rules',
            'theme_mods_'
        );
        
        foreach ($skip_options as $skip) {
            if (strpos($option_name, $skip) === 0) {
                return;
            }
        }
        
        // Don't log if values are the same
        if ($old_value === $new_value) {
            return;
        }
        
        $this->log_activity(
            get_current_user_id(),
            'option_updated',
            sprintf('Option "%s" updated', $option_name),
            'option',
            0
        );
    }
    
    /**
     * Get logs from the database
     *
     * @since    1.0.0
     * @param    int       $per_page    Number of logs per page.
     * @param    int       $page        Current page number.
     * @param    array     $filters     Array of filters.
     * @return   array     Array containing the logs and pagination info.
     */
    public function get_logs($per_page = 20, $page = 1, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        
        // Build the WHERE clause based on filters
        $where = "";
        $where_args = array();
        
        if (!empty($filters['user_id'])) {
            $where .= " AND user_id = %d";
            $where_args[] = $filters['user_id'];
        }
        
        if (!empty($filters['action_type'])) {
            $where .= " AND action_type = %s";
            $where_args[] = $filters['action_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where .= " AND created_at >= %s";
            $where_args[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where .= " AND created_at <= %s";
            $where_args[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Count total records matching the filters
        $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1" . $where;
        
        if (!empty($where_args)) {
            $count_query = $wpdb->prepare($count_query, $where_args);
        }
        
        $total_items = $wpdb->get_var($count_query);
        
        // Calculate pagination
        $offset = ($page - 1) * $per_page;
        $total_pages = ceil($total_items / $per_page);
        
        // Build the main query
        $query = "SELECT * FROM {$table_name} WHERE 1=1" . $where . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $final_args = array_merge($where_args, array($per_page, $offset));
        
        if (!empty($final_args)) {
            $query = $wpdb->prepare($query, $final_args);
        }
        
        $logs = $wpdb->get_results($query, ARRAY_A);
        
        return array(
            'logs' => $logs,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page
        );
    }
    
    /**
     * Delete logs based on criteria
     *
     * @since    1.0.0
     * @param    array     $filters    Array of filters.
     * @return   int       Number of logs deleted.
     */
    public function delete_logs($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_total_monitor_logs';
        
        // Build the WHERE clause based on filters
        $where = "";
        $where_args = array();
        
        if (!empty($filters['user_id'])) {
            $where .= " AND user_id = %d";
            $where_args[] = $filters['user_id'];
        }
        
        if (!empty($filters['action_type'])) {
            $where .= " AND action_type = %s";
            $where_args[] = $filters['action_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where .= " AND created_at >= %s";
            $where_args[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where .= " AND created_at <= %s";
            $where_args[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $query = "DELETE FROM {$table_name} WHERE 1=1" . $where;
        
        if (!empty($where_args)) {
            $query = $wpdb->prepare($query, $where_args);
        }
        
        return $wpdb->query($query);
    }
}
