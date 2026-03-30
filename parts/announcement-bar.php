<?php
/**
 * Template Part: Announcement Bar
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text     = get_option( 'ss_announcement_text', '' );
$url      = get_option( 'ss_announcement_url', '' );
$style    = get_option( 'ss_announcement_style', 'default' );
$position = get_option( 'ss_announcement_position', 'header' );
$icon     = get_option( 'ss_announcement_icon', 'none' );

if ( empty( $text ) ) {
	return;
}

$icons = array(
	'megaphone' => '<svg class="announcement-bar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
	'tag'       => '<svg class="announcement-bar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
	'sparkle'   => '<svg class="announcement-bar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
	'clock'     => '<svg class="announcement-bar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
);

$icon_svg = ( 'none' !== $icon && isset( $icons[ $icon ] ) ) ? $icons[ $icon ] : '';
?>
<div class="announcement-bar" data-style="<?php echo esc_attr( $style ); ?>" data-position="<?php echo esc_attr( $position ); ?>" role="complementary" aria-label="<?php esc_attr_e( 'Announcement', 'sender-symposium' ); ?>">
	<?php if ( ! empty( $url ) ) : ?>
		<a href="<?php echo esc_url( $url ); ?>" class="announcement-bar__inner">
			<?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG ?>
			<span><?php echo wp_kses_post( $text ); ?></span>
		</a>
	<?php else : ?>
		<span class="announcement-bar__inner">
			<?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG ?>
			<span><?php echo wp_kses_post( $text ); ?></span>
		</span>
	<?php endif; ?>
</div>
