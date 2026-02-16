<?php
/**
 * Speakers — Data Storage
 *
 * CRUD operations for speakers stored as a serialized array in wp_options.
 * No CPT, no taxonomies, flat structure.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ss_get_speakers() {
	return get_option( 'ss_speakers', array() );
}

function ss_save_speakers( $speakers ) {
	update_option( 'ss_speakers', $speakers, false );
}

function ss_get_speaker( $id ) {
	foreach ( ss_get_speakers() as $speaker ) {
		if ( $speaker['id'] === $id ) {
			return $speaker;
		}
	}
	return null;
}

function ss_upsert_speaker( $data ) {
	$speakers = ss_get_speakers();
	$found    = false;
	foreach ( $speakers as $i => $speaker ) {
		if ( $speaker['id'] === $data['id'] ) {
			$speakers[ $i ] = $data;
			$found          = true;
			break;
		}
	}
	if ( ! $found ) {
		$speakers[] = $data;
	}
	ss_save_speakers( $speakers );
}

function ss_delete_speaker( $id ) {
	$speakers = array_values( array_filter( ss_get_speakers(), function ( $s ) use ( $id ) {
		return $s['id'] !== $id;
	} ) );
	ss_save_speakers( $speakers );
}

function ss_generate_speaker_id() {
	return 'spk_' . bin2hex( random_bytes( 8 ) );
}

function ss_next_speaker_order() {
	$max = 0;
	foreach ( ss_get_speakers() as $s ) {
		if ( isset( $s['order'] ) && $s['order'] > $max ) {
			$max = $s['order'];
		}
	}
	return $max + 1;
}

function ss_sanitize_speaker( $raw ) {
	return array(
		'id'           => sanitize_key( $raw['id'] ?? ss_generate_speaker_id() ),
		'name'         => sanitize_text_field( $raw['name'] ?? '' ),
		'job_title'    => sanitize_text_field( $raw['job_title'] ?? '' ),
		'company'      => sanitize_text_field( $raw['company'] ?? '' ),
		'linkedin_url' => esc_url_raw( $raw['linkedin_url'] ?? '' ),
		'website_url'  => esc_url_raw( $raw['website_url'] ?? '' ),
		'image_url'    => esc_url_raw( $raw['image_url'] ?? '' ),
		'topic'        => sanitize_text_field( $raw['topic'] ?? '' ),
		'description'  => wp_kses_post( $raw['description'] ?? '' ),
		'featured'     => ! empty( $raw['featured'] ),
		'status'       => in_array( $raw['status'] ?? '', array( 'published', 'unconfirmed' ), true )
			? $raw['status'] : 'unconfirmed',
		'order'        => absint( $raw['order'] ?? 0 ),
	);
}

function ss_get_published_speakers( $sort = 'manual' ) {
	$published = array_filter( ss_get_speakers(), function ( $s ) {
		return 'published' === ( $s['status'] ?? '' );
	} );
	if ( 'alphabetical' === $sort ) {
		usort( $published, function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		} );
	} else {
		usort( $published, function ( $a, $b ) {
			return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
		} );
	}
	return array_values( $published );
}

function ss_speaker_initials( $name ) {
	$parts    = explode( ' ', trim( $name ) );
	$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) );
	if ( count( $parts ) > 1 ) {
		$initials .= mb_strtoupper( mb_substr( end( $parts ), 0, 1 ) );
	}
	return $initials;
}
