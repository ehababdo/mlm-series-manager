/**
 * TMDB Integration JavaScript
 */
jQuery(document).ready(function($) {
    // Search TMDB
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

    // Import from TMDB
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
                    
                    // Reload page if needed
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                } else {
                    $button.html('Error')
                           .removeClass('button-primary')
                           .addClass('button-link-delete');
                }
            })
            .fail(function() {
                $button.html('Error')
                       .removeClass('button-primary')
                       .addClass('button-link-delete')
                       .prop('disabled', false);
            });
    });
});