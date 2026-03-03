<?php
/**
 * Plugin Name: TCP Document Library
 * Plugin URI: https://github.com/willfowler33/cptdssds
 * Description: Progressive Web App for The Concrete Protector - provides mobile-optimized access to technical PDF documents (TDS/SDS) with Google Sheets integration.
 * Version: 1.0.0
 * Author: The Concrete Protector
 * Author URI: https://theconcreteprotector.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tcp-docs
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TCP_DOCS_VERSION', '1.0.0');
define('TCP_DOCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TCP_DOCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TCP_DOCS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class TCP_Document_Library {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once TCP_DOCS_PLUGIN_DIR . 'includes/class-database.php';
        require_once TCP_DOCS_PLUGIN_DIR . 'includes/class-google-sheets.php';
        require_once TCP_DOCS_PLUGIN_DIR . 'includes/class-api.php';
        require_once TCP_DOCS_PLUGIN_DIR . 'includes/class-shortcode.php';

        // Admin classes
        if (is_admin()) {
            require_once TCP_DOCS_PLUGIN_DIR . 'admin/class-admin.php';
            require_once TCP_DOCS_PLUGIN_DIR . 'admin/class-settings.php';
        }

        // Public classes
        require_once TCP_DOCS_PLUGIN_DIR . 'public/class-public.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));

        // Auto-sync on user login
        add_action('wp_login', array($this, 'auto_sync_on_login'), 10, 2);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        TCP_Database::create_tables();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Check for database upgrades
        TCP_Database::upgrade_database();

        // Initialize database
        TCP_Database::get_instance();

        // Initialize API
        TCP_API::get_instance();

        // Initialize shortcode
        TCP_Shortcode::get_instance();

        // Initialize admin
        if (is_admin()) {
            TCP_Admin::get_instance();
            TCP_Settings::get_instance();
        }

        // Initialize public
        TCP_Public::get_instance();

        // Load text domain
        load_plugin_textdomain('tcp-docs', false, dirname(TCP_DOCS_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Auto-sync on user login
     */
    public function auto_sync_on_login($user_login, $user) {
        // Trigger background sync
        if (class_exists('TCP_Google_Sheets')) {
            TCP_Google_Sheets::get_instance()->schedule_sync();
        }
    }
}

/**
 * Initialize the plugin
 */
function tcp_docs_init() {
    return TCP_Document_Library::get_instance();
}

// Start the plugin
tcp_docs_init();
