<?php
/**
 * One-click setup: pretty permalinks (needed for the original URLs), the original site title, the Media Library
 * import of every image and video, then the editable pages (Home as front page) and the Main menu.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function blab_setup_missing() {
    $missing = array();
    if ( '' === (string) get_option( 'permalink_structure' ) ) { $missing['permalinks'] = 'turn on pretty permalinks so the original URLs (/services, /projects, /contact …) work'; }
    if ( 'BlackLAB' !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "BlackLAB" (the original title)'; }
    if ( blab_media_pending() ) { $missing['media'] = 'add the website images and videos to the Media Library'; }
    $pages = blab_pages_pending();
    if ( $pages ) { $missing['pages'] = 'create the editable pages (Home, Services, Projects, Contact …) and the main menu'; }
    return $missing;
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    if ( isset( $_GET['blab_setup'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        echo '<div class="notice notice-success is-dismissible"><p><strong>Black Lab is set up.</strong> Every page is now under <a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">Pages</a> (Home is the front page) and the menu is under <a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">Appearance → Menus</a>. Edit text, images, buttons and videos in the block editor.</p></div>';
    }
    $missing = blab_setup_missing();
    if ( ! $missing ) { return; }
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=blab_setup' ), 'blab_setup' );
    echo '<div class="notice notice-warning"><p><strong>Black Lab setup:</strong> ' . esc_html( implode( '; ', $missing ) ) . '.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">Complete Black Lab setup</a></p></div>';
} );

add_action( 'admin_post_blab_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'blab_setup' );
    if ( '' === (string) get_option( 'permalink_structure' ) ) {
        update_option( 'permalink_structure', '/%postname%/' );
        flush_rewrite_rules( true );
    }
    if ( 'BlackLAB' !== get_option( 'blogname' ) ) {
        update_option( 'blogname', 'BlackLAB' );
        update_option( 'blogdescription', '' );
    }
    // Pages are created after the media import so their images and videos point at the Media Library.
    if ( blab_media_pending() ) { wp_safe_redirect( blab_media_import_url() ); exit; }
    blab_pages_seed();
    wp_safe_redirect( admin_url( 'edit.php?post_type=page&blab_setup=done' ) );
    exit;
} );
