<?php
/**
 * Admin Sync Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle manual sync
$sync_result = null;
if (isset($_POST['tcp_manual_sync']) && check_admin_referer('tcp_manual_sync')) {
    $sheets = TCP_Google_Sheets::get_instance();
    $sync_result = $sheets->sync_from_sheets();
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($sync_result): ?>
        <?php if ($sync_result['success']): ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e('Sync completed successfully!', 'tcp-docs'); ?></strong></p>
                <?php if (isset($sync_result['data'])): ?>
                    <ul>
                        <li><?php printf(__('Created: %d', 'tcp-docs'), $sync_result['data']['created']); ?></li>
                        <li><?php printf(__('Updated: %d', 'tcp-docs'), $sync_result['data']['updated']); ?></li>
                        <li><?php printf(__('Skipped: %d', 'tcp-docs'), $sync_result['data']['skipped']); ?></li>
                        <?php if (isset($sync_result['data']['errors']) && $sync_result['data']['errors'] > 0): ?>
                            <li><?php printf(__('Errors: %d', 'tcp-docs'), $sync_result['data']['errors']); ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="notice notice-error is-dismissible">
                <p><strong><?php _e('Sync failed:', 'tcp-docs'); ?></strong></p>
                <p><?php echo esc_html($sync_result['message']); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="tcp-sync-page">
        <div class="card">
            <h2><?php _e('Sync Documents from Google Sheets', 'tcp-docs'); ?></h2>
            <p><?php _e('This will fetch the latest document metadata from your Google Sheet and update the database.', 'tcp-docs'); ?></p>

            <?php
            $last_sync = get_option('tcp_docs_last_sync', '');
            if ($last_sync):
                $time_diff = human_time_diff(strtotime($last_sync), current_time('timestamp'));
            ?>
                <p>
                    <strong><?php _e('Last Sync:', 'tcp-docs'); ?></strong>
                    <?php echo esc_html($time_diff); ?> <?php _e('ago', 'tcp-docs'); ?>
                    <br>
                    <small><?php echo esc_html($last_sync); ?></small>
                </p>
            <?php else: ?>
                <p><em><?php _e('No sync performed yet.', 'tcp-docs'); ?></em></p>
            <?php endif; ?>

            <form method="post" onsubmit="return confirm('<?php _e('Are you sure you want to sync documents from Google Sheets?', 'tcp-docs'); ?>');">
                <?php wp_nonce_field('tcp_manual_sync'); ?>
                <button type="submit" name="tcp_manual_sync" class="button button-primary button-large">
                    <?php _e('Sync Now', 'tcp-docs'); ?>
                </button>
            </form>
        </div>

        <div class="card">
            <h2><?php _e('How It Works', 'tcp-docs'); ?></h2>
            <ol>
                <li><?php _e('Connects to your Google Sheet using the API key configured in settings', 'tcp-docs'); ?></li>
                <li><?php _e('Fetches all rows from the "File Data" tab', 'tcp-docs'); ?></li>
                <li><?php _e('Matches documents by HubSpot File URL (unique identifier)', 'tcp-docs'); ?></li>
                <li><?php _e('Creates new documents or updates existing ones', 'tcp-docs'); ?></li>
                <li><?php _e('Skips rows without a HubSpot File URL', 'tcp-docs'); ?></li>
            </ol>

            <h3><?php _e('Required Columns', 'tcp-docs'); ?></h3>
            <ul>
                <li><strong>Title</strong> - Document title</li>
                <li><strong>Description</strong> - Document description</li>
                <li><strong>Category</strong> - Epoxies, Polyaspartics, etc.</li>
                <li><strong>Brand</strong> - The Concrete Protector, Match Patch Pro, etc.</li>
                <li><strong>Document Type</strong> - TDS or SDS</li>
                <li><strong>HubSpot File URL</strong> - <em>(required)</em> Direct link to PDF</li>
                <li><strong>HubSpot File ID</strong> - File ID in HubSpot</li>
                <li><strong>File Name</strong> - Original filename</li>
            </ul>
        </div>

        <div class="card">
            <h2><?php _e('Auto-Sync', 'tcp-docs'); ?></h2>
            <?php $auto_sync = get_option('tcp_docs_auto_sync_enabled', true); ?>
            <p>
                <?php if ($auto_sync): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    <strong><?php _e('Auto-sync is enabled', 'tcp-docs'); ?></strong>
                <?php else: ?>
                    <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                    <strong><?php _e('Auto-sync is disabled', 'tcp-docs'); ?></strong>
                <?php endif; ?>
            </p>
            <p><?php _e('When enabled, documents are automatically synced in the background when users log in.', 'tcp-docs'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=tcp-documents-settings'); ?>" class="button">
                <?php _e('Change Settings', 'tcp-docs'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.tcp-sync-page .card {
    max-width: 800px;
    margin-bottom: 20px;
}
.tcp-sync-page h2 {
    margin-top: 0;
}
.tcp-sync-page ol,
.tcp-sync-page ul {
    margin-left: 20px;
}
</style>
