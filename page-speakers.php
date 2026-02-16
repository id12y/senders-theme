<?php
/**
 * Template Name: Speakers
 * Template for displaying the speakers page.
 *
 * Uses WordPress page template (page-speakers.php) — create a page with
 * the slug "speakers" or assign the "Speakers" template from the page editor.
 *
 * @package SenderSymposium
 */

get_header();

$display  = ss_get_speakers_display();
$sort     = isset( $_GET['sort'] ) && 'alpha' === $_GET['sort'] ? 'alphabetical' : $display['default_sort'];
$speakers = ss_get_published_speakers( $sort );

/* Split featured / non-featured */
$featured = array();
$rest     = array();
if ( $display['enable_featured'] ) {
	foreach ( $speakers as $s ) {
		if ( ! empty( $s['featured'] ) ) {
			$featured[] = $s;
		} else {
			$rest[] = $s;
		}
	}
} else {
	$rest = $speakers;
}
?>

<div class="section">
	<div class="container">

		<?php if ( ! empty( $display['page_title'] ) ) : ?>
		<header class="speakers-page__header">
			<h1 class="speakers-page__title"><?php echo esc_html( $display['page_title'] ); ?></h1>
			<?php if ( ! empty( $display['page_subtitle'] ) ) : ?>
				<p class="speakers-page__subtitle"><?php echo esc_html( $display['page_subtitle'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $display['intro_text'] ) ) : ?>
				<div class="speakers-page__intro"><?php echo wp_kses_post( $display['intro_text'] ); ?></div>
			<?php endif; ?>
		</header>
		<?php endif; ?>

		<?php if ( empty( $speakers ) ) : ?>
			<p class="speakers-page__empty"><?php esc_html_e( 'Speakers will be announced soon.', 'sender-symposium' ); ?></p>
		<?php else : ?>

			<?php /* Sort toggle */ ?>
			<?php if ( $display['enable_alpha_toggle'] ) : ?>
			<nav class="speakers-sort" aria-label="<?php esc_attr_e( 'Sort speakers', 'sender-symposium' ); ?>">
				<a
					class="speakers-sort__link <?php echo 'manual' === $sort || 'alphabetical' !== $sort ? 'speakers-sort__link--active' : ''; ?>"
					href="<?php echo esc_url( remove_query_arg( 'sort' ) ); ?>"
				>
					<?php esc_html_e( 'Curated', 'sender-symposium' ); ?>
				</a>
				<a
					class="speakers-sort__link <?php echo 'alphabetical' === $sort ? 'speakers-sort__link--active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'sort', 'alpha' ) ); ?>"
				>
					<?php echo esc_html( $display['alpha_toggle_label'] ); ?>
				</a>
			</nav>
			<?php endif; ?>

			<?php /* Featured speakers section */ ?>
			<?php if ( ! empty( $featured ) ) : ?>
			<section class="speakers-section" aria-label="<?php esc_attr_e( 'Featured speakers', 'sender-symposium' ); ?>">
				<?php if ( ! empty( $display['featured_title'] ) ) : ?>
				<header class="speakers-section__header">
					<h2><?php echo esc_html( $display['featured_title'] ); ?></h2>
					<?php if ( ! empty( $display['featured_subtitle'] ) ) : ?>
						<p><?php echo esc_html( $display['featured_subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
				<?php endif; ?>
				<div class="speaker-grid" style="--speaker-cols: <?php echo esc_attr( $display['grid_columns'] ); ?>;">
					<?php foreach ( $featured as $speaker ) {
						ss_render_speaker_card( $speaker, $display );
					} ?>
				</div>
			</section>
			<?php endif; ?>

			<?php /* All speakers section */ ?>
			<?php if ( ! empty( $rest ) ) : ?>
			<section class="speakers-section" aria-label="<?php esc_attr_e( 'All speakers', 'sender-symposium' ); ?>">
				<?php if ( ! empty( $display['all_title'] ) && ! empty( $featured ) ) : ?>
				<header class="speakers-section__header">
					<h2><?php echo esc_html( $display['all_title'] ); ?></h2>
					<?php if ( ! empty( $display['all_subtitle'] ) ) : ?>
						<p><?php echo esc_html( $display['all_subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
				<?php endif; ?>
				<div class="speaker-grid" style="--speaker-cols: <?php echo esc_attr( $display['grid_columns'] ); ?>;">
					<?php foreach ( $rest as $speaker ) {
						ss_render_speaker_card( $speaker, $display );
					} ?>
				</div>
			</section>
			<?php endif; ?>

		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
