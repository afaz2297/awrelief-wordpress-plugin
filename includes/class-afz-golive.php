<?php
/**
 * AWRELIEF → Go live: checks the site is ready and runs the setup tools (tools/seed.php,
 * tools/pages.php, tools/shipping.php) from the dashboard, for hosts without a command line.
 * Only the design layer is touched: products, orders, customers and payments stay as they are.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Go_Live {

	const SLUG = 'afazalif-go-live';

	/** Where the design pictures and films go: wp-content/uploads/afz-design-assets/ */
	const ASSETS = 'afz-design-assets';

	/** step => [ label, tool file, arguments after the assets folder ] */
	const STEPS = array(
		'seed'     => array( '1. Design pictures, films, scents, homepage, header and footer', 'seed.php', array( '--home', '--theme' ) ),
		'pages'    => array( '2. Inner pages (Our Story, FAQ, Contact, Delivery, Returns, Terms, Privacy, Track my order, Care guide, Journal)', 'pages.php', array() ),
		'shipping' => array( '3. UK-only delivery methods', 'shipping.php', array() ),
	);

	/**
	 * Plugins on the old site whose job the AWRELIEF plugin now does. Deactivate, do not delete:
	 * the MailerLite plugin keeps the API key the newsletter form uses.
	 */
	const REPLACED = array(
		'caddy/caddy.php'                                                           => 'Caddy (side cart)',
		'icegram/icegram.php'                                                       => 'Icegram (popups)',
		'elementskit-lite/elementskit-lite.php'                                     => 'ElementsKit Lite',
		'sticky-header-effects-for-elementor/sticky-header-effects-for-elementor.php' => 'Sticky Header Effects',
		'wpforms-lite/wpforms.php'                                                  => 'WPForms Lite (contact form)',
		'official-mailerlite-sign-up-forms/mailerlite.php'                          => 'MailerLite sign-up forms (keep its API key: deactivate only)',
		'woo-refund-and-exchange-lite/woocommerce-refund-and-exchange-lite.php'     => 'Refund and Exchange (old returns portal)',
		'insert-headers-and-footers/ihaf.php'                                       => 'WPCode / Insert Headers and Footers (the Facebook tag is now in AWRELIEF settings)',
		'astra-sites/astra-sites.php'                                               => 'Starter Templates',
	);

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
	}

	public function menu() {
		add_submenu_page( AFZ_Settings::SLUG, __( 'Go live', 'afazalif' ), __( 'Go live', 'afazalif' ), 'manage_options', self::SLUG, array( $this, 'page' ) );
	}

	public static function assets_dir() {
		$up = wp_upload_dir( null, false );
		return trailingslashit( $up['basedir'] ) . self::ASSETS;
	}

	/** Each check: [ ok|warn|bad, title, detail ] */
	public function checks() {
		$c   = array();
		$dir = self::assets_dir();

		$c[] = did_action( 'elementor/loaded' ) ? array( 'ok', 'Elementor is active', '' ) : array( 'bad', 'Elementor is not active', 'Install and activate Elementor first.' );
		$c[] = class_exists( '\ElementorPro\Plugin' ) ? array( 'ok', 'Pro Elements is active', '' ) : array( 'bad', 'Pro Elements is not active', 'The header and footer need it. Upload and activate pro-elements.zip.' );
		$c[] = 'hello-elementor' === get_template() ? array( 'ok', 'Hello Elementor theme is active', '' ) : array( 'warn', 'The theme is not Hello Elementor', 'Current theme: ' . esc_html( wp_get_theme()->get( 'Name' ) ) . '. Activate Hello Elementor after step 1.' );

		$videos = glob( $dir . '/video/*.mp4' ) ?: array();
		$scents = glob( $dir . '/img/scents/*.webp' ) ?: array();
		$c[]    = ( count( $videos ) >= 4 && count( $scents ) >= 13 )
			? array( 'ok', 'Design pictures and films found', count( $videos ) . ' films, ' . count( $scents ) . ' scent pictures in uploads/' . self::ASSETS . '/' )
			: array( 'bad', 'Design pictures and films not found', 'Upload awrelief-design-assets.zip to wp-content/uploads/ and extract it there, so the folder uploads/' . self::ASSETS . '/ exists.' );

		$c[] = file_exists( WPMU_PLUGIN_DIR . '/aw-local-safety.php' )
			? array( 'bad', 'The local-copy safety plugin is on this site', 'wp-content/mu-plugins/aw-local-safety.php blocks all email, payments and outside connections. Delete it on the live site.' )
			: array( 'ok', 'No local-copy safety plugin', '' );

		$c[] = is_plugin_active( 'yaymail/yaymail.php' )
			? array( 'bad', 'YayMail is active', 'It replaces the WooCommerce emails, so customers would not get the AWRELIEF emails. Deactivate it under Plugins.' )
			: array( 'ok', 'WooCommerce emails use the AWRELIEF design', '' );

		$left = array();
		foreach ( self::REPLACED as $file => $what ) {
			if ( is_plugin_active( $file ) ) {
				$left[] = $what;
			}
		}
		$c[] = $left
			? array( 'warn', 'Old plugins the design replaces are still active', 'Deactivate (do not delete): ' . esc_html( implode( '; ', $left ) ) . '.' )
			: array( 'ok', 'Old side cart, popup, form and header plugins are off', '' );

		$c[] = get_user_by( 'login', 'afz-local-test' )
			? array( 'warn', 'Test account afz-local-test exists', 'Delete it under Users.' )
			: array( 'ok', 'No test account', '' );

		$c[] = '0' === (string) get_option( 'blog_public' )
			? array( 'warn', 'Search engines are blocked', 'Untick "Discourage search engines" under Settings → Reading on the live site.' )
			: array( 'ok', 'Search engines can see the site', '' );

		$c[] = 'GBP' === get_woocommerce_currency() ? array( 'ok', 'Currency is GBP (£)', '' ) : array( 'bad', 'Currency is ' . esc_html( get_woocommerce_currency() ), 'Set WooCommerce → Settings → General → Currency to Pound sterling.' );
		$c[] = '' !== trim( (string) get_option( 'blogname' ) ) ? array( 'ok', 'Site name: ' . esc_html( get_option( 'blogname' ) ), '' ) : array( 'warn', 'Site name is empty', 'It is set to AWRELIEF the next time an administrator opens the dashboard.' );

		$done = (array) get_option( AFZ_Updates::OPTION, array() );
		$open = array();
		foreach ( array_keys( AFZ_Updates::STEPS ) as $step ) {
			if ( empty( $done[ $step ] ) || 'running' === $done[ $step ]['result'] ) {
				$open[] = $step;
			}
		}
		$c[] = $open
			? array( 'warn', 'Content updates still running', 'Open any dashboard page a few more times: ' . esc_html( implode( ', ', $open ) ) . '.' )
			: array( 'ok', 'Content updates done (scent pictures, delivery page, site name)', '' );

		$ships = get_option( 'woocommerce_specific_ship_to_countries' );
		$c[]   = ( 'specific' === get_option( 'woocommerce_ship_to_countries' ) && array( 'GB' ) === array_values( (array) $ships ) )
			? array( 'ok', 'Ships to the UK only', '' )
			: array( 'warn', 'Not set to UK-only delivery yet', 'Step 3 does this.' );

		return $c;
	}

	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$output = '';
		$ran    = '';
		if ( isset( $_POST['afz_step'] ) ) {
			check_admin_referer( 'afz_go_live' );
			$ran = sanitize_key( wp_unslash( $_POST['afz_step'] ) );
			if ( empty( $_POST['afz_backup'] ) ) {
				$output = 'Tick the backup box first. Nothing was changed.';
			} elseif ( isset( self::STEPS[ $ran ] ) ) {
				$output = $this->run( $ran );
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Go live', 'afazalif' ); ?></h1>
			<p style="max-width:760px"><?php esc_html_e( 'Moves the AWRELIEF design onto this site: design pictures, scents, homepage, inner pages, header, footer and UK delivery. Products, orders, customers, payments and email settings are not touched. Each step is safe to run again; earlier page layouts are kept as revisions.', 'afazalif' ); ?></p>

			<h2><?php esc_html_e( 'Checks', 'afazalif' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<tbody>
				<?php
				$icons = array( 'ok' => array( 'yes-alt', '#2e7d32' ), 'warn' => array( 'warning', '#b26a00' ), 'bad' => array( 'dismiss', '#c62828' ) );
				foreach ( $this->checks() as $chk ) :
					list( $state, $title, $detail ) = $chk;
					?>
					<tr>
						<td style="width:28px"><span class="dashicons dashicons-<?php echo esc_attr( $icons[ $state ][0] ); ?>" style="color:<?php echo esc_attr( $icons[ $state ][1] ); ?>"></span></td>
						<td><strong><?php echo esc_html( $title ); ?></strong><?php echo $detail ? '<br><span style="color:#555">' . wp_kses_post( $detail ) . '</span>' : ''; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2 style="margin-top:28px"><?php esc_html_e( 'Steps', 'afazalif' ); ?></h2>
			<form method="post" style="max-width:900px">
				<?php wp_nonce_field( 'afz_go_live' ); ?>
				<p><label><input type="checkbox" name="afz_backup" value="1"> <?php esc_html_e( 'I have a fresh backup of this site (files and database).', 'afazalif' ); ?></label></p>
				<?php foreach ( self::STEPS as $key => $step ) : ?>
					<p><button type="submit" class="button<?php echo 'seed' === $key ? ' button-primary' : ''; ?>" name="afz_step" value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $step[0] ); ?></button></p>
				<?php endforeach; ?>
				<p style="color:#555"><?php esc_html_e( 'Run them in order. Step 1 uploads about 40 files; if the page stops with a time-out, click it again and it carries on where it stopped.', 'afazalif' ); ?></p>
			</form>

			<?php if ( $output ) : ?>
				<h2><?php echo esc_html( self::STEPS[ $ran ][0] ?? '' ); ?></h2>
				<pre style="max-width:900px;max-height:480px;overflow:auto;background:#111;color:#e8e2d6;padding:16px;border-radius:6px"><?php echo esc_html( $output ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}

	/** Runs one tool and returns what it printed. */
	public function run( $key ) {
		list( , $file, $extra ) = self::STEPS[ $key ];
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		ignore_user_abort( true );
		$dir = self::assets_dir();
		if ( 'seed' === $key && ! is_dir( $dir ) ) {
			return 'uploads/' . self::ASSETS . '/ not found. Nothing was changed.';
		}
		$args = array_merge( array( '', ABSPATH . 'wp-load.php', $dir ), $extra ); // read by the tool
		ob_start();
		try {
			include AFZ_DIR . 'tools/' . $file;
		} catch ( Throwable $e ) {
			echo "\nstopped: " . $e->getMessage() . "\n"; // phpcs:ignore
		}
		return (string) ob_get_clean();
	}
}
