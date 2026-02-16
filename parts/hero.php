<?php
/**
 * Template Part: Hero — La Pedrera-inspired
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ss-hero container" aria-labelledby="hero-title">

	<div class="ss-hero__content">
		<p class="ss-hero__eyebrow">
			<?php
			/* translators: Eyebrow text above the hero title. */
			echo esc_html__( 'Barcelona', 'sender-symposium' )
				. ' &middot; '
				. esc_html__( 'La Pedrera (Casa Mila)', 'sender-symposium' );
			?>
		</p>

		<h1 id="hero-title" class="ss-hero__title">
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
		</h1>

		<p class="ss-hero__subtitle">
			<?php esc_html_e( 'An advanced, practitioner-only symposium for the people who run email sending infrastructure at scale. No vendor pitches. No beginner sessions. Just the conversations that matter.', 'sender-symposium' ); ?>
		</p>

		<div class="ss-hero__meta" aria-label="<?php esc_attr_e( 'Event details', 'sender-symposium' ); ?>">
			<span class="ss-hero__meta-item">
				<strong><?php esc_html_e( '2026', 'sender-symposium' ); ?></strong>
			</span>
			<span class="ss-hero__meta-divider" aria-hidden="true"></span>
			<span class="ss-hero__meta-item">
				<?php esc_html_e( 'Barcelona', 'sender-symposium' ); ?>
			</span>
			<span class="ss-hero__meta-divider" aria-hidden="true"></span>
			<span class="ss-hero__meta-item">
				<?php esc_html_e( 'La Pedrera', 'sender-symposium' ); ?>
			</span>
			<span class="ss-hero__meta-divider" aria-hidden="true"></span>
			<span class="ss-hero__meta-item">
				<span class="badge"><?php esc_html_e( 'Advanced', 'sender-symposium' ); ?></span>
			</span>
		</div>

		<div class="ss-hero__actions">
			<a href="#tickets" class="btn btn--primary">
				<?php esc_html_e( 'Get tickets', 'sender-symposium' ); ?>
			</a>
			<a href="#format" class="btn btn--secondary">
				<?php esc_html_e( 'See the format', 'sender-symposium' ); ?>
			</a>
		</div>
	</div>

	<?php if ( ss_show_hero_field() ) : ?>
	<div class="ss-hero__field" aria-hidden="true">
		<svg class="ss-hero__field-svg" viewBox="0 0 400 500" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1">
			<path d="M50,80 Q100,20 200,60 Q300,100 350,40" stroke-width="1.5" opacity="0.6"/>
			<path d="M30,140 Q120,80 200,120 Q280,160 370,100" stroke-width="1.5" opacity="0.5"/>
			<path d="M40,200 Q130,140 210,180 Q290,220 360,160" stroke-width="1.5" opacity="0.4"/>
			<path d="M50,260 Q140,200 220,240 Q300,280 350,220" stroke-width="1.5" opacity="0.35"/>
			<path d="M30,320 Q120,260 200,300 Q280,340 370,280" stroke-width="1.5" opacity="0.3"/>
			<path d="M40,380 Q130,320 210,360 Q290,400 360,340" stroke-width="1.5" opacity="0.25"/>
			<path d="M50,440 Q140,380 220,420 Q300,460 350,400" stroke-width="1.5" opacity="0.2"/>

			<line x1="100" y1="30" x2="100" y2="470" stroke-width="0.5" opacity="0.15"/>
			<line x1="200" y1="30" x2="200" y2="470" stroke-width="0.5" opacity="0.15"/>
			<line x1="300" y1="30" x2="300" y2="470" stroke-width="0.5" opacity="0.15"/>

			<ellipse cx="150" cy="150" rx="40" ry="25" stroke-width="0.8" opacity="0.2"/>
			<ellipse cx="250" cy="250" rx="45" ry="28" stroke-width="0.8" opacity="0.18"/>
			<ellipse cx="150" cy="350" rx="38" ry="22" stroke-width="0.8" opacity="0.15"/>
			<ellipse cx="250" cy="420" rx="42" ry="26" stroke-width="0.8" opacity="0.12"/>

			<path d="M80,480 Q200,430 320,480" stroke-width="1" opacity="0.2"/>
			<path d="M100,490 Q200,450 300,490" stroke-width="0.8" opacity="0.15"/>
		</svg>
	</div>
	<?php endif; ?>

</section>
