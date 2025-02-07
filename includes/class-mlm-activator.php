<?php
/**
 * MLM Series Manager Activator
 *
 * @package MLM_Series_Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MLM_Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        self::create_tables();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Series table
        $series_table = $wpdb->prefix . 'mlm_series';
        $sql_series = "CREATE TABLE IF NOT EXISTS $series_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tmdb_id INT,
            title VARCHAR(255) NOT NULL,
            original_title VARCHAR(255),
            trailer_url VARCHAR(255),
            thumbnail VARCHAR(255),
            poster VARCHAR(255),
            icon VARCHAR(255),
            description TEXT,
            language VARCHAR(50),
            imdb_rating FLOAT,
            age_restriction VARCHAR(20),
            country VARCHAR(100),
            release_date DATE,
            genre VARCHAR(100),
            total_seasons INT DEFAULT 1,
            duration VARCHAR(50),
            views BIGINT DEFAULT 0,
            status VARCHAR(50),
            INDEX idx_tmdb_id (tmdb_id),
            INDEX idx_views (views)
        ) $charset_collate;";
        dbDelta($sql_series);

        // Episodes table
        $episodes_table = $wpdb->prefix . 'mlm_episodes';
        $sql_episodes = "CREATE TABLE IF NOT EXISTS $episodes_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            series_id INT NOT NULL,
            season_number INT NOT NULL,
            episode_number INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            thumbnail VARCHAR(255),
            duration VARCHAR(50),
            air_date DATE,
            video_links JSON,
            subtitle_links JSON,
            views BIGINT DEFAULT 0,
            status VARCHAR(50),
            INDEX idx_views (views),
            FOREIGN KEY (series_id) REFERENCES {$wpdb->prefix}mlm_series(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_episodes);

        // Movies table
        $movies_table = $wpdb->prefix . 'mlm_movies';
        $sql_movies = "CREATE TABLE IF NOT EXISTS $movies_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tmdb_id INT,
            title VARCHAR(255) NOT NULL,
            original_title VARCHAR(255),
            trailer_url VARCHAR(255),
            thumbnail VARCHAR(255),
            poster VARCHAR(255),
            icon VARCHAR(255),
            description TEXT,
            language VARCHAR(50),
            imdb_rating FLOAT,
            age_restriction VARCHAR(20),
            country VARCHAR(100),
            release_date DATE,
            genre VARCHAR(100),
            duration VARCHAR(50),
            video_links JSON,
            subtitle_links JSON,
            views BIGINT DEFAULT 0,
            status VARCHAR(50),
            INDEX idx_tmdb_id (tmdb_id),
            INDEX idx_views (views)
        ) $charset_collate;";
        dbDelta($sql_movies);

        // TV Channels table
        $channels_table = $wpdb->prefix . 'mlm_channels';
        $sql_channels = "CREATE TABLE IF NOT EXISTS $channels_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            logo VARCHAR(255),
            video_links JSON,
            category VARCHAR(50),
            language VARCHAR(50),
            country VARCHAR(100),
            views BIGINT DEFAULT 0,
            status VARCHAR(50),
            INDEX idx_views (views)
        ) $charset_collate;";
        dbDelta($sql_channels);

        // Links table for temporary streaming links
        $links_table = $wpdb->prefix . 'mlm_links';
        $sql_links = "CREATE TABLE IF NOT EXISTS $links_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content_id INT NOT NULL,
            type ENUM('movie', 'series') NOT NULL,
            stream_link TEXT NOT NULL,
            expiry_time DATETIME NOT NULL,
            status VARCHAR(50) DEFAULT 'active',
            INDEX idx_content (content_id, type),
            INDEX idx_expiry (expiry_time),
            INDEX idx_status (status)
        ) $charset_collate;";
        dbDelta($sql_links);
    }
}