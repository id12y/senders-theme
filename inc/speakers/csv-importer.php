<?php
/**
 * Speakers — CSV Importer
 *
 * Parses CSV files and imports speakers. Handles messy data gracefully.
 * Never fatals, never crashes. Returns structured feedback.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import speakers from a CSV file.
 *
 * @param string $file_path Path to the uploaded CSV file.
 * @return array {imported, skipped, warnings[]}
 */
function ss_import_speakers_csv( $file_path ) {
	$result = array( 'imported' => 0, 'skipped' => 0, 'warnings' => array() );

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

	$aliases = array(
		'name'         => array( 'name', 'speaker name', 'speaker', 'full name' ),
		'job_title'    => array( 'job title', 'job_title', 'title', 'role', 'position' ),
		'company'      => array( 'company', 'organization', 'org', 'employer' ),
		'linkedin_url' => array( 'linkedin', 'linkedin url', 'linkedin_url', 'linkedin link' ),
		'website_url'  => array( 'website', 'website url', 'website_url', 'url', 'web', 'site', 'homepage' ),
		'image_url'    => array( 'image', 'image url', 'image_url', 'photo', 'photo url', 'avatar' ),
	);

	$map = array();
	foreach ( $headers as $i => $h ) {
		$norm = strtolower( trim( preg_replace( '/^\x{FEFF}/u', '', $h ) ) );
		foreach ( $aliases as $field => $names ) {
			if ( in_array( $norm, $names, true ) ) {
				$map[ $field ] = $i;
				break;
			}
		}
	}

	if ( ! isset( $map['name'] ) ) {
		fclose( $handle );
		$result['warnings'][] = __( 'No "Name" column found in CSV headers.', 'sender-symposium' );
		return $result;
	}

	$next_order  = ss_next_speaker_order();
	$new         = array();
	$row_num     = 1;

	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;

		if ( empty( array_filter( $row, function ( $c ) { return '' !== trim( $c ); } ) ) ) {
			continue;
		}

		$name = isset( $row[ $map['name'] ] ) ? trim( $row[ $map['name'] ] ) : '';
		if ( '' === $name ) {
			$result['skipped']++;
			$result['warnings'][] = sprintf( __( 'Row %d: Missing name, skipped.', 'sender-symposium' ), $row_num );
			continue;
		}

		$data = array(
			'id'           => ss_generate_speaker_id(),
			'name'         => $name,
			'job_title'    => ss_csv_cell( $row, $map, 'job_title' ),
			'company'      => ss_csv_cell( $row, $map, 'company' ),
			'linkedin_url' => ss_csv_cell( $row, $map, 'linkedin_url' ),
			'website_url'  => ss_csv_cell( $row, $map, 'website_url' ),
			'image_url'    => ss_csv_cell( $row, $map, 'image_url' ),
			'topic'        => '',
			'description'  => '',
			'featured'     => false,
			'status'       => 'unconfirmed',
			'order'        => $next_order++,
		);

		if ( '' !== $data['linkedin_url'] && ! filter_var( $data['linkedin_url'], FILTER_VALIDATE_URL ) ) {
			$result['warnings'][] = sprintf( __( 'Row %1$d (%2$s): LinkedIn URL may be invalid.', 'sender-symposium' ), $row_num, $name );
		}
		if ( '' !== $data['website_url'] && ! filter_var( $data['website_url'], FILTER_VALIDATE_URL ) ) {
			$result['warnings'][] = sprintf( __( 'Row %1$d (%2$s): Website URL may be invalid.', 'sender-symposium' ), $row_num, $name );
		}
		if ( '' !== $data['image_url'] && ! filter_var( $data['image_url'], FILTER_VALIDATE_URL ) ) {
			$result['warnings'][] = sprintf( __( 'Row %1$d (%2$s): Image URL may be invalid.', 'sender-symposium' ), $row_num, $name );
		}

		$new[] = ss_sanitize_speaker( $data );
		$result['imported']++;
	}

	fclose( $handle );

	if ( ! empty( $new ) ) {
		ss_save_speakers( array_merge( ss_get_speakers(), $new ) );
	}

	return $result;
}

function ss_csv_cell( $row, $map, $field ) {
	return isset( $map[ $field ], $row[ $map[ $field ] ] ) ? trim( $row[ $map[ $field ] ] ) : '';
}
