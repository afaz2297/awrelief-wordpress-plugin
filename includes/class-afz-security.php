<?php
/**
 * Baseline hardening that never gets in a customer's way. Each part can be switched off
 * under AWRELIEF → Site settings → Security.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Security {

	const MAX_TRIES = 5;
	const LOCK_MIN  = 15;

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'boot' ) );
	}

	public function boot() {
		if ( afz_on( 'sec_editor' ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
		if ( afz_on( 'sec_xmlrpc' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'xmlrpc_methods', '__return_empty_array' );
			add_filter( 'wp_headers', function ( $h ) {
				unset( $h['X-Pingback'] );
				return $h;
			} );
		}
		if ( afz_on( 'sec_users' ) ) {
			add_filter( 'rest_endpoints', array( $this, 'hide_user_endpoints' ) );
			add_action( 'template_redirect', array( $this, 'block_author_scan' ), 1 );
			add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
				return 'users' === $name ? false : $provider;
			}, 10, 2 );
		}
		if ( afz_on( 'sec_headers' ) ) {
			add_action( 'send_headers', array( $this, 'headers' ) );
		}
		if ( afz_on( 'sec_login' ) ) {
			add_filter( 'authenticate', array( $this, 'check_lock' ), 30, 3 );
			add_action( 'wp_login_failed', array( $this, 'login_failed' ) );
			add_action( 'wp_login', array( $this, 'login_ok' ), 10, 1 );
		}
	}

	public function hide_user_endpoints( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	public function block_author_scan() {
		if ( ! is_admin() && isset( $_GET['author'] ) && ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
		if ( is_author() && ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	public function headers() {
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000' );
		}
	}

	private function key( $username = '' ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		return 'afz_login_' . md5( $ip . '|' . strtolower( (string) $username ) );
	}

	public function check_lock( $user, $username, $password ) {
		if ( '' === (string) $username ) {
			return $user;
		}
		$tries = (int) get_transient( $this->key( $username ) );
		if ( $tries >= self::MAX_TRIES ) {
			return new WP_Error( 'afz_locked', sprintf(
				/* translators: %d minutes */
				__( '<strong>Too many attempts.</strong> Please wait %d minutes or reset your password.', 'afazalif' ),
				self::LOCK_MIN
			) );
		}
		return $user;
	}

	public function login_failed( $username ) {
		$key = $this->key( $username );
		set_transient( $key, (int) get_transient( $key ) + 1, self::LOCK_MIN * MINUTE_IN_SECONDS );
	}

	public function login_ok( $username ) {
		delete_transient( $this->key( $username ) );
	}
}
