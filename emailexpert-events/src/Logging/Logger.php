<?php
/**
 * Lightweight logging facade.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Logging;

/**
 * Logging facade for the plugin.
 *
 * Milestone 1 ships the facade only: entries are exposed through the
 * `eex_log` action and kept in an in-memory buffer for the current request
 * (used by WP-CLI output and tests). Milestone 2 adds the persistent
 * {$wpdb->prefix}eex_log table as a subscriber to the same action, so no
 * call sites change.
 *
 * API keys must never reach this class: callers log endpoint, status and
 * duration only (SPEC 3.3).
 */
class Logger {

	public const CONTEXT_SYNC    = 'sync';
	public const CONTEXT_WEBHOOK = 'webhook';
	public const CONTEXT_API     = 'api';

	public const LEVEL_INFO    = 'info';
	public const LEVEL_WARNING = 'warning';
	public const LEVEL_ERROR   = 'error';

	/**
	 * In-memory entries for the current request.
	 *
	 * @var array<int, array{context: string, level: string, message: string, data: array}>
	 */
	private array $entries = array();

	/**
	 * Record a log entry.
	 *
	 * @param string $context One of the CONTEXT_* constants.
	 * @param string $level   One of the LEVEL_* constants.
	 * @param string $message Human-readable message (British English).
	 * @param array  $data    Structured context data. Never include credentials.
	 */
	public function log( string $context, string $level, string $message, array $data = array() ): void {
		$entry = array(
			'context' => $context,
			'level'   => $level,
			'message' => $message,
			'data'    => $data,
		);

		$this->entries[] = $entry;

		/**
		 * Fires for every log entry. The persistent log table (M2) and any
		 * external observers subscribe here.
		 *
		 * @param string $context Log context (sync|webhook|api).
		 * @param string $level   Severity (info|warning|error).
		 * @param string $message Message text.
		 * @param array  $data    Structured data.
		 */
		do_action( 'eex_log', $context, $level, $message, $data );
	}

	/**
	 * Entries recorded during the current request.
	 *
	 * @return array<int, array{context: string, level: string, message: string, data: array}>
	 */
	public function entries(): array {
		return $this->entries;
	}
}
