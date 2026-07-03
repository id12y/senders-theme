<?php
/**
 * Minimal PSR-4 autoloader for the Emailexpert\Events namespace.
 *
 * No Composer requirement at runtime (see SPEC section 12).
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events;

/**
 * PSR-4 style autoloader mapping Emailexpert\Events\ to src/.
 */
final class Autoloader {

	/**
	 * Namespace prefix handled by this autoloader.
	 */
	private const PREFIX = 'Emailexpert\\Events\\';

	/**
	 * Register the autoloader with SPL.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	/**
	 * Load a class file for a class in our namespace.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	public static function autoload( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$path     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
