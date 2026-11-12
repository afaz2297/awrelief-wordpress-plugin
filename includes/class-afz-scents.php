<?php
/**
 * "Scents": a product taxonomy holding each fragrance's profile (family, top/heart/base
 * notes, aura colours, ingredient image). Candles, diffusers and refills of the same scent
 * share one entry, so the Scent Library, product pages and homepage all read the same data.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Scents {

	const TAX = 'afz_scent';

	const FIELDS = array(
		'family' => array( 'Scent family', 'text', 'e.g. Floral gourmand' ),
		'top'    => array( 'Top notes', 'text', 'e.g. Iris, Orange Blossom' ),
		'heart'  => array( 'Heart notes', 'text', 'e.g. Pear, Powdered Almond' ),
		'base'   => array( 'Base notes', 'text', 'e.g. Patchouli, Tonka Bean' ),
		'aura'   => array( 'Aura colour', 'color', 'Main glow behind the scent' ),
		'aura2'  => array( 'Second aura colour', 'color', 'Soft secondary glow' ),
		'image'  => array( 'Ingredient image', 'image', 'Transparent WebP of the ingredients' ),
		'order'  => array( 'Order', 'number', 'Lower numbers come first in the Scent Library' ),
	);

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( self::TAX . '_add_form_fields', array( $this, 'add_fields' ) );
		add_action( self::TAX . '_edit_form_fields', array( $this, 'edit_fields' ) );
		add_action( 'created_' . self::TAX, array( $this, 'save' ) );
		add_action( 'edited_' . self::TAX, array( $this, 'save' ) );
		add_filter( 'manage_edit-' . self::TAX . '_columns', array( $this, 'columns' ) );
		add_filter( 'manage_' . self::TAX . '_custom_column', array( $this, 'column' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'product_profile' ), 25 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'card_note_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_card_note' ) );
	}

	/** Short line under the product name on cards ("Plant-based · DEET-free"). */
	public function card_note_field() {
		woocommerce_wp_text_input( array(
			'id'          => '_afz_card_note',
			'label'       => __( 'Card line', 'afazalif' ),
			'description' => __( 'Short line under the name on product cards. Leave empty to show the scent notes.', 'afazalif' ),
			'desc_tip'    => true,
		) );
		woocommerce_wp_checkbox( array(
			'id'          => '_afz_plain',
			'label'       => __( 'Plain card background', 'afazalif' ),
			'description' => __( 'Show this product on a plain dark card, without the coloured glow.', 'afazalif' ),
		) );
		woocommerce_wp_text_input( array(
			'id'          => '_afz_aura',
			'label'       => __( 'Glow colour', 'afazalif' ),
			'placeholder' => '#a8cc8c',
			'description' => __( 'Colour behind the product on cards and its page, for products without a scent. Leave empty to use the scent colour.', 'afazalif' ),
			'desc_tip'    => true,
		) );
	}

	/** Glow colour behind a product: its own colour, then its scent, then gold. */
	public static function aura( $product, $plain = false, $scent = null ) {
		$own = sanitize_hex_color( (string) $product->get_meta( '_afz_aura' ) );
		if ( $own ) {
			return $own;
		}
		return $plain ? 'transparent' : ( $scent ? $scent['aura'] : '#c9a04a' );
	}

	public function save_card_note( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the product form
		if ( isset( $_POST['_afz_card_note'] ) ) {
			$product->update_meta_data( '_afz_card_note', sanitize_text_field( wp_unslash( $_POST['_afz_card_note'] ) ) );
		}
		$product->update_meta_data( '_afz_plain', empty( $_POST['_afz_plain'] ) ? 'no' : 'yes' );
		if ( isset( $_POST['_afz_aura'] ) ) {
			$product->update_meta_data( '_afz_aura', (string) sanitize_hex_color( wp_unslash( $_POST['_afz_aura'] ) ) );
		}
		// phpcs:enable
	}

	public function register() {
		register_taxonomy( self::TAX, array( 'product' ), array(
			'labels'            => array(
				'name'          => __( 'Scents', 'afazalif' ),
				'singular_name' => __( 'Scent', 'afazalif' ),
				'add_new_item'  => __( 'Add new scent', 'afazalif' ),
				'edit_item'     => __( 'Edit scent', 'afazalif' ),
				'search_items'  => __( 'Search scents', 'afazalif' ),
				'menu_name'     => __( 'Scents', 'afazalif' ),
			),
			'public'            => true,
			'hierarchical'      => true, // checkbox list on the product screen
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'scent' ),
		) );
	}

	/** Profile of one scent term as plain data. */
	public static function data( $term ) {
		$term = is_object( $term ) ? $term : get_term( $term, self::TAX );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}
		$d = array(
			'id'   => $term->term_id,
			'slug' => $term->slug,
			'name' => $term->name,
			'url'  => get_term_link( $term ),
		);
		foreach ( array_keys( self::FIELDS ) as $k ) {
			$d[ $k ] = get_term_meta( $term->term_id, 'afz_' . $k, true );
		}
		$d['aura']  = $d['aura'] ? $d['aura'] : '#b9a6d9';
		$d['aura2'] = $d['aura2'] ? $d['aura2'] : '#e8c9a0';
		return $d;
	}

	/** All scents in Library order. */
	public static function all() {
		$terms = get_terms( array( 'taxonomy' => self::TAX, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$out = array_map( array( __CLASS__, 'data' ), $terms );
		usort( $out, function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'] ?: strcmp( $a['name'], $b['name'] );
		} );
		return $out;
	}

	/** First scent assigned to a product, if any. */
	public static function of_product( $product_id ) {
		$terms = get_the_terms( $product_id, self::TAX );
		return $terms && ! is_wp_error( $terms ) ? self::data( $terms[0] ) : null;
	}

	/** The formats a scent comes in (by product category) with the lowest price of each. */
	public static function formats( $term_id ) {
		$cache = get_transient( 'afz_formats2_' . $term_id );
		if ( is_array( $cache ) ) {
			return $cache;
		}
		$ids = get_posts( array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'tax_query'   => array( array( 'taxonomy' => self::TAX, 'terms' => $term_id ) ), // phpcs:ignore
		) );
		$out = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p || ! $p->is_visible() ) {
				continue;
			}
			$label = self::format_label( $p );
			$price = (float) wc_get_price_to_display( $p );
			if ( ! isset( $out[ $label ] ) || $price < $out[ $label ]['price'] ) {
				$out[ $label ] = array( 'label' => $label, 'price' => $price, 'url' => $p->get_permalink(), 'id' => $id, 'img' => (int) $p->get_image_id() );
			}
		}
		$order = array( 'Candle' => 1, 'Diffuser' => 2, 'Refill' => 3 );
		uasort( $out, function ( $a, $b ) use ( $order ) {
			return ( $order[ $a['label'] ] ?? 9 ) <=> ( $order[ $b['label'] ] ?? 9 );
		} );
		$out = array_values( $out );
		set_transient( 'afz_formats2_' . $term_id, $out, DAY_IN_SECONDS );
		return $out;
	}

	public static function format_label( $product ) {
		$name = strtolower( $product->get_name() );
		// order matters: "Candle Holder" is an accessory, "Diffuser Refill" is a refill
		foreach ( array( 'holder' => 'Accessory', 'refill' => 'Refill', 'diffuser' => 'Diffuser', 'repellent' => 'Repellent', 'gift' => 'Gift card', 'candle' => 'Candle' ) as $needle => $label ) {
			if ( false !== strpos( $name, $needle ) ) {
				return $label;
			}
		}
		$cats = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		return $cats ? $cats[0] : __( 'Product', 'afazalif' );
	}

	/* ---------- admin ---------- */

	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( $screen && self::TAX === $screen->taxonomy ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_add_inline_script( 'wp-color-picker', "jQuery(function($){\$('.afz-color').wpColorPicker();\$(document).on('click','.afz-term-img',function(e){e.preventDefault();var b=\$(this),f=wp.media({title:'Ingredient image',library:{type:'image'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();b.siblings('input').val(a.id);b.siblings('.afz-term-prev').html('<img src=\"'+a.url+'\" style=\"max-width:120px\">');});f.open();});});" );
		}
	}

	private function input( $key, $value ) {
		list( , $type, $help ) = self::FIELDS[ $key ];
		$name = 'afz_' . $key;
		if ( 'image' === $type ) {
			$src = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
			return '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><span class="afz-term-prev">' . ( $src ? '<img src="' . esc_url( $src ) . '" style="max-width:120px">' : '' ) . '</span><button class="button afz-term-img">' . esc_html__( 'Choose image', 'afazalif' ) . '</button><p class="description">' . esc_html( $help ) . '</p>';
		}
		$cls = 'color' === $type ? 'afz-color' : 'regular-text';
		$t   = 'number' === $type ? 'number' : 'text';
		return '<input type="' . $t . '" class="' . $cls . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><p class="description">' . esc_html( $help ) . '</p>';
	}

	public function add_fields() {
		wp_nonce_field( 'afz_scent', 'afz_scent_nonce' );
		foreach ( self::FIELDS as $key => $f ) {
			echo '<div class="form-field"><label>' . esc_html( $f[0] ) . '</label>' . $this->input( $key, '' ) . '</div>'; // phpcs:ignore
		}
	}

	public function edit_fields( $term ) {
		wp_nonce_field( 'afz_scent', 'afz_scent_nonce' );
		foreach ( self::FIELDS as $key => $f ) {
			echo '<tr class="form-field"><th scope="row"><label>' . esc_html( $f[0] ) . '</label></th><td>' . $this->input( $key, get_term_meta( $term->term_id, 'afz_' . $key, true ) ) . '</td></tr>'; // phpcs:ignore
		}
	}

	public function save( $term_id ) {
		if ( ! isset( $_POST['afz_scent_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['afz_scent_nonce'] ), 'afz_scent' ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		foreach ( self::FIELDS as $key => $f ) {
			if ( ! isset( $_POST[ 'afz_' . $key ] ) ) {
				continue;
			}
			$v = wp_unslash( $_POST[ 'afz_' . $key ] );
			$v = 'color' === $f[1] ? sanitize_hex_color( $v ) : ( in_array( $f[1], array( 'image', 'number' ), true ) ? (string) absint( $v ) : sanitize_text_field( $v ) );
			update_term_meta( $term_id, 'afz_' . $key, $v );
		}
		delete_transient( 'afz_formats2_' . $term_id );
	}

	public function columns( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			if ( 'name' === $k ) {
				$new['afz_img'] = '';
			}
			$new[ $k ] = $v;
		}
		unset( $new['description'] );
		$new['afz_notes'] = __( 'Notes', 'afazalif' );
		return $new;
	}

	public function column( $out, $col, $term_id ) {
		if ( 'afz_img' === $col ) {
			$id = (int) get_term_meta( $term_id, 'afz_image', true );
			return $id ? wp_get_attachment_image( $id, array( 48, 48 ), false, array( 'style' => 'width:48px;height:48px;object-fit:contain;border-radius:8px;background:' . esc_attr( get_term_meta( $term_id, 'afz_aura', true ) ?: '#eee' ) . '33' ) ) : '';
		}
		if ( 'afz_notes' === $col ) {
			$d = self::data( $term_id );
			return esc_html( implode( ' · ', array_filter( array( $d['top'], $d['heart'], $d['base'] ) ) ) );
		}
		return $out;
	}

	/** Top / heart / base notes on single product pages. */
	public function product_profile() {
		$d = self::of_product( get_the_ID() );
		if ( ! $d || ! ( $d['top'] || $d['heart'] || $d['base'] ) ) {
			return;
		}
		echo '<div class="afz afz-profile light"><dl class="scent__notes">';
		foreach ( array( 'top' => __( 'Top', 'afazalif' ), 'heart' => __( 'Heart', 'afazalif' ), 'base' => __( 'Base', 'afazalif' ) ) as $k => $label ) {
			if ( $d[ $k ] ) {
				echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $d[ $k ] ) . '</dd></div>';
			}
		}
		echo '</dl></div>';
	}
}

// Product saves change which formats a scent comes in.
add_action( 'save_post_product', function ( $id ) {
	$terms = wp_get_post_terms( $id, AFZ_Scents::TAX, array( 'fields' => 'ids' ) );
	foreach ( is_array( $terms ) ? $terms : array() as $t ) {
		delete_transient( 'afz_formats2_' . $t );
	}
} );
