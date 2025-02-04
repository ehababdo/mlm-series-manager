<?php
if (!defined('ABSPATH')) {
    exit;
}

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
    }

    // Movies methods
    public function add_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $data = $this->sanitize_movie_data($_POST);
        global $wpdb;

        $wpdb->insert(
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

    // Similar methods for edit_movie, delete_movie, get_movie...

    // Series methods
    public function add_series() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $data = $this->sanitize_series_data($_POST);
        global $wpdb;

        $wpdb->insert(
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
                'status' => $data['status'],
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );

        wp_send_json_success(array(
            'message' => 'Series added successfully',
            'series_id' => $wpdb->insert_id
        ));
    }
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


    public function handle_delete_channel() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_channel_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Get channel ID
        $channel_id = isset($_POST['channel_id']) ? intval($_POST['channel_id']) : 0;
        if (!$channel_id) {
            wp_send_json_error(array('message' => 'Invalid channel ID'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mlm_channels';

        // Delete the channel
        $result = $wpdb->delete(
            $table_name,
            array('id' => $channel_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }

        wp_send_json_success(array('message' => 'Channel deleted successfully'));
    }
    // Similar methods for edit_series, delete_series, get_series...

    // Helper methods
    private function sanitize_movie_data($data) {
        return array(
            'title' => sanitize_text_field($data['title']),
            'description' => wp_kses_post($data['description']),
            'trailer_url' => esc_url_raw($data['trailer_url']),
            'thumbnail' => esc_url_raw($data['thumbnail']),
            'poster' => esc_url_raw($data['poster']),
            'icon' => esc_url_raw($data['icon']),
            'language' => sanitize_text_field($data['language']),
            'imdb_rating' => floatval($data['imdb_rating']),
            'age_restriction' => sanitize_text_field($data['age_restriction']),
            'country' => sanitize_text_field($data['country']),
            'release_date' => sanitize_text_field($data['release_date']),
            'genre' => sanitize_text_field($data['genre']),
            'duration' => sanitize_text_field($data['duration']),
            'status' => sanitize_text_field($data['status']),
            'video_links' => isset($data['video_links']) ? $this->sanitize_video_links($data['video_links']) : array()
        );
    }

    private function sanitize_video_links($links) {
        $sanitized = array();
        foreach ($links as $link) {
            $sanitized[] = array(
                'url' => esc_url_raw($link['url']),
                'quality' => sanitize_text_field($link['quality']),
                'server' => sanitize_text_field($link['server'])
            );
        }
        return $sanitized;
    }
}