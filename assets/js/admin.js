/**
 * Media Library Manager Admin Scripts
 * Version: 1.0.0
 * Current Date and Time (UTC): 2025-02-04 00:45:13
 * Author: ehababdo
 */

(function($) {
    'use strict';

    // Global variables
    const MLM = {
        currentUser: 'ehababdo',
        currentDateTime: '2025-02-04 00:45:13',
        ajaxurl: window.ajaxurl || '/wp-admin/admin-ajax.php',
        nonce: window.mlm_nonce || '',
        // Configuration options
        config: {
            qualities: ['4K', '1080p', '720p', '480p', '360p'],
            itemsPerPage: 25,
            episodesPerSeason: 12
        },        
        init: function() {
            this.initMediaUploader();
            this.initStreamingLinks();
            this.initEpisodeManagement();
            this.initDeleteActions();
            this.initFormValidation();
            this.initDatePickers();
            this.initSortableTables();
            this.initBulkActions();
            this.initSearchFilters();
        },

        // Media Uploader
        initMediaUploader: function() {
            let mediaUploader;

            $('.media-upload').on('click', function(e) {
                e.preventDefault();
                const button = $(this);
                const targetInput = $('#' + button.data('target'));

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Select or Upload Media',
                    button: {
                        text: 'Use this media'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url);
                    
                    // Preview image if available
                    const previewDiv = targetInput.siblings('.image-preview');
                    if (previewDiv.length) {
                        previewDiv.html(`<img src="${attachment.url}" alt="Preview" style="max-width: 150px;">`);
                    }
                });

                mediaUploader.open();
            });
        },

        // Streaming Links Management
        initStreamingLinks: function() {
            // Add new streaming link
            $(document).on('click', '.add-streaming-link', function() {
                const container = $(this).closest('.episode-links, #streaming-links');
                const template = MLM.getStreamingLinkTemplate(container.data('episode-id'));
                $(this).before(template);
            });

            // Remove streaming link
            $(document).on('click', '.remove-link', function() {
                const linksContainer = $(this).closest('.episode-links, #streaming-links');
                const linksCount = linksContainer.find('.streaming-link').length;

                if (linksCount > 1) {
                    $(this).closest('.streaming-link').remove();
                } else {
                    alert('At least one streaming link is required.');
                }
            });

            // URL validation
            $(document).on('change', 'input[name$="[video_urls][]"]', function() {
                const url = $(this).val();
                if (url && !MLM.isValidUrl(url)) {
                    alert('Please enter a valid URL');
                    $(this).val('').focus();
                }
            });
        },

        // Episode Management
        initEpisodeManagement: function() {
            // Add new episode
            $('.add-episode').on('click', function() {
                const episodeCount = $('.episode-block').length + 1;
                const seasonNumber = Math.ceil(episodeCount / 12); // Assuming 12 episodes per season
                const episodeNumber = episodeCount % 12 || 12;
                
                const template = MLM.getEpisodeTemplate(episodeCount, seasonNumber, episodeNumber);
                $(this).before(template);
            });

            // Toggle episode details
            $(document).on('click', '.episode-block h3', function() {
                $(this).siblings('.episode-content').slideToggle();
            });

            // Sort episodes
            if ($.fn.sortable) {
                $('#episodes-container').sortable({
                    handle: 'h3',
                    items: '.episode-block',
                    update: MLM.updateEpisodeNumbers
                });
            }
        },

        // Delete Actions
        initDeleteActions: function() {
            // Delete movie/series
            $('.mlm-delete-item').on('click', function(e) {
                e.preventDefault();
                
                const itemId = $(this).data('id');
                const itemType = $(this).data('type');
                
                if (!confirm(`Are you sure you want to delete this ${itemType}? This action cannot be undone.`)) {
                    return;
                }

                MLM.showLoader();

                $.ajax({
                    url: MLM.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mlm_delete_item',
                        id: itemId,
                        type: itemType,
                        nonce: MLM.nonce
                    },
                    success: function(response) {
                        MLM.hideLoader();
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || 'Error deleting item');
                        }
                    },
                    error: function() {
                        MLM.hideLoader();
                        alert('Server error occurred');
                    }
                });
            });
        },

        // Form Validation
        initFormValidation: function() {
            $('form.mlm-form').on('submit', function(e) {
                const form = $(this);
                let isValid = true;
                let firstError = null;

                // Clear previous errors
                $('.mlm-error').remove();
                $('.mlm-error-field').removeClass('mlm-error-field');

                // Validate required fields
                form.find('[required]').each(function() {
                    if (!$(this).val().trim()) {
                        isValid = false;
                        $(this).addClass('mlm-error-field');
                        $(this).after(`<span class="mlm-error">${$(this).attr('name')} is required</span>`);
                        
                        if (!firstError) {
                            firstError = $(this);
                        }
                    }
                });

                // Validate URLs
                form.find('input[type="url"]').each(function() {
                    const url = $(this).val().trim();
                    if (url && !MLM.isValidUrl(url)) {
                        isValid = false;
                        $(this).addClass('mlm-error-field');
                        $(this).after('<span class="mlm-error">Please enter a valid URL</span>');
                        
                        if (!firstError) {
                            firstError = $(this);
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    firstError.focus();
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                }
            });
        },

        // Date Pickers
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.date-picker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        // Sortable Tables
        initSortableTables: function() {
            if ($.fn.dataTable) {
                $('.mlm-table').DataTable({
                    order: [[0, 'desc']],
                    pageLength: 25,
                    responsive: true
                });
            }
        },

        // Bulk Actions
        initBulkActions: function() {
            $('#doaction, #doaction2').on('click', function(e) {
                const action = $(this).prev('select').val();
                if (action === '-1') {
                    return false;
                }

                const items = $('input[name="item[]"]:checked');
                if (items.length === 0) {
                    alert('Please select items to perform this action');
                    return false;
                }

                if (action === 'delete' && !confirm('Are you sure you want to delete the selected items?')) {
                    return false;
                }
            });
        },

        // Search Filters
        initSearchFilters: function() {
            $('.mlm-filter').on('change', function() {
                $(this).closest('form').submit();
            });
        },

        // Utility Functions
        getStreamingLinkTemplate: function(episodeId = '') {
            const prefix = episodeId ? `episode[${episodeId}]` : '';
            const qualityOptions = this.config.qualities.map(quality => 
                `<option value="${quality}">${quality}</option>`
            ).join('');

            return `
                <div class="streaming-link">
                    <input type="url" name="${prefix}[video_urls][]" placeholder="Video URL" required>
                    <select name="${prefix}[video_qualities][]">
                        ${qualityOptions}
                    </select>
                    <input type="text" name="${prefix}[server_names][]" placeholder="Server Name">
                    <button type="button" class="button remove-link">Remove</button>
                </div>
            `;
        },

        getEpisodeTemplate: function(count, seasonNum, episodeNum) {
            return `
                <div class="episode-block">
                    <h3>Season ${seasonNum} Episode ${episodeNum}</h3>
                    <div class="episode-content">
                        <input type="hidden" name="episode[new_${count}][season]" value="${seasonNum}">
                        <input type="hidden" name="episode[new_${count}][number]" value="${episodeNum}">
                        
                        <div class="mlm-form-row">
                            <label>Episode Title</label>
                            <input type="text" name="episode[new_${count}][title]" required>
                        </div>

                        <div class="mlm-form-row">
                            <label>Description</label>
                            <textarea name="episode[new_${count}][description]" rows="3"></textarea>
                        </div>

                        <div class="mlm-form-row">
                            <label>Duration (minutes)</label>
                            <input type="number" name="episode[new_${count}][duration]">
                        </div>

                        <div class="mlm-form-row">
                            <label>Air Date</label>
                            <input type="date" name="episode[new_${count}][air_date]">
                        </div>

                        <div class="episode-links" data-episode-id="new_${count}">
                            <h4>Streaming Links</h4>
                            ${this.getStreamingLinkTemplate(`new_${count}`)}
                            <button type="button" class="button add-episode-link">Add Streaming Link</button>
                        </div>
                    </div>
                </div>
            `;
        },

        updateEpisodeNumbers: function() {
            $('.episode-block').each(function(index) {
                const seasonNum = Math.ceil((index + 1) / 12);
                const episodeNum = (index + 1) % 12 || 12;
                $(this).find('h3').text(`Season ${seasonNum} Episode ${episodeNum}`);
            });
        },

        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        showLoader: function() {
            if (!$('.mlm-loader').length) {
                $('body').append('<div class="mlm-loader"></div>');
            }
            $('.mlm-loader').show();
        },

        hideLoader: function() {
            $('.mlm-loader').hide();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MLM.init();
    });

})(jQuery);