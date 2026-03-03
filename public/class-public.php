<?php
/**
 * Public Class
 * Handles frontend functionality and assets
 */

if (!defined('ABSPATH')) {
    exit;
}

class TCP_Public {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('tcp_docs_enqueue_assets', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only enqueue if shortcode is present or if action is called
        if (!$this->should_enqueue() && !doing_action('tcp_docs_enqueue_assets')) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'tcp-public-css',
            TCP_DOCS_PLUGIN_URL . 'public/css/style.css',
            array(),
            TCP_DOCS_VERSION
        );

        // Enqueue React and ReactDOM from CDN for simplicity
        // For production, you might want to bundle these
        wp_enqueue_script(
            'react',
            'https://unpkg.com/react@18/umd/react.production.min.js',
            array(),
            '18.2.0',
            true
        );

        wp_enqueue_script(
            'react-dom',
            'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',
            array('react'),
            '18.2.0',
            true
        );

        // Enqueue our app
        wp_enqueue_script(
            'tcp-public-js',
            TCP_DOCS_PLUGIN_URL . 'public/js/app.js',
            array('react', 'react-dom'),
            TCP_DOCS_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('tcp-public-js', 'tcpDocsData', array(
            'restUrl' => rest_url('tcp-docs/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => TCP_DOCS_PLUGIN_URL,
            'isLoggedIn' => is_user_logged_in(),
            'currentUser' => $this->get_current_user_data(),
            'strings' => array(
                'searchPlaceholder' => __('Search documents...', 'tcp-docs'),
                'noResults' => __('No documents found', 'tcp-docs'),
                'loading' => __('Loading...', 'tcp-docs'),
                'error' => __('Error loading documents', 'tcp-docs'),
                'allCategories' => __('All Categories', 'tcp-docs'),
                'allBrands' => __('All Brands', 'tcp-docs'),
                'allTypes' => __('All Types', 'tcp-docs'),
                'viewPdf' => __('View PDF', 'tcp-docs'),
            )
        ));
    }

    /**
     * Check if assets should be enqueued
     */
    private function should_enqueue() {
        global $post;

        if (!$post) {
            return false;
        }

        // Check if shortcode is present in content
        return has_shortcode($post->post_content, 'tcp_documents');
    }

    /**
     * Get current user data for frontend
     */
    private function get_current_user_data() {
        if (!is_user_logged_in()) {
            return null;
        }

        $user = wp_get_current_user();

        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'avatar' => get_avatar_url($user->ID),
            'isAdmin' => current_user_can('manage_options')
        );
    }
}
