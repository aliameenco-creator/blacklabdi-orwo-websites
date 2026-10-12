<?php
/**
 * Editable WordPress pages for every original page (parts/pages.json + parts/pages/<slug>.html) and the main menu.
 * The setup button creates them once, at their original addresses, after the images and videos are in the Media
 * Library. Existing published pages are never overwritten; a page an editor deletes is not re-created.
 * WordPress's untouched sample "Privacy Policy" draft is replaced by the site's own Privacy Policy page.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function blab_pages_data() {
    static $pages = null;
    if ( null === $pages ) {
        $file = get_theme_file_path( 'parts/pages.json' );
        $pages = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $pages;
}

/** Page with this slug in any status (including the trash), or 0. */
function blab_page_by_slug( $slug ) {
    $ids = get_posts( array( 'post_type' => 'page', 'name' => $slug, 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ), 'numberposts' => 1, 'fields' => 'ids' ) );
    if ( ! $ids ) { $ids = get_posts( array( 'post_type' => 'page', 'name' => $slug . '__trashed', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids' ) ); }
    return $ids ? (int) $ids[0] : 0;
}

/** WordPress's own sample privacy page, still a draft with the "Suggested text" template. */
function blab_sample_privacy_page() {
    $id = (int) get_option( 'wp_page_for_privacy_policy' );
    if ( ! $id || 'draft' !== get_post_status( $id ) ) { return 0; }
    return str_contains( (string) get_post_field( 'post_content', $id ), 'Suggested text' ) ? $id : 0;
}

function blab_pages_pending() {
    $pending = array();
    foreach ( blab_pages_data() as $page ) {
        if ( get_option( 'blab_page_' . $page['slug'] ) ) { continue; } // created before (maybe deleted by an editor since)
        $existing = blab_page_by_slug( $page['slug'] );
        if ( $existing && ! ( ! empty( $page['privacy'] ) && $existing === blab_sample_privacy_page() ) ) { continue; }
        $pending[] = $page;
    }
    if ( ! get_option( 'blab_menu_created' ) ) { $pending[] = array( 'slug' => '_menu' ); }
    return $pending;
}

function blab_pages_seed() {
    if ( ! current_user_can( 'edit_pages' ) ) { return 0; }
    $created = 0;
    foreach ( blab_pages_pending() as $page ) {
        if ( '_menu' === $page['slug'] ) { continue; }
        $postarr = array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_content' => blab_page_markup( $page['slug'] ),
        );
        $sample = ! empty( $page['privacy'] ) ? blab_sample_privacy_page() : 0;
        if ( $sample ) { $postarr['ID'] = $sample; }
        $id = $sample ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
        if ( is_wp_error( $id ) ) { continue; }
        update_option( 'blab_page_' . $page['slug'], (int) $id, false );
        if ( ! empty( $page['seo_title'] ) ) { update_post_meta( $id, '_yoast_wpseo_title', $page['seo_title'] ); }
        if ( ! empty( $page['front'] ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', (int) $id );
        }
        if ( ! empty( $page['privacy'] ) ) { update_option( 'wp_page_for_privacy_policy', (int) $id ); }
        blab_mark_seeded( $id );
        ++$created;
    }
    blab_menu_seed();
    blab_trash_samples();
    return $created;
}

/** Content version of the theme's page designs (theme version that last changed them). */
function blab_content_version() {
    $file = get_theme_file_path( 'parts/content-version.txt' );
    return is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : '0';
}

/** Remember which design version a page holds and a fingerprint of it, to tell later whether anyone edited it. */
function blab_mark_seeded( $id ) {
    update_post_meta( $id, '_blab_seed_version', blab_content_version() );
    update_post_meta( $id, '_blab_seed_hash', md5( (string) get_post_field( 'post_content', $id ) ) );
}

/**
 * Pages created by setup whose design is older than the theme's: [slug => [id, title, state]], state "unchanged"
 * (nobody edited it since setup) or "edited". Pages from 0.2.0 carry no fingerprint: they count as unchanged when
 * they were never modified after creation.
 */
function blab_pages_outdated() {
    $out = array();
    foreach ( blab_pages_data() as $page ) {
        $id = (int) get_option( 'blab_page_' . $page['slug'] );
        if ( ! $id || 'publish' !== get_post_status( $id ) || get_post_meta( $id, '_blab_seed_version', true ) === blab_content_version() ) { continue; }
        $hash = get_post_meta( $id, '_blab_seed_hash', true );
        if ( $hash ) {
            $unchanged = md5( (string) get_post_field( 'post_content', $id ) ) === $hash;
        } else {
            $post = get_post( $id );
            $unchanged = abs( strtotime( $post->post_modified_gmt ) - strtotime( $post->post_date_gmt ) ) < 180
                || ( ! empty( $page['privacy'] ) && ! str_contains( $post->post_content, '<!-- wp:paragraph' ) && str_contains( $post->post_content, 'blab-hero-plain' ) );
        }
        $out[ $page['slug'] ] = array( $id, $page['title'], $unchanged ? 'unchanged' : 'edited' );
    }
    return $out;
}

/** Replace a page's content with the theme's current design (WordPress keeps the previous version as a revision). */
function blab_page_refresh( $slug ) {
    $id = (int) get_option( 'blab_page_' . $slug );
    if ( ! $id || ! current_user_can( 'edit_page', $id ) ) { return false; }
    $result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => blab_page_markup( $slug ) ) ), true );
    if ( is_wp_error( $result ) ) { return false; }
    blab_mark_seeded( $id );
    return true;
}

/** Appearance → Black Lab Pages: update pages to a newer theme design, page by page. */
add_action( 'admin_menu', function () {
    add_theme_page( 'Black Lab Pages', 'Black Lab Pages', 'edit_pages', 'blab-pages', 'blab_pages_screen' );
} );

function blab_pages_screen() {
    if ( ! current_user_can( 'edit_pages' ) ) { return; }
    echo '<div class="wrap"><h1>Black Lab Pages</h1>';
    if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        echo '<div class="notice notice-success"><p>' . (int) $_GET['updated'] . ' page(s) updated to the new design. The previous version of each is saved under Revisions in the page editor.</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    if ( blab_media_pending() ) {
        $url = wp_nonce_url( admin_url( 'admin-post.php?action=blab_setup' ), 'blab_setup' );
        echo '<p>This theme version has new images or videos. Add them to the Media Library first.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">Complete Black Lab setup</a></p></div>';
        return;
    }
    $outdated = blab_pages_outdated();
    if ( ! $outdated ) { echo '<p>All pages created by the theme are up to date with version ' . esc_html( blab_content_version() ) . '.</p></div>'; return; }
    echo '<p>The theme has an improved design for these pages. Pages nobody has edited are ticked. Updating a page you edited replaces your changes (you can restore them from the page\'s Revisions).</p>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="blab_pages_refresh">';
    wp_nonce_field( 'blab_pages_refresh' );
    echo '<table class="widefat striped" style="max-width:720px"><thead><tr><th style="width:40px"></th><th>Page</th><th>Status</th></tr></thead><tbody>';
    foreach ( $outdated as $slug => $row ) {
        echo '<tr><td><input type="checkbox" name="pages[]" value="' . esc_attr( $slug ) . '"' . checked( 'unchanged' === $row[2], true, false ) . '></td><td><a href="' . esc_url( get_edit_post_link( $row[0] ) ) . '">' . esc_html( $row[1] ) . '</a></td><td>' . ( 'unchanged' === $row[2] ? 'Not edited since setup' : '<strong>Edited</strong> — your changes would be replaced' ) . '</td></tr>';
    }
    echo '</tbody></table><p><button class="button button-primary" type="submit">Update selected pages</button></p></form></div>';
}

add_action( 'admin_post_blab_pages_refresh', function () {
    if ( ! current_user_can( 'edit_pages' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'blab_pages_refresh' );
    $slugs = array_map( 'sanitize_key', (array) wp_unslash( $_POST['pages'] ?? array() ) );
    $outdated = blab_pages_outdated();
    $done = 0;
    foreach ( $slugs as $slug ) {
        if ( isset( $outdated[ $slug ] ) && blab_page_refresh( $slug ) ) { ++$done; }
    }
    wp_safe_redirect( admin_url( 'themes.php?page=blab-pages&updated=' . $done ) );
    exit;
} );

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'edit_pages' ) || ( isset( $_GET['page'] ) && 'blab-pages' === $_GET['page'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( blab_pages_pending() || ! blab_pages_outdated() ) { return; }
    echo '<div class="notice notice-info"><p><strong>Black Lab:</strong> an updated design is available for your pages. <a class="button" href="' . esc_url( admin_url( 'themes.php?page=blab-pages' ) ) . '">Review and update pages</a></p></div>';
} );

/** WordPress's untouched "Hello world!" post and "Sample Page" would show on News and in search: move them to the trash. */
function blab_trash_samples() {
    foreach ( array( array( 'post', 'hello-world', 'Welcome to WordPress' ), array( 'page', 'sample-page', 'This is an example page' ) ) as $sample ) {
        $posts = get_posts( array( 'post_type' => $sample[0], 'name' => $sample[1], 'post_status' => 'publish', 'numberposts' => 1 ) );
        if ( $posts && str_contains( $posts[0]->post_content, $sample[2] ) ) { wp_trash_post( $posts[0]->ID ); }
    }
}

/** Main menu (Appearance → Menus): the original navigation with the Services dropdown. */
function blab_menu_seed() {
    if ( get_option( 'blab_menu_created' ) ) { return; }
    $locations = get_theme_mod( 'nav_menu_locations', array() );
    if ( ! empty( $locations['primary'] ) && wp_get_nav_menu_object( $locations['primary'] ) ) { update_option( 'blab_menu_created', 1, false ); return; }
    $menu = wp_get_nav_menu_object( 'Main menu' );
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu( 'Main menu' );
    if ( is_wp_error( $menu_id ) ) { return; }
    $add = function ( $slug, $title, $parent = 0 ) use ( $menu_id ) {
        $page = get_page_by_path( $slug );
        $args = array( 'menu-item-title' => $title, 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent );
        if ( $page && 'publish' === $page->post_status ) {
            $args += array( 'menu-item-object' => 'page', 'menu-item-object-id' => $page->ID, 'menu-item-type' => 'post_type' );
        } else {
            $args += array( 'menu-item-type' => 'custom', 'menu-item-url' => 'home' === $slug ? home_url( '/' ) : home_url( user_trailingslashit( '/' . $slug ) ) );
        }
        return (int) wp_update_nav_menu_item( $menu_id, 0, $args );
    };
    $add( 'home', 'Home' );
    $services = $add( 'services', 'Services' );
    foreach ( array( 'post-production' => 'Post Production', 'colour-grading' => 'Colour Grading', 'copy-of-post-production' => 'Film Restoration', 'copy-of-post-production-1' => 'Digital Lab' ) as $slug => $title ) { $add( $slug, $title, $services ); }
    $add( 'projects', 'Projects' );
    $add( 'blog', 'News' );
    $add( 'contact', 'Contact' );
    $locations['primary'] = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );
    update_option( 'blab_menu_created', 1, false );
}

/** Fallback menu (same structure as WordPress menus) until the Main menu exists. */
function blab_fallback_menu() {
    $current = blab_current_route();
    $children = array( 'post-production' => 'Post Production', 'colour-grading' => 'Colour Grading', 'copy-of-post-production' => 'Film Restoration', 'copy-of-post-production-1' => 'Digital Lab' );
    $item = function ( $slug, $title, $extra = '' ) use ( $current ) {
        $on = $slug === $current ? ' current-menu-item' : '';
        $url = 'home' === $slug ? home_url( '/' ) : home_url( user_trailingslashit( '/' . $slug ) );
        return '<li class="menu-item' . $on . '"><a href="' . esc_url( $url ) . '"' . ( $on ? ' aria-current="page"' : '' ) . '>' . esc_html( $title ) . '</a>' . $extra . '</li>';
    };
    $sub = '<ul class="sub-menu">';
    foreach ( $children as $slug => $title ) { $sub .= $item( $slug, $title ); }
    $sub .= '</ul>';
    $services = $item( 'services', 'Services', $sub );
    $services = str_replace( 'class="menu-item', 'class="menu-item menu-item-has-children' . ( isset( $children[ $current ] ) ? ' current-menu-ancestor' : '' ), $services );
    return '<ul class="menu">' . $item( 'home', 'Home' ) . $services . $item( 'projects', 'Projects' ) . $item( 'blog', 'News' ) . $item( 'contact', 'Contact' ) . '</ul>';
}
