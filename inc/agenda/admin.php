<?php
/**
 * Agenda Module — Admin Menu, Handlers & Asset Enqueue
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/admin-views.php';

/* -------------------------------------------------------------------------
   Menu Registration
   ------------------------------------------------------------------------- */

function ss_agenda_admin_menu() {
	add_submenu_page(
		'sender-symposium-settings',
		__( 'Agenda', 'sender-symposium' ),
		__( 'Agenda', 'sender-symposium' ),
		'manage_options',
		'ss-agenda',
		'ss_agenda_render_page'
	);
}
add_action( 'admin_menu', 'ss_agenda_admin_menu' );

/* -------------------------------------------------------------------------
   Admin Assets
   ------------------------------------------------------------------------- */

function ss_agenda_admin_enqueue( $hook ) {
	if ( 'sender-symposium_page_ss-agenda' !== $hook ) {
		return;
	}
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	wp_enqueue_style(
		'ss-agenda-admin',
		$uri . '/assets/css/agenda-admin.css',
		array(),
		ss_asset_version( $dir . '/assets/css/agenda-admin.css' )
	);

	wp_enqueue_script(
		'ss-agenda-admin',
		$uri . '/assets/js/agenda-admin.js',
		array(),
		ss_asset_version( $dir . '/assets/js/agenda-admin.js' ),
		array( 'in_footer' => true )
	);

	wp_localize_script( 'ss-agenda-admin', 'ssAgendaAdmin', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ss_agenda_admin' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'ss_agenda_admin_enqueue' );

/* -------------------------------------------------------------------------
   POST Action Handlers (admin_init)
   ------------------------------------------------------------------------- */

function ss_agenda_handle_actions() {
	if ( ! isset( $_POST['ss_agenda_action'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'sender-symposium' ) );
	}

	$action   = sanitize_key( $_POST['ss_agenda_action'] );
	$redirect = admin_url( 'admin.php?page=ss-agenda' );

	switch ( $action ) {

		case 'save_session':
			check_admin_referer( 'ss_save_session' );
			$raw     = isset( $_POST['session'] ) ? wp_unslash( $_POST['session'] ) : array();
			$session = ss_sanitize_session( $raw );
			$agenda  = ss_get_agenda();
			$edit_id = ! empty( $raw['id'] ) ? sanitize_key( $raw['id'] ) : null;

			$errors = ss_validate_session( $session, $agenda['sessions'], $edit_id );
			if ( ! empty( $errors ) ) {
				set_transient( 'ss_agenda_errors', $errors, 60 );
				$redirect = add_query_arg( array( 'tab' => 'sessions', 'message' => 'validation_error' ), $redirect );
				break;
			}

			$session_id = ss_upsert_session( $session );
			$msg = $edit_id ? 'session_updated' : 'session_added';
			$redirect = add_query_arg( array( 'tab' => 'sessions', 'message' => $msg ), $redirect );
			break;

		case 'delete_session':
			check_admin_referer( 'ss_delete_session' );
			$id = isset( $_POST['session_id'] ) ? sanitize_key( $_POST['session_id'] ) : '';
			if ( ! empty( $id ) ) {
				ss_delete_session( $id );
			}
			$redirect = add_query_arg( array( 'tab' => 'sessions', 'message' => 'session_deleted' ), $redirect );
			break;

		case 'duplicate_session':
			check_admin_referer( 'ss_duplicate_session' );
			$id = isset( $_POST['session_id'] ) ? sanitize_key( $_POST['session_id'] ) : '';
			if ( ! empty( $id ) ) {
				ss_duplicate_session( $id );
			}
			$redirect = add_query_arg( array( 'tab' => 'sessions', 'message' => 'session_duplicated' ), $redirect );
			break;

		case 'import_json':
			check_admin_referer( 'ss_agenda_import' );
			if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
				$redirect = add_query_arg( array( 'tab' => 'import', 'message' => 'import_no_file' ), $redirect );
				break;
			}
			$ext = strtolower( pathinfo( $_FILES['import_file']['name'], PATHINFO_EXTENSION ) );
			if ( 'json' !== $ext ) {
				$redirect = add_query_arg( array( 'tab' => 'import', 'message' => 'import_wrong_type' ), $redirect );
				break;
			}
			$json   = file_get_contents( $_FILES['import_file']['tmp_name'] );
			$parsed = ss_agenda_parse_json_import( $json );
			if ( ! empty( $parsed['errors'] ) ) {
				set_transient( 'ss_agenda_import_errors', $parsed['errors'], 120 );
				$redirect = add_query_arg( array( 'tab' => 'import', 'message' => 'import_errors' ), $redirect );
				break;
			}
			$tz = isset( $parsed['timezone'] ) ? $parsed['timezone'] : '';
			ss_agenda_apply_import( $parsed['sessions'], $tz );
			if ( ! empty( $parsed['warnings'] ) ) {
				set_transient( 'ss_agenda_import_warnings', $parsed['warnings'], 120 );
			}
			$redirect = add_query_arg( array(
				'tab'      => 'import',
				'message'  => 'import_done',
				'imported' => count( $parsed['sessions'] ),
			), $redirect );
			break;

		case 'import_csv':
			check_admin_referer( 'ss_agenda_import' );
			if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
				$redirect = add_query_arg( array( 'tab' => 'import', 'message' => 'import_no_file' ), $redirect );
				break;
			}
			$ext = strtolower( pathinfo( $_FILES['import_file']['name'], PATHINFO_EXTENSION ) );
			if ( 'csv' !== $ext ) {
				$redirect = add_query_arg( array( 'tab' => 'import', 'message' => 'import_wrong_type' ), $redirect );
				break;
			}
			$parsed = ss_agenda_parse_csv_import( $_FILES['import_file']['tmp_name'] );
			if ( ! empty( $parsed['errors'] ) ) {
				set_transient( 'ss_agenda_import_errors', $parsed['errors'], 120 );
				$redirect = add_query_arg( array( 'tab' => 'import', 'message' => 'import_errors' ), $redirect );
				break;
			}
			ss_agenda_apply_import( $parsed['sessions'] );
			if ( ! empty( $parsed['warnings'] ) ) {
				set_transient( 'ss_agenda_import_warnings', $parsed['warnings'], 120 );
			}
			$redirect = add_query_arg( array(
				'tab'      => 'import',
				'message'  => 'import_done',
				'imported' => count( $parsed['sessions'] ),
			), $redirect );
			break;

		case 'restore_backup':
			check_admin_referer( 'ss_agenda_restore' );
			$restored = ss_agenda_restore_backup();
			$msg      = $restored ? 'backup_restored' : 'no_backup';
			$redirect = add_query_arg( array( 'tab' => 'import', 'message' => $msg ), $redirect );
			break;

		case 'save_settings':
			check_admin_referer( 'ss_agenda_settings' );
			$agenda = ss_get_agenda();
			$agenda['timezone'] = isset( $_POST['timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['timezone'] ) ) : $agenda['timezone'];
			ss_save_agenda( $agenda );
			$redirect = add_query_arg( array( 'tab' => 'settings', 'message' => 'settings_saved' ), $redirect );
			break;
	}

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_init', 'ss_agenda_handle_actions' );

/* -------------------------------------------------------------------------
   AJAX Handlers
   ------------------------------------------------------------------------- */

/**
 * Reorder sessions via AJAX.
 */
function ss_ajax_reorder_sessions() {
	check_ajax_referer( 'ss_agenda_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	$order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
	$ids   = array_filter( array_map( 'trim', explode( ',', $order ) ) );
	if ( empty( $ids ) ) {
		wp_send_json_error( 'No IDs' );
	}

	$agenda = ss_get_agenda();
	$sort   = 1;
	foreach ( $ids as $id ) {
		foreach ( $agenda['sessions'] as &$s ) {
			if ( $s['id'] === $id ) {
				$s['sort_order'] = $sort++;
				break;
			}
		}
		unset( $s );
	}
	ss_save_agenda( $agenda );
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_reorder_sessions', 'ss_ajax_reorder_sessions' );

/**
 * Delete session via AJAX.
 */
function ss_ajax_delete_session() {
	check_ajax_referer( 'ss_agenda_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
	if ( empty( $id ) ) {
		wp_send_json_error( 'No ID' );
	}
	ss_delete_session( $id );
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_delete_session_ajax', 'ss_ajax_delete_session' );

/**
 * Bulk delete sessions via AJAX.
 */
function ss_ajax_bulk_delete_sessions() {
	check_ajax_referer( 'ss_agenda_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	$raw = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
	$ids = array_filter( array_map( 'sanitize_key', explode( ',', $raw ) ) );
	if ( empty( $ids ) ) {
		wp_send_json_error( 'No IDs' );
	}
	foreach ( $ids as $id ) {
		ss_delete_session( $id );
	}
	wp_send_json_success( array( 'deleted' => count( $ids ) ) );
}
add_action( 'wp_ajax_ss_bulk_delete_sessions', 'ss_ajax_bulk_delete_sessions' );

/* -------------------------------------------------------------------------
   Export Download Handlers (admin_init, early)
   ------------------------------------------------------------------------- */

function ss_agenda_handle_exports() {
	if ( ! isset( $_GET['ss_agenda_export'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$format = sanitize_key( $_GET['ss_agenda_export'] );

	switch ( $format ) {
		case 'json':
			check_admin_referer( 'ss_agenda_export' );
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="agenda-export-' . gmdate( 'Y-m-d' ) . '.json"' );
			echo ss_agenda_export_json();
			exit;

		case 'csv':
			check_admin_referer( 'ss_agenda_export' );
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="agenda-export-' . gmdate( 'Y-m-d' ) . '.csv"' );
			echo ss_agenda_export_csv();
			exit;

		case 'template':
			check_admin_referer( 'ss_agenda_export' );
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="agenda-template.csv"' );
			echo ss_agenda_blank_csv_template();
			exit;
	}
}
add_action( 'admin_init', 'ss_agenda_handle_exports', 5 );
