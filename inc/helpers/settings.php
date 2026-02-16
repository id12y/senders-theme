<?php
/**
 * Site Settings — Schema, Helpers & Getters
 *
 * Central schema map for `ss_site_settings` and accessor functions.
 * Loaded unconditionally (frontend + admin) because ss_get_setting_nonempty()
 * is called from ss_ticketing_defaults() and ss_hero_defaults().
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   SCHEMA — Single source of truth for all site settings keys
   ========================================================================= */

/**
 * Return the canonical schema for all ss_site_settings keys.
 *
 * Each entry defines:
 *   type        – sanitisation type (text, email, url, wysiwyg, int, date, hex, select, checkbox)
 *   default     – value used when the key has never been saved
 *   tab         – admin tab slug (used by schema-driven renderer)
 *   label       – human-readable field label
 *   description – helper text shown below the field
 *   allowed     – (select only) whitelist of valid values
 *
 * @return array<string, array>
 */
function ss_site_settings_schema() {
	return array(
		'_schema_version' => array(
			'type'    => 'int',
			'default' => 1,
			'tab'     => '',
			'label'   => '',
		),

		/* ── Branding ──
		 * Logo fields intentionally omitted — canonical sources are:
		 *   Light logo → Customizer (custom_logo theme mod)
		 *   Dark logo  → General tab (ss_logo_dark_id option)
		 * Any previously saved brand_logo_light / brand_logo_dark values
		 * remain in the DB but are no longer rendered or sanitised.
		 */
		'brand_wordmark_alt' => array(
			'type'        => 'text',
			'default'     => '',
			'tab'         => 'site_branding',
			'label'       => __( 'Wordmark Alt Text', 'sender-symposium' ),
			'description' => __( 'Alternative text for the brand wordmark image.', 'sender-symposium' ),
		),
		'primary_contact_email' => array(
			'type'        => 'email',
			'default'     => '',
			'tab'         => 'site_branding',
			'label'       => __( 'Primary Contact Email', 'sender-symposium' ),
			'description' => __( 'Displayed in footer, contact pages, and schema.', 'sender-symposium' ),
		),
		'primary_contact_url' => array(
			'type'        => 'url',
			'default'     => '',
			'tab'         => 'site_branding',
			'label'       => __( 'Primary Contact URL', 'sender-symposium' ),
			'description' => __( 'Link to contact page or form.', 'sender-symposium' ),
		),
		'footer_text_line_1' => array(
			'type'        => 'inline_html',
			'default'     => '',
			'tab'         => 'site_branding',
			'label'       => __( 'Footer Text — Line 1', 'sender-symposium' ),
			'description' => __( 'Optional line above the copyright notice. HTML allowed (links, bold, etc.).', 'sender-symposium' ),
		),
		'footer_text_line_2' => array(
			'type'        => 'inline_html',
			'default'     => '',
			'tab'         => 'site_branding',
			'label'       => __( 'Footer Text — Line 2', 'sender-symposium' ),
			'description' => __( 'Optional second line above the copyright notice. HTML allowed.', 'sender-symposium' ),
		),

		/* ── Event Defaults ── */
		'event_name' => array(
			'type'        => 'text',
			'default'     => 'Sender Symposium Barcelona',
			'tab'         => 'site_event',
			'label'       => __( 'Event Name', 'sender-symposium' ),
			'description' => __( 'Used in hero, ticketing header, and Schema.org.', 'sender-symposium' ),
		),
		'event_date' => array(
			'type'        => 'date',
			'default'     => '',
			'tab'         => 'site_event',
			'label'       => __( 'Event Date', 'sender-symposium' ),
			'description' => __( 'Stored as YYYY-MM-DD. Used to derive display date in hero key facts.', 'sender-symposium' ),
		),
		'event_start_time' => array(
			'type'        => 'text',
			'default'     => '9:00 AM',
			'tab'         => 'site_event',
			'label'       => __( 'Event Start Time', 'sender-symposium' ),
			'description' => __( 'Display format, e.g. "9:00 AM".', 'sender-symposium' ),
		),
		'event_end_time' => array(
			'type'        => 'text',
			'default'     => '7:00 PM',
			'tab'         => 'site_event',
			'label'       => __( 'Event End Time', 'sender-symposium' ),
			'description' => __( 'Display format, e.g. "7:00 PM".', 'sender-symposium' ),
		),
		'event_timezone' => array(
			'type'        => 'text',
			'default'     => 'CEST',
			'tab'         => 'site_event',
			'label'       => __( 'Event Timezone', 'sender-symposium' ),
			'description' => __( 'Display label, e.g. "CEST". Not used for time conversion.', 'sender-symposium' ),
		),
		'event_city' => array(
			'type'        => 'text',
			'default'     => 'Barcelona',
			'tab'         => 'site_event',
			'label'       => __( 'City', 'sender-symposium' ),
			'description' => __( 'Used in hero key facts as "City, Country".', 'sender-symposium' ),
		),
		'event_country' => array(
			'type'        => 'text',
			'default'     => 'Spain',
			'tab'         => 'site_event',
			'label'       => __( 'Country', 'sender-symposium' ),
			'description' => '',
		),
		'event_venue_name' => array(
			'type'        => 'text',
			'default'     => 'La Pedrera (Casa Milà)',
			'tab'         => 'site_event',
			'label'       => __( 'Venue Name', 'sender-symposium' ),
			'description' => __( 'Full venue name shown in hero key facts.', 'sender-symposium' ),
		),
		'event_venue_short' => array(
			'type'        => 'text',
			'default'     => 'Casa Mila, La Pedrera',
			'tab'         => 'site_event',
			'label'       => __( 'Venue Short Name', 'sender-symposium' ),
			'description' => __( 'Compact venue name for space-constrained contexts.', 'sender-symposium' ),
		),
		'event_capacity' => array(
			'type'        => 'int',
			'default'     => 120,
			'tab'         => 'site_event',
			'label'       => __( 'Event Capacity', 'sender-symposium' ),
			'description' => __( 'Maximum attendees. Used in scarcity messaging.', 'sender-symposium' ),
		),

		/* ── Ticketing Defaults ── */
		'ticketing_journey_step_1' => array(
			'type'        => 'text',
			'default'     => 'Ticket',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Journey Step 1 Label', 'sender-symposium' ),
			'description' => __( 'Checkout progress bar — step 1. Overridden by page meta box.', 'sender-symposium' ),
		),
		'ticketing_journey_step_2' => array(
			'type'        => 'text',
			'default'     => 'Information',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Journey Step 2 Label', 'sender-symposium' ),
			'description' => __( 'Checkout progress bar — step 2.', 'sender-symposium' ),
		),
		'ticketing_journey_step_3' => array(
			'type'        => 'text',
			'default'     => 'Confirm',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Journey Step 3 Label', 'sender-symposium' ),
			'description' => __( 'Checkout progress bar — step 3.', 'sender-symposium' ),
		),
		'ticketing_security_label' => array(
			'type'        => 'text',
			'default'     => 'Secured checkout',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Security / Trust Badge Label', 'sender-symposium' ),
			'description' => __( 'Shown next to the lock icon in the journey bar.', 'sender-symposium' ),
		),
		'ticketing_rightcard_1_title' => array(
			'type'        => 'text',
			'default'     => '',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Right Column Card 1 — Title', 'sender-symposium' ),
			'description' => __( 'Maps to the info card heading in the ticketing sidebar.', 'sender-symposium' ),
		),
		'ticketing_rightcard_1_body' => array(
			'type'        => 'wysiwyg',
			'default'     => '',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Right Column Card 1 — Body', 'sender-symposium' ),
			'description' => __( 'Rich text body for the info card.', 'sender-symposium' ),
		),
		'ticketing_rightcard_2_title' => array(
			'type'        => 'text',
			'default'     => '',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Right Column Card 2 — Title', 'sender-symposium' ),
			'description' => __( 'Maps to the "Why Different" block title in the ticketing sidebar.', 'sender-symposium' ),
		),
		'ticketing_rightcard_2_body' => array(
			'type'        => 'wysiwyg',
			'default'     => '',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Right Column Card 2 — Body', 'sender-symposium' ),
			'description' => __( 'Rich text body for the second sidebar card.', 'sender-symposium' ),
		),
		'ticketing_footer_help_text' => array(
			'type'        => 'text',
			'default'     => 'Need help or booking for a team? Contact us.',
			'tab'         => 'site_ticketing',
			'label'       => __( 'Footer Help Text', 'sender-symposium' ),
			'description' => __( 'Reassurance line below the ticketing layout.', 'sender-symposium' ),
		),

		/* ── Design Defaults ── */
		'accent_color' => array(
			'type'        => 'hex',
			'default'     => '',
			'tab'         => 'site_design',
			'label'       => __( 'Accent Colour', 'sender-symposium' ),
			'description' => __( 'Applied as --accent-color CSS variable. Leave blank for theme default.', 'sender-symposium' ),
		),
		'surface_mode' => array(
			'type'        => 'select',
			'default'     => 'theme-default',
			'tab'         => 'site_design',
			'label'       => __( 'Surface Mode', 'sender-symposium' ),
			'description' => __( 'Override the default light/dark behaviour. "Theme default" defers to the Dark Mode tab.', 'sender-symposium' ),
			'allowed'     => array( 'theme-default', 'force-light', 'force-dark' ),
		),
		'card_style' => array(
			'type'        => 'select',
			'default'     => 'theme-default',
			'tab'         => 'site_design',
			'label'       => __( 'Card Style', 'sender-symposium' ),
			'description' => __( 'Premium adds elevated shadows and accent borders to ticketing sidebar cards.', 'sender-symposium' ),
			'allowed'     => array( 'theme-default', 'premium' ),
		),

		/* ── Navigation ── */
		'nav_preset' => array(
			'type'        => 'select',
			'default'     => 'baseline',
			'tab'         => 'site_navigation',
			'label'       => __( 'Menu Preset', 'sender-symposium' ),
			'description' => __( 'Visual style applied to the primary navigation.', 'sender-symposium' ),
			'allowed'     => array( 'baseline', 'gaudi', 'modern-lite', 'modern-plus', 'signature' ),
		),
		'nav_density' => array(
			'type'        => 'select',
			'default'     => 'comfortable',
			'tab'         => 'site_navigation',
			'label'       => __( 'Density', 'sender-symposium' ),
			'description' => __( 'Spacing between menu items.', 'sender-symposium' ),
			'allowed'     => array( 'compact', 'comfortable' ),
		),
		'nav_radius' => array(
			'type'        => 'select',
			'default'     => 'm',
			'tab'         => 'site_navigation',
			'label'       => __( 'Border Radius', 'sender-symposium' ),
			'description' => __( 'Corner rounding for dropdown panels and hover backgrounds.', 'sender-symposium' ),
			'allowed'     => array( 's', 'm', 'l' ),
		),
		'nav_underline' => array(
			'type'        => 'select',
			'default'     => 'off',
			'tab'         => 'site_navigation',
			'label'       => __( 'Underline', 'sender-symposium' ),
			'description' => __( 'Show underline on active and hovered menu items.', 'sender-symposium' ),
			'allowed'     => array( 'on', 'off' ),
		),
		'nav_animation' => array(
			'type'        => 'select',
			'default'     => 'subtle',
			'tab'         => 'site_navigation',
			'label'       => __( 'Animation', 'sender-symposium' ),
			'description' => __( 'Transition effects on hover and dropdown open.', 'sender-symposium' ),
			'allowed'     => array( 'off', 'subtle' ),
		),
		'nav_indicator' => array(
			'type'        => 'select',
			'default'     => 'caret',
			'tab'         => 'site_navigation',
			'label'       => __( 'Submenu Indicator', 'sender-symposium' ),
			'description' => __( 'Icon style for parent items with submenus.', 'sender-symposium' ),
			'allowed'     => array( 'caret', 'plus' ),
		),

		/* ── Integrations ── */
		'tt_mode' => array(
			'type'        => 'select',
			'default'     => 'none',
			'tab'         => 'site_integrations',
			'label'       => __( 'Ticket Tailor Mode', 'sender-symposium' ),
			'description' => __( 'Global fallback embed method. Script embed is recommended; shortcode may redirect on multi-step checkouts. Per-page settings always override.', 'sender-symposium' ),
			'allowed'     => array( 'none', 'script_embed', 'shortcode' ),
		),
		'tt_checkout_url' => array(
			'type'        => 'url',
			'default'     => '',
			'tab'         => 'site_integrations',
			'label'       => __( 'Ticket Tailor Checkout URL', 'sender-symposium' ),
			'description' => __( 'Required for script embed mode. Full URL to your Ticket Tailor checkout page.', 'sender-symposium' ),
		),
		'tt_shortcode' => array(
			'type'        => 'text',
			'default'     => '',
			'tab'         => 'site_integrations',
			'label'       => __( 'Ticket Tailor Shortcode', 'sender-symposium' ),
			'description' => __( 'Required for shortcode mode. e.g. [ticket_tailor id="..."]', 'sender-symposium' ),
		),
		'tt_ref_param' => array(
			'type'        => 'text',
			'default'     => 'ssweb',
			'tab'         => 'site_integrations',
			'label'       => __( 'Referral Parameter', 'sender-symposium' ),
			'description' => __( 'Appended to Ticket Tailor URLs for tracking.', 'sender-symposium' ),
		),
		'tt_allow_fallback' => array(
			'type'        => 'checkbox',
			'default'     => 1,
			'tab'         => 'site_integrations',
			'label'       => __( 'Allow Fallback', 'sender-symposium' ),
			'description' => __( 'If the primary embed method fails, attempt the alternative.', 'sender-symposium' ),
		),
	);
}

/* =========================================================================
   GETTERS
   ========================================================================= */

/**
 * Key-exists getter.
 *
 * Returns the stored value even if it is an empty string — falls back to
 * $default only when the key is entirely absent from the saved option.
 *
 * Use this when empty string is a meaningful value (admin intentionally cleared).
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback when key is absent.
 * @return mixed
 */
function ss_get_setting( $key, $default = null ) {
	static $cache = null;
	if ( null === $cache ) {
		$cache = get_option( 'ss_site_settings', array() );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
	}
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}
	return $default;
}

/**
 * Alias of ss_get_setting() for readability.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback when key is absent.
 * @return mixed
 */
function ss_get_setting_allow_empty( $key, $default = null ) {
	return ss_get_setting( $key, $default );
}

/**
 * Non-empty getter.
 *
 * Returns the stored value only if it is non-empty (after trimming for strings).
 * Falls back to $default when the key is absent, null, or whitespace-only string.
 *
 * Use this when an empty field should mean "use the hardcoded default".
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback when value is empty.
 * @return mixed
 */
function ss_get_setting_nonempty( $key, $default = null ) {
	static $cache = null;
	if ( null === $cache ) {
		$cache = get_option( 'ss_site_settings', array() );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
	}
	if ( ! array_key_exists( $key, $cache ) ) {
		return $default;
	}
	$val = $cache[ $key ];
	if ( null === $val ) {
		return $default;
	}
	/* Trim strings: a space-only field should be treated as empty */
	if ( is_string( $val ) ) {
		$val = trim( $val );
		if ( '' === $val ) {
			return $default;
		}
	}
	/* Integer 0 is valid (e.g. attachment ID "no logo") — only treat as empty for non-int fields */
	return $val;
}

/* =========================================================================
   SCHEMA MIGRATOR STUB
   ========================================================================= */

/**
 * Lightweight schema version check.
 * Future migrations go in the switch-case below.
 */
function ss_site_maybe_migrate() {
	$settings = get_option( 'ss_site_settings', array() );
	if ( empty( $settings ) || ! is_array( $settings ) ) {
		return; /* Option not created yet — nothing to migrate. */
	}
	$version = isset( $settings['_schema_version'] ) ? (int) $settings['_schema_version'] : 0;
	if ( $version >= 1 ) {
		return; /* Already current. */
	}
	/* Version 0 → 1: initial schema stamp. */
	$settings['_schema_version'] = 1;
	update_option( 'ss_site_settings', $settings );
}
add_action( 'admin_init', 'ss_site_maybe_migrate' );
