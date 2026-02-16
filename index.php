<?php
/**
 * Main template — Sender Symposium
 *
 * @package SenderSymposium
 */

get_header();
?>

<div class="container section">

	<?php if ( have_posts() ) : ?>

		<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'flow' ); ?>>

				<header>
					<?php if ( is_singular() ) : ?>
						<h1><?php the_title(); ?></h1>
					<?php else : ?>
						<h2>
							<a href="<?php the_permalink(); ?>">
								<?php the_title(); ?>
							</a>
						</h2>
					<?php endif; ?>
				</header>

				<div class="entry-content flow">
					<?php
					if ( is_singular() ) {
						the_content();
					} else {
						the_excerpt();
					}
					?>
				</div>

			</article>

		<?php endwhile; ?>

		<?php the_posts_navigation(); ?>

	<?php else : ?>

		<p><?php esc_html_e( 'No content found.', 'sender-symposium' ); ?></p>

	<?php endif; ?>

</div>

<?php
get_footer();
