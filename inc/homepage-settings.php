<?php
/**
 * Homepage Content Settings
 *
 * Default content, accessor, and save functions for the front-page
 * content blocks. Each section can be toggled on/off from admin.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default homepage content — used on first install and as fallback.
 */
function ss_homepage_defaults() {
	return array(
		/* ── Event Info Strip ── */
		'event_strip_enabled' => true,
		'event_strip'         => array(
			array( 'label' => 'Date',     'value' => 'January 2026' ),
			array( 'label' => 'Location', 'value' => 'Barcelona, Spain' ),
			array( 'label' => 'Venue',    'value' => 'La Pedrera (Casa Mila)' ),
			array( 'label' => 'Format',   'value' => 'Single Track · 200 Attendees' ),
		),

		/* ── Audience Block ── */
		'audience_enabled'   => true,
		'audience_title'     => __( 'Who Should Attend', 'sender-symposium' ),
		'audience_title_for' => __( 'This Is For You If…', 'sender-symposium' ),
		'audience_items_for' => "You send email at scale — transactional, marketing, or both\nYou manage email infrastructure or deliverability\nYou build tools or platforms for email senders\nYou want to understand where email technology is heading",
		'audience_title_not' => __( 'Probably Not Your Event If…', 'sender-symposium' ),
		'audience_items_not' => "You're looking for basic email marketing tips\nYou don't touch the technical side of email\nYou want a huge expo-hall conference",

		/* ── Value / Outcome Cards ── */
		'values_enabled' => true,
		'values_title'   => __( 'What You\'ll Walk Away With', 'sender-symposium' ),
		'values'         => array(
			array( 'number' => '01', 'title' => 'Operational Clarity',  'body' => 'Concrete frameworks for scaling email infrastructure without firefighting.' ),
			array( 'number' => '02', 'title' => 'Technical Edge',       'body' => 'Deep dives on authentication, deliverability, and emerging protocols.' ),
			array( 'number' => '03', 'title' => 'Peer Network',         'body' => 'Relationships with the 200 people who actually understand your challenges.' ),
		),

		/* ── Format Block ── */
		'format_enabled' => true,
		'format_title'   => __( 'How It Works', 'sender-symposium' ),
		'format_items'   => array(
			array( 'title' => 'Keynotes',   'body' => 'Big-picture talks from industry leaders on where email is going.' ),
			array( 'title' => 'Deep Dives', 'body' => 'Technical sessions with real data, real code, and real results.' ),
			array( 'title' => 'Workshops',  'body' => 'Hands-on sessions where you build and test alongside experts.' ),
			array( 'title' => 'Networking', 'body' => 'Curated introductions, roundtables, and plenty of unstructured time.' ),
		),

		/* ── Credibility Row ── */
		'credibility_enabled' => true,
		'credibility_items'   => array(
			array( 'number' => '200', 'label' => 'Attendees' ),
			array( 'number' => '30+', 'label' => 'Speakers' ),
			array( 'number' => '2',   'label' => 'Days' ),
			array( 'number' => '1',   'label' => 'Track' ),
		),

		/* ── CTA Block ── */
		'cta_enabled'        => true,
		'cta_title'          => __( 'Ready to Join?', 'sender-symposium' ),
		'cta_body'           => __( 'Secure your spot at the conference built for people who send email at scale.', 'sender-symposium' ),
		'cta_button_text'    => __( 'Get Tickets', 'sender-symposium' ),
		'cta_button_url'     => '#',
		'cta_secondary_text' => __( 'View Speakers', 'sender-symposium' ),
		'cta_secondary_url'  => '/speakers/',
	);
}

/**
 * Get current homepage content merged with defaults.
 */
function ss_get_homepage() {
	$saved    = get_option( 'ss_homepage', array() );
	$defaults = ss_homepage_defaults();

	/* Merge scalar keys */
	$merged = wp_parse_args( $saved, $defaults );

	/* Array keys need special handling — don't merge, use saved if present */
	$array_keys = array( 'event_strip', 'values', 'format_items', 'credibility_items' );
	foreach ( $array_keys as $key ) {
		if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
			$merged[ $key ] = $saved[ $key ];
		}
	}

	return $merged;
}

/**
 * Sanitize and save homepage content.
 *
 * @param array $raw Raw POST data.
 */
function ss_save_homepage( $raw ) {
	$clean = array();

	/* Booleans (checkboxes) */
	$toggles = array(
		'event_strip_enabled', 'audience_enabled', 'values_enabled',
		'format_enabled', 'credibility_enabled', 'cta_enabled',
	);
	foreach ( $toggles as $key ) {
		$clean[ $key ] = ! empty( $raw[ $key ] );
	}

	/* Scalar text fields */
	$text_fields = array(
		'audience_title', 'audience_title_for', 'audience_title_not',
		'values_title', 'format_title',
		'cta_title', 'cta_body', 'cta_button_text', 'cta_secondary_text',
	);
	foreach ( $text_fields as $key ) {
		$clean[ $key ] = sanitize_text_field( $raw[ $key ] ?? '' );
	}

	/* URL fields */
	$clean['cta_button_url']    = esc_url_raw( $raw['cta_button_url'] ?? '#' );
	$clean['cta_secondary_url'] = esc_url_raw( $raw['cta_secondary_url'] ?? '' );

	/* Textarea fields (one item per line) */
	$clean['audience_items_for'] = sanitize_textarea_field( $raw['audience_items_for'] ?? '' );
	$clean['audience_items_not'] = sanitize_textarea_field( $raw['audience_items_not'] ?? '' );

	/* Event strip items (array of label + value) */
	$clean['event_strip'] = array();
	if ( isset( $raw['event_strip'] ) && is_array( $raw['event_strip'] ) ) {
		foreach ( $raw['event_strip'] as $item ) {
			$label = sanitize_text_field( $item['label'] ?? '' );
			$value = sanitize_text_field( $item['value'] ?? '' );
			if ( '' !== $label && '' !== $value ) {
				$clean['event_strip'][] = array( 'label' => $label, 'value' => $value );
			}
		}
	}

	/* Value cards (number, title, body) */
	$clean['values'] = array();
	if ( isset( $raw['values'] ) && is_array( $raw['values'] ) ) {
		foreach ( $raw['values'] as $item ) {
			$num   = sanitize_text_field( $item['number'] ?? '' );
			$title = sanitize_text_field( $item['title'] ?? '' );
			$body  = sanitize_text_field( $item['body'] ?? '' );
			if ( '' !== $title ) {
				$clean['values'][] = array( 'number' => $num, 'title' => $title, 'body' => $body );
			}
		}
	}

	/* Format items (title, body) */
	$clean['format_items'] = array();
	if ( isset( $raw['format_items'] ) && is_array( $raw['format_items'] ) ) {
		foreach ( $raw['format_items'] as $item ) {
			$title = sanitize_text_field( $item['title'] ?? '' );
			$body  = sanitize_text_field( $item['body'] ?? '' );
			if ( '' !== $title ) {
				$clean['format_items'][] = array( 'title' => $title, 'body' => $body );
			}
		}
	}

	/* Credibility items (number, label) */
	$clean['credibility_items'] = array();
	if ( isset( $raw['credibility_items'] ) && is_array( $raw['credibility_items'] ) ) {
		foreach ( $raw['credibility_items'] as $item ) {
			$num   = sanitize_text_field( $item['number'] ?? '' );
			$label = sanitize_text_field( $item['label'] ?? '' );
			if ( '' !== $num && '' !== $label ) {
				$clean['credibility_items'][] = array( 'number' => $num, 'label' => $label );
			}
		}
	}

	update_option( 'ss_homepage', $clean, false );
}
