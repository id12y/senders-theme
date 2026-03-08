<?php
/**
 * Agenda Module — Frontend Rendering & Shortcode
 *
 * Shortcode: [ss_agenda]
 * Attributes: day, room, track, speaker
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the full agenda.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function ss_agenda_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'day'     => '',
		'room'    => '',
		'track'   => '',
		'speaker' => '',
	), $atts, 'ss_agenda' );

	$agenda = ss_get_agenda();
	$sessions = $agenda['sessions'];

	/* Filter to published only */
	$sessions = array_filter( $sessions, function ( $s ) {
		return 'draft' !== $s['status'];
	} );

	/* Apply attribute filters */
	if ( ! empty( $atts['day'] ) ) {
		$day = sanitize_text_field( $atts['day'] );
		$sessions = array_filter( $sessions, function ( $s ) use ( $day ) {
			return $s['date'] === $day;
		} );
	}
	if ( ! empty( $atts['room'] ) ) {
		$room = sanitize_text_field( $atts['room'] );
		$sessions = array_filter( $sessions, function ( $s ) use ( $room ) {
			return $s['room'] === $room;
		} );
	}
	if ( ! empty( $atts['track'] ) ) {
		$track = sanitize_text_field( $atts['track'] );
		$sessions = array_filter( $sessions, function ( $s ) use ( $track ) {
			return $s['track'] === $track;
		} );
	}
	if ( ! empty( $atts['speaker'] ) ) {
		$speaker_id = sanitize_key( $atts['speaker'] );
		$sessions = array_filter( $sessions, function ( $s ) use ( $speaker_id ) {
			foreach ( $s['participants'] as $p ) {
				if ( $p['speaker_id'] === $speaker_id ) {
					return true;
				}
			}
			return false;
		} );
	}

	if ( empty( $sessions ) ) {
		return '<p class="ss-agenda__empty">' . esc_html__( 'No sessions scheduled yet.', 'sender-symposium' ) . '</p>';
	}

	/* Group by day */
	$by_day = array();
	foreach ( $sessions as $s ) {
		$date = $s['date'] ?: 'unscheduled';
		$by_day[ $date ][] = $s;
	}
	ksort( $by_day );

	/* Sort within each day */
	foreach ( $by_day as &$day_sessions ) {
		usort( $day_sessions, function ( $a, $b ) {
			$cmp = strcmp( $a['start_time'], $b['start_time'] );
			return 0 !== $cmp ? $cmp : $a['sort_order'] - $b['sort_order'];
		} );
	}
	unset( $day_sessions );

	/* Collect filter data */
	$all_days   = array_keys( $by_day );
	$all_rooms  = array();
	$all_tracks = array();
	foreach ( $sessions as $s ) {
		if ( ! empty( $s['room'] ) ) {
			$all_rooms[ $s['room'] ] = true;
		}
		if ( ! empty( $s['track'] ) ) {
			$all_tracks[ $s['track'] ] = true;
		}
	}
	$all_rooms  = array_keys( $all_rooms );
	$all_tracks = array_keys( $all_tracks );
	sort( $all_rooms );
	sort( $all_tracks );

	$show_filters = count( $all_days ) > 1 || count( $all_rooms ) > 1 || count( $all_tracks ) > 0;

	ob_start();
	?>
	<div class="ss-agenda" data-agenda-root>

		<?php if ( $show_filters ) : ?>
		<div class="ss-agenda__filters" role="group" aria-label="<?php esc_attr_e( 'Filter sessions', 'sender-symposium' ); ?>">
			<?php if ( count( $all_days ) > 1 ) : ?>
			<div class="ss-agenda__filter-group">
				<label class="ss-agenda__filter-label" for="ss-agenda-filter-day"><?php esc_html_e( 'Day', 'sender-symposium' ); ?></label>
				<select class="ss-agenda__filter-select" id="ss-agenda-filter-day" data-filter="day">
					<option value=""><?php esc_html_e( 'All Days', 'sender-symposium' ); ?></option>
					<?php foreach ( $all_days as $d ) : ?>
						<option value="<?php echo esc_attr( $d ); ?>">
							<?php echo esc_html( ss_agenda_format_day_label( $d ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<?php if ( count( $all_rooms ) > 1 ) : ?>
			<div class="ss-agenda__filter-group">
				<label class="ss-agenda__filter-label" for="ss-agenda-filter-room"><?php esc_html_e( 'Room', 'sender-symposium' ); ?></label>
				<select class="ss-agenda__filter-select" id="ss-agenda-filter-room" data-filter="room">
					<option value=""><?php esc_html_e( 'All Rooms', 'sender-symposium' ); ?></option>
					<?php foreach ( $all_rooms as $r ) : ?>
						<option value="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( $r ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $all_tracks ) ) : ?>
			<div class="ss-agenda__filter-group">
				<label class="ss-agenda__filter-label" for="ss-agenda-filter-track"><?php esc_html_e( 'Track', 'sender-symposium' ); ?></label>
				<select class="ss-agenda__filter-select" id="ss-agenda-filter-track" data-filter="track">
					<option value=""><?php esc_html_e( 'All Tracks', 'sender-symposium' ); ?></option>
					<?php foreach ( $all_tracks as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php foreach ( $by_day as $date => $day_sessions ) : ?>
		<section class="ss-agenda__day" data-day="<?php echo esc_attr( $date ); ?>">
			<h2 class="ss-agenda__day-title">
				<?php echo esc_html( ss_agenda_format_day_heading( $date ) ); ?>
			</h2>

			<div class="ss-agenda__sessions">
				<?php
				/* Group parallel sessions by time slot */
				$time_slots = array();
				foreach ( $day_sessions as $s ) {
					$slot_key = $s['start_time'] . '-' . $s['end_time'];
					$time_slots[ $slot_key ][] = $s;
				}
				?>

				<?php foreach ( $time_slots as $slot_key => $slot_sessions ) : ?>
					<?php
					$is_parallel = count( $slot_sessions ) > 1;
					$first       = $slot_sessions[0];
					?>

					<div class="ss-agenda__time-slot <?php echo $is_parallel ? 'ss-agenda__time-slot--parallel' : ''; ?>">
						<div class="ss-agenda__time-col">
							<time class="ss-agenda__time" datetime="<?php echo esc_attr( $date . 'T' . $first['start_time'] ); ?>">
								<?php echo esc_html( ss_agenda_format_time( $first['start_time'] ) ); ?>
							</time>
							<span class="ss-agenda__time-end">
								<?php echo esc_html( ss_agenda_format_time( $first['end_time'] ) ); ?>
							</span>
						</div>

						<div class="ss-agenda__slot-sessions">
							<?php foreach ( $slot_sessions as $s ) : ?>
								<?php ss_agenda_render_session_card( $s ); ?>
							<?php endforeach; ?>
						</div>
					</div>

				<?php endforeach; ?>
			</div>
		</section>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ss_agenda', 'ss_agenda_shortcode' );

/* -------------------------------------------------------------------------
   Session Card
   ------------------------------------------------------------------------- */

/**
 * Render a single session card.
 *
 * @param array $session Session data.
 */
function ss_agenda_render_session_card( $session ) {
	$is_utility  = in_array( $session['type'], array( 'break', 'lunch', 'networking' ), true );
	$has_details = ! empty( $session['description'] ) || ! empty( $session['participants'] ) || ! empty( $session['cta_url'] );
	$card_id     = 'session-' . esc_attr( $session['id'] );
	$panel_id    = 'panel-' . esc_attr( $session['id'] );

	$classes = array( 'ss-agenda__card' );
	$classes[] = 'ss-agenda__card--' . esc_attr( $session['type'] );
	if ( $is_utility ) {
		$classes[] = 'ss-agenda__card--utility';
	}
	?>
	<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		data-room="<?php echo esc_attr( $session['room'] ); ?>"
		data-track="<?php echo esc_attr( $session['track'] ); ?>"
		id="<?php echo esc_attr( $card_id ); ?>">

		<?php if ( $has_details && ! $is_utility ) : ?>
		<button class="ss-agenda__card-toggle"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $panel_id ); ?>"
			type="button">
		<?php endif; ?>

			<div class="ss-agenda__card-header">
				<div class="ss-agenda__card-meta">
					<?php if ( ! empty( $session['room'] ) ) : ?>
						<span class="ss-agenda__room"><?php echo esc_html( $session['room'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $session['track'] ) ) : ?>
						<span class="ss-agenda__track"><?php echo esc_html( $session['track'] ); ?></span>
					<?php endif; ?>
					<span class="ss-agenda__type ss-agenda__type--<?php echo esc_attr( $session['type'] ); ?>"><?php echo esc_html( ucfirst( $session['type'] ) ); ?></span>
				</div>

				<h3 class="ss-agenda__card-title"><?php echo esc_html( $session['title'] ); ?></h3>

				<?php if ( ! empty( $session['subtitle'] ) ) : ?>
					<p class="ss-agenda__card-subtitle"><?php echo esc_html( $session['subtitle'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $session['participants'] ) && ! $is_utility ) : ?>
					<div class="ss-agenda__participants-summary">
						<?php
						$participant_names = array();
						foreach ( $session['participants'] as $p ) {
							if ( function_exists( 'ss_get_speaker' ) ) {
								$spk = ss_get_speaker( $p['speaker_id'] );
								if ( $spk ) {
									$label = esc_html( $spk['name'] );
									if ( 'speaker' !== $p['role'] ) {
										$label .= ' <span class="ss-agenda__role">(' . esc_html( $p['role'] ) . ')</span>';
									}
									$participant_names[] = $label;
								}
							}
						}
						echo implode( '<span class="ss-agenda__sep" aria-hidden="true"> · </span>', $participant_names );
						?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $has_details && ! $is_utility ) : ?>
				<span class="ss-agenda__expand-icon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</span>
			<?php endif; ?>

		<?php if ( $has_details && ! $is_utility ) : ?>
		</button>
		<?php endif; ?>

		<?php if ( $has_details && ! $is_utility ) : ?>
		<div class="ss-agenda__card-details" id="<?php echo esc_attr( $panel_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $card_id ); ?>" hidden>

			<?php if ( ! empty( $session['description'] ) ) : ?>
				<div class="ss-agenda__description">
					<?php echo wp_kses_post( wpautop( $session['description'] ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $session['participants'] ) ) : ?>
				<div class="ss-agenda__participants-detail">
					<?php foreach ( $session['participants'] as $p ) :
						$spk = function_exists( 'ss_get_speaker' ) ? ss_get_speaker( $p['speaker_id'] ) : null;
						if ( ! $spk ) {
							continue;
						}
						?>
						<div class="ss-agenda__participant">
							<?php
							$img_url = '';
							if ( ! empty( $spk['image_attachment_id'] ) ) {
								$img_url = wp_get_attachment_image_url( $spk['image_attachment_id'], 'thumbnail' );
							}
							if ( empty( $img_url ) && ! empty( $spk['image_url'] ) ) {
								$img_url = $spk['image_url'];
							}
							?>
							<?php if ( ! empty( $img_url ) ) : ?>
								<img class="ss-agenda__participant-photo" src="<?php echo esc_url( $img_url ); ?>"
									alt="<?php echo esc_attr( $spk['name'] ); ?>"
									width="48" height="48"
									loading="lazy" decoding="async">
							<?php else : ?>
								<span class="ss-agenda__participant-placeholder" aria-hidden="true">
									<?php echo esc_html( function_exists( 'ss_speaker_initials' ) ? ss_speaker_initials( $spk['name'] ) : mb_strtoupper( mb_substr( $spk['name'], 0, 1 ) ) ); ?>
								</span>
							<?php endif; ?>

							<div class="ss-agenda__participant-info">
								<span class="ss-agenda__participant-name"><?php echo esc_html( $spk['name'] ); ?></span>
								<span class="ss-agenda__participant-role"><?php echo esc_html( ucfirst( $p['role'] ) ); ?></span>
								<?php if ( ! empty( $spk['job_title'] ) || ! empty( $spk['company'] ) ) : ?>
									<span class="ss-agenda__participant-title">
										<?php
										$parts = array_filter( array( $spk['job_title'], $spk['company'] ) );
										echo esc_html( implode( ', ', $parts ) );
										?>
									</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $session['cta_url'] ) ) : ?>
				<div class="ss-agenda__cta">
					<a href="<?php echo esc_url( $session['cta_url'] ); ?>" class="ss-agenda__cta-link">
						<?php echo esc_html( ! empty( $session['cta_label'] ) ? $session['cta_label'] : __( 'Learn More', 'sender-symposium' ) ); ?>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</article>
	<?php
}

/* -------------------------------------------------------------------------
   Formatting Helpers
   ------------------------------------------------------------------------- */

/**
 * Format a date for a day tab label.
 *
 * @param string $date Y-m-d date string.
 * @return string Formatted label e.g. "Day 1 · Mon 15 Jun"
 */
function ss_agenda_format_day_label( $date ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return $date;
	}
	$ts = strtotime( $date );
	return wp_date( 'D j M', $ts );
}

/**
 * Format a date for a day heading.
 *
 * @param string $date Y-m-d date string.
 * @return string e.g. "Monday, 15 June 2025"
 */
function ss_agenda_format_day_heading( $date ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return $date;
	}
	$ts = strtotime( $date );
	return wp_date( 'l, j F Y', $ts );
}

/**
 * Format a time string (24h to display format).
 *
 * @param string $time HH:MM format.
 * @return string e.g. "9:00" or "14:30"
 */
function ss_agenda_format_time( $time ) {
	if ( ! preg_match( '/^(\d{2}):(\d{2})$/', $time, $m ) ) {
		return $time;
	}
	$h = intval( $m[1] );
	return $h . ':' . $m[2];
}
