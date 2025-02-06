<?php
/*
Plugin Name: Media Library Manager
Plugin URI: 
Description: A comprehensive media library for managing movies, series, videos, and live TV channels
Version: 1.0
Author: ehababdo
License: GPL v2 or later
Text Domain: media-library-manager
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MLM_VERSION', '1.0');
define('MLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MLM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'mlm_activate');

function mlm_activate() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Series table
    // Series table
    $series_table = $wpdb->prefix . 'mlm_series';
    $sql_series = "CREATE TABLE IF NOT EXISTS $series_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
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
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT,
        updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
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
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT,
        updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT,
        FOREIGN KEY (series_id) REFERENCES {$wpdb->prefix}mlm_series(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    dbDelta($sql_episodes);

    // Episode streaming links table
    $episode_links_table = $wpdb->prefix . 'mlm_episode_links';
    $sql_episode_links = "CREATE TABLE IF NOT EXISTS $episode_links_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        episode_id INT NOT NULL,
        video_url VARCHAR(255) NOT NULL,
        quality VARCHAR(20),
        server_name VARCHAR(100),
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT,
        updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT,
        FOREIGN KEY (episode_id) REFERENCES {$wpdb->prefix}mlm_episodes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    dbDelta($sql_episode_links);

    // Movies table
    $movies_table = $wpdb->prefix . 'mlm_movies';
    $sql_movies = "CREATE TABLE IF NOT EXISTS $movies_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
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
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT,
        updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    dbDelta($sql_movies);

    // Movie streaming links table
    $movie_links_table = $wpdb->prefix . 'mlm_movie_links';
    $sql_movie_links = "CREATE TABLE IF NOT EXISTS $movie_links_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        movie_id INT NOT NULL,
        video_url VARCHAR(255) NOT NULL,
        quality VARCHAR(20),
        server_name VARCHAR(100),
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT,
        updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT,
        FOREIGN KEY (movie_id) REFERENCES {$wpdb->prefix}mlm_movies(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    dbDelta($sql_movie_links);

    // TV Channels table
    $channels_table = $wpdb->prefix . 'mlm_channels';
    $sql_channels = "CREATE TABLE IF NOT EXISTS $channels_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        logo VARCHAR(255),
        stream_url VARCHAR(255),
        backup_stream_url VARCHAR(255),
        category VARCHAR(50),
        language VARCHAR(50),
        country VARCHAR(100),
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT,
        updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT
    ) $charset_collate;";

    dbDelta($sql_channels);
}

// Include required files
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-admin.php';
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-ajax.php';
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-api.php';
require_once MLM_PLUGIN_DIR . 'includes/functions.php';



// Initialize the plugin
function mlm_init() {
    // Initialize admin
    new MLM_Admin();
    
    // Initialize AJAX handler
    new MLM_Ajax();
    
    // Initialize REST API
    new MLM_API();
}
add_action('plugins_loaded', 'mlm_init');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'mlm_activate');
register_deactivation_hook(__FILE__, 'mlm_deactivate');

function mlm_deactivate() {
    // Cleanup tasks if needed
}