<?php
/**
 * Shared base for every AWRELIEF widget.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

abstract class AFZ_Widget_Base extends Widget_Base {

	public function get_categories() {
		return array( 'awrelief' );
	}

	public function get_keywords() {
		return array( 'awrelief', 'afz', 'afaz' );
	}

	public function get_custom_help_url() {
		return admin_url( 'admin.php?page=afazalif' );
	}

	/** Our markup brings its own wrappers. */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_style_depends(): array {
		return array( 'afazalif' );
	}

	public function get_script_depends(): array {
		return array( 'afazalif' );
	}

	/** Open the scoping wrapper every stylesheet rule hangs off. */
	protected function open( $extra = '' ) {
		echo '<div class="afz afz-w-' . esc_attr( $this->get_name() ) . ( $extra ? ' ' . esc_attr( $extra ) : '' ) . '">';
	}

	protected function close() {
		echo '</div>';
	}

	/* ---------- control helpers ---------- */

	protected function gold_help() {
		return __( 'Words in {curly brackets} turn gold italic. A new line starts a new line.', 'afazalif' );
	}

	protected function text( $key, $label, $default = '', $type = 'text', $extra = array() ) {
		$types = array(
			'text'     => Controls_Manager::TEXT,
			'textarea' => Controls_Manager::TEXTAREA,
		);
		$this->add_control( $key, array_merge( array(
			'label'       => $label,
			'type'        => $types[ $type ],
			'default'     => $default,
			'label_block' => true,
			'dynamic'     => array( 'active' => true ),
		), 'textarea' === $type ? array( 'rows' => 3 ) : array(), $extra ) );
	}

	protected function media( $key, $label, $default_file = '', $type = 'image', $extra = array() ) {
		$this->add_control( $key, array_merge( array(
			'label'       => $label,
			'type'        => Controls_Manager::MEDIA,
			'media_types' => array( $type ),
			'default'     => $default_file ? afz_seed_media( $default_file ) : array( 'url' => '', 'id' => 0 ),
		), $extra ) );
	}

	/**
	 * Still image laid over a film until the film really plays. iPhones in Low Power Mode refuse to
	 * autoplay and draw a play button on the video itself; the still covers it until the first touch.
	 */
	protected function film_poster( $class, $poster, $poster_m = '', $phone = '(max-width: 900px) and (orientation: portrait)' ) {
		if ( ! $poster && ! $poster_m ) {
			return '';
		}
		$out = '<picture class="film-poster ' . esc_attr( $class ) . '" aria-hidden="true">';
		if ( $poster_m && $poster ) {
			$out .= '<source media="' . esc_attr( $phone ) . '" srcset="' . esc_url( $poster_m ) . '">';
		}
		return $out . '<img src="' . esc_url( $poster ? $poster : $poster_m ) . '" alt="" decoding="async"></picture>';
	}

	protected function link( $key, $label, $default = '' ) {
		$this->add_control( $key, array(
			'label'   => $label,
			'type'    => Controls_Manager::URL,
			'default' => array( 'url' => $default ),
			'dynamic' => array( 'active' => true ),
		) );
	}

	protected function button_controls( $prefix, $label, $text, $url, $style = 'gold' ) {
		$this->add_control( $prefix . '_heading', array( 'label' => $label, 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->text( $prefix . '_text', __( 'Button text', 'afazalif' ), $text, 'text', array( 'description' => __( 'Leave empty to hide the button.', 'afazalif' ) ) );
		$this->link( $prefix . '_link', __( 'Link', 'afazalif' ), $url );
		$this->add_control( $prefix . '_style', array(
			'label'   => __( 'Style', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => $style,
			'options' => array( 'gold' => __( 'Gold', 'afazalif' ), 'ghost' => __( 'Outline', 'afazalif' ) ),
		) );
	}

	protected function button_html( $s, $prefix, $icon = '→' ) {
		$text = trim( (string) ( $s[ $prefix . '_text' ] ?? '' ) );
		if ( '' === $text ) {
			return '';
		}
		$style = ( $s[ $prefix . '_style' ] ?? 'gold' ) === 'ghost' ? 'ghost' : 'gold';
		$icon  = 'gold' === $style && $icon ? '<span class="btn__icon">' . esc_html( $icon ) . '</span>' : '';
		return '<a class="btn btn--' . $style . ' magnetic"' . afz_link_attrs( $s[ $prefix . '_link' ] ?? '' ) . '><span class="btn__label">' . esc_html( $text ) . '</span>' . $icon . '</a>';
	}

	protected function heading_tag( $default = 'h2' ) {
		$this->add_control( 'heading_tag', array(
			'label'   => __( 'Heading level (SEO)', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => $default,
			'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'p' => __( 'Not a heading', 'afazalif' ) ),
		) );
	}

	protected function tag( $s, $default = 'h2' ) {
		$t = $s['heading_tag'] ?? $default;
		return in_array( $t, array( 'h1', 'h2', 'h3', 'p' ), true ) ? $t : $default;
	}

	protected function section_id( $default = '' ) {
		$this->add_control( 'section_id', array(
			'label'       => __( 'Section ID (for links)', 'afazalif' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => $default,
			'description' => __( 'Letters, numbers and dashes. Link to it with #your-id.', 'afazalif' ),
			'separator'   => 'before',
		) );
	}

	protected function id_attr( $s ) {
		$id = sanitize_title( $s['section_id'] ?? '' );
		return $id ? ' id="' . esc_attr( $id ) . '"' : '';
	}

	/** Heading split into lines for the line reveal ("text<br>text"). */
	protected function split_lines( $text ) {
		return implode( '<br>', array_map( 'afz_hl', preg_split( '/\r\n|\r|\n/', trim( (string) $text ) ) ) );
	}

	/** Options list of published products for SELECT2 controls. */
	protected static function product_options() {
		static $opts;
		if ( null === $opts ) {
			$opts = array();
			foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
				$opts[ (string) $p->ID ] = $p->post_title;
			}
		}
		return $opts;
	}

	protected static function scent_options() {
		$opts = array( '' => __( '— None —', 'afazalif' ) );
		foreach ( AFZ_Scents::all() as $s ) {
			$opts[ (string) $s['id'] ] = $s['name'];
		}
		return $opts;
	}

	protected static function product_id_by_title( $title ) {
		$ids = get_posts( array( 'post_type' => 'product', 'title' => $title, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
		return $ids ? (string) $ids[0] : '';
	}

	/** Rating summary of a product: [average, count]. */
	protected static function rating( $product ) {
		return array( (float) $product->get_average_rating(), (int) $product->get_review_count() );
	}

	/** Editor text; site-relative links ("/delivery/") work wherever WordPress is installed. */
	protected function rich( $html ) {
		$html = preg_replace_callback( '#href=(["\'])(/(?!/)[^"\']*)\1#', function ( $m ) {
			return 'href=' . $m[1] . esc_url( home_url( $m[2] ) ) . $m[1];
		}, (string) $html );
		return $this->parse_text_editor( $html );
	}

	protected function repeater( $key, $label, Repeater $r, $defaults, $title_field ) {
		$this->add_control( $key, array(
			'label'       => $label,
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'default'     => $defaults,
			'title_field' => $title_field,
		) );
	}
}
