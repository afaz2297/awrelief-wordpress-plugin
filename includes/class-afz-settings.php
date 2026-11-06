<?php
/**
 * "AWRELIEF" dashboard: one settings screen with tabs, stored in a single option.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Settings {

	const OPTION = 'afz_settings';
	const SLUG   = 'afazalif';

	private static $instance;
	private static $cache;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'update_option_' . self::OPTION, function () {
			self::$cache = null;
		} );
	}

	public static function get() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = is_array( $saved ) ? $saved : array();
		}
		return self::$cache;
	}

	/** Tabs → fields. Each field: label, type, default, help. */
	public static function schema() {
		return array(
			'store'       => array(
				'title'  => __( 'Store', 'afazalif' ),
				'icon'   => 'dashicons-store',
				'intro'  => __( 'Details used across the header, footer, basket and emails.', 'afazalif' ),
				'fields' => array(
					'announce'     => array( 'label' => __( 'Top bar messages', 'afazalif' ), 'type' => 'textarea', 'default' => "Free UK delivery over £40\nHandmade · Vegan · Cruelty-free\nJoin the AWRELIEF circle for 15% off your first order", 'help' => __( 'One message per line. They rotate every few seconds.', 'afazalif' ) ),
					'free_ship'    => array( 'label' => __( 'Free delivery from (£)', 'afazalif' ), 'type' => 'number', 'default' => '40', 'help' => __( 'Drives the progress bar in the basket. Keep it the same as the Free shipping minimum in WooCommerce → Settings → Shipping.', 'afazalif' ) ),
					'email'        => array( 'label' => __( 'Contact email', 'afazalif' ), 'type' => 'email', 'default' => 'hello@awrelief.com' ),
					'whatsapp'     => array( 'label' => __( 'WhatsApp number', 'afazalif' ), 'type' => 'text', 'default' => '447386047673', 'help' => __( 'International format without + or spaces, e.g. 447386047673.', 'afazalif' ) ),
					'instagram'    => array( 'label' => 'Instagram', 'type' => 'url', 'default' => 'https://instagram.com/awrelief' ),
					'tiktok'       => array( 'label' => 'TikTok', 'type' => 'url', 'default' => 'https://www.tiktok.com/@awrelief' ),
					'facebook'     => array( 'label' => 'Facebook', 'type' => 'url', 'default' => 'https://www.facebook.com/share/wiTDv1L9rmDBTABi/?mibextid=LQQJ4d' ),
					'company_name' => array( 'label' => __( 'Company name', 'afazalif' ), 'type' => 'text', 'default' => 'AWRELIEF' ),
					'company_no'   => array( 'label' => __( 'Company number', 'afazalif' ), 'type' => 'text', 'default' => '15435755', 'help' => __( 'Shown on the Terms page (UK limited companies must show it on their website).', 'afazalif' ) ),
					'copyright'    => array( 'label' => __( 'Footer copyright', 'afazalif' ), 'type' => 'text', 'default' => '© {year} AWRELIEF. All rights reserved.', 'help' => __( '{year} becomes the current year.', 'afazalif' ) ),
				),
			),
			'motion'      => array(
				'title'  => __( 'Motion', 'afazalif' ),
				'icon'   => 'dashicons-art',
				'intro'  => __( 'The premium effects. Visitors who ask their device for reduced motion always get a calm, static version.', 'afazalif' ),
				'fields' => array(
					'preloader' => array( 'label' => __( 'Logo preloader on the homepage', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'smooth'    => array( 'label' => __( 'Smooth scrolling', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'cursor'    => array( 'label' => __( 'Gold cursor (desktop)', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'smoke'     => array( 'label' => __( 'Candle smoke effect', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
				),
			),
			'popup'       => array(
				'title'  => __( 'Welcome offer', 'afazalif' ),
				'icon'   => 'dashicons-megaphone',
				'intro'  => __( 'Newsletter popup for first-time visitors. Shown once, then not again for 30 days.', 'afazalif' ),
				'fields' => array(
					'popup_on'      => array( 'label' => __( 'Show the popup', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'popup_delay'   => array( 'label' => __( 'Open after (seconds)', 'afazalif' ), 'type' => 'number', 'default' => '12' ),
					'popup_eyebrow' => array( 'label' => __( 'Small heading', 'afazalif' ), 'type' => 'text', 'default' => 'The AWRELIEF circle' ),
					'popup_title'   => array( 'label' => __( 'Title', 'afazalif' ), 'type' => 'text', 'default' => '{15% off} your first order', 'help' => __( 'Words in {curly brackets} turn gold.', 'afazalif' ) ),
					'popup_text'    => array( 'label' => __( 'Text', 'afazalif' ), 'type' => 'textarea', 'default' => 'Join for your welcome code, new scent launches and members-only offers. Gentle emails, never too many.' ),
					'popup_code'    => array( 'label' => __( 'Coupon code shown after joining', 'afazalif' ), 'type' => 'text', 'default' => 'NEWCUSTOMER', 'help' => __( 'Must exist in Marketing → Coupons.', 'afazalif' ) ),
					'popup_image'   => array( 'label' => __( 'Image', 'afazalif' ), 'type' => 'media', 'default' => '' ),
				),
			),
			'newsletter'  => array(
				'title'  => __( 'Newsletter', 'afazalif' ),
				'icon'   => 'dashicons-email-alt',
				'intro'  => __( 'Sign-ups from the website go to MailerLite. Every sign-up is also kept here, so nothing is lost if MailerLite is unreachable.', 'afazalif' ),
				'fields' => array(
					'ml_key'   => array( 'label' => __( 'MailerLite API key', 'afazalif' ), 'type' => 'password', 'default' => '', 'help' => __( 'Leave empty to use the key from the MailerLite for WooCommerce plugin.', 'afazalif' ) ),
					'ml_group' => array( 'label' => __( 'MailerLite group ID', 'afazalif' ), 'type' => 'text', 'default' => '', 'help' => __( 'Optional. New subscribers are added to this group.', 'afazalif' ) ),
				),
			),
			'cookies'     => array(
				'title'  => __( 'Cookies', 'afazalif' ),
				'icon'   => 'dashicons-shield',
				'intro'  => __( 'A small banner for UK/EU cookie consent. The choice is remembered for a year.', 'afazalif' ),
				'fields' => array(
					'cookies_on'   => array( 'label' => __( 'Show the cookie banner', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'cookies_text' => array( 'label' => __( 'Text', 'afazalif' ), 'type' => 'textarea', 'default' => 'We use essential cookies to run the shop and, with your permission, a few more to understand how the site is used.' ),
				),
			),
			'code'        => array(
				'title'  => __( 'Tracking & code', 'afazalif' ),
				'icon'   => 'dashicons-editor-code',
				'intro'  => __( 'Verification tags and tracking snippets for the <head> of every page.', 'afazalif' ),
				'fields' => array(
					'head_code' => array( 'label' => __( 'Code in <head>', 'afazalif' ), 'type' => 'code', 'default' => '<meta name="facebook-domain-verification" content="9l4ydf2ki8kg0ovyxor406cod1owo6" />' ),
				),
			),
			'performance' => array(
				'title'  => __( 'Speed', 'afazalif' ),
				'icon'   => 'dashicons-performance',
				'intro'  => __( 'Every image becomes WebP and loads only when needed. Unused WordPress extras are switched off.', 'afazalif' ),
				'fields' => array(
					'webp_on'      => array( 'label' => __( 'Convert new uploads to WebP', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'webp_quality' => array( 'label' => __( 'WebP quality', 'afazalif' ), 'type' => 'number', 'default' => '82' ),
					'max_width'    => array( 'label' => __( 'Largest image width (px)', 'afazalif' ), 'type' => 'number', 'default' => '2400', 'help' => __( 'Bigger uploads are scaled down to this width.', 'afazalif' ) ),
					'trim_wp'      => array( 'label' => __( 'Remove unused WordPress extras (emoji, embeds, RSD links)', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
				),
			),
			'security'    => array(
				'title'  => __( 'Security', 'afazalif' ),
				'icon'   => 'dashicons-lock',
				'intro'  => __( 'Sensible protection that never gets in a customer\'s way.', 'afazalif' ),
				'fields' => array(
					'sec_login'   => array( 'label' => __( 'Limit login attempts (5 tries, then a 15 minute pause)', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'sec_xmlrpc'  => array( 'label' => __( 'Turn off XML-RPC', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'sec_users'   => array( 'label' => __( 'Hide usernames from the public API and author links', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'sec_headers' => array( 'label' => __( 'Security headers', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
					'sec_editor'  => array( 'label' => __( 'Block the theme and plugin file editor', 'afazalif' ), 'type' => 'toggle', 'default' => '1' ),
				),
			),
		);
	}

	public static function defaults() {
		$d = array();
		foreach ( self::schema() as $tab ) {
			foreach ( $tab['fields'] as $key => $f ) {
				$d[ $key ] = $f['default'];
			}
		}
		return $d;
	}

	public static function install_defaults() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, self::defaults() );
		}
	}

	public function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 1.5c.6 2.4 2 3.6 3.6 4.4-1.7.6-3 2-3.6 4.4C9.4 7.9 8 6.5 6.4 5.9 8 5.1 9.4 3.9 10 1.5zM4.5 11c.4 1.3 1.1 2 2 2.4-.9.3-1.6 1-2 2.4-.4-1.4-1.1-2.1-2-2.4.9-.4 1.6-1.1 2-2.4zm11 0c.4 1.3 1.1 2 2 2.4-.9.3-1.6 1-2 2.4-.4-1.4-1.1-2.1-2-2.4.9-.4 1.6-1.1 2-2.4zM10 12.5c.5 2 1.6 3 3 3.5-1.4.5-2.5 1.5-3 3.5-.5-2-1.6-3-3-3.5 1.4-.5 2.5-1.5 3-3.5z"/></svg>' );
		add_menu_page( 'AWRELIEF', 'AWRELIEF', 'manage_options', self::SLUG, array( $this, 'page' ), $icon, 3 );
		add_submenu_page( self::SLUG, __( 'Site settings', 'afazalif' ), __( 'Site settings', 'afazalif' ), 'manage_options', self::SLUG, array( $this, 'page' ) );
		add_submenu_page( self::SLUG, __( 'Scents', 'afazalif' ), __( 'Scents', 'afazalif' ), 'manage_product_terms', 'edit-tags.php?taxonomy=afz_scent&post_type=product' );
	}

	public function register() {
		register_setting( 'afz', self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	public function sanitize( $in ) {
		$out = self::get();
		$in  = is_array( $in ) ? $in : array();
		foreach ( self::schema() as $tab ) {
			foreach ( $tab['fields'] as $key => $f ) {
				if ( 'toggle' === $f['type'] ) {
					// unchecked boxes are not posted; only reset toggles that belong to the tab being saved
					if ( isset( $in['__tab'] ) && isset( self::schema()[ $in['__tab'] ]['fields'][ $key ] ) ) {
						$out[ $key ] = empty( $in[ $key ] ) ? '0' : '1';
					}
					continue;
				}
				if ( ! array_key_exists( $key, $in ) ) {
					continue;
				}
				$v = wp_unslash( $in[ $key ] );
				switch ( $f['type'] ) {
					case 'number':
						$out[ $key ] = (string) max( 0, (float) $v );
						break;
					case 'email':
						$out[ $key ] = sanitize_email( $v );
						break;
					case 'url':
						$out[ $key ] = esc_url_raw( $v );
						break;
					case 'media':
						$out[ $key ] = (string) absint( $v );
						break;
					case 'textarea':
						$out[ $key ] = sanitize_textarea_field( $v );
						break;
					case 'code':
						// only administrators who may post unfiltered HTML can save scripts
						$out[ $key ] = current_user_can( 'unfiltered_html' ) ? $v : wp_kses_post( $v );
						break;
					default:
						$out[ $key ] = sanitize_text_field( $v );
				}
			}
		}
		return $out;
	}

	public function assets( $hook ) {
		$page = sanitize_key( $_GET['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! in_array( $page, array( self::SLUG, 'afz-entries', 'afz-subscribers' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'afz-admin', AFZ_URL . 'assets/css/admin.css', array(), AFZ_VERSION . '.' . filemtime( AFZ_DIR . 'assets/css/admin.css' ) );
		wp_enqueue_script( 'afz-admin', AFZ_URL . 'assets/js/admin.js', array( 'jquery' ), AFZ_VERSION . '.' . filemtime( AFZ_DIR . 'assets/js/admin.js' ), true );
	}

	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$schema = self::schema();
		$tab    = isset( $_GET['tab'] ) && isset( $schema[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'store'; // phpcs:ignore WordPress.Security.NonceVerification
		$opts   = array_merge( self::defaults(), self::get() );
		$stats  = $this->stats();
		?>
		<div class="afz-admin">
			<header class="afz-admin__hero">
				<div class="afz-admin__brand">
					<span class="afz-admin__emblem" aria-hidden="true"></span>
					<div>
						<p class="afz-admin__eyebrow"><?php esc_html_e( 'Site control centre', 'afazalif' ); ?></p>
						<h1>AWRELIEF</h1>
					</div>
				</div>
				<ul class="afz-admin__stats">
					<?php foreach ( $stats as $s ) : ?>
						<li><b><?php echo esc_html( $s[0] ); ?></b><span><?php echo esc_html( $s[1] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</header>

			<div class="afz-admin__body">
				<nav class="afz-admin__tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'afazalif' ); ?>">
					<?php foreach ( $schema as $key => $t ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG, 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $key === $tab ? 'is-active' : ''; ?>">
							<span class="dashicons <?php echo esc_attr( $t['icon'] ); ?>"></span><?php echo esc_html( $t['title'] ); ?>
						</a>
					<?php endforeach; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=afz-entries' ) ); ?>"><span class="dashicons dashicons-feedback"></span><?php esc_html_e( 'Messages', 'afazalif' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=afz-subscribers' ) ); ?>"><span class="dashicons dashicons-groups"></span><?php esc_html_e( 'Subscribers', 'afazalif' ); ?></a>
				</nav>

				<form class="afz-admin__panel" method="post" action="options.php">
					<?php settings_fields( 'afz' ); ?>
					<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[__tab]" value="<?php echo esc_attr( $tab ); ?>">
					<h2><?php echo esc_html( $schema[ $tab ]['title'] ); ?></h2>
					<p class="afz-admin__intro"><?php echo esc_html( $schema[ $tab ]['intro'] ); ?></p>
					<?php settings_errors(); ?>
					<div class="afz-admin__fields">
						<?php foreach ( $schema[ $tab ]['fields'] as $key => $f ) : ?>
							<?php $this->field( $key, $f, $opts[ $key ] ?? '' ); ?>
						<?php endforeach; ?>
					</div>
					<?php if ( 'performance' === $tab ) : ?>
						<?php AFZ_Performance::instance()->tool_panel(); ?>
					<?php endif; ?>
					<div class="afz-admin__save"><?php submit_button( __( 'Save changes', 'afazalif' ), 'primary afz-btn', 'submit', false ); ?></div>
				</form>
			</div>
			<p class="afz-admin__credit"><?php echo esc_html( sprintf( 'Afaz Alif · v%s', AFZ_VERSION ) ); ?></p>
		</div>
		<?php
	}

	private function field( $key, $f, $value ) {
		$name = self::OPTION . '[' . $key . ']';
		$id   = 'afz-' . $key;
		echo '<div class="afz-field afz-field--' . esc_attr( $f['type'] ) . '">';
		if ( 'toggle' === $f['type'] ) {
			echo '<label class="afz-toggle" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( '1', (string) $value, false ) . '><span class="afz-toggle__ui" aria-hidden="true"></span><span class="afz-toggle__label">' . esc_html( $f['label'] ) . '</span></label>';
		} else {
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label>';
			switch ( $f['type'] ) {
				case 'textarea':
				case 'code':
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . ( 'code' === $f['type'] ? 6 : 4 ) . '"' . ( 'code' === $f['type'] ? ' spellcheck="false" class="code"' : '' ) . '>' . esc_textarea( $value ) . '</textarea>';
					break;
				case 'media':
					$src = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : '';
					echo '<div class="afz-media" data-afz-media><input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><span class="afz-media__preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '' ) . '</span><button type="button" class="button" data-afz-pick>' . esc_html__( 'Choose image', 'afazalif' ) . '</button><button type="button" class="button-link" data-afz-clear>' . esc_html__( 'Remove', 'afazalif' ) . '</button></div>';
					break;
				default:
					$type = in_array( $f['type'], array( 'number', 'email', 'url', 'password' ), true ) ? $f['type'] : 'text';
					echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( 'number' === $type ? ' step="any" min="0"' : '' ) . ( 'password' === $type ? ' autocomplete="new-password"' : '' ) . '>';
			}
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="afz-field__help">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</div>';
	}

	/** Headline numbers for the dashboard hero. */
	private function stats() {
		$out = array();
		if ( function_exists( 'wc_get_orders' ) ) {
			$out[] = array( (string) wp_count_posts( 'product' )->publish, __( 'Products', 'afazalif' ) );
			$out[] = array( (string) wp_count_terms( array( 'taxonomy' => 'afz_scent', 'hide_empty' => false ) ), __( 'Scents', 'afazalif' ) );
		}
		$out[] = array( (string) AFZ_Forms::count_entries( 'unread' ), __( 'New messages', 'afazalif' ) );
		$out[] = array( (string) AFZ_Forms::count_subscribers(), __( 'Subscribers', 'afazalif' ) );
		return $out;
	}
}
