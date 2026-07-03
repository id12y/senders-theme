<?php
/**
 * Plugin Name: Elementor Posts Widget - Categories Meta Extension
 * Description: Adds a "Categories" option to the Meta Data control of the Elementor Pro Posts widget, output as linked, comma-separated category archives.
 * Version:     1.0.0
 * Author:      Senders
 * License:     GPL-2.0+
 * Requires Plugins: elementor
 *
 * @package Elementor_Posts_Meta_Categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Boot only when Elementor (and the Pro Posts widget) can exist.
 * Hooked on plugins_loaded so a missing/deactivated Elementor never fatals.
 */
function epmc_init() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'epmc_missing_elementor_notice' );
		return;
	}

	// The Posts widget registers its skin controls on this same hook at
	// priority 10, so priority 20 guarantees the meta_data controls exist.
	add_action( 'elementor/element/posts/section_layout/before_section_end', 'epmc_add_categories_option', 20 );

	add_filter( 'elementor/widget/render_content', 'epmc_render_categories', 10, 2 );
}
add_action( 'plugins_loaded', 'epmc_init' );

/**
 * Admin notice shown when Elementor is not active.
 */
function epmc_missing_elementor_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Elementor Posts Widget - Categories Meta Extension requires Elementor (and Elementor Pro for the Posts widget) to be active.', 'epmc' )
	);
}

/**
 * Inject "Categories" into every skin's meta_data control on the Posts widget.
 *
 * Skin controls are ID-prefixed (classic_meta_data, cards_meta_data,
 * full_content_meta_data), so we match by suffix to cover all skins,
 * including third-party ones.
 *
 * @param \Elementor\Widget_Base $element The Posts widget instance.
 */
function epmc_add_categories_option( $element ) {
	$controls = $element->get_controls();

	if ( ! is_array( $controls ) ) {
		return;
	}

	foreach ( $controls as $control_id => $control ) {
		if ( substr( $control_id, -strlen( '_meta_data' ) ) !== '_meta_data' ) {
			continue;
		}

		if ( empty( $control['options'] ) || isset( $control['options']['categories'] ) ) {
			continue;
		}

		$control['options']['categories'] = esc_html__( 'Categories', 'epmc' );

		$element->update_control( $control_id, [ 'options' => $control['options'] ] );
	}
}

/**
 * Inject linked category lists into the rendered Posts widget markup.
 *
 * Elementor Pro's Skin_Base::render_meta_data() has no filter of its own,
 * but it always prints the .elementor-post__meta-data wrapper when any meta
 * option (including ours) is selected. We locate each rendered <article>,
 * read its post ID from the post-{ID} class, and prepend the category links
 * inside that wrapper.
 *
 * @param string                 $content Rendered widget HTML.
 * @param \Elementor\Widget_Base $widget  The widget instance.
 * @return string
 */
function epmc_render_categories( $content, $widget ) {
	if ( 'posts' !== $widget->get_name() ) {
		return $content;
	}

	$skin_id  = $widget->get_settings( '_skin' );
	$skin_id  = $skin_id ? $skin_id : 'classic';
	$settings = $widget->get_settings_for_display();
	$meta     = isset( $settings[ $skin_id . '_meta_data' ] ) ? (array) $settings[ $skin_id . '_meta_data' ] : [];

	if ( ! in_array( 'categories', $meta, true ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/<article[^>]*\bclass="[^"]*\bpost-(\d+)\b[^"]*"[^>]*>.*?<\/article>/s',
		'epmc_inject_into_article',
		$content
	);
}

/**
 * preg_replace_callback handler: add the category span to one article block.
 *
 * @param array $matches [0] full <article> markup, [1] post ID.
 * @return string
 */
function epmc_inject_into_article( $matches ) {
	$article    = $matches[0];
	$post_id    = (int) $matches[1];
	$categories = get_the_category( $post_id );

	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		return $article;
	}

	$links = [];

	foreach ( $categories as $category ) {
		$url = get_category_link( $category->term_id );

		if ( is_wp_error( $url ) ) {
			continue;
		}

		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html( $category->name )
		);
	}

	if ( empty( $links ) ) {
		return $article;
	}

	// A plain child <span> inherits Elementor's own meta separator styling.
	$html = '<span class="elementor-post-categories">' . implode( ', ', $links ) . '</span>';

	// Insert just inside the meta wrapper; fall back to no-op if a custom
	// skin renders without one.
	$needle = 'class="elementor-post__meta-data';
	$pos    = strpos( $article, $needle );

	if ( false === $pos ) {
		return $article;
	}

	$tag_end = strpos( $article, '>', $pos );

	if ( false === $tag_end ) {
		return $article;
	}

	return substr( $article, 0, $tag_end + 1 ) . $html . substr( $article, $tag_end + 1 );
}
