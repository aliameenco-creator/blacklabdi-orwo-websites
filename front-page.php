<?php
/** Site root: the editable Home page once setup has created it, otherwise the original home layout. */
get_header();
if ( 'page' === get_option( 'show_on_front' ) && have_posts() ) {
    while ( have_posts() ) {
        the_post();
        echo '<div class="blab-content">';
        the_content();
        echo '</div>';
    }
} else {
    echo '<div class="blab-content">' . apply_filters( 'the_content', blab_page_markup( 'home' ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme block markup.
}
get_footer();
