<?php
/**
 * REST API Class
 * Provides REST endpoints for document queries and operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_API {

    private static $instance = null;
    private $namespace = 'tcp-docs/v1';
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = TCP_Database::get_instance();
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        // Get documents with filters
        register_rest_route($this->namespace, '/documents', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_documents'),
            'permission_callback' => array($this, 'check_read_permission'),
            'args' => array(
                'search' => array(
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'category' => array(
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'brand' => array(
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'product_system' => array(
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'document_type' => array(
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 1,
                    'maximum' => 100
                ),
                'limit' => array(
                    'type' => 'integer',
                    'default' => 30
                ),
                'offset' => array(
                    'type' => 'integer',
                    'default' => 0
                )
            )
        ));

        // Get single document
        register_rest_route($this->namespace, '/documents/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_document'),
            'permission_callback' => array($this, 'check_read_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));

        // Get filter options
        register_rest_route($this->namespace, '/filters', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_filters'),
            'permission_callback' => array($this, 'check_read_permission')
        ));

        // Get current user
        register_rest_route($this->namespace, '/auth/user', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_user'),
            'permission_callback' => '__return_true'
        ));

        // Admin: Sync from Google Sheets
        register_rest_route($this->namespace, '/admin/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_sheets'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));

        // Admin: Test Google Sheets connection
        register_rest_route($this->namespace, '/admin/test-connection', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_connection'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }

    /**
     * Get documents endpoint
     */
    public function get_documents($request) {
        // Support both page/per_page and limit/offset
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');

        // Calculate offset from page if using pagination
        $limit = $request->get_param('limit');
        $offset = $request->get_param('offset');

        if ($page && $per_page) {
            $limit = $per_page;
            $offset = ($page - 1) * $per_page;
        }

        $params = array(
            'search' => $request->get_param('search'),
            'category' => $request->get_param('category'),
            'brand' => $request->get_param('brand'),
            'product_system' => $request->get_param('product_system'),
            'document_type' => $request->get_param('document_type'),
            'limit' => $limit,
            'offset' => $offset
        );

        $documents = $this->db->get_documents($params);
        $total = $this->db->get_total_count($params);

        $response = array(
            'success' => true,
            'data' => $documents,
            'total' => $total,
            'pagination' => array(
                'page' => $page ? $page : 1,
                'per_page' => $limit,
                'total_pages' => $limit > 0 ? ceil($total / $limit) : 1,
                'total_items' => $total
            )
        );

        return new WP_REST_Response($response, 200);
    }

    /**
     * Get single document endpoint
     */
    public function get_document($request) {
        $id = $request->get_param('id');
        $document = $this->db->get_document($id);

        if (!$document) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Document not found'
            ), 404);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $document
        ), 200);
    }

    /**
     * Get filter options endpoint
     */
    public function get_filters($request) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'categories' => $this->db->get_categories(),
                'brands' => $this->db->get_brands(),
                'product_systems' => $this->db->get_product_systems(),
                'document_types' => $this->db->get_document_types()
            )
        ), 200);
    }

    /**
     * Get current user endpoint
     */
    public function get_current_user($request) {
        $user = wp_get_current_user();

        if ($user->ID === 0) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Not logged in'
            ), 401);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar_url' => get_avatar_url($user->ID),
                'is_admin' => current_user_can('manage_options')
            )
        ), 200);
    }

    /**
     * Sync from Google Sheets endpoint
     */
    public function sync_sheets($request) {
        $sheets = TCP_Google_Sheets::get_instance();
        $result = $sheets->sync_from_sheets();

        $status = $result['success'] ? 200 : 500;

        return new WP_REST_Response($result, $status);
    }

    /**
     * Test Google Sheets connection endpoint
     */
    public function test_connection($request) {
        $sheets = TCP_Google_Sheets::get_instance();
        $result = $sheets->test_connection();

        $status = $result['success'] ? 200 : 500;

        return new WP_REST_Response($result, $status);
    }

    /**
     * Check read permission
     */
    public function check_read_permission($request) {
        // Check if login is required
        $require_login = get_option('tcp_docs_require_login', true);

        if ($require_login) {
            return is_user_logged_in();
        }

        // Allow public access if login is not required
        return true;
    }

    /**
     * Check admin permission
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }
}
