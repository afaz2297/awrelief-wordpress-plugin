<?php
/**
 * Front-end assets and the site-wide UI: preloader, cursor, grain, basket drawer,
 * search, welcome popup and cookie banner.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Frontend {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 20 );
		add_action( 'wp_head', array( $this, 'head' ), 1 );
		add_action( 'wp_head', array( $this, 'head_code' ), 99 );
		add_action( 'wp_footer', array( $this, 'global_ui' ), 5 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		// Hello Elementor's own reset and theme styles are replaced by the design system.
		add_filter( 'hello_elementor_enqueue_style', '__return_false' );
		add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );
		add_filter( 'hello_elementor_header_footer', '__return_false' );
	}

	public static function asset_ver( $rel ) {
		$file = AFZ_DIR . $rel;
		return AFZ_VERSION . '.' . ( file_exists( $file ) ? filemtime( $file ) : '0' );
	}

	public static function is_editor() {
		return isset( $_GET['elementor-preview'] ) || ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode() ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	public function assets() {
		wp_enqueue_style( 'afz-fonts', 'https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..700;1,6..96,400..700&family=DM+Mono:wght@300;400&family=Hanken+Grotesk:wght@300;400;500;600&display=swap', array(), null );
		wp_enqueue_style( 'afazalif', AFZ_URL . 'assets/css/afazalif.css', array(), self::asset_ver( 'assets/css/afazalif.css' ) );

		wp_register_script( 'afz-gsap', AFZ_URL . 'assets/vendor/gsap.min.js', array(), '3.12.5', true );
		wp_register_script( 'afz-scrolltrigger', AFZ_URL . 'assets/vendor/ScrollTrigger.min.js', array( 'afz-gsap' ), '3.12.5', true );
		wp_register_script( 'afz-lenis', AFZ_URL . 'assets/vendor/lenis.min.js', array(), '1.1.13', true );
		wp_register_script( 'afz-smoke', AFZ_URL . 'assets/js/smoke.js', array(), self::asset_ver( 'assets/js/smoke.js' ), true );
		$deps = array( 'afz-gsap', 'afz-scrolltrigger' );
		if ( afz_on( 'smooth' ) ) {
			$deps[] = 'afz-lenis';
		}
		if ( afz_on( 'smoke' ) ) {
			$deps[] = 'afz-smoke';
		}
		wp_enqueue_script( 'afazalif', AFZ_URL . 'assets/js/afazalif.js', $deps, self::asset_ver( 'assets/js/afazalif.js' ), true );
		wp_localize_script( 'afazalif', 'AFZ', $this->config() );
	}

	private function config() {
		$wc = function_exists( 'WC' );
		return array(
			'ajax'     => $wc ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : admin_url( 'admin-ajax.php?action=%%endpoint%%' ),
			'nonce'    => wp_create_nonce( 'afz' ),
			'freeShip' => (float) afz_opt( 'free_ship', 40 ),
			'currency' => $wc ? html_entity_decode( get_woocommerce_currency_symbol() ) : '£',
			'checkout' => $wc ? wc_get_checkout_url() : '',
			'shop'     => $wc ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' ),
			'editor'   => self::is_editor(),
			'motion'   => array(
				'preloader' => afz_on( 'preloader' ) && is_front_page(),
				'smooth'    => afz_on( 'smooth' ),
				'cursor'    => afz_on( 'cursor' ),
				'smoke'     => afz_on( 'smoke' ),
			),
			'popup'    => array(
				'on'    => afz_on( 'popup_on' ) && ! afz_is_quiet_page(),
				'delay' => (int) afz_opt( 'popup_delay', 12 ),
			),
			'cookies'  => afz_on( 'cookies_on' ),
			'i18n'     => array(
				'spend'    => __( 'Spend <b>%s</b> more for <b>free UK delivery</b>', 'afazalif' ),
				'unlocked' => __( '<b>You\'ve unlocked free UK delivery ✦</b>', 'afazalif' ),
				'error'    => __( 'Something went wrong. Please try again.', 'afazalif' ),
				'products' => __( 'Products', 'afazalif' ),
				'scents'   => __( 'Scents', 'afazalif' ),
				'none'     => __( 'Nothing matches “%s” yet. Try a scent name like Heaven or Chill.', 'afazalif' ),
				'all'      => __( 'See all %d results', 'afazalif' ),
			),
		);
	}

	public function head() {
		echo '<meta name="theme-color" content="#0c0b09">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}

	public function head_code() {
		$code = (string) afz_opt( 'head_code', '' );
		if ( '' !== trim( $code ) ) {
			echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- saved only by users with unfiltered_html
		}
	}

	public function body_class( $classes ) {
		$classes[] = 'afz-site';
		if ( afz_on( 'preloader' ) && is_front_page() && ! self::is_editor() ) {
			$classes[] = 'is-loading';
		}
		return $classes;
	}

	public function global_ui() {
		$editor = self::is_editor();
		echo '<div class="afz afz-global">';
		if ( ! $editor && afz_on( 'preloader' ) && is_front_page() ) {
			include AFZ_DIR . 'templates/preloader.php';
		}
		if ( ! $editor ) {
			if ( afz_on( 'cursor' ) ) {
				echo '<div class="cursor is-hidden" aria-hidden="true"><span class="cursor__dot"></span><span class="cursor__ring"><em class="cursor__label"></em></span></div>';
			}
			if ( afz_on( 'grain' ) ) {
				echo '<div class="grain" aria-hidden="true"></div>';
			}
			echo '<div class="scroll-progress" aria-hidden="true"><span></span></div>';
		}
		include AFZ_DIR . 'templates/cart.php';
		include AFZ_DIR . 'templates/search.php';
		if ( ! $editor && afz_on( 'popup_on' ) && ! afz_is_quiet_page() ) {
			include AFZ_DIR . 'templates/popup.php';
		}
		if ( ! $editor && afz_on( 'cookies_on' ) ) {
			include AFZ_DIR . 'templates/cookies.php';
		}
		echo '</div>';
	}
}

/** Never interrupt checkout, basket or account pages with marketing UI. */
function afz_is_quiet_page() {
	return function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() || is_account_page() );
}
