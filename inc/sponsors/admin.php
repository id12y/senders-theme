<?php
/**
 * Sponsors — Admin Page
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

function ss_sponsors_admin_menu() {
	add_submenu_page(
		'sender-symposium-settings',
		esc_html__( 'Sponsors', 'sender-symposium' ),
		esc_html__( 'Sponsors', 'sender-symposium' ),
		'manage_options',
		'ss-sponsors',
		'ss_sponsors_render_page'
	);
}
add_action( 'admin_menu', 'ss_sponsors_admin_menu' );

/* ─── Enqueue ─── */

function ss_sponsors_admin_enqueue( $hook ) {
	if ( 'sender-symposium_page_ss-sponsors' !== $hook ) {
		return;
	}
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	wp_enqueue_media();

	wp_enqueue_style(
		'ss-sponsors-admin',
		$uri . '/assets/css/sponsors-admin.css',
		array(),
		ss_asset_version( $dir . '/assets/css/sponsors-admin.css' )
	);
	wp_enqueue_script(
		'ss-sponsors-admin',
		$uri . '/assets/js/sponsors-admin.js',
		array(),
		ss_asset_version( $dir . '/assets/js/sponsors-admin.js' ),
		true
	);
	wp_localize_script( 'ss-sponsors-admin', 'ssSponsorsAdmin', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ss_sponsors_admin' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'ss_sponsors_admin_enqueue' );

/* ─── Prepopulate on admin visit ─── */

function ss_sponsors_admin_init_prepopulate() {
	ss_sponsors_maybe_prepopulate();
}
add_action( 'admin_init', 'ss_sponsors_admin_init_prepopulate' );

/* ─── Form handlers (POST → redirect) ─── */

function ss_sponsors_handle_actions() {
	if ( ! isset( $_POST['ss_sponsors_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action = sanitize_key( $_POST['ss_sponsors_action'] );
	$args   = array( 'page' => 'ss-sponsors' );

	switch ( $action ) {
		case 'save_sponsor':
			check_admin_referer( 'ss_save_sponsor' );
			$args['tab'] = 'sponsors';
			$raw         = wp_unslash( $_POST['sponsor'] ?? array() );
			if ( empty( $raw['sponsor_name'] ) ) {
				$args['message'] = 'sponsor_no_name';
				break;
			}
			$is_new = empty( $raw['id'] );
			if ( $is_new ) {
				$raw['id']    = ss_generate_sponsor_id();
				$raw['order'] = ss_next_sponsor_order();
			} else {
				$existing = ss_get_sponsor( $raw['id'] );
				if ( ! $existing ) {
					$args['message'] = 'sponsor_not_found';
					break;
				}
				$raw['order'] = $existing['order'];
				/* Preserve dismissed flag if not in form */
				if ( ! isset( $raw['dark_logo_reco_dismissed'] ) && ! empty( $existing['dark_logo_reco_dismissed'] ) ) {
					$raw['dark_logo_reco_dismissed'] = '1';
				}
			}
			ss_upsert_sponsor( ss_sanitize_sponsor( $raw ) );
			$args['message'] = $is_new ? 'sponsor_added' : 'sponsor_updated';
			break;

		case 'import_csv':
			check_admin_referer( 'ss_import_sponsors_csv' );
			$args['tab'] = 'import';
			if ( empty( $_FILES['csv_file']['tmp_name'] ) || UPLOAD_ERR_OK !== ( $_FILES['csv_file']['error'] ?? 4 ) ) {
				$args['message'] = 'import_no_file';
				break;
			}
			$always_new       = ! empty( $_POST['always_new'] );
			$result           = ss_import_sponsors_csv( $_FILES['csv_file']['tmp_name'], $always_new );
			$args['message']  = 'import_done';
			$args['imported'] = $result['imported'];
			$args['updated']  = $result['updated'];
			$args['skipped']  = $result['skipped'];
			if ( ! empty( $result['warnings'] ) ) {
				set_transient( 'ss_sponsor_import_warnings', $result['warnings'], 120 );
			}
			break;

		case 'save_display':
			check_admin_referer( 'ss_save_sponsors_display' );
			$args['tab'] = 'display';
			ss_save_sponsors_display( wp_unslash( $_POST['display'] ?? array() ) );
			$args['message'] = 'display_saved';
			break;

		case 'reset_defaults':
			check_admin_referer( 'ss_reset_sponsors' );
			$args['tab'] = 'sponsors';
			delete_option( 'ss_sponsors' );
			ss_sponsors_maybe_prepopulate();
			$args['message'] = 'reset_done';
			break;

		default:
			return;
	}

	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'ss_sponsors_handle_actions' );

/* ─── AJAX: Reorder ─── */

function ss_ajax_reorder_sponsors() {
	check_ajax_referer( 'ss_sponsors_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$ids = array_map( 'sanitize_key', explode( ',', $_POST['order'] ?? '' ) );
	if ( empty( $ids ) ) {
		wp_send_json_error( 'No order data' );
	}

	$sponsors = ss_get_sponsors();
	$id_map   = array();
	foreach ( $sponsors as &$s ) {
		$id_map[ $s['id'] ] = &$s;
	}
	unset( $s );

	foreach ( $ids as $pos => $id ) {
		if ( isset( $id_map[ $id ] ) ) {
			$id_map[ $id ]['order'] = $pos + 1;
		}
	}

	ss_save_sponsors( $sponsors );
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_reorder_sponsors', 'ss_ajax_reorder_sponsors' );

/* ─── AJAX: Toggle status / featured ─── */

function ss_ajax_toggle_sponsor() {
	check_ajax_referer( 'ss_sponsors_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$id    = sanitize_key( $_POST['id'] ?? '' );
	$field = sanitize_key( $_POST['field'] ?? '' );
	if ( ! in_array( $field, array( 'status', 'featured' ), true ) ) {
		wp_send_json_error( 'Invalid field' );
	}

	$sponsor = ss_get_sponsor( $id );
	if ( ! $sponsor ) {
		wp_send_json_error( 'Not found' );
	}

	if ( 'status' === $field ) {
		$sponsor['status'] = 'confirmed' === $sponsor['status'] ? 'unconfirmed' : 'confirmed';
	} else {
		$sponsor['featured'] = ! $sponsor['featured'];
	}

	ss_upsert_sponsor( $sponsor );
	wp_send_json_success( array( 'status' => $sponsor['status'], 'featured' => $sponsor['featured'] ) );
}
add_action( 'wp_ajax_ss_toggle_sponsor', 'ss_ajax_toggle_sponsor' );

/* ─── AJAX: Delete ─── */

function ss_ajax_delete_sponsor() {
	check_ajax_referer( 'ss_sponsors_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}
	$id = sanitize_key( $_POST['id'] ?? '' );
	if ( $id ) {
		ss_delete_sponsor( $id );
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_delete_sponsor_ajax', 'ss_ajax_delete_sponsor' );

/* ─── AJAX: Dismiss dark logo recommendation ─── */

function ss_ajax_dismiss_dark_logo_reco() {
	check_ajax_referer( 'ss_sponsors_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}
	$id      = sanitize_key( $_POST['id'] ?? '' );
	$sponsor = ss_get_sponsor( $id );
	if ( ! $sponsor ) {
		wp_send_json_error( 'Not found' );
	}
	$sponsor['dark_logo_reco_dismissed'] = true;
	ss_upsert_sponsor( $sponsor );
	wp_send_json_success();
}
add_action( 'wp_ajax_ss_dismiss_dark_logo_reco', 'ss_ajax_dismiss_dark_logo_reco' );
