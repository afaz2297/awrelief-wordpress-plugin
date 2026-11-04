<?php
/**
 * WooCommerce in the AWRELIEF design: shop and category pages, product pages, basket,
 * checkout and account. Layout lives in templates/woo/; every WooCommerce hook that payment
 * gateways, gift cards and structured data rely on still runs.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Woo {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'template_include', array( $this, 'template' ), 99 );
		add_filter( 'woocommerce_enqueue_styles', array( $this, 'styles' ) );
		add_action( 'wp', array( $this, 'hooks' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'loop_shop_per_page', function () {
			return 24;
		} );
		add_filter( 'woocommerce_breadcrumb_defaults', array( $this, 'breadcrumb' ) );
		add_action( 'woocommerce_before_cart_totals', array( $this, 'meter' ) );
		add_filter( 'woocommerce_package_rates', array( $this, 'free_beats_standard' ), 100 );
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'meter_row' ) );
		add_filter( 'woocommerce_output_related_products_args', function ( $a ) {
			$a['posts_per_page'] = 4;
			$a['columns']        = 4;
			return $a;
		} );
	}

	public static function is_shop_page() {
		return function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_tax( AFZ_Scents::TAX ) );
	}

	public function template( $template ) {
		if ( ! function_exists( 'is_woocommerce' ) || AFZ_Frontend::is_editor() ) {
			return $template;
		}
		if ( is_product() ) {
			return AFZ_DIR . 'templates/woo/single.php';
		}
		if ( self::is_shop_page() ) {
			return AFZ_DIR . 'templates/woo/archive.php';
		}
		if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url( 'order-received' ) ) {
			return AFZ_DIR . 'templates/woo/page.php';
		}
		// everything else that Elementor did not lay out still gets the site frame
		if ( is_404() || ( is_singular( array( 'page', 'post' ) ) && ! self::built_with_elementor( get_queried_object_id() ) ) ) {
			return AFZ_DIR . 'templates/page.php';
		}
		return $template;
	}

	private static function built_with_elementor( $id ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		$doc = \Elementor\Plugin::$instance->documents->get( $id );
		return $doc && $doc->is_built_with_elementor();
	}

	/** WooCommerce's own purple styles are replaced by the design system. */
	public function styles( $styles ) {
		unset( $styles['woocommerce-general'], $styles['woocommerce-smallscreen'], $styles['woocommerce-layout'] );
		return $styles;
	}

	public function body_class( $c ) {
		if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
			$c[] = 'afz-woo-page';
		}
		return $c;
	}

	public function breadcrumb( $d ) {
		$d['delimiter']   = '<span class="crumbs__sep">/</span>';
		$d['wrap_before'] = '<nav class="crumbs mono" aria-label="' . esc_attr__( 'Breadcrumb', 'afazalif' ) . '">';
		$d['wrap_after']  = '</nav>';
		$d['home']        = __( 'Home', 'afazalif' );
		return $d;
	}

	/** Product pages: our own order and pieces, WooCommerce's hooks kept for gateways and SEO. */
	public function hooks() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 ); // placed by the template
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		// the scent profile is placed by the template itself
		remove_action( 'woocommerce_single_product_summary', array( AFZ_Scents::instance(), 'product_profile' ), 25 );
	}

	/**
	 * When a free delivery rate applies, its paid twin disappears so nobody pays £3.99 (or £6.99)
	 * by accident. Paid upgrades without a free twin stay available.
	 */
	public function free_beats_standard( $rates ) {
		// "Free Standard Delivery" replaces "Standard Delivery", "Free Next Day Delivery" replaces "Next Day Delivery"
		foreach ( array( 'standard', 'next day' ) as $kind ) {
			$has_free = false;
			foreach ( $rates as $r ) {
				$has_free = $has_free || ( 'free_shipping' === $r->get_method_id() && false !== stripos( $r->get_label(), $kind ) );
			}
			if ( ! $has_free ) {
				continue;
			}
			foreach ( $rates as $key => $r ) {
				if ( 'flat_rate' === $r->get_method_id() && false !== stripos( $r->get_label(), $kind ) && false === stripos( $r->get_label(), 'europe' ) ) {
					unset( $rates[ $key ] );
				}
			}
		}
		return $rates;
	}

	/** Free-delivery progress above the basket totals (same card as the basket drawer). */
	public function meter() {
		$free = (float) afz_opt( 'free_ship', 40 );
		if ( ! $free || ! WC()->cart ) {
			return;
		}
		$sum  = (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax();
		$left = max( 0, $free - $sum );
		echo '<div class="afz-meter"><div class="cart__ship' . ( $left <= 0 ? ' is-free' : '' ) . '"><div class="cart__ship-top">' . afz_icon( 'truck' ) . '<p class="cart__ship-msg">'; // phpcs:ignore
		echo $left > 0 ? wp_kses_post( sprintf( __( 'Spend <b>%s</b> more for <b>free UK delivery</b>', 'afazalif' ), afz_money( $left ) ) ) : '<b>' . esc_html__( 'You\'ve unlocked free UK delivery ✦', 'afazalif' ) . '</b>';
		echo '</p></div><span class="cart__ship-bar"><i style="transform:scaleX(' . esc_attr( min( 1, $sum / $free ) ) . ')"></i></span></div></div>';
	}

	/** On checkout, a one-line reminder when free delivery is close. */
	public function meter_row() {
		$free = (float) afz_opt( 'free_ship', 40 );
		if ( ! $free || ! WC()->cart ) {
			return;
		}
		$left = $free - ( (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax() );
		if ( $left > 0 && $left < $free ) {
			echo '<tr class="afz-near-free"><td colspan="2">' . wp_kses_post( sprintf( __( 'Add <b>%s</b> more for free UK delivery', 'afazalif' ), afz_money( $left ) ) ) . '</td></tr>';
		}
	}

	/* ---------- shared pieces ---------- */

	/** One product card, the same design as the homepage "Bestsellers". */
	public static function card( $product, $plain = null ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product || ! $product->is_visible() ) {
			return '';
		}
		if ( null === $plain ) {
			$plain = 'yes' === $product->get_meta( '_afz_plain' );
		}
		$id    = $product->get_id();
		$sc    = $plain ? null : AFZ_Scents::of_product( $id );
		$aura  = $plain ? 'transparent' : ( $sc ? $sc['aura'] : '#c9a04a' );
		$price = $product->get_price_html();
		$notes = trim( (string) $product->get_meta( '_afz_card_note' ) );
		if ( '' === $notes && $sc ) {
			$notes = implode( ' · ', array_filter( array( strtok( (string) $sc['top'], ',' ), strtok( (string) $sc['heart'], ',' ), strtok( (string) $sc['base'], ',' ) ) ) );
		}
		$badge = AFZ_Scents::format_label( $product );
		$badge = 'Diffuser' === $badge ? 'Reed Diffuser' : $badge;
		$count = (int) $product->get_review_count();
		$simple = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
		ob_start();
		?>
		<article class="prod">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="prod__media" style="--a1:<?php echo esc_attr( $aura ); ?>" data-cursor="View">
				<?php if ( $sc && $sc['image'] ) : ?><?php echo afz_img( (int) $sc['image'], 'prod__notesimg', '', 'medium_large' ); // phpcs:ignore ?><?php endif; ?>
				<?php echo $product->get_image_id() ? afz_img( (int) $product->get_image_id(), 'prod__pack', $product->get_name(), 'woocommerce_single' ) : wc_placeholder_img( 'woocommerce_single', array( 'class' => 'prod__pack' ) ); // phpcs:ignore ?>
				<span class="mono prod__badge"><?php echo esc_html( $badge ); ?></span>
				<?php if ( ! $product->is_in_stock() ) : ?>
					<span class="mono prod__flag"><?php esc_html_e( 'Sold out', 'afazalif' ); ?></span>
				<?php elseif ( $product->is_on_sale() ) : ?>
					<span class="mono prod__flag"><?php esc_html_e( 'Offer', 'afazalif' ); ?></span>
				<?php endif; ?>
				<?php if ( $simple ) : ?>
					<button class="prod__add" data-add="<?php echo esc_attr( $id ); ?>" type="button"><span><?php esc_html_e( 'Add to basket', 'afazalif' ); ?></span><span><?php echo esc_html( afz_money( wc_get_price_to_display( $product ) ) ); ?></span></button>
				<?php else : ?>
					<span class="prod__add"><span><?php echo $product->is_in_stock() ? esc_html__( 'Choose options', 'afazalif' ) : esc_html__( 'View product', 'afazalif' ); ?></span><span>→</span></span>
				<?php endif; ?>
			</a>
			<div class="prod__info">
				<h3 class="display prod__name"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
				<span class="prod__price"><?php echo wp_kses_post( $price ); ?></span>
				<?php if ( $notes ) : ?><span class="mono prod__notes"><?php echo esc_html( $notes ); ?></span><?php endif; ?>
				<?php if ( $count ) : ?><span class="prod__rating"><?php echo self::stars( (float) $product->get_average_rating() ); // phpcs:ignore ?><span class="mono"><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $count, 'afazalif' ), $count ) ); ?></span></span><?php endif; ?>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/** Gold stars for a rating, with the number for screen readers. */
	public static function stars( $rating ) {
		$rating = max( 0, min( 5, (float) $rating ) );
		return '<span class="stars stars--rated" style="--rating:' . esc_attr( $rating / 5 * 100 ) . '%" role="img" aria-label="' . esc_attr( sprintf( __( 'Rated %s out of 5', 'afazalif' ), number_format( $rating, 1 ) ) ) . '"><span aria-hidden="true">★★★★★</span></span>';
	}

	/** Other formats of the same scent ("Also as: Diffuser £34.99"). */
	public static function siblings( $product ) {
		$sc = AFZ_Scents::of_product( $product->get_id() );
		if ( ! $sc ) {
			return array();
		}
		return array_values( array_filter( AFZ_Scents::formats( $sc['id'] ), function ( $f ) use ( $product ) {
			return (int) $f['id'] !== $product->get_id();
		} ) );
	}

	/** Category chips for the shop filter bar. */
	public static function category_chips() {
		$current = is_product_category() ? get_queried_object_id() : 0;
		$out     = array( array( __( 'All', 'afazalif' ), get_permalink( wc_get_page_id( 'shop' ) ), is_shop() ) );
		$terms   = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) );
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$out[] = array( $t->name, get_term_link( $t ), $current === $t->term_id );
			foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $t->term_id ) ) as $child ) {
				$out[] = array( $child->name, get_term_link( $child ), $current === $child->term_id );
			}
		}
		return $out;
	}
}
