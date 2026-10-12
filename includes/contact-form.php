<?php
/**
 * Forms recreated from the original Wix forms (fields, order, labels and button text in parts/forms.json):
 * "contact" (First Name, Last Name, Email*, Message) and "subscribe" (the footer mailing list). Submissions are
 * saved as private "Enquiries" in wp-admin and emailed to the configured address. No third-party service is used.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function blab_form_definitions() {
    static $forms = null;
    if ( null === $forms ) {
        $file = get_theme_file_path( 'parts/forms.json' );
        $forms = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return is_array( $forms ) ? $forms : array();
}

function blab_contact_recipient() {
    $email = sanitize_email( (string) get_option( 'blab_contact_recipient', '' ) );
    return is_email( $email ) ? $email : 'info@blacklabdi.com';
}

add_action( 'init', function () {
    register_post_type( 'blab_enquiry', array(
        'labels'          => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry', 'menu_name' => 'Enquiries', 'all_items' => 'All enquiries', 'edit_item' => 'Enquiry' ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false,
        'menu_icon'       => 'dashicons-email-alt',
        'menu_position'   => 26,
        'supports'        => array( 'title' ),
        'capability_type' => 'post',
        'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap'    => true,
    ) );
} );

/** Recipient address under Settings > General. */
add_action( 'admin_init', function () {
    register_setting( 'general', 'blab_contact_recipient', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );
    add_settings_field( 'blab_contact_recipient', 'Black Lab form recipient', function () {
        echo '<input type="email" class="regular-text" name="blab_contact_recipient" value="' . esc_attr( get_option( 'blab_contact_recipient', '' ) ) . '" placeholder="info@blacklabdi.com"><p class="description">Contact and mailing-list submissions are emailed here and always saved under Enquiries. Leave empty for info@blacklabdi.com.</p>';
    }, 'general' );
} );

add_action( 'add_meta_boxes_blab_enquiry', function () {
    add_meta_box( 'blab_enquiry_fields', 'Submitted details', function ( $post ) {
        $values = (array) get_post_meta( $post->ID, '_blab_fields', true );
        $form = blab_form_definitions()[ get_post_meta( $post->ID, '_blab_form', true ) ?: 'contact' ] ?? array();
        echo '<table class="widefat striped"><tbody><tr><th style="width:180px">Form</th><td>' . esc_html( $form['title'] ?? 'Contact' ) . '</td></tr>';
        foreach ( $form['fields'] ?? array() as $field ) {
            echo '<tr><th>' . esc_html( $field['label'] ) . '</th><td style="white-space:pre-wrap">' . esc_html( $values[ $field['key'] ] ?? '' ) . '</td></tr>';
        }
        $mail = get_post_meta( $post->ID, '_blab_mail', true );
        echo '<tr><th>Email notification</th><td>' . esc_html( $mail ? $mail : 'unknown' ) . '</td></tr><tr><th>Page</th><td>' . esc_html( get_post_meta( $post->ID, '_blab_page', true ) ) . '</td></tr></tbody></table>';
    }, 'blab_enquiry', 'normal', 'high' );
} );
add_filter( 'manage_blab_enquiry_posts_columns', function ( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'Name / Email', 'blab_form' => 'Form', 'blab_mail' => 'Email sent', 'date' => 'Received' );
} );
add_action( 'manage_blab_enquiry_posts_custom_column', function ( $column, $id ) {
    if ( 'blab_form' === $column ) { echo esc_html( blab_form_definitions()[ get_post_meta( $id, '_blab_form', true ) ?: 'contact' ]['title'] ?? 'Contact' ); }
    if ( 'blab_mail' === $column ) { echo esc_html( get_post_meta( $id, '_blab_mail', true ) ); }
}, 10, 2 );

function blab_form_respond( $ok, $message, $errors = array(), $values = array(), $form_id = 'contact' ) {
    if ( str_contains( (string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'application/json' ) ) {
        wp_send_json( array( 'ok' => $ok, 'message' => $message, 'errors' => $errors ), $ok ? 200 : 422 );
    }
    $back = wp_get_referer() ? wp_get_referer() : home_url( '/contact' );
    $back = remove_query_arg( array( 'blab_form', 'blab_sent', 'blab_token' ), $back );
    $anchor = '#form-' . $form_id;
    if ( $ok ) {
        wp_safe_redirect( add_query_arg( array( 'blab_form' => $form_id, 'blab_sent' => '1' ), $back ) . $anchor );
        exit;
    }
    $token = wp_generate_password( 20, false );
    set_transient( 'blab_form_' . $token, array( 'message' => $message, 'errors' => $errors, 'values' => $values ), 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( array( 'blab_form' => $form_id, 'blab_token' => $token ), $back ) . $anchor );
    exit;
}

function blab_handle_form() {
    $forms = blab_form_definitions();
    $form_id = isset( $_POST['blab_form'] ) ? sanitize_key( wp_unslash( $_POST['blab_form'] ) ) : 'contact'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form; see anti-spam checks.
    if ( ! isset( $forms[ $form_id ] ) ) { $form_id = 'contact'; }
    $form = $forms[ $form_id ];
    $values = array();
    $errors = array();
    foreach ( $form['fields'] as $field ) {
        $raw = isset( $_POST[ 'blab_' . $field['key'] ] ) ? wp_unslash( $_POST[ 'blab_' . $field['key'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidationSanitization.InputNotSanitized -- Public form; sanitised below.
        $value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
        $values[ $field['key'] ] = $value;
        if ( $field['required'] && '' === trim( $value ) ) { $errors[ $field['key'] ] = $field['label'] . ' is required.'; }
        elseif ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) { $errors[ $field['key'] ] = 'Please enter a valid email address.'; }
        elseif ( mb_strlen( $value ) > (int) $field['max'] ) { $errors[ $field['key'] ] = $field['label'] . ' is too long (maximum ' . (int) $field['max'] . ' characters).'; }
    }
    // Spam protection that survives page caching: honeypot, minimum time on page (JavaScript), per-visitor rate limit.
    if ( ! empty( $_POST['blab_website'] ) ) { blab_form_respond( true, $form['thanks'], array(), array(), $form_id ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $elapsed = isset( $_POST['blab_elapsed'] ) ? (int) $_POST['blab_elapsed'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( $elapsed < 3000 ) {
        blab_form_respond( false, 'Your message could not be verified. Please wait a moment and submit again, or email info@blacklabdi.com.', array(), $values, $form_id );
    }
    $visitor = 'blab_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
    $count = (int) get_transient( $visitor );
    if ( $count >= 5 ) { blab_form_respond( false, 'Too many messages in a short time. Please try again later or email info@blacklabdi.com.', array(), $values, $form_id ); }
    if ( $errors ) { blab_form_respond( false, 'Please check the highlighted fields.', $errors, $values, $form_id ); }
    set_transient( $visitor, $count + 1, 10 * MINUTE_IN_SECONDS );

    $page = esc_url_raw( (string) wp_get_referer() );
    $name = trim( ( $values['first_name'] ?? '' ) . ' ' . ( $values['last_name'] ?? '' ) );
    $title = ( '' !== $name ? $name : $values['email'] ) . ( 'subscribe' === $form_id ? ' (mailing list)' : '' );
    $id = wp_insert_post( array( 'post_type' => 'blab_enquiry', 'post_status' => 'private', 'post_title' => $title ), true );
    if ( ! is_wp_error( $id ) ) {
        update_post_meta( $id, '_blab_fields', $values );
        update_post_meta( $id, '_blab_form', $form_id );
        update_post_meta( $id, '_blab_page', $page );
    }
    $lines = array();
    foreach ( $form['fields'] as $field ) { $lines[] = $field['label'] . ': ' . ( '' === $values[ $field['key'] ] ? '-' : $values[ $field['key'] ] ); }
    $lines[] = '';
    $lines[] = 'Sent from: ' . $page;
    $reply = '' !== $name ? str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $values['email'] . '>' : $values['email'];
    $subject = 'subscribe' === $form_id ? 'BlackLAB website mailing list sign-up: ' . $values['email'] : 'BlackLAB website enquiry from ' . ( '' !== $name ? $name : $values['email'] );
    $sent = wp_mail( blab_contact_recipient(), $subject, implode( "\n", $lines ), array( 'Reply-To: ' . $reply ) );
    if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_blab_mail', $sent ? 'sent to ' . blab_contact_recipient() : 'FAILED - check site email settings' ); }
    blab_form_respond( true, $form['thanks'], array(), array(), $form_id );
}
add_action( 'admin_post_nopriv_blab_form', 'blab_handle_form' );
add_action( 'admin_post_blab_form', 'blab_handle_form' );

/** Print one of the original forms ("contact" or "subscribe"). */
function blab_form( $form_id = 'contact' ) {
    $form = blab_form_definitions()[ $form_id ] ?? array();
    if ( ! $form ) { return; }
    $state = array( 'message' => '', 'errors' => array(), 'values' => array() );
    $active = isset( $_GET['blab_form'] ) ? sanitize_key( wp_unslash( $_GET['blab_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $mine = $active === $form_id;
    if ( $mine && isset( $_GET['blab_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = get_transient( 'blab_form_' . sanitize_key( wp_unslash( $_GET['blab_token'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_array( $saved ) ) { $state = $saved; }
    }
    $uid = $form_id . '-' . wp_unique_id();
    echo '<form class="blab-form form-' . esc_attr( $form_id ) . '" id="form-' . esc_attr( $form_id ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate data-blab-form>';
    echo '<div class="form-status" role="status" aria-live="polite">';
    if ( $mine && isset( $_GET['blab_sent'] ) ) { echo '<p class="form-success">' . esc_html( $form['thanks'] ) . '</p>'; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    elseif ( $state['message'] ) { echo '<p class="form-error">' . esc_html( $state['message'] ) . '</p>'; }
    echo '</div><input type="hidden" name="action" value="blab_form"><input type="hidden" name="blab_form" value="' . esc_attr( $form_id ) . '"><input type="hidden" name="blab_elapsed" value="" data-elapsed>';
    echo '<div class="hp" aria-hidden="true"><label>Website <input type="text" name="blab_website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">';
    foreach ( $form['fields'] as $field ) {
        $name = 'blab_' . $field['key'];
        $id = $name . '-' . $uid;
        $value = $mine ? ( $state['values'][ $field['key'] ] ?? '' ) : '';
        $error = $mine ? ( $state['errors'][ $field['key'] ] ?? '' ) : '';
        $attrs = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" maxlength="' . (int) $field['max'] . '"' . ( $field['required'] ? ' required aria-required="true"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );
        $auto = array( 'first_name' => 'given-name', 'last_name' => 'family-name', 'email' => 'email' );
        if ( isset( $auto[ $field['key'] ] ) ) { $attrs .= ' autocomplete="' . esc_attr( $auto[ $field['key'] ] ) . '"'; }
        if ( ! empty( $field['placeholder'] ) ) { $attrs .= ' placeholder="' . esc_attr( $field['placeholder'] ) . '" aria-label="' . esc_attr( $field['placeholder'] ) . '"'; }
        echo '<div class="field field-' . esc_attr( $field['key'] ) . ( 'textarea' === $field['type'] ? ' wide' : '' ) . ( 'email' === $field['type'] && 'contact' === $form_id ? ' wide' : '' ) . '">';
        if ( empty( $field['placeholder'] ) ) { echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . ( $field['required'] ? ' <span class="req" aria-hidden="true">*</span>' : '' ) . '</label>'; }
        if ( 'textarea' === $field['type'] ) { echo '<textarea rows="6"' . $attrs . '>' . esc_textarea( $value ) . '</textarea>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes escaped above.
        else { echo '<input type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $value ) . '"' . $attrs . '>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span class="field-error">' . esc_html( $error ) . '</span></div>';
    }
    echo '</div><div class="form-actions"><button class="btn gold" type="submit">' . esc_html( $form['submit'] ) . '</button></div>';
    echo '<noscript><p class="form-error">Please enable JavaScript to send this form, or email <a href="mailto:info@blacklabdi.com">info@blacklabdi.com</a>.</p></noscript></form>';
}
