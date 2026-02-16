<?php
/**
 * Speakers — Display Settings
 *
 * Default values and accessors for the Speakers page copy and layout.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ss_speakers_display_defaults() {
	return array(
		'page_title'          => __( 'Speakers', 'sender-symposium' ),
		'page_subtitle'       => __( 'The people shaping email infrastructure', 'sender-symposium' ),
		'intro_text'          => '',
		'featured_title'      => __( 'Featured Speakers', 'sender-symposium' ),
		'featured_subtitle'   => '',
		'all_title'           => __( 'All Speakers', 'sender-symposium' ),
		'all_subtitle'        => '',
		'alpha_toggle_label'  => __( 'Sort alphabetically', 'sender-symposium' ),
		'default_sort'        => 'manual',
		'enable_alpha_toggle' => true,
		'enable_featured'     => true,
		'grid_columns'        => '3',
		'card_style'          => 'elevated',
		'show_company'        => true,
		'show_linkedin'       => true,
		'show_topic'          => true,
		'show_description'    => true,
	);
}

function ss_get_speakers_display() {
	return wp_parse_args( get_option( 'ss_speakers_display', array() ), ss_speakers_display_defaults() );
}

function ss_save_speakers_display( $data ) {
	$clean = array(
		'page_title'          => sanitize_text_field( $data['page_title'] ?? '' ),
		'page_subtitle'       => sanitize_text_field( $data['page_subtitle'] ?? '' ),
		'intro_text'          => wp_kses_post( $data['intro_text'] ?? '' ),
		'featured_title'      => sanitize_text_field( $data['featured_title'] ?? '' ),
		'featured_subtitle'   => sanitize_text_field( $data['featured_subtitle'] ?? '' ),
		'all_title'           => sanitize_text_field( $data['all_title'] ?? '' ),
		'all_subtitle'        => sanitize_text_field( $data['all_subtitle'] ?? '' ),
		'alpha_toggle_label'  => sanitize_text_field( $data['alpha_toggle_label'] ?? '' ),
		'default_sort'        => in_array( $data['default_sort'] ?? '', array( 'manual', 'alphabetical' ), true )
			? $data['default_sort'] : 'manual',
		'enable_alpha_toggle' => ! empty( $data['enable_alpha_toggle'] ),
		'enable_featured'     => ! empty( $data['enable_featured'] ),
		'grid_columns'        => in_array( $data['grid_columns'] ?? '', array( '2', '3', '4' ), true )
			? $data['grid_columns'] : '3',
		'card_style'          => in_array( $data['card_style'] ?? '', array( 'minimal', 'elevated' ), true )
			? $data['card_style'] : 'elevated',
		'show_company'        => ! empty( $data['show_company'] ),
		'show_linkedin'       => ! empty( $data['show_linkedin'] ),
		'show_topic'          => ! empty( $data['show_topic'] ),
		'show_description'    => ! empty( $data['show_description'] ),
	);
	update_option( 'ss_speakers_display', $clean, false );
}
