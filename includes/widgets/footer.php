<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Footer extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-footer';
	}

	public function get_title() {
		return __( 'AW Footer', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-footer';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'about', __( 'Short description', 'afazalif' ), 'Handmade candles, reed diffusers, refills and insect repellent. Vegan, cruelty-free and made with love.', 'textarea' );
		$this->add_control( 'social_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => '<p style="line-height:1.5">' . esc_html__( 'Social links and the copyright line are edited in AWRELIEF → Site settings → Store.', 'afazalif' ) . '</p>' ) );
		foreach ( array(
			1 => array( 'Shop', "Candles | /product-category/candles/\nReed Diffusers | /product-category/reed-diffusers/\nDiffuser Refills | /product-category/diffuser-refill/\nInsect Repellent | /product-category/repellent/\nGift Cards | /gift-card/" ),
			2 => array( 'Help', "Delivery | /delivery/\nReturns | /returns/\nFAQ | /faq/\nTrack my order | /track-order/\nContact | /contact-us/" ),
			3 => array( 'About', "Our Story | /about-us/\nJournal | /journal/\nCare Guide | /care-guide/\nTerms | /tcs/\nPrivacy & Cookies | /privacy-policy/" ),
		) as $i => $col ) {
			$this->text( 'col' . $i . '_title', sprintf( __( 'Column %d heading', 'afazalif' ), $i ), $col[0], 'text', array( 'separator' => 'before' ) );
			$this->text( 'col' . $i, sprintf( __( 'Column %d links', 'afazalif' ), $i ), $col[1], 'textarea', array( 'rows' => 6, 'description' => __( 'One per line: Label | link', 'afazalif' ) ) );
		}
		$this->text( 'secure', __( 'Secure checkout line', 'afazalif' ), 'Secure checkout', 'text', array( 'separator' => 'before' ) );
		$this->text( 'mark', __( 'Large signature', 'afazalif' ), 'Feel at {peace.}', 'text', array( 'description' => $this->gold_help() ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$this->open();
		?>
		<footer class="footer">
			<div class="wrap">
				<div class="footer__top">
					<div class="footer__brand">
						<span class="gold-mask gold-mask--emblem footer__emblem"></span>
						<p><?php echo esc_html( $s['about'] ); ?></p>
						<div class="footer__socials">
							<?php foreach ( afz_socials() as $key => $sc ) : ?>
								<a href="<?php echo esc_url( $sc['url'] ); ?>" class="social magnetic" aria-label="<?php echo esc_attr( $sc['label'] ); ?>" target="_blank" rel="noopener"><?php echo afz_icon( $key ); // phpcs:ignore ?></a>
							<?php endforeach; ?>
						</div>
					</div>
					<nav class="footer__cols" aria-label="<?php esc_attr_e( 'Footer', 'afazalif' ); ?>">
						<?php foreach ( array( 1, 2, 3 ) as $i ) : ?>
							<div><p class="eyebrow"><?php echo esc_html( $s[ 'col' . $i . '_title' ] ); ?></p><ul>
								<?php
								foreach ( preg_split( '/\r\n|\r|\n/', (string) $s[ 'col' . $i ] ) as $line ) {
									$p = array_map( 'trim', explode( '|', $line, 2 ) );
									if ( '' !== $p[0] ) {
										echo '<li><a href="' . esc_url( afz_url( $p[1] ?? '#' ) ) . '">' . esc_html( $p[0] ) . '</a></li>';
									}
								}
								?>
							</ul></div>
						<?php endforeach; ?>
					</nav>
				</div>
				<?php if ( $s['secure'] ) : ?>
					<div class="footer__social"><span class="mono footer__pay"><?php echo afz_icon( 'lock' ); // phpcs:ignore ?><?php echo esc_html( $s['secure'] ); ?></span></div>
				<?php endif; ?>
				<?php if ( $s['mark'] ) : ?>
					<div class="footer__mark" aria-hidden="true"><span class="display" data-split="chars"><?php echo afz_hl( $s['mark'] ); // phpcs:ignore ?></span></div>
				<?php endif; ?>
				<div class="footer__base mono"><span><?php echo esc_html( str_replace( '{year}', wp_date( 'Y' ), (string) afz_opt( 'copyright' ) ) ); ?></span></div>
			</div>
		</footer>
		<?php
		$this->close();
	}
}
