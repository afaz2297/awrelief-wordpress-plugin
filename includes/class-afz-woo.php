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
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'order_return_action' ), 10, 2 );
		add_action( 'wc_ajax_afz_search', array( $this, 'live_search' ) );
		// after WooCommerce's own product query, which resets post__in and the sort order
		add_action( 'pre_get_posts', array( $this, 'product_search' ), 20 );
		// reviews pasted from a phone app carry "object replacement" characters that show as [OBJ]
		add_filter( 'comment_text', function ( $text ) {
			return str_replace( "\u{FFFC}", '', $text );
		}, 5 );
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'meter_row' ) );
		// checkout summary: a picture of each product, with "Qty: 1" under its name
		add_action( 'woocommerce_review_order_before_cart_contents', function () {
			add_filter( 'woocommerce_cart_item_name', array( $this, 'review_item_name' ), 20, 2 );
		} );
		add_action( 'woocommerce_review_order_after_cart_contents', function () {
			remove_filter( 'woocommerce_cart_item_name', array( $this, 'review_item_name' ), 20 );
		} );
		add_filter( 'woocommerce_checkout_cart_item_quantity', function ( $html, $item ) {
			return '<span class="product-quantity">' . esc_html( sprintf( __( 'Qty: %d', 'afazalif' ), (int) $item['quantity'] ) ) . '</span>';
		}, 20, 2 );
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

	/** Checkout summary row: product picture on a light tile, then the name. */
	public function review_item_name( $name, $item ) {
		$product = isset( $item['data'] ) ? $item['data'] : null;
		$img_id  = $product ? (int) $product->get_image_id() : 0;
		if ( ! $img_id && $product && $product->get_parent_id() ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$img_id = $parent ? (int) $parent->get_image_id() : 0;
		}
		$src = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : '';
		$pic = $src ? '<span class="afz-ri__img"><img src="' . esc_url( $src ) . '" alt="" width="128" height="160" loading="lazy" decoding="async"></span>' : '';
		return $pic . '<span class="afz-ri__name">' . $name . '</span>';
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

	/**
	 * Product text without the old "Top: … / Middle: … / Base: …" lines, which the notes table
	 * on the product page already shows. Prose that merely mentions "top notes" stays.
	 */
	public static function without_notes( $html ) {
		$gap   = '(?:\s|&nbsp;|\x{00A0}|<[^>]+>)*';
		$word  = '(?:Top|Middle|Heart|Base|Bottom)';
		$label = $gap . $word . '(?:\s+notes?)?' . $gap . ':';
		$bare  = $gap . $word . '\s+(?!notes?\b)[^.<\n]{1,70}'; // "Top Lavender, Juniper Water"
		$html  = preg_replace( '#<(p|div)\b[^>]*>(?:' . $label . '.*?|' . $bare . ')</\1>#isu', '', (string) $html );
		return preg_replace( '#^(?:' . $label . '.*|' . $bare . ')$#mu', '', $html );
	}

	/** Returns start from the customer's own order list. */
	public function order_return_action( $actions, $order ) {
		$page = get_page_by_path( 'returns' );
		if ( $page && $order->has_status( array( 'processing', 'completed' ) ) ) {
			$actions['afz_return'] = array(
				'url'  => add_query_arg( 'order', $order->get_id(), get_permalink( $page ) ) . '#start-return',
				'name' => __( 'Return items', 'afazalif' ),
			);
		}
		return $actions;
	}

	/* ---------- search ---------- */

	/**
	 * Product IDs for a search, best match first: product names, then scent and category names,
	 * then short descriptions. Long descriptions are skipped on purpose: every candle's care
	 * text mentions "children", so "chil" used to return the whole shop instead of Chill.
	 */
	public static function search_ids( $q, $limit = 60 ) {
		global $wpdb;
		$q = trim( wp_strip_all_tags( (string) $q ) );
		if ( mb_strlen( $q ) < 2 ) {
			return array();
		}
		$like  = '%' . $wpdb->esc_like( $q ) . '%';
		$start = $wpdb->esc_like( $q ) . '%';
		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, CASE WHEN post_title LIKE %s THEN 0 WHEN post_title LIKE %s THEN 1 ELSE 3 END AS score
			 FROM $wpdb->posts WHERE post_type = 'product' AND post_status = 'publish' AND (post_title LIKE %s OR post_excerpt LIKE %s)",
			$start, $like, $like, $like
		) );
		$score = array();
		foreach ( $rows as $r ) {
			$score[ (int) $r->ID ] = (int) $r->score;
		}
		// scent ("Chill") and category ("Candles") names
		$terms = get_terms( array( 'taxonomy' => array( AFZ_Scents::TAX, 'product_cat' ), 'name__like' => $q, 'hide_empty' => true, 'fields' => 'ids' ) );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$ids = get_posts( array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => -1,
				'fields'      => 'ids',
				'tax_query'   => array( 'relation' => 'OR', array( 'taxonomy' => AFZ_Scents::TAX, 'terms' => $terms ), array( 'taxonomy' => 'product_cat', 'terms' => $terms ) ), // phpcs:ignore
			) );
			foreach ( $ids as $id ) {
				$score[ (int) $id ] = min( $score[ (int) $id ] ?? 9, 2 );
			}
		}
		$ids = array_keys( $score );
		usort( $ids, function ( $a, $b ) use ( $score ) {
			return $score[ $a ] <=> $score[ $b ] ?: strcmp( get_the_title( $a ), get_the_title( $b ) );
		} );
		return array_slice( $ids, 0, $limit );
	}

	/** The shop's search results page uses the same ranked lookup. */
	public function product_search( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || 'product' !== $query->get( 'post_type' ) ) {
			return;
		}
		$ids = self::search_ids( $query->get( 's' ), 200 );
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		// best match first, unless the shopper picked a sort order
		if ( empty( $_GET['orderby'] ) || 'relevance' === $_GET['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$query->set( 'orderby', 'post__in' );
			$query->set( 'order', 'ASC' );
		}
		add_filter( 'posts_search', array( $this, 'skip_content_search' ), 10, 2 );
	}

	public function skip_content_search( $search, $query ) {
		return $query->is_main_query() ? '' : $search;
	}

	/** Search as you type: products and scents for the search panel. */
	public function live_search() {
		check_ajax_referer( 'afz', 'nonce' );
		$q   = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
		$ids = self::search_ids( $q, 60 );
		$out = array( 'products' => array(), 'scents' => array(), 'total' => count( $ids ), 'all' => add_query_arg( array( 's' => rawurlencode( $q ), 'post_type' => 'product' ), home_url( '/' ) ) );
		foreach ( array_slice( $ids, 0, 6 ) as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$sc                = AFZ_Scents::of_product( $id );
			$label             = AFZ_Scents::format_label( $p );
			$out['products'][] = array(
				'name'  => $p->get_name(),
				'url'   => $p->get_permalink(),
				'img'   => $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img_src(),
				'price' => wp_strip_all_tags( html_entity_decode( $p->get_price_html() ) ),
				'meta'  => trim( ( 'Diffuser' === $label ? 'Reed Diffuser' : $label ) . ( $sc ? ' · ' . $sc['family'] : '' ), ' ·' ),
			);
		}
		$scents = get_terms( array( 'taxonomy' => AFZ_Scents::TAX, 'name__like' => $q, 'hide_empty' => false, 'number' => 4 ) );
		foreach ( is_wp_error( $scents ) ? array() : $scents as $t ) {
			$d               = AFZ_Scents::data( $t );
			$out['scents'][] = array( 'name' => $d['name'], 'url' => $d['url'], 'family' => $d['family'], 'aura' => $d['aura'], 'img' => $d['image'] ? wp_get_attachment_image_url( (int) $d['image'], 'thumbnail' ) : '' );
		}
		wp_send_json_success( $out );
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
		$aura  = AFZ_Scents::aura( $product, $plain, $sc );
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
