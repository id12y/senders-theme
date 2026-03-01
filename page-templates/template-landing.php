<?php
/**
 * Template Name: Ticket Landing Page
 *
 * Conversion-focused landing page for partner/speaker ticket promotions.
 * Single CTA repeated throughout, all sections toggleable.
 * All content editable via the Landing Page Settings meta box.
 *
 * @package SenderSymposium
 */

/**
 * Render the TicketTailor embed block.
 * Reuses the same embed logic as template-ticketing.php.
 */
if ( ! function_exists( 'ss_landing_render_tt_embed' ) ) :
function ss_landing_render_tt_embed( $tt ) {
	$tt_mode      = $tt['embed_method'];
	$tt_has_event = ! empty( $tt['event_id'] );
	$tt_has_short = ! empty( $tt['shortcode'] );
	$tt_bg_fill   = ! empty( $tt['widget_bg_transparent'] ) ? 'false' : 'true';

	$tt_base_url = '';
	if ( $tt_has_event ) {
		$tt_checkout_host = ! empty( $tt['custom_domain'] )
			? $tt['custom_domain']
			: 'www.tickettailor.com';
		$tt_base_url = 'https://' . $tt_checkout_host . '/checkout/new-event/' . $tt['event_id'];
		if ( ! empty( $tt['access_code'] ) ) {
			$tt_base_url .= '?a=' . rawurlencode( $tt['access_code'] );
		}
	}

	$use_widget_js = ( 'widget_js' === $tt_mode && $tt_has_event )
		|| ( 'auto' === $tt_mode && $tt_has_event );
	$use_shortcode = ( 'shortcode' === $tt_mode && $tt_has_short );
	$auto_shortcode_fallback = ( 'auto' === $tt_mode && $tt_has_short );

	?>
	<div class="ss-landing__embed-surface<?php echo ! empty( $tt['widget_bg_transparent'] ) ? ' ss-landing__embed--tt-transparent' : ''; ?>">
		<?php if ( $use_widget_js ) : ?>
			<div class="ss-tt-wrap" data-ss-tt-fallback>
				<div class="tt-widget"
					data-url="<?php echo esc_url( $tt_base_url ); ?>"
					data-type="inline"
					data-inline-minimal="true"
					data-inline-show-logo="false"
					data-inline-bg-fill="<?php echo esc_attr( $tt_bg_fill ); ?>"></div>
				<div class="ss-tt-fallback" hidden>
					<?php if ( $auto_shortcode_fallback ) : ?>
						<?php echo do_shortcode( $tt['shortcode'] ); ?>
					<?php elseif ( ! empty( $tt['fallback_text'] ) ) : ?>
						<p class="ss-landing__fallback-text"><?php echo esc_html( $tt['fallback_text'] ); ?></p>
					<?php endif; ?>
				</div>
				<noscript>
					<?php if ( $tt_has_short ) : ?>
						<?php echo do_shortcode( $tt['shortcode'] ); ?>
					<?php elseif ( ! empty( $tt['fallback_text'] ) ) : ?>
						<p class="ss-landing__fallback-text"><?php echo esc_html( $tt['fallback_text'] ); ?></p>
					<?php endif; ?>
				</noscript>
			</div>
		<?php elseif ( $use_shortcode ) : ?>
			<div class="ss-tt-wrap ss-tt-wrap--shortcode">
				<?php echo do_shortcode( $tt['shortcode'] ); ?>
			</div>
		<?php elseif ( current_user_can( 'edit_pages' ) ) : ?>
			<div class="ss-landing__notice" role="alert">
				<p><?php esc_html_e( 'No TicketTailor embed configured. Add your Event ID or shortcode in the Landing Page Settings meta box.', 'sender-symposium' ); ?></p>
			</div>
		<?php else : ?>
			<?php if ( ! empty( $tt['fallback_text'] ) ) : ?>
				<p class="ss-landing__fallback-text"><?php echo esc_html( $tt['fallback_text'] ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
endif;

get_header();

$s     = ss_get_landing_settings( get_the_ID() );
$hero  = $s['hero'];
$event = $s['event'];
$offer = $s['offer'];
$why   = $s['why'];
$vs    = $s['value_stack'];
$aud   = $s['audience'];
$notes = $s['notes'];
$cls   = $s['closing'];
$tt    = $s['tickettailor'];

/* Determine where #tickets anchor lands */
$tt_anchor_placed = false;
$tt_inside_closing = $tt['show_embed'] && 'inside_closing' === $tt['embed_position'];
?>

<div class="ss-landing">

	<?php /* ═══ 1. HERO (Above the Fold) ═══ */ ?>
	<section class="ss-landing__hero">
		<div class="container">
			<div class="ss-landing__hero-grid">

				<div class="ss-landing__hero-content">
					<?php if ( ! empty( $hero['overline'] ) ) : ?>
						<p class="ss-landing__overline"><?php echo esc_html( $hero['overline'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $hero['event_name'] ) ) : ?>
						<p class="ss-landing__event-name"><?php echo esc_html( $hero['event_name'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $hero['headline'] ) ) : ?>
						<h1 class="ss-landing__headline"><?php echo esc_html( $hero['headline'] ); ?></h1>
					<?php endif; ?>

					<?php if ( ! empty( $hero['subheadline'] ) ) : ?>
						<p class="ss-landing__subheadline"><?php echo esc_html( $hero['subheadline'] ); ?></p>
					<?php endif; ?>

					<?php if ( $hero['show_micro_trust'] ) :
						$trust_items = array_filter( array(
							$hero['micro_trust_1'],
							$hero['micro_trust_2'],
							$hero['micro_trust_3'],
						) );
						if ( ! empty( $trust_items ) ) : ?>
						<ul class="ss-landing__micro-trust" aria-label="<?php esc_attr_e( 'Event highlights', 'sender-symposium' ); ?>">
							<?php foreach ( $trust_items as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $hero['show_scarcity'] && ! empty( $hero['scarcity_text'] ) ) : ?>
						<div class="ss-landing__scarcity">
							<span><?php echo esc_html( $hero['scarcity_text'] ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $hero['cta_label'] ) && ! empty( $hero['cta_url'] ) ) : ?>
						<div class="ss-landing__hero-actions">
							<a class="btn btn--primary" href="<?php echo esc_url( $hero['cta_url'] ); ?>">
								<?php echo esc_html( $hero['cta_label'] ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>

				<?php
				$partner_img = $hero['partner_image_id'] ? wp_get_attachment_image_url( $hero['partner_image_id'], 'medium_large' ) : '';
				$partner_logo = $hero['partner_logo_id'] ? wp_get_attachment_image_url( $hero['partner_logo_id'], 'medium' ) : '';
				if ( $partner_img || ! empty( $hero['partner_name'] ) ) :
				?>
				<div class="ss-landing__hero-partner">
					<?php if ( $partner_img ) : ?>
						<div class="ss-landing__partner-portrait">
							<img src="<?php echo esc_url( $partner_img ); ?>"
								alt="<?php echo esc_attr( $hero['partner_name'] ); ?>"
								width="280" height="280"
								loading="eager" decoding="async">
						</div>
					<?php endif; ?>

					<?php if ( $partner_logo ) : ?>
						<div class="ss-landing__partner-logo">
							<img src="<?php echo esc_url( $partner_logo ); ?>"
								alt="<?php echo esc_attr( $hero['partner_title'] ); ?>"
								loading="eager" decoding="async">
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $hero['partner_name'] ) ) : ?>
						<div class="ss-landing__partner-credit">
							<span class="ss-landing__partner-name"><?php echo esc_html( $hero['partner_name'] ); ?></span>
							<?php if ( ! empty( $hero['partner_title'] ) ) : ?>
								<span class="ss-landing__partner-title"><?php echo esc_html( $hero['partner_title'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

			</div>
		</div>
	</section>

	<?php /* ═══ 2. EVENT CONTEXT ═══ */ ?>
	<?php if ( $event['show'] ) : ?>
	<section class="ss-landing__event" aria-label="<?php esc_attr_e( 'About the event', 'sender-symposium' ); ?>">
		<div class="container">
			<?php if ( ! empty( $event['body'] ) ) : ?>
				<div class="ss-landing__prose"><?php echo wp_kses_post( $event['body'] ); ?></div>
			<?php endif; ?>

			<?php
			$event_bullets = ss_lines_to_array( $event['bullets'] );
			if ( ! empty( $event_bullets ) ) : ?>
			<ul class="ss-landing__statement-list">
				<?php foreach ( $event_bullets as $bullet ) : ?>
					<li><?php echo esc_html( $bullet ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if ( ! empty( $event['footer'] ) ) : ?>
				<p class="ss-landing__section-footer ss-landing__section-footer--emphasis"><?php echo esc_html( $event['footer'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 3. PARTNER EXCLUSIVE OFFER ═══ */ ?>
	<?php if ( $offer['show'] ) : ?>
	<section class="ss-landing__offer"<?php echo ! empty( $offer['headline'] ) ? ' aria-label="' . esc_attr( $offer['headline'] ) . '"' : ''; ?>>
		<div class="container">
			<?php if ( ! empty( $offer['overline'] ) ) : ?>
				<p class="ss-landing__overline"><?php echo esc_html( $offer['overline'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $offer['headline'] ) ) : ?>
				<h2 class="ss-landing__section-headline"><?php echo esc_html( $offer['headline'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $offer['value_note'] ) ) : ?>
				<p class="ss-landing__value-note"><?php echo esc_html( $offer['value_note'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $offer['body'] ) ) : ?>
				<div class="ss-landing__prose"><?php echo wp_kses_post( $offer['body'] ); ?></div>
			<?php endif; ?>

			<?php
			$offer_items = ss_lines_to_array( $offer['items'] );
			if ( ! empty( $offer_items ) ) : ?>
			<ul class="ss-landing__check-list">
				<?php foreach ( $offer_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if ( ! empty( $offer['footer'] ) ) : ?>
				<div class="ss-landing__section-footer ss-landing__section-footer--emphasis"><?php echo wp_kses_post( $offer['footer'] ); ?></div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 4. WHY THIS MATTERS ═══ */ ?>
	<?php if ( $why['show'] ) : ?>
	<section class="ss-landing__why"<?php echo ! empty( $why['headline'] ) ? ' aria-label="' . esc_attr( $why['headline'] ) . '"' : ''; ?>>
		<div class="container">
			<?php if ( ! empty( $why['headline'] ) ) : ?>
				<h2 class="ss-landing__section-headline"><?php echo esc_html( $why['headline'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $why['body'] ) ) : ?>
				<div class="ss-landing__prose"><?php echo wp_kses_post( $why['body'] ); ?></div>
			<?php endif; ?>

			<?php
			$why_items = ss_lines_to_array( $why['items'] );
			if ( ! empty( $why_items ) ) : ?>
			<ul class="ss-landing__progression">
				<?php foreach ( $why_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if ( ! empty( $why['footer'] ) ) : ?>
				<p class="ss-landing__section-footer"><?php echo esc_html( $why['footer'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 5. VALUE STACK ═══ */ ?>
	<?php if ( $vs['show'] ) : ?>
	<section class="ss-landing__value-stack"<?php echo ! empty( $vs['headline'] ) ? ' aria-label="' . esc_attr( $vs['headline'] ) . '"' : ''; ?>>
		<div class="container">
			<?php if ( ! empty( $vs['headline'] ) ) : ?>
				<h2 class="ss-landing__section-headline"><?php echo esc_html( $vs['headline'] ); ?></h2>
			<?php endif; ?>

			<div class="ss-landing__value-card">
				<?php if ( ! empty( $vs['ticket_name'] ) ) : ?>
					<h3 class="ss-landing__value-card-name"><?php echo esc_html( $vs['ticket_name'] ); ?></h3>
				<?php endif; ?>

				<?php if ( ! empty( $vs['ticket_price'] ) ) : ?>
					<p class="ss-landing__value-card-price"><?php echo esc_html( $vs['ticket_price'] ); ?></p>
				<?php endif; ?>

				<?php
				$ticket_items = ss_lines_to_array( $vs['ticket_items'] );
				if ( ! empty( $ticket_items ) ) : ?>
				<ul class="ss-landing__check-list">
					<?php foreach ( $ticket_items as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>

				<?php if ( ! empty( $vs['bonus_name'] ) ) : ?>
				<div class="ss-landing__value-bonus">
					<?php if ( ! empty( $vs['bonus_condition'] ) ) : ?>
						<p class="ss-landing__value-bonus-label"><?php echo esc_html( $vs['bonus_condition'] ); ?></p>
					<?php endif; ?>
					<p class="ss-landing__value-bonus-name"><?php echo esc_html( $vs['bonus_name'] ); ?></p>
					<?php if ( ! empty( $vs['bonus_description'] ) ) : ?>
						<p class="ss-landing__value-bonus-desc"><?php echo esc_html( $vs['bonus_description'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ TT EMBED: after_value_stack ═══ */ ?>
	<?php if ( $tt['show_embed'] && 'after_value_stack' === $tt['embed_position'] ) :
		$tt_anchor_placed = true;
	?>
	<section class="ss-landing__embed" id="tickets" aria-label="<?php esc_attr_e( 'Purchase tickets', 'sender-symposium' ); ?>">
		<div class="container">
			<?php ss_landing_render_tt_embed( $tt ); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 6. AUDIENCE ═══ */ ?>
	<?php if ( $aud['show'] ) : ?>
	<section class="ss-landing__audience"<?php echo ! empty( $aud['headline'] ) ? ' aria-label="' . esc_attr( $aud['headline'] ) . '"' : ''; ?>>
		<div class="container">
			<?php if ( ! empty( $aud['headline'] ) ) : ?>
				<h2 class="ss-landing__section-headline"><?php echo esc_html( $aud['headline'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $aud['intro'] ) ) : ?>
				<p class="ss-landing__audience-intro"><?php echo esc_html( $aud['intro'] ); ?></p>
			<?php endif; ?>

			<?php
			$aud_items = ss_lines_to_array( $aud['items'] );
			if ( ! empty( $aud_items ) ) : ?>
			<ul class="ss-landing__role-list">
				<?php foreach ( $aud_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if ( ! empty( $aud['footer'] ) ) : ?>
				<p class="ss-landing__section-footer"><?php echo esc_html( $aud['footer'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 7. IMPORTANT NOTES ═══ */ ?>
	<?php if ( $notes['show'] ) : ?>
	<section class="ss-landing__notes"<?php echo ! empty( $notes['headline'] ) ? ' aria-label="' . esc_attr( $notes['headline'] ) . '"' : ''; ?>>
		<div class="container">
			<?php if ( ! empty( $notes['headline'] ) ) : ?>
				<h2 class="ss-landing__section-headline"><?php echo esc_html( $notes['headline'] ); ?></h2>
			<?php endif; ?>

			<?php
			$note_items = ss_lines_to_array( $notes['items'] );
			if ( ! empty( $note_items ) ) : ?>
			<ul class="ss-landing__notes-list">
				<?php foreach ( $note_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ TT EMBED: before_closing ═══ */ ?>
	<?php if ( $tt['show_embed'] && 'before_closing' === $tt['embed_position'] ) :
		$tt_anchor_placed = true;
	?>
	<section class="ss-landing__embed" id="tickets" aria-label="<?php esc_attr_e( 'Purchase tickets', 'sender-symposium' ); ?>">
		<div class="container">
			<?php ss_landing_render_tt_embed( $tt ); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 8. CLOSING CTA ═══ */ ?>
	<?php if ( $cls['show'] ) : ?>
	<section class="ss-landing__closing"<?php echo ( ! $tt_anchor_placed && ! $tt_inside_closing ) ? ' id="tickets"' : ''; ?>>
		<div class="container">
			<?php if ( ! empty( $cls['headline'] ) ) : ?>
				<h2 class="ss-landing__section-headline"><?php echo esc_html( $cls['headline'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $cls['body'] ) ) : ?>
				<div class="ss-landing__prose ss-landing__prose--centered"><?php echo wp_kses_post( $cls['body'] ); ?></div>
			<?php endif; ?>

			<?php
			$cls_bullets = ss_lines_to_array( $cls['bullets'] );
			if ( ! empty( $cls_bullets ) ) : ?>
			<ul class="ss-landing__statement-list ss-landing__statement-list--centered">
				<?php foreach ( $cls_bullets as $bullet ) : ?>
					<li><?php echo esc_html( $bullet ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php /* TT EMBED: inside_closing */ ?>
			<?php if ( $tt['show_embed'] && 'inside_closing' === $tt['embed_position'] ) :
				$tt_anchor_placed = true;
			?>
			<div class="ss-landing__embed-inline" id="tickets">
				<?php ss_landing_render_tt_embed( $tt ); ?>
			</div>
			<?php endif; ?>

			<?php if ( $hero['show_scarcity'] && ! empty( $hero['scarcity_text'] ) ) : ?>
				<p class="ss-landing__scarcity ss-landing__scarcity--closing">
					<?php echo esc_html( $hero['scarcity_text'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $cls['cta_label'] ) && ! empty( $cls['cta_url'] ) ) : ?>
				<div class="ss-landing__closing-actions">
					<a class="btn btn--primary ss-landing__btn-lg" href="<?php echo esc_url( $cls['cta_url'] ); ?>">
						<?php echo esc_html( $cls['cta_label'] ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

</div>

<?php
get_footer();
