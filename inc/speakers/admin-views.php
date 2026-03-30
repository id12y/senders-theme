<?php
/**
 * Speakers — Admin Views
 *
 * HTML rendering functions for the speakers admin page tabs.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Main page ─── */

function ss_speakers_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab = sanitize_key( $_GET['tab'] ?? 'speakers' );
	$url = admin_url( 'admin.php?page=ss-speakers' );

	ss_speakers_admin_notices();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Speakers', 'sender-symposium' ); ?></h1>
		<nav class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'speakers' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=speakers' ); ?>"><?php esc_html_e( 'Speakers', 'sender-symposium' ); ?></a>
			<a class="nav-tab <?php echo 'import' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=import' ); ?>"><?php esc_html_e( 'Import CSV', 'sender-symposium' ); ?></a>
			<a class="nav-tab <?php echo 'display' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=display' ); ?>"><?php esc_html_e( 'Display Settings', 'sender-symposium' ); ?></a>
		</nav>
		<div style="padding-top:20px;">
		<?php
		switch ( $tab ) {
			case 'import':
				ss_speakers_render_import();
				break;
			case 'display':
				ss_speakers_render_display();
				break;
			default:
				ss_speakers_render_list();
		}
		?>
		</div>
	</div>
	<?php
}

/* ─── Admin notices ─── */

function ss_speakers_admin_notices() {
	$msg = sanitize_key( $_GET['message'] ?? '' );
	if ( ! $msg ) {
		return;
	}

	$map = array(
		'import_no_file'    => array( 'error', __( 'Please select a CSV file.', 'sender-symposium' ) ),
		'speaker_added'     => array( 'success', __( 'Speaker added.', 'sender-symposium' ) ),
		'speaker_updated'   => array( 'success', __( 'Speaker updated.', 'sender-symposium' ) ),
		'speaker_no_name'   => array( 'error', __( 'Speaker name is required.', 'sender-symposium' ) ),
		'speaker_not_found' => array( 'error', __( 'Speaker not found.', 'sender-symposium' ) ),
		'display_saved'     => array( 'success', __( 'Display settings saved.', 'sender-symposium' ) ),
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
		$warnings = get_transient( 'ss_import_warnings' );
		if ( $warnings ) {
			delete_transient( 'ss_import_warnings' );
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

/* ─── Speakers list tab ─── */

function ss_speakers_render_list() {
	$action  = sanitize_key( $_GET['action'] ?? '' );
	$edit_id = sanitize_key( $_GET['id'] ?? '' );
	$url     = admin_url( 'admin.php?page=ss-speakers&tab=speakers' );

	if ( 'add' === $action || ( 'edit' === $action && $edit_id ) ) {
		$speaker = 'edit' === $action ? ss_get_speaker( $edit_id ) : null;
		ss_speakers_render_form( $speaker );
	}
	?>
	<?php $export_url = wp_nonce_url( admin_url( 'admin.php?page=ss-speakers&ss_speakers_export=csv' ), 'ss_speakers_export' ); ?>
	<p>
		<a href="<?php echo esc_url( $url . '&action=add' ); ?>" class="button button-primary"><?php esc_html_e( 'Add Speaker', 'sender-symposium' ); ?></a>
		<a href="<?php echo esc_url( $export_url ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'sender-symposium' ); ?></a>
	</p>
	<?php
	$speakers = ss_get_speakers();
	usort( $speakers, function ( $a, $b ) { return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 ); } );

	if ( empty( $speakers ) ) {
		echo '<p>' . esc_html__( 'No speakers yet. Add one or import from CSV.', 'sender-symposium' ) . '</p>';
		return;
	}
	?>
	<table class="wp-list-table widefat striped ss-speakers-table">
		<thead>
			<tr>
				<th class="ss-col-drag"></th>
				<th><?php esc_html_e( 'Name', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Company', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Status', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Featured', 'sender-symposium' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'sender-symposium' ); ?></th>
			</tr>
		</thead>
		<tbody id="ss-speakers-tbody">
			<?php foreach ( $speakers as $s ) : ?>
			<tr data-id="<?php echo esc_attr( $s['id'] ); ?>" draggable="true">
				<td class="ss-col-drag"><span class="ss-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'sender-symposium' ); ?>">&#9776;</span></td>
				<td>
					<strong><?php echo esc_html( $s['name'] ); ?></strong>
					<?php if ( $s['job_title'] ) : ?><br><span class="description"><?php echo esc_html( $s['job_title'] ); ?></span><?php endif; ?>
				</td>
				<td><?php echo esc_html( $s['company'] ); ?></td>
				<td>
					<button type="button" class="ss-status-toggle ss-status--<?php echo esc_attr( $s['status'] ); ?>" data-id="<?php echo esc_attr( $s['id'] ); ?>">
						<?php echo 'published' === $s['status'] ? esc_html__( 'Published', 'sender-symposium' ) : esc_html__( 'Unconfirmed', 'sender-symposium' ); ?>
					</button>
				</td>
				<td>
					<button type="button" class="ss-featured-toggle <?php echo $s['featured'] ? 'ss-featured--active' : ''; ?>" data-id="<?php echo esc_attr( $s['id'] ); ?>" aria-pressed="<?php echo $s['featured'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Toggle featured', 'sender-symposium' ); ?>">&#9733;</button>
				</td>
				<td>
					<a href="<?php echo esc_url( $url . '&action=edit&id=' . $s['id'] ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'sender-symposium' ); ?></a>
					<button type="button" class="button button-small button-link-delete ss-delete-speaker" data-id="<?php echo esc_attr( $s['id'] ); ?>"><?php esc_html_e( 'Delete', 'sender-symposium' ); ?></button>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/* ─── Speaker add/edit form ─── */

function ss_speakers_render_form( $speaker = null ) {
	$is_edit = null !== $speaker;
	$s = $is_edit ? $speaker : array(
		'id' => '', 'name' => '', 'job_title' => '', 'company' => '',
		'linkedin_url' => '', 'website_url' => '', 'image_attachment_id' => 0,
		'image_url' => '', 'topic' => '',
		'description' => '', 'featured' => false, 'status' => 'unconfirmed',
	);
	?>
	<div class="ss-speaker-form-wrap">
		<h2><?php echo $is_edit ? esc_html__( 'Edit Speaker', 'sender-symposium' ) : esc_html__( 'Add Speaker', 'sender-symposium' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'ss_save_speaker' ); ?>
			<input type="hidden" name="ss_speakers_action" value="save_speaker" />
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="speaker[id]" value="<?php echo esc_attr( $s['id'] ); ?>" />
			<?php endif; ?>
			<table class="form-table">
				<tr><th><label for="ss-name"><?php esc_html_e( 'Name *', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-name" name="speaker[name]" value="<?php echo esc_attr( $s['name'] ); ?>" class="regular-text" required /></td></tr>
				<tr><th><label for="ss-jobtitle"><?php esc_html_e( 'Job Title', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-jobtitle" name="speaker[job_title]" value="<?php echo esc_attr( $s['job_title'] ); ?>" class="regular-text" /></td></tr>
				<tr><th><label for="ss-company"><?php esc_html_e( 'Company', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-company" name="speaker[company]" value="<?php echo esc_attr( $s['company'] ); ?>" class="regular-text" /></td></tr>
				<tr><th><label for="ss-linkedin"><?php esc_html_e( 'LinkedIn URL', 'sender-symposium' ); ?></label></th>
					<td><input type="url" id="ss-linkedin" name="speaker[linkedin_url]" value="<?php echo esc_attr( $s['linkedin_url'] ); ?>" class="regular-text" /></td></tr>
				<tr><th><label for="ss-website"><?php esc_html_e( 'Website URL', 'sender-symposium' ); ?></label></th>
					<td><input type="url" id="ss-website" name="speaker[website_url]" value="<?php echo esc_attr( $s['website_url'] ?? '' ); ?>" class="regular-text" /></td></tr>
				<tr><th><?php esc_html_e( 'Image', 'sender-symposium' ); ?></th>
					<td>
						<?php ss_speakers_media_field( $s['image_attachment_id'] ?? 0, $s['image_url'] ?? '' ); ?>
					</td></tr>
				<tr><th><label for="ss-topic"><?php esc_html_e( 'Topic', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-topic" name="speaker[topic]" value="<?php echo esc_attr( $s['topic'] ); ?>" class="regular-text" /></td></tr>
				<tr><th><label for="ss-desc"><?php esc_html_e( 'Description', 'sender-symposium' ); ?></label></th>
					<td><textarea id="ss-desc" name="speaker[description]" class="large-text" rows="4"><?php echo esc_textarea( $s['description'] ); ?></textarea></td></tr>
				<tr><th><?php esc_html_e( 'Status', 'sender-symposium' ); ?></th>
					<td><select name="speaker[status]">
						<option value="unconfirmed" <?php selected( $s['status'], 'unconfirmed' ); ?>><?php esc_html_e( 'Unconfirmed', 'sender-symposium' ); ?></option>
						<option value="published" <?php selected( $s['status'], 'published' ); ?>><?php esc_html_e( 'Published', 'sender-symposium' ); ?></option>
					</select></td></tr>
				<tr><th><?php esc_html_e( 'Featured', 'sender-symposium' ); ?></th>
					<td><label><input type="checkbox" name="speaker[featured]" value="1" <?php checked( $s['featured'] ); ?> /> <?php esc_html_e( 'Mark as featured', 'sender-symposium' ); ?></label></td></tr>
			</table>
			<?php submit_button( $is_edit ? __( 'Update Speaker', 'sender-symposium' ) : __( 'Add Speaker', 'sender-symposium' ) ); ?>
		</form>
	</div>
	<hr />
	<?php
}

/* ─── Speaker image media picker field ─── */

/**
 * Render a combined media library picker + URL fallback field for speaker image.
 */
function ss_speakers_media_field( $attachment_id, $url_value ) {
	$attachment_id = absint( $attachment_id );
	$preview_url   = '';
	if ( $attachment_id ) {
		$preview_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
	}
	if ( ! $preview_url && $url_value ) {
		$preview_url = $url_value;
	}
	?>
	<div class="ss-speaker-media-field">
		<input type="hidden" name="speaker[image_attachment_id]" value="<?php echo esc_attr( $attachment_id ); ?>" class="ss-speaker-media-id" />
		<div class="ss-speaker-media-preview">
			<?php if ( $preview_url ) : ?>
				<img src="<?php echo esc_url( $preview_url ); ?>" alt="" style="max-height:80px;max-width:150px;object-fit:cover;border-radius:4px;" />
			<?php endif; ?>
		</div>
		<button type="button" class="button ss-speaker-media-choose"><?php esc_html_e( 'Choose from Media Library', 'sender-symposium' ); ?></button>
		<button type="button" class="button-link ss-speaker-media-remove" style="<?php echo $attachment_id ? '' : 'display:none;'; ?>color:#a00;margin-left:8px;"><?php esc_html_e( 'Remove', 'sender-symposium' ); ?></button>
		<div style="margin-top:8px;">
			<label class="description"><?php esc_html_e( 'Or paste image URL:', 'sender-symposium' ); ?></label>
			<input type="url" name="speaker[image_url]" value="<?php echo esc_attr( $url_value ); ?>" class="regular-text ss-speaker-url-fallback" />
		</div>
	</div>
	<?php
}

/* ─── Import tab ─── */

function ss_speakers_render_import() {
	?>
	<h2><?php esc_html_e( 'Import Speakers from CSV', 'sender-symposium' ); ?></h2>
	<p><?php esc_html_e( 'Upload a CSV file with speaker data. All imported speakers default to "Unconfirmed" status. Duplicates (same name) are updated by default.', 'sender-symposium' ); ?></p>
	<div class="ss-csv-example">
		<h4><?php esc_html_e( 'Expected CSV format:', 'sender-symposium' ); ?></h4>
		<code>Name,Job Title,Company,LinkedIn,Website,Image URL</code>
		<p class="description"><?php esc_html_e( 'Headers are matched case-insensitively. Unknown columns are ignored. Rows without a Name are skipped.', 'sender-symposium' ); ?></p>
	</div>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'ss_import_csv' ); ?>
		<input type="hidden" name="ss_speakers_action" value="import_csv" />
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

function ss_speakers_render_display() {
	$d = ss_get_speakers_display();
	?>
	<form method="post">
		<?php wp_nonce_field( 'ss_save_display' ); ?>
		<input type="hidden" name="ss_speakers_action" value="save_display" />

		<h3><?php esc_html_e( 'Page Copy', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><label for="d-title"><?php esc_html_e( 'Page Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-title" name="display[page_title]" value="<?php echo esc_attr( $d['page_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-sub"><?php esc_html_e( 'Page Subtitle', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-sub" name="display[page_subtitle]" value="<?php echo esc_attr( $d['page_subtitle'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-intro"><?php esc_html_e( 'Intro Paragraph', 'sender-symposium' ); ?></label></th>
				<td><textarea id="d-intro" name="display[intro_text]" class="large-text" rows="3"><?php echo esc_textarea( $d['intro_text'] ); ?></textarea></td></tr>
		</table>

		<h3><?php esc_html_e( 'Featured Section', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Enable', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="display[enable_featured]" value="1" <?php checked( $d['enable_featured'] ); ?> /> <?php esc_html_e( 'Show featured speakers in a separate section', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><label for="d-ft"><?php esc_html_e( 'Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-ft" name="display[featured_title]" value="<?php echo esc_attr( $d['featured_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-fs"><?php esc_html_e( 'Subtitle', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-fs" name="display[featured_subtitle]" value="<?php echo esc_attr( $d['featured_subtitle'] ); ?>" class="regular-text" /></td></tr>
		</table>

		<h3><?php esc_html_e( 'All Speakers Section', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><label for="d-at"><?php esc_html_e( 'Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-at" name="display[all_title]" value="<?php echo esc_attr( $d['all_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label for="d-as"><?php esc_html_e( 'Subtitle', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-as" name="display[all_subtitle]" value="<?php echo esc_attr( $d['all_subtitle'] ); ?>" class="regular-text" /></td></tr>
		</table>

		<h3><?php esc_html_e( 'Sorting', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Default Sort', 'sender-symposium' ); ?></th>
				<td><select name="display[default_sort]">
					<option value="manual" <?php selected( $d['default_sort'], 'manual' ); ?>><?php esc_html_e( 'Manual order', 'sender-symposium' ); ?></option>
					<option value="alphabetical" <?php selected( $d['default_sort'], 'alphabetical' ); ?>><?php esc_html_e( 'Alphabetical', 'sender-symposium' ); ?></option>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Alphabetical Toggle', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="display[enable_alpha_toggle]" value="1" <?php checked( $d['enable_alpha_toggle'] ); ?> /> <?php esc_html_e( 'Show sort toggle on frontend', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><label for="d-al"><?php esc_html_e( 'Toggle Label', 'sender-symposium' ); ?></label></th>
				<td><input type="text" id="d-al" name="display[alpha_toggle_label]" value="<?php echo esc_attr( $d['alpha_toggle_label'] ); ?>" class="regular-text" /></td></tr>
		</table>

		<h3><?php esc_html_e( 'Layout & Style', 'sender-symposium' ); ?></h3>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Grid Columns', 'sender-symposium' ); ?></th>
				<td><select name="display[grid_columns]">
					<option value="2" <?php selected( $d['grid_columns'], '2' ); ?>>2</option>
					<option value="3" <?php selected( $d['grid_columns'], '3' ); ?>>3</option>
					<option value="4" <?php selected( $d['grid_columns'], '4' ); ?>>4</option>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Card Style', 'sender-symposium' ); ?></th>
				<td><select name="display[card_style]">
					<option value="elevated" <?php selected( $d['card_style'], 'elevated' ); ?>><?php esc_html_e( 'Elevated (subtle shadow)', 'sender-symposium' ); ?></option>
					<option value="minimal" <?php selected( $d['card_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal (no shadow)', 'sender-symposium' ); ?></option>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Show Fields', 'sender-symposium' ); ?></th>
				<td>
					<label><input type="checkbox" name="display[show_company]" value="1" <?php checked( $d['show_company'] ); ?> /> <?php esc_html_e( 'Company', 'sender-symposium' ); ?></label><br>
					<label><input type="checkbox" name="display[show_linkedin]" value="1" <?php checked( $d['show_linkedin'] ); ?> /> <?php esc_html_e( 'LinkedIn', 'sender-symposium' ); ?></label><br>
					<label><input type="checkbox" name="display[show_website]" value="1" <?php checked( $d['show_website'] ); ?> /> <?php esc_html_e( 'Website', 'sender-symposium' ); ?></label><br>
					<label><input type="checkbox" name="display[show_topic]" value="1" <?php checked( $d['show_topic'] ); ?> /> <?php esc_html_e( 'Topic', 'sender-symposium' ); ?></label><br>
					<label><input type="checkbox" name="display[show_description]" value="1" <?php checked( $d['show_description'] ); ?> /> <?php esc_html_e( 'Description', 'sender-symposium' ); ?></label>
				</td></tr>
		</table>
		<?php submit_button( __( 'Save Display Settings', 'sender-symposium' ) ); ?>
	</form>
	<?php
}
