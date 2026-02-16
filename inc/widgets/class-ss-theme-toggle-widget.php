<?php
/**
 * Elementor Widget: Theme Toggle (dark/light mode)
 *
 * Renders the same toggle button as the default theme header.
 * Drop this widget into any Elementor-built header, footer, or page.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class SS_Theme_Toggle_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ss-theme-toggle';
	}

	public function get_title() {
		return esc_html__( 'Theme Toggle', 'sender-symposium' );
	}

	public function get_icon() {
		return 'eicon-adjust';
	}

	public function get_categories() {
		return array( 'sender-symposium' );
	}

	public function get_keywords() {
		return array( 'dark', 'light', 'mode', 'toggle', 'theme', 'switch' );
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_toggle', array(
			'label' => esc_html__( 'Toggle', 'sender-symposium' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'icon_size', array(
			'label'      => esc_html__( 'Icon Size', 'sender-symposium' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array(
				'px' => array( 'min' => 12, 'max' => 48, 'step' => 1 ),
			),
			'default'    => array( 'unit' => 'px', 'size' => 20 ),
			'selectors'  => array(
				'{{WRAPPER}} .theme-toggle svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'align', array(
			'label'   => esc_html__( 'Alignment', 'sender-symposium' ),
			'type'    => \Elementor\Controls_Manager::CHOOSE,
			'options' => array(
				'left'   => array(
					'title' => esc_html__( 'Left', 'sender-symposium' ),
					'icon'  => 'eicon-text-align-left',
				),
				'center' => array(
					'title' => esc_html__( 'Center', 'sender-symposium' ),
					'icon'  => 'eicon-text-align-center',
				),
				'right'  => array(
					'title' => esc_html__( 'Right', 'sender-symposium' ),
					'icon'  => 'eicon-text-align-right',
				),
			),
			'default'   => 'center',
			'selectors' => array(
				'{{WRAPPER}}' => 'text-align: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		/* Style tab */
		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Style', 'sender-symposium' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'sender-symposium' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .theme-toggle' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'icon_hover_color', array(
			'label'     => esc_html__( 'Icon Hover Color', 'sender-symposium' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .theme-toggle:hover' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'bg_hover_color', array(
			'label'     => esc_html__( 'Background Hover Color', 'sender-symposium' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .theme-toggle:hover' => 'background-color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		?>
		<button
			class="theme-toggle"
			type="button"
			aria-pressed="false"
			aria-label="<?php esc_attr_e( 'Toggle dark mode', 'sender-symposium' ); ?>"
		>
			<svg class="theme-toggle__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
			<svg class="theme-toggle__moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
			</svg>
		</button>
		<?php
	}
}
