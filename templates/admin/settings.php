<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-04 02:26:41';
$current_user = 'ehababdo';

// Get current options
$options = get_option('mlm_settings', array());
$default_options = array(
    'general' => array(
        'default_language' => 'English',
        'items_per_page' => 12,
        'default_status' => 'active',
        'auto_publish' => 'no'
    ),
    'player' => array(
        'default_player' => 'html5',
        'autoplay' => 'no',
        'quality_selector' => 'yes',
        'thumbnail_position' => 'center'
    ),
    'display' => array(
        'show_views' => 'yes',
        'show_ratings' => 'yes',
        'show_duration' => 'yes',
        'show_description' => 'yes'
    ),
    'social' => array(
        'enable_sharing' => 'yes',
        'facebook_app_id' => '',
        'twitter_handle' => ''
    ),
    'advanced' => array(
        'cache_duration' => '3600',
        'api_key' => '',
        'custom_css' => '',
        'custom_js' => '',
        'tmdb_api_key' => '', 
        'tmdb_language' => 'en'

    )
);

// Merge with defaults
$options = wp_parse_args($options, $default_options);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mlm_settings_nonce'])) {
    if (!wp_verify_nonce($_POST['mlm_settings_nonce'], 'mlm_save_settings')) {
        wp_die('Invalid nonce specified');
    }

    // General Settings
    $options['general']['default_language'] = sanitize_text_field($_POST['default_language']);
    $options['general']['items_per_page'] = absint($_POST['items_per_page']);
    $options['general']['default_status'] = sanitize_text_field($_POST['default_status']);
    $options['general']['auto_publish'] = sanitize_text_field($_POST['auto_publish']);

    // Player Settings
    $options['player']['default_player'] = sanitize_text_field($_POST['default_player']);
    $options['player']['autoplay'] = sanitize_text_field($_POST['autoplay']);
    $options['player']['quality_selector'] = sanitize_text_field($_POST['quality_selector']);
    $options['player']['thumbnail_position'] = sanitize_text_field($_POST['thumbnail_position']);

    // Display Settings
    $options['display']['show_views'] = sanitize_text_field($_POST['show_views']);
    $options['display']['show_ratings'] = sanitize_text_field($_POST['show_ratings']);
    $options['display']['show_duration'] = sanitize_text_field($_POST['show_duration']);
    $options['display']['show_description'] = sanitize_text_field($_POST['show_description']);

    // Social Settings
    $options['social']['enable_sharing'] = sanitize_text_field($_POST['enable_sharing']);
    $options['social']['facebook_app_id'] = sanitize_text_field($_POST['facebook_app_id']);
    $options['social']['twitter_handle'] = sanitize_text_field($_POST['twitter_handle']);

    // Advanced Settings
    $options['advanced']['cache_duration'] = sanitize_text_field($_POST['cache_duration']);
    $options['advanced']['api_key'] = sanitize_text_field($_POST['api_key']);
    $options['advanced']['custom_css'] = wp_kses_post($_POST['custom_css']);
    $options['advanced']['custom_js'] = wp_kses_post($_POST['custom_js']);
    $options['advanced']['tmdb_api_key'] = sanitize_text_field($_POST['tmdb_api_key']);
    $options['advanced']['tmdb_language'] = sanitize_text_field($_POST['tmdb_language']);

    // Save options
    update_option('mlm_settings', $options);

    // Add success message
    add_settings_error(
        'mlm_settings',
        'settings_updated',
        'Settings saved successfully.',
        'updated'
    );
}

// Get available languages
$languages = array('English', 'Arabic', 'Spanish', 'French', 'German', 'Italian', 'Japanese', 'Korean', 'Chinese');

// Get available players
$players = array(
    'html5' => 'HTML5 Player',
    'plyr' => 'Plyr Player',
    'videojs' => 'Video.js Player',
    'jwplayer' => 'JW Player'
);
?>

<div class="wrap mlm-settings">
    <h1>MLM Settings</h1>

    <?php settings_errors('mlm_settings'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('mlm_save_settings', 'mlm_settings_nonce'); ?>

        <nav class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active">General</a>
            <a href="#player" class="nav-tab">Player</a>
            <a href="#display" class="nav-tab">Display</a>
            <a href="#social" class="nav-tab">Social</a>
            <a href="#advanced" class="nav-tab">Advanced</a>
        </nav>

        <div class="tab-content">
            <!-- General Settings -->
            <div id="general" class="tab-pane active">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="default_language">Default Language</label></th>
                        <td>
                            <select id="default_language" name="default_language">
                                <?php foreach ($languages as $language): ?>
                                    <option value="<?php echo esc_attr($language); ?>" 
                                            <?php selected($options['general']['default_language'], $language); ?>>
                                        <?php echo esc_html($language); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="items_per_page">Items Per Page</label></th>
                        <td>
                            <input type="number" id="items_per_page" name="items_per_page" 
                                   value="<?php echo esc_attr($options['general']['items_per_page']); ?>" 
                                   min="1" max="100">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="default_status">Default Status</label></th>
                        <td>
                            <select id="default_status" name="default_status">
                                <option value="active" <?php selected($options['general']['default_status'], 'active'); ?>>
                                    Active
                                </option>
                                <option value="inactive" <?php selected($options['general']['default_status'], 'inactive'); ?>>
                                    Inactive
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="auto_publish">Auto Publish</label></th>
                        <td>
                            <select id="auto_publish" name="auto_publish">
                                <option value="yes" <?php selected($options['general']['auto_publish'], 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected($options['general']['auto_publish'], 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Player Settings -->
            <div id="player" class="tab-pane">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="default_player">Default Player</label></th>
                        <td>
                            <select id="default_player" name="default_player">
                                <?php foreach ($players as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" 
                                            <?php selected($options['player']['default_player'], $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="autoplay">Autoplay</label></th>
                        <td>
                            <select id="autoplay" name="autoplay">
                                <option value="yes" <?php selected($options['player']['autoplay'], 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected($options['player']['autoplay'], 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quality_selector">Quality Selector</label></th>
                        <td>
                            <select id="quality_selector" name="quality_selector">
                                <option value="yes" <?php selected($options['player']['quality_selector'], 'yes'); ?>>
                                    Show
                                </option>
                                <option value="no" <?php selected($options['player']['quality_selector'], 'no'); ?>>
                                    Hide
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="thumbnail_position">Thumbnail Position</label></th>
                        <td>
                            <select id="thumbnail_position" name="thumbnail_position">
                                <option value="left" <?php selected($options['player']['thumbnail_position'], 'left'); ?>>
                                    Left
                                </option>
                                <option value="center" <?php selected($options['player']['thumbnail_position'], 'center'); ?>>
                                    Center
                                </option>
                                <option value="right" <?php selected($options['player']['thumbnail_position'], 'right'); ?>>
                                    Right
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Display Settings -->
            <div id="display" class="tab-pane">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="show_views">Show Views</label></th>
                        <td>
                            <select id="show_views" name="show_views">
                                <option value="yes" <?php selected($options['display']['show_views'], 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected($options['display']['show_views'], 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="show_ratings">Show Ratings</label></th>
                        <td>
                            <select id="show_ratings" name="show_ratings">
                                <option value="yes" <?php selected($options['display']['show_ratings'], 'yes'); ?>>
                                    Yes
                                </option>
                                <option value="no" <?php selected($options['display']['show_ratings'], 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="show_duration">Show Duration</label></th>
                        <td>
                            <select id="show_duration" name="show_duration">
                                <option value="yes" <?php selected($options['display']['show_duration'], 'yes'); ?>>
                                    Yes
                                </option>
                                <option value="no" <?php selected($options['display']['show_duration'], 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="show_description">Show Description</label></th>
                        <td>
                            <select id="show_description" name="show_description">
                                <option value="yes" <?php selected($options['display']['show_description'], 'yes'); ?>>
                                    Yes
                                </option>
                                <option value="no" <?php selected($options['display']['show_description'], 'no'); ?>>
                                    No
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Social Settings -->
            <div id="social" class="tab-pane">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="enable_sharing">Enable Sharing</label></th>
                        <td>
                            <select id="enable_sharing" name="enable_sharing">
                                <option value="yes" <?php selected($options['social']['enable_sharing'], 'yes'); ?>>
                                    Yes
                                </option>
                                <option value="no" <?php selected($options['social']['enable_sharing'], 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="facebook_app_id">Facebook App ID</label></th>
                        <td>
                            <input type="text" id="facebook_app_id" name="facebook_app_id" 
                                   value="<?php echo esc_attr($options['social']['facebook_app_id']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="twitter_handle">Twitter Handle</label></th>
                        <td>
                            <input type="text" id="twitter_handle" name="twitter_handle" 
                                   value="<?php echo esc_attr($options['social']['twitter_handle']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Advanced Settings -->
            <div id="advanced" class="tab-pane">
                <table class="form-table">

                    <tr>
                        <th scope="row"><label for="tmdb_api_key">TMDB API Key</label></th>
                        <td>
                            <input type="text" 
                                   id="tmdb_api_key" 
                                   name="tmdb_api_key" 
                                   value="<?php echo esc_attr($options['advanced']['tmdb_api_key'] ?? ''); ?>" 
                                   class="regular-text">
                            <p class="description">
                                Enter your TheMovieDB API key. You can get one from 
                                <a href="https://www.themoviedb.org/settings/api" target="_blank">here</a>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tmdb_language">TMDB Language</label></th>
                        <td>
                            <select id="tmdb_language" name="tmdb_language">
                                <option value="ar" <?php selected(($options['advanced']['tmdb_language'] ?? 'ar'), 'ar'); ?>>Arabic</option>
                                <option value="en" <?php selected(($options['advanced']['tmdb_language'] ?? 'ar'), 'en'); ?>>English</option>
                            </select>
                            <p class="description">Select the default language for fetching data from TMDB.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="cache_duration">Cache Duration (seconds)</label></th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" 
                                   value="<?php echo esc_attr($options['advanced']['cache_duration']); ?>" 
                                   min="0">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="api_key">API Key</label></th>
                        <td>
                            <input type="text" id="api_key" name="api_key" 
                                   value="<?php echo esc_attr($options['advanced']['api_key']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="custom_css">Custom CSS</label></th>
                        <td>
                            <textarea id="custom_css" name="custom_css" rows="5" class="large-text code"><?php 
                                echo esc_textarea($options['advanced']['custom_css']); 
                            ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="custom_js">Custom JavaScript</label></th>
                        <td>
                            <textarea id="custom_js" name="custom_js" rows="5" class="large-text code"><?php 
                                echo esc_textarea($options['advanced']['custom_js']); 
                            ?></textarea>
                        </td>
                    </tr>
                </table>
</div>
        </div>

        <div class="submit-wrapper">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </div>
    </form>
</div>

<style>
.mlm-settings {
    margin: 20px;
}

.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.form-table {
    margin-top: 0;
}

.form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
}

.submit-wrapper {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 0 0 4px 4px;
}

.regular-text {
    width: 25em;
}

.large-text {
    width: 100%;
    max-width: 800px;
}

.description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update content
        var target = $(this).attr('href').substring(1);
        $('.tab-pane').removeClass('active');
        $('#' + target).addClass('active');
    });

    // Form validation
    $('form').on('submit', function(e) {
        // Validate cache duration
        var cacheDuration = $('#cache_duration').val();
        if (cacheDuration && (isNaN(cacheDuration) || cacheDuration < 0)) {
            e.preventDefault();
            alert('Cache duration must be a positive number.');
            $('#cache_duration').focus();
            return false;
        }

        // Validate items per page
        var itemsPerPage = $('#items_per_page').val();
        if (itemsPerPage && (isNaN(itemsPerPage) || itemsPerPage < 1 || itemsPerPage > 100)) {
            e.preventDefault();
            alert('Items per page must be between 1 and 100.');
            $('#items_per_page').focus();
            return false;
        }
    });

    // Initialize code editors if available
    if (wp.codeEditor) {
        // CSS Editor
        wp.codeEditor.initialize($('#custom_css'), {
            codemirror: {
                mode: 'css',
                lineNumbers: true,
                lineWrapping: true,
                theme: 'default'
            }
        });

        // JavaScript Editor
        wp.codeEditor.initialize($('#custom_js'), {
            codemirror: {
                mode: 'javascript',
                lineNumbers: true,
                lineWrapping: true,
                theme: 'default'
            }
        });
    }

    // Add confirmation for API key changes
    var originalApiKey = $('#api_key').val();
    $('form').on('submit', function(e) {
        var newApiKey = $('#api_key').val();
        if (originalApiKey && newApiKey !== originalApiKey) {
            if (!confirm('Are you sure you want to change the API key? This may affect existing integrations.')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
                