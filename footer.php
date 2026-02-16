<?php
/**
 * Footer template — Sender Symposium
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main><!-- #main-content -->

<?php
/*
 * Allow Elementor Theme Builder to override the footer.
 */
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) :
?>
<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">

			<div class="footer-col">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" rel="home">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				</a>
				<p class="text-small" style="margin-top: var(--sp-3); color: var(--text-muted); max-width: 32ch;">
					<?php echo esc_html( get_bloginfo( 'description' ) ); ?>
				</p>
			</div>

			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<div class="footer-col">
					<h3 class="footer-col__title"><?php esc_html_e( 'Event', 'sender-symposium' ); ?></h3>
					<?php
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-col__list',
						'depth'          => 1,
						'fallback_cb'    => false,
					) );
					?>
				</div>
			<?php endif; ?>

			<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
				<?php dynamic_sidebar( 'footer-widgets' ); ?>
			<?php endif; ?>

		</div>

		<div class="footer-bottom">
			<?php
			$footer_line_1 = trim( (string) ss_get_setting( 'footer_text_line_1', '' ) );
			$footer_line_2 = trim( (string) ss_get_setting( 'footer_text_line_2', '' ) );
			?>
			<?php if ( '' !== $footer_line_1 ) : ?>
				<p class="footer-text-line"><?php echo wp_kses_post( $footer_line_1 ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $footer_line_2 ) : ?>
				<p class="footer-text-line"><?php echo wp_kses_post( $footer_line_2 ); ?></p>
			<?php endif; ?>
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'sender-symposium' ); ?></span>
		</div>
	</div>
</footer>
<?php endif; ?>

<?php if ( defined( 'SS_GEEK_LAYER_ENABLED' ) && SS_GEEK_LAYER_ENABLED ) : ?>
<!--
  Curious minds tend to run better pipelines.
  If you are reading this, you probably segment with intent.
  emailexpert.io
-->
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
