<?php
/**
 * HeySummit v2 API client.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Api;

use Emailexpert\Events\Logging\Logger;
use WP_Error;

/**
 * Read-only client for the HeySummit v2 API (SPEC 3.3).
 *
 * - Wraps wp_remote_get(). GET only: this build never writes to HeySummit,
 *   and the class exposes no way to issue a non-GET request.
 * - Timeout 15s. Two retries with backoff on 5xx and network timeouts.
 *   No retry on 4xx.
 * - Follows DRF pagination (`next`) until exhausted, hard-capped at 50
 *   pages by default (filter: `eex_client_max_pages`).
 * - 401/403 produce a WP_Error with code `eex_auth`; callers surface the
 *   persistent admin notice and abort their run.
 * - Every request outcome is logged (endpoint, status, duration). The API
 *   key is never logged.
 */
class HeySummitClient {

	public const BASE_URL = 'https://app.heysummit.com/api/v2/';

	/**
	 * Error code for authentication/authorisation failures.
	 */
	public const ERROR_AUTH = 'eex_auth';

	/**
	 * Request timeout in seconds.
	 */
	private const TIMEOUT = 15;

	/**
	 * Retries after the initial attempt, on 5xx or network failure.
	 */
	private const MAX_RETRIES = 2;

	/**
	 * API key for this client instance.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Base URL, trailing slash guaranteed.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * HTTP transport. Defaults to wp_remote_get; injectable for tests.
	 *
	 * @var callable
	 */
	private $transport;

	/**
	 * Constructor.
	 *
	 * @param string      $api_key HeySummit API key.
	 * @param Logger|null $logger  Logger instance.
	 * @param array       $args    Optional overrides: `transport` (callable
	 *                             with wp_remote_get signature), `base_url`.
	 */
	public function __construct( string $api_key, ?Logger $logger = null, array $args = array() ) {
		$this->api_key   = $api_key;
		$this->logger    = $logger ?? new Logger();
		$this->base_url  = trailingslashit( $args['base_url'] ?? self::BASE_URL );
		$this->transport = $args['transport'] ?? 'wp_remote_get';
	}

	/**
	 * GET a single API path and return the decoded JSON body.
	 *
	 * @param string $path  Path relative to the base URL, e.g. 'events/'.
	 * @param array  $query Query arguments.
	 * @return array|WP_Error Decoded body on success.
	 */
	public function get( string $path, array $query = array() ) {
		return $this->request( $this->build_url( $path, $query ) );
	}

	/**
	 * GET a paginated collection, following `next` until exhausted.
	 *
	 * Returns the merged `results` array. If the response is not paginated
	 * (no `results` key), the decoded body is returned unchanged.
	 *
	 * @param string $path  Path relative to the base URL.
	 * @param array  $query Query arguments for the first page.
	 * @return array|WP_Error Merged results on success.
	 */
	public function get_all( string $path, array $query = array() ) {
		/**
		 * Hard safety cap on pages followed per collection.
		 *
		 * @param int    $max_pages Default 50.
		 * @param string $path      The collection path being fetched.
		 */
		$max_pages = (int) apply_filters( 'eex_client_max_pages', 50, $path );

		$url     = $this->build_url( $path, $query );
		$results = array();
		$pages   = 0;

		while ( $url ) {
			$body = $this->request( $url );
			if ( is_wp_error( $body ) ) {
				return $body;
			}

			if ( ! isset( $body['results'] ) || ! is_array( $body['results'] ) ) {
				// Not a paginated collection: return the object as-is.
				return 0 === $pages ? $body : $results;
			}

			$results = array_merge( $results, $body['results'] );
			++$pages;

			$next = $body['next'] ?? null;
			if ( ! is_string( $next ) || '' === $next ) {
				break;
			}

			if ( $pages >= $max_pages ) {
				$this->logger->log(
					Logger::CONTEXT_API,
					Logger::LEVEL_WARNING,
					sprintf( 'Pagination cap of %d pages reached for %s; results truncated.', $max_pages, $path ),
					array(
						'path'  => $path,
						'pages' => $pages,
					)
				);
				break;
			}

			$next_url = $this->validate_next_url( $next );
			if ( is_wp_error( $next_url ) ) {
				return $next_url;
			}
			$url = $next_url;
		}//end while

		return $results;
	}

	/**
	 * Perform a GET with retry/backoff and logging.
	 *
	 * @param string $url Absolute URL.
	 * @return array|WP_Error Decoded JSON body on success.
	 */
	private function request( string $url ) {
		$endpoint = $this->loggable_endpoint( $url );
		$attempts = 0;

		do {
			++$attempts;
			$start = microtime( true );

			$response = call_user_func(
				$this->transport,
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'Authorization' => 'Token ' . $this->api_key,
						'Accept'        => 'application/json',
					),
				)
			);

			$duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );

			if ( is_wp_error( $response ) ) {
				$this->log_outcome( $endpoint, 0, $duration_ms, $response->get_error_message() );
				$retryable = true;
				$result    = new WP_Error(
					'eex_http_error',
					sprintf(
						/* translators: 1: endpoint path, 2: error detail. */
						__( 'HeySummit request to %1$s failed: %2$s', 'emailexpert-events' ),
						$endpoint,
						$response->get_error_message()
					)
				);
			} else {
				$status = (int) wp_remote_retrieve_response_code( $response );
				$this->log_outcome( $endpoint, $status, $duration_ms );

				if ( 200 === $status ) {
					$body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( ! is_array( $body ) ) {
						return new WP_Error(
							'eex_bad_json',
							sprintf(
								/* translators: %s: endpoint path. */
								__( 'HeySummit returned a response that is not valid JSON for %s.', 'emailexpert-events' ),
								$endpoint
							)
						);
					}
					return $body;
				}

				if ( 401 === $status || 403 === $status ) {
					return new WP_Error(
						self::ERROR_AUTH,
						__( 'HeySummit API key invalid or lacks access.', 'emailexpert-events' ),
						array( 'status' => $status )
					);
				}

				$retryable = $status >= 500;
				$result    = new WP_Error(
					'eex_http_' . $status,
					sprintf(
						/* translators: 1: endpoint path, 2: HTTP status code. */
						__( 'HeySummit request to %1$s returned HTTP %2$d.', 'emailexpert-events' ),
						$endpoint,
						$status
					),
					array( 'status' => $status )
				);
			}//end if

			if ( $retryable && $attempts <= self::MAX_RETRIES ) {
				$this->backoff( $attempts );
				continue;
			}

			return $result;
		} while ( true );
	}

	/**
	 * Sleep before a retry. Filterable so tests and CLI can zero it.
	 *
	 * @param int $attempt Attempt number just failed (1-based).
	 */
	private function backoff( int $attempt ): void {
		/**
		 * Seconds to wait before retry N.
		 *
		 * @param int $delay   Default: attempt number (1s, then 2s).
		 * @param int $attempt The attempt that just failed.
		 */
		$delay = (float) apply_filters( 'eex_client_retry_delay', $attempt, $attempt );
		if ( $delay > 0 ) {
			usleep( (int) ( $delay * 1000000 ) );
		}
	}

	/**
	 * Build an absolute URL from a relative path and query args.
	 *
	 * @param string $path  Relative path.
	 * @param array  $query Query args.
	 */
	private function build_url( string $path, array $query = array() ): string {
		$url = $this->base_url . ltrim( $path, '/' );
		if ( $query ) {
			$url = add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $query ) ), $url );
		}
		return $url;
	}

	/**
	 * Ensure a `next` pagination URL stays on the API host before following
	 * it with our Authorization header attached.
	 *
	 * @param string $next The `next` URL from a paginated response.
	 * @return string|WP_Error
	 */
	private function validate_next_url( string $next ) {
		$base_host = wp_parse_url( $this->base_url, PHP_URL_HOST );
		$next_host = wp_parse_url( $next, PHP_URL_HOST );

		if ( ! $next_host || strtolower( (string) $next_host ) !== strtolower( (string) $base_host ) ) {
			return new WP_Error(
				'eex_bad_next_url',
				__( 'HeySummit pagination pointed at an unexpected host; aborting.', 'emailexpert-events' )
			);
		}
		return $next;
	}

	/**
	 * Endpoint string safe for logs: path and query only, never the key
	 * (the key travels in a header and is never part of the URL).
	 *
	 * @param string $url Absolute URL.
	 */
	private function loggable_endpoint( string $url ): string {
		$parts = wp_parse_url( $url );
		$path  = $parts['path'] ?? $url;
		if ( ! empty( $parts['query'] ) ) {
			$path .= '?' . $parts['query'];
		}
		return $path;
	}

	/**
	 * Log a request outcome.
	 *
	 * @param string $endpoint    Endpoint path.
	 * @param int    $status      HTTP status, 0 for transport failure.
	 * @param int    $duration_ms Duration in milliseconds.
	 * @param string $detail      Optional error detail.
	 */
	private function log_outcome( string $endpoint, int $status, int $duration_ms, string $detail = '' ): void {
		$level = ( $status >= 200 && $status < 300 ) ? Logger::LEVEL_INFO : Logger::LEVEL_WARNING;

		$data = array(
			'endpoint'    => $endpoint,
			'status'      => $status,
			'duration_ms' => $duration_ms,
		);
		if ( '' !== $detail ) {
			$data['detail'] = $detail;
		}

		$this->logger->log(
			Logger::CONTEXT_API,
			$level,
			sprintf( 'GET %s -> %s (%dms)', $endpoint, $status ? $status : 'network error', $duration_ms ),
			$data
		);
	}
}
