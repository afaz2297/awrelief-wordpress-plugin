<?php
defined( 'ABSPATH' ) || exit;

class AFZ_Widget_Gift extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-gift';
	}

	public function get_title() {
		return __( 'AW Gift card', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->media( 'image', __( 'Card image', 'afazalif' ), 'gift-card.webp' );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Not sure which scent?' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "Give the gift\nof {peace}", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'text', __( 'Text', 'afazalif' ), 'Choose an amount, add a personal message, and we\'ll send it straight to their inbox the same day.', 'textarea' );
		$gift = get_posts( array( 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'wps_wgm_giftcard' ) ) ) ); // phpcs:ignore
		$this->button_controls( 'btn', __( 'Button', 'afazalif' ), 'Send a gift card', $gift ? get_permalink( $gift[0] ) : '/gift-card/', 'gold' );
		$this->text( 'small', __( 'Small line', 'afazalif' ), 'From £10' );
		$this->section_id( 'gift' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$this->open( 'light' );
		?>
		<section class="gift light"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap gift__grid">
				<div class="gift__card" data-tilt data-cursor="Gift">
					<?php echo afz_img( $s['image'], '', __( 'AWRELIEF gift card', 'afazalif' ), 'large' ); // phpcs:ignore ?>
					<span class="gift__shine"></span>
				</div>
				<div class="gift__copy">
					<p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display gift__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<p><?php echo esc_html( $s['text'] ); ?></p>
					<?php echo $this->button_html( $s, 'btn' ); // phpcs:ignore ?>
					<?php if ( $s['small'] ) : ?><p class="mono gift__from"><?php echo esc_html( $s['small'] ); ?></p><?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
