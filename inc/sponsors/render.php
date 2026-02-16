<?php
/**
 * Sponsors — Frontend Render Helpers + Shortcode
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a single sponsor card.
 */
function ss_render_sponsor_card( $sponsor, $display ) {
	$logo_url       = ss_sponsor_logo_url( $sponsor );
	$dark_logo_url  = ss_sponsor_dark_logo_url( $sponsor );
	$has_dark       = ! empty( $dark_logo_url );
	$show_desc      = $display['show_descriptions'] && ! empty( $sponsor['description'] );
	$new_tab        = ! empty( $display['open_links_new_tab'] );
	$link_target    = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
	$has_link       = ! empty( $sponsor['website_link'] );
	$density_class  = 'compact' === ( $display['card_density'] ?? 'comfortable' ) ? ' sponsor-card--compact' : '';
	?>
	<article class="sponsor-card<?php echo esc_attr( $density_class ); ?>">
		<?php if ( $logo_url ) : ?>
		<div class="sponsor-card__logo-wrap" style="--logo-max-h: <?php echo esc_attr( $display['logo_max_height'] ?? '64' ); ?>px;">
			<img
				class="sponsor-card__logo sponsor-card__logo--light<?php echo $has_dark ? '' : ' sponsor-card__logo--only'; ?>"
				src="<?php echo esc_url( $logo_url ); ?>"
				alt="<?php echo esc_attr( $sponsor['sponsor_name'] ); ?>"
				loading="lazy"
				decoding="async"
			/>
			<?php if ( $has_dark ) : ?>
			<img
				class="sponsor-card__logo sponsor-card__logo--dark"
				src="<?php echo esc_url( $dark_logo_url ); ?>"
				alt="<?php echo esc_attr( $sponsor['sponsor_name'] ); ?>"
				loading="lazy"
				decoding="async"
			/>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<div class="sponsor-card__body">
			<h4 class="sponsor-card__name"><?php echo esc_html( $sponsor['sponsor_name'] ); ?></h4>
			<?php if ( $show_desc ) : ?>
				<p class="sponsor-card__desc"><?php echo wp_kses_post( $sponsor['description'] ); ?></p>
			<?php endif; ?>
			<?php if ( $has_link ) : ?>
				<a class="sponsor-card__link" href="<?php echo esc_url( $sponsor['website_link'] ); ?>"<?php echo $link_target; ?>>
					<?php esc_html_e( 'Visit website', 'sender-symposium' ); ?>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
					<?php if ( $new_tab ) : ?>
						<span class="sr-only"><?php esc_html_e( '(opens in new tab)', 'sender-symposium' ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Shortcode: [ss_sponsors]
 */
function ss_sponsors_shortcode( $atts ) {
	$display  = ss_get_sponsors_display();
	$defaults = array(
		'show_unconfirmed'  => '0',
		'levels'            => '',
		'columns_desktop'   => $display['grid_columns_desktop'],
		'columns_tablet'    => $display['grid_columns_tablet'],
		'columns_mobile'    => $display['grid_columns_mobile'],
		'show_descriptions' => $display['show_descriptions'] ? '1' : '0',
		'curated'           => '1',
	);
	$a = shortcode_atts( $defaults, $atts, 'ss_sponsors' );

	/* Merge shortcode overrides into display */
	$display['grid_columns_desktop'] = $a['columns_desktop'];
	$display['grid_columns_tablet']  = $a['columns_tablet'];
	$display['grid_columns_mobile']  = $a['columns_mobile'];
	$display['show_descriptions']    = '1' === $a['show_descriptions'];

	$sort     = '1' === $a['curated'] ? 'manual' : 'alphabetical';
	$sponsors = '1' === $a['show_unconfirmed'] ? ss_get_sponsors() : ss_get_confirmed_sponsors( $sort );

	/* Filter by levels if specified */
	if ( ! empty( $a['levels'] ) ) {
		$allowed = array_map( 'strtolower', array_map( 'trim', explode( ',', $a['levels'] ) ) );
		/* Map friendly names to keys */
		$level_keys = array();
		$all_levels = ss_sponsor_levels();
		foreach ( $allowed as $l ) {
			foreach ( $all_levels as $key => $label ) {
				if ( $key === $l || strtolower( $label ) === $l ) {
					$level_keys[] = $key;
				}
			}
		}
		if ( ! empty( $level_keys ) ) {
			$sponsors = array_filter( $sponsors, function ( $s ) use ( $level_keys ) {
				return in_array( $s['sponsor_level'], $level_keys, true );
			} );
			$sponsors = array_values( $sponsors );
		}
	}

	/* Sort by level priority then order */
	$level_priority = array( 'platinum' => 0, 'gold' => 1, 'silver' => 2, 'bronze' => 3 );
	usort( $sponsors, function ( $x, $y ) use ( $level_priority, $sort ) {
		$la = $level_priority[ $x['sponsor_level'] ] ?? 9;
		$lb = $level_priority[ $y['sponsor_level'] ] ?? 9;
		if ( $la !== $lb ) {
			return $la - $lb;
		}
		if ( 'alphabetical' === $sort ) {
			return strcasecmp( $x['sponsor_name'], $y['sponsor_name'] );
		}
		return ( $x['order'] ?? 0 ) - ( $y['order'] ?? 0 );
	} );

	/* Group by level */
	$grouped = array();
	foreach ( $sponsors as $s ) {
		$grouped[ $s['sponsor_level'] ][] = $s;
	}

	/* Title map */
	$level_titles = array(
		'platinum' => $display['platinum_title'],
		'gold'     => $display['gold_title'],
		'silver'   => $display['silver_title'],
		'bronze'   => $display['bronze_title'],
	);

	ob_start();
	?>
	<div class="ss-sponsors" style="--spr-cols-d:<?php echo esc_attr( $display['grid_columns_desktop'] ); ?>;--spr-cols-t:<?php echo esc_attr( $display['grid_columns_tablet'] ); ?>;--spr-cols-m:<?php echo esc_attr( $display['grid_columns_mobile'] ); ?>;">

		<?php if ( ! empty( $display['page_title'] ) ) : ?>
		<header class="ss-sponsors__header">
			<h2 class="ss-sponsors__title"><?php echo esc_html( $display['page_title'] ); ?></h2>
			<?php if ( ! empty( $display['page_subtitle'] ) ) : ?>
				<p class="ss-sponsors__subtitle"><?php echo esc_html( $display['page_subtitle'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $display['intro_text'] ) ) : ?>
				<div class="ss-sponsors__intro"><?php echo wp_kses_post( $display['intro_text'] ); ?></div>
			<?php endif; ?>
		</header>
		<?php endif; ?>

		<?php
		/* Platinum section — special handling */
		$platinum = $grouped['platinum'] ?? array();
		?>
		<section class="ss-sponsors__section ss-sponsors__section--platinum" aria-label="<?php echo esc_attr( $level_titles['platinum'] ); ?>">
			<h3 class="ss-sponsors__section-title"><?php echo esc_html( $level_titles['platinum'] ); ?></h3>
			<?php if ( ! empty( $platinum ) ) : ?>
				<div class="sponsor-grid sponsor-grid--platinum">
					<?php foreach ( $platinum as $sponsor ) {
						ss_render_sponsor_card( $sponsor, $display );
					} ?>
				</div>
			<?php else : ?>
				<div class="ss-sponsors__platinum-empty">
					<p class="ss-sponsors__platinum-empty-text"><?php echo esc_html( $display['platinum_empty_text'] ); ?></p>
					<?php if ( ! empty( $display['platinum_empty_cta'] ) ) : ?>
						<?php if ( ! empty( $display['platinum_empty_url'] ) ) : ?>
							<a class="ss-sponsors__platinum-cta" href="<?php echo esc_url( $display['platinum_empty_url'] ); ?>"><?php echo esc_html( $display['platinum_empty_cta'] ); ?></a>
						<?php else : ?>
							<p class="ss-sponsors__platinum-cta-text"><?php echo esc_html( $display['platinum_empty_cta'] ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php
		/* Other levels */
		foreach ( array( 'gold', 'silver', 'bronze' ) as $level ) :
			$level_sponsors = $grouped[ $level ] ?? array();
			if ( empty( $level_sponsors ) ) {
				continue;
			}
		?>
		<section class="ss-sponsors__section ss-sponsors__section--<?php echo esc_attr( $level ); ?>" aria-label="<?php echo esc_attr( $level_titles[ $level ] ); ?>">
			<h3 class="ss-sponsors__section-title"><?php echo esc_html( $level_titles[ $level ] ); ?></h3>
			<div class="sponsor-grid sponsor-grid--<?php echo esc_attr( $level ); ?>">
				<?php foreach ( $level_sponsors as $sponsor ) {
					ss_render_sponsor_card( $sponsor, $display );
				} ?>
			</div>
		</section>
		<?php endforeach; ?>

		<?php /* CTA section */ ?>
		<?php if ( ! empty( $display['cta_heading'] ) ) : ?>
		<section class="ss-sponsors__cta">
			<h3 class="ss-sponsors__cta-heading"><?php echo esc_html( $display['cta_heading'] ); ?></h3>
			<?php if ( ! empty( $display['cta_text'] ) ) : ?>
				<div class="ss-sponsors__cta-text"><?php echo wp_kses_post( $display['cta_text'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $display['cta_button_url'] ) && ! empty( $display['cta_button_label'] ) ) : ?>
				<a class="ss-sponsors__cta-btn" href="<?php echo esc_url( $display['cta_button_url'] ); ?>"><?php echo esc_html( $display['cta_button_label'] ); ?></a>
			<?php endif; ?>
		</section>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ss_sponsors', 'ss_sponsors_shortcode' );
