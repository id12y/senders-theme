<?php
/**
 * Hero Block — Meta Box & Settings
 *
 * Stores hero settings as a single structured post-meta array:
 *   ss_hero_settings
 *
 * Architecture matches ticketing module: one meta key, nonce + capability
 * checks, escape on output.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ═══════════════════════════════════════════════════════════
   DEFAULTS
   ═══════════════════════════════════════════════════════════ */

function ss_hero_defaults() {
	return array(
		'enabled'     => true,
		'mode'        => 'simple',
		'eyebrow'     => __( 'Sender Symposium Barcelona', 'sender-symposium' ),
		'headline'    => __( 'A working conference for teams who own lifecycle messaging.', 'sender-symposium' ),
		'subheadline' => __( 'Leadership-level, high-interaction sessions for CRM, retention, Growth, Marketing Ops, and platform owners.', 'sender-symposium' ),
		'body'        => '<p>Short talks, hard cases, extended Q&amp;A, and practical labs—built for decision-makers accountable for revenue and retention.</p>',
		'primary_cta' => array(
			'label' => __( 'Secure your seat', 'sender-symposium' ),
			'url'   => '/tickets/',
			'style' => 'solid',
		),
		'secondary_cta' => array(
			'enabled' => true,
			'label'   => __( 'View agenda', 'sender-symposium' ),
			'url'     => '/agenda/',
			'style'   => 'outline',
		),
		'key_facts' => array(
			'enabled'  => true,
			'date'     => '24 April 2026',
			'location' => 'Barcelona, Spain',
			'venue'    => 'La Pedrera (Casa Milà)',
			'format'   => 'High-interaction · Limited seats',
		),
		'microcopy' => __( 'Tickets are intentionally limited.', 'sender-symposium' ),
		'logo' => array(
			'enabled'       => false,
			'image_id'      => 0,
			'dark_image_id' => 0,
		),
		'background' => array(
			'image_id'         => 0,
			'overlay_strength' => 'medium',
		),
		'layout' => array(
			'alignment'     => 'center',
			'content_width' => 'standard',
			'spacing'       => 'standard',
		),
		'countdown' => array(
			'enabled'          => false,
			'target_datetime'  => '',
		),
	);
}

/* ═══════════════════════════════════════════════════════════
   GET SETTINGS (deep-merged with defaults)
   ═══════════════════════════════════════════════════════════ */

function ss_get_hero_settings( $post_id ) {
	$saved    = get_post_meta( $post_id, 'ss_hero_settings', true );
	$defaults = ss_hero_defaults();

	if ( empty( $saved ) || ! is_array( $saved ) ) {
		return $defaults;
	}

	$merged = array();
	foreach ( $defaults as $key => $section ) {
		if ( is_array( $section ) ) {
			$merged[ $key ] = wp_parse_args( $saved[ $key ] ?? array(), $section );
		} else {
			$merged[ $key ] = $saved[ $key ] ?? $section;
		}
	}
	return $merged;
}

/* ═══════════════════════════════════════════════════════════
   SANITIZE
   ═══════════════════════════════════════════════════════════ */

function ss_sanitize_hero_settings( $raw ) {
	$clean = array();

	$clean['enabled'] = ! empty( $raw['enabled'] );
	$clean['mode']    = in_array( $raw['mode'] ?? '', array( 'simple', 'image' ), true )
		? $raw['mode'] : 'simple';

	$clean['eyebrow']     = sanitize_text_field( $raw['eyebrow'] ?? '' );
	$clean['headline']    = sanitize_text_field( $raw['headline'] ?? '' );
	$clean['subheadline'] = sanitize_text_field( $raw['subheadline'] ?? '' );
	$clean['body']        = wp_kses_post( $raw['body'] ?? '' );

	/* Primary CTA */
	$pc = $raw['primary_cta'] ?? array();
	$clean['primary_cta'] = array(
		'label' => sanitize_text_field( $pc['label'] ?? '' ),
		'url'   => esc_url_raw( $pc['url'] ?? '' ),
		'style' => in_array( $pc['style'] ?? '', array( 'solid', 'outline' ), true )
			? $pc['style'] : 'solid',
	);

	/* Secondary CTA */
	$sc = $raw['secondary_cta'] ?? array();
	$clean['secondary_cta'] = array(
		'enabled' => ! empty( $sc['enabled'] ),
		'label'   => sanitize_text_field( $sc['label'] ?? '' ),
		'url'     => esc_url_raw( $sc['url'] ?? '' ),
		'style'   => in_array( $sc['style'] ?? '', array( 'text', 'outline' ), true )
			? $sc['style'] : 'outline',
	);

	/* Key Facts */
	$kf = $raw['key_facts'] ?? array();
	$clean['key_facts'] = array(
		'enabled'  => ! empty( $kf['enabled'] ),
		'date'     => sanitize_text_field( $kf['date'] ?? '' ),
		'location' => sanitize_text_field( $kf['location'] ?? '' ),
		'venue'    => sanitize_text_field( $kf['venue'] ?? '' ),
		'format'   => sanitize_text_field( $kf['format'] ?? '' ),
	);

	$clean['microcopy'] = sanitize_text_field( $raw['microcopy'] ?? '' );

	/* Logo */
	$logo = $raw['logo'] ?? array();
	$clean['logo'] = array(
		'enabled'       => ! empty( $logo['enabled'] ),
		'image_id'      => absint( $logo['image_id'] ?? 0 ),
		'dark_image_id' => absint( $logo['dark_image_id'] ?? 0 ),
	);

	/* Background */
	$bg = $raw['background'] ?? array();
	$clean['background'] = array(
		'image_id'         => absint( $bg['image_id'] ?? 0 ),
		'overlay_strength' => in_array( $bg['overlay_strength'] ?? '', array( 'low', 'medium', 'high' ), true )
			? $bg['overlay_strength'] : 'medium',
	);

	/* Layout */
	$layout = $raw['layout'] ?? array();
	$clean['layout'] = array(
		'alignment'     => in_array( $layout['alignment'] ?? '', array( 'left', 'center' ), true )
			? $layout['alignment'] : 'center',
		'content_width' => in_array( $layout['content_width'] ?? '', array( 'standard', 'wide' ), true )
			? $layout['content_width'] : 'standard',
		'spacing'       => in_array( $layout['spacing'] ?? '', array( 'compact', 'standard', 'spacious' ), true )
			? $layout['spacing'] : 'standard',
	);

	/* Countdown */
	$cd = $raw['countdown'] ?? array();
	$clean['countdown'] = array(
		'enabled'         => ! empty( $cd['enabled'] ),
		'target_datetime' => sanitize_text_field( $cd['target_datetime'] ?? '' ),
	);

	return $clean;
}

/* ═══════════════════════════════════════════════════════════
   REGISTER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_hero_register_meta_box() {
	add_meta_box(
		'ss_hero_meta_box',
		__( 'Hero Block Settings', 'sender-symposium' ),
		'ss_hero_render_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ss_hero_register_meta_box' );

/* ═══════════════════════════════════════════════════════════
   RENDER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_hero_render_meta_box( $post ) {
	$s = ss_get_hero_settings( $post->ID );
	wp_nonce_field( 'ss_hero_save', 'ss_hero_nonce' );

	$fs = 'border:1px solid #ccd0d4;padding:12px 16px;margin-bottom:16px;border-radius:4px;background:#fff;';
	?>
	<p class="description" style="margin-bottom:16px;">
		<?php esc_html_e( 'Hero block renders on the front page above all other content. These settings apply when this page is set as the static front page.', 'sender-symposium' ); ?>
	</p>

	<?php /* ── Enable Hero ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Enable Hero', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_hero[enabled]" value="1" <?php checked( $s['enabled'] ); ?>>
			<?php esc_html_e( 'Show hero block on this page', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Mode', 'sender-symposium' ); ?><br>
			<select name="ss_hero[mode]">
				<option value="simple" <?php selected( $s['mode'], 'simple' ); ?>><?php esc_html_e( 'Simple (no background image)', 'sender-symposium' ); ?></option>
				<option value="image" <?php selected( $s['mode'], 'image' ); ?>><?php esc_html_e( 'Image (background with overlay)', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
	</fieldset>

	<?php /* ── Content ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Content', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><?php esc_html_e( 'Eyebrow', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_hero[eyebrow]" value="<?php echo esc_attr( $s['eyebrow'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[headline]" value="<?php echo esc_attr( $s['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Subheadline', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_hero[subheadline]" rows="2" class="large-text"><?php echo esc_textarea( $s['subheadline'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_hero[body]" rows="3" class="large-text"><?php echo esc_textarea( $s['body'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Microcopy', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional, shown below CTAs', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_hero[microcopy]" value="<?php echo esc_attr( $s['microcopy'] ); ?>" class="large-text"></label>
		</p>
	</fieldset>

	<?php /* ── CTAs ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Calls to Action', 'sender-symposium' ); ?></strong></legend>

		<h4><?php esc_html_e( 'Primary CTA', 'sender-symposium' ); ?></h4>
		<p>
			<label><?php esc_html_e( 'Label', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[primary_cta][label]" value="<?php echo esc_attr( $s['primary_cta']['label'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'URL', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[primary_cta][url]" value="<?php echo esc_attr( $s['primary_cta']['url'] ); ?>" class="regular-text" placeholder="/tickets/"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Style', 'sender-symposium' ); ?><br>
			<select name="ss_hero[primary_cta][style]">
				<option value="solid" <?php selected( $s['primary_cta']['style'], 'solid' ); ?>><?php esc_html_e( 'Solid', 'sender-symposium' ); ?></option>
				<option value="outline" <?php selected( $s['primary_cta']['style'], 'outline' ); ?>><?php esc_html_e( 'Outline', 'sender-symposium' ); ?></option>
			</select></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Secondary CTA', 'sender-symposium' ); ?></h4>
		<p>
			<label><input type="checkbox" name="ss_hero[secondary_cta][enabled]" value="1" <?php checked( $s['secondary_cta']['enabled'] ); ?>>
			<?php esc_html_e( 'Show secondary CTA', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Label', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[secondary_cta][label]" value="<?php echo esc_attr( $s['secondary_cta']['label'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'URL', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[secondary_cta][url]" value="<?php echo esc_attr( $s['secondary_cta']['url'] ); ?>" class="regular-text" placeholder="/agenda/"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Style', 'sender-symposium' ); ?><br>
			<select name="ss_hero[secondary_cta][style]">
				<option value="outline" <?php selected( $s['secondary_cta']['style'], 'outline' ); ?>><?php esc_html_e( 'Outline', 'sender-symposium' ); ?></option>
				<option value="text" <?php selected( $s['secondary_cta']['style'], 'text' ); ?>><?php esc_html_e( 'Text link', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
	</fieldset>

	<?php /* ── Key Facts ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Key Facts', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_hero[key_facts][enabled]" value="1" <?php checked( $s['key_facts']['enabled'] ); ?>>
			<?php esc_html_e( 'Show key facts row', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Date', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[key_facts][date]" value="<?php echo esc_attr( $s['key_facts']['date'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Location', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[key_facts][location]" value="<?php echo esc_attr( $s['key_facts']['location'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Venue', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[key_facts][venue]" value="<?php echo esc_attr( $s['key_facts']['venue'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Format', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_hero[key_facts][format]" value="<?php echo esc_attr( $s['key_facts']['format'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>

	<?php /* ── Background ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Background', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><?php esc_html_e( 'Image Attachment ID', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( '0 = no image; set mode to "Image" above to use', 'sender-symposium' ); ?>)</small><br>
			<input type="number" name="ss_hero[background][image_id]" value="<?php echo esc_attr( $s['background']['image_id'] ); ?>" min="0" class="small-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Overlay Strength', 'sender-symposium' ); ?><br>
			<select name="ss_hero[background][overlay_strength]">
				<option value="low" <?php selected( $s['background']['overlay_strength'], 'low' ); ?>><?php esc_html_e( 'Low (35%)', 'sender-symposium' ); ?></option>
				<option value="medium" <?php selected( $s['background']['overlay_strength'], 'medium' ); ?>><?php esc_html_e( 'Medium (50%)', 'sender-symposium' ); ?></option>
				<option value="high" <?php selected( $s['background']['overlay_strength'], 'high' ); ?>><?php esc_html_e( 'High (65%)', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
	</fieldset>

	<?php /* ── Logo ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Logo (in hero)', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_hero[logo][enabled]" value="1" <?php checked( $s['logo']['enabled'] ); ?>>
			<?php esc_html_e( 'Show logo in hero', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Logo Attachment ID (light)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( '0 = use site logo', 'sender-symposium' ); ?>)</small><br>
			<input type="number" name="ss_hero[logo][image_id]" value="<?php echo esc_attr( $s['logo']['image_id'] ); ?>" min="0" class="small-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Logo Attachment ID (dark)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( '0 = use light logo for both', 'sender-symposium' ); ?>)</small><br>
			<input type="number" name="ss_hero[logo][dark_image_id]" value="<?php echo esc_attr( $s['logo']['dark_image_id'] ); ?>" min="0" class="small-text"></label>
		</p>
	</fieldset>

	<?php /* ── Layout ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Layout', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><?php esc_html_e( 'Alignment', 'sender-symposium' ); ?><br>
			<select name="ss_hero[layout][alignment]">
				<option value="center" <?php selected( $s['layout']['alignment'], 'center' ); ?>><?php esc_html_e( 'Center', 'sender-symposium' ); ?></option>
				<option value="left" <?php selected( $s['layout']['alignment'], 'left' ); ?>><?php esc_html_e( 'Left', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Content Width', 'sender-symposium' ); ?><br>
			<select name="ss_hero[layout][content_width]">
				<option value="standard" <?php selected( $s['layout']['content_width'], 'standard' ); ?>><?php esc_html_e( 'Standard (~48ch)', 'sender-symposium' ); ?></option>
				<option value="wide" <?php selected( $s['layout']['content_width'], 'wide' ); ?>><?php esc_html_e( 'Wide (~65ch)', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Spacing', 'sender-symposium' ); ?><br>
			<select name="ss_hero[layout][spacing]">
				<option value="compact" <?php selected( $s['layout']['spacing'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'sender-symposium' ); ?></option>
				<option value="standard" <?php selected( $s['layout']['spacing'], 'standard' ); ?>><?php esc_html_e( 'Standard', 'sender-symposium' ); ?></option>
				<option value="spacious" <?php selected( $s['layout']['spacing'], 'spacious' ); ?>><?php esc_html_e( 'Spacious', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
	</fieldset>

	<?php /* ── Countdown ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Countdown', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_hero[countdown][enabled]" value="1" <?php checked( $s['countdown']['enabled'] ); ?>>
			<?php esc_html_e( 'Show countdown', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Target Date/Time', 'sender-symposium' ); ?>
			<small><?php echo esc_html( 'e.g. 2026-04-24T09:00:00+02:00' ); ?></small><br>
			<input type="text" name="ss_hero[countdown][target_datetime]" value="<?php echo esc_attr( $s['countdown']['target_datetime'] ); ?>" class="regular-text" placeholder="2026-04-24T09:00:00+02:00"></label>
		</p>
	</fieldset>
	<?php
}

/* ═══════════════════════════════════════════════════════════
   SAVE
   ═══════════════════════════════════════════════════════════ */

function ss_hero_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['ss_hero_nonce'] )
		|| ! wp_verify_nonce( $_POST['ss_hero_nonce'], 'ss_hero_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$raw   = $_POST['ss_hero'] ?? array();
	$clean = ss_sanitize_hero_settings( $raw );
	update_post_meta( $post_id, 'ss_hero_settings', $clean );
}
add_action( 'save_post_page', 'ss_hero_save_meta_box' );

/* ═══════════════════════════════════════════════════════════
   PRELOAD HERO IMAGE (above the fold)
   ═══════════════════════════════════════════════════════════ */

function ss_preload_hero_image() {
	if ( ! is_front_page() ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}
	$s = ss_get_hero_settings( $post_id );
	if ( ! $s['enabled'] || 'image' !== $s['mode'] || empty( $s['background']['image_id'] ) ) {
		return;
	}
	$img_url = wp_get_attachment_image_url( $s['background']['image_id'], 'full' );
	if ( $img_url ) {
		echo '<link rel="preload" as="image" href="' . esc_url( $img_url ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'ss_preload_hero_image', 4 );
