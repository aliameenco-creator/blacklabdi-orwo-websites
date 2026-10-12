<?php
/** Original Black Lab URLs (services, projects, contact …) served from theme layouts generated from the archive. */
get_header();
blab_theme_part( 'pages/' . ( $GLOBALS['blab_route'] ?? '' ) );
get_footer();
