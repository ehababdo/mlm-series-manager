<?php
/**
 * TMDB API Integration
 *
 * @package MLM_Series_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MLM_TMDB_API
 * Handles all interactions with TheMovieDB API for both movies and TV series
 */
class MLM_TMDB_API {
    private static $instance = null;
    private $api_key;
    private $language;
    private $base_url       = 'https://api.themoviedb.org/3';
    private $image_base_url = 'https://image.tmdb.org/t/p/';

    /**
     * Constructor - Initialize API settings
     */
    private function __construct() {
        $options        = get_option('mlm_settings', array());
        $this->api_key  = isset($options['advanced']['tmdb_api_key']) ? $options['advanced']['tmdb_api_key'] : '';
        $this->language = isset($options['advanced']['tmdb_language']) ? $options['advanced']['tmdb_language'] : 'ar';
    }

    /**
     * Get singleton instance
     *
     * @return MLM_TMDB_API Instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Search for series or movies
     *
     * @param string $query Search query
     * @param string $type Type of search (movie or tv)
     * @param int $page Page number
     * @return object|false Search results
     */
    public function search($query, $type = 'tv', $page = 1) {
        $endpoint = "/search/" . ($type === 'movie' ? 'movie' : 'tv');
        $params   = array(
            'query' => $query,
            'page'  => $page,
        );

        return $this->make_request($endpoint, $params);
    }

    /**
     * Get item details (movie or series)
     *
     * @param int $item_id TMDB ID
     * @param string $type Item type (movie or tv)
     * @return object|false Item details
     */
    public function get_item_details($item_id, $type = 'tv') {
        $endpoint = "/{$type}/{$item_id}";
        $params   = array(
            'append_to_response' => 'credits,external_ids,images,videos,release_dates',
        );

        return $this->make_request($endpoint, $params);
    }

    /**
     * Get season details with episodes
     * 
     * @param int $series_id TMDB series ID
     * @param int $season_number Season number
     * @return object|false Season details with episodes
     */
    public function get_season_details($series_id, $season_number) {
        $endpoint = "/tv/{$series_id}/season/{$season_number}";
        $params = array(
            'append_to_response' => 'credits,images,videos'
        );
        
        return $this->make_request($endpoint, $params);
    }

    /**
     * Get episode details
     * 
     * @param int $series_id TMDB series ID
     * @param int $season_number Season number
     * @param int $episode_number Episode number
     * @return object|false Episode details
     */
    public function get_episode_details($series_id, $season_number, $episode_number) {
        $endpoint = "/tv/{$series_id}/season/{$season_number}/episode/{$episode_number}";
        $params = array(
            'append_to_response' => 'credits,images,videos'
        );
        
        return $this->make_request($endpoint, $params);
    }

    /**
     * Format episode data for database
     * 
     * @param object $episode_data TMDB episode data
     * @param int $series_id Local series ID
     * @return array Formatted episode data
     */
    public function format_episode_data($episode_data, $series_id) {
        if (!$episode_data) {
            return false;
        }

        return array(
            'series_id' => $series_id,
            'season_number' => $episode_data->season_number,
            'episode_number' => $episode_data->episode_number,
            'title' => $episode_data->name,
            'description' => $episode_data->overview,
            'thumbnail' => $this->get_image_url($episode_data->still_path, 'w300'),
            'air_date' => $episode_data->air_date,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        );
    }

    /**
     * Import season episodes
     * 
     * @param int $tmdb_series_id TMDB series ID
     * @param int $local_series_id Local database series ID
     * @param int $season_number Season number to import
     * @return array Result of import operation
     */
    public function import_season_episodes($tmdb_series_id, $local_series_id, $season_number) {
        $season = $this->get_season_details($tmdb_series_id, $season_number);
        
        if (!$season || empty($season->episodes)) {
            return array(
                'success' => false,
                'message' => 'No episodes found for this season'
            );
        }

        global $wpdb;
        $imported = 0;
        $errors = array();

        foreach ($season->episodes as $episode) {
            // Check if episode already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}mlm_episodes 
                 WHERE series_id = %d AND season_number = %d AND episode_number = %d",
                $local_series_id,
                $season_number,
                $episode->episode_number
            ));

            if ($exists) {
                continue; // Skip existing episodes
            }

            $episode_data = $this->format_episode_data($episode, $local_series_id);
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'mlm_episodes',
                $episode_data,
                array(
                    '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
                )
            );

            if ($result) {
                $imported++;
            } else {
                $errors[] = sprintf(
                    'Failed to import episode S%02dE%02d: %s',
                    $season_number,
                    $episode->episode_number,
                    $wpdb->last_error
                );
            }
        }

        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'message' => sprintf(
                'Imported %d episodes from season %d',
                $imported,
                $season_number
            )
        );
    }

    /**
     * Get all seasons for a series
     * 
     * @param int $series_id TMDB series ID
     * @return object|false Series seasons data
     */
    public function get_series_seasons($series_id) {
        $endpoint = "/tv/{$series_id}";
        $params = array(
            'append_to_response' => 'seasons'
        );
        
        $response = $this->make_request($endpoint, $params);
        return $response ? $response->seasons : false;
    }
    /**
     * Get video links for series/movie
     * 
     * @param int $id TMDB ID
     * @param string $type Type of content (tv or movie)
     * @return array Array of video links
     */
    public function get_videos($id, $type = 'tv') {
        $endpoint = "/{$type}/{$id}/videos";
        $response = $this->make_request($endpoint);
        
        if (!$response || empty($response->results)) {
            return array();
        }

        $videos = array();
        foreach ($response->results as $video) {
            if ($video->site === 'YouTube' && in_array($video->type, array('Trailer', 'Teaser'))) {
                $videos[] = array(
                    'key' => $video->key,
                    'name' => $video->name,
                    'type' => $video->type,
                    'url' => "https://www.youtube.com/watch?v={$video->key}"
                );
            }
        }

        return $videos;
    }

    /**
     * Get external IDs (IMDB, etc)
     * 
     * @param int $id TMDB ID
     * @param string $type Type of content (tv or movie)
     * @return object|false External IDs
     */
    public function get_external_ids($id, $type = 'tv') {
        $endpoint = "/{$type}/{$id}/external_ids";
        return $this->make_request($endpoint);
    }

    /**
     * Get content ratings
     * 
     * @param int $id TMDB ID
     * @return object|false Content ratings
     */
    public function get_content_ratings($id) {
        $endpoint = "/tv/{$id}/content_ratings";
        return $this->make_request($endpoint);
    }

    /**
     * Import full series with first season
     * 
     * @param int $tmdb_id TMDB series ID
     * @return array Import result
     */
    public function import_full_series($tmdb_id) {
        // Get series details
        $series_data = $this->get_item_details($tmdb_id, 'tv');
        if (!$series_data) {
            return array(
                'success' => false,
                'message' => 'Could not fetch series details'
            );
        }

        // Get additional data
        $videos = $this->get_videos($tmdb_id, 'tv');
        $external_ids = $this->get_external_ids($tmdb_id, 'tv');
        $content_ratings = $this->get_content_ratings($tmdb_id);

        // Merge all data
        $series_data->videos = $videos;
        $series_data->external_ids = $external_ids;
        $series_data->content_ratings = $content_ratings;

        // Format data for our database
        $formatted_data = $this->format_series_data($series_data);
        
        global $wpdb;
        
        // Check if series already exists
        $existing_series = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mlm_series WHERE tmdb_id = %d",
            $tmdb_id
        ));

        if ($existing_series) {
            // Update existing series
            $wpdb->update(
                $wpdb->prefix . 'mlm_series',
                $formatted_data,
                array('id' => $existing_series),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%d'),
                array('%d')
            );
            $series_id = $existing_series;
        } else {
            // Insert new series
            $wpdb->insert(
                $wpdb->prefix . 'mlm_series',
                $formatted_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%d')
            );
            $series_id = $wpdb->insert_id;
        }

        if (!$series_id) {
            return array(
                'success' => false,
                'message' => 'Error saving series to database'
            );
        }

        // Import first season if available
        if (!empty($series_data->seasons)) {
            $first_season = $series_data->seasons[0];
            $this->import_season_episodes($tmdb_id, $series_id, $first_season->season_number);
        }

        return array(
            'success' => true,
            'series_id' => $series_id,
            'message' => 'Series imported successfully'
        );
    }
    /**
     * Format item data for database
     *
     * @param object $tmdb_data TMDB data
     * @param string $type Item type (movie or tv)
     * @return array Formatted data
     */
    public function format_item_data($tmdb_data, $type = 'tv') {
        if (!$tmdb_data) {
            return false;
        }

        $common_data = array(
            'description' => $tmdb_data->overview,
            'poster'      => $this->get_image_url($tmdb_data->poster_path, 'original'),
            'thumbnail'   => $this->get_image_url($tmdb_data->backdrop_path, 'w780'),
            'icon'        => $this->get_image_url($tmdb_data->poster_path, 'w92'),
            'language'    => $this->language,
            'imdb_rating' => $tmdb_data->vote_average,
            'genre'       => $this->get_genres($tmdb_data->genres),
            'trailer_url' => $this->get_trailer_url($tmdb_data->videos->results ?? array()),
            'status'      => $type === 'movie' ?
            $this->format_movie_status($tmdb_data->status) :
            $this->format_series_status($tmdb_data->status),
            'created_at'  => current_time('mysql'),
            'created_by'  => get_current_user_id(),
        );

        if ($type === 'movie') {
            return array_merge($common_data, array(
                'title'           => $tmdb_data->title,
                'original_title'  => $tmdb_data->original_title,
                'release_date'    => $tmdb_data->release_date,
                'duration'        => $tmdb_data->runtime . ' min',
                'country'         => $this->get_production_country($tmdb_data->production_countries),
                'age_restriction' => $this->get_age_restriction($tmdb_data->release_dates->results ?? array()),
            ));
        } else {
            return array_merge($common_data, array(
                'title'          => $tmdb_data->name,
                'original_title' => $tmdb_data->original_name,
                'release_date'   => $tmdb_data->first_air_date,
                'country'        => $this->get_country($tmdb_data->origin_country),
                'total_seasons'  => $tmdb_data->number_of_seasons,
            ));
        }
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return object|false API response
     */
    private function make_request($endpoint, $params = array()) {
        if (empty($this->api_key)) {
            return false;
        }

        $params['api_key']  = $this->api_key;
        $params['language'] = $this->language;

        $url = $this->base_url . $endpoint . '?' . http_build_query($params);

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            $this->log_error('TMDB API Error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->status_code) && $data->status_code !== 200) {
            $this->log_error('TMDB API Error: ' . ($data->status_message ?? 'Unknown error'));
            return false;
        }

        return $data;
    }

    // Helper methods
    private function get_image_url($path, $size = 'original') {
        if (empty($path)) {
            return '';
        }
        return $this->image_base_url . $size . $path;
    }

    private function format_movie_status($status) {
        $status_map = array(
            'Released'        => 'active',
            'Post Production' => 'coming_soon',
            'In Production'   => 'coming_soon',
            'Planned'         => 'coming_soon',
            'Canceled'        => 'canceled',
        );
        return isset($status_map[$status]) ? $status_map[$status] : 'unknown';
    }

    private function format_series_status($status) {
        $status_map = array(
            'Returning Series' => 'active',
            'Ended'            => 'completed',
            'Canceled'         => 'canceled',
        );
        return isset($status_map[$status]) ? $status_map[$status] : 'unknown';
    }

    private function get_genres($genres) {
        if (empty($genres)) {
            return '';
        }
        return implode(', ', array_map(function ($genre) {
            return $genre->name;
        }, $genres));
    }

    private function get_country($countries) {
        if (empty($countries)) {
            return '';
        }
        return is_array($countries) ? $countries[0] : $countries;
    }

    private function get_production_country($countries) {
        if (empty($countries)) {
            return '';
        }
        return $countries[0]->name ?? '';
    }

    private function get_age_restriction($release_dates) {
        if (empty($release_dates)) {
            return '';
        }

        foreach ($release_dates as $release) {
            if ($release->iso_3166_1 === strtoupper(substr($this->language, 0, 2))) {
                foreach ($release->release_dates as $date) {
                    if (!empty($date->certification)) {
                        return $date->certification;
                    }
                }
            }
        }

        foreach ($release_dates as $release) {
            if ($release->iso_3166_1 === 'US') {
                foreach ($release->release_dates as $date) {
                    if (!empty($date->certification)) {
                        return $date->certification;
                    }
                }
            }
        }

        return '';
    }

    private function get_trailer_url($videos) {
        if (empty($videos)) {
            return '';
        }

        foreach ($videos as $video) {
            if ($video->type === 'Trailer' && $video->site === 'YouTube') {
                return 'https://www.youtube.com/watch?v=' . $video->key;
            }
        }

        if (!empty($videos[0]) && $videos[0]->site === 'YouTube') {
            return 'https://www.youtube.com/watch?v=' . $videos[0]->key;
        }

        return '';
    }

    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log($message);
        }
    }
}