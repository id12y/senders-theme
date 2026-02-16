<?php
/**
 * About Page — Meta Box & Settings
 *
 * Stores all about page settings as a single structured post-meta array:
 *   ss_about_settings
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ═══════════════════════════════════════════════════════════
   DEFAULTS
   ═══════════════════════════════════════════════════════════ */

function ss_about_defaults() {
	return array(
		'intro' => array(
			'enabled'  => true,
			'overline' => '',
			'headline' => __( 'Built by operators. For operators.', 'sender-symposium' ),
			'body'     => '<p>Emailexpert has delivered 16 in-person industry events across 8 countries, alongside multiple online conferences and working sessions. More than 3,500 professionals have participated, with speakers and delegates representing over 40 countries. Across these events, over 400 expert speakers have contributed to programme discussions covering deliverability, lifecycle messaging, CRM, compliance, and revenue strategy.</p>',
		),
		'stats' => array(
			'enabled' => true,
			'items'   => array(
				array( 'number' => '16',    'label' => __( 'In-Person Events', 'sender-symposium' ) ),
				array( 'number' => '3,500+', 'label' => __( 'Professionals Engaged', 'sender-symposium' ) ),
				array( 'number' => '8',     'label' => __( 'Countries Hosted', 'sender-symposium' ) ),
				array( 'number' => '40+',   'label' => __( 'Countries Represented', 'sender-symposium' ) ),
				array( 'number' => '400+',  'label' => __( 'Speakers Hosted', 'sender-symposium' ) ),
			),
		),
		'track_record' => array(
			'enabled' => true,
			'title'   => __( 'Our Track Record', 'sender-symposium' ),
			'items'   => array(
				array(
					'name' => 'Deliverability Summit',
					'meta' => __( 'Amsterdam · 2024–2025', 'sender-symposium' ),
					'desc' => __( 'Full-venue in-person conference focused on infrastructure, compliance, and measurable email performance.', 'sender-symposium' ),
				),
				array(
					'name' => 'Emailexpert User Conference',
					'meta' => __( 'Alicante · 2025', 'sender-symposium' ),
					'desc' => __( 'Working-format event for CRM, lifecycle, and messaging platform leaders.', 'sender-symposium' ),
				),
				array(
					'name' => __( 'Industry Roundtables', 'sender-symposium' ),
					'meta' => __( 'Multi-city', 'sender-symposium' ),
					'desc' => __( 'Small-format peer sessions designed for senior operators in the email ecosystem.', 'sender-symposium' ),
				),
				array(
					'name' => __( 'Industry Awards Dinners', 'sender-symposium' ),
					'meta' => __( 'International', 'sender-symposium' ),
					'desc' => __( 'Recognition events acknowledging measurable achievement across email and lifecycle strategy.', 'sender-symposium' ),
				),
			),
		),
		'contributors' => array(
			'enabled'     => true,
			'title'       => __( 'Curated by Practitioners', 'sender-symposium' ),
			'subtitle'    => __( 'Sender Symposium is structured and moderated by experienced operators drawn from across the CRM, lifecycle, and email ecosystem. Programme contributors bring practical experience from within complex messaging environments.', 'sender-symposium' ),
			'speaker_ids' => array(),
		),
		'philosophy' => array(
			'enabled' => true,
			'title'   => __( 'Why Sender Symposium Is Different', 'sender-symposium' ),
			'points'  => "Built as a working room, not a keynote marathon.\nExtended audience Q&A, not passive listening.\nLimited seats to protect depth and interaction.\nNo expo floor or vendor booths.\nPeer-level conversation over stage performance.",
		),
		'gallery' => array(
			'enabled'   => true,
			'title'     => __( 'From Our Events', 'sender-symposium' ),
			'image_ids' => array(),
		),
		'location' => array(
			'enabled' => true,
			'title'   => __( 'Why La Pedrera', 'sender-symposium' ),
			'body'    => '<p>La Pedrera provides an architectural setting designed for conversation rather than scale. Its structure supports proximity, clarity, and focused exchange &mdash; aligned with the format of a working conference.</p>',
		),
		'closing' => array(
			'enabled'     => true,
			'statement'   => __( 'Sender Symposium is intentionally limited. If you are accountable for lifecycle revenue, CRM performance, or messaging infrastructure, we would value your participation.', 'sender-symposium' ),
			'button_text' => __( 'Secure your seat', 'sender-symposium' ),
			'button_url'  => '/tickets/',
		),
	);
}

/* ═══════════════════════════════════════════════════════════
   GET SETTINGS (deep-merged with defaults)
   ═══════════════════════════════════════════════════════════ */

function ss_get_about_settings( $post_id ) {
	$saved    = get_post_meta( $post_id, 'ss_about_settings', true );
	$defaults = ss_about_defaults();

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

function ss_sanitize_about_settings( $raw ) {
	$clean = array();

	/* Intro */
	$intro = $raw['intro'] ?? array();
	$clean['intro'] = array(
		'enabled'  => ! empty( $intro['enabled'] ),
		'overline' => sanitize_text_field( $intro['overline'] ?? '' ),
		'headline' => sanitize_text_field( $intro['headline'] ?? '' ),
		'body'     => wp_kses_post( $intro['body'] ?? '' ),
	);

	/* Stats */
	$stats = $raw['stats'] ?? array();
	$clean['stats'] = array(
		'enabled' => ! empty( $stats['enabled'] ),
		'items'   => array(),
	);
	if ( ! empty( $stats['items'] ) && is_array( $stats['items'] ) ) {
		foreach ( $stats['items'] as $item ) {
			$num   = sanitize_text_field( $item['number'] ?? '' );
			$label = sanitize_text_field( $item['label'] ?? '' );
			if ( '' !== $num || '' !== $label ) {
				$clean['stats']['items'][] = array(
					'number' => $num,
					'label'  => $label,
				);
			}
		}
	}

	/* Track Record */
	$tr = $raw['track_record'] ?? array();
	$clean['track_record'] = array(
		'enabled' => ! empty( $tr['enabled'] ),
		'title'   => sanitize_text_field( $tr['title'] ?? '' ),
		'items'   => array(),
	);
	if ( ! empty( $tr['items'] ) && is_array( $tr['items'] ) ) {
		foreach ( $tr['items'] as $item ) {
			$name = sanitize_text_field( $item['name'] ?? '' );
			if ( '' !== $name ) {
				$clean['track_record']['items'][] = array(
					'name' => $name,
					'meta' => sanitize_text_field( $item['meta'] ?? '' ),
					'desc' => sanitize_text_field( $item['desc'] ?? '' ),
				);
			}
		}
	}

	/* Contributors */
	$cont = $raw['contributors'] ?? array();
	$clean['contributors'] = array(
		'enabled'     => ! empty( $cont['enabled'] ),
		'title'       => sanitize_text_field( $cont['title'] ?? '' ),
		'subtitle'    => sanitize_text_field( $cont['subtitle'] ?? '' ),
		'speaker_ids' => array(),
	);
	if ( ! empty( $cont['speaker_ids'] ) && is_array( $cont['speaker_ids'] ) ) {
		foreach ( $cont['speaker_ids'] as $entry ) {
			$sid = sanitize_key( $entry['id'] ?? '' );
			if ( '' !== $sid ) {
				$clean['contributors']['speaker_ids'][] = array(
					'id'         => $sid,
					'role_label' => sanitize_text_field( $entry['role_label'] ?? '' ),
					'short_note' => sanitize_text_field( $entry['short_note'] ?? '' ),
				);
			}
		}
	}

	/* Philosophy */
	$phil = $raw['philosophy'] ?? array();
	$clean['philosophy'] = array(
		'enabled' => ! empty( $phil['enabled'] ),
		'title'   => sanitize_text_field( $phil['title'] ?? '' ),
		'points'  => sanitize_textarea_field( $phil['points'] ?? '' ),
	);

	/* Gallery */
	$gal = $raw['gallery'] ?? array();
	$clean['gallery'] = array(
		'enabled'   => ! empty( $gal['enabled'] ),
		'title'     => sanitize_text_field( $gal['title'] ?? '' ),
		'image_ids' => array(),
	);
	if ( ! empty( $gal['image_ids'] ) && is_array( $gal['image_ids'] ) ) {
		foreach ( $gal['image_ids'] as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$clean['gallery']['image_ids'][] = $id;
			}
		}
	}

	/* Location */
	$loc = $raw['location'] ?? array();
	$clean['location'] = array(
		'enabled' => ! empty( $loc['enabled'] ),
		'title'   => sanitize_text_field( $loc['title'] ?? '' ),
		'body'    => wp_kses_post( $loc['body'] ?? '' ),
	);

	/* Closing */
	$cls = $raw['closing'] ?? array();
	$clean['closing'] = array(
		'enabled'     => ! empty( $cls['enabled'] ),
		'statement'   => sanitize_text_field( $cls['statement'] ?? '' ),
		'button_text' => sanitize_text_field( $cls['button_text'] ?? '' ),
		'button_url'  => esc_url_raw( $cls['button_url'] ?? '' ),
	);

	return $clean;
}

/* ═══════════════════════════════════════════════════════════
   REGISTER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_about_register_meta_box() {
	add_meta_box(
		'ss_about_meta_box',
		__( 'About Page Settings', 'sender-symposium' ),
		'ss_about_render_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ss_about_register_meta_box' );

/* ═══════════════════════════════════════════════════════════
   RENDER META BOX
   ═══════════════════════════════════════════════════════════ */

function ss_about_render_meta_box( $post ) {
	$s = ss_get_about_settings( $post->ID );
	wp_nonce_field( 'ss_about_save', 'ss_about_nonce' );

	$fs = 'border:1px solid #ccd0d4;padding:12px 16px;margin-bottom:16px;border-radius:4px;background:#fff;';
	?>
	<p class="description" style="margin-bottom:16px;">
		<?php esc_html_e( 'These settings apply when the "About Page" template is selected for this page.', 'sender-symposium' ); ?>
	</p>

	<?php /* ── Intro ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Intro', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[intro][enabled]" value="1" <?php checked( $s['intro']['enabled'] ); ?>>
			<?php esc_html_e( 'Show intro section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Overline', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'optional', 'sender-symposium' ); ?>)</small><br>
			<input type="text" name="ss_about[intro][overline]" value="<?php echo esc_attr( $s['intro']['overline'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Headline (H1)', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[intro][headline]" value="<?php echo esc_attr( $s['intro']['headline'] ); ?>" class="large-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_about[intro][body]" rows="5" class="large-text"><?php echo esc_textarea( $s['intro']['body'] ); ?></textarea></label>
		</p>
	</fieldset>

	<?php /* ── Stats ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Stats Strip', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[stats][enabled]" value="1" <?php checked( $s['stats']['enabled'] ); ?>>
			<?php esc_html_e( 'Show stats strip', 'sender-symposium' ); ?></label>
		</p>
		<table class="widefat" style="max-width:500px;">
			<thead><tr><th><?php esc_html_e( 'Number', 'sender-symposium' ); ?></th><th><?php esc_html_e( 'Label', 'sender-symposium' ); ?></th></tr></thead>
			<tbody>
			<?php
			$stat_items = $s['stats']['items'];
			for ( $i = 0; $i < 8; $i++ ) :
				$num   = $stat_items[ $i ]['number'] ?? '';
				$label = $stat_items[ $i ]['label'] ?? '';
			?>
			<tr>
				<td><input type="text" name="ss_about[stats][items][<?php echo $i; ?>][number]" value="<?php echo esc_attr( $num ); ?>" style="width:80px;"></td>
				<td><input type="text" name="ss_about[stats][items][<?php echo $i; ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text"></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Leave both fields empty to remove a stat.', 'sender-symposium' ); ?></p>
	</fieldset>

	<?php /* ── Track Record ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Track Record', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[track_record][enabled]" value="1" <?php checked( $s['track_record']['enabled'] ); ?>>
			<?php esc_html_e( 'Show track record section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[track_record][title]" value="<?php echo esc_attr( $s['track_record']['title'] ); ?>" class="regular-text"></label>
		</p>
		<table class="widefat" style="max-width:900px;">
			<thead><tr>
				<th><?php esc_html_e( 'Event Name', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Meta (year · location · scope)', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Description', 'sender-symposium' ); ?></th>
			</tr></thead>
			<tbody>
			<?php
			$tr_items = $s['track_record']['items'];
			for ( $i = 0; $i < 8; $i++ ) :
				$name = $tr_items[ $i ]['name'] ?? '';
				$meta = $tr_items[ $i ]['meta'] ?? '';
				$desc = $tr_items[ $i ]['desc'] ?? '';
			?>
			<tr>
				<td><input type="text" name="ss_about[track_record][items][<?php echo $i; ?>][name]" value="<?php echo esc_attr( $name ); ?>" class="regular-text"></td>
				<td><input type="text" name="ss_about[track_record][items][<?php echo $i; ?>][meta]" value="<?php echo esc_attr( $meta ); ?>" class="regular-text"></td>
				<td><input type="text" name="ss_about[track_record][items][<?php echo $i; ?>][desc]" value="<?php echo esc_attr( $desc ); ?>" class="large-text"></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Leave event name empty to remove a row.', 'sender-symposium' ); ?></p>
	</fieldset>

	<?php /* ── Contributors (Speaker Selector) ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Contributors', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[contributors][enabled]" value="1" <?php checked( $s['contributors']['enabled'] ); ?>>
			<?php esc_html_e( 'Show contributors section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[contributors][title]" value="<?php echo esc_attr( $s['contributors']['title'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Subtitle', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[contributors][subtitle]" value="<?php echo esc_attr( $s['contributors']['subtitle'] ); ?>" class="large-text"></label>
		</p>
		<?php
		$all_speakers = ss_get_speakers();
		$selected     = $s['contributors']['speaker_ids'];
		?>
		<h4><?php esc_html_e( 'Selected Contributors (max 6)', 'sender-symposium' ); ?></h4>
		<table class="widefat" style="max-width:900px;">
			<thead><tr>
				<th><?php esc_html_e( 'Speaker', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Role Label', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Short Note', 'sender-symposium' ); ?></th>
			</tr></thead>
			<tbody>
			<?php for ( $i = 0; $i < 6; $i++ ) :
				$sel_id    = $selected[ $i ]['id'] ?? '';
				$sel_role  = $selected[ $i ]['role_label'] ?? '';
				$sel_note  = $selected[ $i ]['short_note'] ?? '';
			?>
			<tr>
				<td>
					<select name="ss_about[contributors][speaker_ids][<?php echo $i; ?>][id]" style="min-width:200px;">
						<option value=""><?php esc_html_e( '— Select speaker —', 'sender-symposium' ); ?></option>
						<?php foreach ( $all_speakers as $spk ) : ?>
						<option value="<?php echo esc_attr( $spk['id'] ); ?>" <?php selected( $sel_id, $spk['id'] ); ?>>
							<?php echo esc_html( $spk['name'] . ( ! empty( $spk['company'] ) ? ' — ' . $spk['company'] : '' ) ); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
				<td><input type="text" name="ss_about[contributors][speaker_ids][<?php echo $i; ?>][role_label]" value="<?php echo esc_attr( $sel_role ); ?>" placeholder="<?php esc_attr_e( 'e.g. Curator', 'sender-symposium' ); ?>" class="regular-text"></td>
				<td><input type="text" name="ss_about[contributors][speaker_ids][<?php echo $i; ?>][short_note]" value="<?php echo esc_attr( $sel_note ); ?>" class="large-text"></td>
			</tr>
			<?php endfor; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Select from existing speakers. Manage speakers in Appearance → Theme Settings → Speakers.', 'sender-symposium' ); ?></p>
	</fieldset>

	<?php /* ── Philosophy ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Philosophy', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[philosophy][enabled]" value="1" <?php checked( $s['philosophy']['enabled'] ); ?>>
			<?php esc_html_e( 'Show philosophy section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[philosophy][title]" value="<?php echo esc_attr( $s['philosophy']['title'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Points (one per line)', 'sender-symposium' ); ?><br>
			<textarea name="ss_about[philosophy][points]" rows="6" class="large-text"><?php echo esc_textarea( $s['philosophy']['points'] ); ?></textarea></label>
		</p>
	</fieldset>

	<?php /* ── Gallery ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Gallery', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[gallery][enabled]" value="1" <?php checked( $s['gallery']['enabled'] ); ?>>
			<?php esc_html_e( 'Show gallery section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[gallery][title]" value="<?php echo esc_attr( $s['gallery']['title'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Image IDs (comma-separated)', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[gallery][image_ids_raw]" value="<?php echo esc_attr( implode( ', ', $s['gallery']['image_ids'] ) ); ?>" class="large-text" placeholder="123, 456, 789"></label>
		</p>
		<p class="description"><?php esc_html_e( 'Enter attachment IDs separated by commas (4–8 images recommended). Or use the gallery picker below.', 'sender-symposium' ); ?></p>
		<div class="ss-media-picker" data-title="<?php esc_attr_e( 'Select Gallery Images', 'sender-symposium' ); ?>" data-button="<?php esc_attr_e( 'Add to gallery', 'sender-symposium' ); ?>" data-gallery="true">
			<input type="hidden" class="ss-media-picker__id" value="<?php echo esc_attr( implode( ',', $s['gallery']['image_ids'] ) ); ?>" data-target-field="ss_about[gallery][image_ids_raw]">
			<div class="ss-media-picker__preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
				<?php foreach ( $s['gallery']['image_ids'] as $img_id ) :
					$thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
					if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button ss-gallery-picker__choose"><?php esc_html_e( 'Choose Gallery Images', 'sender-symposium' ); ?></button>
		</div>
	</fieldset>

	<?php /* ── Location ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Location', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[location][enabled]" value="1" <?php checked( $s['location']['enabled'] ); ?>>
			<?php esc_html_e( 'Show location section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Section Title', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[location][title]" value="<?php echo esc_attr( $s['location']['title'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Body', 'sender-symposium' ); ?>
			<small>(<?php esc_html_e( 'HTML allowed', 'sender-symposium' ); ?>)</small><br>
			<textarea name="ss_about[location][body]" rows="4" class="large-text"><?php echo esc_textarea( $s['location']['body'] ); ?></textarea></label>
		</p>
	</fieldset>

	<?php /* ── Closing ── */ ?>
	<fieldset style="<?php echo $fs; ?>">
		<legend><strong><?php esc_html_e( 'Closing + CTA', 'sender-symposium' ); ?></strong></legend>
		<p>
			<label><input type="checkbox" name="ss_about[closing][enabled]" value="1" <?php checked( $s['closing']['enabled'] ); ?>>
			<?php esc_html_e( 'Show closing section', 'sender-symposium' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Statement', 'sender-symposium' ); ?><br>
			<textarea name="ss_about[closing][statement]" rows="3" class="large-text"><?php echo esc_textarea( $s['closing']['statement'] ); ?></textarea></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Button Text', 'sender-symposium' ); ?><br>
			<input type="text" name="ss_about[closing][button_text]" value="<?php echo esc_attr( $s['closing']['button_text'] ); ?>" class="regular-text"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Button URL', 'sender-symposium' ); ?><br>
			<input type="url" name="ss_about[closing][button_url]" value="<?php echo esc_url( $s['closing']['button_url'] ); ?>" class="regular-text"></label>
		</p>
	</fieldset>
	<?php
}

/* ═══════════════════════════════════════════════════════════
   SAVE
   ═══════════════════════════════════════════════════════════ */

function ss_about_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['ss_about_nonce'] )
		|| ! wp_verify_nonce( $_POST['ss_about_nonce'], 'ss_about_save' ) ) {
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

	$raw = wp_unslash( $_POST['ss_about'] ?? array() );

	/* Convert gallery comma-separated string to array before sanitize */
	if ( ! empty( $raw['gallery']['image_ids_raw'] ) ) {
		$raw['gallery']['image_ids'] = array_map( 'absint', array_filter(
			array_map( 'trim', explode( ',', $raw['gallery']['image_ids_raw'] ) )
		) );
	}
	unset( $raw['gallery']['image_ids_raw'] );

	$clean = ss_sanitize_about_settings( $raw );
	update_post_meta( $post_id, 'ss_about_settings', $clean );
}
add_action( 'save_post_page', 'ss_about_save_meta_box' );
