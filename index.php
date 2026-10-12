<?php
/** Posts index, archives and search. */
get_header();
blab_page_head( is_search() ? 'Search: ' . get_search_query() : ( is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : 'News' ) );
echo '<section class="band band-white"><div class="wrap">';
if ( have_posts() ) {
    echo '<div class="post-grid">';
    while ( have_posts() ) {
        the_post();
        echo '<article class="post-card"><div class="post-body"><time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date( 'j F Y' ) ) . '</time><h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2><p>' . esc_html( wp_trim_words( get_the_excerpt(), 32 ) ) . '</p></div></article>';
    }
    echo '</div>';
    the_posts_pagination( array( 'mid_size' => 1 ) );
} else {
    echo '<div class="news-empty"><h2>Check back soon</h2><p>Once posts are published, you’ll see them here.</p></div>';
}
echo '</div></section>';
get_footer();
