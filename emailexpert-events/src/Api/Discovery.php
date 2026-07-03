<?php
/**
 * Live API discovery prober.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Api;

use WP_Error;

/**
 * Runs safe, read-only discovery probes against the HeySummit v2 API
 * (SPEC 3.2) and produces a structured report for docs/api-notes.md.
 *
 * Strictly GET-only. Never touches write or action endpoints: the API
 * includes an event archive action, and a stray POST against a live event
 * would archive it. Attendee emails are redacted from the report.
 */
class Discovery {

	/**
	 * API client.
	 *
	 * @var HeySummitClient
	 */
	private HeySummitClient $client;

	/**
	 * Constructor.
	 *
	 * @param HeySummitClient $client Authenticated client.
	 */
	public function __construct( HeySummitClient $client ) {
		$this->client = $client;
	}

	/**
	 * Run all probes and return a structured report.
	 *
	 * @return array|WP_Error Report array, or WP_Error if events/ is unreachable.
	 */
	public function run() {
		$report = array(
			'endpoints' => array(),
			'notes'     => array(),
		);

		// 1. Events list: the anchor for everything else.
		$events = $this->client->get( 'events/' );
		if ( is_wp_error( $events ) ) {
			return $events;
		}
		$report['endpoints']['events/'] = $this->describe_collection( $events );

		$first_event = $events['results'][0] ?? null;
		$event_id    = $first_event['id'] ?? null;

		if ( $event_id ) {
			$detail                              = $this->client->get( 'events/' . rawurlencode( (string) $event_id ) . '/' );
			$report['endpoints']['events/<id>/'] = is_wp_error( $detail )
				? array( 'error' => $detail->get_error_message() )
				: array( 'fields' => $this->field_types( $detail ) );
		} else {
			$report['notes'][] = 'No events visible to this key; downstream probes skipped.';
		}

		// 2. Collections, plus event-filter parameter discovery for each.
		foreach ( array( 'talks', 'speakers', 'categories', 'attendees' ) as $resource ) {
			$path = $resource . '/';
			$list = $this->client->get( $path );

			if ( is_wp_error( $list ) ) {
				$report['endpoints'][ $path ] = array( 'error' => $list->get_error_message() );
				continue;
			}

			$described = $this->describe_collection( $list, 'attendees' === $resource );
			if ( $event_id ) {
				$described['event_filter'] = $this->probe_event_filter( $path, (string) $event_id, (int) ( $list['count'] ?? 0 ) );
			}
			$report['endpoints'][ $path ] = $described;

			// Detail shape for the first item of each resource (attendee
			// details included: needed for webhook verification in M6).
			$first_id = $list['results'][0]['id'] ?? null;
			if ( null !== $first_id ) {
				$detail = $this->client->get( $path . rawurlencode( (string) $first_id ) . '/' );
				if ( ! is_wp_error( $detail ) ) {
					$report['endpoints'][ $resource . '/<id>/' ] = array(
						'fields' => $this->field_types( $detail, 'attendees' === $resource ),
					);
				}
			}
		}//end foreach

		// 3. Page size behaviour.
		$report['notes'][] = $this->probe_page_size();

		// 4. Replay/recording field hunt on talks (SPEC 4.3 `_eex_replay_url`).
		$talk_fields       = array_merge(
			array_keys( $report['endpoints']['talks/']['result_fields'] ?? array() ),
			array_keys( $report['endpoints']['talks/<id>/']['fields'] ?? array() )
		);
		$replayish         = array_values(
			array_filter(
				array_unique( $talk_fields ),
				static fn( $f ) => (bool) preg_match( '/replay|record|video|vimeo|youtube|embed/i', (string) $f )
			)
		);
		$report['notes'][] = $replayish
			? 'Candidate replay fields on talks: ' . implode( ', ', $replayish ) . '. Verify content before mapping.'
			: 'No replay/recording-shaped field found on talks; `_eex_replay_url` will be a manual editor-owned field.';

		return $report;
	}

	/**
	 * Describe a paginated collection response.
	 *
	 * @param array $body   Decoded list response.
	 * @param bool  $redact Redact email-shaped values (attendees).
	 */
	private function describe_collection( array $body, bool $redact = false ): array {
		$described = array(
			'paginated'     => isset( $body['results'] ),
			'count'         => $body['count'] ?? null,
			'result_fields' => array(),
		);

		$first = $body['results'][0] ?? null;
		if ( is_array( $first ) ) {
			$described['result_fields'] = $this->field_types( $first, $redact );
		}

		return $described;
	}

	/**
	 * Map of field name => observed type (and a truncated sample for scalars).
	 *
	 * @param array $item   Response object.
	 * @param bool  $redact Redact email-shaped values.
	 */
	private function field_types( array $item, bool $redact = false ): array {
		$fields = array();
		foreach ( $item as $key => $value ) {
			$type = strtolower( gettype( $value ) );
			if ( is_scalar( $value ) ) {
				$sample = (string) $value;
				if ( $redact || preg_match( '/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $sample ) ) {
					$sample = str_contains( $sample, '@' ) ? '[redacted email]' : $sample;
				}
				if ( strlen( $sample ) > 60 ) {
					$sample = substr( $sample, 0, 57 ) . '...';
				}
				$fields[ $key ] = $type . ' (' . $sample . ')';
			} else {
				$fields[ $key ] = $type;
			}
		}
		return $fields;
	}

	/**
	 * Try candidate event-filter parameter names against a collection and
	 * report which ones the API appears to honour (count changes or count
	 * equals unfiltered but endpoint accepts it without error).
	 *
	 * @param string $path            Collection path.
	 * @param string $event_id        Known event ID.
	 * @param int    $unfiltered_count Count without a filter.
	 */
	private function probe_event_filter( string $path, string $event_id, int $unfiltered_count ): array {
		$outcomes = array();
		foreach ( array( 'event', 'event_id', 'event__id', 'events' ) as $param ) {
			$filtered = $this->client->get( $path, array( $param => $event_id ) );
			if ( is_wp_error( $filtered ) ) {
				$outcomes[ $param ] = 'error: ' . $filtered->get_error_message();
				continue;
			}
			$count              = $filtered['count'] ?? null;
			$outcomes[ $param ] = sprintf(
				'accepted; count %s (unfiltered %d)%s',
				var_export( $count, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- report string, not debug output.
				$unfiltered_count,
				( null !== $count && (int) $count !== $unfiltered_count ) ? ' — appears to filter' : ' — may be ignored'
			);
		}
		return $outcomes;
	}

	/**
	 * Probe page size limits on events/.
	 */
	private function probe_page_size(): string {
		$probe = $this->client->get( 'events/', array( 'page_size' => 100 ) );
		if ( is_wp_error( $probe ) ) {
			return 'page_size probe failed: ' . $probe->get_error_message();
		}
		$returned = isset( $probe['results'] ) && is_array( $probe['results'] ) ? count( $probe['results'] ) : 0;
		return sprintf( 'events/?page_size=100 accepted; %d results on first page (count %s).', $returned, var_export( $probe['count'] ?? null, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- report string.
	}

	/**
	 * Render a report as Markdown for docs/api-notes.md.
	 *
	 * @param array  $report Report from run().
	 * @param string $when   ISO 8601 timestamp of the run.
	 */
	public static function to_markdown( array $report, string $when ): string {
		$md = "## Live discovery run ({$when})\n\n";

		foreach ( $report['endpoints'] as $endpoint => $info ) {
			$md .= "### `{$endpoint}`\n\n";

			if ( isset( $info['error'] ) ) {
				$md .= '- **Error:** ' . $info['error'] . "\n\n";
				continue;
			}
			if ( array_key_exists( 'paginated', $info ) ) {
				$md .= '- Paginated: ' . ( $info['paginated'] ? 'yes' : 'no' ) . "\n";
				$md .= '- Count: ' . var_export( $info['count'], true ) . "\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- doc generation.
			}
			$fields = $info['fields'] ?? $info['result_fields'] ?? array();
			if ( $fields ) {
				$md .= "- Fields:\n";
				foreach ( $fields as $name => $type ) {
					$md .= "  - `{$name}`: {$type}\n";
				}
			}
			if ( ! empty( $info['event_filter'] ) ) {
				$md .= "- Event filter probes:\n";
				foreach ( $info['event_filter'] as $param => $outcome ) {
					$md .= "  - `?{$param}=`: {$outcome}\n";
				}
			}
			$md .= "\n";
		}//end foreach

		if ( ! empty( $report['notes'] ) ) {
			$md .= "### Notes\n\n";
			foreach ( $report['notes'] as $note ) {
				$md .= "- {$note}\n";
			}
			$md .= "\n";
		}

		return $md;
	}
}
