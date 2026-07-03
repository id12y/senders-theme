<?php
/**
 * WP-CLI commands.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Cli;

use Emailexpert\Events\Api\Connections;
use Emailexpert\Events\Api\Discovery;
use Emailexpert\Events\Api\HeySummitClient;
use Emailexpert\Events\Logging\Logger;
use WP_CLI;

/**
 * `wp eex` commands. Milestone 1 ships discovery and connection testing;
 * `wp eex sync`, `status` and `orphans` arrive with the sync engine (M3).
 */
class Commands {

	/**
	 * Connections store.
	 *
	 * @var Connections
	 */
	private Connections $connections;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Register commands with WP-CLI.
	 *
	 * @param Connections $connections Connections store.
	 * @param Logger      $logger      Logger.
	 */
	public static function register( Connections $connections, Logger $logger ): void {
		$instance              = new self();
		$instance->connections = $connections;
		$instance->logger      = $logger;

		WP_CLI::add_command( 'eex discover', array( $instance, 'discover' ) );
		WP_CLI::add_command( 'eex test-connection', array( $instance, 'test_connection' ) );
	}

	/**
	 * Run read-only discovery against the HeySummit v2 API and print a
	 * Markdown report for docs/api-notes.md.
	 *
	 * ## OPTIONS
	 *
	 * [--connection=<id>]
	 * : Connection ID to use. Defaults to the first configured connection.
	 *
	 * [--output=<file>]
	 * : Also write the report to this file path.
	 *
	 * ## EXAMPLES
	 *
	 *     wp eex discover
	 *     wp eex discover --output=wp-content/plugins/emailexpert-events/docs/api-notes-discovery.md
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args.
	 */
	public function discover( array $args, array $assoc_args ): void {
		$connection = $this->resolve_connection( $assoc_args );

		// CLI runs should not sleep between retries longer than needed,
		// but keep the production backoff: discovery hits live data.
		$client    = new HeySummitClient( $connection['api_key'], $this->logger );
		$discovery = new Discovery( $client );

		WP_CLI::log( sprintf( 'Running read-only discovery using connection "%s"…', $connection['label'] ) );

		$report = $discovery->run();
		if ( is_wp_error( $report ) ) {
			WP_CLI::error( $report->get_error_message() );
		}

		$markdown = Discovery::to_markdown( $report, gmdate( 'c' ) );
		WP_CLI::log( $markdown );

		if ( ! empty( $assoc_args['output'] ) ) {
			$path    = (string) $assoc_args['output'];
			$written = file_put_contents( $path, $markdown, FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI context, developer-owned path.
			if ( false === $written ) {
				WP_CLI::warning( sprintf( 'Could not write to %s.', $path ) );
			} else {
				WP_CLI::success( sprintf( 'Report appended to %s.', $path ) );
			}
		}

		WP_CLI::success( 'Discovery complete. Fold the findings into docs/api-notes.md before building the mappers (M2).' );
	}

	/**
	 * Test a HeySummit connection by calling events/.
	 *
	 * ## OPTIONS
	 *
	 * [--connection=<id>]
	 * : Connection ID to test. Defaults to the first configured connection.
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args.
	 */
	public function test_connection( array $args, array $assoc_args ): void {
		$connection = $this->resolve_connection( $assoc_args );
		$client     = new HeySummitClient( $connection['api_key'], $this->logger );
		$response   = $client->get( 'events/' );

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( $response->get_error_message() );
		}

		WP_CLI::success(
			sprintf(
				'Connected as "%s". %d event(s) visible to this key.',
				$connection['label'],
				(int) ( $response['count'] ?? count( $response['results'] ?? array() ) )
			)
		);
	}

	/**
	 * Resolve the connection to use from --connection or default to first.
	 *
	 * @param array $assoc_args Associative CLI args.
	 * @return array{id: string, label: string, api_key: string}
	 */
	private function resolve_connection( array $assoc_args ): array {
		$connection = ! empty( $assoc_args['connection'] )
			? $this->connections->get( (string) $assoc_args['connection'] )
			: $this->connections->first();

		if ( ! $connection || '' === $connection['api_key'] ) {
			WP_CLI::error( 'No connection with an API key found. Add one under Settings → emailexpert Events, or define EEX_HEYSUMMIT_API_KEY in wp-config.php.' );
		}

		return $connection;
	}
}
