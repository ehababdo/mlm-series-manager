<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$stats = mlm_get_statistics();
$current_user = wp_get_current_user();
$user_activities = mlm_get_user_activity(get_current_user_id(), 5);
?>

<div class="wrap mlm-dashboard">
    <h1>
        <span class="dashicons dashicons-video-alt3"></span> 
        Media Library Manager Dashboard
    </h1>

    <!-- Overview Statistics Cards -->
    <div class="mlm-stats-grid">
        <!-- Movies Card -->
        <div class="mlm-stat-card">
            <div class="mlm-stat-header">
                <span class="dashicons dashicons-video-alt2"></span>
                <h2>Movies</h2>
            </div>
            <div class="mlm-stat-body">
                <div class="mlm-stat-number"><?php echo esc_html($stats['movies']['total']); ?></div>
                <div class="mlm-stat-label">Total Movies</div>
                <div class="mlm-stat-active">
                    <?php echo esc_html($stats['movies']['active']); ?> Active
                </div>
            </div>
            <div class="mlm-stat-footer">
                <a href="<?php echo admin_url('admin.php?page=mlm-movies'); ?>" class="button button-primary">
                    Manage Movies
                </a>
            </div>
        </div>

        <!-- TV Series Card -->
        <div class="mlm-stat-card">
            <div class="mlm-stat-header">
                <span class="dashicons dashicons-playlist-video"></span>
                <h2>TV Series</h2>
            </div>
            <div class="mlm-stat-body">
                <div class="mlm-stat-number"><?php echo esc_html($stats['series']['total']); ?></div>
                <div class="mlm-stat-label">Total Series</div>
                <div class="mlm-stat-active">
                    <?php echo esc_html($stats['series']['active']); ?> Active
                </div>
            </div>
            <div class="mlm-stat-footer">
                <a href="<?php echo admin_url('admin.php?page=mlm-series'); ?>" class="button button-primary">
                    Manage Series
                </a>
            </div>
        </div>

        <!-- Episodes Card -->
        <div class="mlm-stat-card">
            <div class="mlm-stat-header">
                <span class="dashicons dashicons-playlist-audio"></span>
                <h2>Episodes</h2>
            </div>
            <div class="mlm-stat-body">
                <div class="mlm-stat-number"><?php echo esc_html($stats['episodes']['total']); ?></div>
                <div class="mlm-stat-label">Total Episodes</div>
            </div>
            <div class="mlm-stat-footer">
                <a href="<?php echo admin_url('admin.php?page=mlm-series'); ?>" class="button button-primary">
                    Manage Episodes
                </a>
            </div>
        </div>

        <!-- TV Channels Card -->
        <div class="mlm-stat-card">
            <div class="mlm-stat-header">
                <span class="dashicons dashicons-desktop"></span>
                <h2>TV Channels</h2>
            </div>
            <div class="mlm-stat-body">
                <div class="mlm-stat-number"><?php echo esc_html($stats['channels']['total']); ?></div>
                <div class="mlm-stat-label">Total Channels</div>
                <div class="mlm-stat-active">
                    <?php echo esc_html($stats['channels']['active']); ?> Active
                </div>
            </div>
            <div class="mlm-stat-footer">
                <a href="<?php echo admin_url('admin.php?page=mlm-channels'); ?>" class="button button-primary">
                    Manage Channels
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Quick Actions -->
    <div class="mlm-dashboard-grid">
        <!-- Recent Activities -->
        <div class="mlm-dashboard-column">
            <div class="mlm-card">
                <h2>Recent Activities</h2>
                <div class="mlm-activities-list">
                    <?php if (!empty($user_activities)) : ?>
                        <?php foreach ($user_activities as $activity) : ?>
                            <div class="mlm-activity-item">
                                <span class="mlm-activity-icon dashicons 
                                    <?php echo esc_attr($activity->type === 'movie' ? 'dashicons-video-alt2' : 
                                        ($activity->type === 'series' ? 'dashicons-playlist-video' : 'dashicons-desktop')); ?>">
                                </span>
                                <div class="mlm-activity-details">
                                    <div class="mlm-activity-title">
                                        <?php echo esc_html($activity->title); ?>
                                    </div>
                                    <div class="mlm-activity-meta">
                                        <?php echo esc_html(ucfirst($activity->type)); ?> - 
                                        <?php echo esc_html(human_time_diff(strtotime($activity->created_at), current_time('timestamp'))); ?> ago
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No recent activities found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mlm-dashboard-column">
            <div class="mlm-card">
                <h2>Quick Actions</h2>
                <div class="mlm-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=mlm-movies&action=add'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        Add New Movie
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mlm-series&action=add'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        Add New Series
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mlm-channels&action=add'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        Add New Channel
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mlm-statistics'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-chart-bar"></span>
                        View Statistics
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mlm-settings'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Settings
                    </a>
                </div>
            </div>

            <!-- Latest Updates -->
            <div class="mlm-card">
                <h2>Latest Updates</h2>
                <div class="mlm-updates-list">
                    <h3>Recent Movies</h3>
                    <ul>
                        <?php foreach ($stats['movies']['latest'] as $movie) : ?>
                            <li>
                                <a href="<?php echo admin_url('admin.php?page=mlm-movies&action=edit&id=' . $movie->id); ?>">
                                    <?php echo esc_html($movie->title); ?>
                                </a>
                                <span class="mlm-meta">
                                    <?php echo esc_html(human_time_diff(strtotime($movie->created_at), current_time('timestamp'))); ?> ago
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <h3>Recent Series</h3>
                    <ul>
                        <?php foreach ($stats['series']['latest'] as $series) : ?>
                            <li>
                                <a href="<?php echo admin_url('admin.php?page=mlm-series&action=edit&id=' . $series->id); ?>">
                                    <?php echo esc_html($series->title); ?>
                                </a>
                                <span class="mlm-meta">
                                    <?php echo esc_html(human_time_diff(strtotime($series->created_at), current_time('timestamp'))); ?> ago
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <h3>Recent Episodes</h3>
                    <ul>
                        <?php foreach ($stats['episodes']['latest'] as $episode) : ?>
                            <li>
                                <a href="<?php echo admin_url('admin.php?page=mlm-series&action=edit&id=' . $episode->series_id); ?>">
                                    <?php echo esc_html($episode->series_title); ?> - 
                                    S<?php echo esc_html($episode->season_number); ?>E<?php echo esc_html($episode->episode_number); ?>
                                </a>
                                <span class="mlm-meta">
                                    <?php echo esc_html(human_time_diff(strtotime($episode->created_at), current_time('timestamp'))); ?> ago
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Styles */
.mlm-dashboard {
    margin: 20px;
}

.mlm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.mlm-stat-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
}

.mlm-stat-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.mlm-stat-header .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    margin-right: 10px;
    color: #2271b1;
}

.mlm-stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #2271b1;
}

.mlm-stat-label {
    color: #666;
    margin-bottom: 10px;
}

.mlm-stat-active {
    color: #46b450;
    font-size: 14px;
    margin-bottom: 15px;
}

.mlm-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.mlm-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.mlm-activities-list {
    max-height: 400px;
    overflow-y: auto;
}

.mlm-activity-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.mlm-activity-icon {
    margin-right: 10px;
    color: #2271b1;
}

.mlm-activity-meta {
    font-size: 12px;
    color: #666;
}

.mlm-quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.mlm-quick-actions .button {
    display: flex;
    align-items: center;
    justify-content: center;
}

.mlm-quick-actions .dashicons {
    margin-right: 5px;
}

.mlm-updates-list h3 {
    margin: 15px 0 10px;
    color: #2271b1;
}

.mlm-updates-list ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.mlm-updates-list li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.mlm-meta {
    font-size: 12px;
    color: #666;
    margin-left: 10px;
}
</style>