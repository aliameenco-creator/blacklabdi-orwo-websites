<?php
/** Black Lab theme. Layouts are generated from the archived site; settings and enquiries live in WordPress. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/github-updater.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/media.php';
require_once __DIR__ . '/includes/pages.php';
require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/contact-form.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
    add_editor_style( array( 'assets/blab-editor.css' ) );
    register_nav_menus( array( 'primary' => 'Main menu' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'blab-theme', get_theme_file_uri( 'assets/blab.css' ), array(), $version );
    wp_enqueue_script( 'blab-theme', get_theme_file_uri( 'assets/blab.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/** Mark the document before first paint so animations never hide content when JavaScript is off; preload the brand fonts. */
add_action( 'wp_head', function () {
    echo "<script>document.documentElement.classList.add('js')</script>\n";
    foreach ( array( 'cormorant-normal-latin', 'manrope-normal-latin' ) as $font ) {
        echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/' . $font . '.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
    if ( ! has_site_icon() ) {
        $icon = esc_url( get_theme_file_uri( 'assets/blab-favicon.png' ) );
        echo '<link rel="icon" type="image/png" href="' . $icon . '"><link rel="apple-touch-icon" href="' . $icon . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
    }
}, 1 );

/** Fonts are bundled with the theme (no third-party font requests): Cormorant Garamond headings, Manrope text (SIL OFL). */
function blab_font_css() {
    $dir = esc_url( get_theme_file_uri( 'assets/fonts/' ) );
    $css = '';
    $latin_ext = 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF';
    $latin = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';
    foreach ( array( array( 'Cormorant Garamond', 'cormorant', 'normal', '300 700' ), array( 'Cormorant Garamond', 'cormorant', 'italic', '300 700' ), array( 'Manrope', 'manrope', 'normal', '200 800' ) ) as $f ) {
        foreach ( array( 'latin' => $latin, 'latin-ext' => $latin_ext ) as $subset => $range ) {
            $css .= "@font-face{font-family:'{$f[0]}';font-style:{$f[2]};font-weight:{$f[3]};font-display:swap;src:url({$dir}{$f[1]}-{$f[2]}-{$subset}.woff2) format('woff2');unicode-range:{$range}}";
        }
    }
    return $css;
}
add_action( 'wp_head', function () {
    echo '<style id="blab-fonts">' . blab_font_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped URL, fixed CSS.
}, 2 );
add_action( 'enqueue_block_editor_assets', function () {
    wp_add_inline_style( 'wp-block-library', blab_font_css() );
} );

add_filter( 'body_class', function ( $classes ) {
    if ( is_front_page() ) { $classes[] = 'blab-home'; }
    return $classes;
} );

/** Button styles offered in the editor (gold, outline, link) to match the design. */
add_action( 'init', function () {
    if ( ! function_exists( 'register_block_style' ) ) { return; }
    register_block_style( 'core/button', array( 'name' => 'gold', 'label' => 'Gold' ) );
    register_block_style( 'core/button', array( 'name' => 'link', 'label' => 'Text link' ) );
} );

/** Content uses root-relative links (/services); keep them valid on any install, in the site's trailing-slash form. */
add_filter( 'the_content', 'blab_links', 20 );
