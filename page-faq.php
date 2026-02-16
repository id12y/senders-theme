<?php
/**
 * Template Name: FAQ
 * Template for displaying the FAQ page.
 *
 * Uses <details><summary> for an accessible accordion that degrades
 * gracefully without JavaScript. JS enhances with single-open behaviour.
 *
 * Create a page with the slug "faq" or assign the "FAQ" template.
 *
 * @package SenderSymposium
 */

get_header();

$faq      = ss_get_faq();
$settings = $faq['settings'];
$sections = $faq['sections'];

$page_classes = 'faq-page';
if ( 'list' === $settings['hotel_display_style'] ) {
	$page_classes .= ' faq-page--hotel-list';
}
?>

<div class="<?php echo esc_attr( $page_classes ); ?>"
	data-single-open="<?php echo $settings['accordion_single_open'] ? 'true' : 'false'; ?>">
	<div class="container">

		<header class="faq-page-header">
			<h1 class="faq-page-title"><?php echo esc_html( $faq['page_title'] ); ?></h1>
			<?php if ( $settings['show_page_subtitle'] && ! empty( $faq['page_subtitle'] ) ) : ?>
				<p class="faq-page-subtitle"><?php echo esc_html( $faq['page_subtitle'] ); ?></p>
			<?php endif; ?>
			<?php if ( $settings['show_intro_paragraph'] && ! empty( $faq['intro_paragraph'] ) ) : ?>
				<div class="faq-intro"><?php echo wp_kses_post( $faq['intro_paragraph'] ); ?></div>
			<?php endif; ?>
		</header>

		<?php if ( empty( $sections ) ) : ?>
			<p class="faq-empty"><?php esc_html_e( 'No FAQ content yet.', 'sender-symposium' ); ?></p>
		<?php else : ?>

			<?php foreach ( $sections as $section ) :
				$section_class = 'faq-section';
				if ( 'elevated' === $settings['section_style'] ) {
					$section_class .= ' elevated';
				}
			?>
			<section class="<?php echo esc_attr( $section_class ); ?>"
				aria-label="<?php echo esc_attr( $section['title'] ); ?>">

				<h2 class="faq-section-title"><?php echo esc_html( $section['title'] ); ?></h2>
				<?php if ( ! empty( $section['subtitle'] ) ) : ?>
					<p class="faq-section-subtitle"><?php echo esc_html( $section['subtitle'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $section['items'] ) ) : ?>
				<div class="faq-accordion">
					<?php foreach ( $section['items'] as $idx => $item ) :
						$open = ( 0 === $idx && $settings['open_first_item_each_section'] ) ? ' open' : '';
					?>
					<details class="faq-item"<?php echo $open; ?>>
						<summary class="faq-question">
							<span><?php echo esc_html( $item['question'] ); ?></span>
							<svg class="faq-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</summary>
						<div class="faq-answer">
							<?php echo wp_kses_post( $item['answer_html'] ); ?>
						</div>
					</details>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

			</section>
			<?php endforeach; ?>

		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
