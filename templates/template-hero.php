<?php
/**
 * Template Name: Page with Hero
 * Template Post Type: page
 *
 * Page template with the La Pedrera-inspired hero section.
 *
 * @package SenderSymposium
 */

get_header();

get_template_part( 'parts/hero' );
?>

<div class="container section">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="entry-content flow">
			<?php the_content(); ?>
		</div>
		<?php
	endwhile;
	?>
</div>

<?php
get_footer();
