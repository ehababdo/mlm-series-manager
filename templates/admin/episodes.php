<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-04 01:55:07';
$current_user = 'ehababdo';

global $wpdb;
$series_table = $wpdb->prefix . 'mlm_series';
$episodes_table = $wpdb->prefix . 'mlm_episodes';
$episode_links_table = $wpdb->prefix . 'mlm_episode_links';

// Get series ID from URL
$series_id = isset($_GET['series_id']) ? intval($_GET['series_id']) : 0;

// Get series information
$series = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $series_table WHERE id = %d",
    $series_id
));

if (!$series) {
    wp_die('Series not found');
}

// Initialize variables
$message = '';
$message_type = 'success';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['mlm_episodes_nonce']) || !wp_verify_nonce($_POST['mlm_episodes_nonce'], 'mlm_episodes_action')) {
        wp_die('Invalid nonce specified');
    }

    // Handle episode updates/additions
    if (isset($_POST['episode']) && is_array($_POST['episode'])) {
        foreach ($_POST['episode'] as $episode_id => $episode_data) {
            $episode = array(
                'series_id' => $series_id,
                'season_number' => intval($episode_data['season']),
                'episode_number' => intval($episode_data['number']),
                'title' => sanitize_text_field($episode_data['title']),
                'description' => wp_kses_post($episode_data['description']),
                'thumbnail' => isset($episode_data['thumbnail']) ? esc_url_raw($episode_data['thumbnail']) : '',
                'duration' => sanitize_text_field($episode_data['duration']),
                'air_date' => sanitize_text_field($episode_data['air_date']),
                'status' => 'active',
                'updated_at' => $current_datetime,
                'updated_by' => $current_user
            );

            if (strpos($episode_id, 'new_') === 0) {
                // New episode
                $episode['created_at'] = $current_datetime;
                $episode['created_by'] = $current_user;
                $wpdb->insert($episodes_table, $episode);
                $episode_id = $wpdb->insert_id;
            } else {
                // Update existing episode
                $wpdb->update(
                    $episodes_table,
                    $episode,
                    array('id' => $episode_id)
                );
            }

            // Handle streaming links
            if (isset($episode_data['video_urls']) && is_array($episode_data['video_urls'])) {
                // First, delete existing links
                $wpdb->delete($episode_links_table, array('episode_id' => $episode_id));

                // Then add new links
                foreach ($episode_data['video_urls'] as $key => $url) {
                    if (empty($url)) continue;

                    $wpdb->insert(
                        $episode_links_table,
                        array(
                            'episode_id' => $episode_id,
                            'video_url' => esc_url_raw($url),
                            'quality' => sanitize_text_field($episode_data['video_qualities'][$key]),
                            'server_name' => sanitize_text_field($episode_data['server_names'][$key]),
                            'status' => 'active',
                            'created_at' => $current_datetime,
                            'created_by' => $current_user,
                            'updated_at' => $current_datetime,
                            'updated_by' => $current_user
                        )
                    );
                }
            }
        }
    }

    // Handle deleted episodes
    if (!empty($_POST['deleted_episodes'])) {
        $deleted_episodes = array_map('intval', explode(',', $_POST['deleted_episodes']));
        foreach ($deleted_episodes as $episode_id) {
            $wpdb->delete($episodes_table, array('id' => $episode_id));
        }
    }

    $message = 'Episodes updated successfully.';
}

// Get all episodes for this series
$episodes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $episodes_table WHERE series_id = %d ORDER BY season_number, episode_number",
    $series_id
));

// Get streaming links for each episode
foreach ($episodes as $episode) {
    $episode->video_links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $episode_links_table WHERE episode_id = %d AND status = 'active'",
        $episode->id
    ));
}

// Available qualities
$qualities = array('4K', '1080p', '720p', '480p', '360p');
?>

<div class="wrap mlm-episodes">
    <h1 class="wp-heading-inline">
        Episodes for: <?php echo esc_html($series->title); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=mlm-series'); ?>" class="page-title-action">
        Back to Series
    </a>

    <?php if ($message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="mlm-form">
        <?php wp_nonce_field('mlm_episodes_action', 'mlm_episodes_nonce'); ?>
        <input type="hidden" name="deleted_episodes" value="">

        <div id="episodes-container">
            <?php if ($episodes): ?>
                <?php foreach ($episodes as $episode): ?>
                    <div class="episode-block" data-episode-id="<?php echo $episode->id; ?>">
                        <div class="episode-header">
                            <h3>
                                <span class="episode-title">
                                    Season <?php echo esc_html($episode->season_number); ?> 
                                    Episode <?php echo esc_html($episode->episode_number); ?>
                                </span>
                                <div class="episode-actions">
                                    <button type="button" class="button button-link-delete delete-episode" 
                                            title="Delete Episode">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </h3>
                        </div>

                        <div class="episode-content">
                            <div class="mlm-form-row episode-season-number">
                                <label>Season Number</label>
                                <input type="number" name="episode[<?php echo $episode->id; ?>][season]" 
                                       value="<?php echo esc_attr($episode->season_number); ?>" 
                                       min="1" class="season-number">
                            </div>

                            <div class="mlm-form-row episode-number">
                                <label>Episode Number</label>
                                <input type="number" name="episode[<?php echo $episode->id; ?>][number]" 
                                       value="<?php echo esc_attr($episode->episode_number); ?>" 
                                       min="1" class="episode-number">
                            </div>

                            <div class="mlm-form-row">
                                <label>Episode Title</label>
                                <input type="text" name="episode[<?php echo $episode->id; ?>][title]" 
                                       value="<?php echo esc_attr($episode->title); ?>" required>
                            </div>

                            <div class="mlm-form-row">
                                <label>Description</label>
                                <textarea name="episode[<?php echo $episode->id; ?>][description]" rows="3"><?php 
                                    echo esc_textarea($episode->description); 
                                ?></textarea>
                            </div>

                            <div class="mlm-form-row">
                                <label>Thumbnail</label>
                                <input type="url" name="episode[<?php echo $episode->id; ?>][thumbnail]" 
                                       value="<?php echo esc_url($episode->thumbnail); ?>" 
                                       class="regular-text">
                                <button type="button" class="button media-upload" 
                                        data-target="episode-<?php echo $episode->id; ?>-thumbnail">
                                    Upload Image
                                </button>
                            </div>

                            <div class="mlm-form-row">
                                <label>Duration (minutes)</label>
                                <input type="number" name="episode[<?php echo $episode->id; ?>][duration]" 
                                       value="<?php echo esc_attr($episode->duration); ?>">
                            </div>

                            <div class="mlm-form-row">
                                <label>Air Date</label>
                                <input type="date" name="episode[<?php echo $episode->id; ?>][air_date]" 
                                       value="<?php echo esc_attr($episode->air_date); ?>">
                            </div>

                            <div class="episode-links" data-episode-id="<?php echo $episode->id; ?>">
                                <h4>Streaming Links</h4>
                                <?php if ($episode->video_links): ?>
                                    <?php foreach ($episode->video_links as $link): ?>
                                        <div class="streaming-link">
                                            <input type="url" name="episode[<?php echo $episode->id; ?>][video_urls][]" 
                                                   value="<?php echo esc_url($link->video_url); ?>" 
                                                   placeholder="Video URL">
                                            <select name="episode[<?php echo $episode->id; ?>][video_qualities][]">
                                                <?php foreach ($qualities as $quality): ?>
                                                    <option value="<?php echo esc_attr($quality); ?>" 
                                                            <?php selected($link->quality, $quality); ?>>
                                                        <?php echo esc_html($quality); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="episode[<?php echo $episode->id; ?>][server_names][]" 
                                                   value="<?php echo esc_attr($link->server_name); ?>" 
                                                   placeholder="Server Name">
                                            <button type="button" class="button remove-link">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <button type="button" class="button add-episode-link">Add Streaming Link</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <button type="button" class="button button-primary add-episode">Add New Episode</button>
        </div>

        <div class="mlm-form-actions">
            <button type="submit" class="button button-primary">Save Episodes</button>
        </div>
    </form>
</div>

<style>
.episode-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.episode-header h3 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    margin: 0;
}

.episode-actions {
    display: flex;
    gap: 10px;
}

.delete-episode {
    color: #dc3232;
    padding: 0;
}

.delete-episode:hover {
    color: #dc3232;
    opacity: 0.8;
}

.episode-block {
    background: #f8f9fa;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.episode-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.episode-season-number,
.episode-number {
    width: 150px;
}

.streaming-link {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 10px;
    margin-bottom: 10px;
    align-items: start;
}

.mlm-form-actions {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Define qualities array
    const qualities = <?php echo json_encode($qualities); ?>;
    
    // Media Uploader
    $('.media-upload').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetInput = button.prev('input');
        
        var mediaUploader = wp.media({
            title: 'Select or Upload Media',
            button: {
                text: 'Use this media'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            targetInput.val(attachment.url);
        });

        mediaUploader.open();
    });

    // Add new episode
    $('.add-episode').on('click', function() {
        var episodeCount = $('.episode-block').length + 1;
        var lastSeason = $('.episode-block').last().find('.season-number').val() || 1;
        var template = `
            <div class="episode-block" data-episode-id="new_${episodeCount}">
                <div class="episode-header">
                    <h3>
                        <span class="episode-title">New Episode</span>
                        <div class="episode-actions">
                            <button type="button" class="button button-link-delete delete-episode" 
                                    title="Delete Episode">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </h3>
                </div>
                
                <div class="episode-content">
                    <div class="mlm-form-row episode-season-number">
                        <label>Season Number</label>
                        <input type="number" name="episode[new_${episodeCount}][season]" 
                               value="${lastSeason}" min="1" class="season-number">
                    </div>

                    <div class="mlm-form-row episode-number">
                        <label>Episode Number</label>
                        <input type="number" name="episode[new_${episodeCount}][number]" 
                               value="1" min="1" class="episode-number">
                    </div>
                    
                    <div class="mlm-form-row">
                        <label>Episode Title</label>
                        <input type="text" name="episode[new_${episodeCount}][title]" required>
                    </div>

                    <div class="mlm-form-row">
                        <label>Description</label>
                        <textarea name="episode[new_${episodeCount}][description]" rows="3"></textarea>
                    </div>

                    <div class="mlm-form-row">
                        <label>Thumbnail</label>
                        <input type="url" name="episode[new_${episodeCount}][thumbnail]" class="regular-text">
                        <button type="button" class="button media-upload" 
                                data-target="episode-new_${episodeCount}-thumbnail">
                            Upload Image
                        </button>
                    </div>

                    <div class="mlm-form-row">
                        <label>Duration (minutes)</label>
                        <input type="number" name="episode[new_${episodeCount}][duration]">
                    </div>

                    <div class="mlm-form-row">
                        <label>Air Date</label>
                        <input type="date" name="episode[new_${episodeCount}][air_date]">
                    </div>

                    <div class="episode-links" data-episode-id="new_${episodeCount}">
                        <h4>Streaming Links</h4>
                        <div class="streaming-link">
                            <input type="url" name="episode[new_${episodeCount}][video_urls][]" 
                                   placeholder="Video URL">
                            <select name="episode[new_${episodeCount}][video_qualities][]">
                                ${qualities.map(q => `<option value="${q}">${q}</option>`).join('')}
                            </select>
                            <input type="text" name="episode[new_${episodeCount}][server_names][]" 
                                   placeholder="Server Name">
                            <button type="button" class="button remove-link">Remove</button>
                        </div>
                        <button type="button" class="button add-episode-link">Add Streaming Link</button>
                    </div>
                </div>
            </div>
        `;
        $(this).before(template);
        updateEpisodeTitles();
    });

    // Delete episode
    $(document).on('click', '.delete-episode', function() {
        var episodeBlock = $(this).closest('.episode-block');
        var episodeId = episodeBlock.data('episode-id');
        
        if (confirm('Are you sure you want to delete this episode? This action cannot be undone.')) {
            if (episodeId && !episodeId.toString().startsWith('new_')) {
                // Add to deleted episodes list
                var deletedEpisodesInput = $('input[name="deleted_episodes"]');
                var deletedEpisodes = deletedEpisodesInput.val().split(',').filter(Boolean);
                deletedEpisodes.push(episodeId);
                deletedEpisodesInput.val(deletedEpisodes.join(','));
            }
            
            episodeBlock.remove();
            updateEpisodeTitles();
        }
    });

    // Add streaming link
    $(document).on('click', '.add-episode-link', function() {
        var episodeId = $(this).closest('.episode-links').data('episode-id');
        var template = `
            <div class="streaming-link">
                <input type="url" name="episode[${episodeId}][video_urls][]" placeholder="Video URL">
                <select name="episode[${episodeId}][video_qualities][]">
                    ${qualities.map(q => `<option value="${q}">${q}</option>`).join('')}
                </select>
                <input type="text" name="episode[${episodeId}][server_names][]" placeholder="Server Name">
                <button type="button" class="button remove-link">Remove</button>
            </div>
        `;
        $(this).before(template);
    });

    // Remove streaming link
    $(document).on('click', '.remove-link', function() {
        $(this).closest('.streaming-link').remove();
    });

    // Update episode numbers when season/episode numbers change
    $(document).on('change', '.season-number, .episode-number', function() {
        updateEpisodeTitles();
    });

    // Function to update episode titles
    function updateEpisodeTitles() {
        $('.episode-block').each(function() {
            var seasonNum = $(this).find('.season-number').val();
            var episodeNum = $(this).find('.episode-number').val();
            $(this).find('.episode-title').text(`Season ${seasonNum} Episode ${episodeNum}`);
        });
    }

    // Form validation
    $('form.mlm-form').on('submit', function(e) {
        var hasErrors = false;
        
        // Check for duplicate season/episode combinations
        var episodeCombinations = {};
        $('.episode-block').each(function() {
            var seasonNum = $(this).find('.season-number').val();
            var episodeNum = $(this).find('.episode-number').val();
            var key = `${seasonNum}-${episodeNum}`;
            
            if (episodeCombinations[key]) {
                hasErrors = true;
                alert(`Duplicate episode found: Season ${seasonNum} Episode ${episodeNum}`);
                return false;
            }
            
            episodeCombinations[key] = true;
        });

        if (hasErrors) {
            e.preventDefault();
            return false;
        }
    });

    // Initialize sorting
    if ($.fn.sortable) {
        $('#episodes-container').sortable({
            items: '.episode-block',
            handle: '.episode-header',
            update: function() {
                updateEpisodeTitles();
            }
        });
    }
});
</script>
