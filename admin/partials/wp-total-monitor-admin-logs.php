<?php
/**
 * Admin page for viewing activity logs
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get the logger instance
require_once WP_TOTAL_MONITOR_PATH . 'includes/class-wp-total-monitor-logger.php';
$logger = new WP_Total_Monitor_Logger();

// Get admin instance
$admin = new WP_Total_Monitor_Admin();

// Process filters
$filters = array();

// Verify nonce for GET filters if present (not required for initial page load)
if (isset($_GET['_wpnonce']) && (isset($_GET['user_id']) || isset($_GET['action_type']) || isset($_GET['date_from']) || isset($_GET['date_to']))) {
    // Only verify if actually filtering
    if (!wp_verify_nonce($_GET['_wpnonce'], 'wp_total_monitor_filter_logs')) {
        wp_die(__('Security check failed. Please try again.', 'wp-total-monitor'));
    }
}

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

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Get logs
$logs_data = $logger->get_logs($per_page, $current_page, $filters);
$logs = $logs_data['logs'];
?>

<div class="wrap wp-total-monitor-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wp-total-monitor-filters">
        <div class="wp-total-monitor-export-button">
            <a href="<?php 
                $export_args = array(
                    'page' => 'wp-total-monitor-logs',
                    'action' => 'export_csv',
                    '_wpnonce' => wp_create_nonce('wp_total_monitor_export_csv')
                );
                
                // Add filter parameters if they exist
                if (isset($_GET['user_id'])) {
                    $export_args['user_id'] = intval($_GET['user_id']);
                }
                if (isset($_GET['action_type'])) {
                    $export_args['action_type'] = sanitize_text_field($_GET['action_type']);
                }
                if (isset($_GET['date_from'])) {
                    $export_args['date_from'] = sanitize_text_field($_GET['date_from']);
                }
                if (isset($_GET['date_to'])) {
                    $export_args['date_to'] = sanitize_text_field($_GET['date_to']);
                }
                
                echo esc_url(add_query_arg($export_args, admin_url('admin.php')));
            ?>" class="button button-primary">
                <span class="dashicons dashicons-media-spreadsheet" style="margin-top: 3px;"></span> <?php _e('Export to CSV', 'wp-total-monitor'); ?>
            </a>
        </div>
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="wp-total-monitor-logs">
            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wp_total_monitor_filter_logs'); ?>">
            
            <div class="filter-items">
                <div class="filter-item">
                    <label for="user_id"><?php _e('User:', 'wp-total-monitor'); ?></label>
                    <select name="user_id" id="user_id">
                        <option value=""><?php _e('All Users', 'wp-total-monitor'); ?></option>
                        <?php
                        $user_options = $admin->get_user_options();
                        foreach ($user_options as $user_id => $username) {
                            $selected = isset($filters['user_id']) && $filters['user_id'] == $user_id ? 'selected' : '';
                            echo '<option value="' . esc_attr($user_id) . '" ' . $selected . '>' . esc_html($username) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="action_type"><?php _e('Action:', 'wp-total-monitor'); ?></label>
                    <select name="action_type" id="action_type">
                        <option value=""><?php _e('All Actions', 'wp-total-monitor'); ?></option>
                        <?php
                        $action_options = $admin->get_action_type_options();
                        foreach ($action_options as $action_type => $label) {
                            $selected = isset($filters['action_type']) && $filters['action_type'] == $action_type ? 'selected' : '';
                            echo '<option value="' . esc_attr($action_type) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="date_from"><?php _e('From:', 'wp-total-monitor'); ?></label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo isset($filters['date_from']) ? esc_attr($filters['date_from']) : ''; ?>">
                </div>
                
                <div class="filter-item">
                    <label for="date_to"><?php _e('To:', 'wp-total-monitor'); ?></label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo isset($filters['date_to']) ? esc_attr($filters['date_to']) : ''; ?>">
                </div>
                
                <div class="filter-item">
                    <button type="submit" class="button"><?php _e('Filter', 'wp-total-monitor'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-total-monitor-logs')); ?>" class="button"><?php _e('Reset', 'wp-total-monitor'); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="wp-total-monitor-logs-table">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Date & Time', 'wp-total-monitor'); ?></th>
                    <th><?php _e('User', 'wp-total-monitor'); ?></th>
                    <th><?php _e('Role', 'wp-total-monitor'); ?></th>
                    <th><?php _e('IP Address', 'wp-total-monitor'); ?></th>
                    <th><?php _e('Action', 'wp-total-monitor'); ?></th>
                    <th><?php _e('Description', 'wp-total-monitor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No logs found.', 'wp-total-monitor'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['created_at']))); ?></td>
                            <td><?php echo esc_html($log['username']); ?></td>
                            <td><?php echo esc_html(ucfirst($log['user_role'])); ?></td>
                            <td><?php echo esc_html($log['ip_address']); ?></td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $log['action_type']))); ?></td>
                            <td><?php echo esc_html($log['action_description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($logs_data['total_pages'] > 1): ?>
        <div class="wp-total-monitor-pagination">
            <div class="tablenav">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $logs_data['total_items'], 'wp-total-monitor'), number_format_i18n($logs_data['total_items'])); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        // First page
                        if ($current_page > 1) {
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('First page', 'wp-total-monitor') . '</span><span aria-hidden="true">&laquo;</span></a>';
                        } else {
                            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                        }
                        
                        // Previous page
                        if ($current_page > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '"><span class="screen-reader-text">' . __('Previous page', 'wp-total-monitor') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        } else {
                            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                        }
                        
                        // Current page
                        echo '<span class="paging-input">' . $current_page . ' of <span class="total-pages">' . $logs_data['total_pages'] . '</span></span>';
                        
                        // Next page
                        if ($current_page < $logs_data['total_pages']) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '"><span class="screen-reader-text">' . __('Next page', 'wp-total-monitor') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        } else {
                            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                        }
                        
                        // Last page
                        if ($current_page < $logs_data['total_pages']) {
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $logs_data['total_pages'])) . '"><span class="screen-reader-text">' . __('Last page', 'wp-total-monitor') . '</span><span aria-hidden="true">&raquo;</span></a>';
                        } else {
                            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
