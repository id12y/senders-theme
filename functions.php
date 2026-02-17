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

	/* Menu presets — loaded after components so preset classes override base nav */
	wp_enqueue_style(
		'ss-menu-presets',
		$uri . '/assets/css/menu-presets.css',
		array( 'ss-components' ),
		ss_asset_version( $dir . '/assets/css/menu-presets.css' )
	);

	/* Elementor fallback — only when Elementor is not active */
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		wp_enqueue_style(
			'ss-elementor-fallback',
			$uri . '/assets/css/elementor-fallback.css',
			array( 'ss-base' ),
			ss_asset_version( $dir . '/assets/css/elementor-fallback.css' )
		);
	}

	/* Hero Block CSS + optional countdown JS — front page */
	if ( is_front_page() ) {
		wp_enqueue_style(
			'ss-hero-block',
			$uri . '/assets/css/hero-block.css',
			array( 'ss-components' ),
			ss_asset_version( $dir . '/assets/css/hero-block.css' )
		);

		$hero_pid = get_queried_object_id();
		if ( $hero_pid ) {
			$hero_s = ss_get_hero_settings( $hero_pid );
			if ( $hero_s['countdown']['enabled'] && ! empty( $hero_s['countdown']['target_datetime'] ) ) {
				wp_enqueue_script(
					'ss-hero-countdown',
					$uri . '/assets/js/hero-countdown.js',
					array(),
					ss_asset_version( $dir . '/assets/js/hero-countdown.js' ),
					array( 'strategy' => 'defer', 'in_footer' => true )
				);
			}
		}
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

	/* Ticketing CSS + JS — on ticketing page template */
	if ( is_page_template( 'page-templates/template-ticketing.php' ) ) {
		wp_enqueue_style(
			'ss-ticketing',
			$uri . '/assets/css/ticketing.css',
			array( 'ss-components' ),
			ss_asset_version( $dir . '/assets/css/ticketing.css' )
		);

		/* Premium card style CSS (conditional) */
		if ( function_exists( 'ss_get_setting' ) && 'premium' === ss_get_setting( 'card_style' ) ) {
			wp_enqueue_style(
				'ss-premium-overrides',
				$uri . '/assets/css/premium-overrides.css',
				array( 'ss-ticketing' ),
				ss_asset_version( $dir . '/assets/css/premium-overrides.css' )
			);
		}

		/* TicketTailor widget.js from CDN + our init script */
		$tt_settings = ss_get_ticketing_settings( get_the_ID() );
		$tt_mode     = $tt_settings['tickettailor']['embed_method'] ?? 'auto';
		if ( in_array( $tt_mode, array( 'auto', 'widget_js' ), true ) && ! empty( $tt_settings['tickettailor']['event_id'] ) ) {
			wp_enqueue_script(
				'tt-widget',
				'https://cdn.tickettailor.com/js/widgets/min/widget.js',
				array(),
				null,
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
			wp_enqueue_script(
				'ss-tt-init',
				$uri . '/assets/js/tickettailor-init.js',
				array( 'tt-widget' ),
				ss_asset_version( $dir . '/assets/js/tickettailor-init.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
		}
	}

	/* About CSS — on about page template */
	if ( is_page_template( 'page-templates/template-about.php' ) ) {
		wp_enqueue_style(
			'ss-about',
			$uri . '/assets/css/about.css',
			array( 'ss-components' ),
			ss_asset_version( $dir . '/assets/css/about.css' )
		);
	}

	/* Sponsors CSS — on sponsors template, slug match, or shortcode usage */
	if ( is_page_template( 'page-sponsors.php' ) || is_page( 'sponsors' ) || is_page( 'partners' ) || ( is_singular() && has_shortcode( get_post()->post_content ?? '', 'ss_sponsors' ) ) ) {
		wp_enqueue_style(
			'ss-sponsors',
			$uri . '/assets/css/sponsors.css',
			array( 'ss-components' ),
			ss_asset_version( $dir . '/assets/css/sponsors.css' )
		);
	}

	/* FAQ CSS + JS — on FAQ page template */
	if ( is_page_template( 'page-faq.php' ) || is_page( 'faq' ) ) {
		wp_enqueue_style(
			'ss-faq',
			$uri . '/assets/css/faq.css',
			array( 'ss-components' ),
			ss_asset_version( $dir . '/assets/css/faq.css' )
		);
		wp_enqueue_script(
			'ss-faq',
			$uri . '/assets/js/faq.js',
			array(),
			ss_asset_version( $dir . '/assets/js/faq.js' ),
			array( 'strategy' => 'defer', 'in_footer' => true )
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

/**
 * Defer non-critical stylesheets using the print/onload pattern.
 *
 * Converts render-blocking <link> to media="print" with an onload
 * that switches to media="all", plus a <noscript> fallback.
 */
function ss_defer_non_critical_styles( $html, $handle ) {
	$defer = array( 'ss-menu-presets', 'ss-elementor-fallback' );
	if ( ! in_array( $handle, $defer, true ) || is_admin() ) {
		return $html;
	}
	$html     = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html );
	$noscript = '<noscript>' . str_replace( "media='print' onload=\"this.media='all'\"", "media='all'", $html ) . '</noscript>';
	return $html . "\n" . $noscript;
}
add_filter( 'style_loader_tag', 'ss_defer_non_critical_styles', 10, 2 );

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

	/* Font overrides */
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

	/* Site settings: accent colour + premium shadow tokens */
	if ( function_exists( 'ss_get_setting_nonempty' ) ) {
		$accent = ss_get_setting_nonempty( 'accent_color', '' );
		if ( '' !== $accent && preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $accent ) ) {
			$color_lines[] = '--accent-color: ' . $accent . ';';
		}
		if ( 'premium' === ss_get_setting( 'card_style' ) ) {
			$root_lines[] = '--ss-shadow-elevated: 0 10px 30px rgba(0,0,0,.06);';
			$root_lines[] = '--ss-shadow-elevated-dark: 0 10px 30px rgba(0,0,0,.35);';
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
   8. ELEMENTOR COMPATIBILITY (conditional)
   ========================================================================== */

/**
 * Load Elementor integration only when the plugin is active.
 *
 * Hooked to plugins_loaded because ELEMENTOR_VERSION is not defined during
 * after_setup_theme (plugins load after themes).
 */
function ss_maybe_support_elementor() {
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		add_theme_support( 'elementor' );
		require_once get_template_directory() . '/inc/elementor.php';
	}
}
add_action( 'plugins_loaded', 'ss_maybe_support_elementor' );

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
   8d. FAQ MODULE
   ========================================================================== */

require_once get_template_directory() . '/inc/faq/storage.php';

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/faq/admin.php';
}

/* ==========================================================================
   8d2. SITE SETTINGS HELPERS
   Loaded before ticketing/hero so ss_get_setting_nonempty() is available
   when their defaults functions are called.
   ========================================================================== */

require_once get_template_directory() . '/inc/helpers/settings.php';

/* ==========================================================================
   8e. TICKETING MODULE
   ========================================================================== */

require_once get_template_directory() . '/inc/ticketing/meta-box.php';

/* ==========================================================================
   8f. HERO BLOCK MODULE
   ========================================================================== */

require_once get_template_directory() . '/inc/hero/meta-box.php';
require_once get_template_directory() . '/inc/hero/render.php';

/* ==========================================================================
   8g. ABOUT PAGE MODULE
   ========================================================================== */

require_once get_template_directory() . '/inc/about/meta-box.php';

/* ==========================================================================
   8h. SPONSORS MODULE
   ========================================================================== */

require_once get_template_directory() . '/inc/sponsors/storage.php';
require_once get_template_directory() . '/inc/sponsors/display-settings.php';
require_once get_template_directory() . '/inc/sponsors/render.php';
require_once get_template_directory() . '/inc/sponsors/csv-importer.php';

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/sponsors/admin.php';
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
   10. CLEAN UP wp_head OUTPUT
   ========================================================================== */

remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/* ==========================================================================
   11. INCLUDE ADMIN SETTINGS
   ========================================================================== */

require_once get_template_directory() . '/inc/admin-settings.php';

/* ==========================================================================
   11b. SITE SETTINGS ADMIN (Control Panel)
   ========================================================================== */

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin/settings-page.php';
}

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

/**
 * Render a media library picker field for admin meta boxes.
 *
 * @param string $name  The input name attribute.
 * @param int    $value Current attachment ID (0 = none).
 * @param string $label Button label text.
 */
/**
 * Allow font file uploads in the WordPress Media Library.
 */
function ss_allow_font_uploads( $mimes ) {
	$mimes['woff2'] = 'font/woff2';
	$mimes['woff']  = 'font/woff';
	$mimes['ttf']   = 'font/ttf';
	$mimes['otf']   = 'font/otf';
	return $mimes;
}
add_filter( 'upload_mimes', 'ss_allow_font_uploads' );

/**
 * Fix MIME type detection for font files (wp_check_filetype_and_ext may
 * fail on hosts with restrictive finfo — this provides a fallback).
 */
function ss_fix_font_mime_types( $data, $file, $filename, $mimes ) {
	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$font_mimes = array(
		'woff2' => 'font/woff2',
		'woff'  => 'font/woff',
		'ttf'   => 'font/ttf',
		'otf'   => 'font/otf',
	);
	if ( isset( $font_mimes[ $ext ] ) && empty( $data['type'] ) ) {
		$data['ext']  = $ext;
		$data['type'] = $font_mimes[ $ext ];
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'ss_fix_font_mime_types', 10, 4 );

function ss_media_picker( $name, $value, $label = '' ) {
	if ( empty( $label ) ) {
		$label = __( 'Choose Image', 'sender-symposium' );
	}
	$value = absint( $value );
	?>
	<div class="ss-media-picker" data-title="<?php echo esc_attr( $label ); ?>" data-button="<?php esc_attr_e( 'Use this image', 'sender-symposium' ); ?>">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="ss-media-picker__id">
		<div class="ss-media-picker__preview"></div>
		<button type="button" class="button ss-media-picker__choose"><?php echo esc_html( $label ); ?></button>
		<button type="button" class="button-link ss-media-picker__remove" style="display:none;color:#a00;margin-left:8px;"><?php esc_html_e( 'Remove', 'sender-symposium' ); ?></button>
	</div>
	<?php
}

/* ==========================================================================
   13. GEEK LAYER (console easter egg for technically curious visitors)
   ========================================================================== */

if ( ! defined( 'SS_GEEK_LAYER_ENABLED' ) ) {
	define( 'SS_GEEK_LAYER_ENABLED', (bool) ss_get_setting( 'geek_layer_enabled', 1 ) );
}

/**
 * Enqueue the Geek Layer script and pass the feature flag.
 * No DOM manipulation, no network calls, no layout impact.
 */
function ss_enqueue_geek_layer() {
	if ( ! SS_GEEK_LAYER_ENABLED || is_admin() ) {
		return;
	}
	$uri = get_template_directory_uri();
	$dir = get_template_directory();
	wp_enqueue_script(
		'ss-geek-layer',
		$uri . '/assets/js/geek-layer.js',
		array(),
		ss_asset_version( $dir . '/assets/js/geek-layer.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
	wp_add_inline_script(
		'ss-geek-layer',
		'window.SS_GEEK_LAYER_ENABLED=true;',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'ss_enqueue_geek_layer', 20 );

/**
 * Add recommended crawl rules to the WordPress virtual robots.txt.
 *
 * Builds a complete, self-contained block (User-agent + Disallow rules +
 * Sitemap) and prepends it to $output. This guarantees the theme's rules
 * are always under a valid User-agent directive regardless of whether
 * WordPress core, Yoast, or another plugin provides one.
 */
function ss_robots_txt_rules( $output ) {
	$rules  = "User-agent: *\n";
	$rules .= "Disallow: /wp-login.php\n";
	$rules .= "Disallow: /wp-includes/\n";
	$rules .= "Disallow: /wp-content/plugins/\n";
	$rules .= "Disallow: /wp-json/\n";
	$rules .= "Disallow: /?s=\n";
	$rules .= "Disallow: /search/\n";
	$rules .= "\n";
	$rules .= "Sitemap: " . esc_url( home_url( '/sitemap.xml' ) ) . "\n";
	return $rules . "\n" . $output;
}
add_filter( 'robots_txt', 'ss_robots_txt_rules', 10 );

/**
 * Append CRM-themed lines to the WordPress virtual robots.txt.
 */
function ss_geek_layer_robots( $output ) {
	if ( ! SS_GEEK_LAYER_ENABLED ) {
		return $output;
	}
	$output .= "\n";
	$output .= "# ----------------------------------------------------------\n";
	$output .= "# If you are reading this, you probably automate with intent.\n";
	$output .= "# Good bots segment before they crawl.\n";
	$output .= "# The best pipelines are the ones nobody has to babysit.\n";
	$output .= "# emailexpert.com — systems that compound.\n";
	$output .= "# ----------------------------------------------------------\n";
	return $output;
}
add_filter( 'robots_txt', 'ss_geek_layer_robots', 99 );

/* ==========================================================================
   14. HIDE PHP ERRORS ON FRONTEND
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
