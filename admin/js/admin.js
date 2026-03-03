/**
 * TCP Document Library - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Test Connection Button
        $('#tcp-test-connection').on('click', function() {
            var button = $(this);
            var resultDiv = $('#tcp-test-result');

            button.prop('disabled', true).text('Testing...');
            resultDiv.html('');

            $.ajax({
                url: tcpDocs.restUrl + '/admin/test-connection',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', tcpDocs.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html(
                            '<div class="notice notice-success inline">' +
                            '<p>' + response.message + '</p>' +
                            '<p>Rows found: ' + (response.row_count || 0) + '</p>' +
                            '</div>'
                        );
                    } else {
                        resultDiv.html(
                            '<div class="notice notice-error inline">' +
                            '<p>' + response.message + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Connection test failed. Please check your settings.';

                    resultDiv.html(
                        '<div class="notice notice-error inline">' +
                        '<p>' + message + '</p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        });

        // Sync Button (if exists on other pages)
        $('.tcp-sync-button').on('click', function(e) {
            if (!confirm(tcpDocs.strings.confirmSync)) {
                e.preventDefault();
                return false;
            }

            var button = $(this);
            button.prop('disabled', true).text('Syncing...');

            $.ajax({
                url: tcpDocs.restUrl + '/admin/sync',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', tcpDocs.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        alert(tcpDocs.strings.syncSuccess);
                        location.reload();
                    } else {
                        alert(tcpDocs.strings.syncError + '\n' + response.message);
                        button.prop('disabled', false).text('Sync Now');
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unknown error';
                    alert(tcpDocs.strings.syncError + '\n' + message);
                    button.prop('disabled', false).text('Sync Now');
                }
            });
        });

        // Auto-refresh sync status
        if ($('.tcp-sync-page').length) {
            // Could implement auto-refresh of sync status here
        }
    });

})(jQuery);
