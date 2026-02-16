<?php
/**
 * Ticketing Page — Meta Box & Settings
 *
 * Stores all ticketing settings as a single structured post-meta array:
 *   ss_ticketing_settings
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ═══════════════════════════════════════════════════════════
   DEFAULTS
   ═══════════════════════════════════════════════════════════ */

function ss_ticketing_defaults() {
	return array(
		'journey_bar' => array(
			'show'             => true,
			'show_logo'        => true,
			'logo_id'          => 0,
			'logo_dark_id'     => 0,
			'step_1'           => __( 'Ticket', 'sender-symposium' ),
			'step_2'           => __( 'Information', 'sender-symposium' ),
			'step_3'           => __( 'Confirm', 'sender-symposium' ),
			'active_step'      => 1,
			'show_numbers'     => true,
			'show_trust_badge' => true,
			'trust_badge_text' => __( 'Secured checkout', 'sender-symposium' ),
		),
		'intro' => array(
			'overline'         => __( 'Founder Delegate Access', 'sender-symposium' ),
			'headline'         => __( 'Join 120 Leadership-Level Operators in Barcelona', 'sender-symposium' ),
			'body'             => '<p>Sender Symposium is a high-interaction working room for CRM, Lifecycle, Growth, and Marketing Ops leaders. No fluff. No spectators. Just real orchestration, measurement, and revenue conversations.</p>',
			'show_micro_trust' => true,
			'micro_trust_1'    => __( 'Limited to ~120 delegates', 'sender-symposium' ),
			'micro_trust_2'    => __( "Hosted at Gaudí's La Pedrera", 'sender-symposium' ),
			'micro_trust_3'    => __( 'All food & refreshments included', 'sender-symposium' ),
		),
		'scarcity' => array(
			'show' => true,
			'text' => __( 'Tickets are intentionally limited to maintain depth and interaction.', 'sender-symposium' ),
		),
		'tickettailor' => array(
			'shortcode'              => '',
			'event_id'               => '',
			'access_code'            => '',
			'custom_domain'          => '',
			'embed_method'           => 'auto',
			'fallback_text'          => __( 'Tickets are available at sendersymposium.com/tickets/', 'sender-symposium' ),
			'widget_bg_transparent'  => true,
			'domain_mismatch_note'   => __( 'If checkout opens in a new tab, please complete your purchase there.', 'sender-symposium' ),
		),
		'right_column' => array(
			'order' => 'info_first',
		),
		'why_different' => array(
			'show'    => true,
			'title'   => __( 'Why This Event Is Different', 'sender-symposium' ),
			'bullets' => "Working sessions, not passive talks\nExtended Q&A with practitioners\nPeer-level discussion, not vendor pitches",
		),
		'info_card' => array(
			'show'    => true,
			'heading' => __( 'Become a Founder Delegate', 'sender-symposium' ),
			'body'    => '<p>Secure your place in Barcelona and join a community of operators who shape lifecycle strategy across Europe and beyond.</p>',
		),
		'testimonial' => array(
			'show'      => true,
			'quote'     => __( 'Excited to have you join us in Barcelona for the very first Sender Symposium, tailored to you.', 'sender-symposium' ),
			'name'      => 'Andrew Bonar',
			'role'      => '',
			'company'   => 'Emailexpert',
			'avatar_id' => 0,
		),
		'reassurance' => array(
			'show' => true,
			'text' => __( 'Need help or booking for a team? Contact us.', 'sender-symposium' ),
			'link' => '',
		),
	);
}

/* ═══════════════════════════════════════════════════════════
   GET SETTINGS (deep-merged with defaults)
   ═══════════════════════════════════════════════════════════ */

function ss_get_ticketing_settings( $post_id ) {
	$saved    = get_post_meta( $post_id, 'ss_ticketing_settings', true );
	$defaults = ss_ticketing_defaults();

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

function ss_sanitize_ticketing_settings( $raw ) {
	$clean = array();

	/* Journey Bar */
	$jb = $raw['journey_bar'] ?? array();
	$clean['journey_bar'] = array(
		'show'             => ! empty( $jb['show'] ),
		'show_logo'        => ! empty( $jb['show_logo'] ),
		'logo_id'          => absint( $jb['logo_id'] ?? 0 ),
		'logo_dark_id'     => absint( $jb['logo_dark_id'] ?? 0 ),
		'step_1'           => sanitize_text_field( $jb['step_1'] ?? '' ),
		'step_2'           => sanitize_text_field( $jb['step_2'] ?? '' ),
		'step_3'           => sanitize_text_field( $jb['step_3'] ?? '' ),
		'active_step'      => min( 3, max( 1, absint( $jb['active_step'] ?? 1 ) ) ),
		'show_numbers'     => ! empty( $jb['show_numbers'] ),
		'show_trust_badge' => ! empty( $jb['show_trust_badge'] ),
		'trust_badge_text' => sanitize_text_field( $jb['trust_badge_text'] ?? '' ),
	);

	/* Intro */
	$intro = $raw['intro'] ?? array();
	$clean['intro'] = array(
		'overline'         => sanitize_text_field( $intro['overline'] ?? '' ),
		'headline'         => sanitize_text_field( $intro['headline'] ?? '' ),
		'body'             => wp_kses_post( $intro['body'] ?? '' ),
		'show_micro_trust' => ! empty( $intro['show_micro_trust'] ),
		'micro_trust_1'    => sanitize_text_field( $intro['micro_trust_1'] ?? '' ),
		'micro_trust_2'    => sanitize_text_field( $intro['micro_trust_2'] ?? '' ),
		'micro_trust_3'    => sanitize_text_field( $intro['micro_trust_3'] ?? '' ),
	);

	/* Scarcity */
	$sc = $raw['scarcity'] ?? array();
	$clean['scarcity'] = array(
		'show' => ! empty( $sc['show'] ),
		'text' => sanitize_text_field( $sc['text'] ?? '' ),
	);

	/* TicketTailor */
	$tt = $raw['tickettailor'] ?? array();
	$clean['tickettailor'] = array(
		'shortcode'              => sanitize_text_field( $tt['shortcode'] ?? '' ),
		'event_id'               => sanitize_text_field( $tt['event_id'] ?? '' ),
		'access_code'            => sanitize_text_field( $tt['access_code'] ?? '' ),
		'custom_domain'          => sanitize_text_field( $tt['custom_domain'] ?? '' ),
		'embed_method'           => in_array( $tt['embed_method'] ?? '', array( 'auto', 'widget_js', 'shortcode' ), true )
			? $tt['embed_method'] : 'auto',
		'fallback_text'          => sanitize_text_field( $tt['fallback_text'] ?? '' ),
		'widget_bg_transparent'  => ! empty( $tt['widget_bg_transparent'] ),
		'domain_mismatch_note'   => sanitize_text_field( $tt['domain_mismatch_note'] ?? '' ),
	);

	/* Right column order */
	$rc = $raw['right_column'] ?? array();
	$clean['right_column'] = array(
		'order' => in_array( $rc['order'] ?? '', array( 'info_first', 'testimonial_first' ), true )
			? $rc['order'] : 'info_first',
	);

	/* Why Different */
	$wd = $raw['why_different'] ?? array();
	$clean['why_different'] = array(
		'show'    => ! empty( $wd['show'] ),
		'title'   => sanitize_text_field( $wd['title'] ?? '' ),
		'bullets' => sanitize_textarea_field( $wd['bullets'] ?? '' ),
	);

	/* Info Card */
	$ic = $raw['info_card'] ?? array();
	$clean['info_card'] = array(
		'show'    => ! empty( $ic['show'] ),
		'heading' => sanitize_text_field( $ic['heading'] ?? '' ),
		'body'    => wp_kses_post( $ic['body'] ?? '' ),
	);

	/* Testimonial */
	$tm = $raw['testimonial'] ?? array();
	$clean['testimonial'] = array(
		'show'      => ! empty( $tm['show'] ),
		'quote'     => sanitize_text_field( $tm['quote'] ?? '' ),
		'name'      => sanitize_text_field( $tm['name'] ?? '' ),
		'role'      => sanitize_text_field( $tm['role'] ?? '' ),
		'company'   => sanitize_text_field( $tm['company'] ?? '' ),
		'avatar_id' => absint( $tm['avatar_id'] ?? 0 ),
	);

	/* Reassurance */
	$re = $raw['reassurance'] ?? array();
	$clean['reassurance'] = array(
		'show' => ! empty( $re['show'] ),
		'text' => sanitize_text_field( $re['text'] ?? '' ),
		'link' => esc_url_raw( $re['link'] ?? '' ),
	);

	return $clean;
}

/* ═══════════════════════════════════════════════════════════
   REGISTER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_ticketing_register_meta_box() {
	add_meta_box(
		'ss_ticketing_meta_box',
		__( 'Ticketing Page Settings', 'sender-symposium' ),
		'ss_ticketing_render_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ss_ticketing_register_meta_box' );

/* ═══════════════════════════════════════════════════════════
   RENDER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_ticketing_render_meta_box( $post ) {
	$s = ss_get_ticketing_settings( $post->ID );
	wp_nonce_field( 'ss_ticketing_save', 'ss_ticketing_nonce' );

	$fs = 'border:1px solid #ccd0d4;padding:12px 16px;margin-bottom:16px;border-radius:4px;background:#fff;';
	?>
	<p class="description" style="margin-bottom:16px;">
		<?php esc_html_e( 'These settings apply when the "Ticketing Page" template is selected for this page.', 'sender-symposium' ); ?>
	</p>

	<?php /* ── Journey Bar ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Journey Bar', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_ticketing[journey_bar][show]" value="1" <?php checked( $s['journey_bar']['show'] ); ?>>
			<?php esc_html_e( 'Show journey bar', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" name="ss_ticketing[journey_bar][show_logo]" value="1" <?php checked( $s['journey_bar']['show_logo'] ); ?>>
			<?php esc_html_e( 'Show logo in journey bar', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Logo (light mode)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'leave empty to use site logo', 'sender-symposium' ); ?>)</small></label><br>
			<?php ss_media_picker( 'ss_ticketing[journey_bar][logo_id]', $s['journey_bar']['logo_id'], __( 'Choose Light Logo', 'sender-symposium' ) ); ?>
		</p>
		<p>
			<label><?php esc_html_e( 'Logo (dark mode)', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'leave empty to use light logo for both', 'sender-symposium' ); ?>)</small></label><br>
			<?php ss_media_picker( 'ss_ticketing[journey_bar][logo_dark_id]', $s['journey_bar']['logo_dark_id'], __( 'Choose Dark Logo', 'sender-symposium' ) ); ?>
		</p>
		<p>
			<label><?php esc_html_e( 'Step 1', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[journey_bar][step_1]" value="<?php echo esc_attr( $s['journey_bar']['step_1'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Step 2', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[journey_bar][step_2]" value="<?php echo esc_attr( $s['journey_bar']['step_2'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Step 3', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[journey_bar][step_3]" value="<?php echo esc_attr( $s['journey_bar']['step_3'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Active Step', 'sender-symposium' ); ?><br>
			<select name="ss_ticketing[journey_bar][active_step]">
				<option value="1" <?php selected( $s['journey_bar']['active_step'], 1 ); ?>>1</option>
				<option value="2" <?php selected( $s['journey_bar']['active_step'], 2 ); ?>>2</option>
				<option value="3" <?php selected( $s['journey_bar']['active_step'], 3 ); ?>>3</option>
			</select></label>
		</p>
		<p>
			<label><input type="checkbox" name="ss_ticketing[journey_bar][show_numbers]" value="1" <?php checked( $s['journey_bar']['show_numbers'] ); ?>>
			<?php esc_html_e( 'Show step numbers', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" name="ss_ticketing[journey_bar][show_trust_badge]" value="1" <?php checked( $s['journey_bar']['show_trust_badge'] ); ?>>
			<?php esc_html_e( 'Show trust badge', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Trust Badge Text', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[journey_bar][trust_badge_text]" value="<?php echo esc_attr( $s['journey_bar']['trust_badge_text'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>

	<?php /* ── Conversion Intro ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Conversion Intro', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><?php esc_html_e( 'Overline', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[intro][overline]" value="<?php echo esc_attr( $s['intro']['overline'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[intro][headline]" value="<?php echo esc_attr( $s['intro']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_ticketing[intro][body]" rows="4" class="large-text"><?php echo esc_textarea( $s['intro']['body'] ); ?></textarea></label>
		</p>
		<p>
			<label><input type="checkbox" name="ss_ticketing[intro][show_micro_trust]" value="1" <?php checked( $s['intro']['show_micro_trust'] ); ?>>
			<?php esc_html_e( 'Show micro trust row', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Micro Trust Item 1', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[intro][micro_trust_1]" value="<?php echo esc_attr( $s['intro']['micro_trust_1'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Micro Trust Item 2', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[intro][micro_trust_2]" value="<?php echo esc_attr( $s['intro']['micro_trust_2'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Micro Trust Item 3', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[intro][micro_trust_3]" value="<?php echo esc_attr( $s['intro']['micro_trust_3'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>

	<?php /* ── Scarcity ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Scarcity Line', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_ticketing[scarcity][show]" value="1" <?php checked( $s['scarcity']['show'] ); ?>>
			<?php esc_html_e( 'Show scarcity line', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Text', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[scarcity][text]" value="<?php echo esc_attr( $s['scarcity']['text'] ); ?>" class="large-text"></label>
		</p>
	</fieldset>

	<?php /* ── TicketTailor ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'TicketTailor', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><?php esc_html_e( 'Embed Method', 'sender-symposium' ); ?><br>
			<select name="ss_ticketing[tickettailor][embed_method]">
				<option value="auto" <?php selected( $s['tickettailor']['embed_method'], 'auto' ); ?>><?php esc_html_e( 'Auto — JS embed with shortcode fallback (recommended)', 'sender-symposium' ); ?></option>
				<option value="widget_js" <?php selected( $s['tickettailor']['embed_method'], 'widget_js' ); ?>><?php esc_html_e( 'JS embed only (widget.js)', 'sender-symposium' ); ?></option>
				<option value="shortcode" <?php selected( $s['tickettailor']['embed_method'], 'shortcode' ); ?>><?php esc_html_e( 'Shortcode only', 'sender-symposium' ); ?></option>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Event ID', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'for JS embed / auto — find in your TicketTailor dashboard embed code', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[tickettailor][event_id]" value="<?php echo esc_attr( $s['tickettailor']['event_id'] ); ?>" class="regular-text" placeholder="ev_123456"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Access Code', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional — reveals hidden ticket types via ?a=CODE', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[tickettailor][access_code]" value="<?php echo esc_attr( $s['tickettailor']['access_code'] ); ?>" class="regular-text" placeholder=""></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Custom Checkout Domain', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'if you configured a CNAME in TicketTailor, e.g. tickets.sendersymposium.com — leave empty for default', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[tickettailor][custom_domain]" value="<?php echo esc_attr( $s['tickettailor']['custom_domain'] ); ?>" class="regular-text" placeholder="tickets.yourdomain.com"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Shortcode', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'used by shortcode mode, or as fallback in auto mode', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[tickettailor][shortcode]" value="<?php echo esc_attr( $s['tickettailor']['shortcode'] ); ?>" class="large-text" placeholder='[ticket-tailor id="..."]'></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Fallback Text', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'shown if embed unavailable', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[tickettailor][fallback_text]" value="<?php echo esc_attr( $s['tickettailor']['fallback_text'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><input type="checkbox" name="ss_ticketing[tickettailor][widget_bg_transparent]" value="1" <?php checked( $s['tickettailor']['widget_bg_transparent'] ); ?>>
			<?php esc_html_e( 'Transparent widget background (recommended for dark mode)', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Domain Mismatch Note', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'shown on page when TT domain differs from site domain — leave empty to hide', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[tickettailor][domain_mismatch_note]" value="<?php echo esc_attr( $s['tickettailor']['domain_mismatch_note'] ); ?>" class="large-text"></label>
		</p>

		<?php
		/* ── Domain Status Warning ── */
		$tt_event_id = $s['tickettailor']['event_id'];
		if ( ! empty( $tt_event_id ) ) :
			$tt_host   = ! empty( $s['tickettailor']['custom_domain'] )
				? $s['tickettailor']['custom_domain']
				: 'www.tickettailor.com';
			$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$site_reg      = implode( '.', array_slice( explode( '.', $site_host ?: '' ), -2 ) );
			$tt_reg        = implode( '.', array_slice( explode( '.', $tt_host ?: '' ), -2 ) );
			$domains_match = ( $site_reg === $tt_reg );
		?>
		<div class="notice notice-<?php echo $domains_match ? 'success' : 'warning'; ?> inline" style="margin:8px 0;padding:8px 12px;">
			<?php if ( $domains_match ) : ?>
				<p><strong><?php esc_html_e( 'Domain status:', 'sender-symposium' ); ?></strong>
				<?php
				/* translators: %1$s: TT host, %2$s: site host */
				printf( esc_html__( 'TicketTailor domain (%1$s) matches site domain (%2$s). Embedded checkout should work in all browsers.', 'sender-symposium' ), '<code>' . esc_html( $tt_host ) . '</code>', '<code>' . esc_html( $site_host ) . '</code>' );
				?></p>
			<?php else : ?>
				<p><strong><?php esc_html_e( 'Domain mismatch detected:', 'sender-symposium' ); ?></strong>
				<?php
				printf( esc_html__( 'TicketTailor domain (%1$s) differs from site domain (%2$s).', 'sender-symposium' ), '<code>' . esc_html( $tt_host ) . '</code>', '<code>' . esc_html( $site_host ) . '</code>' );
				?></p>
				<p><?php esc_html_e( 'Browsers may open checkout in a new page/window due to third-party cookie restrictions. To keep checkout embedded, set up a TicketTailor custom domain under your site domain (e.g. tickets.sendersymposium.com via CNAME).', 'sender-symposium' ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<p class="description">
			<?php esc_html_e( 'Tip: For the best inline checkout experience (no redirects), set up a custom domain in TicketTailor (e.g. tickets.yourdomain.com via CNAME). This prevents third-party cookie issues in Safari/Firefox.', 'sender-symposium' ); ?>
		</p>
	</fieldset>

	<?php /* ── Right Column ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Right Column', 'sender-symposium' ); ?></strong></legend>

		<p>
			<label><?php esc_html_e( 'Block Order', 'sender-symposium' ); ?><br>
			<select name="ss_ticketing[right_column][order]">
				<option value="info_first" <?php selected( $s['right_column']['order'], 'info_first' ); ?>><?php esc_html_e( 'Info card first', 'sender-symposium' ); ?></option>
				<option value="testimonial_first" <?php selected( $s['right_column']['order'], 'testimonial_first' ); ?>><?php esc_html_e( 'Testimonial first', 'sender-symposium' ); ?></option>
			</select></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Why This Is Different', 'sender-symposium' ); ?></h4>
		<p>
			<label><input type="checkbox" name="ss_ticketing[why_different][show]" value="1" <?php checked( $s['why_different']['show'] ); ?>>
			<?php esc_html_e( 'Show block', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Title', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[why_different][title]" value="<?php echo esc_attr( $s['why_different']['title'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Bullets (one per line, max 4)', 'sender-symposium' ); ?><br>
			<textarea name="ss_ticketing[why_different][bullets]" rows="4" class="large-text"><?php echo esc_textarea( $s['why_different']['bullets'] ); ?></textarea></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Information Card', 'sender-symposium' ); ?></h4>
		<p>
			<label><input type="checkbox" name="ss_ticketing[info_card][show]" value="1" <?php checked( $s['info_card']['show'] ); ?>>
			<?php esc_html_e( 'Show card', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Heading', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[info_card][heading]" value="<?php echo esc_attr( $s['info_card']['heading'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_ticketing[info_card][body]" rows="4" class="large-text"><?php echo esc_textarea( $s['info_card']['body'] ); ?></textarea></label>
		</p>

		<hr>
		<h4><?php esc_html_e( 'Testimonial', 'sender-symposium' ); ?></h4>
		<p>
			<label><input type="checkbox" name="ss_ticketing[testimonial][show]" value="1" <?php checked( $s['testimonial']['show'] ); ?>>
			<?php esc_html_e( 'Show testimonial', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Quote', 'sender-symposium' ); ?><br>
			<textarea name="ss_ticketing[testimonial][quote]" rows="3" class="large-text"><?php echo esc_textarea( $s['testimonial']['quote'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Name', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[testimonial][name]" value="<?php echo esc_attr( $s['testimonial']['name'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Role', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_ticketing[testimonial][role]" value="<?php echo esc_attr( $s['testimonial']['role'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Company', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[testimonial][company]" value="<?php echo esc_attr( $s['testimonial']['company'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Avatar', 'sender-symposium' ); ?></label><br>
			<?php ss_media_picker( 'ss_ticketing[testimonial][avatar_id]', $s['testimonial']['avatar_id'], __( 'Choose Avatar', 'sender-symposium' ) ); ?>
		</p>
	</fieldset>

	<?php /* ── Reassurance ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Reassurance', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_ticketing[reassurance][show]" value="1" <?php checked( $s['reassurance']['show'] ); ?>>
			<?php esc_html_e( 'Show reassurance line', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Text', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_ticketing[reassurance][text]" value="<?php echo esc_attr( $s['reassurance']['text'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Link URL', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="url" name="ss_ticketing[reassurance][link]" value="<?php echo esc_url( $s['reassurance']['link'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>
	<?php
}

/* ═══════════════════════════════════════════════════════════
   SAVE
   ═══════════════════════════════════════════════════════════ */

function ss_ticketing_save_meta_box( $post_id ) {
	/* Security checks */
	if ( ! isset( $_POST['ss_ticketing_nonce'] )
		|| ! wp_verify_nonce( $_POST['ss_ticketing_nonce'], 'ss_ticketing_save' ) ) {
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

	$raw   = $_POST['ss_ticketing'] ?? array();
	$clean = ss_sanitize_ticketing_settings( $raw );
	update_post_meta( $post_id, 'ss_ticketing_settings', $clean );
}
add_action( 'save_post_page', 'ss_ticketing_save_meta_box' );
