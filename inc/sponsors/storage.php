<?php
/**
 * Sponsors — Data Storage
 *
 * CRUD operations for sponsors stored as a serialized array in wp_options.
 * No CPT, no taxonomies, flat structure.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Valid sponsor levels (order = display priority) ─── */

function ss_sponsor_levels() {
	return array(
		'platinum' => __( 'Platinum Headline Partner', 'sender-symposium' ),
		'gold'     => __( 'Gold', 'sender-symposium' ),
		'silver'   => __( 'Silver', 'sender-symposium' ),
		'bronze'   => __( 'Bronze', 'sender-symposium' ),
	);
}

function ss_sponsor_level_label( $key ) {
	$levels = ss_sponsor_levels();
	return $levels[ $key ] ?? $key;
}

/* ─── CRUD ─── */

function ss_get_sponsors() {
	return get_option( 'ss_sponsors', array() );
}

function ss_save_sponsors( $sponsors ) {
	update_option( 'ss_sponsors', $sponsors, false );
}

function ss_get_sponsor( $id ) {
	foreach ( ss_get_sponsors() as $sponsor ) {
		if ( $sponsor['id'] === $id ) {
			return $sponsor;
		}
	}
	return null;
}

function ss_upsert_sponsor( $data ) {
	$sponsors = ss_get_sponsors();
	$found    = false;
	foreach ( $sponsors as $i => $sponsor ) {
		if ( $sponsor['id'] === $data['id'] ) {
			$sponsors[ $i ] = $data;
			$found          = true;
			break;
		}
	}
	if ( ! $found ) {
		$sponsors[] = $data;
	}
	ss_save_sponsors( $sponsors );
}

function ss_delete_sponsor( $id ) {
	$sponsors = array_values( array_filter( ss_get_sponsors(), function ( $s ) use ( $id ) {
		return $s['id'] !== $id;
	} ) );
	ss_save_sponsors( $sponsors );
}

function ss_generate_sponsor_id() {
	return 'spr_' . bin2hex( random_bytes( 8 ) );
}

function ss_next_sponsor_order() {
	$max = 0;
	foreach ( ss_get_sponsors() as $s ) {
		if ( isset( $s['order'] ) && $s['order'] > $max ) {
			$max = $s['order'];
		}
	}
	return $max + 1;
}

/* ─── Sanitize ─── */

function ss_sanitize_sponsor( $raw ) {
	$levels = array_keys( ss_sponsor_levels() );
	return array(
		'id'                      => sanitize_key( $raw['id'] ?? ss_generate_sponsor_id() ),
		'sponsor_name'            => sanitize_text_field( $raw['sponsor_name'] ?? '' ),
		'sponsor_level'           => in_array( $raw['sponsor_level'] ?? '', $levels, true )
			? $raw['sponsor_level'] : 'bronze',
		'description'             => wp_kses_post( $raw['description'] ?? '' ),
		'website_link'            => esc_url_raw( $raw['website_link'] ?? '' ),
		'image_url'               => esc_url_raw( $raw['image_url'] ?? '' ),
		'image_dark_url'          => esc_url_raw( $raw['image_dark_url'] ?? '' ),
		'logo_attachment_id'      => absint( $raw['logo_attachment_id'] ?? 0 ),
		'logo_dark_attachment_id' => absint( $raw['logo_dark_attachment_id'] ?? 0 ),
		'featured'                => ! empty( $raw['featured'] ),
		'status'                  => in_array( $raw['status'] ?? '', array( 'confirmed', 'unconfirmed' ), true )
			? $raw['status'] : 'unconfirmed',
		'order'                   => absint( $raw['order'] ?? 0 ),
		'dark_logo_reco_dismissed' => ! empty( $raw['dark_logo_reco_dismissed'] ),
	);
}

/* ─── Query helpers ─── */

function ss_get_confirmed_sponsors( $sort = 'manual' ) {
	$confirmed = array_filter( ss_get_sponsors(), function ( $s ) {
		return 'confirmed' === ( $s['status'] ?? '' );
	} );

	/* Sort by level priority first, then by order within level */
	$level_priority = array( 'platinum' => 0, 'gold' => 1, 'silver' => 2, 'bronze' => 3 );

	if ( 'alphabetical' === $sort ) {
		usort( $confirmed, function ( $a, $b ) use ( $level_priority ) {
			$la = $level_priority[ $a['sponsor_level'] ] ?? 9;
			$lb = $level_priority[ $b['sponsor_level'] ] ?? 9;
			if ( $la !== $lb ) {
				return $la - $lb;
			}
			return strcasecmp( $a['sponsor_name'], $b['sponsor_name'] );
		} );
	} else {
		usort( $confirmed, function ( $a, $b ) use ( $level_priority ) {
			$la = $level_priority[ $a['sponsor_level'] ] ?? 9;
			$lb = $level_priority[ $b['sponsor_level'] ] ?? 9;
			if ( $la !== $lb ) {
				return $la - $lb;
			}
			return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
		} );
	}

	return array_values( $confirmed );
}

/**
 * Get sponsor logo URL (light mode), preferring attachment over raw URL.
 */
function ss_sponsor_logo_url( $sponsor ) {
	if ( ! empty( $sponsor['logo_attachment_id'] ) ) {
		$url = wp_get_attachment_image_url( $sponsor['logo_attachment_id'], 'medium' );
		if ( $url ) {
			return $url;
		}
	}
	return $sponsor['image_url'] ?? '';
}

/**
 * Get sponsor logo URL (dark mode), preferring attachment over raw URL.
 */
function ss_sponsor_dark_logo_url( $sponsor ) {
	if ( ! empty( $sponsor['logo_dark_attachment_id'] ) ) {
		$url = wp_get_attachment_image_url( $sponsor['logo_dark_attachment_id'], 'medium' );
		if ( $url ) {
			return $url;
		}
	}
	return $sponsor['image_dark_url'] ?? '';
}

/**
 * Check if dark logo is recommended for a sponsor.
 * Conservative heuristic — only flags, never changes data.
 */
function ss_sponsor_dark_logo_recommended( $sponsor ) {
	/* Already has dark logo — no recommendation needed */
	if ( ss_sponsor_dark_logo_url( $sponsor ) ) {
		return false;
	}
	/* Admin dismissed recommendation */
	if ( ! empty( $sponsor['dark_logo_reco_dismissed'] ) ) {
		return false;
	}
	/* Only flag prominent sponsors */
	if ( ! in_array( $sponsor['sponsor_level'], array( 'platinum', 'gold', 'silver' ), true ) ) {
		return false;
	}
	/* Check file extension heuristic */
	$logo = ss_sponsor_logo_url( $sponsor );
	if ( empty( $logo ) ) {
		return false;
	}
	$ext = strtolower( pathinfo( wp_parse_url( $logo, PHP_URL_PATH ) ?: '', PATHINFO_EXTENSION ) );
	if ( in_array( $ext, array( 'png', 'svg' ), true ) ) {
		return true;
	}
	/* Check filename hints */
	$basename = strtolower( basename( wp_parse_url( $logo, PHP_URL_PATH ) ?: '' ) );
	$hints    = array( 'dark', 'black', 'navy', 'blue', 'outline' );
	foreach ( $hints as $hint ) {
		if ( false !== strpos( $basename, $hint ) ) {
			return true;
		}
	}
	return false;
}

/* ─── Prepopulate defaults ─── */

function ss_sponsors_maybe_prepopulate() {
	if ( false !== get_option( 'ss_sponsors' ) ) {
		return;
	}
	$upload_base = content_url( '/uploads/2026/01/' );
	$defaults = array(
		array( 'sponsor_name' => 'EasyDMARC',        'sponsor_level' => 'gold',   'description' => 'Simplify, Manage, and Automate Your DMARC Journey',                     'image_url' => $upload_base . 'easydmarc.png',                                       'website_link' => 'https://easydmarc.com/' ),
		array( 'sponsor_name' => 'Emailexpert',      'sponsor_level' => 'gold',   'description' => 'The Sender Symposium Event Organisers',                                  'image_url' => $upload_base . 'emailexpert-bannerlogo-1-5.png',                       'website_link' => 'https://emailexpert.com' ),
		array( 'sponsor_name' => 'Halon',            'sponsor_level' => 'silver', 'description' => 'The leading email infrastructure for service providers',                  'image_url' => $upload_base . 'halon.png',                                           'website_link' => 'https://halon.io' ),
		array( 'sponsor_name' => 'Postmastery',      'sponsor_level' => 'silver', 'description' => 'Empower email senders to achieve more',                                  'image_url' => $upload_base . 'postmastery.png',                                     'website_link' => 'https://postmastery.com/' ),
		array( 'sponsor_name' => 'GreenArrow',       'sponsor_level' => 'silver', 'description' => 'Smarter, more powerful sending & delivery software.',                     'image_url' => $upload_base . 'greenarrow-logo.blue_.4400-transparent-scaled-1.png', 'website_link' => 'https://greenarrowemail.com' ),
		array( 'sponsor_name' => 'Omnivery',         'sponsor_level' => 'silver', 'description' => 'SMTP/API sending platform',                                              'image_url' => $upload_base . 'omvr_rgb_positive_color.png',                         'website_link' => 'https://omnivery.com' ),
		array( 'sponsor_name' => 'KumoMTA',          'sponsor_level' => 'silver', 'description' => 'Enterprise Open-Source On-Prem/Private Cloud MTA',                       'image_url' => $upload_base . 'KUMO_Logo_NEW_Color.png',                             'website_link' => 'https://kumomta.com' ),
		array( 'sponsor_name' => 'Emailexpert',      'sponsor_level' => 'silver', 'description' => 'The Sender Symposium Event Organisers',                                  'image_url' => $upload_base . 'emailexpert-bannerlogo-1-5.png',                       'website_link' => 'https://emailexpert.com' ),
		array( 'sponsor_name' => 'Aurora SendCloud',  'sponsor_level' => 'bronze', 'description' => '',                                                                       'image_url' => $upload_base . 'sendcloud.png',                                       'website_link' => 'https://www.aurorasendcloud.com/' ),
		array( 'sponsor_name' => 'Infobip',          'sponsor_level' => 'bronze', 'description' => '',                                                                       'image_url' => $upload_base . 'Infobip_logo_horizontal_orange.png',                  'website_link' => 'https://infobip.com' ),
		array( 'sponsor_name' => 'ZeroBounce',       'sponsor_level' => 'bronze', 'description' => '',                                                                       'image_url' => $upload_base . 'zerobounce.png',                                      'website_link' => 'https://zerobounce.net' ),
		array( 'sponsor_name' => 'Bouncer',          'sponsor_level' => 'bronze', 'description' => '',                                                                       'image_url' => $upload_base . 'bouncer-1.png',                                       'website_link' => 'https://usebouncer.com' ),
		array( 'sponsor_name' => 'Emailexpert',      'sponsor_level' => 'bronze', 'description' => '',                                                                       'image_url' => $upload_base . 'emailexpert-bannerlogo-1-5.png',                       'website_link' => 'https://emailexpert.com' ),
	);

	$sponsors = array();
	$order    = 1;
	foreach ( $defaults as $raw ) {
		$raw['id']     = ss_generate_sponsor_id();
		$raw['status'] = 'confirmed';
		$raw['order']  = $order++;
		$sponsors[]    = ss_sanitize_sponsor( $raw );
	}
	ss_save_sponsors( $sponsors );
}
