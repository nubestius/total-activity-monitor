=== Total Activity Monitor ===
Contributors: shieldpress
Donate link: https://shieldpress.co/plugins/total-activity-monitor
Tags: activity log, security, monitoring, audit trail, woocommerce
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: LICENSE.txt

Comprehensive activity monitoring solution that logs all user actions on your WordPress site with analytics dashboard and CSV export capabilities.

== Description ==

WP Total Monitor provides extensive tracking of all user activities on your WordPress site, helping you maintain security and accountability. Keep a complete record of who did what and when on your website.

= Key Features =

* Interactive analytics dashboard with visual statistics
* Export logs to CSV for external analysis
* Track user logins and logouts
* Monitor content creation, updates, and deletions
* Track taxonomy changes (categories, tags, etc.)
* Log plugin activations, deactivations, and updates
* Record theme changes and updates
* Monitor role changes
* Customizable log retention periods (5 days, 10 days, 30 days, or forever)
* Filterable log interface
* IP address tracking
* Multiple language support (English, Spanish, French, German, Italian, Portuguese)

= Use Cases =

* **Security Monitoring**: Keep track of all changes to identify potential security issues
* **User Accountability**: Know which users are making what changes
* **Troubleshooting**: Identify the root cause when something goes wrong
* **Compliance**: Maintain detailed activity logs for regulatory requirements

== Installation ==

1. Upload the `wp-total-monitor` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the logs via the new "Total Monitor" menu in your admin dashboard
4. Configure the log retention period in the Settings tab

== Frequently Asked Questions ==

= How long are logs stored? =

By default, logs are kept for 30 days. You can change this to 5 days, 10 days, or set it to "Forever" to never delete logs automatically.

= Does this plugin slow down my site? =

No, WP Total Monitor is designed to have minimal impact on performance. Logging happens in the background and doesn't affect your site's front-end performance.

= Can I export logs? =

Yes! You can export all activity logs to CSV format. Simply go to the Activity Logs page and click the "Export to CSV" button. You can also apply filters before exporting to get exactly the data you need.

= Is this plugin GDPR compliant? =

The plugin stores user IDs, usernames, and IP addresses as part of its logging functionality. Ensure your site's privacy policy discloses this data collection. You should also consider the appropriate log retention period for your needs.

== Screenshots ==

1. Activity logs view with filtering options
2. Settings page with retention options

== Changelog ==

= 2.0.0 =
* Enhancement: Cache system implementation to optimize performance
* Enhancement: Significant reduction of direct database queries
* Enhancement: Improved dashboard with better visualizations
* Security: Fixed SQL injection vulnerability in uninstall.php
* Security: Proper implementation of query preparation
* Security: Correct escaping of table names with esc_sql()
* Security: Better handling of database schema changes
* Code: Rewritten uninstallation process with classes and best practices
* Code: Removed external resource dependencies (Chart.js now included locally)
* Code: Code optimization to comply with WordPress standards
* Documentation: Complete user guides in Spanish and English
* Documentation: Quick start guide

= 1.2.0 =
* Security: Added proper data escaping to all output
* Security: Enhanced data sanitization throughout the plugin
* Security: Added nonce verification to all forms and actions
* Security: Improved CSV export security with nonce verification
* Security: Added proper JSON encoding with wp_json_encode()
* Code: Various code improvements and best practices implementation

= 1.1.0 =
* Added export to CSV functionality
* Added multilingual support with language selection
* Added activity dashboard with statistics and charts
* Fixed minor bugs in logging functionality
* Added monitoring for theme changes and updates
* Added taxonomy tracking (categories, tags, etc.)
* Improved plugin description with detailed features
* Enhanced UI for better user experience
* Added comprehensive documentation

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major update that includes significant performance improvements with cache system, important security fixes, and general code optimization. Update recommended for all users.

= 1.2.0 =
This is a security update. It improves escaping, sanitization, and adds nonce verification to forms. Update recommended for all users.

= 1.1.0 =
This update adds an interactive dashboard, CSV export functionality, language selection for the admin interface, improved monitoring capabilities, and enhances the overall user experience.

= 1.0.0 =
Initial release of WP Total Monitor.

== Privacy Policy ==

WP Total Monitor stores the following user data:
* Username
* User role
* IP address
* Actions performed on the site

This data is stored in your WordPress database and is only accessible to administrators. The retention period is configurable in the plugin settings.
