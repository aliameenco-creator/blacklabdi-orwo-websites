<?php
/**
 * Original SEO data (titles as on the old site) for the pages the theme serves itself, from parts/seo.json.
 * The old site published no meta descriptions; none are invented. When an SEO plugin (Yoast, Rank Math,
 * AIOSEO, SEOPress) is active it owns the homepage tags and any WordPress page; the theme still titles the
 * routes it serves itself.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function blab_seo_data( $route ) {
    static $data = null;
    if ( null === $data ) {
        $file = get_theme_file_path( 'parts/seo.json' );
        $data = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $data[ $route ] ?? array();
}

function blab_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function blab_route_url( $route ) {
    // Match the address WordPress actually serves (trailing slash per the permalink setting), so canonical never points at a redirect.
    return 'home' === $route ? home_url( '/' ) : home_url( user_trailingslashit( '/' . $route ) );
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $route = blab_current_route();
    $virtual = ! empty( $GLOBALS['blab_route'] );
    if ( ! $route || ( blab_seo_plugin_active() && ! $virtual ) ) { return $title; }
    $seo = blab_seo_data( $route );
    return ! empty( $seo['title'] ) ? $seo['title'] : $title;
}, 99 );

add_action( 'wp_head', function () {
    $route = blab_current_route();
    if ( ! $route || blab_seo_plugin_active() || ! empty( $GLOBALS['blab_skip_seo'] ) ) { return; }
    $seo = blab_seo_data( $route );
    if ( ! $seo || ( is_singular( 'post' ) ) ) { return; }
    $url = blab_route_url( $route );
    $title = $seo['title'] ?? get_bloginfo( 'name' );
    $description = $seo['description'] ?? '';
    if ( '' !== $description ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
    if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; }
    echo '<meta property="og:type" content="website"><meta property="og:site_name" content="BlackLAB"><meta property="og:title" content="' . esc_attr( $title ) . '"><meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    if ( '' !== $description ) { echo '<meta property="og:description" content="' . esc_attr( $description ) . '"><meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n"; }
    $image = get_theme_file_uri( 'assets/blab-share.jpg' );
    echo '<meta property="og:image" content="' . esc_url( $image ) . '"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . esc_attr( $title ) . '"><meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
}, 3 );

/** Add the theme-served original URLs to WordPress's XML sitemap (except those a real WordPress page already provides). */
add_action( 'init', function () {
    if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) { return; }
    wp_register_sitemap_provider( 'blab', new class() extends WP_Sitemaps_Provider {
        public function __construct() { $this->name = 'blab'; $this->object_type = 'blab'; }
        public function get_url_list( $page_num, $object_subtype = '' ) {
            $urls = array( array( 'loc' => home_url( '/' ) ) );
            foreach ( blab_routes() as $route ) {
                if ( ! get_page_by_path( $route ) ) { $urls[] = array( 'loc' => blab_route_url( $route ) ); }
            }
            return $urls;
        }
        public function get_max_num_pages( $object_subtype = '' ) { return 1; }
    } );
} );
