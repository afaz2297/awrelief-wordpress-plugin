<?php
/**
 * Plugin Name:       Afaz Alif – AWRELIEF
 * Plugin URI:        https://github.com/afaz2297/awrelief-wordpress-plugin
 * Update URI:        false
 * Description:       Design system, custom Elementor widgets, side cart, popups, scent library and site tools for AWRELIEF. Replaces the old page-builder add-ons, popup, side-cart and form plugins.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce, elementor
 * Author:            Afaz Alif
 * Author URI:        https://afazalif.com/
 * License:           GPL-2.0-or-later
 * Text Domain:       afazalif
 */

defined( 'ABSPATH' ) || exit;

define( 'AFZ_VERSION', '1.0.0' );
define( 'AFZ_FILE', __FILE__ );
define( 'AFZ_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFZ_URL', plugin_dir_url( __FILE__ ) );

require_once AFZ_DIR . 'includes/helpers.php';
require_once AFZ_DIR . 'includes/icons.php';
require_once AFZ_DIR . 'includes/class-afz-settings.php';
require_once AFZ_DIR . 'includes/class-afz-frontend.php';
require_once AFZ_DIR . 'includes/class-afz-scents.php';
require_once AFZ_DIR . 'includes/class-afz-cart.php';
require_once AFZ_DIR . 'includes/class-afz-woo.php';
require_once AFZ_DIR . 'includes/class-afz-forms.php';
require_once AFZ_DIR . 'includes/class-afz-elementor.php';
require_once AFZ_DIR . 'includes/class-afz-performance.php';
require_once AFZ_DIR . 'includes/class-afz-security.php';

AFZ_Settings::instance();
AFZ_Frontend::instance();
AFZ_Scents::instance();
AFZ_Cart::instance();
AFZ_Woo::instance();
AFZ_Forms::instance();
AFZ_Elementor::instance();
AFZ_Performance::instance();
AFZ_Security::instance();

register_activation_hook( __FILE__, array( 'AFZ_Settings', 'install_defaults' ) );
register_activation_hook( __FILE__, array( 'AFZ_Forms', 'install' ) );
// new /scent/… links need fresh permalink rules
register_activation_hook( __FILE__, function () {
	AFZ_Scents::instance()->register();
	flush_rewrite_rules();
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=afazalif' ) ) . '">' . esc_html__( 'Settings', 'afazalif' ) . '</a>' );
	return $links;
} );
