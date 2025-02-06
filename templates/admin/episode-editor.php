<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-05 23:29:59';
$current_user = 'ehababdo';

global $wpdb;
$series_table = $wpdb->prefix . 'mlm_series';
$episodes_table = $wpdb->prefix . 'mlm_episodes';
$episode_links_table = $wpdb->prefix . 'mlm_episode_links';

// Get action and IDs
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$series_id = isset($_GET['series_id']) ? intval($_GET['series_id']) : 0;

// Get episode data if editing
$episode = null;
if ($action === 'edit' && $episode_id) {
    $episode = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $episodes_table WHERE id = %d",
        $episode_id
    ));
    
    if ($episode) {
        $series_id = $episode->series_id;
        $episode->video_links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $episode_links_table WHERE episode_id = %d AND status = 'active'",
            $episode_id
        ));
    }
}

// Get series information
$series = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $series_table WHERE id = %d",
    $series_id
));

if (!$series) {
    wp_die('Series not found');
}

// Available qualities
$qualities = array('4K', '1080p', '720p', '480p', '360p');
?>

<style>
.mlm-episode-editor {
    max-width: 800px;
    margin: 20px auto;
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.editor-header {
    margin: -25px -25px 20px;
    padding: 20px 25px;
    background: #f8f9fa;
    border-bottom: 2px solid #e2e4e7;
    border-radius: 8px 8px 0 0;
}

.editor-title {
    margin: 0;
    font-size: 1.5em;
}

.mlm-form-row {
    margin-bottom: 20px;
}

.mlm-form-row label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.mlm-form-row input[type="text"],
.mlm-form-row input[type="number"],
.mlm-form-row input[type="url"],
.mlm-form-row input[type="date"],
.mlm-form-row select,
.mlm-form-row textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.episode-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.thumbnail-preview {
    margin: 10px 0;
}

.thumbnail-preview img {
    max-width: 200px;
    height: auto;
    border-radius: 4px;
}

.streaming-link {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 10px;
    align-items: start;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.editor-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.editor-actions .button {
    margin-left: 10px;
}
</style>

<div class="wrap mlm-episode-editor">
    <div class="editor-header">
        <h1 class="editor-title">
            <?php echo $action === 'edit' ? 'Edit Episode' : 'Add New Episode'; ?>
        </h1>
    </div>

    <form method="post" action="<?php echo admin_url('admin.php?page=mlm-episodes'); ?>" class="mlm-form">
        <?php wp_nonce_field('mlm_episode_editor', 'mlm_episode_nonce'); ?>
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="episode_id" value="<?php echo $episode_id; ?>">
        <input type="hidden" name="series_id" value="<?php echo $series_id; ?>">

        <div class="episode-grid">
            <div class="episode-main-info">
                <div class="mlm-form-row">
                    <label>Season Number</label>
                    <input type="number" name="episode[season]" 
                           value="<?php echo $episode ? esc_attr($episode->season_number) : '1'; ?>" 
                           min="1" required>
                </div>

                <div class="mlm-form-row">
                    <label>Episode Number</label>
                    <input type="number" name="episode[number]" 
                           value="<?php echo $episode ? esc_attr($episode->episode_number) : '1'; ?>" 
                           min="1" required>
                </div>

                <div class="mlm-form-row">
                    <label>Episode Title</label>
                    <input type="text" name="episode[title]" 
                           value="<?php echo $episode ? esc_attr($episode->title) : ''; ?>" required>
                </div>

                <div class="mlm-form-row">
                    <label>Duration (minutes)</label>
                    <input type="number" name="episode[duration]" 
                           value="<?php echo $episode ? esc_attr($episode->duration) : ''; ?>">
                </div>

                <div class="mlm-form-row">
                    <label>Air Date</label>
                    <input type="date" name="episode[air_date]" 
                           value="<?php echo $episode ? esc_attr($episode->air_date) : ''; ?>">
                </div>
            </div>

            <div class="episode-media">
                <div class="mlm-form-row">
                    <label>Description</label>
                    <textarea name="episode[description]" rows="4"><?php 
                        echo $episode ? esc_textarea($episode->description) : ''; 
                    ?></textarea>
                </div>

                <div class="mlm-form-row">
                    <label>Thumbnail</label>
                    <div class="thumbnail-preview">
                        <?php if ($episode && $episode->thumbnail): ?>
                            <img src="<?php echo esc_url($episode->thumbnail); ?>" 
                                 alt="Episode thumbnail">
                        <?php endif; ?>
                    </div>
                    <input type="url" name="episode[thumbnail]" class="thumbnail-url"
                           value="<?php echo $episode ? esc_url($episode->thumbnail) : ''; ?>">
                    <button type="button" class="button media-upload">Upload Image</button>
                </div>

                <div class="episode-links">
                    <h3>Streaming Links</h3>
                    <?php if ($episode && $episode->video_links): ?>
                        <?php foreach ($episode->video_links as $link): ?>
                            <div class="streaming-link">
                                <input type="url" name="episode[video_urls][]" 
                                       value="<?php echo esc_url($link->video_url); ?>" 
                                       placeholder="Video URL">
                                <select name="episode[video_qualities][]">
                                    <?php foreach ($qualities as $quality): ?>
                                        <option value="<?php echo esc_attr($quality); ?>" 
                                                <?php selected($link->quality, $quality); ?>>
                                            <?php echo esc_html($quality); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="episode[server_names][]" 
                                       value="<?php echo esc_attr($link->server_name); ?>" 
                                       placeholder="Server Name">
                                <button type="button" class="button remove-link">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button type="button" class="button add-streaming-link">Add Streaming Link</button>
                </div>
            </div>
        </div>

        <div class="editor-actions">
            <a href="<?php echo admin_url('admin.php?page=mlm-episodes&series_id=' . $series_id); ?>" 
               class="button">Cancel</a>
            <button type="submit" class="button button-primary">
                <?php echo $action === 'edit' ? 'Update Episode' : 'Add Episode'; ?>
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Media Uploader
    $('.media-upload').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetInput = button.prev('input');
        var previewContainer = button.closest('.mlm-form-row').find('.thumbnail-preview');
        
        var mediaUploader = wp.media({
            title: 'Select or Upload Episode Thumbnail',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            targetInput.val(attachment.url);
            previewContainer.html(`<img src="${attachment.url}" alt="Episode thumbnail">`);
        });

        mediaUploader.open();
    });

    // Add streaming link
    $('.add-streaming-link').on('click', function() {
        var template = `
            <div class="streaming-link">
                <input type="url" name="episode[video_urls][]" placeholder="Video URL">
                <select name="episode[video_qualities][]">
                    <?php foreach ($qualities as $quality): ?>
                        <option value="<?php echo esc_attr($quality); ?>">
                            <?php echo esc_html($quality); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="episode[server_names][]" placeholder="Server Name">
                <button type="button" class="button remove-link">Remove</button>
            </div>
        `;
        $(this).before(template);
    });

    // Remove streaming link
    $(document).on('click', '.remove-link', function() {
        $(this).closest('.streaming-link').remove();
    });

    // Form validation
    $('form.mlm-form').on('submit', function(e) {
        var required = ['season', 'number', 'title'];
        var hasErrors = false;

        required.forEach(function(field) {
            var input = $(`input[name="episode[${field}]"]`);
            if (!input.val()) {
                alert(`Please fill in the ${field} field`);
                input.focus();
                hasErrors = true;
                return false;
            }
        });

        if (hasErrors) {
            e.preventDefault();
            return false;
        }
    });
});
</script>