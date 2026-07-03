<?php
/**
 * HeySummit connection store.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Api;

/**
 * Manages the list of HeySummit API connections (SPEC 5.1).
 *
 * Connections are a list, not a single key, because different properties
 * (the hub, FORUM, Deliverability Summit) may live under different
 * HeySummit accounts. Stored in the `eex_connections` option as:
 *
 *   [ [ 'id' => 'c_ab12cd34', 'label' => 'Member Hub', 'api_key' => '...' ], ... ]
 *
 * The first connection's key can be overridden by the
 * EEX_HEYSUMMIT_API_KEY constant in wp-config.php; when defined, the stored
 * key for that connection is ignored and the UI field is disabled.
 */
class Connections {

	public const OPTION = 'eex_connections';

	/**
	 * All connections, with the constant override applied to the first.
	 *
	 * @return array<int, array{id: string, label: string, api_key: string}>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$stored = array_values( array_filter( $stored, 'is_array' ) );

		if ( $this->has_constant_key() ) {
			if ( empty( $stored ) ) {
				$stored[] = array(
					'id'      => 'c_constant',
					'label'   => __( 'Primary (wp-config constant)', 'emailexpert-events' ),
					'api_key' => '',
				);
			}
			$stored[0]['api_key'] = (string) constant( 'EEX_HEYSUMMIT_API_KEY' );
		}

		return $stored;
	}

	/**
	 * Fetch one connection by ID.
	 *
	 * @param string $id Connection ID.
	 * @return array{id: string, label: string, api_key: string}|null
	 */
	public function get( string $id ): ?array {
		foreach ( $this->all() as $connection ) {
			if ( $connection['id'] === $id ) {
				return $connection;
			}
		}
		return null;
	}

	/**
	 * The first configured connection, if any.
	 *
	 * @return array{id: string, label: string, api_key: string}|null
	 */
	public function first(): ?array {
		$all = $this->all();
		return $all[0] ?? null;
	}

	/**
	 * Whether the wp-config constant override is in effect.
	 */
	public function has_constant_key(): bool {
		return defined( 'EEX_HEYSUMMIT_API_KEY' ) && '' !== (string) constant( 'EEX_HEYSUMMIT_API_KEY' );
	}

	/**
	 * Sanitise and persist submitted connections.
	 *
	 * Keys are write-only in the UI: an empty submitted key means
	 * "keep the stored key". The constant-overridden first key is never
	 * written to the database.
	 *
	 * @param array $submitted Raw submitted rows: [ ['id','label','api_key'], ... ].
	 * @return array<int, array{id: string, label: string, api_key: string}> The sanitised list as stored.
	 */
	public function save( array $submitted ): array {
		$existing = get_option( self::OPTION, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		$existing_by_id = array();
		foreach ( $existing as $row ) {
			if ( is_array( $row ) && ! empty( $row['id'] ) ) {
				$existing_by_id[ (string) $row['id'] ] = $row;
			}
		}

		$clean = array();
		foreach ( $submitted as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id    = sanitize_key( $row['id'] ?? '' );
			$label = sanitize_text_field( $row['label'] ?? '' );
			$key   = trim( sanitize_text_field( $row['api_key'] ?? '' ) );

			if ( '' === $id ) {
				$id = self::generate_id();
			}

			if ( '' === $key && isset( $existing_by_id[ $id ]['api_key'] ) ) {
				$key = (string) $existing_by_id[ $id ]['api_key'];
			}

			// The first connection may run on the constant alone; rows beyond
			// the first are meaningless without a key or label.
			if ( '' === $label && '' === $key && ! ( 0 === $index && $this->has_constant_key() ) ) {
				continue;
			}

			if ( '' === $label ) {
				$label = __( 'Connection', 'emailexpert-events' ) . ' ' . ( count( $clean ) + 1 );
			}

			// Never persist the constant key to the database.
			if ( 0 === count( $clean ) && $this->has_constant_key() ) {
				$key = isset( $existing_by_id[ $id ] ) ? (string) ( $existing_by_id[ $id ]['api_key'] ?? '' ) : '';
			}

			$clean[] = array(
				'id'      => $id,
				'label'   => $label,
				'api_key' => $key,
			);
		}//end foreach

		update_option( self::OPTION, $clean, false );

		return $clean;
	}

	/**
	 * Masked representation of a key for display: last four characters only.
	 *
	 * @param string $key API key.
	 */
	public static function mask_key( string $key ): string {
		if ( '' === $key ) {
			return '';
		}
		return '•••• ' . substr( $key, -4 );
	}

	/**
	 * Generate a connection ID.
	 */
	public static function generate_id(): string {
		return 'c_' . substr( md5( uniqid( 'eex', true ) ), 0, 8 );
	}
}
