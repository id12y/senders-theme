<?php
/**
 * Template Part: Announcement Bar
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text = get_option( 'ss_announcement_text', '' );
$url  = get_option( 'ss_announcement_url', '' );

if ( empty( $text ) ) {
	return;
}
?>
<div class="announcement-bar" role="region" aria-label="<?php esc_attr_e( 'Announcement', 'sender-symposium' ); ?>">
	<?php if ( ! empty( $url ) ) : ?>
		<a href="<?php echo esc_url( $url ); ?>">
			<?php echo wp_kses_post( $text ); ?>
		</a>
	<?php else : ?>
		<?php echo wp_kses_post( $text ); ?>
	<?php endif; ?>
</div>
