<?php
/**
 * Admin Class
 * Handles admin interface and functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('TCP Documents', 'tcp-docs'),
            __('TCP Documents', 'tcp-docs'),
            'manage_options',
            'tcp-documents',
            array($this, 'render_documents_page'),
            'dashicons-media-document',
            30
        );

        add_submenu_page(
            'tcp-documents',
            __('All Documents', 'tcp-docs'),
            __('All Documents', 'tcp-docs'),
            'manage_options',
            'tcp-documents',
            array($this, 'render_documents_page')
        );

        add_submenu_page(
            'tcp-documents',
            __('Settings', 'tcp-docs'),
            __('Settings', 'tcp-docs'),
            'manage_options',
            'tcp-documents-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'tcp-documents',
            __('Sync', 'tcp-docs'),
            __('Sync Now', 'tcp-docs'),
            'manage_options',
            'tcp-documents-sync',
            array($this, 'render_sync_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'tcp-documents') === false) {
            return;
        }

        wp_enqueue_style(
            'tcp-admin-css',
            TCP_DOCS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            TCP_DOCS_VERSION
        );

        wp_enqueue_script(
            'tcp-admin-js',
            TCP_DOCS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            TCP_DOCS_VERSION,
            true
        );

        wp_localize_script('tcp-admin-js', 'tcpDocs', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('tcp-docs/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'syncSuccess' => __('Sync completed successfully!', 'tcp-docs'),
                'syncError' => __('Sync failed. Please check your settings.', 'tcp-docs'),
                'confirmSync' => __('Are you sure you want to sync from Google Sheets?', 'tcp-docs')
            )
        ));
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $api_key = get_option('tcp_docs_google_api_key', '');
        $spreadsheet_id = get_option('tcp_docs_spreadsheet_id', '');

        if (empty($api_key) || empty($spreadsheet_id)) {
            $settings_url = admin_url('admin.php?page=tcp-documents-settings');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('TCP Documents:', 'tcp-docs'); ?></strong>
                    <?php _e('Please configure your Google Sheets settings to enable document syncing.', 'tcp-docs'); ?>
                    <a href="<?php echo esc_url($settings_url); ?>"><?php _e('Go to Settings', 'tcp-docs'); ?></a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Render documents page
     */
    public function render_documents_page() {
        $db = TCP_Database::get_instance();

        // Get filter values from URL
        $filter_category = isset($_GET['filter_category']) ? sanitize_text_field($_GET['filter_category']) : '';
        $filter_brand = isset($_GET['filter_brand']) ? sanitize_text_field($_GET['filter_brand']) : '';
        $filter_product_system = isset($_GET['filter_product_system']) ? sanitize_text_field($_GET['filter_product_system']) : '';
        $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';

        // Get documents with filters and NO limit for admin view
        $documents = $db->get_documents(array(
            'category' => $filter_category,
            'brand' => $filter_brand,
            'product_system' => $filter_product_system,
            'document_type' => $filter_type,
            'limit' => -1
        ));

        // Get total count (unfiltered)
        $total = $db->get_total_count();

        // Get filter options
        $categories = $db->get_categories();
        $brands = $db->get_brands();
        $product_systems = $db->get_product_systems();
        $types = $db->get_document_types();

        include TCP_DOCS_PLUGIN_DIR . 'admin/views/documents-list.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        TCP_Settings::get_instance()->render_page();
    }

    /**
     * Render sync page
     */
    public function render_sync_page() {
        include TCP_DOCS_PLUGIN_DIR . 'admin/views/sync-page.php';
    }
}
