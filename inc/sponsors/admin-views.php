<?php
/**
 * Sponsors — Admin Views
 *
 * HTML rendering functions for the sponsors admin page tabs.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Main page ─── */

function ss_sponsors_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab = sanitize_key( $_GET['tab'] ?? 'sponsors' );
	$url = admin_url( 'admin.php?page=ss-sponsors' );

	ss_sponsors_admin_notices();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sponsors', 'sender-symposium' ); ?></h1>
		<nav class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'sponsors' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=sponsors' ); ?>"><?php esc_html_e( 'Sponsors', 'sender-symposium' ); ?></a>
			<a class="nav-tab <?php echo 'import' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=import' ); ?>"><?php esc_html_e( 'Import CSV', 'sender-symposium' ); ?></a>
			<a class="nav-tab <?php echo 'display' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=display' ); ?>"><?php esc_html_e( 'Display Settings', 'sender-symposium' ); ?></a>
		</nav>
		<div style="padding-top:20px;">
		<?php
		switch ( $tab ) {
			case 'import':
				ss_sponsors_render_import();
				break;
			case 'display':
				ss_sponsors_render_display();
				break;
			default:
				ss_sponsors_render_list();
		}
		?>
		</div>
	</div>
	<?php
}

/* ─── Admin notices ─── */

function ss_sponsors_admin_notices() {
	$msg = sanitize_key( $_GET['message'] ?? '' );
	if ( ! $msg ) {
		return;
	}

	$map = array(
		'import_no_file'     => array( 'error', __( 'Please select a CSV file.', 'sender-symposium' ) ),
		'sponsor_added'      => array( 'success', __( 'Sponsor added.', 'sender-symposium' ) ),
		'sponsor_updated'    => array( 'success', __( 'Sponsor updated.', 'sender-symposium' ) ),
		'sponsor_no_name'    => array( 'error', __( 'Sponsor name is required.', 'sender-symposium' ) ),
		'sponsor_not_found'  => array( 'error', __( 'Sponsor not found.', 'sender-symposium' ) ),
		'display_saved'      => array( 'success', __( 'Display settings saved.', 'sender-symposium' ) ),
		'reset_done'         => array( 'success', __( 'Sponsors reset to defaults.', 'sender-symposium' ) ),
	);

	if ( 'import_done' === $msg ) {
		$imported = absint( $_GET['imported'] ?? 0 );
		$updated  = absint( $_GET['updated'] ?? 0 );
		$skipped  = absint( $_GET['skipped'] ?? 0 );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf(
				__( 'Import complete: %1$d created, %2$d updated, %3$d skipped.', 'sender-symposium' ),
				$imported, $updated, $skipped
			) )
		);
		$warnings = get_transient( 'ss_sponsor_import_warnings' );
		if ( $warnings ) {
			delete_transient( 'ss_sponsor_import_warnings' );
			echo '<div class="notice notice-warning is-dismissible"><ul>';
			foreach ( $warnings as $w ) {
				printf( '<li>%s</li>', esc_html( $w ) );
			}
			echo '</ul></div>';
		}
	} elseif ( isset( $map[ $msg ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
	}
}

/* ─── Sponsors list tab ─── */

function ss_sponsors_render_list() {
	$action  = sanitize_key( $_GET['action'] ?? '' );
	$edit_id = sanitize_key( $_GET['id'] ?? '' );
	$url     = admin_url( 'admin.php?page=ss-sponsors&tab=sponsors' );

	/* Filter parameters */
	$filter_level  = sanitize_key( $_GET['level'] ?? '' );
	$filter_status = sanitize_key( $_GET['filter_status'] ?? '' );
	$search        = sanitize_text_field( $_GET['s'] ?? '' );

	if ( 'add' === $action || ( 'edit' === $action && $edit_id ) ) {
		$sponsor = 'edit' === $action ? ss_get_sponsor( $edit_id ) : null;
		ss_sponsors_render_form( $sponsor );
	}
	?>
	<div class="ss-sponsors-toolbar">
		<a href="<?php echo esc_url( $url . '&action=add' ); ?>" class="button button-primary"><?php esc_html_e( 'Add Sponsor', 'sender-symposium' ); ?></a>

		<form method="get" class="ss-sponsors-filters" style="display:inline-flex;gap:8px;align-items:center;margin-left:16px;">
			<input type="hidden" name="page" value="ss-sponsors" />
			<input type="hidden" name="tab" value="sponsors" />
			<select name="level">
				<option value=""><?php esc_html_e( 'All Levels', 'sender-symposium' ); ?></option>
				<?php foreach ( ss_sponsor_levels() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_level, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="filter_status">
				<option value=""><?php esc_html_e( 'All Statuses', 'sender-symposium' ); ?></option>
				<option value="confirmed" <?php selected( $filter_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'sender-symposium' ); ?></option>
				<option value="unconfirmed" <?php selected( $filter_status, 'unconfirmed' ); ?>><?php esc_html_e( 'Unconfirmed', 'sender-symposium' ); ?></option>
			</select>
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search sponsors...', 'sender-symposium' ); ?>" class="regular-text" style="max-width:200px;" />
			<?php submit_button( __( 'Filter', 'sender-symposium' ), 'secondary', 'submit', false ); ?>
		</form>

		<form method="post" style="display:inline;margin-left:8px;" onsubmit="return confirm('<?php esc_attr_e( 'Reset all sponsors to defaults? This cannot be undone.', 'sender-symposium' ); ?>');">
			<?php wp_nonce_field( 'ss_reset_sponsors' ); ?>
			<input type="hidden" name="ss_sponsors_action" value="reset_defaults" />
			<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset to Defaults', 'sender-symposium' ); ?></button>
		</form>
	</div>
	<?php
	$sponsors = ss_get_sponsors();
	usort( $sponsors, function ( $a, $b ) {
		$level_priority = array( 'platinum' => 0, 'gold' => 1, 'silver' => 2, 'bronze' => 3 );
		$la = $level_priority[ $a['sponsor_level'] ] ?? 9;
		$lb = $level_priority[ $b['sponsor_level'] ] ?? 9;
		if ( $la !== $lb ) {
			return $la - $lb;
		}
		return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
	} );

	/* Apply filters */
	if ( '' !== $filter_level ) {
		$sponsors = array_filter( $sponsors, function ( $s ) use ( $filter_level ) {
			return $s['sponsor_level'] === $filter_level;
		} );
	}
	if ( '' !== $filter_status ) {
		$sponsors = array_filter( $sponsors, function ( $s ) use ( $filter_status ) {
			return $s['status'] === $filter_status;
		} );
	}
	if ( '' !== $search ) {
		$sponsors = array_filter( $sponsors, function ( $s ) use ( $search ) {
			return false !== stripos( $s['sponsor_name'], $search );
		} );
	}
	$sponsors = array_values( $sponsors );

	if ( empty( $sponsors ) ) {
		echo '<p>' . esc_html__( 'No sponsors found. Add one or import from CSV.', 'sender-symposium' ) . '</p>';
		return;
	}
	?>
	<table class="wp-list-table widefat striped ss-sponsors-table">
		<thead>
			<tr>
				<th class="ss-col-drag"></th>
				<th class="ss-col-logo"><?php esc_html_e( 'Logo', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Name', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Level', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Status', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Featured', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Dark Logo', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'sender-symposium' ); ?></th>
			</tr>
		</thead>
		<tbody id="ss-sponsors-tbody">
			<?php foreach ( $sponsors as $s ) :
				$logo_url      = ss_sponsor_logo_url( $s );
				$dark_logo_url = ss_sponsor_dark_logo_url( $s );
				$dark_reco     = ss_sponsor_dark_logo_recommended( $s );
			?>
			<tr data-id="<?php echo esc_attr( $s['id'] ); ?>" draggable="true">
				<td class="ss-col-drag"><span class="ss-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'sender-symposium' ); ?>">&#9776;</span></td>
				<td class="ss-col-logo">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-height:32px;max-width:80px;object-fit:contain;" />
					<?php else : ?>
						<span class="description">—</span>
					<?php endif; ?>
				</td>
				<td>
					<strong><?php echo esc_html( $s['sponsor_name'] ); ?></strong>
					<?php if ( ! empty( $s['website_link'] ) ) : ?>
						<br><a href="<?php echo esc_url( $s['website_link'] ); ?>" target="_blank" rel="noopener" class="description"><?php echo esc_html( wp_parse_url( $s['website_link'], PHP_URL_HOST ) ); ?></a>
					<?php endif; ?>
				</td>
				<td><span class="ss-level-badge ss-level--<?php echo esc_attr( $s['sponsor_level'] ); ?>"><?php echo esc_html( ss_sponsor_level_label( $s['sponsor_level'] ) ); ?></span></td>
				<td>
					<button type="button" class="ss-status-toggle ss-status--<?php echo esc_attr( $s['status'] ); ?>" data-id="<?php echo esc_attr( $s['id'] ); ?>">
						<?php echo 'confirmed' === $s['status'] ? esc_html__( 'Confirmed', 'sender-symposium' ) : esc_html__( 'Unconfirmed', 'sender-symposium' ); ?>
					</button>
				</td>
				<td>
					<button type="button" class="ss-featured-toggle <?php echo $s['featured'] ? 'ss-featured--active' : ''; ?>" data-id="<?php echo esc_attr( $s['id'] ); ?>" aria-pressed="<?php echo $s['featured'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Toggle featured', 'sender-symposium' ); ?>">&#9733;</button>
				</td>
				<td>
					<?php if ( $dark_logo_url ) : ?>
						<span class="ss-dark-logo-ok" title="<?php esc_attr_e( 'Dark logo provided', 'sender-symposium' ); ?>">&#10003;</span>
					<?php elseif ( $dark_reco ) : ?>
						<span class="ss-dark-logo-reco" title="<?php esc_attr_e( "We can't know for sure. If this logo looks low-contrast in dark mode, upload an alternate version.", 'sender-symposium' ); ?>">
							<?php esc_html_e( 'Recommended', 'sender-symposium' ); ?>
							<button type="button" class="ss-dismiss-dark-reco" data-id="<?php echo esc_attr( $s['id'] ); ?>" title="<?php esc_attr_e( 'Dismiss', 'sender-symposium' ); ?>">&times;</button>
						</span>
					<?php else : ?>
						<span class="description">—</span>
					<?php endif; ?>
				</td>
				<td>
					<a href="<?php echo esc_url( $url . '&action=edit&id=' . $s['id'] ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'sender-symposium' ); ?></a>
					<button type="button" class="button button-small button-link-delete ss-delete-sponsor" data-id="<?php echo esc_attr( $s['id'] ); ?>"><?php esc_html_e( 'Delete', 'sender-symposium' ); ?></button>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/* ─── Sponsor add/edit form ─── */

function ss_sponsors_render_form( $sponsor = null ) {
	$is_edit = null !== $sponsor;
	$s = $is_edit ? $sponsor : array(
		'id' => '', 'sponsor_name' => '', 'sponsor_level' => 'bronze',
		'description' => '', 'website_link' => '',
		'image_url' => '', 'image_dark_url' => '',
		'logo_attachment_id' => 0, 'logo_dark_attachment_id' => 0,
		'featured' => false, 'status' => 'unconfirmed',
		'dark_logo_reco_dismissed' => false,
	);
	?>
	<div class="ss-sponsor-form-wrap">
		<h2><?php echo $is_edit ? esc_html__( 'Edit Sponsor', 'sender-symposium' ) : esc_html__( 'Add Sponsor', 'sender-symposium' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'ss_save_sponsor' ); ?>
			<input type="hidden" name="ss_sponsors_action" value="save_sponsor" />
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="sponsor[id]" value="<?php echo esc_attr( $s['id'] ); ?>" />
			<?php endif; ?>
			<table class="form-table">
				<tr><th><label for="ss-sname"><?php esc_html_e( 'Sponsor Name *', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-sname" name="sponsor[sponsor_name]" value="<?php echo esc_attr( $s['sponsor_name'] ); ?>" class="regular-text" required /></td></tr>
				<tr><th><label for="ss-slevel"><?php esc_html_e( 'Sponsor Level *', 'sender-symposium' ); ?></label></th>
					<td><select id="ss-slevel" name="sponsor[sponsor_level]">
						<?php foreach ( ss_sponsor_levels() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['sponsor_level'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></td></tr>
				<tr><th><label for="ss-sdesc"><?php esc_html_e( 'Description', 'sender-symposium' ); ?></label></th>
					<td><textarea id="ss-sdesc" name="sponsor[description]" class="large-text" rows="3"><?php echo esc_textarea( $s['description'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Short tagline or description. Basic HTML allowed.', 'sender-symposium' ); ?></p></td></tr>
				<tr><th><label for="ss-swebsite"><?php esc_html_e( 'Website URL', 'sender-symposium' ); ?></label></th>
					<td><input type="url" id="ss-swebsite" name="sponsor[website_link]" value="<?php echo esc_attr( $s['website_link'] ); ?>" class="regular-text" /></td></tr>
				<tr><th><label for="ss-slogo"><?php esc_html_e( 'Logo (Light Mode)', 'sender-symposium' ); ?></label></th>
					<td>
						<?php ss_sponsors_media_field( 'sponsor[logo_attachment_id]', $s['logo_attachment_id'] ?? 0, 'sponsor[image_url]', $s['image_url'] ?? '' ); ?>
					</td></tr>
				<tr><th><label for="ss-slogo-dark"><?php esc_html_e( 'Logo (Dark Mode)', 'sender-symposium' ); ?></label></th>
					<td>
						<?php ss_sponsors_media_field( 'sponsor[logo_dark_attachment_id]', $s['logo_dark_attachment_id'] ?? 0, 'sponsor[image_dark_url]', $s['image_dark_url'] ?? '' ); ?>
						<p class="description"><?php esc_html_e( 'Optional: provide an alternate logo for dark mode (white/mono version).', 'sender-symposium' ); ?></p>
					</td></tr>
				<tr><th><?php esc_html_e( 'Status', 'sender-symposium' ); ?></th>
					<td><select name="sponsor[status]">
						<option value="unconfirmed" <?php selected( $s['status'], 'unconfirmed' ); ?>><?php esc_html_e( 'Unconfirmed', 'sender-symposium' ); ?></option>
						<option value="confirmed" <?php selected( $s['status'], 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'sender-symposium' ); ?></option>
					</select></td></tr>
				<tr><th><?php esc_html_e( 'Featured', 'sender-symposium' ); ?></th>
					<td><label><input type="checkbox" name="sponsor[featured]" value="1" <?php checked( $s['featured'] ); ?> /> <?php esc_html_e( 'Mark as featured (headline placement)', 'sender-symposium' ); ?></label></td></tr>
			</table>
			<?php submit_button( $is_edit ? __( 'Update Sponsor', 'sender-symposium' ) : __( 'Add Sponsor', 'sender-symposium' ) ); ?>
		</form>
	</div>
	<hr />
	<?php
}

/**
 * Render a combined media library picker + URL fallback field.
 */
function ss_sponsors_media_field( $attachment_name, $attachment_id, $url_name, $url_value ) {
	$attachment_id = absint( $attachment_id );
	$preview_url   = '';
	if ( $attachment_id ) {
		$preview_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
	}
	if ( ! $preview_url && $url_value ) {
		$preview_url = $url_value;
	}
	?>
	<div class="ss-sponsor-media-field">
		<input type="hidden" name="<?php echo esc_attr( $attachment_name ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" class="ss-sponsor-media-id" />
		<div class="ss-sponsor-media-preview">
			<?php if ( $preview_url ) : ?>
				<img src="<?php echo esc_url( $preview_url ); ?>" alt="" style="max-height:48px;max-width:120px;object-fit:contain;" />
			<?php endif; ?>
		</div>
		<button type="button" class="button ss-sponsor-media-choose"><?php esc_html_e( 'Choose from Media Library', 'sender-symposium' ); ?></button>
		<button type="button" class="button-link ss-sponsor-media-remove" style="<?php echo $attachment_id ? '' : 'display:none;'; ?>color:#a00;margin-left:8px;"><?php esc_html_e( 'Remove', 'sender-symposium' ); ?></button>
		<div style="margin-top:8px;">
			<label class="description"><?php esc_html_e( 'Or paste URL:', 'sender-symposium' ); ?></label>
			<input type="url" name="<?php echo esc_attr( $url_name ); ?>" value="<?php echo esc_attr( $url_value ); ?>" class="regular-text ss-sponsor-url-fallback" />
		</div>
	</div>
	<?php
}

/* ─── Import tab ─── */

function ss_sponsors_render_import() {
	?>
	<h2><?php esc_html_e( 'Import Sponsors from CSV', 'sender-symposium' ); ?></h2>
	<p><?php esc_html_e( 'Upload a CSV file with sponsor data. By default, imported sponsors are set to "Unconfirmed" and duplicates (same name + website + level) are updated.', 'sender-symposium' ); ?></p>
	<div class="ss-csv-example">
		<h4><?php esc_html_e( 'Expected CSV format:', 'sender-symposium' ); ?></h4>
		<code>sponsor_name,sponsor_level,description,image_url,website_link</code>
		<p class="description"><?php esc_html_e( 'Required: sponsor_name, sponsor_level. Optional: description, image_url, image_dark_url, website_link, position, status. Headers are matched case-insensitively.', 'sender-symposium' ); ?></p>
		<p class="description"><?php esc_html_e( 'Accepted levels: Platinum Headline Partner, Gold, Silver, Bronze (case-insensitive).', 'sender-symposium' ); ?></p>
	</div>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'ss_import_sponsors_csv' ); ?>
		<input type="hidden" name="ss_sponsors_action" value="import_csv" />
		<table class="form-table">
			<tr><th><label for="csv-file"><?php esc_html_e( 'CSV File', 'sender-symposium' ); ?></label></th>
				<td><input type="file" id="csv-file" name="csv_file" accept=".csv,text/csv" required /></td></tr>
			<tr><th><?php esc_html_e( 'Duplicate Handling', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="always_new" value="1" /> <?php esc_html_e( 'Always create new entries (do not update existing)', 'sender-symposium' ); ?></label></td></tr>
		</table>
		<?php submit_button( __( 'Import', 'sender-symposium' ) ); ?>
	</form>
	<?php
}

/* ─── Display settings tab ─── */

function ss_sponsors_render_display() {
	$d = ss_get_sponsors_display();
	?>
	<form method="post">
		<?php wp_nonce_field( 'ss_save_sponsors_display' ); ?>
		<input type="hidden" name="ss_sponsors_action" value="save_display" />

		<h3><?php esc_html_e( 'Page Copy', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><label for="d-title"><?php esc_html_e( 'Page Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-title" name="display[page_title]" value="<?php echo esc_attr( $d['page_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-sub"><?php esc_html_e( 'Page Subtitle', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-sub" name="display[page_subtitle]" value="<?php echo esc_attr( $d['page_subtitle'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-intro"><?php esc_html_e( 'Intro Text', 'sender-symposium' ); ?></label></th>
				<td><textarea id="d-intro" name="display[intro_text]" class="large-text" rows="3"><?php echo esc_textarea( $d['intro_text'] ); ?></textarea></td></tr>
		</table>

		<h3><?php esc_html_e( 'Section Headings', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><label for="d-pt"><?php esc_html_e( 'Platinum Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-pt" name="display[platinum_title]" value="<?php echo esc_attr( $d['platinum_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-pet"><?php esc_html_e( 'Platinum Empty Text', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-pet" name="display[platinum_empty_text]" value="<?php echo esc_attr( $d['platinum_empty_text'] ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Shown when no platinum sponsor exists.', 'sender-symposium' ); ?></p></td></tr>
			<tr><th><label for="d-pec"><?php esc_html_e( 'Platinum Empty CTA', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-pec" name="display[platinum_empty_cta]" value="<?php echo esc_attr( $d['platinum_empty_cta'] ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Becomes a link when a URL is set below.', 'sender-symposium' ); ?></p></td></tr>
			<tr><th><label for="d-peu"><?php esc_html_e( 'Platinum Empty CTA URL', 'sender-symposium' ); ?></label></th>
				<td><input type="url" id="d-peu" name="display[platinum_empty_url]" value="<?php echo esc_attr( $d['platinum_empty_url'] ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Link destination for the CTA text above. Leave empty for plain text.', 'sender-symposium' ); ?></p></td></tr>
			<tr><th><label for="d-gt"><?php esc_html_e( 'Gold Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-gt" name="display[gold_title]" value="<?php echo esc_attr( $d['gold_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-st"><?php esc_html_e( 'Silver Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-st" name="display[silver_title]" value="<?php echo esc_attr( $d['silver_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-bt"><?php esc_html_e( 'Bronze Section Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-bt" name="display[bronze_title]" value="<?php echo esc_attr( $d['bronze_title'] ); ?>" class="regular-text" /></td></tr>
		</table>

		<h3><?php esc_html_e( 'Call-to-Action', 'sender-symposium' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Shown at the bottom of the sponsors page. Set a Button URL to make the heading a link and display the CTA button. Leave the heading empty to hide the entire section.', 'sender-symposium' ); ?></p>
		<table class="form-table">
			<tr><th><label for="d-ctah"><?php esc_html_e( 'CTA Heading', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-ctah" name="display[cta_heading]" value="<?php echo esc_attr( $d['cta_heading'] ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Becomes a clickable link when a Button URL is set.', 'sender-symposium' ); ?></p></td></tr>
			<tr><th><label for="d-ctat"><?php esc_html_e( 'CTA Text', 'sender-symposium' ); ?></label></th>
				<td><textarea id="d-ctat" name="display[cta_text]" class="large-text" rows="2"><?php echo esc_textarea( $d['cta_text'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Optional supporting copy below the heading.', 'sender-symposium' ); ?></p></td></tr>
			<tr><th><label for="d-ctabl"><?php esc_html_e( 'Button Label', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-ctabl" name="display[cta_button_label]" value="<?php echo esc_attr( $d['cta_button_label'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-ctabu"><?php esc_html_e( 'Button URL', 'sender-symposium' ); ?></label></th>
				<td><input type="url" id="d-ctabu" name="display[cta_button_url]" value="<?php echo esc_attr( $d['cta_button_url'] ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Required for the heading link and button to appear.', 'sender-symposium' ); ?></p></td></tr>
		</table>

		<h3><?php esc_html_e( 'Layout', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Grid Columns (Desktop)', 'sender-symposium' ); ?></th>
				<td><select name="display[grid_columns_desktop]">
					<?php foreach ( array( '2', '3', '4', '5' ) as $v ) : ?>
						<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $d['grid_columns_desktop'], $v ); ?>><?php echo esc_html( $v ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Grid Columns (Tablet)', 'sender-symposium' ); ?></th>
				<td><select name="display[grid_columns_tablet]">
					<?php foreach ( array( '2', '3', '4' ) as $v ) : ?>
						<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $d['grid_columns_tablet'], $v ); ?>><?php echo esc_html( $v ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Grid Columns (Mobile)', 'sender-symposium' ); ?></th>
				<td><select name="display[grid_columns_mobile]">
					<?php foreach ( array( '1', '2' ) as $v ) : ?>
						<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $d['grid_columns_mobile'], $v ); ?>><?php echo esc_html( $v ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Card Density', 'sender-symposium' ); ?></th>
				<td><select name="display[card_density]">
					<option value="comfortable" <?php selected( $d['card_density'], 'comfortable' ); ?>><?php esc_html_e( 'Comfortable', 'sender-symposium' ); ?></option>
					<option value="compact" <?php selected( $d['card_density'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'sender-symposium' ); ?></option>
				</select></td></tr>
			<tr><th><label for="d-lmh"><?php esc_html_e( 'Logo Max Height (px)', 'sender-symposium' ); ?></label></th>
				<td><input type="number" id="d-lmh" name="display[logo_max_height]" value="<?php echo esc_attr( $d['logo_max_height'] ); ?>" min="32" max="160" step="4" style="width:80px;" /></td></tr>
			<tr><th><?php esc_html_e( 'Show Descriptions', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="display[show_descriptions]" value="1" <?php checked( $d['show_descriptions'] ); ?> /> <?php esc_html_e( 'Show sponsor descriptions on cards', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Open Links in New Tab', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="display[open_links_new_tab]" value="1" <?php checked( $d['open_links_new_tab'] ); ?> /> <?php esc_html_e( 'Open sponsor website links in a new tab', 'sender-symposium' ); ?></label></td></tr>
		</table>
		<?php submit_button( __( 'Save Display Settings', 'sender-symposium' ) ); ?>
	</form>
	<?php
}
