<?php
/**
 * Speakers — Frontend Render Helpers
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a single speaker card.
 *
 * @param array $speaker Speaker data.
 * @param array $display Display settings.
 */
function ss_render_speaker_card( $speaker, $display ) {
	$card_class = 'speaker-card speaker-card--' . esc_attr( $display['card_style'] );
	if ( ! empty( $speaker['featured'] ) ) {
		$card_class .= ' speaker-card--featured';
	}
	?>
	<article class="<?php echo esc_attr( $card_class ); ?>">
		<div class="speaker-card__image-wrap">
			<?php if ( ! empty( $speaker['image_url'] ) ) : ?>
				<img
					class="speaker-card__image"
					src="<?php echo esc_url( $speaker['image_url'] ); ?>"
					alt="<?php echo esc_attr( $speaker['name'] ); ?>"
					loading="lazy"
					decoding="async"
				/>
			<?php else : ?>
				<div class="speaker-card__placeholder" aria-hidden="true">
					<?php echo esc_html( ss_speaker_initials( $speaker['name'] ) ); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="speaker-card__body">
			<h3 class="speaker-card__name"><?php echo esc_html( $speaker['name'] ); ?></h3>
			<?php if ( ! empty( $speaker['job_title'] ) ) : ?>
				<p class="speaker-card__job-title"><?php echo esc_html( $speaker['job_title'] ); ?></p>
			<?php endif; ?>
			<?php if ( $display['show_company'] && ! empty( $speaker['company'] ) ) : ?>
				<p class="speaker-card__company"><?php echo esc_html( $speaker['company'] ); ?></p>
			<?php endif; ?>
			<?php if ( $display['show_topic'] && ! empty( $speaker['topic'] ) ) : ?>
				<p class="speaker-card__topic"><?php echo esc_html( $speaker['topic'] ); ?></p>
			<?php endif; ?>
			<?php if ( $display['show_description'] && ! empty( $speaker['description'] ) ) : ?>
				<div class="speaker-card__description"><?php echo wp_kses_post( $speaker['description'] ); ?></div>
			<?php endif; ?>
			<?php
			$has_linkedin = $display['show_linkedin'] && ! empty( $speaker['linkedin_url'] );
			$has_website  = ! empty( $display['show_website'] ) && ! empty( $speaker['website_url'] );
			if ( $has_linkedin || $has_website ) : ?>
			<div class="speaker-card__links">
				<?php if ( $has_linkedin ) : ?>
				<a class="speaker-card__link speaker-card__linkedin" href="<?php echo esc_url( $speaker['linkedin_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
					<span><?php esc_html_e( 'LinkedIn', 'sender-symposium' ); ?></span>
					<span class="sr-only"><?php esc_html_e( '(opens in new tab)', 'sender-symposium' ); ?></span>
				</a>
				<?php endif; ?>
				<?php if ( $has_website ) : ?>
				<a class="speaker-card__link speaker-card__website" href="<?php echo esc_url( $speaker['website_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
					<span><?php esc_html_e( 'Website', 'sender-symposium' ); ?></span>
					<span class="sr-only"><?php esc_html_e( '(opens in new tab)', 'sender-symposium' ); ?></span>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</article>
	<?php
}
