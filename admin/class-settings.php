<?php
/**
 * Settings Class
 * Handles plugin settings and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_Settings {

    private static $instance = null;
    private $option_group = 'tcp_docs_settings';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Google Sheets settings
        register_setting($this->option_group, 'tcp_docs_google_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));

        register_setting($this->option_group, 'tcp_docs_spreadsheet_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1kGVVChB5uqT1RR4cCbYxb97Bu2PyI6tgU8F6BfzCFo0'
        ));

        // Auto-sync settings
        register_setting($this->option_group, 'tcp_docs_auto_sync_enabled', array(
            'type' => 'boolean',
            'default' => true
        ));

        register_setting($this->option_group, 'tcp_docs_require_login', array(
            'type' => 'boolean',
            'default' => true
        ));

        // Add settings sections
        add_settings_section(
            'tcp_docs_google_sheets_section',
            __('Google Sheets Integration', 'tcp-docs'),
            array($this, 'render_google_sheets_section'),
            'tcp-documents-settings'
        );

        add_settings_section(
            'tcp_docs_general_section',
            __('General Settings', 'tcp-docs'),
            array($this, 'render_general_section'),
            'tcp-documents-settings'
        );

        // Add settings fields - Google Sheets
        add_settings_field(
            'tcp_docs_google_api_key',
            __('Google Sheets API Key', 'tcp-docs'),
            array($this, 'render_api_key_field'),
            'tcp-documents-settings',
            'tcp_docs_google_sheets_section'
        );

        add_settings_field(
            'tcp_docs_spreadsheet_id',
            __('Spreadsheet ID', 'tcp-docs'),
            array($this, 'render_spreadsheet_id_field'),
            'tcp-documents-settings',
            'tcp_docs_google_sheets_section'
        );

        // Add settings fields - General
        add_settings_field(
            'tcp_docs_auto_sync_enabled',
            __('Auto-sync on Login', 'tcp-docs'),
            array($this, 'render_auto_sync_field'),
            'tcp-documents-settings',
            'tcp_docs_general_section'
        );

        add_settings_field(
            'tcp_docs_require_login',
            __('Require Login', 'tcp-docs'),
            array($this, 'render_require_login_field'),
            'tcp-documents-settings',
            'tcp_docs_general_section'
        );
    }

    /**
     * Render settings page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle settings save
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'tcp_docs_messages',
                'tcp_docs_message',
                __('Settings saved successfully.', 'tcp-docs'),
                'success'
            );
        }

        include TCP_DOCS_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Section callbacks
     */
    public function render_google_sheets_section() {
        echo '<p>' . __('Configure your Google Sheets integration to automatically sync document metadata.', 'tcp-docs') . '</p>';
    }

    public function render_general_section() {
        echo '<p>' . __('General plugin settings.', 'tcp-docs') . '</p>';
    }

    /**
     * Field callbacks
     */
    public function render_api_key_field() {
        $value = get_option('tcp_docs_google_api_key', '');
        ?>
        <input type="text"
               id="tcp_docs_google_api_key"
               name="tcp_docs_google_api_key"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="AIza..."
        />
        <p class="description">
            <?php _e('Your Google Sheets API key. Get one from the Google Cloud Console.', 'tcp-docs'); ?>
            <a href="https://console.cloud.google.com/apis/credentials" target="_blank">
                <?php _e('Get API Key', 'tcp-docs'); ?>
            </a>
        </p>
        <?php
    }

    public function render_spreadsheet_id_field() {
        $value = get_option('tcp_docs_spreadsheet_id', '1kGVVChB5uqT1RR4cCbYxb97Bu2PyI6tgU8F6BfzCFo0');
        ?>
        <input type="text"
               id="tcp_docs_spreadsheet_id"
               name="tcp_docs_spreadsheet_id"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="1kGVVChB5uqT1RR4cCbYxb97Bu2PyI6tgU8F6BfzCFo0"
        />
        <p class="description">
            <?php _e('The ID of your Google Sheet. Found in the spreadsheet URL between /d/ and /edit', 'tcp-docs'); ?>
            <br>
            <code>https://docs.google.com/spreadsheets/d/<strong>[SPREADSHEET_ID]</strong>/edit</code>
            <br>
            <?php _e('All sheets/tabs in this spreadsheet will be synced automatically.', 'tcp-docs'); ?>
        </p>
        <?php
    }

    public function render_auto_sync_field() {
        $value = get_option('tcp_docs_auto_sync_enabled', true);
        ?>
        <label>
            <input type="checkbox"
                   id="tcp_docs_auto_sync_enabled"
                   name="tcp_docs_auto_sync_enabled"
                   value="1"
                   <?php checked($value, true); ?>
            />
            <?php _e('Automatically sync documents when users log in', 'tcp-docs'); ?>
        </label>
        <?php
    }

    public function render_require_login_field() {
        $value = get_option('tcp_docs_require_login', true);
        ?>
        <label>
            <input type="checkbox"
                   id="tcp_docs_require_login"
                   name="tcp_docs_require_login"
                   value="1"
                   <?php checked($value, true); ?>
            />
            <?php _e('Require users to be logged in to view documents', 'tcp-docs'); ?>
        </label>
        <?php
    }
}
