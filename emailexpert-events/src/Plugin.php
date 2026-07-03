<?php
/**
 * Main plugin container.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events;

use Emailexpert\Events\Admin\Notices;
use Emailexpert\Events\Admin\SettingsPage;
use Emailexpert\Events\Api\Connections;
use Emailexpert\Events\Cli\Commands;
use Emailexpert\Events\Logging\Logger;

/**
 * Wires plugin services together. Milestone 1 registers the settings page,
 * connections store, API client plumbing and WP-CLI discovery commands.
 * Later milestones (data layer, sync engine, display, webhooks) attach here.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

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
	 * Get the shared plugin instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor: use instance().
	 */
	private function __construct() {
		$this->logger      = new Logger();
		$this->connections = new Connections();
	}

	/**
	 * Attach all hooks. Runs on plugins_loaded.
	 */
	public function init(): void {
		if ( is_admin() ) {
			$notices = new Notices();
			$notices->register();

			$settings = new SettingsPage( $this->connections, $this->logger, $notices );
			$settings->register();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			Commands::register( $this->connections, $this->logger );
		}
	}

	/**
	 * Connections store accessor.
	 */
	public function connections(): Connections {
		return $this->connections;
	}

	/**
	 * Logger accessor.
	 */
	public function logger(): Logger {
		return $this->logger;
	}

	/**
	 * Activation: generate the webhook secret once (SPEC 8.1) and seed options.
	 */
	public static function activate(): void {
		if ( ! get_option( 'eex_webhook_secret' ) ) {
			update_option( 'eex_webhook_secret', wp_generate_password( 40, false, false ), false );
		}
		if ( false === get_option( 'eex_connections', false ) ) {
			add_option( 'eex_connections', array(), '', false );
		}
	}

	/**
	 * Deactivation: clear scheduled events (sync cron arrives in M3; clearing is safe now).
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'eex_sync_cron' );
	}
}
