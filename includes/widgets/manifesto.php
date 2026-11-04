<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Manifesto extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-manifesto';
	}

	public function get_title() {
		return __( 'AW Manifesto', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-blockquote';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'The AWRELIEF promise' );
		$this->text( 'text', __( 'Statement', 'afazalif' ), 'A home should feel like an exhale. Every candle, diffuser and refill we make is {handmade with love,} vegan, cruelty-free and free from parabens and sulfates. Made for one simple feeling: {peace.}', 'textarea', array( 'rows' => 5, 'description' => __( 'Words light up one by one as visitors read. Words in {curly brackets} turn gold.', 'afazalif' ) ) );
		$this->add_control( 'emblem', array( 'label' => __( 'Show the gold emblem', 'afazalif' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'floats', array( 'label' => __( 'Floating products', 'afazalif' ) ) );
		$this->media( 'float_a', __( 'Top left', 'afazalif' ), 'candle.webp' );
		$this->media( 'float_b', __( 'Top right', 'afazalif' ), 'diffuser.webp' );
		$this->media( 'float_c', __( 'Bottom left', 'afazalif' ), 'refill.webp' );
		$this->media( 'float_d', __( 'Bottom right', 'afazalif' ), 'holder-gold.webp' );
		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$depths = array( 'a' => '0.35', 'b' => '0.6', 'c' => '0.25', 'd' => '0.5' );
		$this->open();
		echo '<section class="manifesto">';
		foreach ( $depths as $k => $d ) {
			echo afz_img( $s[ 'float_' . $k ] ?? '', 'float float--' . $k, '', 'medium', array( 'data-depth' => $d ) ); // phpcs:ignore
		}
		echo '<div class="wrap manifesto__inner">';
		if ( $s['eyebrow'] ) {
			echo '<p class="eyebrow center">' . esc_html( $s['eyebrow'] ) . '</p>';
		}
		echo '<p class="manifesto__text display" data-words>' . afz_hl( $s['text'] ) . '</p>'; // phpcs:ignore
		if ( 'yes' === $s['emblem'] ) {
			echo '<div class="manifesto__sign"><span class="gold-mask gold-mask--emblem manifesto__emblem"></span></div>';
		}
		echo '</div></section>';
		$this->close();
	}
}
