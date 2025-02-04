<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLM_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('mlm/v1', '/movies', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_movies'),
                'permission_callback' => array($this, 'get_items_permissions_check')
            )
        ));

        register_rest_route('mlm/v1', '/movies/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_movie'),
                'permission_callback' => array($this, 'get_items_permissions_check')
            )
        ));

        register_rest_route('mlm/v1', '/series', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_series_list'),
                'permission_callback' => array($this, 'get_items_permissions_check')
            )
        ));

        register_rest_route('mlm/v1', '/series/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_series'),
                'permission_callback' => array($this, 'get_items_permissions_check')
            )
        ));

        register_rest_route('mlm/v1', '/channels', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_channels'),
                'permission_callback' => array($this, 'get_items_permissions_check')
            )
        ));
    }

    public function get_items_permissions_check($request) {
        return true; // Public access
    }

    public function get_movies($request) {
        global $wpdb;
        $movies = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}mlm_movies WHERE status = 'active' ORDER BY created_at DESC"
        );

        foreach ($movies as &$movie) {
            $movie->video_links = $this->get_movie_links($movie->id);
        }

        return rest_ensure_response($movies);
    }

    public function get_movie($request) {
        global $wpdb;
        $movie_id = $request['id'];
        
        $movie = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlm_movies WHERE id = %d AND status = 'active'",
            $movie_id
        ));

        if (!$movie) {
            return new WP_Error('not_found', 'Movie not found', array('status' => 404));
        }

        $movie->video_links = $this->get_movie_links($movie_id);
        return rest_ensure_response($movie);
    }

    private function get_movie_links($movie_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlm_movie_links WHERE movie_id = %d AND status = 'active'",
            $movie_id
        ));
    }

    // Similar methods for series and channels...
}