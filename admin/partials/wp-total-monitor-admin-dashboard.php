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
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$last_week = date('Y-m-d', strtotime('-7 days'));
$last_month = date('Y-m-d', strtotime('-30 days'));

// Get total count
$total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

// Get count for today
$today_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE DATE(created_at) = %s",
    $today
));

// Get count for this week
$week_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %s",
    $last_week . ' 00:00:00'
));

// Get count for this month
$month_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %s",
    $last_month . ' 00:00:00'
));

// Get top 5 action types
$top_actions = $wpdb->get_results(
    "SELECT action_type, COUNT(*) as count FROM {$table_name} 
    GROUP BY action_type 
    ORDER BY count DESC 
    LIMIT 5"
);

// Get top 5 users
$top_users = $wpdb->get_results(
    "SELECT user_id, username, COUNT(*) as count FROM {$table_name} 
    GROUP BY user_id 
    ORDER BY count DESC 
    LIMIT 5"
);

// Get last 7 days activity
$last_seven_days = array();
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE DATE(created_at) = %s",
        $date
    ));
    $last_seven_days[date('D', strtotime($date))] = (int)$count;
}

// Get chart data by category
$categories = $wpdb->get_results(
    "SELECT object_type, COUNT(*) as count FROM {$table_name} 
    WHERE object_type != '' 
    GROUP BY object_type 
    ORDER BY count DESC"
);

$category_labels = array();
$category_data = array();
foreach ($categories as $category) {
    $category_labels[] = ucfirst($category->object_type);
    $category_data[] = (int)$category->count;
}
?>

<div class="wrap wp-total-monitor-wrap wp-total-monitor-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - <?php _e('Dashboard', 'wp-total-monitor'); ?></h1>
    
    <div class="wp-total-monitor-dashboard-header">
        <div class="wp-total-monitor-dashboard-period">
            <select id="wp-total-monitor-period-selector">
                <option value="7d"><?php _e('Last 7 Days', 'wp-total-monitor'); ?></option>
                <option value="30d"><?php _e('Last 30 Days', 'wp-total-monitor'); ?></option>
                <option value="all"><?php _e('All Time', 'wp-total-monitor'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="wp-total-monitor-dashboard-widgets">
        <!-- Stats Summary -->
        <div class="wp-total-monitor-dashboard-widget wp-total-monitor-summary-widget">
            <h2><?php _e('Activity Summary', 'wp-total-monitor'); ?></h2>
            <div class="wp-total-monitor-stats-grid">
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($today_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php _e('Today', 'wp-total-monitor'); ?></span>
                </div>
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($week_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php _e('This Week', 'wp-total-monitor'); ?></span>
                </div>
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($month_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php _e('This Month', 'wp-total-monitor'); ?></span>
                </div>
                <div class="wp-total-monitor-stat-box">
                    <span class="wp-total-monitor-stat-number"><?php echo esc_html($total_count); ?></span>
                    <span class="wp-total-monitor-stat-label"><?php _e('Total Logs', 'wp-total-monitor'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Daily Activity Chart -->
        <div class="wp-total-monitor-dashboard-widget wp-total-monitor-chart-widget">
            <h2><?php _e('Daily Activity', 'wp-total-monitor'); ?></h2>
            <div class="wp-total-monitor-chart-container">
                <canvas id="wp-total-monitor-daily-chart"></canvas>
            </div>
        </div>
        
        <!-- Top Actions -->
        <div class="wp-total-monitor-dashboard-widget">
            <h2><?php _e('Top Actions', 'wp-total-monitor'); ?></h2>
            <div class="wp-total-monitor-table-container">
                <table class="wp-total-monitor-dashboard-table">
                    <thead>
                        <tr>
                            <th><?php _e('Action Type', 'wp-total-monitor'); ?></th>
                            <th><?php _e('Count', 'wp-total-monitor'); ?></th>
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
            <h2><?php _e('Most Active Users', 'wp-total-monitor'); ?></h2>
            <div class="wp-total-monitor-table-container">
                <table class="wp-total-monitor-dashboard-table">
                    <thead>
                        <tr>
                            <th><?php _e('Username', 'wp-total-monitor'); ?></th>
                            <th><?php _e('Activities', 'wp-total-monitor'); ?></th>
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
            <h2><?php _e('Activity by Category', 'wp-total-monitor'); ?></h2>
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
                label: '<?php _e('Activities', 'wp-total-monitor'); ?>',
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
        alert('<?php _e("This functionality would filter data based on the selected period. Implementation would require AJAX calls to refresh the data.", "wp-total-monitor"); ?>');
    });
});
</script>
