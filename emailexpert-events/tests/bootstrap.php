<?php
/**
 * PHPUnit bootstrap: minimal WordPress shims for unit-testing plugin
 * classes without a WordPress install. Integration tests run under wp-env
 * against real WordPress; these shims cover only what the pure-logic unit
 * tests touch.
 */

declare( strict_types=1 );

error_reporting( E_ALL );

require_once dirname( __DIR__ ) . '/src/Autoloader.php';
\Emailexpert\Events\Autoloader::register();

// ---------------------------------------------------------------------------
// In-memory option store.
// ---------------------------------------------------------------------------

$GLOBALS['eex_test_options'] = array();

function get_option( string $name, $default_value = false ) {
	return $GLOBALS['eex_test_options'][ $name ] ?? $default_value;
}

function update_option( string $name, $value, $autoload = null ): bool {
	$GLOBALS['eex_test_options'][ $name ] = $value;
	return true;
}

function add_option( string $name, $value = '', $deprecated = '', $autoload = null ): bool {
	if ( array_key_exists( $name, $GLOBALS['eex_test_options'] ) ) {
		return false;
	}
	$GLOBALS['eex_test_options'][ $name ] = $value;
	return true;
}

function delete_option( string $name ): bool {
	unset( $GLOBALS['eex_test_options'][ $name ] );
	return true;
}

// ---------------------------------------------------------------------------
// Hooks: a tiny filter/action registry.
// ---------------------------------------------------------------------------

$GLOBALS['eex_test_filters'] = array();

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['eex_test_filters'][ $hook ][] = array( $callback, $accepted_args );
	return true;
}

function apply_filters( string $hook, $value, ...$args ) {
	foreach ( $GLOBALS['eex_test_filters'][ $hook ] ?? array() as [ $callback, $accepted_args ] ) {
		$call_args = array_merge( array( $value ), $args );
		$value     = $callback( ...array_slice( $call_args, 0, $accepted_args ) );
	}
	return $value;
}

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	return add_filter( $hook, $callback, $priority, $accepted_args );
}

function do_action( string $hook, ...$args ): void {
	foreach ( $GLOBALS['eex_test_filters'][ $hook ] ?? array() as [ $callback, $accepted_args ] ) {
		$callback( ...array_slice( $args, 0, $accepted_args ) );
	}
}

function eex_tests_reset_hooks_and_options(): void {
	$GLOBALS['eex_test_filters'] = array();
	$GLOBALS['eex_test_options'] = array();
}

// ---------------------------------------------------------------------------
// WP_Error and error helpers.
// ---------------------------------------------------------------------------

class WP_Error {

	private array $errors = array();
	private array $data   = array();

	public function __construct( string $code = '', string $message = '', $data = null ) {
		if ( '' !== $code ) {
			$this->errors[ $code ][] = $message;
			if ( null !== $data ) {
				$this->data[ $code ] = $data;
			}
		}
	}

	public function get_error_code(): string {
		return (string) array_key_first( $this->errors );
	}

	public function get_error_message( string $code = '' ): string {
		$code = '' !== $code ? $code : $this->get_error_code();
		return $this->errors[ $code ][0] ?? '';
	}

	public function get_error_data( string $code = '' ) {
		$code = '' !== $code ? $code : $this->get_error_code();
		return $this->data[ $code ] ?? null;
	}
}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}

// ---------------------------------------------------------------------------
// HTTP response helpers (responses are plain arrays, as from wp_remote_get).
// ---------------------------------------------------------------------------

function wp_remote_retrieve_response_code( $response ) {
	return $response['response']['code'] ?? '';
}

function wp_remote_retrieve_body( $response ): string {
	return (string) ( $response['body'] ?? '' );
}

// ---------------------------------------------------------------------------
// Misc WordPress utilities.
// ---------------------------------------------------------------------------

function trailingslashit( string $value ): string {
	return rtrim( $value, '/\\' ) . '/';
}

function wp_parse_url( string $url, int $component = -1 ) {
	return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
}

function add_query_arg( array $args, string $url ): string {
	if ( empty( $args ) ) {
		return $url;
	}
	$separator = str_contains( $url, '?' ) ? '&' : '?';
	$pairs     = array();
	foreach ( $args as $key => $value ) {
		$pairs[] = $key . '=' . $value;
	}
	return $url . $separator . implode( '&', $pairs );
}

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function sanitize_key( $key ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_text_field( $value ): string {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $value ) ) );
}

function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
	return json_encode( $data, $options, $depth ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
}
