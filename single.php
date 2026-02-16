<?php
/**
 * Single post template — Sender Symposium
 *
 * @package SenderSymposium
 */

get_header();
?>

<div class="container section">

	<?php while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'flow' ); ?>>

			<header>
				<h1><?php echo esc_html( get_the_title() ); ?></h1>
				<p class="text-small" style="color: var(--text-muted); margin-top: var(--sp-2);">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date() ); ?>
					</time>
				</p>
			</header>

			<div class="entry-content flow">
				<?php the_content(); ?>
			</div>

		</article>

	<?php endwhile; ?>

</div>

<?php
get_footer();
