<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLM_Admin {
    private static $instance = null;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'init_admin'));
    }

    public function init_admin() {
        // Register settings
        register_setting('mlm_options', 'mlm_settings');
    }

    public function add_menu_pages() {
        // Main Menu
        add_menu_page(
            'Media Library Manager',
            'Media Library',
            'manage_options',
            'mlm-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-video-alt3',
            30
        );

        // Submenus
        add_submenu_page(
            'mlm-dashboard',
            'Movies',
            'Movies',
            'manage_options',
            'mlm-movies',
            array($this, 'render_movies')
        );

        add_submenu_page(
            'mlm-dashboard',
            'TV Series',
            'TV Series',
            'manage_options',
            'mlm-series',
            array($this, 'render_series')
        );

        add_submenu_page(
            'mlm-dashboard',
            'TV Channels',
            'TV Channels',
            'manage_options',
            'mlm-channels',
            array($this, 'render_channels')
        );

        add_submenu_page(
            'mlm-dashboard',
            'Statistics',
            'Statistics',
            'manage_options',
            'mlm-statistics',
            array($this, 'render_statistics')
        );

        add_submenu_page(
            'mlm-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'mlm-settings',
            array($this, 'render_settings')
        );

        // Add hidden submenu for episodes
        add_submenu_page(
            'mlm-dashboard',          // parent slug
            'Episodes',            // page title
            'Episodes',            // menu title
            'manage_options',      // capability
            'mlm-episodes',        // menu slug
            array($this, 'render_episodes_page')  // callback function
        );

    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'mlm-') !== false) {
            // Styles
            wp_enqueue_style('mlm-admin', MLM_PLUGIN_URL . 'assets/css/admin.css', array(), MLM_VERSION);
            wp_enqueue_style('mlm-select2', MLM_PLUGIN_URL . 'assets/css/select2.min.css', array(), '4.1.0');
            // jQuery UI
            wp_enqueue_script('jquery-ui-sortable');
            // Scripts
            wp_enqueue_media();
            wp_enqueue_script('mlm-select2', MLM_PLUGIN_URL . 'assets/js/select2.min.js', array('jquery'), '4.1.0', true);
            wp_enqueue_script('mlm-admin', MLM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'mlm-select2'), MLM_VERSION, true);

            // Localize script
            wp_localize_script('mlm-admin', 'mlm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mlm_nonce'),
                'current_user' => get_current_user_id(),
                'current_date' => current_time('mysql'),
                'texts' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'media-library-manager'),
                    'error' => __('An error occurred', 'media-library-manager'),
                    'success' => __('Operation completed successfully', 'media-library-manager')
                )
            ));
        }
    }
    public function render_episodes_page() {
        // Check if series_id is provided
        if (!isset($_GET['series_id'])) {
            wp_redirect(admin_url('admin.php?page=mlm-series'));
            exit;
        }
        require_once MLM_PLUGIN_DIR . 'templates/admin/episodes.php';
    }
    // Render pages
    public function render_dashboard() {
        include MLM_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function render_movies() {
        include MLM_PLUGIN_DIR . 'templates/admin/movies.php';
    }

    public function render_series() {
        include MLM_PLUGIN_DIR . 'templates/admin/series.php';
    }

    public function render_channels() {
        include MLM_PLUGIN_DIR . 'templates/admin/channels.php';
    }

    public function render_statistics() {
        include MLM_PLUGIN_DIR . 'templates/admin/statistics.php';
    }

    public function render_settings() {
        include MLM_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    private function get_pagination($total_items, $per_page = 10, $current_page = 1) {
        $total_pages = ceil($total_items / $per_page);
        
        return array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'offset' => ($current_page - 1) * $per_page
        );
    }
    // Singleton instance
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}