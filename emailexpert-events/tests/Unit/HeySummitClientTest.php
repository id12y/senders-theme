<?php
/**
 * Unit tests for HeySummitClient: pagination, retries, auth handling,
 * logging hygiene. HTTP is mocked via the injectable transport.
 */

declare( strict_types=1 );

namespace Emailexpert\Events\Tests\Unit;

use Emailexpert\Events\Api\HeySummitClient;
use Emailexpert\Events\Logging\Logger;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class HeySummitClientTest extends TestCase {

	private const KEY = 'sk_test_super_secret_key_9876';

	/** @var array<int, array{url: string, args: array}> */
	private array $requests = array();

	protected function setUp(): void {
		parent::setUp();
		eex_tests_reset_hooks_and_options();
		$this->requests = array();
		// No sleeping between retries in tests.
		add_filter( 'eex_client_retry_delay', static fn () => 0 );
	}

	/**
	 * Build a client whose transport replays $responses in order (or, for a
	 * callable, computes the response from the URL).
	 *
	 * @param array|callable $responses Queue of responses or URL router.
	 */
	private function client( $responses, ?Logger $logger = null ): HeySummitClient {
		$queue     = is_array( $responses ) ? $responses : null;
		$transport = function ( string $url, array $args ) use ( &$queue, $responses ) {
			$this->requests[] = array(
				'url'  => $url,
				'args' => $args,
			);
			if ( null !== $queue ) {
				$this->assertNotEmpty( $queue, 'Transport called more times than responses queued.' );
				return array_shift( $queue );
			}
			return $responses( $url, $args );
		};

		return new HeySummitClient( self::KEY, $logger, array( 'transport' => $transport ) );
	}

	private static function response( int $code, array $body ): array {
		return array(
			'response' => array( 'code' => $code ),
			'body'     => json_encode( $body ),
		);
	}

	public function test_get_returns_decoded_body_and_sends_token_header(): void {
		$client = $this->client( array( self::response( 200, array( 'count' => 1 ) ) ) );

		$result = $client->get( 'events/', array( 'page' => 2 ) );

		$this->assertSame( array( 'count' => 1 ), $result );
		$this->assertCount( 1, $this->requests );
		$this->assertSame( 'https://app.heysummit.com/api/v2/events/?page=2', $this->requests[0]['url'] );
		$this->assertSame( 'Token ' . self::KEY, $this->requests[0]['args']['headers']['Authorization'] );
		$this->assertSame( 15, $this->requests[0]['args']['timeout'] );
	}

	public function test_get_all_follows_pagination_until_exhausted(): void {
		$base   = 'https://app.heysummit.com/api/v2/talks/';
		$client = $this->client(
			array(
				self::response(
					200,
					array(
						'count'   => 5,
						'next'    => $base . '?page=2',
						'results' => array( array( 'id' => 1 ), array( 'id' => 2 ) ),
					)
				),
				self::response(
					200,
					array(
						'count'   => 5,
						'next'    => $base . '?page=3',
						'results' => array( array( 'id' => 3 ), array( 'id' => 4 ) ),
					)
				),
				self::response(
					200,
					array(
						'count'   => 5,
						'next'    => null,
						'results' => array( array( 'id' => 5 ) ),
					)
				),
			)
		);

		$results = $client->get_all( 'talks/' );

		$this->assertSame( array( 1, 2, 3, 4, 5 ), array_column( $results, 'id' ) );
		$this->assertCount( 3, $this->requests );
		$this->assertSame( $base . '?page=2', $this->requests[1]['url'] );
	}

	public function test_get_all_respects_page_cap_filter(): void {
		add_filter( 'eex_client_max_pages', static fn () => 2 );

		$page   = static fn ( int $n ): array => self::response(
			200,
			array(
				'count'   => 100,
				'next'    => 'https://app.heysummit.com/api/v2/talks/?page=' . ( $n + 1 ),
				'results' => array( array( 'id' => $n ) ),
			)
		);
		$client = $this->client( array( $page( 1 ), $page( 2 ), $page( 3 ) ) );

		$results = $client->get_all( 'talks/' );

		$this->assertCount( 2, $results, 'Cap of 2 pages must stop the crawl.' );
		$this->assertCount( 2, $this->requests );
	}

	public function test_retries_twice_on_5xx_then_succeeds(): void {
		$client = $this->client(
			array(
				self::response( 500, array() ),
				self::response( 502, array() ),
				self::response( 200, array( 'ok' => true ) ),
			)
		);

		$result = $client->get( 'events/' );

		$this->assertSame( array( 'ok' => true ), $result );
		$this->assertCount( 3, $this->requests );
	}

	public function test_gives_up_after_two_retries_on_5xx(): void {
		$client = $this->client(
			array(
				self::response( 500, array() ),
				self::response( 500, array() ),
				self::response( 500, array() ),
			)
		);

		$result = $client->get( 'events/' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'eex_http_500', $result->get_error_code() );
		$this->assertCount( 3, $this->requests, 'Initial attempt plus exactly two retries.' );
	}

	public function test_retries_on_network_error(): void {
		$client = $this->client(
			array(
				new WP_Error( 'http_request_failed', 'cURL error 28: timed out' ),
				self::response( 200, array( 'ok' => true ) ),
			)
		);

		$result = $client->get( 'events/' );

		$this->assertSame( array( 'ok' => true ), $result );
		$this->assertCount( 2, $this->requests );
	}

	public function test_does_not_retry_on_4xx(): void {
		$client = $this->client( array( self::response( 404, array() ) ) );

		$result = $client->get( 'events/999/' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'eex_http_404', $result->get_error_code() );
		$this->assertCount( 1, $this->requests, '4xx must not be retried.' );
	}

	public function test_auth_failure_returns_eex_auth_without_retry(): void {
		foreach ( array( 401, 403 ) as $status ) {
			$this->requests = array();
			$client         = $this->client( array( self::response( $status, array() ) ) );

			$result = $client->get( 'events/' );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( HeySummitClient::ERROR_AUTH, $result->get_error_code() );
			$this->assertCount( 1, $this->requests );
		}
	}

	public function test_rejects_next_url_on_foreign_host(): void {
		$client = $this->client(
			array(
				self::response(
					200,
					array(
						'count'   => 2,
						'next'    => 'https://evil.example.com/api/v2/talks/?page=2',
						'results' => array( array( 'id' => 1 ) ),
					)
				),
			)
		);

		$result = $client->get_all( 'talks/' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'eex_bad_next_url', $result->get_error_code() );
		$this->assertCount( 1, $this->requests, 'The foreign next URL must never be requested.' );
	}

	public function test_get_all_returns_non_paginated_body_unchanged(): void {
		$body   = array(
			'id'    => 42,
			'title' => 'FORUM London',
		);
		$client = $this->client( array( self::response( 200, $body ) ) );

		$this->assertSame( $body, $client->get_all( 'events/42/' ) );
	}

	public function test_invalid_json_is_an_error(): void {
		$client = $this->client(
			array(
				array(
					'response' => array( 'code' => 200 ),
					'body'     => '<html>maintenance</html>',
				),
			)
		);

		$result = $client->get( 'events/' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'eex_bad_json', $result->get_error_code() );
	}

	public function test_logs_outcomes_but_never_the_api_key(): void {
		$logger = new Logger();
		$client = $this->client(
			array(
				self::response( 500, array() ),
				self::response(
					200,
					array(
						'count'   => 0,
						'next'    => null,
						'results' => array(),
					)
				),
			),
			$logger
		);

		$client->get_all( 'events/', array( 'page_size' => 10 ) );

		$entries = $logger->entries();
		$this->assertCount( 2, $entries, 'One entry per request outcome.' );
		$this->assertSame( 'api', $entries[0]['context'] );
		$this->assertSame( 500, $entries[0]['data']['status'] );
		$this->assertSame( 200, $entries[1]['data']['status'] );
		$this->assertArrayHasKey( 'duration_ms', $entries[1]['data'] );
		$this->assertStringContainsString( '/api/v2/events/', $entries[1]['data']['endpoint'] );

		$serialised = json_encode( $entries );
		$this->assertStringNotContainsString( self::KEY, $serialised, 'The API key must never appear in logs.' );
		$this->assertStringNotContainsString( substr( self::KEY, -8 ), $serialised );
	}
}
