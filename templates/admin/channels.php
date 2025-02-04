<?php
if (!defined('ABSPATH')) {
    exit;
}

// Current settings
$current_datetime = '2025-02-04 02:09:18';
$current_user = 'ehababdo';

global $wpdb;
$table_name = $wpdb->prefix . 'mlm_channels';

// Get action and channel ID from URL parameters
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$channel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$message = '';
$message_type = 'success';
$channel = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['mlm_channel_nonce']) || !wp_verify_nonce($_POST['mlm_channel_nonce'], 'mlm_channel_action')) {
        wp_die('Invalid nonce specified');
    }

    // Prepare channel data
    $channel_data = array(
        'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
        'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
        'logo' => isset($_POST['logo']) ? esc_url_raw($_POST['logo']) : '',
        'stream_url' => isset($_POST['stream_url']) ? esc_url_raw($_POST['stream_url']) : '',
        'backup_stream_url' => isset($_POST['backup_stream_url']) ? esc_url_raw($_POST['backup_stream_url']) : '',
        'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
        'language' => isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '',
        'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
        'updated_at' => $current_datetime,
        'updated_by' => $current_user
    );

    if (empty($channel_data['title'])) {
        $message = 'Channel title is required.';
        $message_type = 'error';
    } else {
        if ($action === 'add') {
            $channel_data['created_at'] = $current_datetime;
            $channel_data['created_by'] = $current_user;
            
            $result = $wpdb->insert($table_name, $channel_data);
            $channel_id = $wpdb->insert_id;
            
            if ($result !== false) {
                $message = 'Channel added successfully.';
                $action = 'edit';
            } else {
                $message = 'Error adding channel: ' . $wpdb->last_error;
                $message_type = 'error';
            }
        } elseif ($action === 'edit' && $channel_id) {
            $result = $wpdb->update($table_name, $channel_data, array('id' => $channel_id));
            if ($result !== false) {
                $message = 'Channel updated successfully.';
            } else {
                $message = 'Error updating channel: ' . $wpdb->last_error;
                $message_type = 'error';
            }
        }
    }
}

// Delete channel
if ($action === 'delete' && $channel_id) {
    $result = $wpdb->delete($table_name, array('id' => $channel_id));
    if ($result !== false) {
        wp_redirect(admin_url('admin.php?page=mlm-channels&message=deleted'));
        exit;
    }
}

// Get channel data for editing
if ($action === 'edit' && $channel_id) {
    $channel = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $channel_id
    ));
}

// Available options for select fields
$languages = array('English', 'Arabic', 'Spanish', 'French', 'German', 'Italian', 'Japanese', 'Korean', 'Chinese');
$categories = array(
    'sports' => 'Sports',
    'news' => 'News',
    'entertainment' => 'Entertainment',
    'movies' => 'Movies',
    'kids' => 'Kids',
    'music' => 'Music',
    'documentary' => 'Documentary'
);
?>

<div class="wrap mlm-channels">
    <h1 class="wp-heading-inline">
        <?php echo $action === 'list' ? 'Live Channels' : ($action === 'add' ? 'Add New Channel' : 'Edit Channel'); ?>
    </h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=mlm-channels&action=add'); ?>" class="page-title-action">
            Add New Channel
        </a>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <div class="mlm-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th width="60">Logo</th>
                        <th>Category</th>
                        <th>Language</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $channels = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
                    if ($channels):
                        foreach ($channels as $item):
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($item->title); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=mlm-channels&action=edit&id=' . $item->id); ?>">
                                            Edit
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="mlm-delete-channel" data-id="<?php echo $item->id; ?>">
                                            Delete
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if ($item->logo): ?>
                                    <img src="<?php echo esc_url($item->logo); ?>" 
                                         alt="<?php echo esc_attr($item->title); ?>" 
                                         style="max-width: 40px; height: auto;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($categories[$item->category] ?? $item->category); ?></td>
                            <td><?php echo esc_html($item->language); ?></td>
                            <td>
                                <span class="mlm-status mlm-status-<?php echo esc_attr($item->status); ?>">
                                    <?php echo esc_html(ucfirst($item->status)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=mlm-channels&action=edit&id=' . $item->id); ?>" 
                                   class="button button-small">
                                    Edit
                                </a>
                                <a href="#" class="button button-small button-link-delete mlm-delete-channel" 
                                   data-id="<?php echo $item->id; ?>">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="6">No channels found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <form method="post" action="" class="mlm-form">
            <?php wp_nonce_field('mlm_channel_action', 'mlm_channel_nonce'); ?>
            
            <div class="mlm-form-grid">
                <!-- Basic Information -->
                <div class="mlm-form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="mlm-form-row">
                        <label for="title">Channel Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo esc_attr($channel ? $channel->title : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?php 
                            echo esc_textarea($channel ? $channel->description : ''); 
                        ?></textarea>
                    </div>

                    <div class="mlm-form-row">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php 
                                    selected($channel ? $channel->category : '', $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Media Information -->
                <div class="mlm-form-section">
                    <h2>Media Information</h2>

                    <div class="mlm-form-row">
                        <label for="logo">Channel Logo</label>
                        <input type="url" id="logo" name="logo" class="regular-text" 
                               value="<?php echo esc_url($channel ? $channel->logo : ''); ?>">
                        <button type="button" class="button media-upload" data-target="logo">Upload Logo</button>
                    </div>

                    <div class="mlm-form-row">
                        <label for="stream_url">Stream URL</label>
                        <input type="url" id="stream_url" name="stream_url" class="regular-text" 
                               value="<?php echo esc_url($channel ? $channel->stream_url : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="backup_stream_url">Backup Stream URL</label>
                        <input type="url" id="backup_stream_url" name="backup_stream_url" class="regular-text" 
                               value="<?php echo esc_url($channel ? $channel->backup_stream_url : ''); ?>">
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
                                    selected($channel ? $channel->language : '', $lang); ?>>
                                    <?php echo esc_html($lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mlm-form-row">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" 
                               value="<?php echo esc_attr($channel ? $channel->country : ''); ?>">
                    </div>

                    <div class="mlm-form-row">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php selected($channel ? $channel->status : '', 'active'); ?>>
                                Active
                            </option>
                            <option value="inactive" <?php selected($channel ? $channel->status : '', 'inactive'); ?>>
                                Inactive
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mlm-form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo $action === 'add' ? 'Add Channel' : 'Update Channel'; ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=mlm-channels'); ?>" class="button">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

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

    // Delete channel confirmation
    $('.mlm-delete-channel').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this channel? This action cannot be undone.')) {
            var channelId = $(this).data('id');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_channel',
                    channel_id: channelId,
                    nonce: '<?php echo wp_create_nonce("delete_channel_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error deleting channel');
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
            alert('Channel title is required.');
            $('#title').focus();
            return false;
        }
    });
});
</script>