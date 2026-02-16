<?php
/**
 * Front Page template — Sender Symposium
 *
 * Outputs page content via the_content() so the page builder (Elementor
 * or Gutenberg) owns all editable content. The theme provides header,
 * footer, and styling — content is fully editable from the page editor.
 *
 * All CSS component classes (event-strip, audience-block, value-card,
 * format-block, credibility-row, cta-block, etc.) remain available in
 * the stylesheets for use inside Elementor HTML widgets or Gutenberg
 * Custom HTML blocks.
 *
 * @package SenderSymposium
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

	<div class="entry-content">
		<?php the_content(); ?>
	</div>

<?php endwhile; ?>

<?php
get_footer();
