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

			<header>
				<h1><?php the_title(); ?></h1>
			</header>

			<div class="entry-content flow">
				<?php the_content(); ?>
			</div>

		</article>

	<?php endwhile; ?>

</div>

<?php
get_footer();
