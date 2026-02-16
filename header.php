<?php
/**
 * Header template — Sender Symposium
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">
	<?php esc_html_e( 'Skip to content', 'sender-symposium' ); ?>
</a>

<?php
/* Announcement bar */
if ( ss_announcement_enabled() ) {
	get_template_part( 'parts/announcement-bar' );
}
?>

<?php
/*
 * Allow Elementor Theme Builder to override the header.
 * If Elementor has a header template, it will render here and skip the default.
 */
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) :
?>
<header class="site-header" role="banner">
	<div class="container site-header__inner">

		<?php
		$dark_logo_id = absint( get_option( 'ss_logo_dark_id', 0 ) );
		if ( has_custom_logo() ) :
			if ( $dark_logo_id ) :
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				$logo_light_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
				$logo_dark_url  = wp_get_attachment_image_url( $dark_logo_id, 'full' );
				$site_name      = get_bloginfo( 'name' );
			?>
			<div class="site-logo-wrap">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="custom-logo-link" rel="home">
					<img class="custom-logo ss-logo-light"
						src="<?php echo esc_url( $logo_light_url ); ?>"
						alt="<?php echo esc_attr( $site_name ); ?>">
					<img class="custom-logo ss-logo-dark"
						src="<?php echo esc_url( $logo_dark_url ); ?>"
						alt="<?php echo esc_attr( $site_name ); ?>">
				</a>
			</div>
			<?php else : ?>
			<div class="site-logo-wrap">
				<?php the_custom_logo(); ?>
			</div>
			<?php endif; ?>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" rel="home">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			</a>
		<?php endif; ?>

		<button
			class="menu-toggle"
			aria-controls="primary-nav"
			aria-expanded="false"
			aria-label="<?php esc_attr_e( 'Toggle navigation', 'sender-symposium' ); ?>"
		>
			<span class="menu-toggle__bar"></span>
			<span class="menu-toggle__bar"></span>
			<span class="menu-toggle__bar"></span>
		</button>

		<nav id="primary-nav" class="site-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'sender-symposium' ); ?>" data-open="false">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'depth'          => 2,
					'link_before'    => '<span class="site-nav__link-text">',
					'link_after'     => '</span>',
					'fallback_cb'    => false,
				) );
			} else {
				echo '<ul class="site-nav__list">';
				echo '<li><a href="' . esc_url( home_url( '/' ) ) . '" class="site-nav__link">' . esc_html__( 'Home', 'sender-symposium' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</nav>

		<?php if ( ss_show_theme_toggle() ) : ?>
			<button
				class="theme-toggle"
				type="button"
				aria-pressed="false"
				aria-label="<?php esc_attr_e( 'Toggle dark mode', 'sender-symposium' ); ?>"
			>
				<svg class="theme-toggle__sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="5"></circle>
					<line x1="12" y1="1" x2="12" y2="3"></line>
					<line x1="12" y1="21" x2="12" y2="23"></line>
					<line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
					<line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
					<line x1="1" y1="12" x2="3" y2="12"></line>
					<line x1="21" y1="12" x2="23" y2="12"></line>
					<line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
					<line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
				</svg>
				<svg class="theme-toggle__moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
				</svg>
			</button>
		<?php endif; ?>

	</div>
</header>
<?php endif; ?>

<main id="main-content" role="main">
