<?php
/**
 * Page content, header/footer parts and original URLs for the Black Lab theme.
 *
 * Every original page has block markup in parts/pages/<slug>.html (generated from the archived site). The setup
 * button turns it into a real, editable WordPress page (includes/pages.php); until that page exists the theme
 * renders the same markup at the original URL, so the site never shows a gap.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Original URL slugs (blacklabdi.com paths, unchanged). "home" is the front page. */
function blab_routes() {
    return array( 'services', 'post-production', 'colour-grading', 'copy-of-post-production', 'copy-of-post-production-1', 'projects', 'blog', 'contact', 'privacy-policy', 'terms-conditions', 'about-7' );
}

/** Media Library URL for an imported theme image/video key, or ''. */
function blab_media_url( $key ) {
    $ids = blab_media_ids();
    return isset( $ids[ $key ] ) ? (string) wp_get_attachment_url( $ids[ $key ] ) : '';
}

/**
 * Resolve the generated markup's tokens: image blocks get their Media Library attachment ID, and
 * /assets/img/… and /assets/video/… point at the Media Library copy (once imported) or the theme copy.
 */
function blab_resolve_blocks( $markup ) {
    $ids = blab_media_ids();
    $markup = preg_replace_callback( '~<!-- wp:image (\{[^}]*\}) -->(.*?)<!-- /wp:image -->~s', function ( $m ) use ( $ids ) {
        if ( ! preg_match( '~/assets/img/([a-z0-9]+)-\d+\.webp~', $m[2], $k ) || empty( $ids[ $k[1] ] ) ) { return $m[0]; }
        $id = (int) $ids[ $k[1] ];
        return '<!-- wp:image ' . str_replace( '"id":0', '"id":' . $id, $m[1] ) . ' -->' . str_replace( 'wp-image-0', 'wp-image-' . $id, $m[2] ) . '<!-- /wp:image -->';
    }, $markup );
    $markup = preg_replace_callback( '~/assets/img/([a-z0-9]+)-(\d+)\.webp~', function ( $m ) {
        $url = blab_media_url( $m[1] );
        return $url ? $url : get_theme_file_uri( 'assets/img/' . $m[1] . '-' . $m[2] . '.webp' );
    }, $markup );
    return preg_replace_callback( '~/assets/video/([a-z0-9-]+)\.mp4~', function ( $m ) {
        $url = blab_media_url( $m[1] );
        return $url ? $url : get_theme_file_uri( 'assets/video/' . $m[1] . '.mp4' );
    }, $markup );
}

/** Block markup of an original page (tokens resolved), or ''. Only fixed slugs are accepted. */
function blab_page_markup( $slug ) {
    if ( ! preg_match( '~^[a-z0-9-]+$~D', $slug ) ) { return ''; }
    $file = get_theme_file_path( 'parts/pages/' . $slug . '.html' );
    return is_readable( $file ) ? blab_resolve_blocks( (string) file_get_contents( $file ) ) : '';
}

/** Root-relative links (/services) become full links in the site's trailing-slash form. */
function blab_links( $html ) {
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        $path = preg_match( '~^/[a-z0-9-]+$~D', $m[1] ) ? user_trailingslashit( $m[1] ) : $m[1];
        return 'href="' . esc_url( home_url( $path ) ) . '"';
    }, $html );
}

/** Footer part: generated HTML with the logo, subscribe form and the current year. */
function blab_footer_part() {
    $file = get_theme_file_path( 'parts/footer.html' );
    $html = is_readable( $file ) ? blab_links( blab_resolve_blocks( (string) file_get_contents( $file ) ) ) : '';
    // Copyright year is always the current year (never a stale year copied from the old site).
    $html = str_replace( '<!--blab:year-->', esc_html( wp_date( 'Y' ) ), $html );
    ob_start();
    blab_form( 'subscribe' );
    return str_replace( '<!--blab:form:subscribe-->', (string) ob_get_clean(), $html );
}

function blab_logo_url() {
    $file = get_theme_file_path( 'parts/logo.txt' );
    return is_readable( $file ) ? blab_resolve_blocks( trim( (string) file_get_contents( $file ) ) ) : '';
}

/** Page heading band for pages without their own hero (posts, archives, pages created from scratch). */
function blab_page_head( $title, $intro = '' ) {
    echo '<section class="blab-page-hero blab-hero-plain"><div class="blab-hero-inner"><h1 class="blab-title">' . esc_html( $title ) . '</h1>' . ( $intro ? '<p class="lead">' . esc_html( $intro ) . '</p>' : '' ) . '</div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
}

/** Request path relative to the site root, e.g. "contact". */
function blab_request_path() {
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only compared with fixed strings.
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}

/**
 * Original URL the theme serves itself: no published WordPress page has that slug yet (a draft such as
 * WordPress's sample "Privacy Policy" must never stand in for the real page).
 */
function blab_virtual_route() {
    $path = blab_request_path();
    if ( ! in_array( $path, blab_routes(), true ) || is_preview() ) { return ''; }
    if ( is_404() ) { return $path; }
    if ( is_page() && 'publish' !== get_post_status( get_queried_object_id() ) ) { return $path; }
    return '';
}

/** Old Wix leftovers: the duplicate homepage and the temporary /blacklab path prefix go to their real address. */
add_action( 'template_redirect', function () {
    if ( ! is_404() ) { return; }
    $path = blab_request_path();
    $target = '';
    if ( 'copy-of-home-1' === $path || 'blacklab/copy-of-home-1' === $path || 'blacklab' === $path ) { $target = '/'; }
    elseif ( str_starts_with( $path, 'blacklab/' ) && in_array( substr( $path, 9 ), blab_routes(), true ) ) { $target = '/' . substr( $path, 9 ); }
    if ( $target ) { wp_safe_redirect( '/' === $target ? home_url( '/' ) : home_url( user_trailingslashit( $target ) ), 301 ); exit; }
}, 0 );

add_action( 'template_redirect', function () {
    $route = blab_virtual_route();
    if ( ! $route ) { return; }
    // Same behaviour as WordPress pages: /services goes to /services/ when the permalink structure ends with a slash.
    $raw = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only inspected.
    if ( 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && ! str_ends_with( $raw, '/' ) && str_ends_with( user_trailingslashit( '/x' ), '/' ) ) {
        wp_safe_redirect( home_url( user_trailingslashit( '/' . $route ) ), 301 );
        exit;
    }
    $GLOBALS['blab_route'] = $route;
    global $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
}, 1 );
add_filter( 'template_include', function ( $template ) {
    return empty( $GLOBALS['blab_route'] ) ? $template : get_theme_file_path( 'template-route.php' );
} );

/** Slug of the original page being shown ("home" for the front page), or ''. */
function blab_current_route() {
    if ( is_front_page() ) { return 'home'; }
    if ( ! empty( $GLOBALS['blab_route'] ) ) { return $GLOBALS['blab_route']; }
    if ( is_page() ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        return in_array( $slug, blab_routes(), true ) ? $slug : '';
    }
    if ( is_singular( 'post' ) || is_home() ) { return 'blog'; }
    return '';
}

/** Pages whose content starts with a hero let the header float over it. */
add_filter( 'body_class', function ( $classes ) {
    $hero = ! empty( $GLOBALS['blab_route'] ) || ( is_front_page() && 'page' !== get_option( 'show_on_front' ) );
    if ( ! $hero && is_singular() ) { $hero = str_contains( (string) get_post_field( 'post_content', get_queried_object_id() ), 'blab-page-hero' ); }
    if ( $hero ) { $classes[] = 'has-hero'; }
    return $classes;
} );

/** Shortcodes used inside the page content: the original forms and the News list. */
add_shortcode( 'blab_form', function ( $atts ) {
    $atts = shortcode_atts( array( 'id' => 'contact' ), $atts );
    ob_start();
    blab_form( sanitize_key( $atts['id'] ) );
    return (string) ob_get_clean();
} );

function blab_post_cards( $count ) {
    $query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => $count, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
    if ( ! $query->have_posts() ) { return ''; }
    $html = '<div class="blab-post-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $thumb = get_the_post_thumbnail( null, 'large', array( 'loading' => 'lazy' ) );
        $html .= '<article class="blab-post-card">' . ( $thumb ? '<a class="post-thumb" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>' : '' ) . '<div class="post-body"><time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date( 'j F Y' ) ) . '</time><h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3><p>' . esc_html( wp_trim_words( get_the_excerpt(), 24 ) ) . '</p></div></article>';
    }
    wp_reset_postdata();
    return $html . '</div>';
}

/** News page list. The original News page had no posts yet ("Check back soon"). */
add_shortcode( 'blab_posts', function () {
    $cards = blab_post_cards( 12 );
    return $cards ? $cards : '<div class="blab-news-empty"><h2>Check back soon</h2><p>Once posts are published, you’ll see them here.</p></div>';
} );

/** Latest news on the homepage: only shown once posts exist. */
add_shortcode( 'blab_latest_posts', function () {
    $cards = blab_post_cards( 3 );
    return $cards ? '<section class="blab-section blab-latest"><div class="blab-section-head"><p class="eyebrow">News</p><h2 class="wp-block-heading">News</h2></div>' . $cards . '</section>' : '';
} );
