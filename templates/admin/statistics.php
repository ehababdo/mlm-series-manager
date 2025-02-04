<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-04 02:22:36';
$current_user = 'ehababdo';

global $wpdb;
$series_table = $wpdb->prefix . 'mlm_series';
$episodes_table = $wpdb->prefix . 'mlm_episodes';
$channels_table = $wpdb->prefix . 'mlm_channels';

// Get date range from URL parameters
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

// Fetch statistics
$total_series = $wpdb->get_var("SELECT COUNT(*) FROM $series_table");
$total_episodes = $wpdb->get_var("SELECT COUNT(*) FROM $episodes_table");
$total_channels = $wpdb->get_var("SELECT COUNT(*) FROM $channels_table");

// Get series by genre
$series_by_genre = $wpdb->get_results("
    SELECT genre, COUNT(*) as count 
    FROM $series_table 
    GROUP BY genre 
    ORDER BY count DESC
");

// Get episodes by season
$episodes_by_season = $wpdb->get_results("
    SELECT season_number, COUNT(*) as count 
    FROM $episodes_table 
    GROUP BY season_number 
    ORDER BY season_number
");

// Get channels by category
$channels_by_category = $wpdb->get_results("
    SELECT category, COUNT(*) as count 
    FROM $channels_table 
    GROUP BY category 
    ORDER BY count DESC
");

// Get recent activities
$recent_series = $wpdb->get_results("
    SELECT title, created_at, created_by 
    FROM $series_table 
    ORDER BY created_at DESC 
    LIMIT 5
");

$recent_episodes = $wpdb->get_results("
    SELECT e.title, e.created_at, e.created_by, s.title as series_title 
    FROM $episodes_table e 
    LEFT JOIN $series_table s ON e.series_id = s.id 
    ORDER BY e.created_at DESC 
    LIMIT 5
");

$recent_channels = $wpdb->get_results("
    SELECT title, created_at, created_by 
    FROM $channels_table 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>

<div class="wrap mlm-statistics">
    <h1>MLM Statistics Dashboard</h1>

    <!-- Date Range Filter -->
    <div class="date-range-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="mlm-statistics">
            <label for="start_date">From:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            
            <label for="end_date">To:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            
            <button type="submit" class="button">Filter</button>
        </form>
    </div>

    <!-- Overview Cards -->
    <div class="stats-grid overview">
        <div class="stats-card">
            <div class="stats-icon series-icon">
                <span class="dashicons dashicons-video-alt2"></span>
            </div>
            <div class="stats-content">
                <h3>Total Series</h3>
                <div class="stats-number"><?php echo number_format($total_series); ?></div>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon episodes-icon">
                <span class="dashicons dashicons-playlist-video"></span>
            </div>
            <div class="stats-content">
                <h3>Total Episodes</h3>
                <div class="stats-number"><?php echo number_format($total_episodes); ?></div>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon channels-icon">
                <span class="dashicons dashicons-desktop"></span>
            </div>
            <div class="stats-content">
                <h3>Live Channels</h3>
                <div class="stats-number"><?php echo number_format($total_channels); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="stats-grid charts">
        <!-- Series by Genre -->
        <div class="stats-chart">
            <h3>Series by Genre</h3>
            <canvas id="seriesByGenre"></canvas>
        </div>

        <!-- Episodes by Season -->
        <div class="stats-chart">
            <h3>Episodes by Season</h3>
            <canvas id="episodesBySeason"></canvas>
        </div>

        <!-- Channels by Category -->
        <div class="stats-chart">
            <h3>Channels by Category</h3>
            <canvas id="channelsByCategory"></canvas>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="stats-grid activities">
        <!-- Recent Series -->
        <div class="stats-activity">
            <h3>Recent Series</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Added By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_series as $series): ?>
                        <tr>
                            <td><?php echo esc_html($series->title); ?></td>
                            <td><?php echo esc_html($series->created_by); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($series->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Episodes -->
        <div class="stats-activity">
            <h3>Recent Episodes</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Series</th>
                        <th>Added By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_episodes as $episode): ?>
                        <tr>
                            <td><?php echo esc_html($episode->title); ?></td>
                            <td><?php echo esc_html($episode->series_title); ?></td>
                            <td><?php echo esc_html($episode->created_by); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($episode->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Channels -->
        <div class="stats-activity">
            <h3>Recent Channels</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Added By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_channels as $channel): ?>
                        <tr>
                            <td><?php echo esc_html($channel->title); ?></td>
                            <td><?php echo esc_html($channel->created_by); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($channel->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.mlm-statistics {
    margin: 20px;
}

.date-range-filter {
    background: #fff;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.date-range-filter label {
    margin-right: 10px;
}

.date-range-filter input[type="date"] {
    margin-right: 15px;
}

.stats-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.stats-grid.overview {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.stats-grid.charts {
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
}

.stats-grid.activities {
    grid-template-columns: 1fr;
}

.stats-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stats-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #fff;
}

.series-icon {
    background: #4caf50;
}

.episodes-icon {
    background: #2196f3;
}

.channels-icon {
    background: #ff9800;
}

.stats-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
}

.stats-number {
    font-size: 24px;
    font-weight: 600;
    color: #23282d;
}

.stats-chart {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.stats-activity {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.stats-activity h3 {
    margin-top: 0;
    margin-bottom: 15px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Chart colors
    const colors = [
        '#4caf50', '#2196f3', '#ff9800', '#e91e63', 
        '#9c27b0', '#673ab7', '#3f51b5', '#00bcd4'
    ];

    // Series by Genre Chart
    new Chart(document.getElementById('seriesByGenre'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($series_by_genre, 'genre')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($series_by_genre, 'count')); ?>,
                backgroundColor: colors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Episodes by Season Chart
    new Chart(document.getElementById('episodesBySeason'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($episodes_by_season, 'season_number')); ?>,
            datasets: [{
                label: 'Episodes',
                data: <?php echo json_encode(array_column($episodes_by_season, 'count')); ?>,
                backgroundColor: colors[1]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Channels by Category Chart
    new Chart(document.getElementById('channelsByCategory'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($channels_by_category, 'category')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($channels_by_category, 'count')); ?>,
                backgroundColor: colors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});
</script>