<?php
/*
Plugin Name: Media Library Manager
Plugin URI: 
Description: A comprehensive media library for managing movies, series, videos, and live TV channels
Version: 2.0.0
Author: ehababdo
License: GPL v2 or later
Text Domain: media-library-manager
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MLM_VERSION', '2.0.0');
define('MLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MLM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-activator.php';
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-admin.php';
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-ajax.php';
require_once MLM_PLUGIN_DIR . 'includes/class-mlm-api.php';
require_once MLM_PLUGIN_DIR . 'includes/functions.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('MLM_Activator', 'activate'));
register_deactivation_hook(__FILE__, 'mlm_deactivate');

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

function mlm_deactivate() {
    // Cleanup tasks if needed
}