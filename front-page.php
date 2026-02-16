<?php
/**
 * Front Page template — Sender Symposium
 *
 * Outputs themed content blocks (event strip, audience, values, format,
 * credibility, CTA) driven by admin settings. Each section can be
 * toggled on/off from Appearance → Sender Symposium Settings → Homepage.
 *
 * Page builder content (Elementor / Gutenberg) is rendered via the_content()
 * between the themed blocks. All CSS component classes remain available
 * for use inside Elementor HTML widgets or Gutenberg Custom HTML blocks.
 *
 * @package SenderSymposium
 */

get_header();

$hp            = ss_get_homepage();
$hero_settings = ss_get_hero_settings( get_the_ID() );
$hero_position = $hero_settings['position'] ?? 'above';

/* ── Hero Block — Position 1: above all content ── */
if ( 'above' === $hero_position ) {
	ss_render_hero( get_the_ID() );
}
?>

<?php /* ── Event Info Strip ── */ ?>
<?php if ( $hp['event_strip_enabled'] && ! empty( $hp['event_strip'] ) ) : ?>
<section class="event-strip" aria-label="<?php esc_attr_e( 'Event details', 'sender-symposium' ); ?>">
	<?php foreach ( $hp['event_strip'] as $item ) : ?>
	<div class="event-strip__item">
		<span class="event-strip__label"><?php echo esc_html( $item['label'] ); ?></span>
		<span class="event-strip__value"><?php echo esc_html( $item['value'] ); ?></span>
	</div>
	<?php endforeach; ?>
</section>
<?php endif; ?>

<?php
/* ── Hero Block — Position 2: after event info strip ── */
if ( 'after_event_strip' === $hero_position ) {
	ss_render_hero( get_the_ID() );
}
?>

<?php /* ── Page builder content ── */ ?>
<?php while ( have_posts() ) : the_post(); ?>
	<?php
	$is_elementor_page = get_post_meta( get_the_ID(), '_elementor_edit_mode', true );
	$elementor_active  = defined( 'ELEMENTOR_VERSION' );
	/*
	 * Render the_content() only when Elementor can process its own markup,
	 * or when the page was never built with Elementor (plain Gutenberg/classic).
	 * This prevents raw Elementor storage data from dumping as unstyled HTML.
	 */
	if ( ( $elementor_active || ! $is_elementor_page ) && trim( get_the_content() ) ) :
	?>
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
	<?php endif; ?>
<?php endwhile; ?>

<?php /* ── Audience Block ── */ ?>
<?php if ( $hp['audience_enabled'] ) : ?>
<section class="section" aria-label="<?php esc_attr_e( 'Who should attend', 'sender-symposium' ); ?>">
	<div class="container">
		<?php if ( ! empty( $hp['audience_title'] ) ) : ?>
			<h2 style="text-align:center;margin-bottom:var(--sp-5);"><?php echo esc_html( $hp['audience_title'] ); ?></h2>
		<?php endif; ?>
		<div class="audience-block">
			<div class="audience-block__col audience-block__col--for">
				<h3 class="audience-block__title"><?php echo esc_html( $hp['audience_title_for'] ); ?></h3>
				<ul class="audience-block__list">
					<?php foreach ( ss_lines_to_array( $hp['audience_items_for'] ) as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="audience-block__col audience-block__col--not">
				<h3 class="audience-block__title"><?php echo esc_html( $hp['audience_title_not'] ); ?></h3>
				<ul class="audience-block__list">
					<?php foreach ( ss_lines_to_array( $hp['audience_items_not'] ) as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* ── Value / Outcome Cards ── */ ?>
<?php if ( $hp['values_enabled'] && ! empty( $hp['values'] ) ) : ?>
<section class="section section--alt" aria-label="<?php esc_attr_e( 'Outcomes', 'sender-symposium' ); ?>">
	<div class="container">
		<?php if ( ! empty( $hp['values_title'] ) ) : ?>
			<h2 style="text-align:center;margin-bottom:var(--sp-5);"><?php echo esc_html( $hp['values_title'] ); ?></h2>
		<?php endif; ?>
		<div class="card-grid">
			<?php foreach ( $hp['values'] as $card ) : ?>
			<div class="value-card">
				<?php if ( ! empty( $card['number'] ) ) : ?>
					<div class="value-card__number"><?php echo esc_html( $card['number'] ); ?></div>
				<?php endif; ?>
				<h3 class="value-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
				<p class="value-card__body"><?php echo esc_html( $card['body'] ); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* ── Format Block ── */ ?>
<?php if ( $hp['format_enabled'] && ! empty( $hp['format_items'] ) ) : ?>
<section class="section" aria-label="<?php esc_attr_e( 'Event format', 'sender-symposium' ); ?>">
	<div class="container">
		<?php if ( ! empty( $hp['format_title'] ) ) : ?>
			<h2 style="text-align:center;margin-bottom:var(--sp-5);"><?php echo esc_html( $hp['format_title'] ); ?></h2>
		<?php endif; ?>
		<div class="format-block">
			<?php foreach ( $hp['format_items'] as $item ) : ?>
			<div class="format-item">
				<h3 class="format-item__title"><?php echo esc_html( $item['title'] ); ?></h3>
				<p class="format-item__body"><?php echo esc_html( $item['body'] ); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* ── Credibility Row ── */ ?>
<?php if ( $hp['credibility_enabled'] && ! empty( $hp['credibility_items'] ) ) : ?>
<section class="section section--alt" aria-label="<?php esc_attr_e( 'Event stats', 'sender-symposium' ); ?>">
	<div class="container">
		<div class="credibility-row">
			<?php foreach ( $hp['credibility_items'] as $item ) : ?>
			<div class="credibility-item">
				<div class="credibility-item__number"><?php echo esc_html( $item['number'] ); ?></div>
				<div class="credibility-item__label"><?php echo esc_html( $item['label'] ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* ── CTA Block ── */ ?>
<?php if ( $hp['cta_enabled'] ) : ?>
<section class="section" aria-label="<?php esc_attr_e( 'Call to action', 'sender-symposium' ); ?>">
	<div class="container">
		<div class="cta-block">
			<?php if ( ! empty( $hp['cta_title'] ) ) : ?>
				<h2 class="cta-block__title"><?php echo esc_html( $hp['cta_title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $hp['cta_body'] ) ) : ?>
				<p class="cta-block__body"><?php echo esc_html( $hp['cta_body'] ); ?></p>
			<?php endif; ?>
			<div class="cta-block__actions">
				<?php if ( ! empty( $hp['cta_button_text'] ) ) : ?>
					<a class="btn btn--primary" href="<?php echo esc_url( $hp['cta_button_url'] ); ?>"><?php echo esc_html( $hp['cta_button_text'] ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $hp['cta_secondary_text'] ) ) : ?>
					<a class="btn btn--secondary" href="<?php echo esc_url( $hp['cta_secondary_url'] ); ?>"><?php echo esc_html( $hp['cta_secondary_text'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
