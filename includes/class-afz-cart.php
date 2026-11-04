<?php
/**
 * Basket drawer backend: one WooCommerce AJAX endpoint (wc-ajax=afz_cart) that reads,
 * adds and changes quantities, and returns the whole basket as JSON.
 * Replaces the Caddy side-cart plugin.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Cart {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wc_ajax_afz_cart', array( $this, 'endpoint' ) );
	}

	public function endpoint() {
		check_ajax_referer( 'afz', 'nonce' );
		$op  = sanitize_key( $_POST['op'] ?? 'get' );
		$err = '';
		if ( 'add' === $op ) {
			$product_id = absint( $_POST['id'] ?? 0 );
			$qty        = max( 1, absint( $_POST['qty'] ?? 1 ) );
			$product    = $product_id ? wc_get_product( $product_id ) : null;
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				$err = __( 'Sorry, this product is not available right now.', 'afazalif' );
			} elseif ( $product->is_type( 'variable' ) || $product->is_type( 'wgm_gift_card' ) ) {
				// products with options are chosen on their own page
				wp_send_json_success( array( 'redirect' => $product->get_permalink() ) );
			} elseif ( false === WC()->cart->add_to_cart( $product_id, $qty ) ) {
				$notices = wc_get_notices( 'error' );
				wc_clear_notices();
				$err = $notices ? wp_strip_all_tags( $notices[0]['notice'] ?? '' ) : __( 'Could not add this product.', 'afazalif' );
			}
		} elseif ( 'set' === $op ) {
			$key = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) );
			$qty = absint( $_POST['qty'] ?? 0 );
			if ( $key && WC()->cart->get_cart_item( $key ) ) {
				$qty ? WC()->cart->set_quantity( $key, $qty, true ) : WC()->cart->remove_cart_item( $key );
			}
		}
		WC()->cart->calculate_totals();
		$state = self::state();
		if ( $err ) {
			$state['error'] = $err;
		}
		wp_send_json_success( $state );
	}

	/** The basket as plain data for the drawer. */
	public static function state() {
		$out = array(
			'count'    => 0,
			'subtotal' => 0,
			'items'    => array(),
			'checkout' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
			'shop'     => function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' ),
		);
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $out;
		}
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$p = $item['data'];
			if ( ! $p ) {
				continue;
			}
			$img            = $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
			$out['items'][] = array(
				'key'   => $key,
				'id'    => $p->get_id(),
				'name'  => $p->get_name(),
				'qty'   => (int) $item['quantity'],
				'total' => (float) $item['line_subtotal'] + (float) $item['line_subtotal_tax'],
				'img'   => $img,
				'url'   => $p->get_permalink(),
				'max'   => $p->get_max_purchase_quantity(),
			);
			$out['count']  += (int) $item['quantity'];
		}
		$out['subtotal'] = (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax();
		return $out;
	}
}
