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
        <h2><?php _e('Plugin Information', 'wp-total-monitor'); ?></h2>
        <div class="wp-total-monitor-description">
            <h3><?php _e('What does WP Total Monitor do?', 'wp-total-monitor'); ?></h3>
            <p><?php _e('WP Total Monitor is a comprehensive security and activity tracking solution for WordPress. It helps you monitor and log all user activities on your site, giving you complete visibility into what is happening behind the scenes.', 'wp-total-monitor'); ?></p>
            
            <h3><?php _e('Key Features', 'wp-total-monitor'); ?></h3>
            <ul>
                <li><?php _e('Complete user activity tracking - logins, logouts, profile changes', 'wp-total-monitor'); ?></li>
                <li><?php _e('Content modifications - track all post/page edits and deletions', 'wp-total-monitor'); ?></li>
                <li><?php _e('Plugin and theme changes - monitor when plugins or themes are activated, deactivated, or updated', 'wp-total-monitor'); ?></li>
                <li><?php _e('Settings changes - keep an eye on important option updates', 'wp-total-monitor'); ?></li>
                <li><?php _e('Multiple language support - use the plugin in your preferred language', 'wp-total-monitor'); ?></li>
            </ul>
            
            <p class="wp-total-monitor-version-info">
                <?php _e('This plugin is developed by ', 'wp-total-monitor'); ?><a href="https://shieldpress.co" target="_blank">ShieldPress</a>.
                <br>
                <?php _e('Version:', 'wp-total-monitor'); ?> <strong><?php echo esc_html(WP_TOTAL_MONITOR_VERSION); ?></strong>
            </p>
        </div>
    </div>
    
    <div class="wp-total-monitor-danger-zone">
        <h2><?php _e('Danger Zone', 'wp-total-monitor'); ?></h2>
        <p><?php _e('The actions below will permanently delete log data.', 'wp-total-monitor'); ?></p>
        
        <div class="wp-total-monitor-delete-logs">
            <h3><?php _e('Delete All Logs', 'wp-total-monitor'); ?></h3>
            <p><?php _e('This will permanently delete all activity logs from the database.', 'wp-total-monitor'); ?></p>
            <a href="#" class="button button-delete" id="wp-total-monitor-delete-all-logs"><?php _e('Delete All Logs', 'wp-total-monitor'); ?></a>
        </div>
    </div>
</div>
