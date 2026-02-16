<?php
/**
 * Sponsors — CSV Importer
 *
 * Parses CSV files and imports sponsors. Handles messy data gracefully.
 * Never fatals, never crashes. Returns structured feedback.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import sponsors from a CSV file.
 *
 * @param string $file_path  Path to the uploaded CSV file.
 * @param bool   $always_new If true, always create new entries (no dedup).
 * @return array {imported, updated, skipped, warnings[]}
 */
function ss_import_sponsors_csv( $file_path, $always_new = false ) {
	$result = array( 'imported' => 0, 'updated' => 0, 'skipped' => 0, 'warnings' => array() );

	$handle = fopen( $file_path, 'r' );
	if ( ! $handle ) {
		$result['warnings'][] = __( 'Could not open CSV file.', 'sender-symposium' );
		return $result;
	}

	$headers = fgetcsv( $handle );
	if ( ! $headers ) {
		fclose( $handle );
		$result['warnings'][] = __( 'CSV file is empty or has no headers.', 'sender-symposium' );
		return $result;
	}

	/* ─── Column alias map (case-insensitive, flexible) ─── */
	$aliases = array(
		'sponsor_name'  => array( 'sponsor_name', 'sponsor name', 'name', 'company', 'company name', 'partner', 'partner name' ),
		'sponsor_level' => array( 'sponsor_level', 'sponsor level', 'level', 'tier', 'package', 'sponsorship level' ),
		'description'   => array( 'description', 'desc', 'bio', 'about', 'tagline' ),
		'image_url'     => array( 'image_url', 'image url', 'image', 'logo', 'logo url', 'logo_url' ),
		'image_dark_url' => array( 'image_dark_url', 'dark_logo_url', 'logo_dark_url', 'dark logo', 'dark logo url', 'dark_logo', 'logo_dark' ),
		'website_link'  => array( 'website_link', 'website link', 'website', 'website_url', 'url', 'web', 'site', 'homepage', 'link' ),
		'position'      => array( 'position', 'order', 'sort', 'sort order', 'display order' ),
		'status'        => array( 'status', 'state', 'confirmed' ),
	);

	$map = array();
	foreach ( $headers as $i => $h ) {
		$norm = strtolower( trim( preg_replace( '/^\x{FEFF}/u', '', $h ) ) );
		$norm = str_replace( array( '-', ' ' ), '_', $norm );
		foreach ( $aliases as $field => $names ) {
			if ( in_array( $norm, $names, true ) || in_array( str_replace( '_', ' ', $norm ), $names, true ) ) {
				$map[ $field ] = $i;
				break;
			}
		}
	}

	if ( ! isset( $map['sponsor_name'] ) ) {
		fclose( $handle );
		$result['warnings'][] = __( 'No "sponsor_name" or "Name" column found in CSV headers.', 'sender-symposium' );
		return $result;
	}

	/* ─── Level mapping (friendly → internal) ─── */
	$level_map = array(
		'platinum'                  => 'platinum',
		'platinum headline partner' => 'platinum',
		'headline'                  => 'platinum',
		'headline partner'          => 'platinum',
		'gold'                      => 'gold',
		'silver'                    => 'silver',
		'bronze'                    => 'bronze',
	);

	/* ─── Build existing lookup for dedup ─── */
	$existing      = ss_get_sponsors();
	$existing_map  = array();
	if ( ! $always_new ) {
		foreach ( $existing as $idx => $ex ) {
			$key = strtolower( trim( $ex['sponsor_name'] ) ) . '|' . strtolower( trim( $ex['website_link'] ?? '' ) ) . '|' . strtolower( trim( $ex['sponsor_level'] ?? '' ) );
			$existing_map[ $key ] = $idx;
		}
	}

	$next_order = ss_next_sponsor_order();
	$row_num    = 1;

	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;

		/* Skip empty rows */
		if ( empty( array_filter( $row, function ( $c ) { return '' !== trim( $c ); } ) ) ) {
			continue;
		}

		$name = isset( $row[ $map['sponsor_name'] ] ) ? trim( $row[ $map['sponsor_name'] ] ) : '';
		if ( '' === $name ) {
			$result['skipped']++;
			$result['warnings'][] = sprintf( __( 'Row %d: Missing sponsor name, skipped.', 'sender-symposium' ), $row_num );
			continue;
		}

		/* Parse level */
		$raw_level = strtolower( trim( ss_sponsor_csv_cell( $row, $map, 'sponsor_level' ) ) );
		$level     = $level_map[ $raw_level ] ?? '';
		if ( '' === $level ) {
			if ( '' !== $raw_level ) {
				$result['warnings'][] = sprintf(
					__( 'Row %1$d (%2$s): Unknown level "%3$s", defaulting to Bronze + unconfirmed.', 'sender-symposium' ),
					$row_num, $name, $raw_level
				);
			}
			$level = 'bronze';
		}

		/* Parse status */
		$raw_status = strtolower( trim( ss_sponsor_csv_cell( $row, $map, 'status' ) ) );
		$status     = 'unconfirmed';
		if ( in_array( $raw_status, array( 'confirmed', 'yes', '1', 'true', 'published' ), true ) ) {
			$status = 'confirmed';
		}

		/* Parse position */
		$raw_pos = ss_sponsor_csv_cell( $row, $map, 'position' );
		$order   = '' !== $raw_pos ? absint( $raw_pos ) : $next_order++;

		/* Build data */
		$data = array(
			'sponsor_name'  => $name,
			'sponsor_level' => $level,
			'description'   => ss_sponsor_csv_cell( $row, $map, 'description' ),
			'image_url'     => ss_sponsor_csv_cell( $row, $map, 'image_url' ),
			'image_dark_url' => ss_sponsor_csv_cell( $row, $map, 'image_dark_url' ),
			'website_link'  => ss_sponsor_csv_cell( $row, $map, 'website_link' ),
			'status'        => $status,
			'order'         => $order,
		);

		/* Validate URLs */
		foreach ( array( 'image_url', 'image_dark_url', 'website_link' ) as $url_field ) {
			if ( '' !== $data[ $url_field ] && ! filter_var( $data[ $url_field ], FILTER_VALIDATE_URL ) ) {
				$result['warnings'][] = sprintf(
					__( 'Row %1$d (%2$s): Invalid %3$s, ignored.', 'sender-symposium' ),
					$row_num, $name, str_replace( '_', ' ', $url_field )
				);
				$data[ $url_field ] = '';
			}
		}

		/* Dedup check: match by name + website + level */
		$dedup_key = strtolower( trim( $name ) ) . '|' . strtolower( trim( $data['website_link'] ) ) . '|' . strtolower( trim( $level ) );

		if ( ! $always_new && isset( $existing_map[ $dedup_key ] ) ) {
			$idx              = $existing_map[ $dedup_key ];
			$data['id']       = $existing[ $idx ]['id'];
			$data['order']    = $existing[ $idx ]['order'];
			$data['logo_attachment_id']      = $existing[ $idx ]['logo_attachment_id'] ?? 0;
			$data['logo_dark_attachment_id'] = $existing[ $idx ]['logo_dark_attachment_id'] ?? 0;
			$existing[ $idx ] = ss_sanitize_sponsor( $data );
			$result['updated']++;
		} else {
			$data['id'] = ss_generate_sponsor_id();
			$existing[] = ss_sanitize_sponsor( $data );
			$result['imported']++;
		}
	}

	fclose( $handle );
	ss_save_sponsors( $existing );

	return $result;
}

/**
 * Safe CSV cell accessor.
 */
function ss_sponsor_csv_cell( $row, $map, $field ) {
	return isset( $map[ $field ], $row[ $map[ $field ] ] ) ? trim( $row[ $map[ $field ] ] ) : '';
}
