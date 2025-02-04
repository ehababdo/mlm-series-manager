<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get search query
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get current page
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;

// Current settings
$current_datetime = '2025-02-04 02:38:18';
$current_user = 'ehababdo';

global $wpdb;
$table_name = $wpdb->prefix . 'mlm_series';
$episodes_table = $wpdb->prefix . 'mlm_episodes';
$episode_links_table = $wpdb->prefix . 'mlm_episode_links';

// Get action and series ID from URL parameters
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$series_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$message = '';
$message_type = 'success';
$series = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['mlm_series_nonce']) || !wp_verify_nonce($_POST['mlm_series_nonce'], 'mlm_series_action')) {
        wp_die('Invalid nonce specified');
    }

    // Prepare series data
    $series_data = array(
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
        'release_date' => isset($_POST['release_date']) ? sanitize_text_field($_POST['release_date']) : null,
        'genre' => isset($_POST['genre']) ? sanitize_text_field($_POST['genre']) : '',
        'total_seasons' => isset($_POST['total_seasons']) ? intval($_POST['total_seasons']) : 1,
        'duration' => isset($_POST['duration']) ? sanitize_text_field($_POST['duration']) : '',
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
        'updated_at' => $current_datetime,
        'updated_by' => $current_user
    );

    if (empty($series_data['title'])) {
        $message = 'Series title is required.';
        $message_type = 'error';
    } else {
        if ($action === 'add') {
            $series_data['created_at'] = $current_datetime;
            $series_data['created_by'] = $current_user;
            
            $result = $wpdb->insert($table_name, $series_data);
            $series_id = $wpdb->insert_id;
            
            if ($result !== false) {
                $message = 'Series added successfully.';
            } else {
                $message = 'Error adding series: ' . $wpdb->last_error;
                $message_type = 'error';
            }
        } elseif ($action === 'edit' && $series_id) {
            $result = $wpdb->update($table_name, $series_data, array('id' => $series_id));
            if ($result !== false) {
                $message = 'Series updated successfully.';
            } else {
                $message = 'Error updating series: ' . $wpdb->last_error;
                $message_type = 'error';
            }
        }

        // Handle episodes if submitted
        if ($series_id && isset($_POST['episode']) && is_array($_POST['episode'])) {
            foreach ($_POST['episode'] as $episode_data) {
                if (empty($episode_data['title'])) continue;

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
                    'created_at' => $current_datetime,
                    'created_by' => $current_user,
                    'updated_at' => $current_datetime,
                    'updated_by' => $current_user
                );

                $wpdb->insert($episodes_table, $episode);
                $episode_id = $wpdb->insert_id;

                // Handle episode streaming links
                if ($episode_id && !empty($episode_data['video_urls'])) {
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
    }
}

// Get series data for editing
if ($action === 'edit' && $series_id) {
    $series = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $series_id
    ));

    if ($series) {
        $series->episodes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $episodes_table WHERE series_id = %d ORDER BY season_number, episode_number",
            $series_id
        ));

        foreach ($series->episodes as $episode) {
            $episode->video_links = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $episode_links_table WHERE episode_id = %d AND status = 'active'",
                $episode->id
            ));
        }
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

<div class="wrap mlm-series">
    <h1 class="wp-heading-inline">
        <?php echo $action === 'list' ? 'TV Series' : ($action === 'add' ? 'Add New Series' : 'Edit Series'); ?>
    </h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=mlm-series&action=add'); ?>" class="page-title-action">
            Add New Series
        </a>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <!-- Search Form -->
        <form method="get" class="search-box">
            <input type="hidden" name="page" value="mlm-series">
            <p class="search-box">
                <label class="screen-reader-text" for="series-search">Search Series:</label>
                <input type="search" id="series-search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="Search series...">
                <input type="submit" class="button" value="Search Series">
            </p>
        </form>
        <div class="wp-list-table widefat fixed striped">

                    <?php
                    // Build where clause for search
                    $query_where = '';
                    $query_values = array();

                    if (!empty($search_query)) {
                        $query_where = "WHERE title LIKE %s OR description LIKE %s";
                        $search_term = '%' . $wpdb->esc_like($search_query) . '%';
                        $query_values = array($search_term, $search_term);
                    }

                    // Get total items
                    if (!empty($query_values)) {
                        $total_items = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_name " . $query_where,
                                $query_values
                            )
                        );
                    } else {
                        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                    }
                    // Get pagination data
                    $pagination = array(
                        'total_items' => $total_items,
                        'per_page' => $per_page,
                        'total_pages' => ceil($total_items / $per_page),
                        'current_page' => $current_page,
                        'offset' => ($current_page - 1) * $per_page
                    );

                    if (!empty($query_values)) {
                        $series_list = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM $table_name " . $query_where . " ORDER BY created_at DESC LIMIT %d OFFSET %d",
                                array_merge($query_values, array($per_page, $pagination['offset']))
                            )
                        );
                    } else {
                        $series_list = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                                array($per_page, $pagination['offset'])
                            )
                        );
                    }

                    ?>
 <div class="mlm-grid-view">
    <?php if ($series_list): ?>
        <?php foreach ($series_list as $item): 
            $episode_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $episodes_table WHERE series_id = %d",
                $item->id
            ));
        ?>
            <div class="series-card">
                <div class="series-thumbnail">
                    <?php if ($item->thumbnail): ?>
                        <img src="<?php echo esc_url($item->thumbnail); ?>" 
                             alt="<?php echo esc_attr($item->title); ?>">
                    <?php else: ?>
                        <div class="no-thumbnail">
                            <span class="dashicons dashicons-video-alt2"></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="series-info">
                    <h3 class="series-title"><?php echo esc_html($item->title); ?></h3>
                    <div class="series-meta">
                        <span class="status-badge status-<?php echo esc_attr($item->status); ?>">
                            <?php echo esc_html(ucfirst($item->status)); ?>
                        </span>
                        <span class="episodes-count">
                            <?php echo sprintf(_n('%s Episode', '%s Episodes', $episode_count), number_format_i18n($episode_count)); ?>
                        </span>
                    </div>
                    <div class="series-actions">
                        <a href="<?php echo admin_url('admin.php?page=mlm-series&action=edit&id=' . $item->id); ?>" 
                           class="button button-small" title="Edit Series">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=mlm-episodes&series_id=' . $item->id); ?>" 
                           class="button button-small" title="Manage Episodes">
                            <span class="dashicons dashicons-playlist-video"></span>
                        </a>
                        <a href="#" class="button button-small mlm-delete-series" 
                           data-id="<?php echo $item->id; ?>" title="Delete Series">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-items-found">
            <p>No series found. <?php echo $search_query ? 'Try different search terms.' : ''; ?></p>
        </div>
    <?php endif; ?>
</div>
        </div>
<!-- Pagination -->
<?php if ($pagination['total_pages'] > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php echo sprintf(_n('%s item', '%s items', $pagination['total_items']), number_format_i18n($pagination['total_items'])); ?>
            </span>
            <span class="pagination-links">
                <?php
                // First page
                if ($current_page > 1):
                    $first_url = add_query_arg(array('paged' => 1, 's' => $search_query));
                ?>
                    <a class="first-page button" href="<?php echo esc_url($first_url); ?>">
                        <span>«</span>
                    </a>
                <?php endif; ?>

                <?php
                // Previous page
                if ($current_page > 1):
                    $prev_url = add_query_arg(array('paged' => $current_page - 1, 's' => $search_query));
                ?>
                    <a class="prev-page button" href="<?php echo esc_url($prev_url); ?>">
                        <span>‹</span>
                    </a>
                <?php endif; ?>

                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                    <input class="current-page" id="current-page-selector" type="text" name="paged" 
                           value="<?php echo esc_attr($current_page); ?>" size="1" aria-describedby="table-paging">
                    <span class="tablenav-paging-text"> of 
                        <span class="total-pages"><?php echo number_format_i18n($pagination['total_pages']); ?></span>
                    </span>
                </span>

                <?php
                // Next page
                if ($current_page < $pagination['total_pages']):
                    $next_url = add_query_arg(array('paged' => $current_page + 1, 's' => $search_query));
                ?>
                    <a class="next-page button" href="<?php echo esc_url($next_url); ?>">
                        <span>›</span>
                    </a>
                <?php endif; ?>

                <?php
                // Last page
                if ($current_page < $pagination['total_pages']):
                    $last_url = add_query_arg(array('paged' => $pagination['total_pages'], 's' => $search_query));
                ?>
                    <a class="last-page button" href="<?php echo esc_url($last_url); ?>">
                        <span>»</span>
                    </a>
                <?php endif; ?>
            </span>
        </div>
    </div>
<?php endif; ?>
    <?php else: ?>
        <form method="post" action="" class="mlm-form">
            <?php wp_nonce_field('mlm_series_action', 'mlm_series_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
            <?php if ($series_id): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($series_id); ?>">
            <?php endif; ?>

            <div class="mlm-form-grid">
                <!-- Basic Information -->
                <div class="mlm-form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="mlm-form-row">
                        <label for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo esc_attr($series ? $series->title : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"><?php 
                            echo esc_textarea($series ? $series->description : ''); 
                        ?></textarea>
                    </div>

                    <div class="mlm-form-row">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre">
                            <?php foreach ($genres as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php 
                                    selected($series ? $series->genre : '', $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mlm-form-row">
                        <label for="total_seasons">Total Seasons</label>
                        <input type="number" id="total_seasons" name="total_seasons" min="1" 
                               value="<?php echo esc_attr($series ? $series->total_seasons : '1'); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="duration">Duration (per episode)</label>
                        <input type="text" id="duration" name="duration" placeholder="e.g., 45 minutes" 
                               value="<?php echo esc_attr($series ? $series->duration : ''); ?>">
                    </div>
                </div>

                <!-- Media Information -->
                <div class="mlm-form-section">
                    <h2>Media Information</h2>

                    <div class="mlm-form-row">
                        <label for="trailer_url">Trailer URL</label>
                        <input type="url" id="trailer_url" name="trailer_url" 
                               value="<?php echo esc_url($series ? $series->trailer_url : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                       <label for="thumbnail">Thumbnail URL</label>
                        <input type="url" id="thumbnail" name="thumbnail" 
                               value="<?php echo esc_url($series ? $series->thumbnail : ''); ?>">
                        <button type="button" class="button media-upload" data-target="thumbnail">Upload Image</button>
                    </div>

                    <div class="mlm-form-row">
                        <label for="poster">Poster URL</label>
                        <input type="url" id="poster" name="poster" 
                               value="<?php echo esc_url($series ? $series->poster : ''); ?>">
                        <button type="button" class="button media-upload" data-target="poster">Upload Image</button>
                    </div>

                    <div class="mlm-form-row">
                        <label for="icon">Icon URL</label>
                        <input type="url" id="icon" name="icon" 
                               value="<?php echo esc_url($series ? $series->icon : ''); ?>">
                        <button type="button" class="button media-upload" data-target="icon">Upload Image</button>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="mlm-form-section">
                    <h2>Additional Information</h2>

                    <div class="mlm-form-row">
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <?php foreach ($languages as $lang): ?>
                                <option value="<?php echo esc_attr($lang); ?>" <?php 
                                    selected($series ? $series->language : '', $lang); ?>>
                                    <?php echo esc_html($lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mlm-form-row">
                        <label for="imdb_rating">IMDb Rating</label>
                        <input type="number" id="imdb_rating" name="imdb_rating" step="0.1" min="0" max="10" 
                               value="<?php echo esc_attr($series ? $series->imdb_rating : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="age_restriction">Age Restriction</label>
                        <select id="age_restriction" name="age_restriction">
                            <?php foreach ($age_ratings as $rating): ?>
                                <option value="<?php echo esc_attr($rating); ?>" <?php 
                                    selected($series ? $series->age_restriction : '', $rating); ?>>
                                    <?php echo esc_html($rating); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mlm-form-row">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" 
                               value="<?php echo esc_attr($series ? $series->country : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="release_date">Release Date</label>
                        <input type="date" id="release_date" name="release_date" 
                               value="<?php echo esc_attr($series ? $series->release_date : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php selected($series ? $series->status : '', 'active'); ?>>Active</option>
                            <option value="inactive" <?php selected($series ? $series->status : '', 'inactive'); ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Episodes Section -->
            <div class="mlm-form-section">
                <h2>Episodes</h2>
                <div id="episodes-container">
                    <?php 
                    if ($series && !empty($series->episodes)):
                        foreach ($series->episodes as $episode):
                    ?>
                        <div class="episode-block" data-episode-id="<?php echo $episode->id; ?>">

                            <div class="episode-header">
                                <h3>
                                    <span class="episode-title">Season <?php echo esc_html($episode->season_number); ?> 
                                    Episode <?php echo esc_html($episode->episode_number); ?></span>
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
                                           value="<?php echo esc_attr($episode->title); ?>">
                                </div>

                                <div class="mlm-form-row">
                                    <label>Description</label>
                                    <textarea name="episode[<?php echo $episode->id; ?>][description]" rows="3"><?php 
                                        echo esc_textarea($episode->description); 
                                    ?></textarea>
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

                                <div class="episode-links">
                                    <h4>Streaming Links</h4>
                                    <?php foreach ($episode->video_links as $link): ?>
                                        <div class="streaming-link">
                                            <input type="url" name="episode[<?php echo $episode->id; ?>][video_urls][]" 
                                                   value="<?php echo esc_url($link->video_url); ?>" placeholder="Video URL">
                                            <select name="episode[<?php echo $episode->id; ?>][video_qualities][]">
                                                <?php foreach ($qualities as $quality): ?>
                                                    <option value="<?php echo esc_attr($quality); ?>" 
                                                            <?php selected($link->quality, $quality); ?>>
                                                        <?php echo esc_html($quality); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="episode[<?php echo $episode->id; ?>][server_names][]" 
                                                   value="<?php echo esc_attr($link->server_name); ?>" placeholder="Server Name">
                                            <button type="button" class="button remove-link">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="button" class="button add-episode-link" 
                                            data-episode-id="<?php echo $episode->id; ?>">Add Streaming Link</button>
                                </div>
                            </div>    
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                    <button type="button" class="button button-primary add-episode">Add New Episode</button>
                </div>
            </div>

            <div class="mlm-form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo $action === 'add' ? 'Add Series' : 'Update Series'; ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=mlm-series'); ?>" class="button">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>


<script type="text/javascript">
jQuery(document).ready(function($) {
    // Define qualities array
    const qualities = ['4K', '1080p', '720p', '480p', '360p'];
    const currentDateTime = '2025-02-04 01:43:37';
    const currentUser = 'ehababdo';

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

    // Add new episode with season selection
    $('.add-episode').on('click', function() {
        var episodeCount = $('.episode-block').length + 1;
        var lastSeason = $('.episode-block').last().find('.season-number').val() || 1;
        var template = `
            <div class="episode-block">
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
                if (deletedEpisodesInput.length === 0) {
                    $('form.mlm-form').append('<input type="hidden" name="deleted_episodes" value="">');
                    deletedEpisodesInput = $('input[name="deleted_episodes"]');
                }
                
                var deletedEpisodes = deletedEpisodesInput.val().split(',').filter(Boolean);
                deletedEpisodes.push(episodeId);
                deletedEpisodesInput.val(deletedEpisodes.join(','));
            }
            
            episodeBlock.remove();
            updateEpisodeTitles();
        }
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

    // Delete series confirmation
    $('.mlm-delete-series').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this series? All episodes will also be deleted. This action cannot be undone.')) {
            var seriesId = $(this).data('id');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_series',
                    series_id: seriesId,
                    nonce: '<?php echo wp_create_nonce("delete_series_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error deleting series');
                    }
                },
                error: function() {
                    alert('Server error occurred');
                }
            });
        }
    });

    // Form validation
    $('form.mlm-form').on('submit', function(e) {
        var title = $('#title').val().trim();
        if (!title) {
            e.preventDefault();
            alert('Series title is required.');
            $('#title').focus();
            return false;
        }
    });
});
</script>
<style>
.mlm-grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.series-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.series-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.series-thumbnail {
    position: relative;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
    background: #f5f5f5;
}

.series-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-thumbnail {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-thumbnail .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ddd;
}

.series-info {
    padding: 15px;
}

.series-title {
    margin: 0 0 10px 0;
    font-size: 16px;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.series-meta {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    background: #edf7ed;
    color: #1e4620;
}

.status-inactive {
    background: #fef2f2;
    color: #991b1b;
}

.episodes-count {
    font-size: 12px;
    color: #666;
}

.series-actions {
    display: flex;
    gap: 5px;
}

.series-actions .button {
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.series-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.no-items-found {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Search Box Styling */
.search-box {
    margin-bottom: 20px;
}

.search-box input[type="search"] {
    width: 300px;
    padding: 6px 10px;
}

/* Pagination Styling */
.tablenav-pages {
    margin-top: 20px;
    padding: 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Responsive Design */
@media screen and (max-width: 782px) {
    .mlm-grid-view {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }

    .search-box input[type="search"] {
        width: 100%;
        max-width: 300px;
    }
}
</style>
