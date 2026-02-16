<?php
/**
 * Template Name: Sponsors
 * Template for displaying the sponsors / partners page.
 *
 * Create a page with the slug "sponsors" or "partners", or assign
 * the "Sponsors" template from the page editor dropdown.
 *
 * @package SenderSymposium
 */

get_header();
?>

<div class="section">
	<div class="container">
		<?php echo ss_sponsors_shortcode( array() ); ?>
	</div>
</div>

<?php
get_footer();
