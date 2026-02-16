<?php
/**
 * Sender Symposium — Theme Functions
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SS_VERSION', '1.0.0' );

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

	/* --- CSS: layered enqueue (tokens → base → components) --- */
	wp_enqueue_style(
		'ss-tokens',
		$uri . '/assets/css/tokens.css',
		array(),
		filemtime( $dir . '/assets/css/tokens.css' )
	);
	wp_enqueue_style(
		'ss-base',
		$uri . '/assets/css/base.css',
		array( 'ss-tokens' ),
		filemtime( $dir . '/assets/css/base.css' )
	);
	wp_enqueue_style(
		'ss-components',
		$uri . '/assets/css/components.css',
		array( 'ss-base' ),
		filemtime( $dir . '/assets/css/components.css' )
	);

	/* Hero CSS — only on front page or pages using hero template */
	if ( is_front_page() || is_page_template( 'templates/template-hero.php' ) ) {
		wp_enqueue_style(
			'ss-hero',
			$uri . '/assets/css/hero.css',
			array( 'ss-base' ),
			filemtime( $dir . '/assets/css/hero.css' )
		);
	}

	/* --- JS: deferred, no jQuery --- */
	wp_enqueue_script(
		'ss-theme-toggle',
		$uri . '/assets/js/theme-toggle.js',
		array(),
		filemtime( $dir . '/assets/js/theme-toggle.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);

	wp_enqueue_script(
		'ss-navigation',
		$uri . '/assets/js/navigation.js',
		array(),
		filemtime( $dir . '/assets/js/navigation.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);

	/* Deregister jQuery on frontend — not needed */
	if ( ! is_admin() && ! is_customize_preview() ) {
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', false, array(), false, true );
	}
}
add_action( 'wp_enqueue_scripts', 'ss_enqueue_assets' );

/* ==========================================================================
   3. INLINE CRITICAL CSS & THEME BOOTSTRAP (NO FOUC)
   ========================================================================== */

/**
 * Inline tiny script in <head> before any render to apply stored theme.
 * This prevents the flash of wrong color scheme.
 */
function ss_inline_theme_bootstrap() {
	$dark_mode_default = get_option( 'ss_dark_mode_default', 'system' );
	$forced            = '';

	if ( 'light' === $dark_mode_default ) {
		$forced = "'light'";
	} elseif ( 'dark' === $dark_mode_default ) {
		$forced = "'dark'";
	}
	?>
	<script>
	(function(){
		var f=<?php echo $forced ? $forced : 'null'; ?>;
		if(f){document.documentElement.setAttribute('data-theme',f);return;}
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
	$font_file_url = esc_url( $font_file_url );
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
	/* Minimal above-the-fold: background + text color */
	body { background-color: var(--surface-page); color: var(--text-primary); }
	</style>
	<?php
}
add_action( 'wp_head', 'ss_inline_critical_css', 2 );

/* ==========================================================================
   4. ADMIN SETTINGS — CSS VARIABLE OVERRIDES
   ========================================================================== */

/**
 * Output admin-configured CSS variable overrides in wp_head.
 */
function ss_output_custom_properties() {
	$css_lines = array();

	/* Font overrides */
	$font_display = get_option( 'ss_font_display', '' );
	$font_body    = get_option( 'ss_font_body', '' );
	if ( ! empty( $font_display ) ) {
		$css_lines[] = '--font-display: ' . esc_attr( $font_display ) . ';';
	}
	if ( ! empty( $font_body ) ) {
		$css_lines[] = '--font-body: ' . esc_attr( $font_body ) . ';';
	}

	/* Container max width */
	$container_max = get_option( 'ss_container_max', '1200' );
	if ( $container_max !== '1200' ) {
		$css_lines[] = '--container-max: ' . absint( $container_max ) . 'px;';
	}

	/* Section padding scale */
	$padding_scale = get_option( 'ss_section_padding', 'standard' );
	switch ( $padding_scale ) {
		case 'compact':
			$css_lines[] = '--section-padding-block: 64px;';
			break;
		case 'airy':
			$css_lines[] = '--section-padding-block: 128px;';
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
			$css_lines[] = '--' . $css_prop . ': ' . esc_attr( $val ) . ';';
		}
	}

	if ( ! empty( $css_lines ) ) {
		echo '<style id="ss-admin-overrides">:root{' . "\n" . implode( "\n", $css_lines ) . "\n" . '}</style>' . "\n";
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
		$title = esc_attr( get_the_title( $post ) );
		$url   = esc_url( get_permalink( $post ) );
		$desc  = esc_attr( wp_trim_words( get_the_excerpt( $post ), 30, '...' ) );
		$image = '';
		if ( has_post_thumbnail( $post ) ) {
			$image = esc_url( get_the_post_thumbnail_url( $post, 'large' ) );
		}
		echo '<meta property="og:type" content="website" />' . "\n";
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
	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Event',
		'name'        => esc_html( get_bloginfo( 'name' ) ),
		'description' => esc_html( get_bloginfo( 'description' ) ),
		'url'         => esc_url( home_url( '/' ) ),
		'location'    => array(
			'@type'   => 'Place',
			'name'    => 'La Pedrera (Casa Mila)',
			'address' => array(
				'@type'           => 'PostalAddress',
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

/**
 * Declare Elementor support and register locations for Theme Builder.
 */
function ss_elementor_locations( $manager ) {
	$manager->register_all_core_locations();
}
add_action( 'elementor/theme/register_locations', 'ss_elementor_locations' );

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

/* Remove wlwmanifest and RSD links */
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

/* Remove shortlink */
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/* ==========================================================================
   11. INCLUDE ADMIN SETTINGS
   ========================================================================== */

require_once get_template_directory() . '/inc/admin-settings.php';

/* ==========================================================================
   12. HELPER — Check if announcement bar is enabled
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
