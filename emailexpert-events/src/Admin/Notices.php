<?php
/**
 * Persistent admin notices.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Admin;

/**
 * Persistent admin notices for API failures (SPEC 3.3).
 *
 * A connection flagged with an auth failure shows an error notice on every
 * admin screen until a successful request clears the flag. Flags survive
 * across requests via the `eex_auth_failures` option.
 */
class Notices {

	public const OPTION = 'eex_auth_failures';

	/**
	 * Attach hooks.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Flag a connection as failing authentication.
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $label         Connection label for display.
	 */
	public function flag_auth_failure( string $connection_id, string $label ): void {
		$failures                   = $this->failures();
		$failures[ $connection_id ] = $label;
		update_option( self::OPTION, $failures, false );
	}

	/**
	 * Clear a connection's auth failure flag.
	 *
	 * @param string $connection_id Connection ID.
	 */
	public function clear_auth_failure( string $connection_id ): void {
		$failures = $this->failures();
		if ( isset( $failures[ $connection_id ] ) ) {
			unset( $failures[ $connection_id ] );
			update_option( self::OPTION, $failures, false );
		}
	}

	/**
	 * Current failure flags.
	 *
	 * @return array<string, string> connection_id => label.
	 */
	private function failures(): array {
		$failures = get_option( self::OPTION, array() );
		return is_array( $failures ) ? $failures : array();
	}

	/**
	 * Render notices for flagged connections.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( $this->failures() as $label ) {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
				esc_html__( 'emailexpert Events:', 'emailexpert-events' ),
				esc_html(
					sprintf(
						/* translators: %s: connection label. */
						__( 'HeySummit API key invalid or lacks access for connection "%s". Syncing is paused for this connection.', 'emailexpert-events' ),
						$label
					)
				),
				esc_url( admin_url( 'options-general.php?page=emailexpert-events' ) ),
				esc_html__( 'Check the connection settings.', 'emailexpert-events' )
			);
		}
	}
}
