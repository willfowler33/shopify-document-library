<?php
/**
 * Admin Settings Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('tcp_docs_messages'); ?>

    <form action="options.php" method="post">
        <?php
        settings_fields('tcp_docs_settings');
        do_settings_sections('tcp-documents-settings');
        submit_button(__('Save Settings', 'tcp-docs'));
        ?>
    </form>

    <hr>

    <div class="tcp-test-connection">
        <h2><?php _e('Test Connection', 'tcp-docs'); ?></h2>
        <p><?php _e('Test your Google Sheets connection before syncing.', 'tcp-docs'); ?></p>
        <button type="button" class="button button-secondary" id="tcp-test-connection">
            <?php _e('Test Connection', 'tcp-docs'); ?>
        </button>
        <div id="tcp-test-result" style="margin-top: 10px;"></div>
    </div>

    <hr>

    <div class="tcp-sync-info">
        <h2><?php _e('Sync Information', 'tcp-docs'); ?></h2>
        <?php
        $last_sync = get_option('tcp_docs_last_sync', '');
        if ($last_sync) {
            $time_diff = human_time_diff(strtotime($last_sync), current_time('timestamp'));
            echo '<p><strong>' . __('Last Sync:', 'tcp-docs') . '</strong> ' . esc_html($time_diff) . ' ' . __('ago', 'tcp-docs') . '</p>';
            echo '<p><small>' . esc_html($last_sync) . '</small></p>';
        } else {
            echo '<p>' . __('No sync performed yet.', 'tcp-docs') . '</p>';
        }
        ?>
        <a href="<?php echo admin_url('admin.php?page=tcp-documents-sync'); ?>" class="button button-primary">
            <?php _e('Sync Now', 'tcp-docs'); ?>
        </a>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#tcp-test-connection').on('click', function() {
        var button = $(this);
        var resultDiv = $('#tcp-test-result');

        button.prop('disabled', true).text('<?php _e('Testing...', 'tcp-docs'); ?>');
        resultDiv.html('');

        $.ajax({
            url: tcpDocs.restUrl + '/admin/test-connection',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', tcpDocs.nonce);
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success inline"><p>' + response.message + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error inline"><p>' + response.message + '</p></div>');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : '<?php _e('Connection test failed', 'tcp-docs'); ?>';
                resultDiv.html('<div class="notice notice-error inline"><p>' + message + '</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e('Test Connection', 'tcp-docs'); ?>');
            }
        });
    });
});
</script>
