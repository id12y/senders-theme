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
		/* ── Display Mode ──
		 * 'full'    → render the full event homepage blocks below.
		 * 'holding' → render the post-event holding page (parts/holding-page.php).
		 * Defaults to 'holding'; flip to 'full' in admin to restore the event homepage.
		 */
		'display_mode' => 'holding',

		/* ── Holding Page (post-event) ── */
		'holding_heading'      => 'Sender Symposium',
		'holding_body'         => "In April 2026, Sender Symposium brought CRM, lifecycle and sender leaders into one focused room in Barcelona — no theatre, no expo floor, just the people who take email seriously, talking honestly about where it's heading.\n\nIt was exactly the room we hoped it would be.\n\nWhat comes next for Sender Symposium, we'll share here when the time is right. In the meantime, the conversation doesn't stop.\n\nThis November, emailexpert Forum brings the whole of email together in London — CRM and marketing alongside deliverability, infrastructure and anti-abuse. If Sender Symposium was your kind of room, Forum is where you'll find that crowd next.",
		'holding_cta_text'     => 'Discover Forum →',
		'holding_cta_url'      => 'https://forum.emailexpert.org/',
		'holding_event_line'   => 'emailexpert Forum · London · 16–17 November 2026',
		'holding_form_enabled' => true,
		'holding_form_prompt'  => "Want to know when Sender Symposium returns? Leave your email and we'll be in touch.",
		'holding_form_button'  => 'Notify me',
		'holding_form_success' => "Thank you — we'll be in touch when there's news to share.",
		'holding_form_error'   => 'Please enter a valid email address.',

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

	/* Display mode */
	$mode = $raw['display_mode'] ?? 'holding';
	$clean['display_mode'] = in_array( $mode, array( 'full', 'holding' ), true ) ? $mode : 'holding';

	/* Booleans (checkboxes) */
	$toggles = array(
		'event_strip_enabled', 'audience_enabled', 'values_enabled',
		'format_enabled', 'credibility_enabled', 'cta_enabled',
		'holding_form_enabled',
	);
	foreach ( $toggles as $key ) {
		$clean[ $key ] = ! empty( $raw[ $key ] );
	}

	/* Holding page — scalar text fields */
	$holding_text = array(
		'holding_heading', 'holding_cta_text', 'holding_event_line',
		'holding_form_prompt', 'holding_form_button',
		'holding_form_success', 'holding_form_error',
	);
	foreach ( $holding_text as $key ) {
		$clean[ $key ] = sanitize_text_field( $raw[ $key ] ?? '' );
	}
	$clean['holding_body']    = sanitize_textarea_field( $raw['holding_body'] ?? '' );
	$clean['holding_cta_url'] = esc_url_raw( $raw['holding_cta_url'] ?? '' );

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
