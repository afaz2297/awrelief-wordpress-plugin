<?php
/**
 * Search snippet text for pages that have none, on top of All in One SEO.
 * A description written in All in One SEO always wins; this only fills the gaps (shop, ranges,
 * scents, the inner pages) and shortens very long ones to what Google shows (about 160 characters).
 * It ships with the plugin, so the live site gets it without copying any SEO settings.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_SEO {

	const MAX = 158;

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'aioseo_description', array( $this, 'description' ), 20 );
		add_filter( 'aioseo_schema_output', array( $this, 'schema' ), 20 );
		add_action( 'template_redirect', array( $this, 'old_pages' ), 1 );
	}

	/** Pages the old returns plugin made; the Returns page and My account replace them. */
	const OLD_PAGES = array(
		'refund-request-form' => 'returns',
		'view-order-msg'      => 'my-account',
	);

	public function old_pages() {
		if ( in_array( 'woo-refund-and-exchange-lite/woocommerce-refund-and-exchange-lite.php', (array) get_option( 'active_plugins', array() ), true ) ) {
			return; // the old plugin still runs these pages until it is switched off at go-live
		}
		foreach ( self::OLD_PAGES as $old => $new ) {
			if ( is_page( $old ) ) {
				$to = get_page_by_path( $new );
				wp_safe_redirect( $to ? get_permalink( $to ) : home_url( '/' ), 301 );
				exit;
			}
		}
	}

	private static function delivery() {
		$min = (float) afz_opt( 'free_ship' );
		/* translators: %s: free delivery threshold, e.g. £40 */
		return $min > 0 ? sprintf( __( 'Free UK delivery over %s.', 'afazalif' ), '£' . rtrim( rtrim( number_format( $min, 2, '.', '' ), '0' ), '.' ) ) : __( 'Delivered across the UK.', 'afazalif' );
	}

	/** Text for the current page when All in One SEO has nothing. */
	private static function fallback() {
		$ship = self::delivery();
		if ( is_tax( 'afz_scent' ) && class_exists( 'AFZ_Scents' ) ) {
			$d     = AFZ_Scents::data( get_queried_object() );
			$notes = $d ? implode( ', ', array_filter( array( $d['top'], $d['heart'], $d['base'] ) ) ) : '';
			$name  = $d ? $d['name'] : single_term_title( '', false );
			/* translators: 1: scent name, 2: notes */
			$text = $notes ? sprintf( __( '%1$s by AWRELIEF, with notes of %2$s.', 'afazalif' ), $name, $notes ) : sprintf( __( '%s by AWRELIEF.', 'afazalif' ), $name );
			return $text . ' ' . __( 'Handmade candles, reed diffusers and refills, vegan and cruelty-free.', 'afazalif' ) . ' ' . $ship;
		}
		if ( is_product_category() || is_product_tag() ) {
			$term = get_queried_object();
			$desc = trim( wp_strip_all_tags( term_description( $term ) ) );
			/* translators: %s: range name, e.g. Candles */
			return $desc ? $desc : sprintf( __( 'Shop AWRELIEF %s: handmade, vegan and cruelty-free.', 'afazalif' ), $term->name ) . ' ' . $ship;
		}
		if ( is_shop() || is_post_type_archive( 'product' ) ) {
			return __( 'Shop handmade soy wax candles, reed diffusers, refills and plant-based insect repellent from AWRELIEF. Vegan and cruelty-free.', 'afazalif' ) . ' ' . $ship;
		}
		if ( is_front_page() ) {
			return __( 'Handmade soy wax candles, reed diffusers, refills and a plant-based, DEET-free insect repellent from AWRELIEF. Vegan and cruelty-free.', 'afazalif' ) . ' ' . $ship;
		}
		$pages = array(
			'delivery'          => __( 'AWRELIEF delivers across the UK with Royal Mail, standard or next day.', 'afazalif' ) . ' ' . $ship,
			'returns'           => __( 'How to return an AWRELIEF order within 14 days, and how refunds work.', 'afazalif' ),
			'faq'               => __( 'Answers to common questions about AWRELIEF candles, reed diffusers, refills, insect repellent, delivery and returns.', 'afazalif' ),
			'tcs'               => __( 'The terms and conditions for shopping with AWRELIEF.', 'afazalif' ),
			'privacy-policy'    => __( 'How AWRELIEF collects, uses and protects your personal data, and how we use cookies.', 'afazalif' ),
			'about-us'          => __( 'The AWRELIEF story: a plant-based, DEET-free insect repellent inspired by travels in Ghana, then handmade candles and reed diffusers.', 'afazalif' ),
			'contact-us'        => __( 'Questions about an order, a scent or a gift? Get in touch with the AWRELIEF team by email or WhatsApp.', 'afazalif' ),
			'care-guide'        => __( 'How to get the best from your AWRELIEF candle, reed diffuser, refill and insect repellent, with simple care and safety tips.', 'afazalif' ),
			'journal'           => __( 'The AWRELIEF journal: scent stories, care rituals and ideas for a calmer home.', 'afazalif' ),
			'track-order'       => __( 'Track your AWRELIEF order with your order number and email address.', 'afazalif' ),
			'gift-card'         => __( 'Give an AWRELIEF gift card, sent by email and spent on handmade candles, reed diffusers and more.', 'afazalif' ),
			'gift-card-balance' => __( 'Check the balance left on your AWRELIEF gift card.', 'afazalif' ),
		);
		foreach ( $pages as $slug => $text ) {
			if ( is_page( $slug ) ) {
				return $text;
			}
		}
		if ( is_singular( 'product' ) ) {
			return wp_strip_all_tags( get_the_excerpt() );
		}
		return '';
	}

	public function description( $value ) {
		if ( is_admin() ) {
			return $value;
		}
		// a description typed into All in One SEO for this page always wins
		if ( '' !== self::own() ) {
			return self::shorten( wp_strip_all_tags( (string) $value ) );
		}
		// otherwise All in One SEO builds one from the page text, which on Elementor pages is a jumble of headings
		$ours = self::fallback();
		return self::shorten( '' !== trim( $ours ) ? $ours : wp_strip_all_tags( (string) $value ) );
	}

	/** The same text in the page's structured data, which All in One SEO otherwise fills from the page text. */
	public function schema( $graphs ) {
		if ( ! is_array( $graphs ) || '' !== self::own() ) {
			return $graphs;
		}
		$ours = self::shorten( self::fallback() );
		if ( '' === $ours ) {
			return $graphs;
		}
		foreach ( $graphs as &$g ) {
			if ( is_array( $g ) && isset( $g['@id'], $g['description'] ) && '#webpage' === substr( (string) $g['@id'], -8 ) ) {
				$g['description'] = $ours;
			}
		}
		return $graphs;
	}

	/** The description saved in All in One SEO for the post or page being shown, if any. */
	private static function own() {
		if ( ! is_singular() && ! ( is_front_page() && get_option( 'page_on_front' ) ) ) {
			return '';
		}
		global $wpdb;
		$table = $wpdb->prefix . 'aioseo_posts';
		$id    = (int) get_queried_object_id();
		if ( ! $id ) {
			return '';
		}
		$own = wp_cache_get( 'afz_seo_desc_' . $id );
		if ( false === $own ) {
			$own = (string) $wpdb->get_var( $wpdb->prepare( "SELECT description FROM {$table} WHERE post_id = %d", $id ) ); // phpcs:ignore
			wp_cache_set( 'afz_seo_desc_' . $id, $own );
		}
		return trim( $own );
	}

	/** Cut at the last full sentence (or word) that fits. */
	public static function shorten( $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
		if ( mb_strlen( $text ) <= self::MAX ) {
			return $text;
		}
		$cut  = mb_substr( $text, 0, self::MAX );
		$stop = max( mb_strrpos( $cut, '. ' ), mb_strrpos( $cut, '! ' ), mb_strrpos( $cut, '? ' ) );
		if ( false !== $stop && $stop > 90 ) {
			return mb_substr( $cut, 0, $stop + 1 );
		}
		$cut = mb_substr( $cut, 0, mb_strrpos( $cut, ' ' ) );
		return rtrim( $cut, ' ,;:–-' ) . '…';
	}
}
