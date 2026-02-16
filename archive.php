<?php
/**
 * Archive template — Sender Symposium
 *
 * @package SenderSymposium
 */

get_header();
?>

<div class="container section">

	<header class="archive-header">
		<?php the_archive_title( '<h1>', '</h1>' ); ?>
		<?php the_archive_description( '<div class="archive-description text-lg" style="color: var(--text-secondary); margin-top: var(--sp-2);">', '</div>' ); ?>
	</header>

	<?php if ( have_posts() ) : ?>

		<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'flow' ); ?>>
				<header>
					<h2>
						<a href="<?php the_permalink(); ?>">
							<?php echo esc_html( get_the_title() ); ?>
						</a>
					</h2>
				</header>
				<div class="entry-content flow">
					<?php the_excerpt(); ?>
				</div>
			</article>

		<?php endwhile; ?>

		<?php the_posts_navigation(); ?>

	<?php else : ?>

		<p><?php esc_html_e( 'No posts found in this archive.', 'sender-symposium' ); ?></p>

	<?php endif; ?>

</div>

<?php
get_footer();
