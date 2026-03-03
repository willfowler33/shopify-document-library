<?php
/**
 * Admin Documents List Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="tcp-admin-stats">
        <div class="tcp-stat-box">
            <div class="tcp-stat-number"><?php echo esc_html($total); ?></div>
            <div class="tcp-stat-label"><?php _e('Total Documents', 'tcp-docs'); ?></div>
        </div>
        <div class="tcp-stat-box">
            <div class="tcp-stat-number"><?php echo count($categories); ?></div>
            <div class="tcp-stat-label"><?php _e('Categories', 'tcp-docs'); ?></div>
        </div>
        <div class="tcp-stat-box">
            <div class="tcp-stat-number"><?php echo count($brands); ?></div>
            <div class="tcp-stat-label"><?php _e('Brands', 'tcp-docs'); ?></div>
        </div>
        <div class="tcp-stat-box">
            <div class="tcp-stat-number"><?php echo count($product_systems); ?></div>
            <div class="tcp-stat-label"><?php _e('Product Systems', 'tcp-docs'); ?></div>
        </div>
    </div>

    <p>
        <a href="<?php echo admin_url('admin.php?page=tcp-documents-sync'); ?>" class="button button-primary">
            <?php _e('Sync from Google Sheets', 'tcp-docs'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=tcp-documents-settings'); ?>" class="button">
            <?php _e('Settings', 'tcp-docs'); ?>
        </a>
    </p>

    <div class="tcp-documents-filters">
        <form method="get">
            <input type="hidden" name="page" value="tcp-documents">

            <select name="filter_category" onchange="this.form.submit()">
                <option value=""><?php _e('All Categories', 'tcp-docs'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>"
                        <?php selected(isset($_GET['filter_category']) ? $_GET['filter_category'] : '', $cat); ?>>
                        <?php echo esc_html($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_brand" onchange="this.form.submit()">
                <option value=""><?php _e('All Brands', 'tcp-docs'); ?></option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo esc_attr($brand); ?>"
                        <?php selected(isset($_GET['filter_brand']) ? $_GET['filter_brand'] : '', $brand); ?>>
                        <?php echo esc_html($brand); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_product_system" onchange="this.form.submit()">
                <option value=""><?php _e('All Product Systems', 'tcp-docs'); ?></option>
                <?php foreach ($product_systems as $ps): ?>
                    <option value="<?php echo esc_attr($ps); ?>"
                        <?php selected(isset($_GET['filter_product_system']) ? $_GET['filter_product_system'] : '', $ps); ?>>
                        <?php echo esc_html($ps); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_type" onchange="this.form.submit()">
                <option value=""><?php _e('All Types', 'tcp-docs'); ?></option>
                <?php foreach ($types as $type): ?>
                    <option value="<?php echo esc_attr($type); ?>"
                        <?php selected(isset($_GET['filter_type']) ? $_GET['filter_type'] : '', $type); ?>>
                        <?php echo esc_html($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($_GET['filter_category']) || !empty($_GET['filter_brand']) || !empty($_GET['filter_product_system']) || !empty($_GET['filter_type'])): ?>
                <a href="<?php echo admin_url('admin.php?page=tcp-documents'); ?>" class="button">
                    <?php _e('Clear Filters', 'tcp-docs'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Title', 'tcp-docs'); ?></th>
                <th><?php _e('Category', 'tcp-docs'); ?></th>
                <th><?php _e('Brand', 'tcp-docs'); ?></th>
                <th><?php _e('Product System', 'tcp-docs'); ?></th>
                <th><?php _e('Type', 'tcp-docs'); ?></th>
                <th><?php _e('Updated', 'tcp-docs'); ?></th>
                <th><?php _e('Actions', 'tcp-docs'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($documents)): ?>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($doc->title); ?></strong>
                            <?php if ($doc->description): ?>
                                <br><small><?php echo esc_html(wp_trim_words($doc->description, 15)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($doc->category); ?></td>
                        <td><?php echo esc_html($doc->brand); ?></td>
                        <td><?php echo esc_html($doc->product_system); ?></td>
                        <td><?php echo esc_html($doc->document_type); ?></td>
                        <td><?php echo esc_html(human_time_diff(strtotime($doc->updated_at), current_time('timestamp'))); ?> ago</td>
                        <td>
                            <?php if ($doc->hubspot_file_url): ?>
                                <a href="<?php echo esc_url($doc->hubspot_file_url); ?>"
                                   class="button button-small"
                                   target="_blank">
                                    <?php _e('View PDF', 'tcp-docs'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">
                        <p style="text-align: center; padding: 40px 0;">
                            <?php _e('No documents found.', 'tcp-docs'); ?>
                            <a href="<?php echo admin_url('admin.php?page=tcp-documents-sync'); ?>">
                                <?php _e('Sync from Google Sheets', 'tcp-docs'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.tcp-admin-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}
.tcp-stat-box {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    min-width: 150px;
}
.tcp-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}
.tcp-stat-label {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}
.tcp-documents-filters {
    margin: 20px 0;
}
.tcp-documents-filters select {
    margin-right: 10px;
}
</style>
