<?php
/**
 * Search results template — Sender Symposium
 *
 * @package SenderSymposium
 */

get_header();
?>

<div class="container section">

	<header class="search-header">
		<h1>
			<?php
			printf(
				/* translators: %s: search query */
				esc_html__( 'Search results for: %s', 'sender-symposium' ),
				'<span class="search-term">' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
		<?php if ( have_posts() ) : ?>
			<p class="text-small" style="color: var(--text-muted); margin-top: var(--sp-2);">
				<?php
				printf(
					/* translators: %d: number of results */
					esc_html( _n( '%d result found', '%d results found', (int) $wp_query->found_posts, 'sender-symposium' ) ),
					(int) $wp_query->found_posts
				);
				?>
			</p>
		<?php endif; ?>
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

		<div class="search-no-results" style="text-align: center; padding: var(--sp-8) 0;">
			<p class="text-lg"><?php esc_html_e( 'No results found. Try a different search term.', 'sender-symposium' ); ?></p>
			<div style="margin-top: var(--sp-5); max-width: 480px; margin-inline: auto;">
				<?php get_search_form(); ?>
			</div>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();
