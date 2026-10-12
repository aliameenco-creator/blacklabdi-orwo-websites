<?php
/** WordPress pages: the original pages carry their own hero; other pages get a heading band. */
get_header();
while ( have_posts() ) :
    the_post();
    $blab_hero = str_contains( (string) get_the_content(), 'blab-page-hero' );
    if ( ! $blab_hero ) { blab_page_head( get_the_title() ); }
    echo '<div class="blab-content' . ( $blab_hero ? '' : ' blab-prose' ) . '">';
    the_content();
    echo '</div>';
endwhile;
get_footer();
