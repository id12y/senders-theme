<?php
/**
 * FAQ — Data Storage & Prepopulation
 *
 * Single serialized option: sender_symposium_faq
 * Prepopulates on first admin visit with fact-based content.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── ID Generation ─── */

function ss_faq_id( $prefix = 'faq' ) {
	return $prefix . '_' . bin2hex( random_bytes( 6 ) );
}

/* ─── Get / Save ─── */

function ss_get_faq() {
	$data = get_option( 'sender_symposium_faq', array() );
	if ( empty( $data ) || ! is_array( $data ) ) {
		return ss_faq_defaults();
	}
	$defaults = ss_faq_defaults();
	$data     = wp_parse_args( $data, $defaults );
	if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
		$data['settings'] = $defaults['settings'];
	} else {
		$data['settings'] = wp_parse_args( $data['settings'], $defaults['settings'] );
	}
	if ( ! isset( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
		$data['sections'] = array();
	}
	return $data;
}

function ss_save_faq( $data ) {
	update_option( 'sender_symposium_faq', $data, false );
}

/**
 * Prepopulate FAQ data if option does not yet exist.
 * Called on first admin visit to FAQ page.
 */
function ss_faq_maybe_prepopulate() {
	if ( false === get_option( 'sender_symposium_faq' ) ) {
		ss_save_faq( ss_faq_defaults() );
	}
}

/* ─── Sanitize full FAQ structure ─── */

function ss_sanitize_faq( $raw ) {
	$clean = array();

	$clean['page_title']      = sanitize_text_field( $raw['page_title'] ?? '' );
	$clean['page_subtitle']   = sanitize_text_field( $raw['page_subtitle'] ?? '' );
	$clean['intro_paragraph'] = wp_kses_post( $raw['intro_paragraph'] ?? '' );

	$settings = $raw['settings'] ?? array();
	$clean['settings'] = array(
		'accordion_single_open'        => ! empty( $settings['accordion_single_open'] ),
		'open_first_item_each_section' => ! empty( $settings['open_first_item_each_section'] ),
		'show_page_subtitle'           => ! empty( $settings['show_page_subtitle'] ),
		'show_intro_paragraph'         => ! empty( $settings['show_intro_paragraph'] ),
		'section_style'                => in_array( $settings['section_style'] ?? '', array( 'minimal', 'elevated' ), true )
			? $settings['section_style'] : 'elevated',
		'hotel_display_style'          => in_array( $settings['hotel_display_style'] ?? '', array( 'cards', 'list' ), true )
			? $settings['hotel_display_style'] : 'cards',
	);

	$clean['sections'] = array();
	if ( isset( $raw['sections'] ) && is_array( $raw['sections'] ) ) {
		foreach ( $raw['sections'] as $sec ) {
			if ( empty( $sec['title'] ) ) {
				continue;
			}
			$section = array(
				'id'       => sanitize_key( $sec['id'] ?? ss_faq_id( 'sec' ) ),
				'title'    => sanitize_text_field( $sec['title'] ),
				'subtitle' => sanitize_text_field( $sec['subtitle'] ?? '' ),
				'order'    => absint( $sec['order'] ?? 0 ),
				'items'    => array(),
			);
			if ( isset( $sec['items'] ) && is_array( $sec['items'] ) ) {
				foreach ( $sec['items'] as $item ) {
					if ( empty( $item['question'] ) ) {
						continue;
					}
					$section['items'][] = array(
						'id'          => sanitize_key( $item['id'] ?? ss_faq_id() ),
						'question'    => sanitize_text_field( $item['question'] ),
						'answer_html' => wp_kses_post( $item['answer_html'] ?? '' ),
						'order'       => absint( $item['order'] ?? 0 ),
					);
				}
				usort( $section['items'], function ( $a, $b ) {
					return $a['order'] - $b['order'];
				} );
			}
			$clean['sections'][] = $section;
		}
		usort( $clean['sections'], function ( $a, $b ) {
			return $a['order'] - $b['order'];
		} );
	}

	return $clean;
}

/* ─── Defaults with prepopulated content ─── */

function ss_faq_defaults() {
	return array(
		'page_title'      => __( 'Frequently Asked Questions', 'sender-symposium' ),
		'page_subtitle'   => __( 'Everything you need to know about Sender Symposium.', 'sender-symposium' ),
		'intro_paragraph' => '',
		'settings'        => array(
			'accordion_single_open'        => false,
			'open_first_item_each_section' => true,
			'show_page_subtitle'           => true,
			'show_intro_paragraph'         => false,
			'section_style'                => 'elevated',
			'hotel_display_style'          => 'cards',
		),
		'sections' => array(
			ss_faq_section_before_you_book(),
			ss_faq_section_travel(),
			ss_faq_section_on_the_day(),
			ss_faq_section_hotels(),
		),
	);
}

/* ── Section 1: Before You Book ── */

function ss_faq_section_before_you_book() {
	return array(
		'id'       => 'sec_before_book',
		'title'    => __( 'Before You Book', 'sender-symposium' ),
		'subtitle' => __( 'Quick answers to help you decide.', 'sender-symposium' ),
		'order'    => 1,
		'items'    => array(
			array(
				'id'          => 'faq_what_is',
				'question'    => __( 'What is Sender Symposium, and who is it for?', 'sender-symposium' ),
				'answer_html' => '<p>Sender Symposium is a focused, single-track conference for the people who build and operate email infrastructure at scale. It brings together deliverability engineers, email platform architects, postmasters, and technical leaders for a day of deep technical sessions, practical workshops, and peer networking.</p><p>If you send email at scale — whether transactional, marketing, or both — this event is designed for you.</p>',
				'order'       => 1,
			),
			array(
				'id'          => 'faq_when',
				'question'    => __( 'When does the event take place?', 'sender-symposium' ),
				'answer_html' => '<p>It takes place April 24, 2026 and is a one-day conference.</p>',
				'order'       => 2,
			),
			array(
				'id'          => 'faq_included',
				'question'    => __( 'What is included?', 'sender-symposium' ),
				'answer_html' => '<p>Your ticket includes access to all sessions and workshops in our working-room format — an intimate, focused setting designed for real learning and discussion rather than passive lectures.</p><p>Full catering is provided throughout the day, including morning coffee, lunch, and afternoon refreshments. The day concludes with the Sender Symposium Awards Dinner — an evening celebration recognising outstanding contributions to the email infrastructure community.</p>',
				'order'       => 3,
			),
			array(
				'id'          => 'faq_tickets',
				'question'    => __( 'Where can I purchase a ticket?', 'sender-symposium' ),
				'answer_html' => '<p>Purchase online here: <a href="https://sendersymposium.com/tickets/" target="_blank" rel="noopener noreferrer">sendersymposium.com/tickets/</a></p>',
				'order'       => 4,
			),
		),
	);
}

/* ── Section 2: Travel & Barcelona ── */

function ss_faq_section_travel() {
	return array(
		'id'       => 'sec_travel',
		'title'    => __( 'Travel & Barcelona', 'sender-symposium' ),
		'subtitle' => __( 'Getting here, where to stay, and what to expect.', 'sender-symposium' ),
		'order'    => 2,
		'items'    => array(
			array(
				'id'          => 'faq_location',
				'question'    => __( 'What is the location?', 'sender-symposium' ),
				'answer_html' => '<p>Sender Symposium takes place at <strong>La Pedrera (Casa Milà)</strong>, one of Antoni Gaudí\'s most celebrated works, located at Passeig de Gràcia 92 in Barcelona\'s Eixample district.</p>'
					. '<p><strong>Getting here:</strong></p>'
					. '<ul>'
					. '<li><strong>Metro:</strong> Diagonal (L3, L5) — 2-minute walk</li>'
					. '<li><strong>Train (FGC):</strong> Provença — 3-minute walk</li>'
					. '<li><strong>Bus:</strong> Lines 7, 22, 24, V15 stop directly outside</li>'
					. '<li><strong>Taxi:</strong> Any taxi to "La Pedrera, Passeig de Gràcia 92"</li>'
					. '<li><strong>Airport:</strong> Barcelona–El Prat (BCN) is approximately 30 minutes by taxi or Aerobus + metro</li>'
					. '</ul>'
					. '<p>The surrounding area is highly walkable, with restaurants, hotels, and shops within easy reach on foot.</p>',
				'order'       => 1,
			),
			array(
				'id'          => 'faq_sant_jordi',
				'question'    => __( 'Is there anything special happening the day before?', 'sender-symposium' ),
				'answer_html' => '<div class="faq-prose">'
					. '<p><strong>Yes.</strong> Thursday <strong>April 23, 2026</strong> is <strong>Sant Jordi</strong> (St George\'s Day), one of Barcelona\'s most beloved and distinctive annual festivals. It\'s a city-wide celebration of <strong>books, roses, and Catalan culture</strong>—lively but still a normal working day.</p>'
					. '<h4 class="faq-miniheading">What is Sant Jordi?</h4>'
					. '<p>Sant Jordi is the patron saint of Catalonia. Over time, the day has become a major celebration of culture and love—often described as Barcelona\'s literary, Catalan counterpart to Valentine\'s Day.</p>'
					. '<h4 class="faq-miniheading">The legend (briefly)</h4>'
					. '<p>According to the legend, Saint George defeats a dragon to save a princess. Where the dragon\'s blood falls, a rosebush blooms—hence the tradition of roses.</p>'
					. '<h4 class="faq-miniheading">What you\'ll see in the city</h4>'
					. '<ul class="faq-list">'
					. '<li><strong>Books &amp; roses everywhere:</strong> People exchange books and roses (today, everyone gives and receives both).</li>'
					. '<li><strong>Streets become stalls:</strong> Many central streets fill with book and flower stands—<strong>La Rambla</strong> is a major focal point.</li>'
					. '<li><strong>Cultural activity:</strong> You may see <em>sardanas</em> (traditional dances) and <em>castells</em> (human towers), including around <strong>Plaça Sant Jaume</strong>.</li>'
					. '<li><strong>Special building access:</strong> Some landmark buildings may offer special activities or open days (examples include the Town Hall, Palau Güell, Sant Pau, the Ateneu, and the Palau de la Generalitat).</li>'
					. '</ul>'
					. '<h4 class="faq-miniheading">Why it matters</h4>'
					. '<p>In 1995, UNESCO designated <strong>April 23</strong> as <strong>World Book and Copyright Day</strong>, inspired in part by Catalonia\'s tradition.</p>'
					. '<div class="faq-note" role="note">'
					. '<p><strong>Practical tip:</strong> Expect a lively city centre and busier streets than usual—allow extra time for travel, and consider enjoying the book and rose stalls while you\'re here.</p>'
					. '</div>'
					. '</div>',
				'order'       => 2,
			),
		),
	);
}

/* ── Section 3: On the Day ── */

function ss_faq_section_on_the_day() {
	return array(
		'id'       => 'sec_on_day',
		'title'    => __( 'On the Day', 'sender-symposium' ),
		'subtitle' => __( 'Practical details for a smooth experience.', 'sender-symposium' ),
		'order'    => 3,
		'items'    => array(
			array(
				'id'          => 'faq_arrive',
				'question'    => __( 'What time should I arrive?', 'sender-symposium' ),
				'answer_html' => '<p>Plan to arrive a little early for registration and coffee. Exact timing will be shared closer to the event.</p>',
				'order'       => 1,
			),
			array(
				'id'          => 'faq_bring',
				'question'    => __( 'What should I bring?', 'sender-symposium' ),
				'answer_html' => '<p>A photo ID, a way to take notes, and anything you need for a full day of working sessions.</p>',
				'order'       => 2,
			),
			array(
				'id'          => 'faq_venue_access',
				'question'    => __( 'Is the venue easy to reach?', 'sender-symposium' ),
				'answer_html' => '<p>La Pedrera sits on Passeig de Gràcia, one of Barcelona\'s best-connected boulevards. Metro lines L3 and L5 stop at Diagonal station (a 2-minute walk), and the FGC commuter rail stops at Provença (3 minutes). Multiple bus lines stop directly outside. The surrounding Eixample district is flat and highly walkable.</p>',
				'order'       => 3,
			),
		),
	);
}

/* ── Section 4: Hotels ── */

function ss_faq_section_hotels() {
	return array(
		'id'       => 'sec_hotels',
		'title'    => __( 'Hotels Near La Pedrera', 'sender-symposium' ),
		'subtitle' => __( 'A curated list within a short walk. Please check availability and rates.', 'sender-symposium' ),
		'order'    => 4,
		'items'    => array(
			array(
				'id'          => 'faq_hotels_budget',
				'question'    => __( '3-star / Budget (2–15 min walk)', 'sender-symposium' ),
				'answer_html' => ss_hotel_grid( array(
					array( 'Hotel Ginebra',        'Classic pension on Rambla de Catalunya.',                   '≈6 min walk', 'https://www.hotelginebra.com/' ),
					array( 'Hostal Oliva',          'Family-run guesthouse on Passeig de Gràcia.',              '≈3 min walk', 'https://www.hostaloliva.com/' ),
					array( 'Hotel Praktik Rambla',  'Design-forward budget hotel on Rambla de Catalunya.',      '≈8 min walk', 'https://www.hotelpraktikrambla.com/' ),
				), 'Budget' ),
				'order' => 1,
			),
			array(
				'id'          => 'faq_hotels_midrange',
				'question'    => __( '4-star / Upper-midrange (2–15 min walk)', 'sender-symposium' ),
				'answer_html' => ss_hotel_grid( array(
					array( 'Hotel Condes de Barcelona', 'Modernist palace on Passeig de Gràcia.',               '≈3 min walk',  'https://www.condesdebarcelona.com/' ),
					array( 'H10 Casa Mimosa',           'Contemporary hotel in a restored mansion.',            '≈2 min walk',  'https://www.h10hotels.com/en/barcelona-hotels/h10-casa-mimosa' ),
					array( 'Hotel Sixtytwo',             'Minimalist boutique on Passeig de Gràcia.',           '≈5 min walk',  'https://www.sixtytwohotel.com/' ),
					array( 'Olivia Balmes Hotel',        'Bright modern hotel on Carrer de Balmes.',            '≈7 min walk',  'https://www.oliviahotels.com/balmes/' ),
				), '4-star' ),
				'order' => 2,
			),
			array(
				'id'          => 'faq_hotels_luxury',
				'question'    => __( 'Luxury / 5-star / GL (2–15 min walk)', 'sender-symposium' ),
				'answer_html' => ss_hotel_grid( array(
					array( 'Monument Hotel',                'Five-star GL in a Modernist landmark.',            '≈1 min walk',  'https://www.monumenthotel.com/' ),
					array( 'Majestic Hotel & Spa',          'Grand luxury on Passeig de Gràcia since 1918.',   '≈8 min walk',  'https://www.hotelmajestic.es/' ),
					array( 'Hotel Alma Barcelona',          'Intimate luxury near Passeig de Gràcia.',          '≈5 min walk',  'https://www.almabarcelona.com/' ),
					array( 'Mandarin Oriental, Barcelona',  'Ultra-luxury on Passeig de Gràcia.',               '≈12 min walk', 'https://www.mandarinoriental.com/barcelona/' ),
				), 'Luxury' ),
				'order' => 3,
			),
			array(
				'id'          => 'faq_hotels_boutique',
				'question'    => __( 'Boutique / Design-focused (2–15 min walk)', 'sender-symposium' ),
				'answer_html' => ss_hotel_grid( array(
					array( 'Sir Victor Hotel',       'Design hotel with rooftop pool on the Diagonal.',          '≈5 min walk',  'https://www.sirhotels.com/victor/' ),
					array( 'Hotel Praktik Garden',   'Garden-terrace hotel in the Eixample.',                    '≈4 min walk',  'https://www.hotelpraktikgarden.com/' ),
					array( 'Casa Bonay',             'Creative hub hotel in a 19th-century building.',           '≈10 min walk', 'https://www.casabonay.com/' ),
					array( 'Cotton House Hotel',     'Former cotton guild in neoclassical splendour.',           '≈10 min walk', 'https://www.hotelcottonhouse.com/' ),
				), 'Boutique' ),
				'order' => 4,
			),
		),
	);
}

/**
 * Build hotel grid HTML for prepopulated answers.
 *
 * @param array  $hotels   Array of [name, desc, distance, url].
 * @param string $category Category label (Budget, 4-star, Luxury, Boutique).
 * @return string
 */
function ss_hotel_grid( $hotels, $category = '' ) {
	$html = '<div class="hotel-grid" data-columns="2">';
	foreach ( $hotels as $h ) {
		$html .= '<article class="hotel-card">'
			. '<header class="hotel-card-header">'
			. '<h5 class="hotel-name">' . esc_html( $h[0] ) . '</h5>'
			. '<div class="hotel-meta">';
		if ( $category ) {
			$html .= '<span class="hotel-tag">' . esc_html( $category ) . '</span>';
		}
		$html .= '<span class="hotel-walk">' . esc_html( $h[2] ) . '</span>'
			. '</div></header>'
			. '<p class="hotel-desc">' . esc_html( $h[1] ) . '</p>'
			. '<p class="hotel-link">'
			. '<a href="' . esc_url( $h[3] ) . '" target="_blank" rel="noopener" class="hotel-visit">'
			. 'Visit website <span aria-hidden="true" class="hotel-icon">&#8599;</span></a></p>'
			. '</article>';
	}
	$html .= '</div>';
	return $html;
}
