<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current Date/Time and User settings
define('CURRENT_UTC_DATETIME', '2025-02-04 00:25:23');
define('CURRENT_USER_LOGIN', 'ehababdo');

global $wpdb;
$table_name = $wpdb->prefix . 'mlm_movies';
$links_table = $wpdb->prefix . 'mlm_movie_links';

// Get action and movie ID from URL parameters
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$message = '';
$message_type = 'success';
$movie = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['mlm_movie_nonce']) || !wp_verify_nonce($_POST['mlm_movie_nonce'], 'mlm_movie_action')) {
        wp_die('Invalid nonce specified');
    }

    // Prepare movie data with proper validation
    $movie_data = array(
        'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
        'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
        'trailer_url' => isset($_POST['trailer_url']) ? esc_url_raw($_POST['trailer_url']) : '',
        'thumbnail' => isset($_POST['thumbnail']) ? esc_url_raw($_POST['thumbnail']) : '',
        'poster' => isset($_POST['poster']) ? esc_url_raw($_POST['poster']) : '',
        'icon' => isset($_POST['icon']) ? esc_url_raw($_POST['icon']) : '',
        'language' => isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '',
        'imdb_rating' => isset($_POST['imdb_rating']) ? floatval($_POST['imdb_rating']) : 0.0,
        'age_restriction' => isset($_POST['age_restriction']) ? sanitize_text_field($_POST['age_restriction']) : '',
        'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
        'release_date' => isset($_POST['release_date']) ? sanitize_text_field($_POST['release_date']) : '',
        'genre' => isset($_POST['genre']) ? sanitize_text_field($_POST['genre']) : '',
        'duration' => isset($_POST['duration']) ? intval($_POST['duration']) : 0,
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
        'updated_at' => CURRENT_UTC_DATETIME,
        'updated_by' => CURRENT_USER_LOGIN
    );

    // Validate required fields
    if (empty($movie_data['title'])) {
        $message = 'Movie title is required.';
        $message_type = 'error';
    } else {
        if ($action === 'add') {
            $movie_data['created_at'] = CURRENT_UTC_DATETIME;
            $movie_data['created_by'] = CURRENT_USER_LOGIN;
            
            $wpdb->insert($table_name, $movie_data);
            $movie_id = $wpdb->insert_id;
            
            if ($movie_id) {
                $message = 'Movie added successfully.';
            } else {
                $message = 'Error adding movie.';
                $message_type = 'error';
            }
        } elseif ($action === 'edit' && $movie_id) {
            $wpdb->update($table_name, $movie_data, array('id' => $movie_id));
            $message = 'Movie updated successfully.';
        }

        // Handle streaming links
        if ($movie_id && ($action === 'add' || $action === 'edit')) {
            if ($action === 'edit') {
                $wpdb->delete($links_table, array('movie_id' => $movie_id));
            }

            if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
                foreach ($_POST['video_urls'] as $key => $url) {
                    if (!empty($url)) {
                        $wpdb->insert(
                            $links_table,
                            array(
                                'movie_id' => $movie_id,
                                'video_url' => esc_url_raw($url),
                                'quality' => isset($_POST['video_qualities'][$key]) ? 
                                    sanitize_text_field($_POST['video_qualities'][$key]) : '',
                                'server_name' => isset($_POST['server_names'][$key]) ? 
                                    sanitize_text_field($_POST['server_names'][$key]) : '',
                                'status' => 'active',
                                'created_at' => CURRENT_UTC_DATETIME,
                                'created_by' => CURRENT_USER_LOGIN
                            )
                        );
                    }
                }
            }
        }
    }
}

// Get movie data for editing
if ($action === 'edit' && $movie_id) {
    $movie = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $movie_id
    ));

    if ($movie) {
        $movie->video_links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $links_table WHERE movie_id = %d AND status = 'active'",
            $movie_id
        ));
    }
}

// Available options for select fields
$languages = array('English', 'Arabic', 'Spanish', 'French', 'German', 'Italian', 'Japanese', 'Korean', 'Chinese');
$age_ratings = array('G', 'PG', 'PG-13', 'R', 'NC-17');
$genres = array(
    'action' => 'Action',
    'comedy' => 'Comedy',
    'drama' => 'Drama',
    'horror' => 'Horror',
    'thriller' => 'Thriller',
    'sci-fi' => 'Science Fiction',
    'romance' => 'Romance',
    'documentary' => 'Documentary',
    'animation' => 'Animation'
);
$qualities = array('4K', '1080p', '720p', '480p', '360p');
?>

<div class="wrap mlm-movies">
    <h1 class="wp-heading-inline">
        <?php echo $action === 'list' ? 'Movies' : ($action === 'add' ? 'Add New Movie' : 'Edit Movie'); ?>
    </h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=mlm-movies&action=add'); ?>" class="page-title-action">
            Add New Movie
        </a>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <!-- Movies List Table -->
        <div class="mlm-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th width="80">Thumbnail</th>
                        <th>Genre</th>
                        <th>IMDb</th>
                        <th>Language</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $movies = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
                    if ($movies):
                        foreach ($movies as $item):
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($item->title); ?></strong>
                            </td>
                            <td>
                                <?php if ($item->thumbnail): ?>
                                    <img src="<?php echo esc_url($item->thumbnail); ?>" 
                                         alt="<?php echo esc_attr($item->title); ?>" 
                                         style="max-width: 50px; height: auto;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($item->genre); ?></td>
                            <td><?php echo esc_html($item->imdb_rating); ?></td>
                            <td><?php echo esc_html($item->language); ?></td>
                            <td><?php echo esc_html($item->duration); ?> min</td>
                            <td>
                                <span class="mlm-status mlm-status-<?php echo esc_attr($item->status); ?>">
                                    <?php echo esc_html(ucfirst($item->status)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=mlm-movies&action=edit&id=' . $item->id); ?>" 
                                   class="button button-small">Edit</a>
                                <a href="#" class="button button-small button-link-delete mlm-delete-movie" 
                                   data-id="<?php echo $item->id; ?>">Delete</a>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="8">No movies found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- Add/Edit Movie Form -->
        <form method="post" action="" class="mlm-form">
            <?php wp_nonce_field('mlm_movie_action', 'mlm_movie_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
            <?php if ($movie_id): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($movie_id); ?>">
            <?php endif; ?>

            <div class="mlm-form-grid">
                <!-- Basic Information -->
                <div class="mlm-form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="mlm-form-row">
                        <label for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo esc_attr($movie ? $movie->title : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"><?php 
                            echo esc_textarea($movie ? $movie->description : ''); 
                        ?></textarea>
                    </div>

                    <div class="mlm-form-row">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre">
                            <?php foreach ($genres as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php 
                                    selected($movie ? $movie->genre : '', $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mlm-form-row">
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <?php foreach ($languages as $lang): ?>
                                <option value="<?php echo esc_attr($lang); ?>" <?php 
                                    selected($movie ? $movie->language : '', $lang); ?>>
                                    <?php echo esc_html($lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mlm-form-row">
                        <label for="release_date">Release Date</label>
                        <input type="date" id="release_date" name="release_date" 
                               value="<?php echo esc_attr($movie ? $movie->release_date : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" min="1" 
                               value="<?php echo esc_attr($movie ? $movie->duration : ''); ?>">
                    </div>
                </div>

                <!-- Media Information -->
                <div class="mlm-form-section">
                    <h2>Media Information</h2>

                    <div class="mlm-form-row">
                        <label for="trailer_url">Trailer URL</label>
                        <input type="url" id="trailer_url" name="trailer_url" 
                               value="<?php echo esc_url($movie ? $movie->trailer_url : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="thumbnail">Thumbnail URL</label>
                        <input type="url" id="thumbnail" name="thumbnail" 
                               value="<?php echo esc_url($movie ? $movie->thumbnail : ''); ?>">
                        <button type="button" class="button media-upload" data-target="thumbnail">Upload Image</button>
                    </div>

                    <div class="mlm-form-row">
                        <label for="poster">Poster URL</label>
                        <input type="url" id="poster" name="poster" 
                               value="<?php echo esc_url($movie ? $movie->poster : ''); ?>">
                        <button type="button" class="button media-upload" data-target="poster">Upload Image</button>
                    </div>

                    <div class="mlm-form-row">
                        <label for="icon">Icon URL</label>
                        <input type="url" id="icon" name="icon" 
                               value="<?php echo esc_url($movie ? $movie->icon : ''); ?>">
                        <button type="button" class="button media-upload" data-target="icon">Upload Image</button>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="mlm-form-section">
                    <h2>Additional Information</h2>

                    <div class="mlm-form-row">
                        <label for="imdb_rating">IMDb Rating</label>
                        <input type="number" id="imdb_rating" name="imdb_rating" step="0.1" min="0" max="10" 
                               value="<?php echo esc_attr($movie ? $movie->imdb_rating : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="age_restriction">Age Restriction</label>
                        <select id="age_restriction" name="age_restriction">
                            <?php foreach ($age_ratings as $rating): ?>
                                <option value="<?php echo esc_attr($rating); ?>" <?php 
                                    selected($movie ? $movie->age_restriction : '', $rating); ?>>
                                    <?php echo esc_html($rating); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                <div class="mlm-form-row">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" 
                           value="<?php echo esc_attr($movie ? $movie->country : ''); ?>">
                </div>

                <div class="mlm-form-row">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" <?php selected($movie ? $movie->status : '', 'active'); ?>>Active</option>
                        <option value="inactive" <?php selected($movie ? $movie->status : '', 'inactive'); ?>>Inactive</option>
                    </select>
                </div>
            </div>


            <!-- Streaming Links -->
            <div class="mlm-form-section">
                <h2>Streaming Links</h2>
                <div id="streaming-links">
                    <?php if ($movie && !empty($movie->video_links)): 
                        foreach ($movie->video_links as $link): ?>
                        <div class="mlm-form-row streaming-link">
                            <input type="url" name="video_urls[]" value="<?php echo esc_url($link->video_url); ?>" 
                                   placeholder="Video URL" required>
                            <select name="video_qualities[]">
                                <?php foreach ($qualities as $quality): ?>
                                    <option value="<?php echo esc_attr($quality); ?>" 
                                            <?php selected($link->quality, $quality); ?>>
                                        <?php echo esc_html($quality); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="server_names[]" value="<?php echo esc_attr($link->server_name); ?>" 
                                   placeholder="Server Name">
                            <button type="button" class="button remove-link">Remove</button>
                        </div>
                    <?php endforeach; 
                    else: ?>
                        <div class="mlm-form-row streaming-link">
                            <input type="url" name="video_urls[]" placeholder="Video URL" required>
                            <select name="video_qualities[]">
                                <?php foreach ($qualities as $quality): ?>
                                    <option value="<?php echo esc_attr($quality); ?>">
                                        <?php echo esc_html($quality); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="server_names[]" placeholder="Server Name">
                            <button type="button" class="button remove-link">Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="button add-streaming-link">Add Streaming Link</button>
            </div>
        </div>

        <div class="mlm-form-actions">
            <button type="submit" class="button button-primary">
                <?php echo $action === 'add' ? 'Add Movie' : 'Update Movie'; ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=mlm-movies'); ?>" class="button">Cancel</a>
        </div>
    </form>
<?php endif; ?>
</div>

<style>
.mlm-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.mlm-form-section {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mlm-form-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mlm-form-row {
    margin-bottom: 15px;
}

.mlm-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.mlm-form-row input[type="text"],
.mlm-form-row input[type="url"],
.mlm-form-row input[type="number"],
.mlm-form-row input[type="date"],
.mlm-form-row select,
.mlm-form-row textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.streaming-link {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.streaming-link input,
.streaming-link select {
    margin: 0;
}

.mlm-form-actions {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mlm-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.mlm-status-active {
    background: #e7f5ea;
    color: #0a6b1d;
}

.mlm-status-inactive {
    background: #f8e7e7;
    color: #be2525;
}

.media-upload {
    margin-top: 5px;
}

.required {
    color: #dc3232;
}

/* Table Styles */
.mlm-table-wrapper {
    margin-top: 20px;
}

.wp-list-table img {
    border-radius: 4px;
}

/* Responsive Adjustments */
@media screen and (max-width: 782px) {
    .streaming-link {
        grid-template-columns: 1fr;
    }
    
    .mlm-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Media Uploader
    $('.media-upload').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetInput = $('#' + button.data('target'));
        
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

    // Add streaming link
    $('.add-streaming-link').on('click', function() {
        var template = $('.streaming-link').first().clone();
        template.find('input').val('');
        template.find('select').prop('selectedIndex', 0);
        $('#streaming-links').append(template);
    });

    // Remove streaming link
    $(document).on('click', '.remove-link', function() {
        var linksCount = $('.streaming-link').length;
        if (linksCount > 1) {
            $(this).closest('.streaming-link').remove();
        } else {
            alert('At least one streaming link is required.');
        }
    });

    // Delete movie confirmation
    $('.mlm-delete-movie').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this movie? This action cannot be undone.')) {
            var movieId = $(this).data('id');
            // Add AJAX delete functionality here
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_movie',
                    movie_id: movieId,
                    nonce: '<?php echo wp_create_nonce("delete_movie_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting movie: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Form validation
    $('form.mlm-form').on('submit', function(e) {
        var title = $('#title').val().trim();
        if (!title) {
            e.preventDefault();
            alert('Movie title is required.');
            $('#title').focus();
            return false;
        }

        var hasValidLinks = false;
        $('input[name="video_urls[]"]').each(function() {
            if ($(this).val().trim()) {
                hasValidLinks = true;
                return false;
            }
        });

        if (!hasValidLinks) {
            e.preventDefault();
            alert('At least one valid streaming link is required.');
            return false;
        }
    });
});
</script>