<?php
/**
 * Front Page template — Sender Symposium
 *
 * Demonstrates the full homepage: hero, event strip, audience targeting,
 * value propositions, format, credibility, testimonials, FAQ, CTA, and footer.
 * All content blocks are admin-editable via Elementor or page content.
 *
 * @package SenderSymposium
 */

get_header();

/* Hero section */
get_template_part( 'parts/hero' );
?>

<!-- Event Info Strip -->
<section class="event-strip" aria-label="<?php esc_attr_e( 'Event details', 'sender-symposium' ); ?>">
	<div class="event-strip__item">
		<span class="event-strip__label"><?php esc_html_e( 'Date', 'sender-symposium' ); ?></span>
		<span class="event-strip__value"><?php esc_html_e( '2026', 'sender-symposium' ); ?></span>
	</div>
	<div class="event-strip__item">
		<span class="event-strip__label"><?php esc_html_e( 'City', 'sender-symposium' ); ?></span>
		<span class="event-strip__value"><?php esc_html_e( 'Barcelona', 'sender-symposium' ); ?></span>
	</div>
	<div class="event-strip__item">
		<span class="event-strip__label"><?php esc_html_e( 'Venue', 'sender-symposium' ); ?></span>
		<span class="event-strip__value"><?php esc_html_e( 'La Pedrera', 'sender-symposium' ); ?></span>
	</div>
	<div class="event-strip__item">
		<span class="event-strip__label"><?php esc_html_e( 'Level', 'sender-symposium' ); ?></span>
		<span class="event-strip__value"><?php esc_html_e( 'Advanced', 'sender-symposium' ); ?></span>
	</div>
</section>

<!-- Who This Is For / Not For -->
<section class="section" aria-labelledby="audience-heading">
	<div class="container flow" style="--flow-space: var(--sp-6);">
		<div style="text-align: center;">
			<span class="eyebrow"><?php esc_html_e( 'Audience', 'sender-symposium' ); ?></span>
			<h2 id="audience-heading" style="margin-top: var(--sp-2);"><?php esc_html_e( 'Is this event for you?', 'sender-symposium' ); ?></h2>
		</div>
		<div class="audience-block">
			<div class="audience-block__col audience-block__col--for">
				<h3 class="audience-block__title"><?php esc_html_e( 'This is for you if...', 'sender-symposium' ); ?></h3>
				<ul class="audience-block__list">
					<li><?php esc_html_e( 'You run or lead an email sending operation at scale', 'sender-symposium' ); ?></li>
					<li><?php esc_html_e( 'You make deliverability and infrastructure decisions', 'sender-symposium' ); ?></li>
					<li><?php esc_html_e( 'You want peer-level conversation, not beginner tutorials', 'sender-symposium' ); ?></li>
					<li><?php esc_html_e( 'You value depth, nuance, and real operational experience', 'sender-symposium' ); ?></li>
				</ul>
			</div>
			<div class="audience-block__col audience-block__col--not">
				<h3 class="audience-block__title"><?php esc_html_e( 'This is not for you if...', 'sender-symposium' ); ?></h3>
				<ul class="audience-block__list">
					<li><?php esc_html_e( 'You are looking for a marketing automation workshop', 'sender-symposium' ); ?></li>
					<li><?php esc_html_e( 'You want vendor pitches disguised as sessions', 'sender-symposium' ); ?></li>
					<li><?php esc_html_e( 'You need basic email marketing guidance', 'sender-symposium' ); ?></li>
					<li><?php esc_html_e( 'You prefer large, expo-style conferences', 'sender-symposium' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</section>

<!-- What You Will Gain -->
<section class="section section--alt" aria-labelledby="outcomes-heading">
	<div class="container flow" style="--flow-space: var(--sp-6);">
		<div style="text-align: center;">
			<span class="eyebrow"><?php esc_html_e( 'Outcomes', 'sender-symposium' ); ?></span>
			<h2 id="outcomes-heading" style="margin-top: var(--sp-2);"><?php esc_html_e( 'What you will take away', 'sender-symposium' ); ?></h2>
		</div>
		<div class="card-grid">
			<div class="value-card">
				<div class="value-card__number">01</div>
				<h3 class="value-card__title"><?php esc_html_e( 'Operational clarity', 'sender-symposium' ); ?></h3>
				<p class="value-card__body"><?php esc_html_e( 'Concrete strategies you can implement the week you return, not abstract theory.', 'sender-symposium' ); ?></p>
			</div>
			<div class="value-card">
				<div class="value-card__number">02</div>
				<h3 class="value-card__title"><?php esc_html_e( 'Peer network', 'sender-symposium' ); ?></h3>
				<p class="value-card__body"><?php esc_html_e( 'Direct relationships with the people who face the same challenges you do, at the same level.', 'sender-symposium' ); ?></p>
			</div>
			<div class="value-card">
				<div class="value-card__number">03</div>
				<h3 class="value-card__title"><?php esc_html_e( 'Decision confidence', 'sender-symposium' ); ?></h3>
				<p class="value-card__body"><?php esc_html_e( 'The context to make informed infrastructure and deliverability decisions under real-world constraints.', 'sender-symposium' ); ?></p>
			</div>
		</div>
	</div>
</section>

<!-- Why This Format Is Different -->
<section class="section" aria-labelledby="format-heading">
	<div class="container flow" style="--flow-space: var(--sp-6);">
		<div style="text-align: center;">
			<span class="eyebrow"><?php esc_html_e( 'Format', 'sender-symposium' ); ?></span>
			<h2 id="format-heading" style="margin-top: var(--sp-2);"><?php esc_html_e( 'Why this is not another conference', 'sender-symposium' ); ?></h2>
			<p class="text-lg" style="margin-inline: auto; color: var(--text-secondary); margin-top: var(--sp-3);">
				<?php esc_html_e( 'Small group. Curated attendees. No expo hall. No filler sessions. Every conversation is the point.', 'sender-symposium' ); ?>
			</p>
		</div>
		<div class="format-block">
			<div class="format-item">
				<h3 class="format-item__title"><?php esc_html_e( 'Curated, not open', 'sender-symposium' ); ?></h3>
				<p class="format-item__body"><?php esc_html_e( 'Attendance is limited and intentionally selected to ensure every participant contributes meaningfully.', 'sender-symposium' ); ?></p>
			</div>
			<div class="format-item">
				<h3 class="format-item__title"><?php esc_html_e( 'Practitioner-led', 'sender-symposium' ); ?></h3>
				<p class="format-item__body"><?php esc_html_e( 'Sessions are led by operators who do this work daily, not keynote speakers reading slides.', 'sender-symposium' ); ?></p>
			</div>
			<div class="format-item">
				<h3 class="format-item__title"><?php esc_html_e( 'Depth over breadth', 'sender-symposium' ); ?></h3>
				<p class="format-item__body"><?php esc_html_e( 'Fewer topics, explored thoroughly. Extended sessions with time for genuine discussion and follow-up.', 'sender-symposium' ); ?></p>
			</div>
		</div>
	</div>
</section>

<!-- Credibility Signals -->
<section class="section section--alt" aria-labelledby="credibility-heading">
	<div class="container">
		<h2 id="credibility-heading" class="sr-only"><?php esc_html_e( 'Event credibility', 'sender-symposium' ); ?></h2>
		<div class="credibility-row">
			<div class="credibility-item">
				<div class="credibility-item__number"><?php esc_html_e( '80', 'sender-symposium' ); ?></div>
				<div class="credibility-item__label"><?php esc_html_e( 'Attendees max', 'sender-symposium' ); ?></div>
			</div>
			<div class="credibility-item">
				<div class="credibility-item__number"><?php esc_html_e( '2', 'sender-symposium' ); ?></div>
				<div class="credibility-item__label"><?php esc_html_e( 'Days', 'sender-symposium' ); ?></div>
			</div>
			<div class="credibility-item">
				<div class="credibility-item__number"><?php esc_html_e( '12', 'sender-symposium' ); ?></div>
				<div class="credibility-item__label"><?php esc_html_e( 'Sessions', 'sender-symposium' ); ?></div>
			</div>
			<div class="credibility-item">
				<div class="credibility-item__number"><?php esc_html_e( '0', 'sender-symposium' ); ?></div>
				<div class="credibility-item__label"><?php esc_html_e( 'Vendor pitches', 'sender-symposium' ); ?></div>
			</div>
		</div>
	</div>
</section>

<!-- Risk Reduction / Guarantees -->
<section class="section" aria-labelledby="risk-heading">
	<div class="container flow" style="--flow-space: var(--sp-5); max-width: 720px; margin-inline: auto; text-align: center;">
		<span class="eyebrow"><?php esc_html_e( 'No risk', 'sender-symposium' ); ?></span>
		<h2 id="risk-heading" style="margin-top: var(--sp-2);"><?php esc_html_e( 'We stand behind the experience', 'sender-symposium' ); ?></h2>
		<p class="text-lg" style="color: var(--text-secondary);">
			<?php esc_html_e( 'If the event does not meet your expectations, we offer a full refund. No questions, no friction. We are confident enough in the quality of the programme and the people in the room.', 'sender-symposium' ); ?>
		</p>
	</div>
</section>

<!-- Page Content (Elementor or editor content) -->
<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( trim( get_the_content() ) ) : ?>
			<div class="container section">
				<div class="entry-content flow">
					<?php the_content(); ?>
				</div>
			</div>
		<?php endif; ?>
	<?php endwhile; ?>
<?php endif; ?>

<!-- Final CTA -->
<section class="section">
	<div class="container">
		<div class="cta-block">
			<h2 class="cta-block__title"><?php esc_html_e( 'Ready to join the conversation?', 'sender-symposium' ); ?></h2>
			<p class="cta-block__body">
				<?php esc_html_e( 'Secure your place at Sender Symposium. Limited to 80 attendees to preserve the quality of conversation.', 'sender-symposium' ); ?>
			</p>
			<div class="cta-block__actions">
				<a href="#tickets" class="btn btn--primary"><?php esc_html_e( 'Get tickets', 'sender-symposium' ); ?></a>
				<a href="#format" class="btn btn--secondary"><?php esc_html_e( 'See the format', 'sender-symposium' ); ?></a>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
