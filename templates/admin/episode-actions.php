<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-04 13:43:35';
$current_user = 'ehababdo';

function mlm_handle_episode_actions() {
    global $wpdb;
    $episodes_table = $wpdb->prefix . 'mlm_episodes';
    $episode_links_table = $wpdb->prefix . 'mlm_episode_links';

    // Verify nonce
    if (!isset($_POST['mlm_episode_nonce']) || !wp_verify_nonce($_POST['mlm_episode_nonce'], 'mlm_episode_editor')) {
        wp_die('Invalid nonce specified');
    }

    $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
    $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
    $series_id = isset($_POST['series_id']) ? intval($_POST['series_id']) : 0;

    if (!$series_id) {
        wp_die('Series ID is required');
    }

    // Handle episode data
    if (isset($_POST['episode']) && is_array($_POST['episode'])) {
        $episode_data = array(
            'series_id' => $series_id,
            'season_number' => intval($_POST['episode']['season']),
            'episode_number' => intval($_POST['episode']['number']),
            'title' => sanitize_text_field($_POST['episode']['title']),
            'description' => wp_kses_post($_POST['episode']['description']),
            'thumbnail' => isset($_POST['episode']['thumbnail']) ? esc_url_raw($_POST['episode']['thumbnail']) : '',
            'duration' => sanitize_text_field($_POST['episode']['duration']),
            'air_date' => sanitize_text_field($_POST['episode']['air_date']),
            'status' => 'active',
            'updated_at' => $current_datetime,
            'updated_by' => $current_user
        );

        if ($action === 'add') {
            // Add new episode
            $episode_data['created_at'] = $current_datetime;
            $episode_data['created_by'] = $current_user;
            
            $wpdb->insert($episodes_table, $episode_data);
            $episode_id = $wpdb->insert_id;

            if ($episode_id) {
                // Redirect to episodes list with success message
                wp_redirect(add_query_arg(array(
                    'page' => 'mlm-episodes',
                    'series_id' => $series_id,
                    'message' => 'added'
                ), admin_url('admin.php')));
                exit;
            }
        } else if ($action === 'edit' && $episode_id) {
            // Update existing episode
            $wpdb->update(
                $episodes_table,
                $episode_data,
                array('id' => $episode_id)
            );

            // Handle streaming links
            if ($episode_id) {
                // First, delete existing links
                $wpdb->delete($episode_links_table, array('episode_id' => $episode_id));

                // Then add new links
                if (isset($_POST['episode']['video_urls']) && is_array($_POST['episode']['video_urls'])) {
                    foreach ($_POST['episode']['video_urls'] as $key => $url) {
                        if (empty($url)) continue;

                        $wpdb->insert(
                            $episode_links_table,
                            array(
                                'episode_id' => $episode_id,
                                'video_url' => esc_url_raw($url),
                                'quality' => sanitize_text_field($_POST['episode']['video_qualities'][$key]),
                                'server_name' => sanitize_text_field($_POST['episode']['server_names'][$key]),
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

            // Redirect to episodes list with success message
            wp_redirect(add_query_arg(array(
                'page' => 'mlm-episodes',
                'series_id' => $series_id,
                'message' => 'updated'
            ), admin_url('admin.php')));
            exit;
        }
    }

    // Handle episode deletion
    if ($action === 'delete' && $episode_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_episode_' . $episode_id)) {
            wp_die('Invalid nonce specified');
        }

        // Delete streaming links first
        $wpdb->delete($episode_links_table, array('episode_id' => $episode_id));
        
        // Then delete the episode
        $wpdb->delete($episodes_table, array('id' => $episode_id));

        // Redirect to episodes list with success message
        wp_redirect(add_query_arg(array(
            'page' => 'mlm-episodes',
            'series_id' => $series_id,
            'message' => 'deleted'
        ), admin_url('admin.php')));
        exit;
    }
}
add_action('admin_init', 'mlm_handle_episode_actions');

// Add success messages
function mlm_episode_admin_notices() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'mlm-episodes' || !isset($_GET['message'])) {
        return;
    }

    $message = '';
    $type = 'success';

    switch ($_GET['message']) {
        case 'added':
            $message = 'Episode added successfully.';
            break;
        case 'updated':
            $message = 'Episode updated successfully.';
            break;
        case 'deleted':
            $message = 'Episode deleted successfully.';
            break;
        default:
            return;
    }

    printf(
        '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        esc_attr($type),
        esc_html($message)
    );
}
add_action('admin_notices', 'mlm_episode_admin_notices');

// Function to generate delete URL with nonce
function mlm_get_episode_delete_url($episode_id, $series_id) {
    return wp_nonce_url(
        add_query_arg(array(
            'page' => 'mlm-episodes',
            'action' => 'delete',
            'id' => $episode_id,
            'series_id' => $series_id
        ), admin_url('admin.php')),
        'delete_episode_' . $episode_id
    );
}

// Update the episode list page to use the new delete URL
function mlm_modify_episode_delete_links() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.delete-episode').on('click', function(e) {
            e.preventDefault();
            var episodeId = $(this).data('id');
            var seriesId = <?php echo isset($_GET['series_id']) ? intval($_GET['series_id']) : 0; ?>;
            
            if (confirm('Are you sure you want to delete this episode? This action cannot be undone.')) {
                window.location.href = '<?php echo admin_url('admin.php'); ?>?page=mlm-episodes&action=delete&id=' + 
                    episodeId + '&series_id=' + seriesId + '&_wpnonce=' + 
                    '<?php echo wp_create_nonce("delete_episode_"); ?>' + episodeId;
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'mlm_modify_episode_delete_links');