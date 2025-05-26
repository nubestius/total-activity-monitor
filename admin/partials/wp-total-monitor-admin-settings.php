<?php
/**
 * Admin page for plugin settings
 *
 * @since      1.0.0
 * @package    WP_Total_Monitor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wp-total-monitor-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('wp_total_monitor_settings');
        do_settings_sections('wp_total_monitor_settings');
        submit_button();
        ?>
    </form>
    
    <div class="wp-total-monitor-info">
        <h2><?php esc_html_e('Plugin Information', 'total-activity-monitor'); ?></h2>
        <div class="wp-total-monitor-description">
            <h3><?php esc_html_e('What does WP Total Monitor do?', 'total-activity-monitor'); ?></h3>
            <p><?php esc_html_e('WP Total Monitor is a comprehensive security and activity tracking solution for WordPress. It helps you monitor and log all user activities on your site, giving you complete visibility into what is happening behind the scenes.', 'total-activity-monitor'); ?></p>
            
            <h3><?php esc_html_e('Key Features', 'total-activity-monitor'); ?></h3>
            <ul>
                <li><?php esc_html_e('Complete user activity tracking - logins, logouts, profile changes', 'total-activity-monitor'); ?></li>
                <li><?php esc_html_e('Content modifications - track all post/page edits and deletions', 'total-activity-monitor'); ?></li>
                <li><?php esc_html_e('Plugin and theme changes - monitor when plugins or themes are activated, deactivated, or updated', 'total-activity-monitor'); ?></li>
                <li><?php esc_html_e('Settings changes - keep an eye on important option updates', 'total-activity-monitor'); ?></li>
                <li><?php esc_html_e('Multiple language support - use the plugin in your preferred language', 'total-activity-monitor'); ?></li>
            </ul>
            
            <p class="wp-total-monitor-version-info">
                <?php esc_html_e('This plugin is developed by ', 'total-activity-monitor'); ?><a href="https://shieldpress.co" target="_blank">ShieldPress</a>.
                <br>
                <?php esc_html_e('Version:', 'total-activity-monitor'); ?> <strong><?php echo esc_html(WP_TOTAL_MONITOR_VERSION); ?></strong>
            </p>
        </div>
    </div>
    
    <div class="wp-total-monitor-danger-zone">
        <h2><?php esc_html_e('Danger Zone', 'total-activity-monitor'); ?></h2>
        <p><?php esc_html_e('The actions below will permanently delete log data.', 'total-activity-monitor'); ?></p>
        
        <div class="wp-total-monitor-delete-logs">
            <h3><?php esc_html_e('Delete All Logs', 'total-activity-monitor'); ?></h3>
            <p><?php esc_html_e('This will permanently delete all activity logs from the database.', 'total-activity-monitor'); ?></p>
            <a href="#" class="button button-delete" id="wp-total-monitor-delete-all-logs"><?php esc_html_e('Delete All Logs', 'total-activity-monitor'); ?></a>
        </div>
    </div>
</div>
