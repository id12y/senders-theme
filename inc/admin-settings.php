<?php
/**
 * Sender Symposium — Admin Settings Page
 *
 * Appearance → Sender Symposium Settings
 * Uses the WordPress Settings API. All inputs sanitized, nonces handled by API.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the settings page under Appearance.
 */
function ss_add_settings_page() {
	add_theme_page(
		esc_html__( 'Sender Symposium Settings', 'sender-symposium' ),
		esc_html__( 'Sender Symposium Settings', 'sender-symposium' ),
		'manage_options',
		'sender-symposium-settings',
		'ss_render_settings_page'
	);
}
add_action( 'admin_menu', 'ss_add_settings_page' );

/**
 * Register settings, sections, and fields.
 */
function ss_register_settings() {

	/* ---- FONTS ---- */
	register_setting( 'ss_settings_group', 'ss_font_display', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );
	register_setting( 'ss_settings_group', 'ss_font_body', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );
	register_setting( 'ss_settings_group', 'ss_font_file_url', array(
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	) );

	/* ---- COLOR OVERRIDES ---- */
	$color_tokens = ss_get_overridable_tokens();
	foreach ( $color_tokens as $token => $label ) {
		register_setting( 'ss_settings_group', 'ss_color_' . $token, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '',
		) );
	}

	/* ---- LAYOUT ---- */
	register_setting( 'ss_settings_group', 'ss_container_max', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_container_max',
		'default'           => '1200',
	) );
	register_setting( 'ss_settings_group', 'ss_section_padding', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_section_padding',
		'default'           => 'standard',
	) );
	register_setting( 'ss_settings_group', 'ss_logo_max_height', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_logo_max_height',
		'default'           => '40',
	) );
	register_setting( 'ss_settings_group', 'ss_hero_field', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_toggle',
		'default'           => 'on',
	) );

	/* ---- ANNOUNCEMENT BAR ---- */
	register_setting( 'ss_settings_group', 'ss_announcement_enabled', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_toggle',
		'default'           => 'off',
	) );
	register_setting( 'ss_settings_group', 'ss_announcement_text', array(
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => '',
	) );
	register_setting( 'ss_settings_group', 'ss_announcement_url', array(
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	) );

	/* ---- EVENT ---- */
	register_setting( 'ss_settings_group', 'ss_event_start_date', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_date',
		'default'           => '',
	) );

	/* ---- DARK MODE ---- */
	register_setting( 'ss_settings_group', 'ss_dark_mode_default', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_dark_mode_default',
		'default'           => 'system',
	) );
	register_setting( 'ss_settings_group', 'ss_dark_mode_toggle', array(
		'type'              => 'string',
		'sanitize_callback' => 'ss_sanitize_toggle',
		'default'           => 'on',
	) );

	/* ---- SECTIONS ---- */
	add_settings_section( 'ss_section_fonts', esc_html__( 'Fonts', 'sender-symposium' ), 'ss_section_fonts_cb', 'sender-symposium-settings' );
	add_settings_section( 'ss_section_colors', esc_html__( 'Color Overrides', 'sender-symposium' ), 'ss_section_colors_cb', 'sender-symposium-settings' );
	add_settings_section( 'ss_section_layout', esc_html__( 'Layout', 'sender-symposium' ), '__return_false', 'sender-symposium-settings' );
	add_settings_section( 'ss_section_announcement', esc_html__( 'Announcement Bar', 'sender-symposium' ), '__return_false', 'sender-symposium-settings' );
	add_settings_section( 'ss_section_event', esc_html__( 'Event', 'sender-symposium' ), '__return_false', 'sender-symposium-settings' );
	add_settings_section( 'ss_section_darkmode', esc_html__( 'Dark Mode', 'sender-symposium' ), '__return_false', 'sender-symposium-settings' );

	/* ---- FIELDS: Fonts ---- */
	add_settings_field( 'ss_font_display', esc_html__( 'Display Font Family', 'sender-symposium' ), 'ss_field_text', 'sender-symposium-settings', 'ss_section_fonts', array(
		'id'          => 'ss_font_display',
		'placeholder' => '"Barcelona Variable", Georgia, serif',
		'description' => esc_html__( 'CSS font-family stack for headings. Leave blank for default (Barcelona Variable).', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_font_body', esc_html__( 'Body Font Family', 'sender-symposium' ), 'ss_field_text', 'sender-symposium-settings', 'ss_section_fonts', array(
		'id'          => 'ss_font_body',
		'placeholder' => 'system-ui, -apple-system, sans-serif',
		'description' => esc_html__( 'CSS font-family stack for body text. Leave blank for default (system-ui).', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_font_file_url', esc_html__( 'Custom Display Font File URL', 'sender-symposium' ), 'ss_field_text', 'sender-symposium-settings', 'ss_section_fonts', array(
		'id'          => 'ss_font_file_url',
		'placeholder' => '',
		'description' => esc_html__( 'URL to a local .woff2 font file (uploaded to Media Library or theme assets). Leave blank to use bundled Barcelona Variable. Must be a local URL — no external CDNs.', 'sender-symposium' ),
	) );

	/* ---- FIELDS: Colors ---- */
	foreach ( $color_tokens as $token => $label ) {
		add_settings_field( 'ss_color_' . $token, esc_html( $label ), 'ss_field_color', 'sender-symposium-settings', 'ss_section_colors', array(
			'id' => 'ss_color_' . $token,
		) );
	}

	/* ---- FIELDS: Layout ---- */
	add_settings_field( 'ss_container_max', esc_html__( 'Container Max Width', 'sender-symposium' ), 'ss_field_select', 'sender-symposium-settings', 'ss_section_layout', array(
		'id'      => 'ss_container_max',
		'options' => array(
			'1120' => '1120px',
			'1200' => '1200px (default)',
			'1280' => '1280px',
		),
	) );
	add_settings_field( 'ss_section_padding', esc_html__( 'Section Padding Scale', 'sender-symposium' ), 'ss_field_select', 'sender-symposium-settings', 'ss_section_layout', array(
		'id'      => 'ss_section_padding',
		'options' => array(
			'compact'  => esc_html__( 'Compact (64px / 48px)', 'sender-symposium' ),
			'standard' => esc_html__( 'Standard (96px / 64px)', 'sender-symposium' ),
			'airy'     => esc_html__( 'Airy (128px / 96px)', 'sender-symposium' ),
		),
	) );
	add_settings_field( 'ss_logo_max_height', esc_html__( 'Logo Max Height', 'sender-symposium' ), 'ss_field_select', 'sender-symposium-settings', 'ss_section_layout', array(
		'id'      => 'ss_logo_max_height',
		'options' => array(
			'28' => '28px (compact)',
			'40' => '40px (default)',
			'56' => '56px',
			'72' => '72px',
		),
	) );
	add_settings_field( 'ss_hero_field', esc_html__( 'Hero Architectural Field', 'sender-symposium' ), 'ss_field_toggle', 'sender-symposium-settings', 'ss_section_layout', array(
		'id'          => 'ss_hero_field',
		'description' => esc_html__( 'Show the abstract La Pedrera linework in the hero section.', 'sender-symposium' ),
	) );

	/* ---- FIELDS: Announcement ---- */
	add_settings_field( 'ss_announcement_enabled', esc_html__( 'Enable Announcement Bar', 'sender-symposium' ), 'ss_field_toggle', 'sender-symposium-settings', 'ss_section_announcement', array(
		'id' => 'ss_announcement_enabled',
	) );
	add_settings_field( 'ss_announcement_text', esc_html__( 'Announcement Text', 'sender-symposium' ), 'ss_field_text', 'sender-symposium-settings', 'ss_section_announcement', array(
		'id'          => 'ss_announcement_text',
		'placeholder' => esc_attr__( 'Early bird tickets available — limited spots.', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_announcement_url', esc_html__( 'Announcement Link URL', 'sender-symposium' ), 'ss_field_text', 'sender-symposium-settings', 'ss_section_announcement', array(
		'id'          => 'ss_announcement_url',
		'placeholder' => 'https://',
	) );

	/* ---- FIELDS: Event ---- */
	add_settings_field( 'ss_event_start_date', esc_html__( 'Event Start Date', 'sender-symposium' ), 'ss_field_date', 'sender-symposium-settings', 'ss_section_event', array(
		'id'          => 'ss_event_start_date',
		'description' => esc_html__( 'Used in the Schema.org Event structured data (JSON-LD). Format: YYYY-MM-DD.', 'sender-symposium' ),
	) );

	/* ---- FIELDS: Dark Mode ---- */
	add_settings_field( 'ss_dark_mode_default', esc_html__( 'Default Mode', 'sender-symposium' ), 'ss_field_select', 'sender-symposium-settings', 'ss_section_darkmode', array(
		'id'      => 'ss_dark_mode_default',
		'options' => array(
			'system' => esc_html__( 'Follow system preference', 'sender-symposium' ),
			'light'  => esc_html__( 'Force light', 'sender-symposium' ),
			'dark'   => esc_html__( 'Force dark', 'sender-symposium' ),
		),
	) );
	add_settings_field( 'ss_dark_mode_toggle', esc_html__( 'Show Theme Toggle', 'sender-symposium' ), 'ss_field_toggle', 'sender-symposium-settings', 'ss_section_darkmode', array(
		'id'          => 'ss_dark_mode_toggle',
		'description' => esc_html__( 'Display the dark/light toggle in the header.', 'sender-symposium' ),
	) );
}
add_action( 'admin_init', 'ss_register_settings' );

/* ==========================================================================
   HELPERS — tokens list, sanitizers, field renderers
   ========================================================================== */

/**
 * Overridable color tokens.
 */
function ss_get_overridable_tokens() {
	return array(
		'surface_page'         => __( 'Surface — Page', 'sender-symposium' ),
		'surface_section'      => __( 'Surface — Section', 'sender-symposium' ),
		'surface_card'         => __( 'Surface — Card', 'sender-symposium' ),
		'text_primary'         => __( 'Text — Primary', 'sender-symposium' ),
		'text_secondary'       => __( 'Text — Secondary', 'sender-symposium' ),
		'action_primary_bg'    => __( 'Action Primary — Background', 'sender-symposium' ),
		'action_primary_hover' => __( 'Action Primary — Hover', 'sender-symposium' ),
		'action_primary_text'  => __( 'Action Primary — Text', 'sender-symposium' ),
		'link_default'         => __( 'Link — Default', 'sender-symposium' ),
		'link_hover'           => __( 'Link — Hover', 'sender-symposium' ),
		'focus_ring'           => __( 'Focus Ring', 'sender-symposium' ),
	);
}

/* ---- Sanitizers ---- */

function ss_sanitize_logo_max_height( $value ) {
	$allowed = array( '28', '40', '56', '72' );
	return in_array( $value, $allowed, true ) ? $value : '40';
}

function ss_sanitize_container_max( $value ) {
	$allowed = array( '1120', '1200', '1280' );
	return in_array( $value, $allowed, true ) ? $value : '1200';
}

function ss_sanitize_section_padding( $value ) {
	$allowed = array( 'compact', 'standard', 'airy' );
	return in_array( $value, $allowed, true ) ? $value : 'standard';
}

function ss_sanitize_toggle( $value ) {
	return $value === 'on' ? 'on' : 'off';
}

function ss_sanitize_date( $value ) {
	$value = sanitize_text_field( $value );
	if ( '' === $value ) {
		return '';
	}
	/* Accept YYYY-MM-DD only */
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) && strtotime( $value ) !== false ) {
		return $value;
	}
	return '';
}

function ss_sanitize_dark_mode_default( $value ) {
	$allowed = array( 'system', 'light', 'dark' );
	return in_array( $value, $allowed, true ) ? $value : 'system';
}

/* ---- Field renderers ---- */

function ss_field_text( $args ) {
	$value = get_option( $args['id'], '' );
	printf(
		'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s" />',
		esc_attr( $args['id'] ),
		esc_attr( $value ),
		isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : ''
	);
	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

function ss_field_color( $args ) {
	$value = get_option( $args['id'], '' );
	printf(
		'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="ss-color-field" data-default-color="" placeholder="#" maxlength="7" pattern="#[0-9a-fA-F]{6}" />',
		esc_attr( $args['id'] ),
		esc_attr( $value )
	);
	echo '<p class="description">' . esc_html__( 'Leave blank to use theme default. Enter a hex color (#RRGGBB). Ensure WCAG AA contrast.', 'sender-symposium' ) . '</p>';
}

function ss_field_select( $args ) {
	$value = get_option( $args['id'] );
	printf( '<select id="%1$s" name="%1$s">', esc_attr( $args['id'] ) );
	foreach ( $args['options'] as $key => $label ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $key ),
			selected( $value, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

function ss_field_date( $args ) {
	$value = get_option( $args['id'], '' );
	printf(
		'<input type="date" id="%1$s" name="%1$s" value="%2$s" />',
		esc_attr( $args['id'] ),
		esc_attr( $value )
	);
	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

function ss_field_toggle( $args ) {
	$value = get_option( $args['id'] );
	printf(
		'<input type="hidden" name="%1$s" value="off" />'
		. '<label><input type="checkbox" id="%1$s" name="%1$s" value="on" %2$s /> %3$s</label>',
		esc_attr( $args['id'] ),
		checked( $value, 'on', false ),
		esc_html__( 'Enabled', 'sender-symposium' )
	);
	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

/* ---- Section callbacks ---- */

function ss_section_fonts_cb() {
	echo '<p>' . esc_html__( 'Configure the font families used by the theme. The bundled Barcelona Variable font is loaded from the theme assets. To use a different display font, upload a .woff2 file and enter its URL below.', 'sender-symposium' ) . '</p>';
}

function ss_section_colors_cb() {
	echo '<p>' . esc_html__( 'Override individual color tokens. Leave blank to use theme defaults. These overrides apply to both light and dark mode base values. Ensure sufficient contrast for accessibility (WCAG AA: 4.5:1 for normal text).', 'sender-symposium' ) . '</p>';
}

/* ==========================================================================
   RENDER SETTINGS PAGE
   ========================================================================== */

function ss_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'ss_settings_group' );
			do_settings_sections( 'sender-symposium-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Enqueue color picker on settings page.
 */
function ss_admin_enqueue( $hook ) {
	if ( 'appearance_page_sender-symposium-settings' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script(
		'ss-admin-settings',
		get_template_directory_uri() . '/assets/js/admin-settings.js',
		array( 'wp-color-picker' ),
		ss_asset_version( get_template_directory() . '/assets/js/admin-settings.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'ss_admin_enqueue' );
