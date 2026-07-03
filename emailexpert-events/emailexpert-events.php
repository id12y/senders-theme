<?php
/**
 * Plugin Name:       emailexpert Events
 * Plugin URI:        https://emailexpert.com/
 * Description:       Syncs HeySummit event data into WordPress and renders indexable event, session and speaker content with full Schema.org markup.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            emailexpert UK Ltd
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       emailexpert-events
 *
 * @package Emailexpert\Events
 */

defined( 'ABSPATH' ) || exit;

define( 'EEX_VERSION', '0.1.0' );
define( 'EEX_PLUGIN_FILE', __FILE__ );
define( 'EEX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EEX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Hard requirement: PHP 8.1+. Bail with a notice rather than fataling on older PHP.
if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'emailexpert Events requires PHP 8.1 or newer. The plugin is inactive.', 'emailexpert-events' )
			);
		}
	);
	return;
}

require_once EEX_PLUGIN_DIR . 'src/Autoloader.php';
\Emailexpert\Events\Autoloader::register();

register_activation_hook( __FILE__, array( \Emailexpert\Events\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \Emailexpert\Events\Plugin::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Emailexpert\Events\Plugin::instance()->init();
	}
);
