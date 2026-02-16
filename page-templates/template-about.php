<?php
/**
 * Template Name: About Page
 *
 * Premium about page — architectural, authority-building, conversion-focused.
 * Re-uses speakers module for contributor cards.
 * All content editable via the About Page Settings meta box.
 *
 * @package SenderSymposium
 */

get_header();

$s     = ss_get_about_settings( get_the_ID() );
$intro = $s['intro'];
$stats = $s['stats'];
$tr    = $s['track_record'];
$cont  = $s['contributors'];
$phil  = $s['philosophy'];
$gal   = $s['gallery'];
$loc   = $s['location'];
$cls   = $s['closing'];
?>

<div class="ss-about">

	<?php /* ═══ 1. HERO-STYLE INTRO ═══ */ ?>
	<?php if ( $intro['enabled'] ) : ?>
	<section class="ss-about__intro">
		<div class="container">
			<?php if ( ! empty( $intro['overline'] ) ) : ?>
				<p class="ss-about__overline"><?php echo esc_html( $intro['overline'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $intro['headline'] ) ) : ?>
				<h1 class="ss-about__headline"><?php echo esc_html( $intro['headline'] ); ?></h1>
			<?php endif; ?>

			<?php if ( ! empty( $intro['body'] ) ) : ?>
				<div class="ss-about__body"><?php echo wp_kses_post( $intro['body'] ); ?></div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 2. SCULPTURAL STATS STRIP ═══ */ ?>
	<?php if ( $stats['enabled'] && ! empty( $stats['items'] ) ) : ?>
	<section class="ss-about__stats" aria-label="<?php esc_attr_e( 'Key statistics', 'sender-symposium' ); ?>">
		<div class="container">
			<div class="ss-about__stats-row">
				<?php foreach ( $stats['items'] as $stat ) : ?>
				<div class="ss-about__stat">
					<span class="ss-about__stat-number"><?php echo esc_html( $stat['number'] ); ?></span>
					<span class="ss-about__stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 3. TRACK RECORD ═══ */ ?>
	<?php if ( $tr['enabled'] && ! empty( $tr['items'] ) ) : ?>
	<section class="ss-about__section" aria-label="<?php echo esc_attr( $tr['title'] ); ?>">
		<div class="container">
			<?php if ( ! empty( $tr['title'] ) ) : ?>
				<h2 class="ss-about__section-title"><?php echo esc_html( $tr['title'] ); ?></h2>
			<?php endif; ?>
			<div class="ss-about__track-list">
				<?php foreach ( $tr['items'] as $event ) : ?>
				<article class="ss-about__track-item">
					<h3 class="ss-about__track-name"><?php echo esc_html( $event['name'] ); ?></h3>
					<?php if ( ! empty( $event['meta'] ) ) : ?>
						<p class="ss-about__track-meta"><?php echo esc_html( $event['meta'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $event['desc'] ) ) : ?>
						<p class="ss-about__track-desc"><?php echo esc_html( $event['desc'] ); ?></p>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 4. CONTRIBUTORS (Speaker Module) ═══ */ ?>
	<?php if ( $cont['enabled'] && ! empty( $cont['speaker_ids'] ) ) : ?>
	<section class="ss-about__section ss-about__section--alt" aria-label="<?php echo esc_attr( $cont['title'] ); ?>">
		<div class="container">
			<?php if ( ! empty( $cont['title'] ) ) : ?>
				<h2 class="ss-about__section-title"><?php echo esc_html( $cont['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $cont['subtitle'] ) ) : ?>
				<p class="ss-about__section-subtitle"><?php echo esc_html( $cont['subtitle'] ); ?></p>
			<?php endif; ?>

			<div class="ss-about__contributors">
				<?php foreach ( $cont['speaker_ids'] as $entry ) :
					$speaker = ss_get_speaker( $entry['id'] );
					if ( ! $speaker ) {
						continue;
					}
				?>
				<article class="ss-about__contributor">
					<div class="ss-about__contributor-image">
						<?php if ( ! empty( $speaker['image_url'] ) ) : ?>
							<img src="<?php echo esc_url( $speaker['image_url'] ); ?>"
								alt="<?php echo esc_attr( $speaker['name'] ); ?>"
								loading="lazy" decoding="async">
						<?php else : ?>
							<div class="ss-about__contributor-placeholder" aria-hidden="true">
								<?php echo esc_html( ss_speaker_initials( $speaker['name'] ) ); ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="ss-about__contributor-body">
						<h3 class="ss-about__contributor-name"><?php echo esc_html( $speaker['name'] ); ?></h3>
						<?php if ( ! empty( $speaker['company'] ) ) : ?>
							<p class="ss-about__contributor-company"><?php echo esc_html( $speaker['company'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $entry['role_label'] ) ) : ?>
							<p class="ss-about__contributor-role"><?php echo esc_html( $entry['role_label'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $entry['short_note'] ) ) : ?>
							<p class="ss-about__contributor-note"><?php echo esc_html( $entry['short_note'] ); ?></p>
						<?php endif; ?>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 5. PHILOSOPHY ═══ */ ?>
	<?php if ( $phil['enabled'] ) :
		$points = ss_lines_to_array( $phil['points'] );
		if ( ! empty( $points ) ) : ?>
	<section class="ss-about__section" aria-label="<?php echo esc_attr( $phil['title'] ); ?>">
		<div class="container">
			<?php if ( ! empty( $phil['title'] ) ) : ?>
				<h2 class="ss-about__section-title"><?php echo esc_html( $phil['title'] ); ?></h2>
			<?php endif; ?>
			<ul class="ss-about__philosophy">
				<?php foreach ( $points as $point ) : ?>
					<li><?php echo esc_html( $point ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
		<?php endif; ?>
	<?php endif; ?>

	<?php /* ═══ 6. GALLERY ═══ */ ?>
	<?php if ( $gal['enabled'] && ! empty( $gal['image_ids'] ) ) : ?>
	<section class="ss-about__section ss-about__section--alt" aria-label="<?php echo esc_attr( $gal['title'] ); ?>">
		<div class="container">
			<?php if ( ! empty( $gal['title'] ) ) : ?>
				<h2 class="ss-about__section-title"><?php echo esc_html( $gal['title'] ); ?></h2>
			<?php endif; ?>
			<div class="ss-about__gallery">
				<?php foreach ( $gal['image_ids'] as $img_id ) :
					$img_url = wp_get_attachment_image_url( $img_id, 'large' );
					$img_alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
					if ( ! $img_url ) {
						continue;
					}
				?>
				<figure class="ss-about__gallery-item">
					<img src="<?php echo esc_url( $img_url ); ?>"
						alt="<?php echo esc_attr( $img_alt ); ?>"
						loading="lazy" decoding="async">
				</figure>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 7. WHY LA PEDRERA ═══ */ ?>
	<?php if ( $loc['enabled'] ) : ?>
	<section class="ss-about__section" aria-label="<?php echo esc_attr( $loc['title'] ); ?>">
		<div class="container">
			<?php if ( ! empty( $loc['title'] ) ) : ?>
				<h2 class="ss-about__section-title"><?php echo esc_html( $loc['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $loc['body'] ) ) : ?>
				<div class="ss-about__prose"><?php echo wp_kses_post( $loc['body'] ); ?></div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php /* ═══ 8. CLOSING + CTA ═══ */ ?>
	<?php if ( $cls['enabled'] ) : ?>
	<section class="ss-about__closing">
		<div class="container">
			<?php if ( ! empty( $cls['statement'] ) ) : ?>
				<p class="ss-about__closing-statement"><?php echo esc_html( $cls['statement'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $cls['button_text'] ) && ! empty( $cls['button_url'] ) ) : ?>
				<div class="ss-about__closing-cta">
					<a class="btn btn--primary" href="<?php echo esc_url( $cls['button_url'] ); ?>"><?php echo esc_html( $cls['button_text'] ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

</div>

<?php
get_footer();
