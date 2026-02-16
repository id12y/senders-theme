<?php
/**
 * Sender Symposium — Elementor Integration
 *
 * Theme Builder location registration. Guarded so the theme loads
 * cleanly whether Elementor (free or Pro) is active or not.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
