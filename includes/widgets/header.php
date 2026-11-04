<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Header extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-header';
	}

	public function get_title() {
		return __( 'AW Header & menu', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-header';
	}

	protected function register_controls() {
		$shop = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : '/shop/';

		$this->start_controls_section( 'nav_s', array( 'label' => __( 'Menu', 'afazalif' ) ) );
		$r = new Repeater();
		$r->add_control( 'label', array( 'label' => __( 'Label', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'link', array( 'label' => __( 'Link', 'afazalif' ), 'type' => Controls_Manager::URL ) );
		$r->add_control( 'mega', array( 'label' => __( 'Opens the shop menu on hover', 'afazalif' ), 'type' => Controls_Manager::SWITCHER ) );
		$this->repeater( 'nav', __( 'Links (left side)', 'afazalif' ), $r, array(
			array( 'label' => 'Shop', 'link' => array( 'url' => $shop ), 'mega' => 'yes' ),
			array( 'label' => 'Scents', 'link' => array( 'url' => '/#library' ) ),
			array( 'label' => 'Our Story', 'link' => array( 'url' => '/about-us/' ) ),
			array( 'label' => 'Journal', 'link' => array( 'url' => '/journal/' ) ),
		), '{{{ label }}}' );
		$this->text( 'help_text', __( 'Help link text', 'afazalif' ), 'Help' );
		$this->link( 'help_link', __( 'Help link', 'afazalif' ), '/faq/' );
		$this->add_control( 'announce_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => '<p style="line-height:1.5">' . esc_html__( 'Top bar messages are edited in AWRELIEF → Site settings → Store.', 'afazalif' ) . '</p>' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'mega_s', array( 'label' => __( 'Shop menu (dropdown)', 'afazalif' ) ) );
		$this->text( 'col1_title', __( 'Column 1 heading', 'afazalif' ), 'Shop by range' );
		$this->text( 'col1', __( 'Column 1 links', 'afazalif' ), "Candles | /product-category/candles/\nReed Diffusers | /product-category/reed-diffusers/\nDiffuser Refills | /product-category/diffuser-refill/\nInsect Repellent | /product-category/repellent/\nAccessories | /product-category/accessories/\nGift Cards | /gift-card/", 'textarea', array( 'rows' => 7, 'description' => __( 'One per line: Label | link. The number of products is added automatically for shop categories.', 'afazalif' ) ) );
		$this->text( 'col2_title', __( 'Column 2 heading', 'afazalif' ), 'Shop by mood' );
		$this->text( 'col2', __( 'Column 2 links', 'afazalif' ), "Fresh & Green | /#library\nFloral | /#library\nWarm & Gourmand | /#library\nSpiced & Woody | /#library", 'textarea', array( 'rows' => 5 ) );
		$this->media( 'feat_image', __( 'Feature image', 'afazalif' ), 'life-sofa.webp' );
		$this->text( 'feat_eyebrow', __( 'Feature small heading', 'afazalif' ), 'Most loved' );
		$this->text( 'feat_title', __( 'Feature title', 'afazalif' ), 'Heaven' );
		$this->text( 'feat_line', __( 'Feature line', 'afazalif' ), 'Pear · Iris · Tonka' );
		$this->link( 'feat_link', __( 'Feature link', 'afazalif' ), '/product/heaven-candle-220g/' );
		$this->end_controls_section();
	}

	/** "Label | /url" lines → [label, url, count]. */
	private static function lines( $text ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( '' === $parts[0] ) {
				continue;
			}
			$url   = afz_url( $parts[1] ?? '#' );
			$count = 0;
			if ( preg_match( '#product-category/([^/]+)#', $url, $m ) ) {
				$t     = get_term_by( 'slug', $m[1], 'product_cat' );
				$count = $t ? (int) $t->count : 0;
			}
			$out[] = array( $parts[0], $url, $count );
		}
		return $out;
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$messages = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) afz_opt( 'announce', '' ) ) ) );
		$count    = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		$account  = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'myaccount' ) ) : wp_login_url();
		$has_mega = false;
		foreach ( $s['nav'] as $n ) {
			$has_mega = $has_mega || 'yes' === $n['mega'];
		}
		$this->open( 'afz-header-root' );
		if ( $messages ) :
			?>
			<div class="announce" role="region" aria-label="<?php esc_attr_e( 'Announcements', 'afazalif' ); ?>">
				<div class="announce__track">
					<?php foreach ( array_values( $messages ) as $i => $m ) : ?>
						<p class="announce__msg<?php echo 0 === $i ? ' is-active' : ''; ?>"><?php echo esc_html( $m ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<header class="header" id="header">
			<div class="header__bar">
				<nav class="header__nav" aria-label="<?php esc_attr_e( 'Primary', 'afazalif' ); ?>">
					<button class="burger" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'afazalif' ); ?>" aria-expanded="false"><span></span><span></span></button>
					<ul class="nav">
						<?php foreach ( $s['nav'] as $n ) : ?>
							<li class="nav__item<?php echo 'yes' === $n['mega'] ? ' has-mega' : ''; ?>"><a<?php echo afz_link_attrs( $n['link'], array( 'class' => 'nav__link', 'data-hover' => '' ) ); // phpcs:ignore ?>><?php echo esc_html( $n['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="header__logo" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) . ' ' . __( 'home', 'afazalif' ) ); ?>">
					<span class="gold-mask gold-mask--emblem header__emblem"></span>
					<span class="gold-mask gold-mask--wordmark header__word"></span>
				</a>
				<div class="header__actions">
					<?php if ( $s['help_text'] ) : ?><a<?php echo afz_link_attrs( $s['help_link'], array( 'class' => 'nav__link hide-sm', 'data-hover' => '' ) ); // phpcs:ignore ?>><?php echo esc_html( $s['help_text'] ); ?></a><?php endif; ?>
					<button class="icon-btn" type="button" aria-label="<?php esc_attr_e( 'Search', 'afazalif' ); ?>" data-cursor="Search" data-open-search><?php echo afz_icon( 'search' ); // phpcs:ignore ?></button>
					<a href="<?php echo esc_url( $account ); ?>" class="icon-btn hide-sm" aria-label="<?php esc_attr_e( 'Account', 'afazalif' ); ?>"><?php echo afz_icon( 'account' ); // phpcs:ignore ?></a>
					<button class="icon-btn basket-btn<?php echo $count ? ' has-items' : ''; ?>" type="button" aria-label="<?php esc_attr_e( 'Basket', 'afazalif' ); ?>" data-open-cart>
						<?php echo afz_icon( 'basket' ); // phpcs:ignore ?>
						<span class="basket-btn__count"><?php echo (int) $count; ?></span>
					</button>
				</div>
			</div>
			<?php if ( $has_mega ) : ?>
				<div class="mega" aria-hidden="true">
					<div class="mega__inner wrap">
						<?php foreach ( array( 1, 2 ) as $c ) : ?>
							<div class="mega__col">
								<p class="eyebrow"><?php echo esc_html( $s[ 'col' . $c . '_title' ] ); ?></p>
								<ul class="mega__list<?php echo 2 === $c ? ' mega__list--sm' : ''; ?>">
									<?php foreach ( self::lines( $s[ 'col' . $c ] ) as $l ) : ?>
										<li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?><?php echo $l[2] ? ' <sup>' . esc_html( str_pad( (string) $l[2], 2, '0', STR_PAD_LEFT ) ) . '</sup>' : ''; ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endforeach; ?>
						<a<?php echo afz_link_attrs( $s['feat_link'], array( 'class' => 'mega__feature', 'data-cursor' => 'Shop' ) ); // phpcs:ignore ?>>
							<?php echo afz_img( $s['feat_image'], '', '', 'large' ); // phpcs:ignore ?>
							<span class="mega__feature-text"><span class="eyebrow"><?php echo esc_html( $s['feat_eyebrow'] ); ?></span><span class="display"><?php echo esc_html( $s['feat_title'] ); ?></span><span class="mono"><?php echo esc_html( $s['feat_line'] ); ?></span></span>
						</a>
					</div>
				</div>
			<?php endif; ?>
		</header>
		<div class="mmenu" aria-hidden="true">
			<ul class="mmenu__list">
				<?php foreach ( $s['nav'] as $n ) : ?>
					<li><a<?php echo afz_link_attrs( $n['link'] ); // phpcs:ignore ?>><?php echo esc_html( $n['label'] ); ?></a></li>
				<?php endforeach; ?>
				<?php if ( $s['help_text'] ) : ?><li><a<?php echo afz_link_attrs( $s['help_link'] ); // phpcs:ignore ?>><?php echo esc_html( $s['help_text'] ); ?></a></li><?php endif; ?>
			</ul>
			<div class="mmenu__foot mono"><a href="mailto:<?php echo esc_attr( afz_opt( 'email' ) ); ?>"><?php echo esc_html( afz_opt( 'email' ) ); ?></a><span><?php echo esc_html( $messages ? reset( $messages ) : '' ); ?></span></div>
		</div>
		<?php
		$this->close();
	}
}
