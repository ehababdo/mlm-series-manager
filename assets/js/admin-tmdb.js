jQuery(document).ready(function($) {
    // TMDB Search Form Handler
    $('#mlm-tmdb-search-form').on('submit', function(e) {
        e.preventDefault();
        const query = $('#mlm-tmdb-search').val();
        const type = $('#mlm-tmdb-type').val();
        
        if (!query) {
            alert('Please enter a search term');
            return;
        }

        const $results = $('#mlm-tmdb-results');
        $results.html('<div class="spinner is-active"></div>');

        searchTMDB(query, type)
            .done(function(response) {
                if (response.success && response.data.results) {
                    displaySearchResults(response.data.results, type);
                } else {
                    $results.html('<p>No results found</p>');
                }
            })
            .fail(function() {
                $results.html('<p>Error searching TMDB</p>');
            });
    });

    // Display Search Results
    function displaySearchResults(results, type) {
        const $results = $('#mlm-tmdb-results');
        let html = '<div class="mlm-tmdb-grid">';
        
        results.forEach(function(item) {
            const title = type === 'movie' ? item.title : item.name;
            const date = type === 'movie' ? item.release_date : item.first_air_date;
            const poster = item.poster_path ? 
                `https://image.tmdb.org/t/p/w185${item.poster_path}` : 
                mlm_admin.placeholder_image;

            html += `
                <div class="mlm-tmdb-item">
                    <div class="mlm-tmdb-poster">
                        <img src="${poster}" alt="${title}">
                    </div>
                    <div class="mlm-tmdb-info">
                        <h4>${title}</h4>
                        <p>${date ? new Date(date).getFullYear() : 'N/A'}</p>
                        <button type="button" 
                                class="button button-primary mlm-import-tmdb" 
                                data-id="${item.id}" 
                                data-type="${type}">
                            Import
                        </button>
                        ${type === 'tv' ? `
                            <button type="button"
                                    class="button mlm-import-episodes"
                                    data-id="${item.id}"
                                    data-title="${title}">
                                Import Episodes
                            </button>
                        ` : ''}
                    </div>
                </div>`;
        });

        html += '</div>';
        $results.html(html);
    }

    // Import Button Handler
    $(document).on('click', '.mlm-import-tmdb', function() {
        const $button = $(this);
        const tmdbId = $button.data('id');
        const type = $button.data('type');

        $button.prop('disabled', true)
               .html('<span class="spinner is-active"></span> Importing...');

        importFromTMDB(tmdbId, type)
            .done(function(response) {
                if (response.success) {
                    $button.html('Imported Successfully')
                           .removeClass('button-primary')
                           .addClass('button-secondary');
                } else {
                    $button.html('Error')
                           .removeClass('button-primary')
                           .addClass('button-link-delete')
                           .prop('disabled', false);
                }
            })
            .fail(function() {
                $button.html('Error')
                       .removeClass('button-primary')
                       .addClass('button-link-delete')
                       .prop('disabled', false);
            });
    });

    // Episodes Import Button Handler
    $(document).on('click', '.mlm-import-episodes', function() {
        const tmdbId = $(this).data('id');
        const seriesTitle = $(this).data('title');
        showEpisodesModal(tmdbId, seriesTitle);
    });

    // Modal Close Handler
    $('.mlm-modal-close').click(function() {
        $('#mlm-episodes-modal').hide();
    });

    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).hasClass('mlm-modal')) {
            $('.mlm-modal').hide();
        }
    });

    // Show Episodes Modal
    function showEpisodesModal(tmdbId, seriesTitle) {
        const $modal = $('#mlm-episodes-modal');
        const $seasonsList = $('#mlm-seasons-list');
        
        $seasonsList.html('<div class="spinner is-active"></div>');
        $modal.show();

        // Get series details including seasons
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlm_get_series_seasons',
                nonce: mlm_admin.nonce,
                tmdb_id: tmdbId
            },
            success: function(response) {
                if (response.success && response.data.seasons) {
                    displaySeasonsList(response.data.seasons, tmdbId, seriesTitle);
                } else {
                    $seasonsList.html('<p>No seasons found</p>');
                }
            },
            error: function() {
                $seasonsList.html('<p>Error loading seasons</p>');
            }
        });
    }

    // Display Seasons List
    function displaySeasonsList(seasons, tmdbId, seriesTitle) {
        const $seasonsList = $('#mlm-seasons-list');
        let html = '<div class="mlm-seasons-grid">';

        seasons.forEach(function(season) {
            const poster = season.poster_path ? 
                `https://image.tmdb.org/t/p/w185${season.poster_path}` : 
                mlm_admin.placeholder_image;

            html += `
                <div class="mlm-season-card">
                    <img src="${poster}" alt="Season ${season.season_number}">
                    <h3>Season ${season.season_number}</h3>
                    <p>${season.episode_count} Episodes</p>
                    <button type="button"
                            class="button button-primary mlm-import-season"
                            data-tmdb-id="${tmdbId}"
                            data-season="${season.season_number}"
                            data-series-title="${seriesTitle}">
                        Import Season ${season.season_number}
                    </button>
                </div>`;
        });

        html += '</div>';
        $seasonsList.html(html);
    }

    // Season Import Button Handler
    $(document).on('click', '.mlm-import-season', function() {
        const $button = $(this);
        const tmdbId = $button.data('tmdb-id');
        const seasonNumber = $button.data('season');
        
        $button.prop('disabled', true)
               .html('<span class="spinner is-active"></span> Importing...');

        // First get or create the series in our database
        importFromTMDB(tmdbId, 'tv')
            .done(function(response) {
                if (response.success) {
                    // Now import the season episodes
                    importSeasonEpisodes(tmdbId, response.data.series_id, seasonNumber)
                        .done(function(episodesResponse) {
                            if (episodesResponse.success) {
                                $button.html(`Imported ${episodesResponse.data.imported} Episodes`)
                                       .removeClass('button-primary')
                                       .addClass('button-secondary');
                            } else {
                                showImportError($button);
                            }
                        })
                        .fail(function() {
                            showImportError($button);
                        });
                } else {
                    showImportError($button);
                }
            })
            .fail(function() {
                showImportError($button);
            });
    });

    function showImportError($button) {
        $button.html('Error')
               .removeClass('button-primary')
               .addClass('button-link-delete')
               .prop('disabled', false);
    }

    // AJAX Functions
    function searchTMDB(query, type = 'tv', page = 1) {
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlm_search_tmdb',
                nonce: mlm_admin.nonce,
                query: query,
                type: type,
                page: page
            }
        });
    }

    function importFromTMDB(tmdbId, type = 'tv') {
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlm_import_from_tmdb',
                nonce: mlm_admin.nonce,
                tmdb_id: tmdbId,
                type: type
            }
        });
    }

    function importSeasonEpisodes(tmdbSeriesId, localSeriesId, seasonNumber) {
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlm_import_season_episodes',
                nonce: mlm_admin.nonce,
                tmdb_series_id: tmdbSeriesId,
                series_id: localSeriesId,
                season_number: seasonNumber
            }
        });
    }
});