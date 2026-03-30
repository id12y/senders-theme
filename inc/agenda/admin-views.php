<?php
/**
 * Agenda Module — Admin View Templates
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
   Main Page Wrapper
   ------------------------------------------------------------------------- */

function ss_agenda_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'sessions';
	$tabs = array(
		'sessions' => __( 'Sessions', 'sender-symposium' ),
		'import'   => __( 'Import / Export', 'sender-symposium' ),
		'settings' => __( 'Settings', 'sender-symposium' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Agenda', 'sender-symposium' ); ?></h1>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'ss-agenda', 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
				   class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php ss_agenda_admin_notices(); ?>

		<?php
		switch ( $tab ) {
			case 'import':
				ss_agenda_render_import();
				break;
			case 'settings':
				ss_agenda_render_settings();
				break;
			default:
				ss_agenda_render_sessions();
				break;
		}
		?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
   Admin Notices
   ------------------------------------------------------------------------- */

function ss_agenda_admin_notices() {
	$message = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : '';
	if ( empty( $message ) ) {
		return;
	}

	$notices = array(
		'session_added'     => array( 'success', __( 'Session added.', 'sender-symposium' ) ),
		'session_updated'   => array( 'success', __( 'Session updated.', 'sender-symposium' ) ),
		'session_deleted'   => array( 'success', __( 'Session deleted.', 'sender-symposium' ) ),
		'session_duplicated' => array( 'success', __( 'Session duplicated.', 'sender-symposium' ) ),
		'settings_saved'    => array( 'success', __( 'Settings saved.', 'sender-symposium' ) ),
		'import_no_file'    => array( 'error', __( 'No file selected.', 'sender-symposium' ) ),
		'import_wrong_type' => array( 'error', __( 'Invalid file type. Please upload a .json or .csv file.', 'sender-symposium' ) ),
		'backup_restored'   => array( 'success', __( 'Backup restored successfully.', 'sender-symposium' ) ),
		'no_backup'         => array( 'error', __( 'No backup found to restore.', 'sender-symposium' ) ),
	);

	if ( 'import_done' === $message ) {
		$count    = isset( $_GET['imported'] ) ? absint( $_GET['imported'] ) : 0;
		$warnings = get_transient( 'ss_agenda_import_warnings' );
		delete_transient( 'ss_agenda_import_warnings' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				/* translators: %d: number of sessions imported */
				printf( esc_html__( 'Import complete: %d sessions imported.', 'sender-symposium' ), $count );
				?>
			</p>
		</div>
		<?php if ( is_array( $warnings ) && ! empty( $warnings ) ) : ?>
			<div class="notice notice-warning is-dismissible">
				<p><strong><?php esc_html_e( 'Import warnings:', 'sender-symposium' ); ?></strong></p>
				<ul style="list-style:disc;margin-left:20px;">
					<?php foreach ( $warnings as $w ) : ?>
						<li><?php echo esc_html( $w ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php
		return;
	}

	if ( 'import_errors' === $message ) {
		$errors = get_transient( 'ss_agenda_import_errors' );
		delete_transient( 'ss_agenda_import_errors' );
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'Import failed with errors:', 'sender-symposium' ); ?></strong></p>
			<?php if ( is_array( $errors ) ) : ?>
				<ul style="list-style:disc;margin-left:20px;">
					<?php foreach ( $errors as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return;
	}

	if ( 'validation_error' === $message ) {
		$errors = get_transient( 'ss_agenda_errors' );
		delete_transient( 'ss_agenda_errors' );
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'Validation errors:', 'sender-symposium' ); ?></strong></p>
			<?php if ( is_array( $errors ) ) : ?>
				<ul style="list-style:disc;margin-left:20px;">
					<?php foreach ( $errors as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return;
	}

	if ( isset( $notices[ $message ] ) ) {
		list( $type, $text ) = $notices[ $message ];
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $text ) );
	}
}

/* -------------------------------------------------------------------------
   Sessions Tab
   ------------------------------------------------------------------------- */

function ss_agenda_render_sessions() {
	$agenda   = ss_get_agenda();
	$sessions = $agenda['sessions'];
	$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
	$edit_id  = isset( $_GET['id'] ) ? sanitize_key( $_GET['id'] ) : '';

	/* Show form if adding or editing */
	if ( 'add' === $action ) {
		ss_agenda_render_session_form();
	} elseif ( 'edit' === $action && ! empty( $edit_id ) ) {
		$session = ss_get_session( $edit_id );
		if ( $session ) {
			ss_agenda_render_session_form( $session );
		}
	}

	/* Group sessions by date */
	$by_date = array();
	foreach ( $sessions as $s ) {
		$date = $s['date'] ?: __( 'No date', 'sender-symposium' );
		$by_date[ $date ][] = $s;
	}
	ksort( $by_date );

	/* Sort sessions within each day */
	foreach ( $by_date as &$day_sessions ) {
		usort( $day_sessions, function ( $a, $b ) {
			$cmp = strcmp( $a['start_time'], $b['start_time'] );
			if ( 0 !== $cmp ) {
				return $cmp;
			}
			return $a['sort_order'] - $b['sort_order'];
		} );
	}
	unset( $day_sessions );

	$add_url = add_query_arg( array( 'page' => 'ss-agenda', 'tab' => 'sessions', 'action' => 'add' ), admin_url( 'admin.php' ) );
	?>
	<div style="margin-top:16px;">
		<a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary">
			<?php esc_html_e( '+ Add Session', 'sender-symposium' ); ?>
		</a>
		<button type="button" id="ss-bulk-delete-btn" class="button" style="display:none;margin-left:8px;color:#a00;">
			<?php esc_html_e( 'Delete Selected', 'sender-symposium' ); ?>
		</button>
		<span style="margin-left:12px;color:#666;">
			<?php
			/* translators: %d: total session count */
			printf( esc_html__( '%d sessions total', 'sender-symposium' ), count( $sessions ) );
			?>
		</span>
	</div>

	<?php if ( empty( $sessions ) ) : ?>
		<p style="margin-top:20px;"><?php esc_html_e( 'No sessions yet. Add your first session above.', 'sender-symposium' ); ?></p>
	<?php else : ?>
		<?php foreach ( $by_date as $date => $day_sessions ) : ?>
			<h2 style="margin-top:24px;margin-bottom:8px;">
				<?php
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					echo esc_html( wp_date( 'l, j F Y', strtotime( $date ) ) );
				} else {
					echo esc_html( $date );
				}
				?>
				<small style="font-weight:normal;color:#888;margin-left:8px;">
					(<?php printf( esc_html__( '%d sessions', 'sender-symposium' ), count( $day_sessions ) ); ?>)
				</small>
			</h2>
			<table class="wp-list-table widefat striped ss-agenda-table">
				<thead>
					<tr>
						<th style="width:30px;"><input type="checkbox" class="ss-select-all" title="<?php esc_attr_e( 'Select all', 'sender-symposium' ); ?>"></th>
						<th style="width:100px;"><?php esc_html_e( 'Time', 'sender-symposium' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Room', 'sender-symposium' ); ?></th>
						<th><?php esc_html_e( 'Title', 'sender-symposium' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Type', 'sender-symposium' ); ?></th>
						<th style="width:200px;"><?php esc_html_e( 'Participants', 'sender-symposium' ); ?></th>
						<th style="width:80px;"><?php esc_html_e( 'Status', 'sender-symposium' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Actions', 'sender-symposium' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $day_sessions as $s ) : ?>
						<?php
						$edit_url = add_query_arg( array(
							'page'   => 'ss-agenda',
							'tab'    => 'sessions',
							'action' => 'edit',
							'id'     => $s['id'],
						), admin_url( 'admin.php' ) );
						$is_utility = in_array( $s['type'], array( 'break', 'lunch', 'networking' ), true );
						?>
						<tr class="<?php echo $is_utility ? 'ss-agenda-row--utility' : ''; ?>" data-id="<?php echo esc_attr( $s['id'] ); ?>">
							<td><input type="checkbox" class="ss-session-check" value="<?php echo esc_attr( $s['id'] ); ?>"></td>
							<td>
								<strong><?php echo esc_html( $s['start_time'] ); ?></strong>
								<span style="color:#999;">–</span>
								<?php echo esc_html( $s['end_time'] ); ?>
							</td>
							<td><?php echo esc_html( $s['room'] ?: '—' ); ?></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $s['title'] ); ?></strong></a>
								<?php if ( ! empty( $s['subtitle'] ) ) : ?>
									<br><span style="color:#666;"><?php echo esc_html( $s['subtitle'] ); ?></span>
								<?php endif; ?>
							</td>
							<td><span class="ss-agenda-type ss-agenda-type--<?php echo esc_attr( $s['type'] ); ?>"><?php echo esc_html( $s['type'] ); ?></span></td>
							<td>
								<?php
								if ( ! empty( $s['participants'] ) && function_exists( 'ss_get_speaker' ) ) {
									$parts = array();
									foreach ( $s['participants'] as $p ) {
										$spk = ss_get_speaker( $p['speaker_id'] );
										if ( $spk ) {
											$name = esc_html( $spk['name'] );
											if ( 'speaker' !== $p['role'] ) {
												$name .= ' <em style="color:#888;">(' . esc_html( $p['role'] ) . ')</em>';
											}
											$parts[] = $name;
										}
									}
									echo implode( '<br>', $parts );
								} else {
									echo '<span style="color:#999;">—</span>';
								}
								?>
							</td>
							<td>
								<?php if ( 'draft' === $s['status'] ) : ?>
									<span style="color:#b26f08;font-weight:600;"><?php esc_html_e( 'Draft', 'sender-symposium' ); ?></span>
								<?php else : ?>
									<span style="color:#2e7d32;"><?php esc_html_e( 'Published', 'sender-symposium' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'sender-symposium' ); ?></a>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'ss_duplicate_session' ); ?>
									<input type="hidden" name="ss_agenda_action" value="duplicate_session">
									<input type="hidden" name="session_id" value="<?php echo esc_attr( $s['id'] ); ?>">
									<button type="submit" class="button button-small"><?php esc_html_e( 'Dup', 'sender-symposium' ); ?></button>
								</form>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this session?', 'sender-symposium' ) ); ?>');">
									<?php wp_nonce_field( 'ss_delete_session' ); ?>
									<input type="hidden" name="ss_agenda_action" value="delete_session">
									<input type="hidden" name="session_id" value="<?php echo esc_attr( $s['id'] ); ?>">
									<button type="submit" class="button button-small" style="color:#a00;"><?php esc_html_e( 'Del', 'sender-symposium' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php
}

/* -------------------------------------------------------------------------
   Session Add/Edit Form
   ------------------------------------------------------------------------- */

function ss_agenda_render_session_form( $session = null ) {
	$is_edit = ! empty( $session );
	if ( ! $is_edit ) {
		$session = array(
			'id'           => '',
			'date'         => '',
			'start_time'   => '',
			'end_time'     => '',
			'room'         => '',
			'track'        => '',
			'type'         => 'talk',
			'title'        => '',
			'subtitle'     => '',
			'description'  => '',
			'participants' => array(),
			'status'       => 'published',
			'sort_order'   => 0,
			'cta_label'    => '',
			'cta_url'      => '',
			'notes'        => '',
		);
	}

	/* Get all speakers for the dropdown */
	$speakers = function_exists( 'ss_get_speakers' ) ? ss_get_speakers() : array();

	/* Get existing rooms and tracks for datalist suggestions */
	$existing_rooms  = ss_get_agenda_rooms();
	$existing_tracks = ss_get_agenda_tracks();

	$types    = ss_agenda_session_types();
	$roles    = ss_agenda_participant_roles();
	$statuses = ss_agenda_session_statuses();

	$back_url = add_query_arg( array( 'page' => 'ss-agenda', 'tab' => 'sessions' ), admin_url( 'admin.php' ) );
	?>
	<div class="ss-agenda-form-wrap" style="margin-top:16px;background:#fff;border:1px solid #c3c4c7;padding:20px;max-width:900px;">
		<h2 style="margin-top:0;">
			<?php $is_edit ? esc_html_e( 'Edit Session', 'sender-symposium' ) : esc_html_e( 'Add Session', 'sender-symposium' ); ?>
			<a href="<?php echo esc_url( $back_url ); ?>" style="font-size:13px;font-weight:normal;margin-left:12px;">&larr; <?php esc_html_e( 'Back to list', 'sender-symposium' ); ?></a>
		</h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ss-agenda' ) ); ?>">
			<?php wp_nonce_field( 'ss_save_session' ); ?>
			<input type="hidden" name="ss_agenda_action" value="save_session">
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="session[id]" value="<?php echo esc_attr( $session['id'] ); ?>">
			<?php endif; ?>

			<table class="form-table">
				<tr>
					<th><label for="ss-title"><?php esc_html_e( 'Title', 'sender-symposium' ); ?> <span style="color:red;">*</span></label></th>
					<td><input type="text" id="ss-title" name="session[title]" value="<?php echo esc_attr( $session['title'] ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="ss-subtitle"><?php esc_html_e( 'Subtitle', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-subtitle" name="session[subtitle]" value="<?php echo esc_attr( $session['subtitle'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="ss-type"><?php esc_html_e( 'Type', 'sender-symposium' ); ?></label></th>
					<td>
						<select id="ss-type" name="session[type]">
							<?php foreach ( $types as $t ) : ?>
								<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $session['type'], $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="ss-date"><?php esc_html_e( 'Date', 'sender-symposium' ); ?> <span style="color:red;">*</span></label></th>
					<td><input type="date" id="ss-date" name="session[date]" value="<?php echo esc_attr( $session['date'] ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="ss-start-time"><?php esc_html_e( 'Start Time', 'sender-symposium' ); ?> <span style="color:red;">*</span></label></th>
					<td><input type="time" id="ss-start-time" name="session[start_time]" value="<?php echo esc_attr( $session['start_time'] ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="ss-end-time"><?php esc_html_e( 'End Time', 'sender-symposium' ); ?> <span style="color:red;">*</span></label></th>
					<td><input type="time" id="ss-end-time" name="session[end_time]" value="<?php echo esc_attr( $session['end_time'] ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="ss-room"><?php esc_html_e( 'Room / Location', 'sender-symposium' ); ?></label></th>
					<td>
						<input type="text" id="ss-room" name="session[room]" value="<?php echo esc_attr( $session['room'] ); ?>" class="regular-text" list="ss-rooms-list">
						<datalist id="ss-rooms-list">
							<?php foreach ( $existing_rooms as $r ) : ?>
								<option value="<?php echo esc_attr( $r ); ?>">
							<?php endforeach; ?>
						</datalist>
					</td>
				</tr>
				<tr>
					<th><label for="ss-track"><?php esc_html_e( 'Track / Stream', 'sender-symposium' ); ?></label></th>
					<td>
						<input type="text" id="ss-track" name="session[track]" value="<?php echo esc_attr( $session['track'] ); ?>" class="regular-text" list="ss-tracks-list">
						<datalist id="ss-tracks-list">
							<?php foreach ( $existing_tracks as $t ) : ?>
								<option value="<?php echo esc_attr( $t ); ?>">
							<?php endforeach; ?>
						</datalist>
					</td>
				</tr>
				<tr>
					<th><label for="ss-description"><?php esc_html_e( 'Description', 'sender-symposium' ); ?></label></th>
					<td><textarea id="ss-description" name="session[description]" rows="4" class="large-text"><?php echo esc_textarea( $session['description'] ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="ss-status"><?php esc_html_e( 'Status', 'sender-symposium' ); ?></label></th>
					<td>
						<select id="ss-status" name="session[status]">
							<?php foreach ( $statuses as $st ) : ?>
								<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $session['status'], $st ); ?>><?php echo esc_html( ucfirst( $st ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="ss-cta-label"><?php esc_html_e( 'CTA Label', 'sender-symposium' ); ?></label></th>
					<td><input type="text" id="ss-cta-label" name="session[cta_label]" value="<?php echo esc_attr( $session['cta_label'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="ss-cta-url"><?php esc_html_e( 'CTA URL', 'sender-symposium' ); ?></label></th>
					<td><input type="url" id="ss-cta-url" name="session[cta_url]" value="<?php echo esc_attr( $session['cta_url'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="ss-sort-order"><?php esc_html_e( 'Sort Order', 'sender-symposium' ); ?></label></th>
					<td><input type="number" id="ss-sort-order" name="session[sort_order]" value="<?php echo esc_attr( $session['sort_order'] ); ?>" min="0" step="1" style="width:80px;"></td>
				</tr>
				<tr>
					<th><label for="ss-notes"><?php esc_html_e( 'Internal Notes', 'sender-symposium' ); ?></label></th>
					<td><textarea id="ss-notes" name="session[notes]" rows="2" class="large-text"><?php echo esc_textarea( $session['notes'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Admin-only notes. Not shown on the frontend.', 'sender-symposium' ); ?></p></td>
				</tr>
			</table>

			<!-- Participants -->
			<h3><?php esc_html_e( 'Participants', 'sender-symposium' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Link speakers from the Speakers module. Add them there first if missing.', 'sender-symposium' ); ?></p>

			<div id="ss-participants-list">
				<?php
				if ( ! empty( $session['participants'] ) ) {
					foreach ( $session['participants'] as $idx => $p ) {
						ss_agenda_render_participant_row( $idx, $p, $speakers, $roles );
					}
				}
				?>
			</div>

			<button type="button" id="ss-add-participant" class="button" style="margin-top:8px;">
				<?php esc_html_e( '+ Add Participant', 'sender-symposium' ); ?>
			</button>

			<!-- Hidden template for JS to clone -->
			<template id="ss-participant-template">
				<?php ss_agenda_render_participant_row( '__INDEX__', array( 'speaker_id' => '', 'role' => 'speaker', 'sort_order' => 0 ), $speakers, $roles ); ?>
			</template>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php $is_edit ? esc_html_e( 'Update Session', 'sender-symposium' ) : esc_html_e( 'Add Session', 'sender-symposium' ); ?>
				</button>
				<a href="<?php echo esc_url( $back_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'sender-symposium' ); ?></a>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Render a single participant row in the session form.
 */
function ss_agenda_render_participant_row( $index, $participant, $speakers, $roles ) {
	?>
	<div class="ss-participant-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;padding:6px 8px;background:#f9f9f9;border:1px solid #ddd;">
		<select name="session[participants][<?php echo esc_attr( $index ); ?>][speaker_id]" style="min-width:200px;">
			<option value=""><?php esc_html_e( '— Select Speaker —', 'sender-symposium' ); ?></option>
			<?php foreach ( $speakers as $spk ) : ?>
				<option value="<?php echo esc_attr( $spk['id'] ); ?>" <?php selected( $participant['speaker_id'], $spk['id'] ); ?>>
					<?php echo esc_html( $spk['name'] ); ?>
					<?php if ( ! empty( $spk['company'] ) ) : ?>
						(<?php echo esc_html( $spk['company'] ); ?>)
					<?php endif; ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select name="session[participants][<?php echo esc_attr( $index ); ?>][role]" style="min-width:120px;">
			<?php foreach ( $roles as $r ) : ?>
				<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $participant['role'], $r ); ?>><?php echo esc_html( ucfirst( $r ) ); ?></option>
			<?php endforeach; ?>
		</select>

		<input type="hidden" name="session[participants][<?php echo esc_attr( $index ); ?>][sort_order]" value="<?php echo esc_attr( $index ); ?>">

		<button type="button" class="button button-link ss-remove-participant" style="color:#a00;" title="<?php esc_attr_e( 'Remove', 'sender-symposium' ); ?>">&times;</button>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
   Import / Export Tab
   ------------------------------------------------------------------------- */

function ss_agenda_render_import() {
	$backup = ss_agenda_has_backup();
	$export_base = wp_nonce_url( admin_url( 'admin.php?page=ss-agenda' ), 'ss_agenda_export' );
	?>
	<div style="margin-top:16px;max-width:700px;">

		<h2><?php esc_html_e( 'Export', 'sender-symposium' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( $export_base . '&ss_agenda_export=json' ); ?>" class="button">
				<?php esc_html_e( 'Export JSON', 'sender-symposium' ); ?>
			</a>
			<a href="<?php echo esc_url( $export_base . '&ss_agenda_export=csv' ); ?>" class="button">
				<?php esc_html_e( 'Export CSV', 'sender-symposium' ); ?>
			</a>
			<a href="<?php echo esc_url( $export_base . '&ss_agenda_export=template' ); ?>" class="button">
				<?php esc_html_e( 'Download Blank CSV Template', 'sender-symposium' ); ?>
			</a>
		</p>

		<hr style="margin:24px 0;">

		<h2><?php esc_html_e( 'Import', 'sender-symposium' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Upload a JSON or CSV file to replace the current agenda. A backup of the current agenda is saved automatically before import.', 'sender-symposium' ); ?>
		</p>

		<form method="post" enctype="multipart/form-data" style="margin-top:12px;">
			<?php wp_nonce_field( 'ss_agenda_import' ); ?>
			<p>
				<input type="file" name="import_file" accept=".json,.csv" required>
			</p>
			<p>
				<label>
					<input type="radio" name="ss_agenda_action" value="import_json" checked>
					<?php esc_html_e( 'Import as JSON', 'sender-symposium' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" name="ss_agenda_action" value="import_csv">
					<?php esc_html_e( 'Import as CSV', 'sender-symposium' ); ?>
				</label>
			</p>
			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'sender-symposium' ); ?></button>
			</p>
		</form>

		<?php if ( $backup ) : ?>
			<hr style="margin:24px 0;">
			<h2><?php esc_html_e( 'Restore Backup', 'sender-symposium' ); ?></h2>
			<p>
				<?php
				/* translators: %1$d: session count, %2$s: backup timestamp */
				printf(
					esc_html__( 'Last backup: %1$d sessions, saved %2$s', 'sender-symposium' ),
					$backup['session_count'],
					esc_html( $backup['time'] )
				);
				?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'ss_agenda_restore' ); ?>
				<input type="hidden" name="ss_agenda_action" value="restore_backup">
				<button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Restore the backup? This will replace the current agenda.', 'sender-symposium' ) ); ?>');">
					<?php esc_html_e( 'Restore Backup', 'sender-symposium' ); ?>
				</button>
			</form>
		<?php endif; ?>

	</div>
	<?php
}

/* -------------------------------------------------------------------------
   Settings Tab
   ------------------------------------------------------------------------- */

function ss_agenda_render_settings() {
	$agenda = ss_get_agenda();
	?>
	<div style="margin-top:16px;max-width:600px;">
		<form method="post">
			<?php wp_nonce_field( 'ss_agenda_settings' ); ?>
			<input type="hidden" name="ss_agenda_action" value="save_settings">

			<table class="form-table">
				<tr>
					<th><label for="ss-timezone"><?php esc_html_e( 'Timezone', 'sender-symposium' ); ?></label></th>
					<td>
						<input type="text" id="ss-timezone" name="timezone" value="<?php echo esc_attr( $agenda['timezone'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'e.g. Europe/Madrid, America/New_York', 'sender-symposium' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'sender-symposium' ); ?></button>
			</p>
		</form>

		<hr style="margin:24px 0;">
		<h3><?php esc_html_e( 'Shortcode', 'sender-symposium' ); ?></h3>
		<p><?php esc_html_e( 'Use this shortcode on any page to display the agenda:', 'sender-symposium' ); ?></p>
		<code>[ss_agenda]</code>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Optional attributes: day="2025-06-15" room="Main Hall" track="Engineering" speaker="spk_abc123"', 'sender-symposium' ); ?>
		</p>
	</div>
	<?php
}
