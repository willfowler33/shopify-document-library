<?php
/**
 * Database Management Class
 * Handles all database operations for TCP Document Library
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_Database {

    private static $instance = null;
    private $wpdb;
    private $documents_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->documents_table = $wpdb->prefix . 'tcp_documents';
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $documents_table = $wpdb->prefix . 'tcp_documents';

        $sql = "CREATE TABLE IF NOT EXISTS $documents_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description text,
            category varchar(100),
            brand varchar(100),
            product_system varchar(100),
            document_type varchar(50),
            file_name varchar(255),
            file_path text,
            hubspot_file_url text,
            hubspot_file_id varchar(255),
            uploaded_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY brand (brand),
            KEY product_system (product_system),
            KEY document_type (document_type),
            KEY hubspot_file_url (hubspot_file_url(255))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Run upgrade check
        self::upgrade_database();
    }

    /**
     * Upgrade database schema if needed
     */
    public static function upgrade_database() {
        global $wpdb;
        $documents_table = $wpdb->prefix . 'tcp_documents';

        // Check if product_system column exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'product_system'",
                DB_NAME,
                $documents_table
            )
        );

        // Add product_system column if it doesn't exist
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE {$documents_table}
                ADD COLUMN product_system varchar(100) DEFAULT NULL AFTER brand,
                ADD KEY product_system (product_system)"
            );
        }
    }

    /**
     * Get all documents with filters
     */
    public function get_documents($args = array()) {
        $defaults = array(
            'search' => '',
            'category' => '',
            'brand' => '',
            'product_system' => '',
            'document_type' => '',
            'limit' => 30,
            'offset' => 0,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $params = array();

        // Search
        if (!empty($args['search'])) {
            $where[] = '(title LIKE %s OR description LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Category filter
        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $params[] = $args['category'];
        }

        // Brand filter
        if (!empty($args['brand'])) {
            $where[] = 'brand = %s';
            $params[] = $args['brand'];
        }

        // Product System filter
        if (!empty($args['product_system'])) {
            $where[] = 'product_system = %s';
            $params[] = $args['product_system'];
        }

        // Document type filter
        if (!empty($args['document_type'])) {
            $where[] = 'document_type = %s';
            $params[] = $args['document_type'];
        }

        $where_clause = implode(' AND ', $where);

        // Build query
        $query = "SELECT * FROM {$this->documents_table} WHERE {$where_clause}";

        // Add ordering
        $allowed_orderby = array('id', 'title', 'category', 'brand', 'created_at', 'updated_at');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'title';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY {$orderby} {$order}";

        // Add limit
        if ($args['limit'] > 0) {
            $query .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }

        // Prepare and execute
        if (!empty($params)) {
            $query = $this->wpdb->prepare($query, $params);
        }

        return $this->wpdb->get_results($query);
    }

    /**
     * Get document by ID
     */
    public function get_document($id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->documents_table} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Get document by HubSpot URL
     */
    public function get_document_by_hubspot_url($url) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->documents_table} WHERE hubspot_file_url = %s",
                $url
            )
        );
    }

    /**
     * Insert document
     */
    public function insert_document($data) {
        $defaults = array(
            'title' => '',
            'description' => '',
            'category' => '',
            'brand' => '',
            'product_system' => '',
            'document_type' => '',
            'file_name' => '',
            'file_path' => '',
            'hubspot_file_url' => '',
            'hubspot_file_id' => '',
            'uploaded_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->documents_table,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update document
     */
    public function update_document($id, $data) {
        return $this->wpdb->update(
            $this->documents_table,
            $data,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Update or insert document by HubSpot URL
     */
    public function upsert_document_by_hubspot_url($data) {
        if (empty($data['hubspot_file_url'])) {
            return false;
        }

        $existing = $this->get_document_by_hubspot_url($data['hubspot_file_url']);

        if ($existing) {
            // Update existing
            return $this->update_document($existing->id, $data);
        } else {
            // Insert new
            return $this->insert_document($data);
        }
    }

    /**
     * Delete document
     */
    public function delete_document($id) {
        return $this->wpdb->delete(
            $this->documents_table,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Get unique categories
     */
    public function get_categories() {
        return $this->wpdb->get_col(
            "SELECT DISTINCT category FROM {$this->documents_table} WHERE category != '' ORDER BY category ASC"
        );
    }

    /**
     * Get unique brands
     */
    public function get_brands() {
        return $this->wpdb->get_col(
            "SELECT DISTINCT brand FROM {$this->documents_table} WHERE brand != '' ORDER BY brand ASC"
        );
    }

    /**
     * Get unique product systems
     */
    public function get_product_systems() {
        return $this->wpdb->get_col(
            "SELECT DISTINCT product_system FROM {$this->documents_table} WHERE product_system != '' ORDER BY product_system ASC"
        );
    }

    /**
     * Get unique document types
     */
    public function get_document_types() {
        return $this->wpdb->get_col(
            "SELECT DISTINCT document_type FROM {$this->documents_table} WHERE document_type != '' ORDER BY document_type ASC"
        );
    }

    /**
     * Get total document count
     */
    public function get_total_count($args = array()) {
        $defaults = array(
            'search' => '',
            'category' => '',
            'brand' => '',
            'product_system' => '',
            'document_type' => ''
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $params = array();

        if (!empty($args['search'])) {
            $where[] = '(title LIKE %s OR description LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $params[] = $args['category'];
        }

        if (!empty($args['brand'])) {
            $where[] = 'brand = %s';
            $params[] = $args['brand'];
        }

        if (!empty($args['product_system'])) {
            $where[] = 'product_system = %s';
            $params[] = $args['product_system'];
        }

        if (!empty($args['document_type'])) {
            $where[] = 'document_type = %s';
            $params[] = $args['document_type'];
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM {$this->documents_table} WHERE {$where_clause}";

        if (!empty($params)) {
            $query = $this->wpdb->prepare($query, $params);
        }

        return (int) $this->wpdb->get_var($query);
    }
}
