<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper Functions for Media Library Manager
 */

// Get formatted current datetime
function mlm_get_current_datetime() {
    return current_time('mysql');
}

// Get movie by ID
function mlm_get_movie($movie_id) {
    global $wpdb;
    $movie = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_movies WHERE id = %d",
        $movie_id
    ));

    if ($movie) {
        $movie->video_links = mlm_get_movie_links($movie_id);
    }

    return $movie;
}

// Get movie streaming links
function mlm_get_movie_links($movie_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_movie_links WHERE movie_id = %d AND status = 'active'",
        $movie_id
    ));
}

// Get series by ID
function mlm_get_series($series_id) {
    global $wpdb;
    $series = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_series WHERE id = %d",
        $series_id
    ));

    if ($series) {
        $series->episodes = mlm_get_series_episodes($series_id);
    }

    return $series;
}

// Get series episodes
function mlm_get_series_episodes($series_id) {
    global $wpdb;
    $episodes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_episodes WHERE series_id = %d ORDER BY season_number, episode_number",
        $series_id
    ));

    foreach ($episodes as &$episode) {
        $episode->video_links = mlm_get_episode_links($episode->id);
    }

    return $episodes;
}

// Get episode streaming links
function mlm_get_episode_links($episode_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_episode_links WHERE episode_id = %d AND status = 'active'",
        $episode_id
    ));
}

// Get channel by ID
function mlm_get_channel($channel_id) {
    global $wpdb;
    $channel = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_channels WHERE id = %d",
        $channel_id
    ));

    if ($channel) {
        $channel->video_links = mlm_get_channel_links($channel_id);
    }

    return $channel;
}

// Get channel streaming links
function mlm_get_channel_links($channel_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_channel_links WHERE channel_id = %d AND status = 'active'",
        $channel_id
    ));
}

// Get statistics
function mlm_get_statistics() {
    global $wpdb;
    
    return array(
        'movies' => array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_movies"),
            'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_movies WHERE status = 'active'"),
            'latest' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mlm_movies ORDER BY created_at DESC LIMIT 5")
        ),
        'series' => array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_series"),
            'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_series WHERE status = 'active'"),
            'latest' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mlm_series ORDER BY created_at DESC LIMIT 5")
        ),
        'episodes' => array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_episodes"),
            'latest' => $wpdb->get_results("SELECT e.*, s.title as series_title FROM {$wpdb->prefix}mlm_episodes e 
                                          JOIN {$wpdb->prefix}mlm_series s ON e.series_id = s.id 
                                          ORDER BY e.created_at DESC LIMIT 5")
        ),
        'channels' => array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_channels"),
            'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mlm_channels WHERE status = 'active'"),
            'latest' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mlm_channels ORDER BY created_at DESC LIMIT 5")
        )
    );
}

// Sanitize and validate video URL
function mlm_sanitize_video_url($url) {
    $url = esc_url_raw($url);
    // Add additional validation for specific video platforms if needed
    return $url;
}

// Get available video qualities
function mlm_get_video_qualities() {
    return array(
        '4K' => '4K (2160p)',
        '1080p' => 'Full HD (1080p)',
        '720p' => 'HD (720p)',
        '480p' => 'SD (480p)',
        '360p' => 'Low (360p)'
    );
}

// Get available genres
function mlm_get_genres() {
    return array(
        'action' => 'Action',
        'adventure' => 'Adventure',
        'comedy' => 'Comedy',
        'drama' => 'Drama',
        'horror' => 'Horror',
        'thriller' => 'Thriller',
        'sci-fi' => 'Science Fiction',
        'fantasy' => 'Fantasy',
        'romance' => 'Romance',
        'documentary' => 'Documentary',
        'animation' => 'Animation',
        'family' => 'Family'
    );
}

// Get available age ratings
function mlm_get_age_ratings() {
    return array(
        'G' => 'G (General Audience)',
        'PG' => 'PG (Parental Guidance)',
        'PG-13' => 'PG-13 (Parental Guidance for children under 13)',
        'R' => 'R (Restricted)',
        'NC-17' => 'NC-17 (Adults Only)'
    );
}

// Format duration
function mlm_format_duration($minutes) {
    if (!$minutes) return '';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return ($hours ? $hours . 'h ' : '') . ($mins ? $mins . 'm' : '');
}

// Get user activity log
function mlm_get_user_activity($user_id, $limit = 10) {
    global $wpdb;
    
    $activities = $wpdb->get_results($wpdb->prepare("
        (SELECT 'movie' as type, title, created_at, 'added' as action 
         FROM {$wpdb->prefix}mlm_movies 
         WHERE created_by = %d)
        UNION ALL
        (SELECT 'series' as type, title, created_at, 'added' as action 
         FROM {$wpdb->prefix}mlm_series 
         WHERE created_by = %d)
        UNION ALL
        (SELECT 'channel' as type, title, created_at, 'added' as action 
         FROM {$wpdb->prefix}mlm_channels 
         WHERE created_by = %d)
        ORDER BY created_at DESC
        LIMIT %d
    ", $user_id, $user_id, $user_id, $limit));

    return $activities;
}

// Check if URL exists and is accessible
function mlm_check_url_exists($url) {
    $response = wp_remote_head($url);
    return !is_wp_error($response) && $response['response']['code'] == 200;
}

// Generate thumbnail from video URL (if supported)
function mlm_generate_thumbnail_from_video($video_url) {
    // Example implementation for YouTube
    if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
        preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user|shorts)\/))([^\?&\"'>]+)/", $video_url, $matches);
        if (isset($matches[1])) {
            return 'https://img.youtube.com/vi/' . $matches[1] . '/maxresdefault.jpg';
        }
    }
    return '';
}

// Add debug logging
function mlm_log($message, $type = 'info') {
    if (WP_DEBUG === true) {
        $log_file = WP_CONTENT_DIR . '/mlm-debug.log';
        $timestamp = current_time('mysql');
        error_log("[$timestamp][$type] $message\n", 3, $log_file);
    }
}

// Clean up old data
function mlm_cleanup_old_data($days = 30) {
    global $wpdb;
    $date = date('Y-m-d H:i:s', strtotime("-$days days"));
    
    // Delete inactive items older than specified days
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}mlm_movies 
        WHERE status = 'inactive' AND updated_at < %s
    ", $date));
    
    // Similar queries for other tables...
}