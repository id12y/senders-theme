<?php
/**
 * Agenda Module — Data Storage & CRUD
 *
 * Stores the full agenda (days, sessions, participants) as a single
 * serialised array in wp_options under the key 'ss_agenda'.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Current schema version — bump when structure changes. */
define( 'SS_AGENDA_SCHEMA_VERSION', 1 );

/* -------------------------------------------------------------------------
   Option key & defaults
   ------------------------------------------------------------------------- */

/**
 * Return an empty agenda scaffold with correct schema version.
 */
function ss_agenda_defaults() {
	return array(
		'schema_version' => SS_AGENDA_SCHEMA_VERSION,
		'updated_at'     => '',
		'timezone'       => 'Europe/Madrid',
		'days'           => array(),
		'sessions'       => array(),
	);
}

/* -------------------------------------------------------------------------
   Read / Write
   ------------------------------------------------------------------------- */

/**
 * Get the full agenda data.
 *
 * @return array
 */
function ss_get_agenda() {
	$data = get_option( 'ss_agenda', array() );
	return wp_parse_args( $data, ss_agenda_defaults() );
}

/**
 * Save the full agenda data.
 *
 * @param array $data Full agenda array.
 */
function ss_save_agenda( $data ) {
	$data['updated_at'] = gmdate( 'c' );
	update_option( 'ss_agenda', $data, false );
}

/* -------------------------------------------------------------------------
   Session helpers
   ------------------------------------------------------------------------- */

/**
 * Generate a stable, unique session ID.
 *
 * @return string e.g. 'ses_a1b2c3d4e5f6g7h8'
 */
function ss_generate_session_id() {
	return 'ses_' . bin2hex( random_bytes( 8 ) );
}

/**
 * Next sort order value for sessions.
 *
 * @param array $sessions
 * @return int
 */
function ss_next_session_order( $sessions ) {
	$max = 0;
	foreach ( $sessions as $s ) {
		if ( isset( $s['sort_order'] ) && $s['sort_order'] > $max ) {
			$max = $s['sort_order'];
		}
	}
	return $max + 1;
}

/**
 * Get a single session by ID.
 *
 * @param string $id Session ID.
 * @return array|null
 */
function ss_get_session( $id ) {
	$agenda = ss_get_agenda();
	foreach ( $agenda['sessions'] as $session ) {
		if ( $session['id'] === $id ) {
			return $session;
		}
	}
	return null;
}

/**
 * Get all sessions for a specific day, sorted by start_time then sort_order.
 *
 * @param string $date Date in Y-m-d format.
 * @return array
 */
function ss_get_sessions_by_day( $date ) {
	$agenda   = ss_get_agenda();
	$sessions = array();
	foreach ( $agenda['sessions'] as $s ) {
		if ( $s['date'] === $date && 'draft' !== $s['status'] ) {
			$sessions[] = $s;
		}
	}
	usort( $sessions, function ( $a, $b ) {
		$cmp = strcmp( $a['start_time'], $b['start_time'] );
		if ( 0 !== $cmp ) {
			return $cmp;
		}
		return $a['sort_order'] - $b['sort_order'];
	} );
	return $sessions;
}

/**
 * Get all published sessions for a specific speaker key.
 *
 * @param string $speaker_key Speaker ID from the speakers module.
 * @return array
 */
function ss_get_sessions_by_speaker( $speaker_key ) {
	$agenda   = ss_get_agenda();
	$sessions = array();
	foreach ( $agenda['sessions'] as $s ) {
		if ( 'draft' === $s['status'] ) {
			continue;
		}
		foreach ( $s['participants'] as $p ) {
			if ( $p['speaker_id'] === $speaker_key ) {
				$sessions[] = $s;
				break;
			}
		}
	}
	return $sessions;
}

/**
 * Get all unique days from sessions, sorted chronologically.
 *
 * @return array Array of date strings in Y-m-d format.
 */
function ss_get_agenda_days() {
	$agenda = ss_get_agenda();
	$days   = array();
	foreach ( $agenda['sessions'] as $s ) {
		if ( 'draft' !== $s['status'] && ! empty( $s['date'] ) ) {
			$days[ $s['date'] ] = true;
		}
	}
	$days = array_keys( $days );
	sort( $days );
	return $days;
}

/**
 * Get all unique rooms from sessions, sorted alphabetically.
 *
 * @return array
 */
function ss_get_agenda_rooms() {
	$agenda = ss_get_agenda();
	$rooms  = array();
	foreach ( $agenda['sessions'] as $s ) {
		if ( ! empty( $s['room'] ) ) {
			$rooms[ $s['room'] ] = true;
		}
	}
	$rooms = array_keys( $rooms );
	sort( $rooms );
	return $rooms;
}

/**
 * Get all unique tracks from sessions, sorted alphabetically.
 *
 * @return array
 */
function ss_get_agenda_tracks() {
	$agenda = ss_get_agenda();
	$tracks = array();
	foreach ( $agenda['sessions'] as $s ) {
		if ( ! empty( $s['track'] ) ) {
			$tracks[ $s['track'] ] = true;
		}
	}
	$tracks = array_keys( $tracks );
	sort( $tracks );
	return $tracks;
}

/* -------------------------------------------------------------------------
   Sanitize
   ------------------------------------------------------------------------- */

/**
 * Allowed session types.
 *
 * @return array
 */
function ss_agenda_session_types() {
	return array( 'talk', 'keynote', 'panel', 'workshop', 'break', 'lunch', 'networking', 'sponsor', 'custom' );
}

/**
 * Allowed participant roles.
 *
 * @return array
 */
function ss_agenda_participant_roles() {
	return array( 'speaker', 'panelist', 'moderator', 'host', 'chair' );
}

/**
 * Allowed session statuses.
 *
 * @return array
 */
function ss_agenda_session_statuses() {
	return array( 'published', 'draft' );
}

/**
 * Sanitize a single participant entry.
 *
 * @param array $raw Raw participant data.
 * @return array
 */
function ss_sanitize_participant( $raw ) {
	$roles = ss_agenda_participant_roles();
	$role  = isset( $raw['role'] ) ? sanitize_key( $raw['role'] ) : 'speaker';
	if ( ! in_array( $role, $roles, true ) ) {
		$role = 'speaker';
	}
	return array(
		'speaker_id'  => isset( $raw['speaker_id'] ) ? sanitize_key( $raw['speaker_id'] ) : '',
		'role'        => $role,
		'sort_order'  => isset( $raw['sort_order'] ) ? absint( $raw['sort_order'] ) : 0,
	);
}

/**
 * Sanitize a single session.
 *
 * @param array $raw  Raw session data.
 * @return array Sanitized session.
 */
function ss_sanitize_session( $raw ) {
	$types    = ss_agenda_session_types();
	$statuses = ss_agenda_session_statuses();

	$type = isset( $raw['type'] ) ? sanitize_key( $raw['type'] ) : 'talk';
	if ( ! in_array( $type, $types, true ) ) {
		$type = 'talk';
	}

	$status = isset( $raw['status'] ) ? sanitize_key( $raw['status'] ) : 'published';
	if ( ! in_array( $status, $statuses, true ) ) {
		$status = 'published';
	}

	$participants = array();
	if ( ! empty( $raw['participants'] ) && is_array( $raw['participants'] ) ) {
		foreach ( $raw['participants'] as $p ) {
			if ( is_array( $p ) && ! empty( $p['speaker_id'] ) ) {
				$participants[] = ss_sanitize_participant( $p );
			}
		}
	}

	return array(
		'id'          => ! empty( $raw['id'] ) ? sanitize_key( $raw['id'] ) : ss_generate_session_id(),
		'date'        => isset( $raw['date'] ) ? sanitize_text_field( $raw['date'] ) : '',
		'start_time'  => isset( $raw['start_time'] ) ? sanitize_text_field( $raw['start_time'] ) : '',
		'end_time'    => isset( $raw['end_time'] ) ? sanitize_text_field( $raw['end_time'] ) : '',
		'room'        => isset( $raw['room'] ) ? sanitize_text_field( $raw['room'] ) : '',
		'track'       => isset( $raw['track'] ) ? sanitize_text_field( $raw['track'] ) : '',
		'type'        => $type,
		'title'       => isset( $raw['title'] ) ? sanitize_text_field( $raw['title'] ) : '',
		'subtitle'    => isset( $raw['subtitle'] ) ? sanitize_text_field( $raw['subtitle'] ) : '',
		'description' => isset( $raw['description'] ) ? wp_kses_post( $raw['description'] ) : '',
		'participants' => $participants,
		'status'      => $status,
		'sort_order'  => isset( $raw['sort_order'] ) ? absint( $raw['sort_order'] ) : 0,
		'cta_label'   => isset( $raw['cta_label'] ) ? sanitize_text_field( $raw['cta_label'] ) : '',
		'cta_url'     => isset( $raw['cta_url'] ) ? esc_url_raw( $raw['cta_url'] ) : '',
		'notes'       => isset( $raw['notes'] ) ? sanitize_textarea_field( $raw['notes'] ) : '',
	);
}

/* -------------------------------------------------------------------------
   CRUD operations
   ------------------------------------------------------------------------- */

/**
 * Insert or update a session.
 *
 * @param array $data Session data (with or without 'id').
 * @return string The session ID.
 */
function ss_upsert_session( $data ) {
	$agenda  = ss_get_agenda();
	$session = ss_sanitize_session( $data );

	$found = false;
	foreach ( $agenda['sessions'] as $i => $s ) {
		if ( $s['id'] === $session['id'] ) {
			$agenda['sessions'][ $i ] = $session;
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		if ( 0 === $session['sort_order'] ) {
			$session['sort_order'] = ss_next_session_order( $agenda['sessions'] );
		}
		$agenda['sessions'][] = $session;
	}

	ss_save_agenda( $agenda );
	return $session['id'];
}

/**
 * Delete a session by ID.
 *
 * @param string $id Session ID.
 */
function ss_delete_session( $id ) {
	$agenda = ss_get_agenda();
	$agenda['sessions'] = array_values( array_filter(
		$agenda['sessions'],
		function ( $s ) use ( $id ) {
			return $s['id'] !== $id;
		}
	) );
	ss_save_agenda( $agenda );
}

/**
 * Duplicate a session (new ID, append " (Copy)" to title).
 *
 * @param string $id Source session ID.
 * @return string|null New session ID or null if source not found.
 */
function ss_duplicate_session( $id ) {
	$source = ss_get_session( $id );
	if ( ! $source ) {
		return null;
	}
	$source['id']         = ss_generate_session_id();
	$source['title']      = $source['title'] . ' (Copy)';
	$source['sort_order'] = 0; // will be set to next order
	return ss_upsert_session( $source );
}

/* -------------------------------------------------------------------------
   Validation
   ------------------------------------------------------------------------- */

/**
 * Validate a session and return errors.
 *
 * @param array $session  Sanitized session data.
 * @param array $all_sessions All existing sessions (for conflict checks).
 * @param string|null $editing_id ID of the session being edited (to exclude from conflict checks).
 * @return array Array of error strings. Empty = valid.
 */
function ss_validate_session( $session, $all_sessions = array(), $editing_id = null ) {
	$errors = array();

	/* Required title */
	if ( empty( $session['title'] ) ) {
		$errors[] = __( 'Title is required.', 'sender-symposium' );
	}

	/* Date format */
	if ( empty( $session['date'] ) ) {
		$errors[] = __( 'Date is required.', 'sender-symposium' );
	} elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $session['date'] ) ) {
		$errors[] = __( 'Date must be in YYYY-MM-DD format.', 'sender-symposium' );
	}

	/* Time validation */
	$is_break = in_array( $session['type'], array( 'break', 'lunch', 'networking' ), true );
	if ( empty( $session['start_time'] ) ) {
		$errors[] = __( 'Start time is required.', 'sender-symposium' );
	} elseif ( ! preg_match( '/^\d{2}:\d{2}$/', $session['start_time'] ) ) {
		$errors[] = __( 'Start time must be in HH:MM format.', 'sender-symposium' );
	}
	if ( empty( $session['end_time'] ) ) {
		$errors[] = __( 'End time is required.', 'sender-symposium' );
	} elseif ( ! preg_match( '/^\d{2}:\d{2}$/', $session['end_time'] ) ) {
		$errors[] = __( 'End time must be in HH:MM format.', 'sender-symposium' );
	}
	if ( ! empty( $session['start_time'] ) && ! empty( $session['end_time'] ) && $session['end_time'] <= $session['start_time'] ) {
		$errors[] = __( 'End time must be after start time.', 'sender-symposium' );
	}

	/* Room required for non-break types */
	if ( ! $is_break && empty( $session['room'] ) ) {
		$errors[] = __( 'Room/location is required for this session type.', 'sender-symposium' );
	}

	/* Participant references */
	if ( function_exists( 'ss_get_speaker' ) ) {
		foreach ( $session['participants'] as $p ) {
			if ( ! empty( $p['speaker_id'] ) && null === ss_get_speaker( $p['speaker_id'] ) ) {
				/* translators: %s: speaker ID */
				$errors[] = sprintf( __( 'Participant references non-existent speaker: %s', 'sender-symposium' ), $p['speaker_id'] );
			}
		}
	}

	/* Conflict checks against other sessions */
	if ( ! empty( $session['date'] ) && ! empty( $session['start_time'] ) && ! empty( $session['end_time'] ) ) {
		foreach ( $all_sessions as $other ) {
			if ( $other['id'] === $editing_id || $other['id'] === $session['id'] ) {
				continue;
			}
			if ( $other['date'] !== $session['date'] ) {
				continue;
			}
			if ( 'draft' === $other['status'] ) {
				continue;
			}

			/* Check time overlap */
			$overlaps = $session['start_time'] < $other['end_time'] && $session['end_time'] > $other['start_time'];
			if ( ! $overlaps ) {
				continue;
			}

			/* Same room conflict */
			if ( ! empty( $session['room'] ) && ! empty( $other['room'] ) && $session['room'] === $other['room'] ) {
				/* translators: %s: conflicting session title */
				$errors[] = sprintf(
					__( 'Room "%1$s" is already booked at this time by "%2$s".', 'sender-symposium' ),
					$session['room'],
					$other['title']
				);
			}

			/* Same speaker conflict */
			if ( ! empty( $session['participants'] ) ) {
				$session_speaker_ids = array_column( $session['participants'], 'speaker_id' );
				$other_speaker_ids   = array_column( $other['participants'], 'speaker_id' );
				$conflicts           = array_intersect( $session_speaker_ids, $other_speaker_ids );
				$conflicts           = array_filter( $conflicts ); // remove empty strings
				if ( ! empty( $conflicts ) ) {
					foreach ( $conflicts as $spk_id ) {
						$spk = function_exists( 'ss_get_speaker' ) ? ss_get_speaker( $spk_id ) : null;
						$name = $spk ? $spk['name'] : $spk_id;
						/* translators: %1$s: speaker name, %2$s: conflicting session title */
						$errors[] = sprintf(
							__( 'Speaker "%1$s" is already assigned to overlapping session "%2$s".', 'sender-symposium' ),
							$name,
							$other['title']
						);
					}
				}
			}
		}
	}

	return $errors;
}

/* -------------------------------------------------------------------------
   Backup & Restore
   ------------------------------------------------------------------------- */

/**
 * Save a backup snapshot of the current agenda before import.
 */
function ss_agenda_backup() {
	$current = get_option( 'ss_agenda', array() );
	if ( ! empty( $current ) ) {
		update_option( 'ss_agenda_backup', $current, false );
		update_option( 'ss_agenda_backup_time', gmdate( 'c' ), false );
	}
}

/**
 * Restore the last backup.
 *
 * @return bool True if restored, false if no backup exists.
 */
function ss_agenda_restore_backup() {
	$backup = get_option( 'ss_agenda_backup', array() );
	if ( empty( $backup ) ) {
		return false;
	}
	update_option( 'ss_agenda', $backup, false );
	return true;
}

/**
 * Check if a backup exists.
 *
 * @return array|false Backup metadata or false.
 */
function ss_agenda_has_backup() {
	$backup = get_option( 'ss_agenda_backup', array() );
	if ( empty( $backup ) ) {
		return false;
	}
	$time    = get_option( 'ss_agenda_backup_time', '' );
	$count   = isset( $backup['sessions'] ) ? count( $backup['sessions'] ) : 0;
	return array(
		'time'           => $time,
		'session_count'  => $count,
	);
}
