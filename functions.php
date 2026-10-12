<?php
/** Black Lab theme. Layouts are generated from the archived site; settings and enquiries live in WordPress. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/github-updater.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/media.php';
require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/contact-form.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'editor-styles' );
    add_editor_style( array( 'assets/blab.css' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'blab-theme', get_theme_file_uri( 'assets/blab.css' ), array(), $version );
    wp_enqueue_script( 'blab-theme', get_theme_file_uri( 'assets/blab.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/** Mark the document before first paint so animations never hide content when JavaScript is off; preload the brand fonts. */
add_action( 'wp_head', function () {
    echo "<script>document.documentElement.classList.add('js')</script>\n";
    foreach ( array( 'anton', 'jost' ) as $font ) {
        echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/' . $font . '.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
    if ( ! has_site_icon() ) {
        $icon = esc_url( get_theme_file_uri( 'assets/blab-favicon.png' ) );
        echo '<link rel="icon" type="image/png" href="' . $icon . '"><link rel="apple-touch-icon" href="' . $icon . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
    }
}, 1 );

/** Fonts are bundled with the theme (no third-party font requests): Anton for headings, Jost for text. */
add_action( 'wp_head', function () {
    $dir = esc_url( get_theme_file_uri( 'assets/fonts/' ) );
    echo "<style id=\"blab-fonts\">@font-face{font-family:'Anton';font-style:normal;font-weight:400;font-display:swap;src:url({$dir}anton.woff2) format('woff2')}@font-face{font-family:'Jost';font-style:normal;font-weight:300 600;font-display:swap;src:url({$dir}jost.woff2) format('woff2')}</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped URL.
}, 2 );

add_filter( 'body_class', function ( $classes ) {
    if ( is_front_page() ) { $classes[] = 'blab-home'; }
    return $classes;
} );

/** Content uses root-relative links; keep them valid on subdirectory installs. */
add_filter( 'the_content', function ( $content ) {
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $content );
}, 20 );
