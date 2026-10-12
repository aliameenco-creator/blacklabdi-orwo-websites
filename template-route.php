<?php
/** Original URL served before its editable WordPress page exists: renders the same block content the page will get. */
get_header();
echo '<div class="blab-content">' . apply_filters( 'the_content', blab_page_markup( $GLOBALS['blab_route'] ?? '' ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme block markup.
get_footer();
