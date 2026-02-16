<?php
/**
 * Speakers — Admin Page
 *
 * Menu registration, form handlers, AJAX handlers, asset enqueue.
 * View rendering is in admin-views.php.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/admin-views.php';

/* ─── Menu ─── */

function ss_speakers_admin_menu() {
	add_submenu_page(
		'sender-symposium-settings',
		esc_html__( 'Speakers', 'sender-symposium' ),
		esc_html__( 'Speakers', 'sender-symposium' ),
		'manage_options',
		'ss-speakers',
		'ss_speakers_render_page'
	);
}
add_action( 'admin_menu', 'ss_speakers_admin_menu' );

/* ─── Enqueue ─── */

function ss_speakers_admin_enqueue( $hook ) {
	if ( 'sender-symposium_page_ss-speakers' !== $hook ) {
		return;
	}
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	wp_enqueue_style(
		'ss-speakers-admin',
		$uri . '/assets/css/speakers-admin.css',
		array(),
		ss_asset_version( $dir . '/assets/css/speakers-admin.css' )
	);
	wp_enqueue_script(
		'ss-speakers-admin',
		$uri . '/assets/js/speakers-admin.js',
		array(),
		ss_asset_version( $dir . '/assets/js/speakers-admin.js' ),
		true
	);
	wp_localize_script( 'ss-speakers-admin', 'ssSpeakersAdmin', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ss_speakers_admin' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'ss_speakers_admin_enqueue' );

/* ─── Form handlers (POST → redirect) ─── */

function ss_speakers_handle_actions() {
	if ( ! isset( $_POST['ss_speakers_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action = sanitize_key( $_POST['ss_speakers_action'] );
	$args   = array( 'page' => 'ss-speakers' );

	switch ( $action ) {
		case 'import_csv':
			check_admin_referer( 'ss_import_csv' );
			$args['tab'] = 'import';
			if ( empty( $_FILES['csv_file']['tmp_name'] ) || UPLOAD_ERR_OK !== ( $_FILES['csv_file']['error'] ?? 4 ) ) {
				$args['message'] = 'import_no_file';
				break;
			}
			$always_new       = ! empty( $_POST['always_new'] );
			$result           = ss_import_speakers_csv( $_FILES['csv_file']['tmp_name'], $always_new );
			$args['message']  = 'import_done';
			$args['imported'] = $result['imported'];
			$args['updated']  = $result['updated'];
			$args['skipped']  = $result['skipped'];
			if ( ! empty( $result['warnings'] ) ) {
				set_transient( 'ss_import_warnings', $result['warnings'], 120 );
			}
			break;

		case 'save_speaker':
			check_admin_referer( 'ss_save_speaker' );
			$args['tab'] = 'speakers';
			$raw         = wp_unslash( $_POST['speaker'] ?? array() );
			if ( empty( $raw['name'] ) ) {
				$args['message'] = 'speaker_no_name';
				break;
			}
			$is_new = empty( $raw['id'] );
			if ( $is_new ) {
				$raw['id']    = ss_generate_speaker_id();
				$raw['order'] = ss_next_speaker_order();
			} else {
				$existing = ss_get_speaker( $raw['id'] );
				if ( ! $existing ) {
					$args['message'] = 'speaker_not_found';
					break;
				}
				$raw['order'] = $existing['order'];
			}
			ss_upsert_speaker( ss_sanitize_speaker( $raw ) );
			$args['message'] = $is_new ? 'speaker_added' : 'speaker_updated';
			break;

		case 'save_display':
			check_admin_referer( 'ss_save_display' );
			$args['tab'] = 'display';
			ss_save_speakers_display( wp_unslash( $_POST['display'] ?? array() ) );
			$args['message'] = 'display_saved';
			break;

		default:
			return;
	}

	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'ss_speakers_handle_actions' );

/* ─── AJAX: Reorder ─── */

function ss_ajax_reorder_speakers() {
	check_ajax_referer( 'ss_speakers_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$ids = array_map( 'sanitize_key', explode( ',', $_POST['order'] ?? '' ) );
	if ( empty( $ids ) ) {
		wp_send_json_error( 'No order data' );
	}

	$speakers = ss_get_speakers();
	$id_map   = array();
	foreach ( $speakers as &$s ) {
		$id_map[ $s['id'] ] = &$s;
	}
	unset( $s );

	foreach ( $ids as $pos => $id ) {
		if ( isset( $id_map[ $id ] ) ) {
			$id_map[ $id ]['order'] = $pos + 1;
		}
	}

	ss_save_speakers( $speakers );
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_reorder_speakers', 'ss_ajax_reorder_speakers' );

/* ─── AJAX: Toggle status / featured ─── */

function ss_ajax_toggle_speaker() {
	check_ajax_referer( 'ss_speakers_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$id    = sanitize_key( $_POST['id'] ?? '' );
	$field = sanitize_key( $_POST['field'] ?? '' );
	if ( ! in_array( $field, array( 'status', 'featured' ), true ) ) {
		wp_send_json_error( 'Invalid field' );
	}

	$speaker = ss_get_speaker( $id );
	if ( ! $speaker ) {
		wp_send_json_error( 'Not found' );
	}

	if ( 'status' === $field ) {
		$speaker['status'] = 'published' === $speaker['status'] ? 'unconfirmed' : 'published';
	} else {
		$speaker['featured'] = ! $speaker['featured'];
	}

	ss_upsert_speaker( $speaker );
	wp_send_json_success( array( 'status' => $speaker['status'], 'featured' => $speaker['featured'] ) );
}
add_action( 'wp_ajax_ss_toggle_speaker', 'ss_ajax_toggle_speaker' );

/* ─── AJAX: Delete ─── */

function ss_ajax_delete_speaker() {
	check_ajax_referer( 'ss_speakers_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}
	$id = sanitize_key( $_POST['id'] ?? '' );
	if ( $id ) {
		ss_delete_speaker( $id );
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_delete_speaker_ajax', 'ss_ajax_delete_speaker' );
