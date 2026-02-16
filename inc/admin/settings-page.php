<?php
/**
 * Site Settings — Admin Settings Page
 *
 * Adds 5 tabs (Branding, Event Defaults, Ticketing Defaults, Design Defaults,
 * Integrations) to the existing Theme Settings page via filter/action hooks.
 *
 * All fields stored in a single option: ss_site_settings
 * Sanitisation is schema-driven via ss_site_settings_schema().
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   SETTINGS API REGISTRATION
   ========================================================================= */

function ss_site_register_settings() {
	register_setting( 'ss_site_settings_group', 'ss_site_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'ss_site_sanitize_settings',
		'default'           => array(),
	) );
}
add_action( 'admin_init', 'ss_site_register_settings' );

/* =========================================================================
   SCHEMA-DRIVEN SANITISATION
   ========================================================================= */

/**
 * Sanitise submitted site settings.
 *
 * Only keys present in the schema are accepted.  Submitted keys overwrite
 * existing saved values (even if empty — so admins can clear a field).
 * Non-submitted keys are preserved (tab isolation).
 *
 * @param mixed $input Raw form input.
 * @return array Sanitised settings merged with existing.
 */
function ss_site_sanitize_settings( $input ) {
	$existing = get_option( 'ss_site_settings', array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	if ( ! is_array( $input ) ) {
		return $existing;
	}

	/* Explicitly unslash — do not rely on WP's implicit handling. */
	$input  = wp_unslash( $input );
	$schema = ss_site_settings_schema();
	$clean  = array();

	foreach ( $input as $key => $val ) {
		if ( ! isset( $schema[ $key ] ) ) {
			continue; /* Reject unknown keys. */
		}
		$type = $schema[ $key ]['type'];
		switch ( $type ) {
			case 'text':
				$clean[ $key ] = sanitize_text_field( $val );
				break;
			case 'email':
				$clean[ $key ] = sanitize_email( $val );
				break;
			case 'url':
				$clean[ $key ] = esc_url_raw( $val );
				break;
			case 'wysiwyg':
			case 'inline_html':
				$clean[ $key ] = wp_kses_post( $val );
				break;
			case 'int':
				$clean[ $key ] = absint( $val );
				break;
			case 'date':
				$val = sanitize_text_field( $val );
				if ( '' !== $val ) {
					$dt = DateTime::createFromFormat( 'Y-m-d', $val );
					$clean[ $key ] = ( $dt && $dt->format( 'Y-m-d' ) === $val ) ? $val : '';
				} else {
					$clean[ $key ] = '';
				}
				break;
			case 'hex':
				$val = sanitize_text_field( $val );
				$clean[ $key ] = preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $val ) ? $val : '';
				break;
			case 'select':
				$allowed = isset( $schema[ $key ]['allowed'] ) ? $schema[ $key ]['allowed'] : array();
				$clean[ $key ] = in_array( $val, $allowed, true ) ? $val : $schema[ $key ]['default'];
				break;
			case 'checkbox':
				$clean[ $key ] = ! empty( $val ) ? 1 : 0;
				break;
			default:
				$clean[ $key ] = sanitize_text_field( $val );
		}
	}

	/* Handle unchecked checkboxes: if a checkbox key was NOT submitted
	   but the current tab includes that key, it means it was unchecked. */
	$submitted_tab = '';
	if ( isset( $input['_ss_current_tab'] ) ) {
		$submitted_tab = sanitize_key( $input['_ss_current_tab'] );
	}
	if ( $submitted_tab ) {
		foreach ( $schema as $key => $field ) {
			if ( 'checkbox' === $field['type']
				&& isset( $field['tab'] ) && $field['tab'] === $submitted_tab
				&& ! array_key_exists( $key, $clean ) ) {
				$clean[ $key ] = 0;
			}
		}
	}

	/* Always persist schema version. */
	$clean['_schema_version'] = 1;

	return array_merge( $existing, $clean );
}

/* =========================================================================
   TAB REGISTRATION
   ========================================================================= */

/**
 * Append 5 site-settings tabs to the admin settings page.
 */
function ss_site_add_tabs( $tabs ) {
	$tabs['site_branding']     = __( 'Branding', 'sender-symposium' );
	$tabs['site_event']        = __( 'Event Defaults', 'sender-symposium' );
	$tabs['site_ticketing']    = __( 'Ticketing Defaults', 'sender-symposium' );
	$tabs['site_design']       = __( 'Design Defaults', 'sender-symposium' );
	$tabs['site_integrations'] = __( 'Integrations', 'sender-symposium' );
	return $tabs;
}
add_filter( 'ss_settings_tabs', 'ss_site_add_tabs' );

/**
 * Dispatch tab rendering to the correct function.
 */
function ss_site_render_tab( $tab ) {
	$renderers = array(
		'site_branding'     => 'ss_site_render_tab_branding',
		'site_event'        => 'ss_site_render_tab_event',
		'site_ticketing'    => 'ss_site_render_tab_ticketing',
		'site_design'       => 'ss_site_render_tab_design',
		'site_integrations' => 'ss_site_render_tab_integrations',
	);
	if ( isset( $renderers[ $tab ] ) && function_exists( $renderers[ $tab ] ) ) {
		call_user_func( $renderers[ $tab ] );
	}
}
add_action( 'ss_render_settings_tab', 'ss_site_render_tab' );

/* =========================================================================
   FIELD RENDERERS (schema-aware helpers)
   ========================================================================= */

/**
 * Open a tab form.
 */
function ss_site_form_open( $tab_slug ) {
	?>
	<form action="options.php" method="post">
		<?php settings_fields( 'ss_site_settings_group' ); ?>
		<input type="hidden" name="ss_site_settings[_ss_current_tab]" value="<?php echo esc_attr( $tab_slug ); ?>" />
		<p class="description" style="margin-bottom:16px;">
			<?php esc_html_e( 'These values act as site-wide defaults. Per-page settings override them.', 'sender-symposium' ); ?>
		</p>
	<?php
}

/**
 * Close a tab form.
 */
function ss_site_form_close() {
	submit_button();
	echo '</form>';
}

/**
 * Render a text field row.
 */
function ss_site_render_text( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	$placeholder = isset( $field['default'] ) && '' !== $field['default'] ? $field['default'] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="text"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render an inline HTML field row (text input, HTML allowed).
 */
function ss_site_render_inline_html( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	$placeholder = isset( $field['default'] ) && '' !== $field['default'] ? $field['default'] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="text"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				class="large-text"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render an email field row.
 */
function ss_site_render_email( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="email"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
				placeholder="you@example.com"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a URL field row.
 */
function ss_site_render_url( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="url"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_url( $value ); ?>"
				class="regular-text"
				placeholder="https://"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a date field row.
 */
function ss_site_render_date( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="date"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render an integer field row.
 */
function ss_site_render_int( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="number"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				class="small-text"
				min="0"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a select field row.
 */
function ss_site_render_select( $key, $s, $schema ) {
	$field   = $schema[ $key ];
	$value   = isset( $s[ $key ] ) ? $s[ $key ] : $field['default'];
	$allowed = isset( $field['allowed'] ) ? $field['allowed'] : array();
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<select id="ss_site_<?php echo esc_attr( $key ); ?>" name="ss_site_settings[<?php echo esc_attr( $key ); ?>]">
				<?php foreach ( $allowed as $opt ) : ?>
					<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $value, $opt ); ?>>
						<?php echo esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $opt ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a hex colour picker field row.
 */
function ss_site_render_hex( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	?>
	<tr>
		<th><label for="ss_site_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<input type="text"
				id="ss_site_<?php echo esc_attr( $key ); ?>"
				name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				class="ss-color-field"
				data-default-color=""
				placeholder="#"
				maxlength="7"
			/>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a checkbox field row.
 */
function ss_site_render_checkbox( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? (int) $s[ $key ] : (int) $field['default'];
	?>
	<tr>
		<th><?php echo esc_html( $field['label'] ); ?></th>
		<td>
			<label>
				<input type="checkbox"
					id="ss_site_<?php echo esc_attr( $key ); ?>"
					name="ss_site_settings[<?php echo esc_attr( $key ); ?>]"
					value="1"
					<?php checked( $value, 1 ); ?>
				/>
				<?php esc_html_e( 'Enabled', 'sender-symposium' ); ?>
			</label>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a media picker field row.
 */
function ss_site_render_media( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = absint( isset( $s[ $key ] ) ? $s[ $key ] : 0 );
	$label = ! empty( $field['label'] ) ? $field['label'] : __( 'Choose Image', 'sender-symposium' );
	?>
	<tr>
		<th><?php echo esc_html( $label ); ?></th>
		<td>
			<?php ss_media_picker( 'ss_site_settings[' . $key . ']', $value, $label ); ?>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render a WYSIWYG (wp_editor) field row.
 */
function ss_site_render_wysiwyg( $key, $s, $schema ) {
	$field = $schema[ $key ];
	$value = isset( $s[ $key ] ) ? $s[ $key ] : '';
	?>
	<tr>
		<th><label><?php echo esc_html( $field['label'] ); ?></label></th>
		<td>
			<?php
			wp_editor( $value, 'ss_site_' . $key, array(
				'textarea_name' => 'ss_site_settings[' . $key . ']',
				'media_buttons' => false,
				'textarea_rows' => 6,
				'teeny'         => true,
				'quicktags'     => true,
			) );
			?>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Auto-render a field by key, dispatching to the correct renderer.
 */
function ss_site_render_field( $key, $s, $schema ) {
	if ( ! isset( $schema[ $key ] ) ) {
		return;
	}
	$type = $schema[ $key ]['type'];
	switch ( $type ) {
		case 'text':
			ss_site_render_text( $key, $s, $schema );
			break;
		case 'inline_html':
			ss_site_render_inline_html( $key, $s, $schema );
			break;
		case 'email':
			ss_site_render_email( $key, $s, $schema );
			break;
		case 'url':
			ss_site_render_url( $key, $s, $schema );
			break;
		case 'date':
			ss_site_render_date( $key, $s, $schema );
			break;
		case 'int':
			ss_site_render_int( $key, $s, $schema );
			break;
		case 'select':
			ss_site_render_select( $key, $s, $schema );
			break;
		case 'hex':
			ss_site_render_hex( $key, $s, $schema );
			break;
		case 'checkbox':
			ss_site_render_checkbox( $key, $s, $schema );
			break;
		case 'wysiwyg':
			ss_site_render_wysiwyg( $key, $s, $schema );
			break;
	}
}

/**
 * Render all fields for a given tab slug.
 */
function ss_site_render_tab_fields( $tab_slug ) {
	$s      = get_option( 'ss_site_settings', array() );
	$schema = ss_site_settings_schema();
	echo '<table class="form-table">';
	foreach ( $schema as $key => $field ) {
		if ( isset( $field['tab'] ) && $field['tab'] === $tab_slug ) {
			ss_site_render_field( $key, $s, $schema );
		}
	}
	echo '</table>';
}

/* =========================================================================
   TAB RENDERERS
   ========================================================================= */

function ss_site_render_tab_branding() {
	ss_site_form_open( 'site_branding' );
	echo '<h2>' . esc_html__( 'Branding', 'sender-symposium' ) . '</h2>';

	/* Logo guidance — single canonical system, no duplicate fields. */
	$settings_url = admin_url( 'admin.php?page=sender-symposium-settings&tab=general' );
	$customize_url = admin_url( 'customize.php?autofocus[section]=title_tagline' );
	?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Primary Logo (Light Mode)', 'sender-symposium' ); ?></th>
			<td>
				<p><?php
					printf(
						/* translators: %s: link to Customizer */
						esc_html__( 'Configured in %s.', 'sender-symposium' ),
						'<a href="' . esc_url( $customize_url ) . '">'
						. esc_html__( 'Appearance → Customize → Site Identity', 'sender-symposium' ) . '</a>'
					);
				?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Optional Dark Mode Logo', 'sender-symposium' ); ?></th>
			<td>
				<p><?php
					printf(
						/* translators: %s: link to General settings tab */
						esc_html__( 'Configured in the %s tab (Layout section).', 'sender-symposium' ),
						'<a href="' . esc_url( $settings_url ) . '">'
						. esc_html__( 'General', 'sender-symposium' ) . '</a>'
					);
				?></p>
				<p class="description">
					<?php esc_html_e( 'If no dark logo is set, the primary logo is used in both modes.', 'sender-symposium' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php

	ss_site_render_tab_fields( 'site_branding' );
	ss_site_form_close();
}

function ss_site_render_tab_event() {
	ss_site_form_open( 'site_event' );
	echo '<h2>' . esc_html__( 'Event Defaults', 'sender-symposium' ) . '</h2>';
	ss_site_render_tab_fields( 'site_event' );
	ss_site_form_close();
}

function ss_site_render_tab_ticketing() {
	ss_site_form_open( 'site_ticketing' );
	echo '<h2>' . esc_html__( 'Ticketing Defaults', 'sender-symposium' ) . '</h2>';
	ss_site_render_tab_fields( 'site_ticketing' );
	ss_site_form_close();
}

function ss_site_render_tab_design() {
	ss_site_form_open( 'site_design' );
	echo '<h2>' . esc_html__( 'Design Defaults', 'sender-symposium' ) . '</h2>';
	ss_site_render_tab_fields( 'site_design' );
	ss_site_form_close();
}

function ss_site_render_tab_integrations() {
	$s = get_option( 'ss_site_settings', array() );

	ss_site_form_open( 'site_integrations' );
	echo '<h2>' . esc_html__( 'Integrations', 'sender-symposium' ) . '</h2>';
	ss_site_render_tab_fields( 'site_integrations' );

	/* ── Inline warnings ── */
	$tt_mode = isset( $s['tt_mode'] ) ? $s['tt_mode'] : 'none';
	$tt_url  = isset( $s['tt_checkout_url'] ) ? trim( $s['tt_checkout_url'] ) : '';
	$tt_sc   = isset( $s['tt_shortcode'] ) ? trim( $s['tt_shortcode'] ) : '';

	if ( 'script_embed' === $tt_mode && '' === $tt_url ) {
		echo '<div class="notice notice-warning inline" style="margin:12px 0;"><p>';
		esc_html_e( 'Script embed mode is selected but no checkout URL has been configured.', 'sender-symposium' );
		echo '</p></div>';
	}
	if ( 'shortcode' === $tt_mode && '' === $tt_sc ) {
		echo '<div class="notice notice-warning inline" style="margin:12px 0;"><p>';
		esc_html_e( 'Shortcode mode is selected but no shortcode has been configured.', 'sender-symposium' );
		echo '</p></div>';
	}

	ss_site_form_close();
}
