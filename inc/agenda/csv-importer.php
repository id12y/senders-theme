<?php
/**
 * Agenda Module — Import / Export
 *
 * Supports JSON (canonical) and CSV (convenience) formats.
 * Import flow: validate → preview → confirm → backup → write.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
   Export — JSON
   ------------------------------------------------------------------------- */

/**
 * Export the current agenda as canonical JSON.
 *
 * @return string JSON string.
 */
function ss_agenda_export_json() {
	$agenda = ss_get_agenda();
	return wp_json_encode( $agenda, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/* -------------------------------------------------------------------------
   Export — CSV
   ------------------------------------------------------------------------- */

/**
 * Get CSV column headers.
 *
 * @return array
 */
function ss_agenda_csv_headers() {
	return array(
		'session_id',
		'date',
		'start_time',
		'end_time',
		'room',
		'track',
		'type',
		'title',
		'subtitle',
		'description',
		'participant_keys',
		'participant_roles',
		'status',
		'cta_label',
		'cta_url',
		'sort_order',
	);
}

/**
 * Export the current agenda as CSV string.
 *
 * @return string CSV content.
 */
function ss_agenda_export_csv() {
	$agenda  = ss_get_agenda();
	$headers = ss_agenda_csv_headers();

	$output = fopen( 'php://temp', 'r+' );
	fputcsv( $output, $headers );

	foreach ( $agenda['sessions'] as $s ) {
		$keys  = array();
		$roles = array();
		foreach ( $s['participants'] as $p ) {
			$keys[]  = $p['speaker_id'];
			$roles[] = $p['role'];
		}

		$row = array(
			$s['id'],
			$s['date'],
			$s['start_time'],
			$s['end_time'],
			$s['room'],
			$s['track'],
			$s['type'],
			$s['title'],
			$s['subtitle'],
			$s['description'],
			implode( '|', $keys ),
			implode( '|', $roles ),
			$s['status'],
			$s['cta_label'],
			$s['cta_url'],
			$s['sort_order'],
		);
		fputcsv( $output, $row );
	}

	rewind( $output );
	$csv = stream_get_contents( $output );
	fclose( $output );

	return $csv;
}

/**
 * Generate a blank CSV template with headers and one sample row.
 *
 * @return string CSV content.
 */
function ss_agenda_blank_csv_template() {
	$headers = ss_agenda_csv_headers();

	$output = fopen( 'php://temp', 'r+' );
	fputcsv( $output, $headers );

	/* Sample row */
	$sample = array(
		'',                         // session_id (leave blank for new)
		'2025-06-15',               // date
		'09:00',                    // start_time
		'09:45',                    // end_time
		'Main Hall',                // room
		'',                         // track (optional)
		'keynote',                  // type
		'Opening Keynote',          // title
		'Welcome to the event',     // subtitle
		'The opening session.',     // description
		'spk_abc123|spk_def456',    // participant_keys (pipe-separated)
		'speaker|moderator',        // participant_roles (pipe-separated, aligned)
		'published',                // status
		'Register Now',             // cta_label
		'https://example.com',      // cta_url
		'1',                        // sort_order
	);
	fputcsv( $output, $sample );

	rewind( $output );
	$csv = stream_get_contents( $output );
	fclose( $output );

	return $csv;
}

/* -------------------------------------------------------------------------
   Import — JSON
   ------------------------------------------------------------------------- */

/**
 * Parse and validate a JSON import.
 *
 * @param string $json_string Raw JSON content.
 * @return array { 'sessions' => array, 'errors' => array, 'warnings' => array }
 */
function ss_agenda_parse_json_import( $json_string ) {
	$result = array(
		'sessions' => array(),
		'errors'   => array(),
		'warnings' => array(),
	);

	$data = json_decode( $json_string, true );
	if ( null === $data || ! is_array( $data ) ) {
		$result['errors'][] = __( 'Invalid JSON format.', 'sender-symposium' );
		return $result;
	}

	if ( empty( $data['sessions'] ) || ! is_array( $data['sessions'] ) ) {
		$result['errors'][] = __( 'No sessions found in JSON data.', 'sender-symposium' );
		return $result;
	}

	foreach ( $data['sessions'] as $i => $raw ) {
		$row_num  = $i + 1;
		$session  = ss_sanitize_session( $raw );
		$errors   = ss_validate_session( $session, $result['sessions'] );
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $err ) {
				/* translators: %1$d: row number, %2$s: error message */
				$result['errors'][] = sprintf( __( 'Session %1$d ("%2$s"): %3$s', 'sender-symposium' ), $row_num, $session['title'], $err );
			}
		} else {
			$result['sessions'][] = $session;
		}
	}

	/* Carry forward timezone if present */
	if ( ! empty( $data['timezone'] ) ) {
		$result['timezone'] = sanitize_text_field( $data['timezone'] );
	}

	return $result;
}

/* -------------------------------------------------------------------------
   Import — CSV
   ------------------------------------------------------------------------- */

/**
 * Safe cell accessor for CSV row.
 *
 * @param array  $row     CSV row array.
 * @param array  $map     Header-to-index map.
 * @param string $field   Field name.
 * @return string
 */
function ss_agenda_csv_cell( $row, $map, $field ) {
	if ( ! isset( $map[ $field ] ) ) {
		return '';
	}
	$idx = $map[ $field ];
	return isset( $row[ $idx ] ) ? trim( $row[ $idx ] ) : '';
}

/**
 * Parse and validate a CSV import.
 *
 * @param string $file_path Path to uploaded CSV file.
 * @return array { 'sessions' => array, 'errors' => array, 'warnings' => array }
 */
function ss_agenda_parse_csv_import( $file_path ) {
	$result = array(
		'sessions' => array(),
		'errors'   => array(),
		'warnings' => array(),
	);

	$handle = fopen( $file_path, 'r' );
	if ( ! $handle ) {
		$result['errors'][] = __( 'Could not open CSV file.', 'sender-symposium' );
		return $result;
	}

	/* Read and map headers */
	$headers = fgetcsv( $handle );
	if ( ! $headers ) {
		fclose( $handle );
		$result['errors'][] = __( 'CSV file is empty or has no headers.', 'sender-symposium' );
		return $result;
	}

	/* Strip BOM */
	$headers[0] = preg_replace( '/^\x{FEFF}/u', '', $headers[0] );

	/* Build header map (lowercase) */
	$map = array();
	foreach ( $headers as $idx => $h ) {
		$key = strtolower( trim( $h ) );
		$map[ $key ] = $idx;
	}

	/* Required columns check */
	$required_cols = array( 'title', 'date', 'start_time', 'end_time' );
	foreach ( $required_cols as $col ) {
		if ( ! isset( $map[ $col ] ) ) {
			$result['errors'][] = sprintf( __( 'Required column "%s" is missing from CSV headers.', 'sender-symposium' ), $col );
		}
	}
	if ( ! empty( $result['errors'] ) ) {
		fclose( $handle );
		return $result;
	}

	$row_num = 1;
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;

		/* Skip empty rows */
		if ( empty( array_filter( $row, 'strlen' ) ) ) {
			continue;
		}

		$cell = function ( $field ) use ( $row, $map ) {
			return ss_agenda_csv_cell( $row, $map, $field );
		};

		/* Parse participants */
		$participants = array();
		$keys_str     = $cell( 'participant_keys' );
		$roles_str    = $cell( 'participant_roles' );
		if ( ! empty( $keys_str ) ) {
			$keys  = array_map( 'trim', explode( '|', $keys_str ) );
			$roles = ! empty( $roles_str ) ? array_map( 'trim', explode( '|', $roles_str ) ) : array();
			foreach ( $keys as $k => $speaker_id ) {
				if ( empty( $speaker_id ) ) {
					continue;
				}
				$participants[] = array(
					'speaker_id' => $speaker_id,
					'role'       => isset( $roles[ $k ] ) && ! empty( $roles[ $k ] ) ? $roles[ $k ] : 'speaker',
					'sort_order' => $k,
				);
			}
		}

		$raw = array(
			'id'           => $cell( 'session_id' ),
			'date'         => $cell( 'date' ),
			'start_time'   => $cell( 'start_time' ),
			'end_time'     => $cell( 'end_time' ),
			'room'         => $cell( 'room' ),
			'track'        => $cell( 'track' ),
			'type'         => $cell( 'type' ) ?: 'talk',
			'title'        => $cell( 'title' ),
			'subtitle'     => $cell( 'subtitle' ),
			'description'  => $cell( 'description' ),
			'participants' => $participants,
			'status'       => $cell( 'status' ) ?: 'published',
			'cta_label'    => $cell( 'cta_label' ),
			'cta_url'      => $cell( 'cta_url' ),
			'sort_order'   => $cell( 'sort_order' ) ?: $row_num,
			'notes'        => '',
		);

		$session = ss_sanitize_session( $raw );
		$errors  = ss_validate_session( $session, $result['sessions'] );

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $err ) {
				/* translators: %1$d: row number, %2$s: error message */
				$result['errors'][] = sprintf( __( 'Row %1$d ("%2$s"): %3$s', 'sender-symposium' ), $row_num, $session['title'], $err );
			}
		} else {
			$result['sessions'][] = $session;
		}
	}

	fclose( $handle );

	if ( empty( $result['sessions'] ) && empty( $result['errors'] ) ) {
		$result['errors'][] = __( 'No valid sessions found in CSV.', 'sender-symposium' );
	}

	return $result;
}

/* -------------------------------------------------------------------------
   Apply Import
   ------------------------------------------------------------------------- */

/**
 * Apply a validated import, replacing the current agenda.
 *
 * @param array  $sessions  Array of sanitized sessions.
 * @param string $timezone  Optional timezone override.
 */
function ss_agenda_apply_import( $sessions, $timezone = '' ) {
	ss_agenda_backup();

	$agenda = ss_agenda_defaults();
	$agenda['sessions'] = $sessions;
	if ( ! empty( $timezone ) ) {
		$agenda['timezone'] = sanitize_text_field( $timezone );
	}

	ss_save_agenda( $agenda );
}
