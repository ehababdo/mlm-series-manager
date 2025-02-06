<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="mlm-tmdb-search-box">
        <form id="mlm-tmdb-search-form">
            <input type="text" 
                   id="mlm-tmdb-search" 
                   placeholder="Search movies or TV series..." 
                   required>
            
            <select id="mlm-tmdb-type">
                <option value="tv">TV Series</option>
                <option value="movie">Movies</option>
            </select>

            <button type="submit" class="button button-primary">
                Search TMDB
            </button>
        </form>
    </div>

    <div id="mlm-tmdb-results"></div>
</div>