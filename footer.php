</main>
<?php
// Contact band above the footer (the homepage and the contact page already end with the form).
$blab_route = blab_current_route();
if ( 'home' !== $blab_route && 'contact' !== $blab_route ) :
    ?>
<section class="blab-cta"><div class="blab-cta-inner"><div><p class="eyebrow">Contact</p><h2>Get In Touch</h2><p>So we can assist your next creative project!</p></div><a class="blab-btn gold" href="<?php echo esc_url( home_url( user_trailingslashit( '/contact' ) ) ); ?>">Contact Us</a></div></section>
    <?php
endif;
echo blab_footer_part(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme HTML and escaped form markup.
wp_footer();
?>
</body></html>
