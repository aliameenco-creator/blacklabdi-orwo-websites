<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0a0a0a"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to Main Content</a>
<header class="site-header" data-header>
    <div class="header-inner">
        <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="BlackLAB home"><img class="brand-logo" src="<?php echo esc_url( blab_logo_url() ); ?>" alt="BlackLAB" width="74" height="60"></a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navigation"><span class="bars" aria-hidden="true"></span><span class="label">Menu</span></button>
        <nav class="nav" id="navigation" aria-label="Main navigation">
            <?php
            if ( has_nav_menu( 'primary' ) ) {
                wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'menu', 'depth' => 2, 'fallback_cb' => false ) );
            } else {
                echo blab_fallback_menu(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function.
            }
            ?>
        </nav>
    </div>
</header>
<main id="main">
