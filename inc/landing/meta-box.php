<?php
/**
 * Ticket Landing Page — Meta Box & Settings
 *
 * Stores all landing page settings as a single structured post-meta array:
 *   ss_landing_settings
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ═══════════════════════════════════════════════════════════
   DEFAULTS
   ═══════════════════════════════════════════════════════════ */

function ss_landing_defaults() {
	$g = function_exists( 'ss_get_setting_nonempty' ) ? 'ss_get_setting_nonempty' : null;

	return array(
		'hero' => array(
			'overline'         => '',
			'event_name'       => $g ? $g( 'event_name', __( 'Sender Symposium Barcelona', 'sender-symposium' ) ) : __( 'Sender Symposium Barcelona', 'sender-symposium' ),
			'headline'         => '',
			'subheadline'      => '',
			'partner_name'     => '',
			'partner_title'    => '',
			'partner_image_id' => 0,
			'partner_logo_id'      => 0,
			'partner_logo_dark_id' => 0,
			'cta_label'        => __( 'Secure Strategic Access', 'sender-symposium' ),
			'cta_url'          => '#tickets',
			'show_scarcity'    => false,
			'scarcity_text'    => '',
			'show_micro_trust' => true,
			'micro_trust_1'    => '',
			'micro_trust_2'    => '',
			'micro_trust_3'    => '',
		),
		'event' => array(
			'show'    => true,
			'body'    => '',
			'bullets' => '',
			'footer'  => '',
		),
		'offer' => array(
			'show'       => true,
			'overline'   => '',
			'headline'   => '',
			'value_note' => '',
			'body'       => '',
			'items'      => '',
			'footer'     => '',
		),
		'why' => array(
			'show'     => true,
			'headline' => __( 'Why This Matters', 'sender-symposium' ),
			'body'     => '',
			'items'    => '',
			'footer'   => '',
		),
		'value_stack' => array(
			'show'              => true,
			'headline'          => __( 'What You Receive', 'sender-symposium' ),
			'ticket_name'       => '',
			'ticket_price'      => '',
			'ticket_items'      => '',
			'bonus_condition'   => '',
			'bonus_name'        => '',
			'bonus_description' => '',
		),
		'audience' => array(
			'show'     => true,
			'headline' => __( 'Who This Is For', 'sender-symposium' ),
			'intro'    => '',
			'items'    => '',
			'footer'   => '',
		),
		'notes' => array(
			'show'     => true,
			'headline' => __( 'Important Notes', 'sender-symposium' ),
			'items'    => '',
		),
		'closing' => array(
			'show'      => true,
			'headline'  => '',
			'body'      => '',
			'bullets'   => '',
			'cta_label' => __( 'Secure Strategic Access', 'sender-symposium' ),
			'cta_url'   => '#tickets',
		),
		'tickettailor' => array(
			'show_embed'            => false,
			'embed_position'        => 'before_closing',
			'event_id'              => '',
			'access_code'           => '',
			'custom_domain'         => '',
			'shortcode'             => '',
			'embed_method'          => 'auto',
			'fallback_text'         => '',
			'widget_bg_transparent' => true,
		),
	);
}

/* ═══════════════════════════════════════════════════════════
   GET SETTINGS (deep-merged with defaults)
   ═══════════════════════════════════════════════════════════ */

function ss_get_landing_settings( $post_id ) {
	$saved    = get_post_meta( $post_id, 'ss_landing_settings', true );
	$defaults = ss_landing_defaults();

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

function ss_sanitize_landing_settings( $raw ) {
	$clean = array();

	/* Hero */
	$h = $raw['hero'] ?? array();
	$clean['hero'] = array(
		'overline'         => sanitize_text_field( $h['overline'] ?? '' ),
		'event_name'       => sanitize_text_field( $h['event_name'] ?? '' ),
		'headline'         => sanitize_text_field( $h['headline'] ?? '' ),
		'subheadline'      => sanitize_text_field( $h['subheadline'] ?? '' ),
		'partner_name'     => sanitize_text_field( $h['partner_name'] ?? '' ),
		'partner_title'    => sanitize_text_field( $h['partner_title'] ?? '' ),
		'partner_image_id' => absint( $h['partner_image_id'] ?? 0 ),
		'partner_logo_id'      => absint( $h['partner_logo_id'] ?? 0 ),
		'partner_logo_dark_id' => absint( $h['partner_logo_dark_id'] ?? 0 ),
		'cta_label'        => sanitize_text_field( $h['cta_label'] ?? '' ),
		'cta_url'          => esc_url_raw( $h['cta_url'] ?? '' ),
		'show_scarcity'    => ! empty( $h['show_scarcity'] ),
		'scarcity_text'    => sanitize_text_field( $h['scarcity_text'] ?? '' ),
		'show_micro_trust' => ! empty( $h['show_micro_trust'] ),
		'micro_trust_1'    => sanitize_text_field( $h['micro_trust_1'] ?? '' ),
		'micro_trust_2'    => sanitize_text_field( $h['micro_trust_2'] ?? '' ),
		'micro_trust_3'    => sanitize_text_field( $h['micro_trust_3'] ?? '' ),
	);

	/* Event context */
	$ev = $raw['event'] ?? array();
	$clean['event'] = array(
		'show'    => ! empty( $ev['show'] ),
		'body'    => wp_kses_post( $ev['body'] ?? '' ),
		'bullets' => sanitize_textarea_field( $ev['bullets'] ?? '' ),
		'footer'  => sanitize_text_field( $ev['footer'] ?? '' ),
	);

	/* Offer */
	$of = $raw['offer'] ?? array();
	$clean['offer'] = array(
		'show'       => ! empty( $of['show'] ),
		'overline'   => sanitize_text_field( $of['overline'] ?? '' ),
		'headline'   => sanitize_text_field( $of['headline'] ?? '' ),
		'value_note' => sanitize_text_field( $of['value_note'] ?? '' ),
		'body'       => wp_kses_post( $of['body'] ?? '' ),
		'items'      => sanitize_textarea_field( $of['items'] ?? '' ),
		'footer'     => wp_kses_post( $of['footer'] ?? '' ),
	);

	/* Why this matters */
	$w = $raw['why'] ?? array();
	$clean['why'] = array(
		'show'     => ! empty( $w['show'] ),
		'headline' => sanitize_text_field( $w['headline'] ?? '' ),
		'body'     => wp_kses_post( $w['body'] ?? '' ),
		'items'    => sanitize_textarea_field( $w['items'] ?? '' ),
		'footer'   => sanitize_text_field( $w['footer'] ?? '' ),
	);

	/* Value stack */
	$vs = $raw['value_stack'] ?? array();
	$clean['value_stack'] = array(
		'show'              => ! empty( $vs['show'] ),
		'headline'          => sanitize_text_field( $vs['headline'] ?? '' ),
		'ticket_name'       => sanitize_text_field( $vs['ticket_name'] ?? '' ),
		'ticket_price'      => sanitize_text_field( $vs['ticket_price'] ?? '' ),
		'ticket_items'      => sanitize_textarea_field( $vs['ticket_items'] ?? '' ),
		'bonus_condition'   => sanitize_text_field( $vs['bonus_condition'] ?? '' ),
		'bonus_name'        => sanitize_text_field( $vs['bonus_name'] ?? '' ),
		'bonus_description' => sanitize_text_field( $vs['bonus_description'] ?? '' ),
	);

	/* Audience */
	$au = $raw['audience'] ?? array();
	$clean['audience'] = array(
		'show'     => ! empty( $au['show'] ),
		'headline' => sanitize_text_field( $au['headline'] ?? '' ),
		'intro'    => sanitize_text_field( $au['intro'] ?? '' ),
		'items'    => sanitize_textarea_field( $au['items'] ?? '' ),
		'footer'   => sanitize_text_field( $au['footer'] ?? '' ),
	);

	/* Notes */
	$no = $raw['notes'] ?? array();
	$clean['notes'] = array(
		'show'     => ! empty( $no['show'] ),
		'headline' => sanitize_text_field( $no['headline'] ?? '' ),
		'items'    => sanitize_textarea_field( $no['items'] ?? '' ),
	);

	/* Closing CTA */
	$cl = $raw['closing'] ?? array();
	$clean['closing'] = array(
		'show'      => ! empty( $cl['show'] ),
		'headline'  => sanitize_text_field( $cl['headline'] ?? '' ),
		'body'      => wp_kses_post( $cl['body'] ?? '' ),
		'bullets'   => sanitize_textarea_field( $cl['bullets'] ?? '' ),
		'cta_label' => sanitize_text_field( $cl['cta_label'] ?? '' ),
		'cta_url'   => esc_url_raw( $cl['cta_url'] ?? '' ),
	);

	/* TicketTailor */
	$tt = $raw['tickettailor'] ?? array();
	$clean['tickettailor'] = array(
		'show_embed'            => ! empty( $tt['show_embed'] ),
		'embed_position'        => in_array( $tt['embed_position'] ?? '', array( 'before_closing', 'inside_closing', 'after_value_stack' ), true )
			? $tt['embed_position'] : 'before_closing',
		'event_id'              => sanitize_text_field( $tt['event_id'] ?? '' ),
		'access_code'           => sanitize_text_field( $tt['access_code'] ?? '' ),
		'custom_domain'         => sanitize_text_field( $tt['custom_domain'] ?? '' ),
		'shortcode'             => sanitize_text_field( $tt['shortcode'] ?? '' ),
		'embed_method'          => in_array( $tt['embed_method'] ?? '', array( 'auto', 'widget_js', 'shortcode' ), true )
			? $tt['embed_method'] : 'auto',
		'fallback_text'         => sanitize_text_field( $tt['fallback_text'] ?? '' ),
		'widget_bg_transparent' => ! empty( $tt['widget_bg_transparent'] ),
	);

	return $clean;
}

/* ═══════════════════════════════════════════════════════════
   REGISTER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_landing_register_meta_box() {
	add_meta_box(
		'ss_landing_meta_box',
		__( 'Landing Page Settings', 'sender-symposium' ),
		'ss_landing_render_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ss_landing_register_meta_box' );

/* ═══════════════════════════════════════════════════════════
   RENDER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_landing_render_meta_box( $post ) {
	$s = ss_get_landing_settings( $post->ID );
	wp_nonce_field( 'ss_landing_save', 'ss_landing_nonce' );

	$fs = 'border:1px solid #ccd0d4;padding:12px 16px;margin-bottom:16px;border-radius:4px;background:#fff;';
	?>
	<p class="description" style="margin-bottom:16px;">
		<?php esc_html_e( 'These settings apply when the "Ticket Landing Page" template is selected for this page.', 'sender-symposium' ); ?>
	</p>

	<?php /* ── Copy Set Import / Export ── */ ?>
	<details style="<?php echo $fs; ?>">
		<summary style="cursor:pointer;"><strong><?php esc_html_e( 'Import / Export Copy Set', 'sender-symposium' ); ?></strong></summary>
		<div style="padding:12px 0 0;">
			<p class="description">
				<?php esc_html_e( 'Paste a JSON copy set to populate all fields at once, or export the current fields to share with an LLM. Image fields and TicketTailor settings are excluded.', 'sender-symposium' ); ?>
			</p>
			<p style="margin-top:8px;">
				<textarea id="ss-landing-copyset-json" rows="8" class="large-text" style="font-family:monospace;font-size:12px;" placeholder='<?php esc_attr_e( 'Paste JSON copy set here...', 'sender-symposium' ); ?>'></textarea>
			</p>
			<p>
				<button type="button" id="ss-landing-copyset-import" class="button button-primary"><?php esc_html_e( 'Import Copy Set', 'sender-symposium' ); ?></button>
				<button type="button" id="ss-landing-copyset-export" class="button"><?php esc_html_e( 'Export Current Fields', 'sender-symposium' ); ?></button>
			</p>
			<div id="ss-landing-copyset-status"></div>
			<p class="description" style="margin-top:8px;">
				<?php esc_html_e( 'Review all fields below after importing, then click Publish/Update to save.', 'sender-symposium' ); ?>
			</p>
		</div>
	</details>

	<?php /* ── Hero ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Hero (Above the Fold)', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><?php esc_html_e( 'Overline', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "Private Access Page"', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[hero][overline]" value="<?php echo esc_attr( $s['hero']['overline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Event Name', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][event_name]" value="<?php echo esc_attr( $s['hero']['event_name'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'outcome-driven, main H1', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[hero][headline]" value="<?php echo esc_attr( $s['hero']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Subheadline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][subheadline]" value="<?php echo esc_attr( $s['hero']['subheadline'] ); ?>" class="large-text"></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Partner / Speaker', 'sender-symposium' ); ?></h4>
		<p>
			<label><?php esc_html_e( 'Partner Name', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][partner_name]" value="<?php echo esc_attr( $s['hero']['partner_name'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Partner Title / Programme', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[hero][partner_title]" value="<?php echo esc_attr( $s['hero']['partner_title'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Partner Photo', 'sender-symposium' ); ?></label><br>
			<?php ss_media_picker( 'ss_landing[hero][partner_image_id]', $s['hero']['partner_image_id'], __( 'Choose Partner Photo', 'sender-symposium' ) ); ?>
		</p>
		<p>
			<label><?php esc_html_e( 'Partner / Company Logo (Light Background)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional — shown on light backgrounds', 'sender-symposium' ); ?>)</small></label><br>
			<?php ss_media_picker( 'ss_landing[hero][partner_logo_id]', $s['hero']['partner_logo_id'], __( 'Choose Logo', 'sender-symposium' ) ); ?>
		</p>
		<p>
			<label><?php esc_html_e( 'Partner / Company Logo (Dark Background)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional — shown when dark mode is active', 'sender-symposium' ); ?>)</small></label><br>
			<?php ss_media_picker( 'ss_landing[hero][partner_logo_dark_id]', $s['hero']['partner_logo_dark_id'], __( 'Choose Dark Logo', 'sender-symposium' ) ); ?>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Call to Action', 'sender-symposium' ); ?></h4>
		<p>
			<label><?php esc_html_e( 'CTA Label', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][cta_label]" value="<?php echo esc_attr( $s['hero']['cta_label'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'CTA URL', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'use #tickets to anchor to TT embed or closing section', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[hero][cta_url]" value="<?php echo esc_attr( $s['hero']['cta_url'] ); ?>" class="regular-text"></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Scarcity Badge', 'sender-symposium' ); ?></h4>
		<p>
			<label><input type="checkbox" name="ss_landing[hero][show_scarcity]" value="1" <?php checked( $s['hero']['show_scarcity'] ); ?>>
			<?php esc_html_e( 'Show scarcity badge', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Scarcity Text', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "Limited to 12 allocations"', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[hero][scarcity_text]" value="<?php echo esc_attr( $s['hero']['scarcity_text'] ); ?>" class="regular-text"></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Micro Trust Row', 'sender-symposium' ); ?></h4>
		<p>
			<label><input type="checkbox" name="ss_landing[hero][show_micro_trust]" value="1" <?php checked( $s['hero']['show_micro_trust'] ); ?>>
			<?php esc_html_e( 'Show micro trust signals', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Trust Signal 1', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][micro_trust_1]" value="<?php echo esc_attr( $s['hero']['micro_trust_1'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Trust Signal 2', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][micro_trust_2]" value="<?php echo esc_attr( $s['hero']['micro_trust_2'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Trust Signal 3', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[hero][micro_trust_3]" value="<?php echo esc_attr( $s['hero']['micro_trust_3'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>

	<?php /* ── Event Context ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Event Context', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[event][show]" value="1" <?php checked( $s['event']['show'] ); ?>>
			<?php esc_html_e( 'Show event context section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[event][body]" rows="5" class="large-text"><?php echo esc_textarea( $s['event']['body'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Statement Bullets (one per line)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "No vendor decks."', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[event][bullets]" rows="4" class="large-text"><?php echo esc_textarea( $s['event']['bullets'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Transition Footer', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[event][footer]" value="<?php echo esc_attr( $s['event']['footer'] ); ?>" class="large-text"></label>
		</p>
	</fieldset>

	<?php /* ── Partner Exclusive Offer ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Partner Exclusive Offer', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[offer][show]" value="1" <?php checked( $s['offer']['show'] ); ?>>
			<?php esc_html_e( 'Show offer section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Overline', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[offer][overline]" value="<?php echo esc_attr( $s['offer']['overline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Offer Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[offer][headline]" value="<?php echo esc_attr( $s['offer']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Value Note', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "Normally priced at ~£1,000."', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[offer][value_note]" value="<?php echo esc_attr( $s['offer']['value_note'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[offer][body]" rows="5" class="large-text"><?php echo esc_textarea( $s['offer']['body'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Included Items (one per line)', 'sender-symposium' ); ?><br>
			<textarea name="ss_landing[offer][items]" rows="4" class="large-text"><?php echo esc_textarea( $s['offer']['items'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Footer Statement', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed — e.g. "This is not a discount."', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[offer][footer]" rows="3" class="large-text"><?php echo esc_textarea( $s['offer']['footer'] ); ?></textarea></label>
		</p>
	</fieldset>

	<?php /* ── Why This Matters ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Why This Matters', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[why][show]" value="1" <?php checked( $s['why']['show'] ); ?>>
			<?php esc_html_e( 'Show section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[why][headline]" value="<?php echo esc_attr( $s['why']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[why][body]" rows="4" class="large-text"><?php echo esc_textarea( $s['why']['body'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Progression Items (one per line)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "From execution to architecture"', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[why][items]" rows="4" class="large-text"><?php echo esc_textarea( $s['why']['items'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Footer', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional motivational close', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[why][footer]" value="<?php echo esc_attr( $s['why']['footer'] ); ?>" class="large-text"></label>
		</p>
	</fieldset>

	<?php /* ── Value Stack ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Value Stack', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[value_stack][show]" value="1" <?php checked( $s['value_stack']['show'] ); ?>>
			<?php esc_html_e( 'Show value stack section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Section Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[value_stack][headline]" value="<?php echo esc_attr( $s['value_stack']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Ticket Name', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[value_stack][ticket_name]" value="<?php echo esc_attr( $s['value_stack']['ticket_name'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Ticket Price', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "€845 + IVA"', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[value_stack][ticket_price]" value="<?php echo esc_attr( $s['value_stack']['ticket_price'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Included Items (one per line)', 'sender-symposium' ); ?><br>
			<textarea name="ss_landing[value_stack][ticket_items]" rows="5" class="large-text"><?php echo esc_textarea( $s['value_stack']['ticket_items'] ); ?></textarea></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Bonus (Optional)', 'sender-symposium' ); ?></h4>
		<p>
			<label><?php esc_html_e( 'Bonus Condition', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'e.g. "Plus, if within the first 12 registrations:"', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[value_stack][bonus_condition]" value="<?php echo esc_attr( $s['value_stack']['bonus_condition'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Bonus Name', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'leave empty to hide bonus block', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[value_stack][bonus_name]" value="<?php echo esc_attr( $s['value_stack']['bonus_name'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Bonus Description', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[value_stack][bonus_description]" value="<?php echo esc_attr( $s['value_stack']['bonus_description'] ); ?>" class="large-text"></label>
		</p>
	</fieldset>

	<?php /* ── Audience ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Audience', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[audience][show]" value="1" <?php checked( $s['audience']['show'] ); ?>>
			<?php esc_html_e( 'Show audience section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[audience][headline]" value="<?php echo esc_attr( $s['audience']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Intro', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[audience][intro]" value="<?php echo esc_attr( $s['audience']['intro'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Roles (one per line)', 'sender-symposium' ); ?><br>
			<textarea name="ss_landing[audience][items]" rows="5" class="large-text"><?php echo esc_textarea( $s['audience']['items'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Footer', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional qualifying statement', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[audience][footer]" value="<?php echo esc_attr( $s['audience']['footer'] ); ?>" class="large-text"></label>
		</p>
	</fieldset>

	<?php /* ── Important Notes ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Important Notes', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[notes][show]" value="1" <?php checked( $s['notes']['show'] ); ?>>
			<?php esc_html_e( 'Show notes section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[notes][headline]" value="<?php echo esc_attr( $s['notes']['headline'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Items (one per line)', 'sender-symposium' ); ?><br>
			<textarea name="ss_landing[notes][items]" rows="5" class="large-text"><?php echo esc_textarea( $s['notes']['items'] ); ?></textarea></label>
		</p>
	</fieldset>

	<?php /* ── Closing CTA ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Closing CTA', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[closing][show]" value="1" <?php checked( $s['closing']['show'] ); ?>>
			<?php esc_html_e( 'Show closing section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[closing][headline]" value="<?php echo esc_attr( $s['closing']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_landing[closing][body]" rows="3" class="large-text"><?php echo esc_textarea( $s['closing']['body'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Reinforcement Bullets (one per line)', 'sender-symposium' ); ?><br>
			<textarea name="ss_landing[closing][bullets]" rows="3" class="large-text"><?php echo esc_textarea( $s['closing']['bullets'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'CTA Label', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[closing][cta_label]" value="<?php echo esc_attr( $s['closing']['cta_label'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'CTA URL', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[closing][cta_url]" value="<?php echo esc_attr( $s['closing']['cta_url'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>

	<?php /* ── TicketTailor Embed ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'TicketTailor Embed', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_landing[tickettailor][show_embed]" value="1" <?php checked( $s['tickettailor']['show_embed'] ); ?>>
			<?php esc_html_e( 'Enable TicketTailor embed on this page', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Embed Position', 'sender-symposium' ); ?><br>
			<select name="ss_landing[tickettailor][embed_position]">
				<option value="before_closing" <?php selected( $s['tickettailor']['embed_position'], 'before_closing' ); ?>><?php esc_html_e( 'Before closing CTA', 'sender-symposium' ); ?></option>
				<option value="inside_closing" <?php selected( $s['tickettailor']['embed_position'], 'inside_closing' ); ?>><?php esc_html_e( 'Inside closing CTA section', 'sender-symposium' ); ?></option>
				<option value="after_value_stack" <?php selected( $s['tickettailor']['embed_position'], 'after_value_stack' ); ?>><?php esc_html_e( 'After value stack', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Embed Method', 'sender-symposium' ); ?><br>
			<select name="ss_landing[tickettailor][embed_method]">
				<option value="auto" <?php selected( $s['tickettailor']['embed_method'], 'auto' ); ?>><?php esc_html_e( 'Auto (recommended)', 'sender-symposium' ); ?></option>
				<option value="widget_js" <?php selected( $s['tickettailor']['embed_method'], 'widget_js' ); ?>><?php esc_html_e( 'JS embed only', 'sender-symposium' ); ?></option>
				<option value="shortcode" <?php selected( $s['tickettailor']['embed_method'], 'shortcode' ); ?>><?php esc_html_e( 'Shortcode only', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Event ID', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[tickettailor][event_id]" value="<?php echo esc_attr( $s['tickettailor']['event_id'] ); ?>" class="regular-text" placeholder="ev_123456"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Access Code', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[tickettailor][access_code]" value="<?php echo esc_attr( $s['tickettailor']['access_code'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Custom Checkout Domain', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[tickettailor][custom_domain]" value="<?php echo esc_attr( $s['tickettailor']['custom_domain'] ); ?>" class="regular-text" placeholder="tickets.yourdomain.com"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Shortcode', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'for shortcode or auto fallback mode', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_landing[tickettailor][shortcode]" value="<?php echo esc_attr( $s['tickettailor']['shortcode'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Fallback Text', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_landing[tickettailor][fallback_text]" value="<?php echo esc_attr( $s['tickettailor']['fallback_text'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><input type="checkbox" name="ss_landing[tickettailor][widget_bg_transparent]" value="1" <?php checked( $s['tickettailor']['widget_bg_transparent'] ); ?>>
			<?php esc_html_e( 'Transparent widget background (recommended for dark mode)', 'sender-symposium' ); ?></label>
		</p>
	</fieldset>
	<?php
}

/* ═══════════════════════════════════════════════════════════
   SAVE
   ═══════════════════════════════════════════════════════════ */

function ss_landing_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['ss_landing_nonce'] )
		|| ! wp_verify_nonce( $_POST['ss_landing_nonce'], 'ss_landing_save' ) ) {
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

	$raw   = wp_unslash( $_POST['ss_landing'] ?? array() );
	$clean = ss_sanitize_landing_settings( $raw );
	update_post_meta( $post_id, 'ss_landing_settings', $clean );
}
add_action( 'save_post_page', 'ss_landing_save_meta_box' );
