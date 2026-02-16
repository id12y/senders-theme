<?php
/**
 * Hero Block — Render Function
 *
 * Called via ss_render_hero( $post_id ) from front-page.php.
 * If hero is disabled → renders nothing.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the hero block.
 *
 * @param int $post_id The page ID to read hero settings from.
 */
function ss_render_hero( $post_id ) {
	$s = ss_get_hero_settings( $post_id );

	if ( ! $s['enabled'] ) {
		return;
	}

	$is_image = 'image' === $s['mode'] && ! empty( $s['background']['image_id'] );

	/* Build section classes */
	$classes   = array( 'ss-hero' );
	$classes[] = 'ss-hero--' . $s['layout']['alignment'];
	$classes[] = 'ss-hero--' . $s['layout']['content_width'];
	$classes[] = 'ss-hero--' . $s['layout']['spacing'];
	if ( $is_image ) {
		$classes[] = 'ss-hero--image';
		$classes[] = 'ss-hero--overlay-' . $s['background']['overlay_strength'];
	}
	?>
	<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

		<?php /* Background image + overlay */ ?>
		<?php if ( $is_image ) :
			$bg_url = wp_get_attachment_image_url( $s['background']['image_id'], 'full' );
			if ( $bg_url ) : ?>
			<img class="ss-hero__bg"
				src="<?php echo esc_url( $bg_url ); ?>"
				alt="" loading="eager" fetchpriority="high" aria-hidden="true">
			<div class="ss-hero__overlay"></div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="ss-hero__inner container">

			<?php /* Logo */ ?>
			<?php if ( $s['logo']['enabled'] ) :
				$logo_light = '';
				$logo_dark  = '';

				if ( $s['logo']['image_id'] ) {
					$logo_light = wp_get_attachment_image_url( $s['logo']['image_id'], 'medium' );
				}
				if ( ! $logo_light && has_custom_logo() ) {
					$custom_logo_id = get_theme_mod( 'custom_logo' );
					$logo_light     = wp_get_attachment_image_url( $custom_logo_id, 'medium' );
				}
				if ( $s['logo']['dark_image_id'] ) {
					$logo_dark = wp_get_attachment_image_url( $s['logo']['dark_image_id'], 'medium' );
				}

				$site_name = get_bloginfo( 'name' );
				$has_dark  = ! empty( $logo_dark );

				if ( $logo_light ) : ?>
				<div class="ss-hero__logo">
					<img class="<?php echo $has_dark ? 'ss-logo-light' : ''; ?>"
						src="<?php echo esc_url( $logo_light ); ?>"
						alt="<?php echo esc_attr( $site_name ); ?>"
						loading="eager">
					<?php if ( $has_dark ) : ?>
					<img class="ss-logo-dark"
						src="<?php echo esc_url( $logo_dark ); ?>"
						alt="<?php echo esc_attr( $site_name ); ?>"
						loading="eager">
					<?php endif; ?>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php /* Eyebrow */ ?>
			<?php if ( ! empty( $s['eyebrow'] ) ) : ?>
				<p class="ss-hero__eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
			<?php endif; ?>

			<?php /* Headline — single H1 */ ?>
			<?php if ( ! empty( $s['headline'] ) ) : ?>
				<h1 class="ss-hero__headline"><?php echo esc_html( $s['headline'] ); ?></h1>
			<?php endif; ?>

			<?php /* Subheadline */ ?>
			<?php if ( ! empty( $s['subheadline'] ) ) : ?>
				<p class="ss-hero__subheadline"><?php echo esc_html( $s['subheadline'] ); ?></p>
			<?php endif; ?>

			<?php /* Body */ ?>
			<?php if ( ! empty( $s['body'] ) ) : ?>
				<div class="ss-hero__body"><?php echo wp_kses_post( $s['body'] ); ?></div>
			<?php endif; ?>

			<?php /* CTAs */ ?>
			<?php
			$has_primary   = ! empty( $s['primary_cta']['label'] ) && ! empty( $s['primary_cta']['url'] );
			$has_secondary = $s['secondary_cta']['enabled']
				&& ! empty( $s['secondary_cta']['label'] )
				&& ! empty( $s['secondary_cta']['url'] );

			if ( $has_primary || $has_secondary ) : ?>
			<div class="ss-hero__ctas">
				<?php if ( $has_primary ) :
					$btn_class = 'solid' === $s['primary_cta']['style']
						? 'btn btn--primary' : 'btn btn--secondary';
				?>
				<a class="<?php echo esc_attr( $btn_class ); ?>"
					href="<?php echo esc_url( $s['primary_cta']['url'] ); ?>">
					<?php echo esc_html( $s['primary_cta']['label'] ); ?>
				</a>
				<?php endif; ?>

				<?php if ( $has_secondary ) :
					$sec_class = 'outline' === $s['secondary_cta']['style']
						? 'btn btn--secondary' : 'ss-hero__cta-text';
				?>
				<a class="<?php echo esc_attr( $sec_class ); ?>"
					href="<?php echo esc_url( $s['secondary_cta']['url'] ); ?>">
					<?php echo esc_html( $s['secondary_cta']['label'] ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php /* Microcopy */ ?>
			<?php if ( ! empty( $s['microcopy'] ) ) : ?>
				<p class="ss-hero__microcopy"><?php echo esc_html( $s['microcopy'] ); ?></p>
			<?php endif; ?>

			<?php /* Key Facts */ ?>
			<?php if ( $s['key_facts']['enabled'] ) :
				$facts = array_filter( array(
					$s['key_facts']['date'],
					$s['key_facts']['location'],
					$s['key_facts']['venue'],
					$s['key_facts']['format'],
				) );
				if ( ! empty( $facts ) ) : ?>
				<div class="ss-hero__facts" role="list" aria-label="<?php esc_attr_e( 'Event details', 'sender-symposium' ); ?>">
					<?php foreach ( $facts as $fact ) : ?>
						<span class="ss-hero__fact" role="listitem"><?php echo esc_html( $fact ); ?></span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php /* Countdown */ ?>
			<?php if ( $s['countdown']['enabled'] && ! empty( $s['countdown']['target_datetime'] ) ) : ?>
			<div class="ss-hero__countdown"
				data-target="<?php echo esc_attr( $s['countdown']['target_datetime'] ); ?>"
				aria-label="<?php esc_attr_e( 'Time remaining until event', 'sender-symposium' ); ?>"
				aria-live="polite">
				<noscript>
					<p><?php echo esc_html( $s['key_facts']['date'] ?: $s['countdown']['target_datetime'] ); ?></p>
				</noscript>
			</div>
			<?php endif; ?>

		</div>
	</section>
	<?php
}
