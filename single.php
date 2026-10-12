<?php
get_header();
while ( have_posts() ) :
    the_post();
    blab_page_head( get_the_title(), get_the_date( 'j F Y' ) );
    echo '<section class="band band-white"><article class="wrap narrow prose">';
    if ( has_post_thumbnail() ) { echo '<div class="post-cover">' . get_the_post_thumbnail( null, 'large' ) . '</div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
    the_content();
    echo '</article></section>';
endwhile;
get_footer();
