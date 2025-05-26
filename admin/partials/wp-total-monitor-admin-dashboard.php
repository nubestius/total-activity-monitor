<?php
/**
 * Admin dashboard for WP Total Monitor
 *
 * @since      1.1.0
 * @package    WP_Total_Monitor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics data for the dashboard
global $wpdb;
$table_name = $wpdb->prefix . 'wp_total_monitor_logs';

// Today's date
$today = gmdate('Y-m-d');
$yesterday = gmdate('Y-m-d', strtotime('-1 day'));
$last_week = gmdate('Y-m-d', strtotime('-7 days'));
$last_month = gmdate('Y-m-d', strtotime('-30 days'));

// Cache group and expiration
$cache_group = 'wp_total_monitor_dashboard';
$cache_expiration = 30 * MINUTE_IN_SECONDS; // 30 minutes

// Get total count with cache
$cache_key = 'total_count';
$total_count = wp_cache_get($cache_key, $cache_group);
if (false === $total_count) {
    $total_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM `" . esc_sql($table_name) . "`"
    );
    wp_cache_set($cache_key, $total_count, $cache_group, $cache_expiration);
}

// Get count for today with cache
$cache_key = 'today_count_' . $today;
$today_count = wp_cache_get($cache_key, $cache_group);
if (false === $today_count) {
    $today_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM `" . esc_sql($table_name) . "` WHERE DATE(created_at) = %s",
            $today
        )
    );
    wp_cache_set($cache_key, $today_count, $cache_group, $cache_expiration);
}

// Get count for this week with cache
$cache_key = 'week_count_' . $last_week;
$week_count = wp_cache_get($cache_key, $cache_group);
if (false === $week_count) {
    $week_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM `" . esc_sql($table_name) . "` WHERE created_at >= %s",
            $last_week . ' 00:00:00'
        )
    );
    wp_cache_set($cache_key, $week_count, $cache_group, $cache_expiration);
}

// Get count for this month with cache
$cache_key = 'month_count_' . $last_month;
$month_count = wp_cache_get($cache_key, $cache_group);
if (false === $month_count) {
    $month_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM `" . esc_sql($table_name) . "` WHERE created_at >= %s",
            $last_month . ' 00:00:00'
        )
    );
    wp_cache_set($cache_key, $month_count, $cache_group, $cache_expiration);
}

// Get top 5 action types with cache
$cache_key = 'top_actions';
$top_actions = wp_cache_get($cache_key, $cache_group);
if (false === $top_actions) {
    $top_actions = $wpdb->get_results(
        "SELECT action_type, COUNT(*) as count FROM `" . esc_sql($table_name) . "` 
        GROUP BY action_type 
        ORDER BY count DESC 
        LIMIT 5"
    );
    wp_cache_set($cache_key, $top_actions, $cache_group, $cache_expiration);
}

// Get top 5 users with cache
$cache_key = 'top_users';
$top_users = wp_cache_get($cache_key, $cache_group);
if (false === $top_users) {
    $top_users = $wpdb->get_results(
        "SELECT user_id, username, COUNT(*) as count FROM `" . esc_sql($table_name) . "` 
        GROUP BY user_id 
        ORDER BY count DESC 
        LIMIT 5"
    );
    wp_cache_set($cache_key, $top_users, $cache_group, $cache_expiration);
}

// Get last 7 days activity with cache
$cache_key = 'last_seven_days_' . gmdate('Y-m-d');
$last_seven_days = wp_cache_get($cache_key, $cache_group);
if (false === $last_seven_days) {
    $last_seven_days = array();
    for ($i = 6; $i >= 0; $i--) {
        $date = gmdate('Y-m-d', strtotime("-{$i} days"));
        $day_cache_key = 'day_count_' . $date;
        $count = wp_cache_get($day_cache_key, $cache_group);
        
        if (false === $count) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM `" . esc_sql($table_name) . "` WHERE DATE(created_at) = %s",
                    $date
                )
            );
            wp_cache_set($day_cache_key, $count, $cache_group, $cache_expiration);
        }
        
        $last_seven_days[gmdate('D', strtotime($date))] = (int)$count;
    }
    wp_cache_set($cache_key, $last_seven_days, $cache_group, $cache_expiration);
}

// Get chart data by category with cache
$cache_key = 'categories';
$categories = wp_cache_get($cache_key, $cache_group);
if (false === $categories) {
    $categories = $wpdb->get_results(
        "SELECT object_type, COUNT(*) as count FROM `" . esc_sql($table_name) . "` 
        WHERE object_type != '' 
        GROUP BY object_type 
        ORDER BY count DESC"
    );
    wp_cache_set($cache_key, $categories, $cache_group, $cache_expiration);
}

$category_labels = array();
$category_data = array();
foreach ($categories as $category) {
    $category_labels[] = ucfirst($category->object_type);
    $category_data[] = (int)$category->count;
}
?>

<div class="wrap wp-total-monitor-wrap wp-total-monitor-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - <?php esc_html_e('Dashboard', 'total-activity-monitor'); ?></h1>
    
    <div class="wp-total-monitor-dashboard-header">
        <div class="wp-total-monitor-dashboard-period">
            <select id="wp-total-monitor-period-selector">
                <option value="7d"><?php esc_html_e('Last 7 Days', 'total-activity-monitor'); ?></option>
                <option value="30d"><?php esc_html_e('Last 30 Days', 'total-activity-monitor'); ?></option>
                <option value="all"><?php esc_html_e('All Time', 'total-activity-monitor'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="wp-total-monitor-dashboard-widgets">
        <!-- Stats Summary -->
        <div class="wp-total-monitor-dashboard-widget wp-total-monitor-summary-widget">
            <h2><?php esc_html_e('Activity Summary', 'total-activity-monitor'); ?></h2>
            <div class="wp-total-monitor-stats-grid">
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($today_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php esc_html_e('Today', 'total-activity-monitor'); ?></span>
                </div>
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($week_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php esc_html_e('This Week', 'total-activity-monitor'); ?></span>
                </div>
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($month_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php esc_html_e('This Month', 'total-activity-monitor'); ?></span>
                </div>
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($total_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php esc_html_e('Total Logs', 'total-activity-monitor'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Daily Activity Chart -->
        <div class="wp-total-monitor-dashboard-widget wp-total-monitor-chart-widget">
            <h2><?php esc_html_e('Daily Activity', 'total-activity-monitor'); ?></h2>
            <div class="wp-total-monitor-chart-container">
                <canvas id="wp-total-monitor-daily-chart"></canvas>
            </div>
        </div>
        
        <!-- Top Actions -->
        <div class="wp-total-monitor-dashboard-widget">
            <h2><?php esc_html_e('Top Actions', 'total-activity-monitor'); ?></h2>
            <div class="wp-total-monitor-table-container">
                <table class="wp-total-monitor-dashboard-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Action Type', 'total-activity-monitor'); ?></th>
                            <th><?php esc_html_e('Count', 'total-activity-monitor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_actions as $action): ?>
                            <tr>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $action->action_type))); ?></td>
                                <td><?php echo esc_html($action->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Top Users -->
        <div class="wp-total-monitor-dashboard-widget">
            <h2><?php esc_html_e('Most Active Users', 'total-activity-monitor'); ?></h2>
            <div class="wp-total-monitor-table-container">
                <table class="wp-total-monitor-dashboard-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Username', 'total-activity-monitor'); ?></th>
                            <th><?php esc_html_e('Activities', 'total-activity-monitor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_users as $user): ?>
                            <tr>
                                <td>
                                    <?php if ($user->user_id > 0): ?>
                                        <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user->user_id)); ?>">
                                            <?php echo esc_html($user->username); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($user->username); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($user->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Event Categories Chart -->
        <div class="wp-total-monitor-dashboard-widget wp-total-monitor-chart-widget">
            <h2><?php esc_html_e('Activity by Category', 'total-activity-monitor'); ?></h2>
            <div class="wp-total-monitor-chart-container">
                <canvas id="wp-total-monitor-category-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Daily Activity Chart
    var dailyCtx = document.getElementById('wp-total-monitor-daily-chart').getContext('2d');
    var dailyChart = new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo wp_json_encode(array_keys($last_seven_days)); ?>,
            datasets: [{
                label: '<?php esc_html_e('Activities', 'total-activity-monitor'); ?>',
                data: <?php echo wp_json_encode(array_values($last_seven_days)); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Categories Chart
    var categoryCtx = document.getElementById('wp-total-monitor-category-chart').getContext('2d');
    var categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo wp_json_encode($category_labels); ?>,
            datasets: [{
                data: <?php echo wp_json_encode($category_data); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // Period selector
    $('#wp-total-monitor-period-selector').on('change', function() {
        // In a real implementation, this would update charts via AJAX
        // For now just display a message
        alert('<?php echo esc_js(esc_html__("This functionality would filter data based on the selected period. Implementation would require AJAX calls to refresh the data.", "total-activity-monitor")); ?>');
    });
});
</script>
