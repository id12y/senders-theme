<?php
/**
 * Default page template — Sender Symposium
 *
 * @package SenderSymposium
 */

get_header();
?>

<div class="container section">

	<?php while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'flow' ); ?>>

			<?php
			$is_elementor_page = get_post_meta( get_the_ID(), '_elementor_edit_mode', true );
			$elementor_active  = defined( 'ELEMENTOR_VERSION' );
			/*
			 * Hide the theme title when this page was built with Elementor
			 * and Elementor is inactive — Elementor content has its own heading.
			 */
			if ( ! $is_elementor_page || $elementor_active ) :
			?>
			<header>
				<h1><?php echo esc_html( get_the_title() ); ?></h1>
			</header>
			<?php endif; ?>

			<div class="entry-content flow">
				<?php the_content(); ?>
			</div>

		</article>

	<?php endwhile; ?>

</div>

<?php
get_footer();
