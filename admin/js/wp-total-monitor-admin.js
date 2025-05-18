/**
 * Admin JavaScript for WP Total Monitor
 *
 * @package    WP_Total_Monitor
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle date range filters
        const dateFrom = $('#date_from');
        const dateTo = $('#date_to');

        // Ensure "to" date is not before "from" date
        dateTo.on('change', function() {
            const toDate = new Date($(this).val());
            const fromDate = new Date(dateFrom.val());
            
            if (fromDate > toDate) {
                dateFrom.val($(this).val());
            }
        });

        // Ensure "from" date is not after "to" date
        dateFrom.on('change', function() {
            const fromDate = new Date($(this).val());
            const toDateValue = dateTo.val();
            
            if (toDateValue) {
                const toDate = new Date(toDateValue);
                
                if (fromDate > toDate) {
                    dateTo.val($(this).val());
                }
            }
        });

        // Handle delete all logs button
        $('#wp-total-monitor-delete-all-logs').on('click', function(e) {
            e.preventDefault();
            
            if (confirm(wpTotalMonitor.confirmDelete)) {
                $.ajax({
                    url: wpTotalMonitor.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wp_total_monitor_delete_logs',
                        nonce: wpTotalMonitor.nonce
                    },
                    beforeSend: function() {
                        // Show loading indicator or disable button
                        $('#wp-total-monitor-delete-all-logs').addClass('disabled').text('Deleting...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            // Reload the page
                            window.location.reload();
                        } else {
                            alert(response.data.message);
                            $('#wp-total-monitor-delete-all-logs').removeClass('disabled').text('Delete All Logs');
                        }
                    },
                    error: function() {
                        alert('An error occurred while trying to delete logs.');
                        $('#wp-total-monitor-delete-all-logs').removeClass('disabled').text('Delete All Logs');
                    }
                });
            }
        });
    });

})(jQuery);
