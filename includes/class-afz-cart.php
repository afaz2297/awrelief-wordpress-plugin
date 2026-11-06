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
		$out['upsells']  = self::upsells();
		return $out;
	}

	/**
	 * "You might also like": the store's own upsells and cross-sells for what is in the basket,
	 * then the same scent in other formats (a refill for a diffuser), then related products.
	 */
	private static function upsells( $limit = 6 ) {
		$in = $first = $scents = $related = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$in[] = (int) $item['product_id'];
		}
		foreach ( array_unique( $in ) as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$first = array_merge( $first, $p->get_upsell_ids(), $p->get_cross_sell_ids() );
			$sc    = AFZ_Scents::of_product( $id );
			if ( $sc ) {
				$scents = array_merge( $scents, wp_list_pluck( AFZ_Scents::formats( $sc['id'] ), 'id' ) );
			}
			$related = array_merge( $related, wc_get_related_products( $id, $limit ) );
		}
		$out = array();
		foreach ( array_diff( array_unique( array_map( 'intval', array_merge( $first, $scents, $related ) ) ), $in ) as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p || ! $p->is_visible() || ! $p->is_purchasable() || ! $p->is_in_stock() ) {
				continue;
			}
			$out[] = array(
				'id'    => $id,
				'name'  => $p->get_name(),
				'url'   => $p->get_permalink(),
				'img'   => $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src(),
				'price' => (float) wc_get_price_to_display( $p ),
				'add'   => $p->is_type( 'simple' ),
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}
}
