<?php
/**
 * Settings page: Settings → emailexpert Events.
 *
 * @package Emailexpert\Events
 */

namespace Emailexpert\Events\Admin;

use Emailexpert\Events\Api\Connections;
use Emailexpert\Events\Api\HeySummitClient;
use Emailexpert\Events\Logging\Logger;

/**
 * Registers the settings screen (SPEC 5).
 *
 * Milestone 1 ships the API section: the connections list with write-only
 * keys, the wp-config constant override, and the per-connection
 * "Test connection" button. The Sync, Webhooks and Display sections land
 * with their respective milestones (M3, M6, M4).
 */
class SettingsPage {

	public const PAGE_SLUG    = 'emailexpert-events';
	public const NONCE_ACTION = 'eex_settings';

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
	 * Notices service.
	 *
	 * @var Notices
	 */
	private Notices $notices;

	/**
	 * Constructor.
	 *
	 * @param Connections $connections Connections store.
	 * @param Logger      $logger      Logger.
	 * @param Notices     $notices     Notices service.
	 */
	public function __construct( Connections $connections, Logger $logger, Notices $notices ) {
		$this->connections = $connections;
		$this->logger      = $logger;
		$this->notices     = $notices;
	}

	/**
	 * Attach hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_eex_save_settings', array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_eex_test_connection', array( $this, 'handle_test_connection' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the options page.
	 */
	public function add_menu(): void {
		add_options_page(
			__( 'emailexpert Events', 'emailexpert-events' ),
			__( 'emailexpert Events', 'emailexpert-events' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue the settings screen script on our page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'eex-admin-settings',
			EEX_PLUGIN_URL . 'assets/admin/settings.js',
			array(),
			EEX_VERSION,
			true
		);

		wp_localize_script(
			'eex-admin-settings',
			'eexSettings',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'testNonce' => wp_create_nonce( 'eex_test_connection' ),
				'i18n'      => array(
					'testing'   => __( 'Testing…', 'emailexpert-events' ),
					'testError' => __( 'Test failed. Please try again.', 'emailexpert-events' ),
				),
			)
		);
	}

	/**
	 * Render the settings screen.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'emailexpert-events' ) );
		}

		$connections  = $this->connections->all();
		$has_constant = $this->connections->has_constant_key();
		$saved        = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'emailexpert Events', 'emailexpert-events' ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'emailexpert-events' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="eex_save_settings" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<h2><?php esc_html_e( 'API connections', 'emailexpert-events' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Each HeySummit account you sync from is a connection. With a single account, one connection is all you need.', 'emailexpert-events' ); ?>
				</p>

				<table class="widefat striped" id="eex-connections" style="max-width: 900px; margin-top: 12px;">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Label', 'emailexpert-events' ); ?></th>
							<th scope="col"><?php esc_html_e( 'API key', 'emailexpert-events' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'emailexpert-events' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( empty( $connections ) ) {
							$connections = array(
								array(
									'id'      => '',
									'label'   => '',
									'api_key' => '',
								),
							);
						}
						foreach ( $connections as $index => $connection ) :
							$is_constant_row = ( 0 === $index && $has_constant );
							?>
							<tr class="eex-connection-row">
								<td>
									<input type="hidden" name="eex_connections[<?php echo (int) $index; ?>][id]" value="<?php echo esc_attr( $connection['id'] ); ?>" />
									<label class="screen-reader-text" for="eex-label-<?php echo (int) $index; ?>"><?php esc_html_e( 'Connection label', 'emailexpert-events' ); ?></label>
									<input type="text" class="regular-text" id="eex-label-<?php echo (int) $index; ?>"
										name="eex_connections[<?php echo (int) $index; ?>][label]"
										value="<?php echo esc_attr( $connection['label'] ); ?>"
										placeholder="<?php esc_attr_e( 'e.g. Member Hub', 'emailexpert-events' ); ?>" />
								</td>
								<td>
									<label class="screen-reader-text" for="eex-key-<?php echo (int) $index; ?>"><?php esc_html_e( 'API key', 'emailexpert-events' ); ?></label>
									<?php if ( $is_constant_row ) : ?>
										<input type="password" class="regular-text" id="eex-key-<?php echo (int) $index; ?>" value="" disabled
											placeholder="<?php echo esc_attr( Connections::mask_key( $connection['api_key'] ) ); ?>" autocomplete="off" />
										<p class="description"><?php esc_html_e( 'Defined by the EEX_HEYSUMMIT_API_KEY constant in wp-config.php and cannot be edited here.', 'emailexpert-events' ); ?></p>
									<?php else : ?>
										<input type="password" class="regular-text" id="eex-key-<?php echo (int) $index; ?>"
											name="eex_connections[<?php echo (int) $index; ?>][api_key]" value="" autocomplete="new-password"
											placeholder="<?php echo esc_attr( '' !== $connection['api_key'] ? Connections::mask_key( $connection['api_key'] ) : __( 'Paste your HeySummit API key', 'emailexpert-events' ) ); ?>" />
										<?php if ( '' !== $connection['api_key'] ) : ?>
											<p class="description"><?php esc_html_e( 'A key is stored. Leave blank to keep it; enter a new key to replace it.', 'emailexpert-events' ); ?></p>
										<?php endif; ?>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( '' !== $connection['id'] && '' !== $connection['api_key'] ) : ?>
										<button type="button" class="button eex-test-connection" data-connection="<?php echo esc_attr( $connection['id'] ); ?>">
											<?php esc_html_e( 'Test connection', 'emailexpert-events' ); ?>
										</button>
										<span class="eex-test-result" role="status" aria-live="polite"></span>
									<?php endif; ?>
									<?php if ( $index > 0 ) : ?>
										<button type="button" class="button-link-delete eex-remove-connection"><?php esc_html_e( 'Remove', 'emailexpert-events' ); ?></button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<button type="button" class="button" id="eex-add-connection"><?php esc_html_e( 'Add connection', 'emailexpert-events' ); ?></button>
				</p>

				<h2><?php esc_html_e( 'Sync', 'emailexpert-events' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Event selection and sync settings become available once the data layer milestone is in place.', 'emailexpert-events' ); ?></p>

				<?php submit_button( __( 'Save settings', 'emailexpert-events' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Persist submitted settings (admin-post handler).
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'emailexpert-events' ) );
		}
		check_admin_referer( self::NONCE_ACTION );

		$submitted = array();
		if ( isset( $_POST['eex_connections'] ) && is_array( $_POST['eex_connections'] ) ) {
			$submitted = map_deep( wp_unslash( $_POST['eex_connections'] ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised via map_deep here and per-field in Connections::save().
		}

		$this->connections->save( array_values( $submitted ) );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&updated=1' ) );
		exit;
	}

	/**
	 * AJAX: test a connection by calling events/ (SPEC 5.1).
	 */
	public function handle_test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'emailexpert-events' ) ), 403 );
		}
		check_ajax_referer( 'eex_test_connection' );

		$connection_id = isset( $_POST['connection'] ) ? sanitize_key( wp_unslash( $_POST['connection'] ) ) : '';
		$connection    = $this->connections->get( $connection_id );

		if ( ! $connection || '' === $connection['api_key'] ) {
			wp_send_json_error( array( 'message' => __( 'Connection not found or no key stored. Save settings first.', 'emailexpert-events' ) ), 400 );
		}

		$client   = new HeySummitClient( $connection['api_key'], $this->logger );
		$response = $client->get( 'events/' );

		if ( is_wp_error( $response ) ) {
			if ( HeySummitClient::ERROR_AUTH === $response->get_error_code() ) {
				$this->notices->flag_auth_failure( $connection['id'], $connection['label'] );
			}
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$this->notices->clear_auth_failure( $connection['id'] );

		$count = isset( $response['count'] ) ? (int) $response['count'] : count( $response['results'] ?? array() );
		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of events visible to the key. */
					_n( 'Connected. %d event visible to this key.', 'Connected. %d events visible to this key.', $count, 'emailexpert-events' ),
					$count
				),
			)
		);
	}
}
