<?php
/** Theme-owned layout parts generated from the archived Black Lab site (tools/build_black_lab_theme.py). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Original URL slugs served by the theme (blacklabdi.com paths, unchanged). */
function blab_routes() {
    return array( 'services', 'post-production', 'colour-grading', 'copy-of-post-production', 'copy-of-post-production-1', 'projects', 'blog', 'contact', 'privacy-policy', 'terms-conditions', 'about-7' );
}

/** Resolve our controlled /assets/ tokens and root-relative links, on any (sub)directory install. */
function blab_theme_fragment( $html ) {
    $html = blab_media_swap( $html );
    $html = preg_replace_callback( '~(src|href|poster|data-video|data-full)="(/assets/[^"<>]+)"~', function ( $m ) {
        return $m[1] . '="' . esc_url( get_theme_file_uri( ltrim( $m[2], '/' ) ) ) . '"';
    }, $html );
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        // Page paths such as /services get the site's trailing-slash form so they never bounce through a redirect.
        $path = preg_match( '~^/[a-z0-9-]+$~D', $m[1] ) ? user_trailingslashit( $m[1] ) : $m[1];
        return 'href="' . esc_url( home_url( $path ) ) . '"';
    }, $html );
}

/** Path of a generated part. Only fixed names are accepted, never user-controlled paths. */
function blab_part_file( $name ) {
    if ( ! preg_match( '~^(?:header|footer|home|pages/[a-z0-9-]+)$~D', $name ) ) { return ''; }
    $file = get_theme_file_path( 'parts/' . $name . '.html' );
    return is_readable( $file ) ? $file : '';
}

/** Recent posts for the News page (the original News page had none yet: "Check back soon"). */
function blab_posts_markup() {
    $query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 12, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
    if ( ! $query->have_posts() ) {
        return '<div class="news-empty"><h2>Check back soon</h2><p>Once posts are published, you’ll see them here.</p></div>';
    }
    $html = '<div class="post-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $thumb = get_the_post_thumbnail( null, 'large', array( 'loading' => 'lazy' ) );
        $html .= '<article class="post-card" data-reveal>' . ( $thumb ? '<a class="post-thumb" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>' : '' ) . '<div class="post-body"><time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date( 'j F Y' ) ) . '</time><h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2><p>' . esc_html( wp_trim_words( get_the_excerpt(), 28 ) ) . '</p></div></article>';
    }
    wp_reset_postdata();
    return $html . '</div>';
}

function blab_theme_part( $name, $return = false ) {
    $file = blab_part_file( $name );
    if ( ! $file ) { return ''; }
    $html = blab_theme_fragment( (string) file_get_contents( $file ) );
    // Copyright year is always the current year (never a stale year copied from the old site).
    $html = str_replace( '<!--blab:year-->', esc_html( wp_date( 'Y' ) ), $html );
    foreach ( array( 'contact', 'subscribe' ) as $form ) {
        if ( str_contains( $html, '<!--blab:form:' . $form . '-->' ) ) {
            ob_start();
            blab_form( $form );
            $html = str_replace( '<!--blab:form:' . $form . '-->', (string) ob_get_clean(), $html );
        }
    }
    if ( str_contains( $html, '<!--blab:posts-->' ) ) { $html = str_replace( '<!--blab:posts-->', blab_posts_markup(), $html ); }
    if ( 'header' === $name ) {
        $current = blab_current_route();
        $html = preg_replace_callback( '~<li data-routes="([^"]*)"~', function ( $m ) use ( $current ) {
            $on = $current && in_array( $current, explode( ' ', $m[1] ), true );
            return '<li' . ( $on ? ' class="active"' : '' ) . ' data-routes="' . $m[1] . '"';
        }, $html );
    }
    if ( $return ) { return $html; }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme HTML, not user input.
    return '';
}

/** Heading band for WordPress-managed pages, posts and archives (same look as the original inner pages). */
function blab_page_head( $title, $intro = '' ) {
    echo '<section class="band band-black page-head"><div class="wrap narrow"><h1 class="display">' . esc_html( $title ) . '</h1>' . ( $intro ? '<p>' . esc_html( $intro ) . '</p>' : '' ) . '</div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
}

/** Request path relative to the site root, e.g. "contact". */
function blab_request_path() {
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only compared with fixed strings.
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}

/** Original URLs the theme serves itself when no WordPress page with that slug exists. */
function blab_virtual_route() {
    if ( ! is_404() ) { return ''; }
    $path = blab_request_path();
    return in_array( $path, blab_routes(), true ) ? $path : '';
}

/** Old Wix leftovers: the duplicate homepage and the temporary /blacklab path prefix go to their canonical address. */
add_action( 'template_redirect', function () {
    if ( ! is_404() ) { return; }
    $path = blab_request_path();
    $target = '';
    if ( 'copy-of-home-1' === $path || 'blacklab/copy-of-home-1' === $path || 'blacklab' === $path ) { $target = '/'; }
    elseif ( str_starts_with( $path, 'blacklab/' ) && in_array( substr( $path, 9 ), blab_routes(), true ) ) { $target = '/' . substr( $path, 9 ); }
    if ( $target ) { wp_safe_redirect( '/' === $target ? home_url( '/' ) : home_url( user_trailingslashit( $target ) ), 301 ); exit; }
}, 0 );

add_action( 'template_redirect', function () {
    if ( ! blab_virtual_route() ) { return; }
    // Same behaviour as WordPress pages: /services goes to /services/ when the permalink structure ends with a slash.
    $raw = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only inspected.
    if ( 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && ! str_ends_with( $raw, '/' ) && str_ends_with( user_trailingslashit( '/x' ), '/' ) ) {
        wp_safe_redirect( home_url( user_trailingslashit( '/' . blab_virtual_route() ) ), 301 );
        exit;
    }
    $GLOBALS['blab_route'] = blab_virtual_route();
    global $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
}, 1 );
add_filter( 'template_include', function ( $template ) {
    return empty( $GLOBALS['blab_route'] ) ? $template : get_theme_file_path( 'template-route.php' );
} );

function blab_current_route() {
    if ( is_front_page() ) { return 'home'; }
    if ( ! empty( $GLOBALS['blab_route'] ) ) { return $GLOBALS['blab_route']; }
    if ( is_page() ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        return in_array( $slug, blab_routes(), true ) ? $slug : '';
    }
    if ( is_home() || is_singular( 'post' ) ) { return 'blog'; }
    return '';
}
