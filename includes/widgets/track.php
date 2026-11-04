<?php
defined( 'ABSPATH' ) || exit;

class AFZ_Widget_Track extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-track';
	}

	public function get_title() {
		return __( 'AW Track my order', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-search-results';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Order status' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), 'Where is {my parcel?}', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'intro', __( 'Intro', 'afazalif' ), 'Enter your order number (from your confirmation email) and the email you ordered with to see its status.', 'textarea' );
		$this->text( 'courier_title', __( 'Courier box title', 'afazalif' ), 'Sent with Royal Mail' );
		$this->text( 'courier_text', __( 'Courier box text', 'afazalif' ), 'Once your order is dispatched we email you a Royal Mail tracking number. Follow it live on the Royal Mail website.', 'textarea' );
		$this->link( 'courier_link', __( 'Courier tracking link', 'afazalif' ), 'https://www.royalmail.com/track-your-item' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$this->open( 'light' );
		?>
		<section class="track light">
			<div class="wrap track__grid">
				<div class="track__main">
					<p class="eyebrow eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display contact__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<p class="contact__intro"><?php echo esc_html( $s['intro'] ); ?></p>
					<div class="woo-wrap track__form"><?php echo do_shortcode( '[woocommerce_order_tracking]' ); // phpcs:ignore ?></div>
				</div>
				<aside class="info-card track__courier">
					<span class="mono info-card__label"><?php esc_html_e( 'Courier', 'afazalif' ); ?></span>
					<h3 class="display info-card__title"><?php echo esc_html( $s['courier_title'] ); ?></h3>
					<p class="info-card__text"><?php echo esc_html( $s['courier_text'] ); ?></p>
					<a class="btn btn--gold" <?php echo afz_link_attrs( array_merge( (array) $s['courier_link'], array( 'is_external' => true ) ) ); // phpcs:ignore ?>><span class="btn__label"><?php esc_html_e( 'Track with Royal Mail', 'afazalif' ); ?></span><span class="btn__icon">↗</span></a>
				</aside>
			</div>
		</section>
		<?php
		$this->close();
	}
}
