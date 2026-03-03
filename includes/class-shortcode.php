<?php
/**
 * Shortcode Class
 * Provides shortcode for displaying document library
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_Shortcode {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('tcp_documents', array($this, 'render_shortcode'));
    }

    /**
     * Render shortcode
     * Usage: [tcp_documents]
     * With attributes: [tcp_documents category="Epoxies" brand="The Concrete Protector"]
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'brand' => '',
            'document_type' => '',
            'search' => ''
        ), $atts);

        // Check if login is required
        $require_login = get_option('tcp_docs_require_login', true);
        if ($require_login && !is_user_logged_in()) {
            return $this->render_login_required();
        }

        // Enqueue frontend assets
        $this->enqueue_assets();

        // Create container with data attributes
        $data_attrs = array();
        foreach ($atts as $key => $value) {
            if (!empty($value)) {
                $data_attrs[] = 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }

        $data_attrs_string = implode(' ', $data_attrs);

        ob_start();
        ?>
        <div id="tcp-document-library"
             class="tcp-documents-wrapper"
             <?php echo $data_attrs_string; ?>>
            <div class="tcp-loading">
                <div class="tcp-spinner"></div>
                <p>Loading documents...</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login required message
     */
    private function render_login_required() {
        $login_url = wp_login_url(get_permalink());

        ob_start();
        ?>
        <div class="tcp-login-required">
            <div class="tcp-login-box">
                <h3>Login Required</h3>
                <p>Please log in to access the document library.</p>
                <a href="<?php echo esc_url($login_url); ?>" class="tcp-login-button">
                    Login
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     */
    private function enqueue_assets() {
        // This will be called by the Public class
        // Just a placeholder to ensure assets are loaded
        do_action('tcp_docs_enqueue_assets');
    }
}
