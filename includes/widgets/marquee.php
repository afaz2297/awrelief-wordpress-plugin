<?php
defined( 'ABSPATH' ) || exit;

class AFZ_Widget_Marquee extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-marquee';
	}

	public function get_title() {
		return __( 'AW Scrolling promises', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-animation-text';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'items', __( 'Promises', 'afazalif' ), "Vegan\nCruelty-free\nNo parabens or sulfates\nRecyclable materials\nHandmade with love\nSoy wax", 'textarea', array( 'description' => __( 'One per line. The line speeds up and leans as visitors scroll.', 'afazalif' ), 'rows' => 7 ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s['items'] ) ) );
		$group = '';
		foreach ( $items as $item ) {
			$group .= '<span>' . esc_html( $item ) . '</span><i>✦</i>';
		}
		$this->open();
		echo '<section class="marquee" aria-label="' . esc_attr( implode( ', ', $items ) ) . '"><div class="marquee__track" data-marquee><div class="marquee__group">' . $group . '</div><div class="marquee__group" aria-hidden="true">' . $group . '</div></div></section>'; // phpcs:ignore
		$this->close();
	}
}
