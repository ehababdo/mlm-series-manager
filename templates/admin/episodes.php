<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-04 13:34:21';
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
?>

<style>
.mlm-episodes-grid {
    max-width: 1200px;
    margin: 20px auto;
}

.episodes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.episodes-filters {
    display: flex;
    gap: 15px;
    align-items: center;
}

.episodes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.episode-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.episode-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.episode-thumbnail {
    position: relative;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
    background: #f0f0f0;
}

.episode-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.episode-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    background: #fff;
    color: #1e1e1e;
}

.episode-info {
    padding: 15px;
}

.episode-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 10px 0;
    color: #1e1e1e;
}

.episode-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.episode-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.episode-actions a {
    flex: 1;
    text-align: center;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s ease;
}

.episode-actions .edit-episode {
    background: #f0f0f0;
    color: #1e1e1e;
}

.episode-actions .edit-episode:hover {
    background: #e0e0e0;
}

.episode-actions .delete-episode {
    background: #dc3232;
    color: #fff;
}

.episode-actions .delete-episode:hover {
    background: #c92929;
}

.no-episodes {
    text-align: center;
    padding: 40px;
    background: #fff;
    border-radius: 8px;
}

/* Filter Styles */
.season-filter {
    min-width: 150px;
}

.search-episodes {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 200px;
}

/* Add Episode Button */
.add-new-episode {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #2271b1;
    color: #fff;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.2s ease;
}

.add-new-episode:hover {
    background: #135e96;
    color: #fff;
}

.add-new-episode .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<div class="wrap mlm-episodes-grid">
    <div class="episodes-header">
        <h1 class="wp-heading-inline">
            Episodes for: <?php echo esc_html($series->title); ?>
        </h1>
        
        <div class="episodes-filters">
            <input type="text" class="search-episodes" placeholder="Search episodes...">
            <select class="season-filter">
                <option value="">All Seasons</option>
                <?php
                $seasons = array_unique(array_map(function($episode) {
                    return $episode->season_number;
                }, $episodes));
                sort($seasons);
                foreach ($seasons as $season): ?>
                    <option value="<?php echo esc_attr($season); ?>">
                        Season <?php echo esc_html($season); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="<?php echo admin_url('admin.php?page=mlm-episodes&action=add&series_id=' . $series_id); ?>" 
               class="add-new-episode">
                <span class="dashicons dashicons-plus-alt2"></span>
                Add New Episode
            </a>
        </div>
    </div>

    <?php if ($episodes): ?>
        <div class="episodes-grid">
            <?php foreach ($episodes as $episode): ?>
                <div class="episode-card" data-season="<?php echo esc_attr($episode->season_number); ?>">
                    <div class="episode-thumbnail">
                        <?php if ($episode->thumbnail): ?>
                            <img src="<?php echo esc_url($episode->thumbnail); ?>" 
                                 alt="Episode <?php echo esc_attr($episode->episode_number); ?>">
                        <?php else: ?>
                            <img src="<?php echo MLM_PLUGIN_URL; ?>assets/images/episode-placeholder.jpg" 
                                 alt="Episode placeholder">
                        <?php endif; ?>
                        <span class="episode-status">
                            <?php echo count($episode->video_links); ?> Links
                        </span>
                    </div>
                    <div class="episode-info">
                        <h3 class="episode-title">
                            S<?php echo esc_html($episode->season_number); ?>E<?php echo esc_html($episode->episode_number); ?>: 
                            <?php echo esc_html($episode->title); ?>
                        </h3>
                        <div class="episode-meta">
                            <span>Duration: <?php echo esc_html($episode->duration); ?> min</span>
                            <span>Air Date: <?php echo esc_html($episode->air_date); ?></span>
                        </div>
                        <div class="episode-actions">
                            <a href="<?php echo admin_url('admin.php?page=mlm-episodes&action=edit&id=' . $episode->id); ?>" 
                               class="edit-episode">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </a>
                            <a href="#" class="delete-episode" data-id="<?php echo $episode->id; ?>">
                                <span class="dashicons dashicons-trash"></span> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-episodes">
            <p>No episodes found. Start by adding your first episode!</p>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Search functionality
    $('.search-episodes').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.episode-card').each(function() {
            var title = $(this).find('.episode-title').text().toLowerCase();
            $(this).toggle(title.includes(searchTerm));
        });
    });

    // Season filter
    $('.season-filter').on('change', function() {
        var season = $(this).val();
        if (season) {
            $('.episode-card').hide();
            $('.episode-card[data-season="' + season + '"]').show();
        } else {
            $('.episode-card').show();
        }
    });

    // Delete episode
    $('.delete-episode').on('click', function(e) {
        e.preventDefault();
        var episodeId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this episode? This action cannot be undone.')) {
            // Add your delete logic here
            window.location.href = `<?php echo admin_url('admin.php?page=mlm-episodes&action=delete&id='); ?>${episodeId}`;
        }
    });
});
</script>