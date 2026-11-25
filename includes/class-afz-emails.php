<?php
/**
 * AWRELIEF look for every WooCommerce email (order, shipping, refund, account, password).
 * Works on top of WooCommerce's own templates, so WooCommerce updates keep working, and it
 * ships with the plugin, so the live site gets it without copying any settings.
 * The WooCommerce → Settings → Emails values are left as they are and only replaced while an
 * email is being built.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Emails {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_email_styles', array( $this, 'styles' ), 20, 2 );
		add_action( 'woocommerce_email_header', array( $this, 'start' ), 1 );
		add_action( 'woocommerce_email_footer', array( $this, 'stop' ), 99 );
		add_filter( 'woocommerce_email_footer_text', array( $this, 'footer_text' ), 5 );
		foreach ( array_keys( self::CLOSING ) as $id ) {
			add_filter( 'woocommerce_email_additional_content_' . $id, array( $this, 'closing' ), 10, 3 );
		}
		add_filter( 'woocommerce_email_order_items_args', array( $this, 'item_images' ) );
	}

	/** A small picture of each product beside its name in the order table (client, 25 Nov). */
	public function item_images( $args ) {
		if ( empty( $args['plain_text'] ) ) {
			$args['show_image'] = true;
			$args['image_size'] = array( 64, 64 );
		}
		return $args;
	}

	/** Closing line per customer email, used while the store still has WooCommerce's stock line. */
	const CLOSING = array(
		'customer_processing_order' => "Thank you for choosing AWRELIEF. We'll let you know as soon as your order is on its way.",
		'customer_completed_order'  => 'Thank you for choosing AWRELIEF. We hope you love your new scent.',
		'customer_on_hold_order'    => "Thank you for choosing AWRELIEF. We'll be in touch as soon as your payment is confirmed.",
		'customer_refunded_order'   => 'If you have any questions about your refund, just reply to this email.',
		'customer_failed_order'     => 'If you need a hand placing your order, just reply to this email.',
		'customer_cancelled_order'  => 'If this was a mistake or you have any questions, just reply to this email.',
		'customer_note'             => 'If you have any questions, just reply to this email.',
		'customer_invoice'          => 'If you have any questions, just reply to this email.',
	);

	/**
	 * Swaps WooCommerce's stock closing line ("Thanks for using awrelief.com!") for ours. A line
	 * the shop owner wrote in WooCommerce → Settings → Emails is kept.
	 */
	public function closing( $content, $object = null, $email = null ) {
		if ( ! $email instanceof WC_Email || ! isset( self::CLOSING[ $email->id ] ) ) {
			return $content;
		}
		$stock = array(
			$email->format_string( $email->get_default_additional_content() ),
			$email->format_string( __( 'Thanks for using {site_url}!', 'woocommerce' ) ),
			$email->format_string( __( 'We hope to see you again soon.', 'woocommerce' ) ),
			'',
		);
		return in_array( trim( (string) $content ), $stock, true ) ? self::CLOSING[ $email->id ] : $content;
	}

	/** Our logo and colours while the email header is built. */
	public function start() {
		add_filter( 'option_woocommerce_email_header_image', array( $this, 'logo' ) );
		add_filter( 'pre_transient_woocommerce_email_header_image', array( $this, 'logo' ) );
		add_filter( 'option_woocommerce_email_header_image_width', array( $this, 'logo_width' ) );
	}

	public function stop() {
		remove_filter( 'option_woocommerce_email_header_image', array( $this, 'logo' ) );
		remove_filter( 'pre_transient_woocommerce_email_header_image', array( $this, 'logo' ) );
		remove_filter( 'option_woocommerce_email_header_image_width', array( $this, 'logo_width' ) );
	}

	public function logo() {
		return AFZ_URL . 'assets/img/email-logo.png?v=1';
	}

	public function logo_width() {
		return '200';
	}

	/** Contact line, social links and the same copyright line as the site footer. */
	public function footer_text() {
		$email = (string) afz_opt( 'email' );
		$lines = array();
		if ( $email ) {
			/* translators: %s: contact email address */
			$lines[] = sprintf( __( 'Need help? Reply to this email or write to %s', 'afazalif' ), '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' );
		}
		$social = array();
		foreach ( array( 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook' ) as $key => $label ) {
			$url = (string) afz_opt( $key );
			if ( $url ) {
				$social[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
		}
		if ( $social ) {
			$lines[] = implode( ' &nbsp;&middot;&nbsp; ', $social );
		}
		$lines[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( home_url() ) ) ) . '</a>';
		$lines[] = '<span class="afz-copy">' . esc_html( str_replace( '{year}', wp_date( 'Y' ), (string) afz_opt( 'copyright' ) ) ) . '</span>';
		return implode( "\n\n", $lines );
	}

	public function styles( $css, $email = null ) {
		$ink     = '#0c0b09';
		$cream   = '#efe7da';
		$text    = '#1b1712';
		$muted   = '#6e6455';
		$gold    = '#c9a04a';
		$gold_lo = '#8e6420';
		$ivory   = '#efe6d6';
		$line    = '#e4dccd';
		// Apple Mail has Didot and Bodoni 72, the closest system fonts to the site's Bodoni Moda
		$serif = "'Bodoni Moda', 'Bodoni 72', Didot, 'Didot LT STD', 'Times New Roman', Georgia, serif";
		$sans  = "'Hanken Grotesk', 'Helvetica Neue', Helvetica, Arial, sans-serif";

		$css .= "
body, #outer_wrapper { background-color: {$cream} !important; }
#wrapper { max-width: 600px; padding: 32px 0 24px !important; background-color: {$cream} !important; }
#template_header_image { background-color: {$ink}; border-radius: 18px 18px 0 0; padding: 40px 24px 6px; text-align: center !important; }
#template_header_image p { margin: 0 !important; text-align: center !important; }
#template_header_image img { width: 200px; max-width: 200px; height: auto; margin: 0 auto !important; display: inline-block; }
#template_container { background-color: #ffffff !important; border: 0 !important; border-radius: 0 0 18px 18px !important; box-shadow: none !important; overflow: hidden; }
#template_header { background-color: {$ink} !important; border-radius: 0 !important; color: {$ivory} !important; border-bottom: 2px solid {$gold} !important; }
#header_wrapper { padding: 18px 40px 38px !important; text-align: center !important; }
#template_header h1, #header_wrapper h1 { color: {$ivory} !important; font-family: {$serif} !important; font-size: 32px !important; font-weight: 400 !important; line-height: 1.2 !important; letter-spacing: 0 !important; text-align: center !important; text-shadow: none !important; margin: 0 !important; }
#template_body, #body_content { background-color: #ffffff !important; }
#body_content table td { padding: 36px 40px 30px; }
#body_content table td td, #body_content table td th { padding: 12px; }
#body_content_inner, #body_content_inner_cell { color: {$text} !important; font-family: {$sans} !important; font-size: 15px !important; line-height: 1.65 !important; }
#body_content p { margin: 0 0 16px !important; }
h2 { color: {$text} !important; font-family: {$serif} !important; font-size: 22px !important; font-weight: 400 !important; line-height: 1.3 !important; margin: 30px 0 14px !important; }
h2 a, h2 a.link { color: {$gold_lo} !important; font-weight: 400 !important; }
h3 { color: {$text} !important; font-family: {$sans} !important; font-size: 15px !important; font-weight: 600 !important; }
a, .link { color: {$gold_lo} !important; font-weight: 500; }
.td { border-color: {$line} !important; color: {$text} !important; font-family: {$sans} !important; }
table.td { border: 1px solid {$line} !important; border-radius: 12px; border-collapse: separate !important; }
th.td { color: {$muted} !important; font-size: 14px !important; font-weight: 400 !important; }
thead th.td { font-size: 11px !important; font-weight: 500 !important; letter-spacing: .12em; text-transform: uppercase; }
.order-totals th.td, .order-totals td.td { border-top-width: 1px !important; font-size: 14px !important; }
.order-totals-total th.td, .order-totals-total td.td { color: {$text} !important; font-size: 17px !important; font-weight: 600 !important; border-top: 1px solid {$gold} !important; }
.order_item td.td { vertical-align: middle !important; }
.order_item img { width: 64px !important; height: 64px !important; max-width: 64px; border: 1px solid {$line}; border-radius: 10px; margin: 0 14px 0 0 !important; vertical-align: middle; display: inline-block; background-color: {$cream}; }
.address { border: 1px solid {$line} !important; border-radius: 12px; padding: 16px 18px !important; color: {$text} !important; font-style: normal !important; line-height: 1.6 !important; word-break: normal !important; overflow-wrap: break-word !important; }
.wc-item-meta, .wc-item-meta li, .wc-item-meta p { color: {$muted} !important; font-size: 13px !important; }
.button, a.button { background-color: {$ink} !important; color: {$ivory} !important; border-radius: 999px !important; padding: 13px 28px !important; text-decoration: none !important; font-weight: 500 !important; display: inline-block; }
#template_footer { background-color: transparent !important; }
#template_footer td, #credit { color: {$muted} !important; font-family: {$sans} !important; font-size: 12px !important; line-height: 1.6 !important; text-align: center !important; padding: 20px 24px 0 !important; border: 0 !important; }
#credit p { margin: 0 0 10px !important; }
#credit a { color: {$gold_lo} !important; font-weight: 500; text-decoration: none; }
#credit .afz-copy { color: {$muted}; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }
@media only screen and (max-width: 600px) {
	#wrapper { padding: 0 !important; }
	#template_header_image { border-radius: 0 !important; padding-top: 32px !important; }
	#template_container { border-radius: 0 !important; }
	#header_wrapper { padding: 16px 22px 30px !important; }
	#template_header h1 { font-size: 26px !important; }
	#body_content #body_content_inner_cell { padding: 26px 20px 22px !important; }
	#body_content #body_content_inner { font-size: 15px !important; }
	.address { padding: 12px !important; font-size: 14px !important; }
	#body_content #addresses td { padding: 0 4px !important; }
	.order_item img { display: block !important; width: 56px !important; height: 56px !important; margin: 0 0 8px !important; }
}
";
		return $css;
	}
}
