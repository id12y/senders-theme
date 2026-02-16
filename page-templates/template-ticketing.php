<?php
/**
 * Template Name: Ticketing Page
 *
 * Conversion-optimised ticketing page with journey bar, intro block,
 * TicketTailor embed, sidebar cards, and reassurance elements.
 *
 * All content editable via the Ticketing Page Settings meta box.
 *
 * @package SenderSymposium
 */

get_header();

$s     = ss_get_ticketing_settings( get_the_ID() );
$jb    = $s['journey_bar'];
$intro = $s['intro'];
$sc    = $s['scarcity'];
$tt    = $s['tickettailor'];
$rc    = $s['right_column'];
$wd    = $s['why_different'];
$ic    = $s['info_card'];
$tm    = $s['testimonial'];
$re    = $s['reassurance'];
?>

<div class="ss-ticketing">

	<?php /* ═══ 1. JOURNEY BAR ═══ */ ?>
	<?php if ( $jb['show'] ) : ?>
	<nav class="ss-ticketing-journey" aria-label="<?php esc_attr_e( 'Checkout progress', 'sender-symposium' ); ?>">
		<div class="container">
			<div class="ss-ticketing-journey__inner">

				<?php
				/* ── Logo (light + optional dark override) ── */
				$logo_light = '';
				$logo_dark  = '';

				if ( $jb['logo_id'] ) {
					$logo_light = wp_get_attachment_image_url( $jb['logo_id'], 'medium' );
				}
				if ( ! $logo_light && has_custom_logo() ) {
					$custom_logo_id = get_theme_mod( 'custom_logo' );
					$logo_light     = wp_get_attachment_image_url( $custom_logo_id, 'medium' );
				}
				if ( $jb['logo_dark_id'] ) {
					$logo_dark = wp_get_attachment_image_url( $jb['logo_dark_id'], 'medium' );
				}

				$site_name = get_bloginfo( 'name' );

				if ( $logo_light ) :
					$has_dark = ! empty( $logo_dark );
				?>
				<div class="ss-ticketing-journey__logo">
					<img
						class="<?php echo $has_dark ? 'ss-logo-light' : ''; ?>"
						src="<?php echo esc_url( $logo_light ); ?>"
						alt="<?php echo esc_attr( $site_name ); ?>"
						loading="lazy"
					>
					<?php if ( $has_dark ) : ?>
					<img
						class="ss-logo-dark"
						src="<?php echo esc_url( $logo_dark ); ?>"
						alt="<?php echo esc_attr( $site_name ); ?>"
						loading="lazy"
					>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php /* ── Steps ── */ ?>
				<ol class="ss-ticketing-steps">
					<?php for ( $i = 1; $i <= 3; $i++ ) :
						$is_active  = ( (int) $jb['active_step'] === $i );
						$step_class = 'ss-ticketing-step';
						if ( $is_active ) {
							$step_class .= ' ss-ticketing-step--active';
						}
						if ( $i < (int) $jb['active_step'] ) {
							$step_class .= ' ss-ticketing-step--complete';
						}
					?>
					<li class="<?php echo esc_attr( $step_class ); ?>"
						<?php if ( $is_active ) : ?>aria-current="step"<?php endif; ?>>
						<?php if ( $jb['show_numbers'] ) : ?>
						<span class="ss-ticketing-step__number"><?php echo esc_html( $i ); ?></span>
						<?php endif; ?>
						<span class="ss-ticketing-step__label"><?php echo esc_html( $jb[ 'step_' . $i ] ); ?></span>
					</li>
					<?php endfor; ?>
				</ol>

				<?php /* ── Trust badge ── */ ?>
				<?php if ( $jb['show_trust_badge'] && ! empty( $jb['trust_badge_text'] ) ) : ?>
				<div class="ss-ticketing-trust">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
						<path d="M12 6H11V4C11 2.34 9.66 1 8 1S5 2.34 5 4V6H4C3.45 6 3 6.45 3 7V13C3 13.55 3.45 14 4 14H12C12.55 14 13 13.55 13 13V7C13 6.45 12.55 6 12 6ZM8 11C7.45 11 7 10.55 7 10S7.45 9 8 9 9 9.45 9 10 8.55 11 8 11ZM9.8 6H6.2V4C6.2 2.84 7.18 2 8 2S9.8 2.84 9.8 4V6Z" fill="currentColor"/>
					</svg>
					<span><?php echo esc_html( $jb['trust_badge_text'] ); ?></span>
				</div>
				<?php endif; ?>

			</div>
		</div>
	</nav>
	<?php endif; ?>

	<?php /* ═══ 2. CONVERSION INTRO ═══ */ ?>
	<section class="ss-ticketing-intro">
		<div class="container">
			<?php if ( ! empty( $intro['overline'] ) ) : ?>
				<p class="ss-ticketing-overline"><?php echo esc_html( $intro['overline'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $intro['headline'] ) ) : ?>
				<h1 class="ss-ticketing-headline"><?php echo esc_html( $intro['headline'] ); ?></h1>
			<?php endif; ?>

			<?php if ( ! empty( $intro['body'] ) ) : ?>
				<div class="ss-ticketing-body"><?php echo wp_kses_post( $intro['body'] ); ?></div>
			<?php endif; ?>

			<?php if ( $intro['show_micro_trust'] ) :
				$trust_items = array_filter( array(
					$intro['micro_trust_1'],
					$intro['micro_trust_2'],
					$intro['micro_trust_3'],
				) );
				if ( ! empty( $trust_items ) ) : ?>
				<ul class="ss-ticketing-micro-trust" aria-label="<?php esc_attr_e( 'Event highlights', 'sender-symposium' ); ?>">
					<?php foreach ( $trust_items as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>

	<?php /* ═══ 3. SCARCITY LINE ═══ */ ?>
	<?php if ( $sc['show'] && ! empty( $sc['text'] ) ) : ?>
	<div class="ss-ticketing-scarcity">
		<div class="container">
			<p><?php echo esc_html( $sc['text'] ); ?></p>
		</div>
	</div>
	<?php endif; ?>

	<?php /* ═══ 4. TWO-COLUMN LAYOUT ═══ */ ?>
	<div class="ss-ticketing-layout">
		<div class="container">
			<div class="ss-ticketing-columns">

				<?php /* ── LEFT: TicketTailor Embed ── */ ?>
				<div class="ss-ticketing-embed">
					<?php if ( ! empty( $tt['shortcode'] ) ) : ?>
						<div class="ss-tt-wrap">
							<?php echo do_shortcode( $tt['shortcode'] ); ?>
						</div>
						<?php if ( ! empty( $tt['fallback_text'] ) ) : ?>
						<noscript>
							<p class="ss-ticketing-fallback"><?php echo esc_html( $tt['fallback_text'] ); ?></p>
						</noscript>
						<?php endif; ?>
					<?php elseif ( current_user_can( 'edit_pages' ) ) : ?>
						<div class="ss-ticketing-notice" role="alert">
							<p><?php esc_html_e( 'No TicketTailor shortcode configured. Add one in the page settings meta box below.', 'sender-symposium' ); ?></p>
						</div>
					<?php else : ?>
						<?php if ( ! empty( $tt['fallback_text'] ) ) : ?>
							<p class="ss-ticketing-fallback"><?php echo esc_html( $tt['fallback_text'] ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php /* ── RIGHT: Sidebar Blocks ── */ ?>
				<aside class="ss-ticketing-sidebar">
					<?php
					/* Build ordered block list */
					$blocks = array();
					if ( $wd['show'] ) {
						$blocks[] = 'why_different';
					}
					if ( $ic['show'] ) {
						$blocks[] = 'info_card';
					}
					if ( $tm['show'] ) {
						$blocks[] = 'testimonial';
					}

					/* Reorder if testimonial first */
					if ( 'testimonial_first' === $rc['order'] ) {
						$reordered = array();
						foreach ( $blocks as $b ) {
							if ( 'testimonial' === $b ) {
								array_unshift( $reordered, $b );
							} else {
								$reordered[] = $b;
							}
						}
						$blocks = $reordered;
					}

					foreach ( $blocks as $block ) :
						switch ( $block ) :

							case 'why_different': ?>
								<div class="ss-ticketing-why">
									<?php if ( ! empty( $wd['title'] ) ) : ?>
										<h3><?php echo esc_html( $wd['title'] ); ?></h3>
									<?php endif; ?>
									<?php
									$bullets = ss_lines_to_array( $wd['bullets'] );
									$bullets = array_slice( $bullets, 0, 4 );
									if ( ! empty( $bullets ) ) : ?>
									<ul>
										<?php foreach ( $bullets as $bullet ) : ?>
											<li><?php echo esc_html( $bullet ); ?></li>
										<?php endforeach; ?>
									</ul>
									<?php endif; ?>
								</div>
								<?php break;

							case 'info_card': ?>
								<div class="ss-ticketing-info-card">
									<?php if ( ! empty( $ic['heading'] ) ) : ?>
										<h3><?php echo esc_html( $ic['heading'] ); ?></h3>
									<?php endif; ?>
									<?php if ( ! empty( $ic['body'] ) ) : ?>
										<div class="ss-ticketing-info-card__body"><?php echo wp_kses_post( $ic['body'] ); ?></div>
									<?php endif; ?>
								</div>
								<?php break;

							case 'testimonial': ?>
								<blockquote class="ss-ticketing-testimonial">
									<?php if ( ! empty( $tm['quote'] ) ) : ?>
										<p class="ss-ticketing-testimonial__quote">&ldquo;<?php echo esc_html( $tm['quote'] ); ?>&rdquo;</p>
									<?php endif; ?>
									<footer class="ss-ticketing-testimonial__footer">
										<?php
										$avatar_url = '';
										if ( $tm['avatar_id'] ) {
											$avatar_url = wp_get_attachment_image_url( $tm['avatar_id'], 'thumbnail' );
										}
										if ( $avatar_url ) : ?>
										<img class="ss-ticketing-testimonial__avatar"
											src="<?php echo esc_url( $avatar_url ); ?>"
											alt="" width="48" height="48" loading="lazy">
										<?php endif; ?>
										<div class="ss-ticketing-testimonial__cite">
											<?php if ( ! empty( $tm['name'] ) ) : ?>
												<cite class="ss-ticketing-testimonial__name"><?php echo esc_html( $tm['name'] ); ?></cite>
											<?php endif; ?>
											<?php
											$meta_parts = array_filter( array( $tm['role'], $tm['company'] ) );
											if ( ! empty( $meta_parts ) ) : ?>
												<span class="ss-ticketing-testimonial__meta"><?php echo esc_html( implode( ', ', $meta_parts ) ); ?></span>
											<?php endif; ?>
										</div>
									</footer>
								</blockquote>
								<?php break;

						endswitch;
					endforeach;
					?>
				</aside>

			</div>
		</div>
	</div>

	<?php /* ═══ 5. REASSURANCE ═══ */ ?>
	<?php if ( $re['show'] && ! empty( $re['text'] ) ) : ?>
	<div class="ss-ticketing-reassurance">
		<div class="container">
			<?php if ( ! empty( $re['link'] ) ) : ?>
				<p><a href="<?php echo esc_url( $re['link'] ); ?>"><?php echo esc_html( $re['text'] ); ?></a></p>
			<?php else : ?>
				<p><?php echo esc_html( $re['text'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

</div>

<?php
get_footer();
