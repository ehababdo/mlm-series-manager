<?php
/**
 * MLM Ajax Handler
 *
 * @package MLM_Series_Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MLM_Ajax {
    private static $instance = null;

    public function __construct() {
        // Movies Actions
        add_action('wp_ajax_mlm_add_movie', array($this, 'add_movie'));
        add_action('wp_ajax_mlm_update_movie', array($this, 'update_movie'));
        add_action('wp_ajax_mlm_delete_movie', array($this, 'delete_movie'));
        add_action('wp_ajax_mlm_get_movie', array($this, 'get_movie'));
        add_action('wp_ajax_mlm_import_movie_tmdb', array($this, 'import_movie_tmdb'));

        // Series Actions
        add_action('wp_ajax_mlm_add_series', array($this, 'add_series'));
        add_action('wp_ajax_mlm_update_series', array($this, 'update_series'));
        add_action('wp_ajax_mlm_delete_series', array($this, 'delete_series'));
        add_action('wp_ajax_mlm_get_series', array($this, 'get_series'));
        add_action('wp_ajax_mlm_import_series_tmdb', array($this, 'import_series_tmdb'));

        // Episodes Actions
        add_action('wp_ajax_mlm_add_episode', array($this, 'add_episode'));
        add_action('wp_ajax_mlm_update_episode', array($this, 'update_episode'));
        add_action('wp_ajax_mlm_delete_episode', array($this, 'delete_episode'));
        add_action('wp_ajax_mlm_get_episode', array($this, 'get_episode'));
        add_action('wp_ajax_mlm_import_episode_tmdb', array($this, 'import_episode_tmdb'));

        // Channels Actions
        add_action('wp_ajax_mlm_add_channel', array($this, 'add_channel'));
        add_action('wp_ajax_mlm_update_channel', array($this, 'update_channel'));
        add_action('wp_ajax_mlm_delete_channel', array($this, 'delete_channel'));
        add_action('wp_ajax_mlm_get_channel', array($this, 'get_channel'));

        // Links Actions
        add_action('wp_ajax_mlm_add_link', array($this, 'add_link'));
        add_action('wp_ajax_mlm_update_link', array($this, 'update_link'));
        add_action('wp_ajax_mlm_delete_link', array($this, 'delete_link'));
    }

    /**
     * Movies Methods
     */
    public function add_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;

        $data = array(
            'tmdb_id' => isset($_POST['tmdb_id']) ? intval($_POST['tmdb_id']) : null,
            'title' => sanitize_text_field($_POST['title']),
            'original_title' => sanitize_text_field($_POST['original_title']),
            'trailer_url' => esc_url_raw($_POST['trailer_url']),
            'thumbnail' => esc_url_raw($_POST['thumbnail']),
            'poster' => esc_url_raw($_POST['poster']),
            'icon' => esc_url_raw($_POST['icon']),
            'description' => wp_kses_post($_POST['description']),
            'language' => sanitize_text_field($_POST['language']),
            'imdb_rating' => floatval($_POST['imdb_rating']),
            'age_restriction' => sanitize_text_field($_POST['age_restriction']),
            'country' => sanitize_text_field($_POST['country']),
            'release_date' => sanitize_text_field($_POST['release_date']),
            'genre' => sanitize_text_field($_POST['genre']),
            'duration' => sanitize_text_field($_POST['duration']),
            'video_links' => json_encode(array_map('esc_url_raw', $_POST['video_links'])),
            'subtitle_links' => json_encode(array_map('esc_url_raw', $_POST['subtitle_links'])),
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $wpdb->insert($wpdb->prefix . 'mlm_movies', $data);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add movie'));
        }

        wp_send_json_success(array(
            'message' => 'Movie added successfully',
            'movie_id' => $wpdb->insert_id
        ));
    }

    public function update_movie() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;

        $movie_id = intval($_POST['id']);
        $data = array(
            'tmdb_id' => isset($_POST['tmdb_id']) ? intval($_POST['tmdb_id']) : null,
            'title' => sanitize_text_field($_POST['title']),
            'original_title' => sanitize_text_field($_POST['original_title']),
            'trailer_url' => esc_url_raw($_POST['trailer_url']),
            'thumbnail' => esc_url_raw($_POST['thumbnail']),
            'poster' => esc_url_raw($_POST['poster']),
            'icon' => esc_url_raw($_POST['icon']),
            'description' => wp_kses_post($_POST['description']),
            'language' => sanitize_text_field($_POST['language']),
            'imdb_rating' => floatval($_POST['imdb_rating']),
            'age_restriction' => sanitize_text_field($_POST['age_restriction']),
            'country' => sanitize_text_field($_POST['country']),
            'release_date' => sanitize_text_field($_POST['release_date']),
            'genre' => sanitize_text_field($_POST['genre']),
            'duration' => sanitize_text_field($_POST['duration']),
            'video_links' => json_encode(array_map('esc_url_raw', $_POST['video_links'])),
            'subtitle_links' => json_encode(array_map('esc_url_raw', $_POST['subtitle_links'])),
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $wpdb->update(
            $wpdb->prefix . 'mlm_movies',
            $data,
            array('id' => $movie_id)
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to update movie'));
        }

        wp_send_json_success(array('message' => 'Movie updated successfully'));
    }

    /**
     * Series Methods
     */
    public function add_series() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;

        $data = array(
            'tmdb_id' => isset($_POST['tmdb_id']) ? intval($_POST['tmdb_id']) : null,
            'title' => sanitize_text_field($_POST['title']),
            'original_title' => sanitize_text_field($_POST['original_title']),
            'trailer_url' => esc_url_raw($_POST['trailer_url']),
            'thumbnail' => esc_url_raw($_POST['thumbnail']),
            'poster' => esc_url_raw($_POST['poster']),
            'icon' => esc_url_raw($_POST['icon']),
            'description' => wp_kses_post($_POST['description']),
            'language' => sanitize_text_field($_POST['language']),
            'imdb_rating' => floatval($_POST['imdb_rating']),
            'age_restriction' => sanitize_text_field($_POST['age_restriction']),
            'country' => sanitize_text_field($_POST['country']),
            'release_date' => sanitize_text_field($_POST['release_date']),
            'genre' => sanitize_text_field($_POST['genre']),
            'total_seasons' => intval($_POST['total_seasons']),
            'duration' => sanitize_text_field($_POST['duration']),
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $wpdb->insert($wpdb->prefix . 'mlm_series', $data);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add series'));
        }

        wp_send_json_success(array(
            'message' => 'Series added successfully',
            'series_id' => $wpdb->insert_id
        ));
    }

    /**
     * Episodes Methods
     */
    public function add_episode() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;

        $data = array(
            'series_id' => intval($_POST['series_id']),
            'season_number' => intval($_POST['season_number']),
            'episode_number' => intval($_POST['episode_number']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'thumbnail' => esc_url_raw($_POST['thumbnail']),
            'duration' => sanitize_text_field($_POST['duration']),
            'air_date' => sanitize_text_field($_POST['air_date']),
            'video_links' => json_encode(array_map('esc_url_raw', $_POST['video_links'])),
            'subtitle_links' => json_encode(array_map('esc_url_raw', $_POST['subtitle_links'])),
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $wpdb->insert($wpdb->prefix . 'mlm_episodes', $data);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add episode'));
        }

        wp_send_json_success(array(
            'message' => 'Episode added successfully',
            'episode_id' => $wpdb->insert_id
        ));
    }

    /**
     * Channels Methods
     */
    public function add_channel() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;

        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'logo' => esc_url_raw($_POST['logo']),
            'video_links' => json_encode(array_map('esc_url_raw', $_POST['video_links'])),
            'category' => sanitize_text_field($_POST['category']),
            'language' => sanitize_text_field($_POST['language']),
            'country' => sanitize_text_field($_POST['country']),
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $wpdb->insert($wpdb->prefix . 'mlm_channels', $data);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add channel'));
        }

        wp_send_json_success(array(
            'message' => 'Channel added successfully',
            'channel_id' => $wpdb->insert_id
        ));
    }

    /**
     * Links Methods
     */
    public function add_link() {
        check_ajax_referer('mlm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;

        $data = array(
            'content_id' => intval($_POST['content_id']),
            'type' => sanitize_text_field($_POST['type']),
            'stream_link' => esc_url_raw($_POST['stream_link']),
            'expiry_time' => gmdate('Y-m-d H:i:s', strtotime('+12 hours')),
            'status' => 'active'
        );

        $result = $wpdb->insert($wpdb->prefix . 'mlm_links', $data);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add link'));
        }

        wp_send_json_success(array(
            'message' => 'Link added successfully',
            'link_id' => $wpdb->insert_id
        ));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}