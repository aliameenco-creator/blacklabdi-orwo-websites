<?php
/** Pages created in WordPress (they take over an original URL when their slug matches). */
get_header();
while ( have_posts() ) :
    the_post();
    blab_page_head( get_the_title() );
    echo '<section class="band band-white"><article class="wrap narrow prose">';
    the_content();
    echo '</article></section>';
endwhile;
get_footer();
