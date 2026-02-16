<?php
/**
 * Sender Symposium — Theme Functions
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SS_VERSION' ) ) {
	define( 'SS_VERSION', '1.0.0' );
}

/**
 * Helper: get asset file version with fallback if file is missing.
 */
function ss_asset_version( $path ) {
	return file_exists( $path ) ? filemtime( $path ) : SS_VERSION;
}

/* ==========================================================================
   1. THEME SETUP
   ========================================================================== */

function ss_theme_setup() {
	/* Translation support */
	load_theme_textdomain( 'sender-symposium', get_template_directory() . '/languages' );

	/* HTML5 markup */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	/* Title tag */
	add_theme_support( 'title-tag' );

	/* Post thumbnails */
	add_theme_support( 'post-thumbnails' );

	/* Custom logo */
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	/* Register nav menus */
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Navigation', 'sender-symposium' ),
		'footer'  => esc_html__( 'Footer Navigation', 'sender-symposium' ),
	) );

	/* Content width */
	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1200;
	}

	/* Elementor Theme Builder support */
	add_theme_support( 'elementor' );

	/* Responsive embeds */
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'ss_theme_setup' );

/* ==========================================================================
   2. ENQUEUE STYLES & SCRIPTS
   ========================================================================== */

function ss_enqueue_assets() {
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	/* --- CSS: layered enqueue (tokens > base > components) --- */
	wp_enqueue_style(
		'ss-tokens',
		$uri . '/assets/css/tokens.css',
		array(),
		ss_asset_version( $dir . '/assets/css/tokens.css' )
	);
	wp_enqueue_style(
		'ss-base',
		$uri . '/assets/css/base.css',
		array( 'ss-tokens' ),
		ss_asset_version( $dir . '/assets/css/base.css' )
	);
	wp_enqueue_style(
		'ss-components',
		$uri . '/assets/css/components.css',
		array( 'ss-base' ),
		ss_asset_version( $dir . '/assets/css/components.css' )
	);

	/* Hero CSS — only on pages using the hero template */
	if ( is_page_template( 'templates/template-hero.php' ) ) {
		wp_enqueue_style(
			'ss-hero',
			$uri . '/assets/css/hero.css',
			array( 'ss-base' ),
			ss_asset_version( $dir . '/assets/css/hero.css' )
		);
	}

	/* Speakers CSS — on speakers page template */
	if ( is_page_template( 'page-speakers.php' ) || is_page( 'speakers' ) ) {
		wp_enqueue_style(
			'ss-speakers',
			$uri . '/assets/css/speakers.css',
			array( 'ss-components' ),
			ss_asset_version( $dir . '/assets/css/speakers.css' )
		);
	}

	/* --- JS: deferred, vanilla JS (no jQuery dependency) --- */
	wp_enqueue_script(
		'ss-theme-toggle',
		$uri . '/assets/js/theme-toggle.js',
		array(),
		ss_asset_version( $dir . '/assets/js/theme-toggle.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);

	wp_enqueue_script(
		'ss-navigation',
		$uri . '/assets/js/navigation.js',
		array(),
		ss_asset_version( $dir . '/assets/js/navigation.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);

	/*
	 * NOTE: We do NOT deregister jQuery. Plugins like Elementor, contact form
	 * plugins, and others depend on it. The theme itself does not use jQuery
	 * on the frontend, but we must not break plugin compatibility.
	 */
}
add_action( 'wp_enqueue_scripts', 'ss_enqueue_assets' );

/* ==========================================================================
   3. INLINE CRITICAL CSS & THEME BOOTSTRAP (NO FOUC)
   ========================================================================== */

/**
 * Inline tiny script in <head> before any render to apply stored theme.
 * This prevents the flash of wrong color scheme.
 *
 * When admin forces a mode, we set data-theme-forced on <html> so the
 * deferred theme-toggle.js knows not to override it.
 */
function ss_inline_theme_bootstrap() {
	$dark_mode_default = get_option( 'ss_dark_mode_default', 'system' );
	$is_forced         = in_array( $dark_mode_default, array( 'light', 'dark' ), true );
	$forced_value      = $is_forced ? $dark_mode_default : '';
	?>
	<script>
	(function(){
		var f=<?php echo $is_forced ? wp_json_encode( $forced_value ) : 'null'; ?>;
		if(f){document.documentElement.setAttribute('data-theme',f);document.documentElement.setAttribute('data-theme-forced','');return;}
		var s;try{s=localStorage.getItem('ss-theme')}catch(e){}
		if(s==='dark'||s==='light'){document.documentElement.setAttribute('data-theme',s);return;}
		var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';
		document.documentElement.setAttribute('data-theme',d);
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'ss_inline_theme_bootstrap', 1 );

/**
 * Inline critical CSS: font-face + above-the-fold essentials.
 */
function ss_inline_critical_css() {
	$font_file_url = get_option( 'ss_font_file_url', '' );
	if ( empty( $font_file_url ) ) {
		$font_file_url = get_template_directory_uri() . '/assets/fonts/Barcelona-Variable.woff2';
	}
	/* Use esc_url_raw(): <style> is "raw text" in HTML5, entities are NOT
	   decoded, so esc_url()'s &#038; would break CSS url() for any URL with &. */
	$font_file_url = esc_url_raw( $font_file_url );
	?>
	<style id="ss-critical">
	@font-face {
		font-family: "Barcelona Variable";
		src: url("<?php echo $font_file_url; ?>") format("woff2");
		font-weight: 100 900;
		font-display: swap;
		font-style: normal;
	}
	/* Prevent FOUC: hide body until theme attribute is set */
	html:not([data-theme]) body { visibility: hidden; }
	html[data-theme] body { visibility: visible; }
	/* Minimal above-the-fold: background + text color with fallbacks */
	body { background-color: var(--surface-page, #F4F1EB); color: var(--text-primary, #1F1F1D); }
	</style>
	<?php
}
add_action( 'wp_head', 'ss_inline_critical_css', 2 );

/* ==========================================================================
   4. ADMIN SETTINGS — CSS VARIABLE OVERRIDES
   ========================================================================== */

/**
 * Output admin-configured CSS variable overrides in wp_head.
 *
 * Color overrides target [data-theme="light"] and [data-theme="dark"]
 * to beat the specificity of the palette definitions in tokens.css.
 * Non-color overrides (fonts, layout) target :root.
 */
function ss_output_custom_properties() {
	$root_lines  = array();
	$color_lines = array();

	/* Font overrides — validate against CSS injection */
	$font_display = get_option( 'ss_font_display', '' );
	$font_body    = get_option( 'ss_font_body', '' );
	if ( ! empty( $font_display ) && preg_match( '/^[a-zA-Z0-9\s,"\'\-\.]+$/', $font_display ) ) {
		$root_lines[] = '--font-display: ' . $font_display . ';';
	}
	if ( ! empty( $font_body ) && preg_match( '/^[a-zA-Z0-9\s,"\'\-\.]+$/', $font_body ) ) {
		$root_lines[] = '--font-body: ' . $font_body . ';';
	}

	/* Logo max height */
	$logo_height = get_option( 'ss_logo_max_height', '40' );
	if ( '40' !== $logo_height ) {
		$root_lines[] = '--logo-max-height: ' . absint( $logo_height ) . 'px;';
	}

	/* Container max width */
	$container_max = get_option( 'ss_container_max', '1200' );
	if ( '1200' !== $container_max ) {
		$root_lines[] = '--container-max: ' . absint( $container_max ) . 'px;';
	}

	/* Section padding scale */
	$padding_scale = get_option( 'ss_section_padding', 'standard' );
	switch ( $padding_scale ) {
		case 'compact':
			$root_lines[] = '--section-padding-block: 64px;';
			break;
		case 'airy':
			$root_lines[] = '--section-padding-block: 128px;';
			break;
	}

	/* Color token overrides */
	$token_map = array(
		'surface_page'         => 'surface-page',
		'surface_section'      => 'surface-section',
		'surface_card'         => 'surface-card',
		'text_primary'         => 'text-primary',
		'text_secondary'       => 'text-secondary',
		'action_primary_bg'    => 'action-primary-bg',
		'action_primary_hover' => 'action-primary-hover',
		'action_primary_text'  => 'action-primary-text',
		'link_default'         => 'link-default',
		'link_hover'           => 'link-hover',
		'focus_ring'           => 'focus-ring',
	);

	foreach ( $token_map as $option_suffix => $css_prop ) {
		$val = get_option( 'ss_color_' . $option_suffix, '' );
		if ( ! empty( $val ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $val ) ) {
			$color_lines[] = '--' . $css_prop . ': ' . $val . ';';
		}
	}

	$output = '';
	if ( ! empty( $root_lines ) ) {
		$output .= ':root{' . "\n" . implode( "\n", $root_lines ) . "\n" . '}' . "\n";
	}
	if ( ! empty( $color_lines ) ) {
		$joined = implode( "\n", $color_lines );
		/* Target both theme attribute selectors to beat tokens.css specificity */
		$output .= '[data-theme="light"]{' . "\n" . $joined . "\n" . '}' . "\n";
		$output .= '[data-theme="dark"]{' . "\n" . $joined . "\n" . '}' . "\n";
	}

	if ( ! empty( $output ) ) {
		echo '<style id="ss-admin-overrides">' . "\n" . $output . '</style>' . "\n";
	}
}
add_action( 'wp_head', 'ss_output_custom_properties', 3 );

/* ==========================================================================
   5. BODY CLASSES
   ========================================================================== */

function ss_body_classes( $classes ) {
	$classes[] = 'ss-theme';

	if ( is_front_page() ) {
		$classes[] = 'ss-front-page';
	}

	/* Elementor canvas detection */
	if ( is_page_template( 'templates/template-canvas.php' ) ) {
		$classes[] = 'ss-canvas';
	}

	/* Dark mode forced state */
	$dm = get_option( 'ss_dark_mode_default', 'system' );
	if ( 'dark' === $dm ) {
		$classes[] = 'ss-forced-dark';
	} elseif ( 'light' === $dm ) {
		$classes[] = 'ss-forced-light';
	}

	return $classes;
}
add_filter( 'body_class', 'ss_body_classes' );

/* ==========================================================================
   6. OPEN GRAPH DEFAULTS
   ========================================================================== */

function ss_open_graph_meta() {
	if ( is_singular() ) {
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		$og_type = is_single() ? 'article' : 'website';
		$title   = the_title_attribute( array( 'echo' => false, 'post' => $post ) );
		$url     = esc_url( get_permalink( $post ) );
		$desc    = esc_attr( wp_trim_words( get_the_excerpt( $post ), 30, '...' ) );
		$image   = '';
		if ( has_post_thumbnail( $post ) ) {
			$image = esc_url( get_the_post_thumbnail_url( $post, 'large' ) );
		}
		echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '" />' . "\n";
		echo '<meta property="og:title" content="' . $title . '" />' . "\n";
		echo '<meta property="og:url" content="' . $url . '" />' . "\n";
		if ( $desc ) {
			echo '<meta property="og:description" content="' . $desc . '" />' . "\n";
		}
		if ( $image ) {
			echo '<meta property="og:image" content="' . $image . '" />' . "\n";
		}
	}
}
add_action( 'wp_head', 'ss_open_graph_meta', 5 );

/* ==========================================================================
   7. EVENT SCHEMA SUPPORT (optional)
   ========================================================================== */

function ss_event_schema() {
	if ( ! is_front_page() ) {
		return;
	}
	$start_date = get_option( 'ss_event_start_date', '' );
	if ( empty( $start_date ) ) {
		/* No date configured — skip schema to avoid invalid structured data */
		return;
	}
	$schema = array(
		'@context'              => 'https://schema.org',
		'@type'                 => 'Event',
		'name'                  => esc_html( get_bloginfo( 'name' ) ),
		'description'           => esc_html( get_bloginfo( 'description' ) ),
		'url'                   => esc_url( home_url( '/' ) ),
		'startDate'             => $start_date,
		'eventStatus'           => 'https://schema.org/EventScheduled',
		'eventAttendanceMode'   => 'https://schema.org/OfflineEventAttendanceMode',
		'location'              => array(
			'@type'   => 'Place',
			'name'    => 'La Pedrera (Casa Mila)',
			'address' => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Passeig de Gracia, 92',
				'addressLocality' => 'Barcelona',
				'addressCountry'  => 'ES',
			),
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'ss_event_schema', 10 );

/* ==========================================================================
   8. ELEMENTOR COMPATIBILITY
   ========================================================================== */

require_once get_template_directory() . '/inc/elementor.php';

/* ==========================================================================
   8b. HOMEPAGE CONTENT SETTINGS
   ========================================================================== */

require_once get_template_directory() . '/inc/homepage-settings.php';

/* ==========================================================================
   8c. SPEAKERS MODULE
   ========================================================================== */

require_once get_template_directory() . '/inc/speakers/storage.php';
require_once get_template_directory() . '/inc/speakers/display-settings.php';
require_once get_template_directory() . '/inc/speakers/render.php';
require_once get_template_directory() . '/inc/speakers/csv-importer.php';

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/speakers/admin.php';
}

/* ==========================================================================
   9. WIDGET AREAS
   ========================================================================== */

function ss_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Widget Area', 'sender-symposium' ),
		'id'            => 'footer-widgets',
		'before_widget' => '<div class="footer-widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="footer-col__title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'ss_widgets_init' );

/* ==========================================================================
   10. SECURITY — Clean up wp_head output
   ========================================================================== */

/* Remove WordPress version from head and feeds */
remove_action( 'wp_head', 'wp_generator' );

/* Remove shortlink */
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/* ==========================================================================
   11. INCLUDE ADMIN SETTINGS
   ========================================================================== */

require_once get_template_directory() . '/inc/admin-settings.php';

/* ==========================================================================
   12. HELPER FUNCTIONS
   ========================================================================== */

function ss_announcement_enabled() {
	return get_option( 'ss_announcement_enabled', 'off' ) === 'on'
		&& ! empty( get_option( 'ss_announcement_text', '' ) );
}

/**
 * Check if the theme toggle should be displayed.
 */
function ss_show_theme_toggle() {
	return get_option( 'ss_dark_mode_toggle', 'on' ) === 'on';
}

/**
 * Check if hero architectural field is enabled.
 */
function ss_show_hero_field() {
	return get_option( 'ss_hero_field', 'on' ) === 'on';
}

/**
 * Convert a newline-delimited string into a trimmed array (empty lines removed).
 */
function ss_lines_to_array( $text ) {
	if ( empty( $text ) ) {
		return array();
	}
	return array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ), 'strlen' ) );
}

/* ==========================================================================
   13. HIDE PHP ERRORS ON FRONTEND
   ========================================================================== */

/**
 * Suppress PHP notices/warnings on the frontend when the admin option is on.
 * Errors are still logged to the server error log.
 * Runs at priority 0 on template_redirect so it catches plugin output.
 */
function ss_maybe_hide_frontend_errors() {
	if ( is_admin() ) {
		return;
	}
	if ( get_option( 'ss_hide_php_errors', 'on' ) !== 'on' ) {
		return;
	}
	@ini_set( 'display_errors', '0' );
}
add_action( 'template_redirect', 'ss_maybe_hide_frontend_errors', 0 );
