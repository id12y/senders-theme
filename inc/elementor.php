<?php
/**
 * Sender Symposium — Elementor Integration
 *
 * Theme Builder location registration and custom widgets.
 * Guarded so the theme loads cleanly whether Elementor is active or not.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   THEME BUILDER LOCATIONS
   ========================================================================= */

/**
 * Register Theme Builder locations.
 *
 * The hook only fires when Elementor is active, so no class_exists or
 * did_action guard is needed on add_action itself.
 *
 * @param object $manager Elementor Locations_Manager instance.
 */
function ss_elementor_locations( $manager ) {
	if ( method_exists( $manager, 'register_all_core_locations' ) ) {
		$manager->register_all_core_locations();
		return;
	}

	/* Elementor Pro 3.35+ — register each core location individually. */
	$core_locations = array( 'header', 'footer', 'single', 'archive' );
	foreach ( $core_locations as $location ) {
		if ( method_exists( $manager, 'register_location' ) ) {
			$manager->register_location( $location );
		}
	}
}
add_action( 'elementor/theme/register_locations', 'ss_elementor_locations' );

/* =========================================================================
   CUSTOM WIDGETS — Theme Toggle
   ========================================================================= */

/**
 * Register custom Elementor widgets.
 *
 * Uses elementor/widgets/register which fires only when Elementor is active.
 */
function ss_register_elementor_widgets( $widgets_manager ) {
	require_once get_template_directory() . '/inc/widgets/class-ss-theme-toggle-widget.php';

	if ( class_exists( 'SS_Theme_Toggle_Widget' ) ) {
		$widgets_manager->register( new SS_Theme_Toggle_Widget() );
	}
}
add_action( 'elementor/widgets/register', 'ss_register_elementor_widgets' );

/**
 * Register a custom widget category for the theme.
 */
function ss_elementor_widget_categories( $elements_manager ) {
	$elements_manager->add_category( 'sender-symposium', array(
		'title' => esc_html__( 'Sender Symposium', 'sender-symposium' ),
		'icon'  => 'eicon-globe',
	) );
}
add_action( 'elementor/elements/categories_registered', 'ss_elementor_widget_categories' );
