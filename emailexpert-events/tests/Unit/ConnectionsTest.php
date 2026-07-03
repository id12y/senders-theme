<?php
/**
 * Unit tests for the Connections store.
 */

declare( strict_types=1 );

namespace Emailexpert\Events\Tests\Unit;

use Emailexpert\Events\Api\Connections;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class ConnectionsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		eex_tests_reset_hooks_and_options();
	}

	public function test_save_and_read_round_trip(): void {
		$connections = new Connections();

		$saved = $connections->save(
			array(
				array(
					'id'      => '',
					'label'   => 'Member Hub',
					'api_key' => 'key_hub_1234',
				),
			)
		);

		$this->assertCount( 1, $saved );
		$this->assertSame( 'Member Hub', $saved[0]['label'] );
		$this->assertSame( 'key_hub_1234', $saved[0]['api_key'] );
		$this->assertNotSame( '', $saved[0]['id'], 'A connection ID is generated when missing.' );

		$this->assertSame( $saved, $connections->all() );
		$this->assertSame( $saved[0], $connections->get( $saved[0]['id'] ) );
		$this->assertSame( $saved[0], $connections->first() );
	}

	public function test_blank_submitted_key_keeps_stored_key(): void {
		$connections = new Connections();
		$saved       = $connections->save(
			array(
				array(
					'id'      => '',
					'label'   => 'Member Hub',
					'api_key' => 'key_original',
				),
			)
		);

		// Re-save the same row with an empty key, as the write-only UI does.
		$resaved = $connections->save(
			array(
				array(
					'id'      => $saved[0]['id'],
					'label'   => 'Member Hub renamed',
					'api_key' => '',
				),
			)
		);

		$this->assertSame( 'key_original', $resaved[0]['api_key'] );
		$this->assertSame( 'Member Hub renamed', $resaved[0]['label'] );
	}

	public function test_empty_rows_are_dropped(): void {
		$connections = new Connections();

		$saved = $connections->save(
			array(
				array(
					'id'      => '',
					'label'   => '',
					'api_key' => '',
				),
				array(
					'id'      => '',
					'label'   => 'Real one',
					'api_key' => 'key_x',
				),
			)
		);

		$this->assertCount( 1, $saved );
		$this->assertSame( 'Real one', $saved[0]['label'] );
	}

	public function test_mask_key_shows_last_four_only(): void {
		$this->assertSame( '•••• 5678', Connections::mask_key( 'sk_live_12345678' ) );
		$this->assertSame( '', Connections::mask_key( '' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_constant_overrides_first_connection_key(): void {
		define( 'EEX_HEYSUMMIT_API_KEY', 'key_from_wp_config' );

		$connections = new Connections();

		$this->assertTrue( $connections->has_constant_key() );

		// With nothing stored, a synthetic first connection appears.
		$all = $connections->all();
		$this->assertCount( 1, $all );
		$this->assertSame( 'key_from_wp_config', $all[0]['api_key'] );

		// The constant key is never written to the option store.
		$connections->save(
			array(
				array(
					'id'      => $all[0]['id'],
					'label'   => 'Primary',
					'api_key' => 'attempted_override',
				),
			)
		);
		$stored = get_option( Connections::OPTION );
		$this->assertStringNotContainsString( 'key_from_wp_config', json_encode( $stored ) );
		$this->assertNotSame( 'attempted_override', $stored[0]['api_key'] );

		// Reads still surface the constant for the first connection.
		$this->assertSame( 'key_from_wp_config', $connections->first()['api_key'] );
	}
}
