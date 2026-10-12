<?php
/** One-click setup: pretty permalinks (needed for the original URLs), the original site title and the Media Library import. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function blab_setup_missing() {
    $missing = array();
    if ( '' === (string) get_option( 'permalink_structure' ) ) { $missing['permalinks'] = 'turn on pretty permalinks so the original URLs (/services, /projects, /contact …) work'; }
    if ( 'BlackLAB' !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "BlackLAB" (the original title)'; }
    if ( blab_media_pending() ) { $missing['media'] = 'add the website images to the Media Library with descriptive file names and alt text (SEO)'; }
    return $missing;
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
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
    if ( blab_media_pending() ) { wp_safe_redirect( blab_media_import_url() ); exit; }
    wp_safe_redirect( home_url( '/' ) );
    exit;
} );
