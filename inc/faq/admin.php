<?php
/**
 * FAQ — Admin Page
 *
 * Menu registration, form handlers, asset enqueue.
 * Views are in admin-views.php.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/admin-views.php';

/* ─── Menu ─── */

function ss_faq_admin_menu() {
	add_theme_page(
		esc_html__( 'FAQ', 'sender-symposium' ),
		esc_html__( 'FAQ', 'sender-symposium' ),
		'manage_options',
		'ss-faq',
		'ss_faq_render_page'
	);
}
add_action( 'admin_menu', 'ss_faq_admin_menu' );

/* ─── Enqueue ─── */

function ss_faq_admin_enqueue( $hook ) {
	if ( 'appearance_page_ss-faq' !== $hook ) {
		return;
	}
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	wp_enqueue_style(
		'ss-faq-admin',
		$uri . '/assets/css/faq-admin.css',
		array(),
		ss_asset_version( $dir . '/assets/css/faq-admin.css' )
	);
	wp_enqueue_script(
		'ss-faq-admin',
		$uri . '/assets/js/faq-admin.js',
		array(),
		ss_asset_version( $dir . '/assets/js/faq-admin.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'ss_faq_admin_enqueue' );

/* ─── Prepopulate on first visit ─── */

function ss_faq_admin_init_prepopulate() {
	if ( isset( $_GET['page'] ) && 'ss-faq' === $_GET['page'] && current_user_can( 'manage_options' ) ) {
		ss_faq_maybe_prepopulate();
	}
}
add_action( 'admin_init', 'ss_faq_admin_init_prepopulate', 5 );

/* ─── Form handlers (POST → redirect) ─── */

function ss_faq_handle_actions() {
	if ( ! isset( $_POST['ss_faq_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action = sanitize_key( $_POST['ss_faq_action'] );
	$args   = array( 'page' => 'ss-faq' );

	switch ( $action ) {

		case 'save_settings':
			check_admin_referer( 'ss_faq_save_settings' );
			$faq = ss_get_faq();
			$faq['page_title']      = sanitize_text_field( wp_unslash( $_POST['page_title'] ?? '' ) );
			$faq['page_subtitle']   = sanitize_text_field( wp_unslash( $_POST['page_subtitle'] ?? '' ) );
			$faq['intro_paragraph'] = wp_kses_post( wp_unslash( $_POST['intro_paragraph'] ?? '' ) );

			$s = wp_unslash( $_POST['settings'] ?? array() );
			$faq['settings'] = array(
				'accordion_single_open'        => ! empty( $s['accordion_single_open'] ),
				'open_first_item_each_section' => ! empty( $s['open_first_item_each_section'] ),
				'show_page_subtitle'           => ! empty( $s['show_page_subtitle'] ),
				'show_intro_paragraph'         => ! empty( $s['show_intro_paragraph'] ),
				'section_style'                => in_array( $s['section_style'] ?? '', array( 'minimal', 'elevated' ), true )
					? $s['section_style'] : 'elevated',
				'hotel_display_style'          => in_array( $s['hotel_display_style'] ?? '', array( 'cards', 'list' ), true )
					? $s['hotel_display_style'] : 'cards',
			);

			ss_save_faq( $faq );
			$args['tab']     = 'settings';
			$args['message'] = 'settings_saved';
			break;

		case 'save_section':
			check_admin_referer( 'ss_faq_save_section' );
			$faq     = ss_get_faq();
			$raw_sec = wp_unslash( $_POST['section'] ?? array() );
			$sec_id  = sanitize_key( $raw_sec['id'] ?? '' );
			$is_new  = empty( $sec_id );

			if ( empty( $raw_sec['title'] ) ) {
				$args['message'] = 'no_title';
				break;
			}

			if ( $is_new ) {
				$sec_id    = ss_faq_id( 'sec' );
				$max_order = 0;
				foreach ( $faq['sections'] as $s ) {
					if ( $s['order'] > $max_order ) {
						$max_order = $s['order'];
					}
				}
				$section = array(
					'id'       => $sec_id,
					'title'    => sanitize_text_field( $raw_sec['title'] ),
					'subtitle' => sanitize_text_field( $raw_sec['subtitle'] ?? '' ),
					'order'    => $max_order + 1,
					'items'    => array(),
				);
			} else {
				$section = null;
				foreach ( $faq['sections'] as $s ) {
					if ( $s['id'] === $sec_id ) {
						$section = $s;
						break;
					}
				}
				if ( ! $section ) {
					$args['message'] = 'not_found';
					break;
				}
				$section['title']    = sanitize_text_field( $raw_sec['title'] );
				$section['subtitle'] = sanitize_text_field( $raw_sec['subtitle'] ?? '' );
			}

			/* Process items */
			$raw_items     = wp_unslash( $_POST['items'] ?? array() );
			$delete_ids    = array_map( 'sanitize_key', wp_unslash( $_POST['delete_items'] ?? array() ) );
			$new_items     = array();
			$item_order    = 1;

			foreach ( $raw_items as $ri ) {
				$item_id = sanitize_key( $ri['id'] ?? '' );
				if ( in_array( $item_id, $delete_ids, true ) ) {
					continue;
				}
				$q = sanitize_text_field( $ri['question'] ?? '' );
				if ( '' === $q ) {
					continue;
				}
				$new_items[] = array(
					'id'          => $item_id ?: ss_faq_id(),
					'question'    => $q,
					'answer_html' => wp_kses_post( $ri['answer_html'] ?? '' ),
					'order'       => $item_order++,
				);
			}
			$section['items'] = $new_items;

			/* Upsert section */
			$found = false;
			foreach ( $faq['sections'] as $i => $s ) {
				if ( $s['id'] === $sec_id ) {
					$faq['sections'][ $i ] = $section;
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$faq['sections'][] = $section;
			}

			ss_save_faq( $faq );
			$args['tab']     = 'sections';
			$args['action']  = 'edit';
			$args['id']      = $sec_id;
			$args['message'] = $is_new ? 'section_added' : 'section_saved';
			break;

		case 'delete_section':
			check_admin_referer( 'ss_faq_delete_section' );
			$faq    = ss_get_faq();
			$del_id = sanitize_key( $_POST['section_id'] ?? '' );
			$faq['sections'] = array_values( array_filter( $faq['sections'], function ( $s ) use ( $del_id ) {
				return $s['id'] !== $del_id;
			} ) );
			ss_save_faq( $faq );
			$args['tab']     = 'sections';
			$args['message'] = 'section_deleted';
			break;

		case 'reorder_sections':
			check_admin_referer( 'ss_faq_reorder_sections' );
			$faq   = ss_get_faq();
			$order = wp_unslash( $_POST['section_order'] ?? array() );
			foreach ( $faq['sections'] as &$s ) {
				if ( isset( $order[ $s['id'] ] ) ) {
					$s['order'] = absint( $order[ $s['id'] ] );
				}
			}
			unset( $s );
			usort( $faq['sections'], function ( $a, $b ) {
				return $a['order'] - $b['order'];
			} );
			ss_save_faq( $faq );
			$args['tab']     = 'sections';
			$args['message'] = 'order_saved';
			break;

		default:
			return;
	}

	wp_safe_redirect( add_query_arg( $args, admin_url( 'themes.php' ) ) );
	exit;
}
add_action( 'admin_init', 'ss_faq_handle_actions' );
