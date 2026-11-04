<?php
/**
 * Registers the "AWRELIEF" Elementor category and widgets.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Elementor {

	private static $instance;

	/** file => class */
	const WIDGETS = array(
		'header'        => 'AFZ_Widget_Header',
		'footer'        => 'AFZ_Widget_Footer',
		'hero'          => 'AFZ_Widget_Hero',
		'marquee'       => 'AFZ_Widget_Marquee',
		'manifesto'     => 'AFZ_Widget_Manifesto',
		'ritual'        => 'AFZ_Widget_Ritual',
		'categories'    => 'AFZ_Widget_Categories',
		'scent-library' => 'AFZ_Widget_Scent_Library',
		'products'      => 'AFZ_Widget_Products',
		'feature'       => 'AFZ_Widget_Feature',
		'story'         => 'AFZ_Widget_Story',
		'reviews'       => 'AFZ_Widget_Reviews',
		'gift'          => 'AFZ_Widget_Gift',
		'newsletter'    => 'AFZ_Widget_Newsletter',
		'page-hero'     => 'AFZ_Widget_Page_Hero',
		'rich'          => 'AFZ_Widget_Rich',
		'faq'           => 'AFZ_Widget_Faq',
		'split'         => 'AFZ_Widget_Split',
		'cards'         => 'AFZ_Widget_Cards',
		'contact'       => 'AFZ_Widget_Contact',
		'track'         => 'AFZ_Widget_Track',
	);

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_css' ) );
		add_action( 'admin_notices', array( $this, 'missing_elementor_notice' ) );
		add_action( 'elementor/init', array( $this, 'maybe_clear_cache' ) );
	}

	/**
	 * Elementor caches rendered CSS. When the plugin's code changes (update or deploy),
	 * clear that cache once so pages pick up the new markup.
	 */
	public function maybe_clear_cache() {
		$stamp = AFZ_VERSION;
		foreach ( glob( AFZ_DIR . 'includes/{*,widgets/*}.php', GLOB_BRACE ) ?: array() as $file ) {
			$stamp .= '|' . filemtime( $file );
		}
		$stamp = md5( $stamp );
		if ( get_option( 'afz_cache_version' ) === $stamp ) {
			return;
		}
		update_option( 'afz_cache_version', $stamp, false );
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	public function category( $manager ) {
		$manager->add_category( 'awrelief', array(
			'title' => 'AWRELIEF',
			'icon'  => 'eicon-apps',
		) );
	}

	public function widgets( $manager ) {
		require_once AFZ_DIR . 'includes/widgets/class-afz-widget-base.php';
		foreach ( self::WIDGETS as $file => $class ) {
			require_once AFZ_DIR . 'includes/widgets/' . $file . '.php';
			$manager->register( new $class() );
		}
	}

	/** Gold icons for our widgets in the Elementor panel. */
	public function editor_css() {
		wp_add_inline_style( 'elementor-editor', '#elementor-panel-category-awrelief .icon i,#elementor-panel-category-awrelief .elementor-element .icon{color:#c9a04a}#elementor-panel-category-awrelief .elementor-element:hover .icon i{color:#8e6420}' );
	}

	public function missing_elementor_notice() {
		if ( did_action( 'elementor/loaded' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'The AWRELIEF design plugin needs Elementor to be installed and active.', 'afazalif' ) . '</p></div>';
	}
}
