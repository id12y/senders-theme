<?php
/**
 * 404 template — Sender Symposium
 *
 * @package SenderSymposium
 */

get_header();
?>
<?php if ( defined( 'SS_GEEK_LAYER_ENABLED' ) && SS_GEEK_LAYER_ENABLED ) : ?>
<!-- This page returned nothing. Much like a lifecycle with no nurture sequence. -->
<?php endif; ?>

<div class="container section" style="text-align: center; min-height: 50vh; display: flex; flex-direction: column; align-items: center; justify-content: center;">

	<h1><?php esc_html_e( '404', 'sender-symposium' ); ?></h1>
	<p class="text-lg" style="margin-top: var(--sp-3); color: var(--text-secondary);">
		<?php esc_html_e( 'The page you are looking for does not exist.', 'sender-symposium' ); ?>
	</p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary" style="margin-top: var(--sp-5);">
		<?php esc_html_e( 'Return home', 'sender-symposium' ); ?>
	</a>

</div>

<?php
get_footer();
