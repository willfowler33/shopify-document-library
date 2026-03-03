<?php
/**
 * Google Sheets Integration Class
 * Syncs document metadata from Google Sheets to WordPress database
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_Google_Sheets {

    private static $instance = null;
    private $api_key;
    private $spreadsheet_id;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api_key = get_option('tcp_docs_google_api_key', '');
        $this->spreadsheet_id = get_option('tcp_docs_spreadsheet_id', '1kGVVChB5uqT1RR4cCbYxb97Bu2PyI6tgU8F6BfzCFo0');
        $this->db = TCP_Database::get_instance();

        // Schedule periodic sync
        add_action('tcp_docs_scheduled_sync', array($this, 'sync_from_sheets'));
    }

    /**
     * Fetch all sheet names from the spreadsheet
     */
    private function fetch_sheet_names() {
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}?key={$this->api_key}&fields=sheets.properties.title";

        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch spreadsheet metadata: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception("Google Sheets API returned status {$status_code}: {$body}");
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $sheet_names = array();
        if (!empty($data['sheets'])) {
            foreach ($data['sheets'] as $sheet) {
                if (!empty($sheet['properties']['title'])) {
                    $sheet_names[] = $sheet['properties']['title'];
                }
            }
        }

        return $sheet_names;
    }

    /**
     * Schedule background sync
     */
    public function schedule_sync() {
        if (!wp_next_scheduled('tcp_docs_scheduled_sync')) {
            wp_schedule_single_event(time() + 10, 'tcp_docs_scheduled_sync');
        }
    }

    /**
     * Main sync function
     */
    public function sync_from_sheets() {
        if (empty($this->api_key) || empty($this->spreadsheet_id)) {
            error_log('TCP Docs: Google Sheets API key or Spreadsheet ID not configured');
            return array(
                'success' => false,
                'message' => 'Google Sheets API credentials not configured'
            );
        }

        try {
            // Fetch all sheet names
            $sheet_names = $this->fetch_sheet_names();

            if (empty($sheet_names)) {
                return array(
                    'success' => false,
                    'message' => 'No sheets found in the spreadsheet'
                );
            }

            // Combined stats across all sheets
            $combined_stats = array(
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'sheets_processed' => 0
            );

            // Loop through each sheet and process
            foreach ($sheet_names as $sheet_name) {
                try {
                    $data = $this->fetch_sheet_data($sheet_name);

                    if (!empty($data)) {
                        $result = $this->process_sheet_data($data);
                        $combined_stats['created'] += $result['created'];
                        $combined_stats['updated'] += $result['updated'];
                        $combined_stats['skipped'] += $result['skipped'];
                        $combined_stats['errors'] += isset($result['errors']) ? $result['errors'] : 0;
                        $combined_stats['sheets_processed']++;
                    }
                } catch (Exception $e) {
                    error_log("TCP Docs: Error processing sheet '{$sheet_name}': " . $e->getMessage());
                    $combined_stats['errors']++;
                }
            }

            // Update last sync time
            update_option('tcp_docs_last_sync', current_time('mysql'));

            return array(
                'success' => true,
                'message' => "Sync completed successfully. Processed {$combined_stats['sheets_processed']} sheets.",
                'data' => $combined_stats
            );

        } catch (Exception $e) {
            error_log('TCP Docs sync error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Fetch data from a specific sheet
     */
    private function fetch_sheet_data($sheet_name) {
        $range = urlencode($sheet_name);
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}/values/{$range}?key={$this->api_key}";

        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            throw new Exception("Failed to fetch data from sheet '{$sheet_name}': " . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception("Google Sheets API returned status {$status_code} for sheet '{$sheet_name}': {$body}");
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['values'])) {
            return array();
        }

        return $data['values'];
    }

    /**
     * Process sheet data and update database
     */
    private function process_sheet_data($rows) {
        if (empty($rows)) {
            return array(
                'created' => 0,
                'updated' => 0,
                'skipped' => 0
            );
        }

        // First row is headers
        $headers = array_shift($rows);

        // Map headers to column indices
        $column_map = $this->map_columns($headers);

        $stats = array(
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        );

        foreach ($rows as $row) {
            try {
                $document_data = $this->parse_row($row, $column_map);

                // Skip rows without HubSpot URL
                if (empty($document_data['hubspot_file_url'])) {
                    $stats['skipped']++;
                    continue;
                }

                // Check if document exists
                $existing = $this->db->get_document_by_hubspot_url($document_data['hubspot_file_url']);

                if ($existing) {
                    // Update existing document
                    $this->db->update_document($existing->id, $document_data);
                    $stats['updated']++;
                } else {
                    // Create new document
                    $this->db->insert_document($document_data);
                    $stats['created']++;
                }

            } catch (Exception $e) {
                error_log('TCP Docs: Error processing row - ' . $e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Map column headers to indices
     */
    private function map_columns($headers) {
        $map = array();

        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim($header));

            // Exact matches first, then partial matches
            if ($normalized === 'title') {
                $map['title'] = $index;
            } elseif ($normalized === 'description') {
                $map['description'] = $index;
            } elseif ($normalized === 'category' || strpos($normalized, 'category') === 0) {
                // Matches "category" or "category (urethanes, epoxies, sealers etc)"
                $map['category'] = $index;
            } elseif ($normalized === 'brand') {
                $map['brand'] = $index;
            } elseif ($normalized === 'product system' || strpos($normalized, 'product system') === 0) {
                // Matches "product system"
                $map['product_system'] = $index;
            } elseif ($normalized === 'document type' || $normalized === 'type' || strpos($normalized, 'document type') === 0) {
                // Matches "document type", "type", or "document type (sds, tds, marketing)"
                $map['document_type'] = $index;
            } elseif ($normalized === 'hubspot file url' || $normalized === 'file url') {
                $map['hubspot_file_url'] = $index;
            } elseif ($normalized === 'hubspot file id' || $normalized === 'file id') {
                $map['hubspot_file_id'] = $index;
            } elseif ($normalized === 'file name' || $normalized === 'filename') {
                $map['file_name'] = $index;
            }
        }

        return $map;
    }

    /**
     * Parse a single row into document data
     */
    private function parse_row($row, $column_map) {
        $data = array(
            'title' => '',
            'description' => '',
            'category' => '',
            'brand' => '',
            'product_system' => '',
            'document_type' => '',
            'file_name' => '',
            'hubspot_file_url' => '',
            'hubspot_file_id' => ''
        );

        foreach ($column_map as $field => $index) {
            if (isset($row[$index])) {
                $data[$field] = trim($row[$index]);
            }
        }

        return $data;
    }

    /**
     * Get last sync time
     */
    public function get_last_sync_time() {
        return get_option('tcp_docs_last_sync', '');
    }

    /**
     * Test connection to Google Sheets
     */
    public function test_connection() {
        try {
            $sheet_names = $this->fetch_sheet_names();

            if (empty($sheet_names)) {
                return array(
                    'success' => false,
                    'message' => 'No sheets found in the spreadsheet'
                );
            }

            // Test fetching data from the first sheet
            $first_sheet = $sheet_names[0];
            $data = $this->fetch_sheet_data($first_sheet);

            return array(
                'success' => true,
                'message' => 'Successfully connected to Google Sheets. Found ' . count($sheet_names) . ' sheet(s): ' . implode(', ', $sheet_names),
                'sheets' => $sheet_names,
                'row_count' => count($data)
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}
