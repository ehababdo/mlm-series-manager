<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once MLM_PLUGIN_DIR . 'includes/class-mlm-tmdb-api.php';

class MLM_Ajax {
    public function __construct() {
        // Movies
        add_action('wp_ajax_mlm_add_movie', array($this, 'add_movie'));
        add_action('wp_ajax_mlm_edit_movie', array($this, 'edit_movie'));
        add_action('wp_ajax_mlm_delete_movie', array($this, 'delete_movie'));
        add_action('wp_ajax_mlm_get_movie', array($this, 'get_movie'));

        // Series
        add_action('wp_ajax_mlm_add_series', array($this, 'add_series'));
        add_action('wp_ajax_mlm_edit_series', array($this, 'edit_series'));
        add_action('wp_ajax_mlm_delete_series', array($this, 'delete_series'));
        add_action('wp_ajax_mlm_get_series', array($this, 'get_series'));

        // Episodes
        add_action('wp_ajax_mlm_add_episode', array($this, 'add_episode'));
        add_action('wp_ajax_mlm_edit_episode', array($this, 'edit_episode'));
        add_action('wp_ajax_mlm_delete_episode', array($this, 'delete_episode'));
        add_action('wp_ajax_mlm_get_episode', array($this, 'get_episode'));

        // Channels
        add_action('wp_ajax_mlm_add_channel', array($this, 'add_channel'));
        add_action('wp_ajax_mlm_edit_channel', array($this, 'edit_channel'));
        add_action('wp_ajax_mlm_delete_channel', array($this, 'delete_channel'));
        add_action('wp_ajax_mlm_get_channel', array($this, 'get_channel'));
        
        add_action('wp_ajax_delete_channel', array($this, 'handle_delete_channel'));
        add_action('wp_ajax_get_episode_links', array($this, 'get_episode_links'));

        // TMDB Integration
        add_action('wp_ajax_mlm_search_tmdb', array($this, 'search_tmdb'));
        add_action('wp_ajax_mlm_import_from_tmdb', array($this, 'import_from_tmdb'));
        add_action('wp_ajax_mlm_import_season_episodes', array($this, 'import_season_episodes'));
    }

    /**
     * Search TMDB
     */
    public function search_tmdb() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $query = sanitize_text_field($_POST['query']);
        $type = sanitize_text_field($_POST['type'] ?? 'tv');
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        $tmdb_api = MLM_TMDB_API::get_instance();
        $results = $tmdb_api->search($query, $type, $page);
        
        if (!$results) {
            wp_send_json_error('No results found');
        }
        
        wp_send_json_success($results);
    }

    /**
     * Import from TMDB
     */
    public function import_from_tmdb() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $item_id = absint($_POST['tmdb_id']);
        $type = sanitize_text_field($_POST['type'] ?? 'tv');
        
        $tmdb_api = MLM_TMDB_API::get_instance();
        $item_info = $tmdb_api->get_item_details($item_id, $type);
        
        if (!$item_info) {
            wp_send_json_error('Could not fetch item information');
        }
        
        $formatted_data = $tmdb_api->format_item_data($item_info, $type);
        
        // Add to database using existing methods
        $_POST = array_merge($_POST, $formatted_data);
        if ($type === 'movie') {
            $this->add_movie();
        } else {
            $this->add_series();
        }
    }
    /**
     * Import Season Episodes from TMDB
     */
    public function import_season_episodes() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $tmdb_series_id = absint($_POST['tmdb_series_id']);
        $local_series_id = absint($_POST['series_id']);
        $season_number = absint($_POST['season_number']);

        if (!$tmdb_series_id || !$local_series_id || !$season_number) {
            wp_send_json_error('Invalid parameters provided');
        }

        $tmdb_api = MLM_TMDB_API::get_instance();
        $result = $tmdb_api->import_season_episodes($tmdb_series_id, $local_series_id, $season_number);

        if (!$result['success']) {
            wp_send_json_error($result['message']);
        }

        wp_send_json_success($result);
    }
    /**
     * Add Movie
     */
    public function add_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $data = $this->sanitize_movie_data($_POST);
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'mlm_movies',
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'trailer_url' => $data['trailer_url'],
                'thumbnail' => $data['thumbnail'],
                'poster' => $data['poster'],
                'icon' => $data['icon'],
                'language' => $data['language'],
                'imdb_rating' => $data['imdb_rating'],
                'age_restriction' => $data['age_restriction'],
                'country' => $data['country'],
                'release_date' => $data['release_date'],
                'genre' => $data['genre'],
                'duration' => $data['duration'],
                'status' => $data['status'],
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error adding movie: ' . $wpdb->last_error));
        }

        $movie_id = $wpdb->insert_id;

        // Add streaming links
        if (!empty($data['video_links'])) {
            foreach ($data['video_links'] as $link) {
                $wpdb->insert(
                    $wpdb->prefix . 'mlm_movie_links',
                    array(
                        'movie_id' => $movie_id,
                        'video_url' => $link['url'],
                        'quality' => $link['quality'],
                        'server_name' => $link['server'],
                        'status' => 'active',
                        'created_by' => get_current_user_id()
                    )
                );
            }
        }

        wp_send_json_success(array(
            'message' => 'Movie added successfully',
            'movie_id' => $movie_id
        ));
    }
/**
     * Edit Movie
     */
    public function edit_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $movie_id = absint($_POST['movie_id']);
        if (!$movie_id) {
            wp_send_json_error(array('message' => 'Invalid movie ID'));
        }

        $data = $this->sanitize_movie_data($_POST);
        global $wpdb;

        // Update movie data
        $result = $wpdb->update(
            $wpdb->prefix . 'mlm_movies',
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'trailer_url' => $data['trailer_url'],
                'thumbnail' => $data['thumbnail'],
                'poster' => $data['poster'],
                'icon' => $data['icon'],
                'language' => $data['language'],
                'imdb_rating' => $data['imdb_rating'],
                'age_restriction' => $data['age_restriction'],
                'country' => $data['country'],
                'release_date' => $data['release_date'],
                'genre' => $data['genre'],
                'duration' => $data['duration'],
                'status' => $data['status'],
                'updated_by' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $movie_id),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', 
                '%s', '%s', '%s', '%s', '%s', '%d', '%s'
            ),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error updating movie: ' . $wpdb->last_error));
        }

        // Handle video links
        if (isset($data['video_links'])) {
            // First, delete existing links
            $wpdb->delete(
                $wpdb->prefix . 'mlm_movie_links',
                array('movie_id' => $movie_id),
                array('%d')
            );

            // Then add new links
            foreach ($data['video_links'] as $link) {
                $wpdb->insert(
                    $wpdb->prefix . 'mlm_movie_links',
                    array(
                        'movie_id' => $movie_id,
                        'video_url' => $link['url'],
                        'quality' => $link['quality'],
                        'server_name' => $link['server'],
                        'status' => 'active',
                        'created_by' => get_current_user_id()
                    ),
                    array(
                        '%d', '%s', '%s', '%s', '%s', '%d'
                    )
                );
            }
        }

        wp_send_json_success(array(
            'message' => 'Movie updated successfully',
            'movie_id' => $movie_id
        ));
    }

    /**
     * Delete Movie
     */
    public function delete_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $movie_id = absint($_POST['movie_id']);
        if (!$movie_id) {
            wp_send_json_error(array('message' => 'Invalid movie ID'));
        }

        global $wpdb;

        // First, delete associated video links
        $wpdb->delete(
            $wpdb->prefix . 'mlm_movie_links',
            array('movie_id' => $movie_id),
            array('%d')
        );

        // Then delete the movie
        $result = $wpdb->delete(
            $wpdb->prefix . 'mlm_movies',
            array('id' => $movie_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error deleting movie: ' . $wpdb->last_error));
        }

        wp_send_json_success(array('message' => 'Movie deleted successfully'));
    }

    /**
     * Get Movie
     */
    public function get_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $movie_id = absint($_POST['movie_id']);
        if (!$movie_id) {
            wp_send_json_error(array('message' => 'Invalid movie ID'));
        }

        global $wpdb;

        // Get movie data
        $movie = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlm_movies WHERE id = %d",
            $movie_id
        ));

        if (!$movie) {
            wp_send_json_error(array('message' => 'Movie not found'));
        }

        // Get video links
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlm_movie_links 
             WHERE movie_id = %d AND status = 'active'
             ORDER BY CASE quality 
                WHEN '4K' THEN 1 
                WHEN '1080p' THEN 2 
                WHEN '720p' THEN 3 
                WHEN '480p' THEN 4 
                WHEN '360p' THEN 5 
                ELSE 6 
             END",
            $movie_id
        ));

        $movie->video_links = $links;

        wp_send_json_success(array(
            'movie' => $movie,
            'message' => 'Movie retrieved successfully'
        ));
    }
    /**
     * Add Series
     */
    public function add_series() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $data = $this->sanitize_series_data($_POST);
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'mlm_series',
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'trailer_url' => $data['trailer_url'],
                'thumbnail' => $data['thumbnail'],
                'poster' => $data['poster'],
                'icon' => $data['icon'],
                'language' => $data['language'],
                'imdb_rating' => $data['imdb_rating'],
                'age_restriction' => $data['age_restriction'],
                'country' => $data['country'],
                'release_date' => $data['release_date'],
                'genre' => $data['genre'],
                'total_seasons' => $data['total_seasons'],
                'status' => $data['status'],
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error adding series: ' . $wpdb->last_error));
        }

        wp_send_json_success(array(
            'message' => 'Series added successfully',
            'series_id' => $wpdb->insert_id
        ));
    }
    /**
     * Edit Series
     */
    public function edit_series() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $series_id = absint($_POST['series_id']);
        if (!$series_id) {
            wp_send_json_error(array('message' => 'Invalid series ID'));
        }

        $data = $this->sanitize_series_data($_POST);
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'mlm_series',
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'trailer_url' => $data['trailer_url'],
                'thumbnail' => $data['thumbnail'],
                'poster' => $data['poster'],
                'icon' => $data['icon'],
                'language' => $data['language'],
                'imdb_rating' => $data['imdb_rating'],
                'age_restriction' => $data['age_restriction'],
                'country' => $data['country'],
                'release_date' => $data['release_date'],
                'genre' => $data['genre'],
                'total_seasons' => $data['total_seasons'],
                'status' => $data['status'],
                'updated_by' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $series_id),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s',
                '%s', '%s', '%s', '%d', '%s', '%d', '%s'
            ),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error updating series: ' . $wpdb->last_error));
        }

        wp_send_json_success(array(
            'message' => 'Series updated successfully',
            'series_id' => $series_id
        ));
    }

    /**
     * Delete Series
     */
    public function delete_series() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $series_id = absint($_POST['series_id']);
        if (!$series_id) {
            wp_send_json_error(array('message' => 'Invalid series ID'));
        }

        global $wpdb;

        // First, delete all episode links
        $wpdb->query($wpdb->prepare(
            "DELETE el FROM {$wpdb->prefix}mlm_episode_links el
             INNER JOIN {$wpdb->prefix}mlm_episodes e ON el.episode_id = e.id
             WHERE e.series_id = %d",
            $series_id
        ));

        // Then delete all episodes
        $wpdb->delete(
            $wpdb->prefix . 'mlm_episodes',
            array('series_id' => $series_id),
            array('%d')
        );

        // Finally delete the series
        $result = $wpdb->delete(
            $wpdb->prefix . 'mlm_series',
            array('id' => $series_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Error deleting series: ' . $wpdb->last_error));
        }

        wp_send_json_success(array('message' => 'Series deleted successfully'));
    }

    /**
     * Get Series
     */
    public function get_series() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $series_id = absint($_POST['series_id']);
        if (!$series_id) {
            wp_send_json_error(array('message' => 'Invalid series ID'));
        }

        global $wpdb;

        // Get series data
        $series = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlm_series WHERE id = %d",
            $series_id
        ));

        if (!$series) {
            wp_send_json_error(array('message' => 'Series not found'));
        }

        // Get episodes count by season
        $episodes = $wpdb->get_results($wpdb->prepare(
            "SELECT season_number, COUNT(*) as episode_count 
             FROM {$wpdb->prefix}mlm_episodes 
             WHERE series_id = %d 
             GROUP BY season_number 
             ORDER BY season_number",
            $series_id
        ));

        $series->episodes_by_season = $episodes;

        wp_send_json_success(array(
            'series' => $series,
            'message' => 'Series retrieved successfully'
        ));
    }
    /**
     * Get episode links
     */
    public function get_episode_links() {
        check_ajax_referer('get_episode_links_nonce', 'nonce');

        $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
        if (!$episode_id) {
            wp_send_json_error(array('message' => 'Invalid episode ID'));
        }

        global $wpdb;
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlm_episode_links 
             WHERE episode_id = %d AND status = 'active'
             ORDER BY CASE quality 
                WHEN '4K' THEN 1 
                WHEN '1080p' THEN 2 
                WHEN '720p' THEN 3 
                WHEN '480p' THEN 4 
                WHEN '360p' THEN 5 
                ELSE 6 
             END",
            $episode_id
        ));

        if (empty($links)) {
            wp_send_json_error(array('message' => 'No links found'));
        }

        wp_send_json_success(array(
            'links' => $links,
            'message' => 'Links retrieved successfully'
        ));
    }

    /**
     * Handle channel deletion
     */
    public function handle_delete_channel() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_channel_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $channel_id = isset($_POST['channel_id']) ? intval($_POST['channel_id']) : 0;
        if (!$channel_id) {
            wp_send_json_error(array('message' => 'Invalid channel ID'));
        }

        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'mlm_channels',
            array('id' => $channel_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }

        wp_send_json_success(array('message' => 'Channel deleted successfully'));
    }

    /**
     * Sanitize series data
     */
    private function sanitize_series_data($data) {
        return array(
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => wp_kses_post($data['description'] ?? ''),
            'trailer_url' => esc_url_raw($data['trailer_url'] ?? ''),
            'thumbnail' => esc_url_raw($data['thumbnail'] ?? ''),
            'poster' => esc_url_raw($data['poster'] ?? ''),
            'icon' => esc_url_raw($data['icon'] ?? ''),
            'language' => sanitize_text_field($data['language'] ?? ''),
            'imdb_rating' => floatval($data['imdb_rating'] ?? 0),
            'age_restriction' => sanitize_text_field($data['age_restriction'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? ''),
            'release_date' => sanitize_text_field($data['release_date'] ?? ''),
            'genre' => sanitize_text_field($data['genre'] ?? ''),
            'total_seasons' => absint($data['total_seasons'] ?? 1),
            'status' => sanitize_text_field($data['status'] ?? 'active')
        );
    }

    /**
     * Sanitize movie data
     */
    private function sanitize_movie_data($data) {
        return array(
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => wp_kses_post($data['description'] ?? ''),
            'trailer_url' => esc_url_raw($data['trailer_url'] ?? ''),
            'thumbnail' => esc_url_raw($data['thumbnail'] ?? ''),
            'poster' => esc_url_raw($data['poster'] ?? ''),
            'icon' => esc_url_raw($data['icon'] ?? ''),
            'language' => sanitize_text_field($data['language'] ?? ''),
            'imdb_rating' => floatval($data['imdb_rating'] ?? 0),
            'age_restriction' => sanitize_text_field($data['age_restriction'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? ''),
            'release_date' => sanitize_text_field($data['release_date'] ?? ''),
            'genre' => sanitize_text_field($data['genre'] ?? ''),
            'duration' => sanitize_text_field($data['duration'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'video_links' => isset($data['video_links']) ? $this->sanitize_video_links($data['video_links']) : array()
        );
    }

    /**
     * Sanitize video links
     */
    private function sanitize_video_links($links) {
        if (!is_array($links)) {
            return array();
        }

        $sanitized = array();
        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }
            
            $sanitized[] = array(
                'url' => esc_url_raw($link['url'] ?? ''),
                'quality' => sanitize_text_field($link['quality'] ?? ''),
                'server' => sanitize_text_field($link['server'] ?? '')
            );
        }
        return $sanitized;
    }
}