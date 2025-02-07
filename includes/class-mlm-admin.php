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
        register_setting('mlm_options', 'mlm_settings', array(
            'type' => 'object',
            'default' => array(
                'tmdb_api_key' => '',
                'items_per_page' => 20,
                'default_language' => 'en',
                'enable_subtitle_links' => true,
            ),
        ));
    }

    public function add_menu_pages() {
        // Main Menu
        add_menu_page(
            __('Media Library Manager', 'media-library-manager'),
            __('Media Library', 'media-library-manager'),
            'manage_options',
            'mlm-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-video-alt3',
            30
        );

        // Movies submenu
        add_submenu_page(
            'mlm-dashboard',
            __('Movies', 'media-library-manager'),
            __('Movies', 'media-library-manager'),
            'manage_options',
            'mlm-movies',
            array($this, 'render_movies')
        );

        // Series submenu
        add_submenu_page(
            'mlm-dashboard',
            __('TV Series', 'media-library-manager'),
            __('TV Series', 'media-library-manager'),
            'manage_options',
            'mlm-series',
            array($this, 'render_series')
        );

        // Episodes submenu (hidden)
        add_submenu_page(
            null, // Hidden from menu
            __('Episodes', 'media-library-manager'),
            __('Episodes', 'media-library-manager'),
            'manage_options',
            'mlm-episodes',
            array($this, 'render_episodes')
        );

        // Channels submenu
        add_submenu_page(
            'mlm-dashboard',
            __('TV Channels', 'media-library-manager'),
            __('TV Channels', 'media-library-manager'),
            'manage_options',
            'mlm-channels',
            array($this, 'render_channels')
        );

        // TMDB Import submenu
        add_submenu_page(
            'mlm-dashboard',
            __('TMDB Import', 'media-library-manager'),
            __('Import from TMDB', 'media-library-manager'),
            'manage_options',
            'mlm-tmdb',
            array($this, 'render_tmdb_page')
        );

        // Statistics submenu
        add_submenu_page(
            'mlm-dashboard',
            __('Statistics', 'media-library-manager'),
            __('Statistics', 'media-library-manager'),
            'manage_options',
            'mlm-statistics',
            array($this, 'render_statistics')
        );

        // Settings submenu
        add_submenu_page(
            'mlm-dashboard',
            __('Settings', 'media-library-manager'),
            __('Settings', 'media-library-manager'),
            'manage_options',
            'mlm-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'mlm-') !== false) {
            // Styles
            wp_enqueue_style('mlm-admin', MLM_PLUGIN_URL . 'assets/css/admin.css', array(), MLM_VERSION);
            wp_enqueue_style('mlm-select2', MLM_PLUGIN_URL . 'assets/css/select2.min.css', array(), '4.1.0');
            wp_enqueue_style('mlm-admin-tmdb', MLM_PLUGIN_URL . 'assets/css/admin-tmdb.css', array(), MLM_VERSION);

            // Scripts
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('mlm-select2', MLM_PLUGIN_URL . 'assets/js/select2.min.js', array('jquery'), '4.1.0', true);
            wp_enqueue_script('mlm-admin', MLM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'mlm-select2'), MLM_VERSION, true);
            wp_enqueue_script('mlm-admin-tmdb', MLM_PLUGIN_URL . 'assets/js/admin-tmdb.js', array('jquery'), MLM_VERSION, true);

            // Localize script
            wp_localize_script('mlm-admin', 'mlm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mlm_nonce'),
                'placeholder_image' => MLM_PLUGIN_URL . 'assets/images/placeholder.png',
                'texts' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'media-library-manager'),
                    'error' => __('An error occurred', 'media-library-manager'),
                    'success' => __('Operation completed successfully', 'media-library-manager'),
                    'no_results' => __('No results found', 'media-library-manager'),
                    'loading' => __('Loading...', 'media-library-manager'),
                    'save_changes' => __('Save Changes', 'media-library-manager'),
                    'saving' => __('Saving...', 'media-library-manager')
                )
            ));
        }
    }

    // Render pages
    public function render_dashboard() {
        include MLM_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function render_movies() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'add' || $action === 'edit') {
            include MLM_PLUGIN_DIR . 'templates/admin/movie-editor.php';
        } else {
            include MLM_PLUGIN_DIR . 'templates/admin/movies.php';
        }
    }

    public function render_series() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'add' || $action === 'edit') {
            include MLM_PLUGIN_DIR . 'templates/admin/series-editor.php';
        } else {
            include MLM_PLUGIN_DIR . 'templates/admin/series.php';
        }
    }

    public function render_episodes() {
        if (!isset($_GET['series_id'])) {
            wp_redirect(admin_url('admin.php?page=mlm-series'));
            exit;
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'add' || $action === 'edit') {
            include MLM_PLUGIN_DIR . 'templates/admin/episode-editor.php';
        } else {
            include MLM_PLUGIN_DIR . 'templates/admin/episodes.php';
        }
    }

    public function render_channels() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'add' || $action === 'edit') {
            include MLM_PLUGIN_DIR . 'templates/admin/channel-editor.php';
        } else {
            include MLM_PLUGIN_DIR . 'templates/admin/channels.php';
        }
    }

    public function render_tmdb_page() {
        include MLM_PLUGIN_DIR . 'templates/admin/tmdb-search.php';
    }

    public function render_statistics() {
        include MLM_PLUGIN_DIR . 'templates/admin/statistics.php';
    }

    public function render_settings() {
        include MLM_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    private function get_pagination($total_items, $per_page = 20, $current_page = 1) {
        $total_pages = ceil($total_items / $per_page);
        
        return array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'offset' => ($current_page - 1) * $per_page
        );
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}