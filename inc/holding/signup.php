<?php
/**
 * Holding Page — Email Signup Handler
 *
 * Captures "notify me" submissions from the post-event holding page.
 * Submissions are validated, stored in the `ss_holding_signups` option,
 * emailed to the site admin, and exposed via the `ss_holding_signup` hook
 * for wiring an external ESP/CRM. Nothing is dropped silently.
 *
 * Flow: front-end form POSTs to admin-post.php → validate → store/notify →
 * redirect back to the homepage with an ss_signup status (PRG pattern, so
 * it works with JavaScript disabled and is fully accessible).
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle a holding-page signup submission.
 */
function ss_holding_handle_signup() {
	$nonce = isset( $_POST['ss_holding_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ss_holding_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'ss_holding_signup' ) ) {
		ss_holding_redirect( 'error' );
	}

	/* Honeypot: real users never fill this. Silently accept (no storage) to
	   avoid giving bots feedback. */
	$honeypot = isset( $_POST['ss_website'] ) ? trim( (string) wp_unslash( $_POST['ss_website'] ) ) : '';
	if ( '' !== $honeypot ) {
		ss_holding_redirect( 'success' );
	}

	$email = isset( $_POST['ss_email'] ) ? sanitize_email( wp_unslash( $_POST['ss_email'] ) ) : '';
	if ( '' === $email || ! is_email( $email ) ) {
		ss_holding_redirect( 'invalid' );
	}

	ss_holding_store_signup( $email );
	ss_holding_notify_admin( $email );

	/**
	 * Fires after a valid holding-page signup is captured.
	 *
	 * Wire an external newsletter/ESP/CRM here without touching templates:
	 *   add_action( 'ss_holding_signup', function ( $email ) {
	 *       // e.g. push $email to Mailchimp / HubSpot / your API
	 *   } );
	 *
	 * @param string $email The validated signup email address.
	 */
	do_action( 'ss_holding_signup', $email );

	ss_holding_redirect( 'success' );
}
add_action( 'admin_post_ss_holding_signup', 'ss_holding_handle_signup' );
add_action( 'admin_post_nopriv_ss_holding_signup', 'ss_holding_handle_signup' );

/**
 * Append a signup to the stored list (deduplicated, capped).
 *
 * @param string $email Validated email address.
 */
function ss_holding_store_signup( $email ) {
	$list = ss_holding_get_signups();

	foreach ( $list as $row ) {
		if ( isset( $row['email'] ) && strtolower( $row['email'] ) === strtolower( $email ) ) {
			return; /* Already captured — no duplicate. */
		}
	}

	$list[] = array(
		'email' => $email,
		'time'  => current_time( 'mysql' ),
	);

	/* Keep the newest 2000 to bound option size. */
	if ( count( $list ) > 2000 ) {
		$list = array_slice( $list, -2000 );
	}

	update_option( 'ss_holding_signups', $list, false );
}

/**
 * Email the site admin about a new signup. Failure is non-fatal.
 *
 * @param string $email Validated email address.
 */
function ss_holding_notify_admin( $email ) {
	$to = get_option( 'admin_email' );
	if ( ! $to || ! is_email( $to ) ) {
		return;
	}

	$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf(
		/* translators: %s: site name. */
		__( '[%s] New Sender Symposium signup', 'sender-symposium' ),
		$site
	);
	$body = sprintf(
		/* translators: 1: email address, 2: timestamp, 3: site URL. */
		__( "A visitor asked to be notified when Sender Symposium returns.\n\nEmail: %1\$s\nTime: %2\$s\nSource: %3\$s\n", 'sender-symposium' ),
		$email,
		current_time( 'mysql' ),
		home_url( '/' )
	);

	wp_mail( $to, $subject, $body );
}

/**
 * Get the stored signup list.
 *
 * @return array<int, array{email:string,time:string}>
 */
function ss_holding_get_signups() {
	$list = get_option( 'ss_holding_signups', array() );
	return is_array( $list ) ? $list : array();
}

/**
 * Redirect back to the homepage with a signup status, then exit.
 *
 * @param string $status One of: success, invalid, error.
 */
function ss_holding_redirect( $status ) {
	$url = add_query_arg( 'ss_signup', $status, home_url( '/' ) ) . '#ss-notify';
	wp_safe_redirect( $url );
	exit;
}
