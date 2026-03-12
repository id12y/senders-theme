<?php
/**
 * Sender Symposium — Admin Settings Page (Tabbed)
 *
 * Appearance → Theme Settings
 * Tabs: General | Colors | Homepage | Announcement | Dark Mode
 *
 * Uses a mix of Settings API (for General/Colors/DarkMode/Announcement)
 * and custom form handling (for Homepage repeatable fields).
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   ADMIN MENU
   ========================================================================= */

function ss_add_settings_page() {
	/* Top-level menu — settings page is the parent.
	 * Priority 5 so the parent exists before submenus register at 10. */
	add_menu_page(
		esc_html__( 'Sender Symposium', 'sender-symposium' ),
		esc_html__( 'Sender Symposium', 'sender-symposium' ),
		'manage_options',
		'sender-symposium-settings',
		'ss_render_settings_page',
		'dashicons-admin-site-alt3',
		59
	);
	/* First submenu replaces the auto-generated parent link. */
	add_submenu_page(
		'sender-symposium-settings',
		esc_html__( 'Settings', 'sender-symposium' ),
		esc_html__( 'Settings', 'sender-symposium' ),
		'manage_options',
		'sender-symposium-settings',
		'ss_render_settings_page'
	);
}
add_action( 'admin_menu', 'ss_add_settings_page', 5 );

/* =========================================================================
   SETTINGS API REGISTRATION
   ========================================================================= */

function ss_register_settings() {

	/* ── General: Fonts ── */
	register_setting( 'ss_tab_general', 'ss_font_display', array(
		'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '',
	) );
	register_setting( 'ss_tab_general', 'ss_font_body', array(
		'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '',
	) );
	register_setting( 'ss_tab_general', 'ss_font_file_url', array(
		'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '',
	) );

	/* ── General: Layout ── */
	register_setting( 'ss_tab_general', 'ss_container_max', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_container_max', 'default' => '1200',
	) );
	register_setting( 'ss_tab_general', 'ss_section_padding', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_section_padding', 'default' => 'standard',
	) );
	register_setting( 'ss_tab_general', 'ss_logo_max_height', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_logo_max_height', 'default' => '40',
	) );
	register_setting( 'ss_tab_general', 'ss_logo_dark_id', array(
		'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0,
	) );
	register_setting( 'ss_tab_general', 'ss_hero_field', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_toggle', 'default' => 'on',
	) );

	/* ── General: Event ── */
	register_setting( 'ss_tab_general', 'ss_event_start_date', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_date', 'default' => '',
	) );

	/* ── General: Debug ── */
	register_setting( 'ss_tab_general', 'ss_hide_php_errors', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_toggle', 'default' => 'on',
	) );

	/* ── Colors ── */
	$color_tokens = ss_get_overridable_tokens();
	foreach ( $color_tokens as $token => $label ) {
		register_setting( 'ss_tab_colors', 'ss_color_' . $token, array(
			'type' => 'string', 'sanitize_callback' => 'sanitize_hex_color', 'default' => '',
		) );
	}

	/* ── Announcement ── */
	register_setting( 'ss_tab_announcement', 'ss_announcement_enabled', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_toggle', 'default' => 'off',
	) );
	register_setting( 'ss_tab_announcement', 'ss_announcement_text', array(
		'type' => 'string', 'sanitize_callback' => 'wp_kses_post', 'default' => '',
	) );
	register_setting( 'ss_tab_announcement', 'ss_announcement_url', array(
		'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '',
	) );

	/* ── Dark Mode ── */
	register_setting( 'ss_tab_darkmode', 'ss_dark_mode_default', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_dark_mode_default', 'default' => 'system',
	) );
	register_setting( 'ss_tab_darkmode', 'ss_dark_mode_toggle', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_toggle', 'default' => 'on',
	) );
	register_setting( 'ss_tab_darkmode', 'ss_text_size_toggle', array(
		'type' => 'string', 'sanitize_callback' => 'ss_sanitize_toggle', 'default' => 'on',
	) );

	/* ── Sections & Fields: General ── */
	add_settings_section( 'ss_section_fonts', esc_html__( 'Fonts', 'sender-symposium' ), 'ss_section_fonts_cb', 'ss_page_general' );
	add_settings_section( 'ss_section_layout', esc_html__( 'Layout', 'sender-symposium' ), '__return_false', 'ss_page_general' );
	add_settings_section( 'ss_section_event', esc_html__( 'Event', 'sender-symposium' ), '__return_false', 'ss_page_general' );
	add_settings_section( 'ss_section_debug', esc_html__( 'Debug', 'sender-symposium' ), '__return_false', 'ss_page_general' );

	add_settings_field( 'ss_font_display', esc_html__( 'Display Font Family', 'sender-symposium' ), 'ss_field_text', 'ss_page_general', 'ss_section_fonts', array(
		'id' => 'ss_font_display', 'placeholder' => '"Barcelona Variable", Georgia, serif',
		'description' => esc_html__( 'CSS font-family stack for headings. Leave blank for default.', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_font_body', esc_html__( 'Body Font Family', 'sender-symposium' ), 'ss_field_text', 'ss_page_general', 'ss_section_fonts', array(
		'id' => 'ss_font_body', 'placeholder' => 'system-ui, -apple-system, sans-serif',
		'description' => esc_html__( 'CSS font-family stack for body text. Leave blank for default.', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_font_file_url', esc_html__( 'Display Font File', 'sender-symposium' ), 'ss_field_font_upload', 'ss_page_general', 'ss_section_fonts', array(
		'id' => 'ss_font_file_url',
		'description' => esc_html__( 'Upload a .woff2 font file, or paste a URL. Leave blank to use bundled Barcelona Variable from theme fonts folder.', 'sender-symposium' ),
	) );

	add_settings_field( 'ss_container_max', esc_html__( 'Container Max Width', 'sender-symposium' ), 'ss_field_select', 'ss_page_general', 'ss_section_layout', array(
		'id' => 'ss_container_max', 'options' => array(
			'1120' => '1120px', '1200' => '1200px (default)', '1280' => '1280px',
		),
	) );
	add_settings_field( 'ss_section_padding', esc_html__( 'Section Padding Scale', 'sender-symposium' ), 'ss_field_select', 'ss_page_general', 'ss_section_layout', array(
		'id' => 'ss_section_padding', 'options' => array(
			'compact' => esc_html__( 'Compact (64px)', 'sender-symposium' ),
			'standard' => esc_html__( 'Standard (96px)', 'sender-symposium' ),
			'airy' => esc_html__( 'Airy (128px)', 'sender-symposium' ),
		),
	) );
	add_settings_field( 'ss_logo_max_height', esc_html__( 'Logo Max Height', 'sender-symposium' ), 'ss_field_select', 'ss_page_general', 'ss_section_layout', array(
		'id' => 'ss_logo_max_height', 'options' => array(
			'28' => '28px (compact)', '40' => '40px (default)', '56' => '56px', '72' => '72px',
		),
	) );
	add_settings_field( 'ss_logo_dark_id', esc_html__( 'Dark Mode Logo', 'sender-symposium' ), 'ss_field_media', 'ss_page_general', 'ss_section_layout', array(
		'id' => 'ss_logo_dark_id',
		'button_label' => esc_html__( 'Choose Dark Logo', 'sender-symposium' ),
		'description' => esc_html__( 'Logo for dark mode (white-on-dark). Leave empty to use the standard logo. Set your light logo in Appearance → Customize → Site Identity.', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_hero_field', esc_html__( 'Hero Architectural Field', 'sender-symposium' ), 'ss_field_toggle', 'ss_page_general', 'ss_section_layout', array(
		'id' => 'ss_hero_field',
		'description' => esc_html__( 'Show the abstract La Pedrera linework in the hero section.', 'sender-symposium' ),
	) );

	add_settings_field( 'ss_event_start_date', esc_html__( 'Event Start Date', 'sender-symposium' ), 'ss_field_date', 'ss_page_general', 'ss_section_event', array(
		'id' => 'ss_event_start_date',
		'description' => esc_html__( 'Used in Schema.org Event JSON-LD. Format: YYYY-MM-DD.', 'sender-symposium' ),
	) );

	add_settings_field( 'ss_hide_php_errors', esc_html__( 'Hide PHP Errors on Frontend', 'sender-symposium' ), 'ss_field_toggle', 'ss_page_general', 'ss_section_debug', array(
		'id' => 'ss_hide_php_errors',
		'description' => esc_html__( 'Suppress PHP notices/warnings from plugins on the frontend. Errors are still logged to the server error log.', 'sender-symposium' ),
	) );

	/* ── Sections & Fields: Colors ── */
	add_settings_section( 'ss_section_colors', esc_html__( 'Color Token Overrides', 'sender-symposium' ), 'ss_section_colors_cb', 'ss_page_colors' );
	foreach ( $color_tokens as $token => $label ) {
		add_settings_field( 'ss_color_' . $token, esc_html( $label ), 'ss_field_color', 'ss_page_colors', 'ss_section_colors', array(
			'id' => 'ss_color_' . $token,
		) );
	}

	/* ── Sections & Fields: Announcement ── */
	add_settings_section( 'ss_section_announcement', esc_html__( 'Announcement Bar', 'sender-symposium' ), '__return_false', 'ss_page_announcement' );
	add_settings_field( 'ss_announcement_enabled', esc_html__( 'Enable Announcement Bar', 'sender-symposium' ), 'ss_field_toggle', 'ss_page_announcement', 'ss_section_announcement', array(
		'id' => 'ss_announcement_enabled',
	) );
	add_settings_field( 'ss_announcement_text', esc_html__( 'Announcement Text', 'sender-symposium' ), 'ss_field_text', 'ss_page_announcement', 'ss_section_announcement', array(
		'id' => 'ss_announcement_text',
		'placeholder' => esc_attr__( 'Early bird tickets available — limited spots.', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_announcement_url', esc_html__( 'Announcement Link URL', 'sender-symposium' ), 'ss_field_text', 'ss_page_announcement', 'ss_section_announcement', array(
		'id' => 'ss_announcement_url', 'placeholder' => 'https://',
	) );

	/* ── Sections & Fields: Dark Mode ── */
	add_settings_section( 'ss_section_darkmode', esc_html__( 'Dark Mode', 'sender-symposium' ), '__return_false', 'ss_page_darkmode' );
	add_settings_field( 'ss_dark_mode_default', esc_html__( 'Default Mode', 'sender-symposium' ), 'ss_field_select', 'ss_page_darkmode', 'ss_section_darkmode', array(
		'id' => 'ss_dark_mode_default', 'options' => array(
			'system' => esc_html__( 'Follow system preference', 'sender-symposium' ),
			'light'  => esc_html__( 'Force light', 'sender-symposium' ),
			'dark'   => esc_html__( 'Force dark', 'sender-symposium' ),
		),
	) );
	add_settings_field( 'ss_dark_mode_toggle', esc_html__( 'Show Theme Toggle', 'sender-symposium' ), 'ss_field_toggle', 'ss_page_darkmode', 'ss_section_darkmode', array(
		'id' => 'ss_dark_mode_toggle',
		'description' => esc_html__( 'Display the dark/light toggle in the header.', 'sender-symposium' ),
	) );
	add_settings_field( 'ss_text_size_toggle', esc_html__( 'Show Text Size Toggle', 'sender-symposium' ), 'ss_field_toggle', 'ss_page_darkmode', 'ss_section_darkmode', array(
		'id' => 'ss_text_size_toggle',
		'description' => esc_html__( 'Display a text size toggle button in the header for accessibility.', 'sender-symposium' ),
	) );
}
add_action( 'admin_init', 'ss_register_settings' );

/* =========================================================================
   HOMEPAGE TAB — Custom form handler (POST → redirect)
   ========================================================================= */

function ss_handle_homepage_save() {
	if ( ! isset( $_POST['ss_homepage_action'] ) || 'save' !== $_POST['ss_homepage_action'] ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'ss_save_homepage' );

	ss_save_homepage( wp_unslash( $_POST['hp'] ?? array() ) );

	wp_safe_redirect( add_query_arg( array(
		'page'    => 'sender-symposium-settings',
		'tab'     => 'homepage',
		'updated' => 'true',
	), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'ss_handle_homepage_save' );

/* =========================================================================
   HELPERS — tokens, sanitizers, field renderers
   ========================================================================= */

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

/* ── Sanitizers ── */

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
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) && strtotime( $value ) !== false ) {
		return $value;
	}
	return '';
}

function ss_sanitize_dark_mode_default( $value ) {
	$allowed = array( 'system', 'light', 'dark' );
	return in_array( $value, $allowed, true ) ? $value : 'system';
}

/* ── Field renderers ── */

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
	echo '<p class="description">' . esc_html__( 'Leave blank for theme default. Hex color (#RRGGBB). Ensure WCAG AA contrast.', 'sender-symposium' ) . '</p>';
}

function ss_field_select( $args ) {
	$value = get_option( $args['id'] );
	printf( '<select id="%1$s" name="%1$s">', esc_attr( $args['id'] ) );
	foreach ( $args['options'] as $key => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
	}
	echo '</select>';
}

function ss_field_date( $args ) {
	$value = get_option( $args['id'], '' );
	printf( '<input type="date" id="%1$s" name="%1$s" value="%2$s" />', esc_attr( $args['id'] ), esc_attr( $value ) );
	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

function ss_field_number( $args ) {
	$value = get_option( $args['id'], 0 );
	printf(
		'<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text" min="0" />',
		esc_attr( $args['id'] ),
		esc_attr( $value )
	);
	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

function ss_field_media( $args ) {
	$value = absint( get_option( $args['id'], 0 ) );
	$label = ! empty( $args['button_label'] ) ? $args['button_label'] : __( 'Choose Image', 'sender-symposium' );
	ss_media_picker( $args['id'], $value, $label );
	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

function ss_field_font_upload( $args ) {
	$value = get_option( $args['id'], '' );
	$filename = $value ? basename( $value ) : '';
	?>
	<div class="ss-font-upload">
		<input type="text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['id'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text ss-font-upload__url" placeholder="https://" />
		<button type="button" class="button ss-font-upload__choose"><?php esc_html_e( 'Upload Font', 'sender-symposium' ); ?></button>
		<?php if ( $filename ) : ?>
		<span class="ss-font-upload__filename" style="display:inline-block;margin-left:8px;color:#666;"><?php echo esc_html( $filename ); ?></span>
		<?php else : ?>
		<span class="ss-font-upload__filename" style="display:none;margin-left:8px;color:#666;"></span>
		<?php endif; ?>
	</div>
	<?php
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

/* ── Section callbacks ── */

function ss_section_fonts_cb() {
	echo '<p>' . esc_html__( 'Configure the font families used by the theme.', 'sender-symposium' ) . '</p>';
}

function ss_section_colors_cb() {
	echo '<p>' . esc_html__( 'Override individual color tokens. Leave blank to use theme defaults. Applies to both light and dark modes. Ensure WCAG AA contrast (4.5:1).', 'sender-symposium' ) . '</p>';
}

/* =========================================================================
   RENDER SETTINGS PAGE — Tabbed
   ========================================================================= */

function ss_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs = apply_filters( 'ss_settings_tabs', array(
		'general'      => __( 'General', 'sender-symposium' ),
		'colors'       => __( 'Colors', 'sender-symposium' ),
		'homepage'     => __( 'Homepage', 'sender-symposium' ),
		'announcement' => __( 'Announcement', 'sender-symposium' ),
		'darkmode'     => __( 'Dark Mode', 'sender-symposium' ),
	) );

	$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
	if ( ! isset( $tabs[ $current_tab ] ) ) {
		$current_tab = 'general';
	}

	$base_url = admin_url( 'admin.php?page=sender-symposium-settings' );

	/* Show updated notice for homepage tab (custom handler) */
	if ( 'homepage' === $current_tab && ! empty( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Homepage settings saved.', 'sender-symposium' ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sender Symposium Settings', 'sender-symposium' ); ?></h1>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>"
				   href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<div style="padding-top:20px;">
		<?php
		switch ( $current_tab ) {
			case 'general':
				ss_render_tab_settings_api( 'ss_tab_general', 'ss_page_general' );
				break;
			case 'colors':
				ss_render_tab_settings_api( 'ss_tab_colors', 'ss_page_colors' );
				break;
			case 'homepage':
				ss_render_tab_homepage();
				break;
			case 'announcement':
				ss_render_tab_settings_api( 'ss_tab_announcement', 'ss_page_announcement' );
				break;
			case 'darkmode':
				ss_render_tab_settings_api( 'ss_tab_darkmode', 'ss_page_darkmode' );
				break;
			default:
				/**
				 * Fires when rendering a tab not handled by the core switch.
				 * Used by inc/admin/settings-page.php to render site settings tabs.
				 *
				 * @param string $current_tab The active tab slug.
				 */
				do_action( 'ss_render_settings_tab', $current_tab );
		}
		?>
		</div>
	</div>
	<?php
}

/**
 * Render a Settings API–based tab.
 */
function ss_render_tab_settings_api( $group, $page ) {
	?>
	<form action="options.php" method="post">
		<?php
		settings_fields( $group );
		do_settings_sections( $page );
		submit_button();
		?>
	</form>
	<?php
}

/**
 * Render the Homepage content tab (custom form).
 */
function ss_render_tab_homepage() {
	$hp = ss_get_homepage();
	?>
	<form method="post">
		<?php wp_nonce_field( 'ss_save_homepage' ); ?>
		<input type="hidden" name="ss_homepage_action" value="save" />

		<p class="description" style="margin-bottom:20px;">
			<?php esc_html_e( 'Edit the front-page content blocks below. Disable any section to hide it. Elementor/Gutenberg content from the homepage editor appears between the event strip and the audience block.', 'sender-symposium' ); ?>
		</p>

		<?php /* ── Event Strip ── */ ?>
		<h2><?php esc_html_e( 'Event Info Strip', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enabled', 'sender-symposium' ); ?></th>
				<td><input type="hidden" name="hp[event_strip_enabled]" value="0" /><label><input type="checkbox" name="hp[event_strip_enabled]" value="1" <?php checked( $hp['event_strip_enabled'] ); ?> /> <?php esc_html_e( 'Show event info strip', 'sender-symposium' ); ?></label></td></tr>
		</table>
		<h4><?php esc_html_e( 'Items (leave both fields empty to remove an item)', 'sender-symposium' ); ?></h4>
		<table class="widefat" style="max-width:600px;">
			<thead><tr><th><?php esc_html_e( 'Label', 'sender-symposium' ); ?></th><th><?php esc_html_e( 'Value', 'sender-symposium' ); ?></th></tr></thead>
			<tbody>
			<?php
			$strip_items = $hp['event_strip'];
			for ( $i = 0; $i < 6; $i++ ) :
				$label = $strip_items[ $i ]['label'] ?? '';
				$value = $strip_items[ $i ]['value'] ?? '';
			?>
			<tr>
				<td><input type="text" name="hp[event_strip][<?php echo $i; ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text" /></td>
				<td><input type="text" name="hp[event_strip][<?php echo $i; ?>][value]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" /></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>

		<hr />

		<?php /* ── Audience ── */ ?>
		<h2><?php esc_html_e( 'Audience Block', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enabled', 'sender-symposium' ); ?></th>
				<td><input type="hidden" name="hp[audience_enabled]" value="0" /><label><input type="checkbox" name="hp[audience_enabled]" value="1" <?php checked( $hp['audience_enabled'] ); ?> /> <?php esc_html_e( 'Show audience block', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[audience_title]" value="<?php echo esc_attr( $hp['audience_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( '"For You" Heading', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[audience_title_for]" value="<?php echo esc_attr( $hp['audience_title_for'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( '"For You" Items', 'sender-symposium' ); ?></label></th>
				<td><textarea name="hp[audience_items_for]" class="large-text" rows="5"><?php echo esc_textarea( $hp['audience_items_for'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One item per line.', 'sender-symposium' ); ?></p></td></tr>
			<tr><th><label><?php esc_html_e( '"Not For You" Heading', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[audience_title_not]" value="<?php echo esc_attr( $hp['audience_title_not'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( '"Not For You" Items', 'sender-symposium' ); ?></label></th>
				<td><textarea name="hp[audience_items_not]" class="large-text" rows="4"><?php echo esc_textarea( $hp['audience_items_not'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One item per line.', 'sender-symposium' ); ?></p></td></tr>
		</table>

		<hr />

		<?php /* ── Values ── */ ?>
		<h2><?php esc_html_e( 'Value / Outcome Cards', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enabled', 'sender-symposium' ); ?></th>
				<td><input type="hidden" name="hp[values_enabled]" value="0" /><label><input type="checkbox" name="hp[values_enabled]" value="1" <?php checked( $hp['values_enabled'] ); ?> /> <?php esc_html_e( 'Show value cards', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[values_title]" value="<?php echo esc_attr( $hp['values_title'] ); ?>" class="regular-text" /></td></tr>
		</table>
		<h4><?php esc_html_e( 'Cards (leave title empty to remove a card)', 'sender-symposium' ); ?></h4>
		<table class="widefat" style="max-width:800px;">
			<thead><tr><th style="width:60px;"><?php esc_html_e( '#', 'sender-symposium' ); ?></th><th><?php esc_html_e( 'Title', 'sender-symposium' ); ?></th><th><?php esc_html_e( 'Body', 'sender-symposium' ); ?></th></tr></thead>
			<tbody>
			<?php
			$val_items = $hp['values'];
			for ( $i = 0; $i < 6; $i++ ) :
				$num   = $val_items[ $i ]['number'] ?? '';
				$title = $val_items[ $i ]['title'] ?? '';
				$body  = $val_items[ $i ]['body'] ?? '';
			?>
			<tr>
				<td><input type="text" name="hp[values][<?php echo $i; ?>][number]" value="<?php echo esc_attr( $num ); ?>" style="width:50px;" /></td>
				<td><input type="text" name="hp[values][<?php echo $i; ?>][title]" value="<?php echo esc_attr( $title ); ?>" class="regular-text" /></td>
				<td><input type="text" name="hp[values][<?php echo $i; ?>][body]" value="<?php echo esc_attr( $body ); ?>" class="large-text" /></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>

		<hr />

		<?php /* ── Format ── */ ?>
		<h2><?php esc_html_e( 'Format Block', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enabled', 'sender-symposium' ); ?></th>
				<td><input type="hidden" name="hp[format_enabled]" value="0" /><label><input type="checkbox" name="hp[format_enabled]" value="1" <?php checked( $hp['format_enabled'] ); ?> /> <?php esc_html_e( 'Show format block', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[format_title]" value="<?php echo esc_attr( $hp['format_title'] ); ?>" class="regular-text" /></td></tr>
		</table>
		<h4><?php esc_html_e( 'Items (leave title empty to remove)', 'sender-symposium' ); ?></h4>
		<table class="widefat" style="max-width:700px;">
			<thead><tr><th><?php esc_html_e( 'Title', 'sender-symposium' ); ?></th><th><?php esc_html_e( 'Description', 'sender-symposium' ); ?></th></tr></thead>
			<tbody>
			<?php
			$fmt_items = $hp['format_items'];
			for ( $i = 0; $i < 6; $i++ ) :
				$title = $fmt_items[ $i ]['title'] ?? '';
				$body  = $fmt_items[ $i ]['body'] ?? '';
			?>
			<tr>
				<td><input type="text" name="hp[format_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr( $title ); ?>" class="regular-text" /></td>
				<td><input type="text" name="hp[format_items][<?php echo $i; ?>][body]" value="<?php echo esc_attr( $body ); ?>" class="large-text" /></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>

		<hr />

		<?php /* ── Credibility ── */ ?>
		<h2><?php esc_html_e( 'Credibility Stats', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enabled', 'sender-symposium' ); ?></th>
				<td><input type="hidden" name="hp[credibility_enabled]" value="0" /><label><input type="checkbox" name="hp[credibility_enabled]" value="1" <?php checked( $hp['credibility_enabled'] ); ?> /> <?php esc_html_e( 'Show credibility row', 'sender-symposium' ); ?></label></td></tr>
		</table>
		<h4><?php esc_html_e( 'Stats (leave both empty to remove)', 'sender-symposium' ); ?></h4>
		<table class="widefat" style="max-width:400px;">
			<thead><tr><th><?php esc_html_e( 'Number', 'sender-symposium' ); ?></th><th><?php esc_html_e( 'Label', 'sender-symposium' ); ?></th></tr></thead>
			<tbody>
			<?php
			$cred_items = $hp['credibility_items'];
			for ( $i = 0; $i < 6; $i++ ) :
				$num   = $cred_items[ $i ]['number'] ?? '';
				$label = $cred_items[ $i ]['label'] ?? '';
			?>
			<tr>
				<td><input type="text" name="hp[credibility_items][<?php echo $i; ?>][number]" value="<?php echo esc_attr( $num ); ?>" style="width:80px;" /></td>
				<td><input type="text" name="hp[credibility_items][<?php echo $i; ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text" /></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>

		<hr />

		<?php /* ── CTA ── */ ?>
		<h2><?php esc_html_e( 'Call to Action', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enabled', 'sender-symposium' ); ?></th>
				<td><input type="hidden" name="hp[cta_enabled]" value="0" /><label><input type="checkbox" name="hp[cta_enabled]" value="1" <?php checked( $hp['cta_enabled'] ); ?> /> <?php esc_html_e( 'Show CTA block', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><label><?php esc_html_e( 'Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[cta_title]" value="<?php echo esc_attr( $hp['cta_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Body Text', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[cta_body]" value="<?php echo esc_attr( $hp['cta_body'] ); ?>" class="large-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Primary Button Text', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[cta_button_text]" value="<?php echo esc_attr( $hp['cta_button_text'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Primary Button URL', 'sender-symposium' ); ?></label></th>
				<td><input type="url" name="hp[cta_button_url]" value="<?php echo esc_attr( $hp['cta_button_url'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Secondary Button Text', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="hp[cta_secondary_text]" value="<?php echo esc_attr( $hp['cta_secondary_text'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Secondary Button URL', 'sender-symposium' ); ?></label></th>
				<td><input type="url" name="hp[cta_secondary_url]" value="<?php echo esc_attr( $hp['cta_secondary_url'] ); ?>" class="regular-text" /></td></tr>
		</table>

		<?php submit_button( __( 'Save Homepage Content', 'sender-symposium' ) ); ?>
	</form>
	<?php
}

/* =========================================================================
   ADMIN ASSETS
   ========================================================================= */

function ss_admin_enqueue( $hook ) {
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	/* Theme settings page — color picker + settings JS + media picker */
	if ( 'toplevel_page_sender-symposium-settings' === $hook ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'ss-admin-settings',
			$uri . '/assets/js/admin-settings.js',
			array( 'wp-color-picker' ),
			ss_asset_version( $dir . '/assets/js/admin-settings.js' ),
			true
		);
		wp_enqueue_media();
		wp_enqueue_script(
			'ss-admin-media-picker',
			$uri . '/assets/js/admin-media-picker.js',
			array( 'jquery' ),
			ss_asset_version( $dir . '/assets/js/admin-media-picker.js' ),
			true
		);
	}

	/* Page editor — media picker for meta box image fields */
	if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
		$screen = get_current_screen();
		if ( $screen && 'page' === $screen->post_type ) {
			wp_enqueue_script(
				'ss-admin-media-picker',
				$uri . '/assets/js/admin-media-picker.js',
				array( 'jquery' ),
				ss_asset_version( $dir . '/assets/js/admin-media-picker.js' ),
				true
			);
			wp_enqueue_script(
				'ss-landing-copyset',
				$uri . '/assets/js/landing-copyset.js',
				array(),
				ss_asset_version( $dir . '/assets/js/landing-copyset.js' ),
				true
			);
		}
	}
}
add_action( 'admin_enqueue_scripts', 'ss_admin_enqueue' );
