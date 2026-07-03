<?php
/**
 * Unit tests for the read-only discovery prober.
 */

declare( strict_types=1 );

namespace Emailexpert\Events\Tests\Unit;

use Emailexpert\Events\Api\Discovery;
use Emailexpert\Events\Api\HeySummitClient;
use PHPUnit\Framework\TestCase;

final class DiscoveryTest extends TestCase {

	/** @var string[] */
	private array $urls = array();

	protected function setUp(): void {
		parent::setUp();
		eex_tests_reset_hooks_and_options();
		$this->urls = array();
	}

	private function client(): HeySummitClient {
		$transport = function ( string $url, array $args ) {
			$this->urls[] = $url;
			$path         = (string) parse_url( $url, PHP_URL_PATH );

			$respond = static fn ( array $body ): array => array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $body ),
			);

			if ( str_ends_with( $path, '/api/v2/events/' ) ) {
				return $respond(
					array(
						'count'   => 1,
						'next'    => null,
						'results' => array(
							array(
								'id'           => 77,
								'title'        => 'Member Hub',
								'is_evergreen' => true,
								'event_url'    => 'https://hub.emailexpert.org',
							),
						),
					)
				);
			}
			if ( str_ends_with( $path, '/api/v2/events/77/' ) ) {
				return $respond(
					array(
						'id'                        => 77,
						'title'                     => 'Member Hub',
						'is_open_for_registrations' => true,
					)
				);
			}
			if ( str_ends_with( $path, '/api/v2/talks/' ) ) {
				return $respond(
					array(
						'count'   => 2,
						'next'    => null,
						'results' => array(
							array(
								'id'         => 501,
								'title'      => 'Deliverability in 2026',
								'replay_url' => 'https://youtu.be/x',
							),
						),
					)
				);
			}
			if ( str_ends_with( $path, '/api/v2/attendees/' ) ) {
				return $respond(
					array(
						'count'   => 1,
						'next'    => null,
						'results' => array(
							array(
								'id'    => 9,
								'email' => 'person@example.com',
							),
						),
					)
				);
			}

			// Everything else: empty collection or empty detail.
			if ( preg_match( '~/api/v2/[a-z]+/\d+/$~', $path ) ) {
				return $respond( array( 'id' => 1 ) );
			}
			return $respond(
				array(
					'count'   => 0,
					'next'    => null,
					'results' => array(),
				)
			);
		};

		return new HeySummitClient( 'test_key', null, array( 'transport' => $transport ) );
	}

	public function test_run_probes_are_get_only_and_report_is_complete(): void {
		$discovery = new Discovery( $this->client() );
		$report    = $discovery->run();

		$this->assertIsArray( $report );
		$this->assertArrayHasKey( 'events/', $report['endpoints'] );
		$this->assertArrayHasKey( 'talks/', $report['endpoints'] );
		$this->assertArrayHasKey( 'speakers/', $report['endpoints'] );
		$this->assertArrayHasKey( 'categories/', $report['endpoints'] );
		$this->assertArrayHasKey( 'attendees/', $report['endpoints'] );

		// Event filter candidates were probed on talks.
		$this->assertArrayHasKey( 'event', $report['endpoints']['talks/']['event_filter'] );
		$this->assertArrayHasKey( 'event_id', $report['endpoints']['talks/']['event_filter'] );

		// Replay-shaped field on talks was spotted.
		$notes = implode( "\n", $report['notes'] );
		$this->assertStringContainsString( 'replay_url', $notes );
	}

	public function test_attendee_emails_are_redacted_in_report(): void {
		$discovery = new Discovery( $this->client() );
		$report    = $discovery->run();

		$this->assertStringNotContainsString(
			'person@example.com',
			json_encode( $report ),
			'Attendee emails must never land in the discovery report.'
		);
	}

	public function test_markdown_rendering(): void {
		$discovery = new Discovery( $this->client() );
		$report    = $discovery->run();

		$markdown = Discovery::to_markdown( $report, '2026-07-03T00:00:00Z' );

		$this->assertStringContainsString( '## Live discovery run (2026-07-03T00:00:00Z)', $markdown );
		$this->assertStringContainsString( '### `events/`', $markdown );
		$this->assertStringContainsString( '`title`', $markdown );
	}
}
