<?php
/**
 * Sponsors — Display Settings
 *
 * Default values and accessors for the Sponsors page copy and layout.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ss_sponsors_display_defaults() {
	return array(
		'page_title'            => __( 'Partners', 'sender-symposium' ),
		'page_subtitle'         => __( 'The organisations making the Sender Symposium possible', 'sender-symposium' ),
		'intro_text'            => '',
		'platinum_title'        => __( 'Platinum Headline Partner', 'sender-symposium' ),
		'platinum_empty_text'   => __( 'Headline Partner Slot Available', 'sender-symposium' ),
		'platinum_empty_cta'    => __( 'Interested in partnering with us?', 'sender-symposium' ),
		'platinum_empty_url'    => '',
		'gold_title'            => __( 'Gold Partners', 'sender-symposium' ),
		'silver_title'          => __( 'Silver Partners', 'sender-symposium' ),
		'bronze_title'          => __( 'Supporting Partners', 'sender-symposium' ),
		'cta_heading'           => __( 'Interested in partnering with us?', 'sender-symposium' ),
		'cta_text'              => '',
		'cta_button_label'      => __( 'Get in touch', 'sender-symposium' ),
		'cta_button_url'        => '',
		'grid_columns_desktop'  => '4',
		'grid_columns_tablet'   => '3',
		'grid_columns_mobile'   => '2',
		'card_density'          => 'comfortable',
		'logo_max_height'       => '64',
		'show_descriptions'     => true,
		'open_links_new_tab'    => true,
	);
}

function ss_get_sponsors_display() {
	return wp_parse_args( get_option( 'ss_sponsors_display', array() ), ss_sponsors_display_defaults() );
}

function ss_save_sponsors_display( $data ) {
	$clean = array(
		'page_title'            => sanitize_text_field( $data['page_title'] ?? '' ),
		'page_subtitle'         => sanitize_text_field( $data['page_subtitle'] ?? '' ),
		'intro_text'            => wp_kses_post( $data['intro_text'] ?? '' ),
		'platinum_title'        => sanitize_text_field( $data['platinum_title'] ?? '' ),
		'platinum_empty_text'   => sanitize_text_field( $data['platinum_empty_text'] ?? '' ),
		'platinum_empty_cta'    => sanitize_text_field( $data['platinum_empty_cta'] ?? '' ),
		'platinum_empty_url'    => esc_url_raw( $data['platinum_empty_url'] ?? '' ),
		'gold_title'            => sanitize_text_field( $data['gold_title'] ?? '' ),
		'silver_title'          => sanitize_text_field( $data['silver_title'] ?? '' ),
		'bronze_title'          => sanitize_text_field( $data['bronze_title'] ?? '' ),
		'cta_heading'           => sanitize_text_field( $data['cta_heading'] ?? '' ),
		'cta_text'              => wp_kses_post( $data['cta_text'] ?? '' ),
		'cta_button_label'      => sanitize_text_field( $data['cta_button_label'] ?? '' ),
		'cta_button_url'        => esc_url_raw( $data['cta_button_url'] ?? '' ),
		'grid_columns_desktop'  => in_array( $data['grid_columns_desktop'] ?? '', array( '2', '3', '4', '5' ), true )
			? $data['grid_columns_desktop'] : '4',
		'grid_columns_tablet'   => in_array( $data['grid_columns_tablet'] ?? '', array( '2', '3', '4' ), true )
			? $data['grid_columns_tablet'] : '3',
		'grid_columns_mobile'   => in_array( $data['grid_columns_mobile'] ?? '', array( '1', '2' ), true )
			? $data['grid_columns_mobile'] : '2',
		'card_density'          => in_array( $data['card_density'] ?? '', array( 'comfortable', 'compact' ), true )
			? $data['card_density'] : 'comfortable',
		'logo_max_height'       => max( 32, min( 160, absint( $data['logo_max_height'] ?? 64 ) ) ),
		'show_descriptions'     => ! empty( $data['show_descriptions'] ),
		'open_links_new_tab'    => ! empty( $data['open_links_new_tab'] ),
	);
	update_option( 'ss_sponsors_display', $clean, false );
}
