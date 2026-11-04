<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Feature extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-feature';
	}

	public function get_title() {
		return __( 'AW Product feature (arch video)', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->add_control( 'product', array(
			'label'   => __( 'Product', 'afazalif' ),
			'type'    => Controls_Manager::SELECT2,
			'options' => self::product_options(),
			'default' => self::product_id_by_title( 'Insect Repellent 100ml' ),
		) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Where it all began' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "Protection that\n{smells like peace}", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'lede', __( 'Text', 'afazalif' ), 'The product that started AWRELIEF. Our 100% plant-based insect repellent keeps mosquitoes, midges, horseflies and ticks away, with a fresh, natural scent you\'ll actually enjoy wearing.', 'textarea' );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => __( 'Point', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$this->repeater( 'points', __( 'Key points', 'afazalif' ), $r, array(
			array( 'text' => 'DEET-free' ),
			array( 'text' => 'Alcohol-free' ),
			array( 'text' => 'Vegan & cruelty-free' ),
			array( 'text' => 'Doubles as a room spray' ),
		), '{{{ text }}}' );
		$this->text( 'quote', __( 'Customer quote', 'afazalif' ), 'I did not get bitten for the whole two weeks that I was away, amazing results.', 'textarea' );
		$this->text( 'cite', __( 'Quote by', 'afazalif' ), 'Melisa · ★★★★★' );
		$this->text( 'button', __( 'Button text', 'afazalif' ), 'Add to basket' );
		$this->end_controls_section();

		$this->start_controls_section( 'media_s', array( 'label' => __( 'Video & image', 'afazalif' ) ) );
		$this->media( 'video', __( 'Video (desktop, 1080p MP4)', 'afazalif' ), 'repel-1080.mp4', 'video' );
		$this->media( 'video_m', __( 'Video (phones, 720p MP4)', 'afazalif' ), 'repel-720.mp4', 'video' );
		$this->media( 'poster', __( 'Poster image', 'afazalif' ), 'repel-poster.webp' );
		$this->media( 'pack', __( 'Floating product image', 'afazalif' ), 'repellent.webp' );
		$this->section_id( 'repel' );
		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = ! empty( $s['product'] ) ? wc_get_product( (int) $s['product'] ) : null;
		$video   = afz_media_url( $s['video'] );
		$mobile  = afz_media_url( $s['video_m'] );
		$poster  = afz_media_url( $s['poster'], 'large' );
		$this->open();
		?>
		<section class="repel"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap repel__grid">
				<div class="repel__media" data-cursor="Play">
					<div class="repel__arch">
						<?php if ( $video ) : ?>
							<video class="repel__video" muted loop playsinline preload="none"<?php echo $poster ? ' poster="' . esc_url( $poster ) . '"' : ''; ?> data-lazy-video>
								<?php if ( $mobile ) : ?><source data-src="<?php echo esc_url( $mobile ); ?>" type="video/mp4" media="(max-width: 900px)"><?php endif; ?>
								<source data-src="<?php echo esc_url( $video ); ?>" type="video/mp4">
							</video>
						<?php elseif ( $poster ) : ?>
							<img class="repel__video" src="<?php echo esc_url( $poster ); ?>" alt="" loading="lazy">
						<?php endif; ?>
					</div>
					<?php echo afz_img( $s['pack'], 'repel__leaf', '', 'medium_large', array( 'data-depth' => '0.4' ) ); // phpcs:ignore ?>
				</div>
				<div class="repel__copy">
					<p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display repel__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<p class="repel__lede"><?php echo esc_html( $s['lede'] ); ?></p>
					<?php if ( $s['points'] ) : ?>
						<ul class="repel__list">
							<?php foreach ( $s['points'] as $i => $pt ) : ?>
								<li><span class="mono"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $pt['text'] ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( $s['quote'] ) : ?>
						<blockquote class="repel__quote"><p>“<?php echo esc_html( $s['quote'] ); ?>”</p><cite class="mono"><?php echo esc_html( $s['cite'] ); ?></cite></blockquote>
					<?php endif; ?>
					<?php if ( $product ) : ?>
						<div class="repel__buy">
							<span class="price repel__price"><?php echo esc_html( afz_money( wc_get_price_to_display( $product ) ) ); ?></span>
							<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
								<button class="btn btn--gold magnetic" type="button" data-add="<?php echo esc_attr( $product->get_id() ); ?>"><span class="btn__label"><?php echo esc_html( $s['button'] ); ?></span><span class="btn__icon">+</span></button>
							<?php else : ?>
								<span class="mono"><?php esc_html_e( 'Back soon', 'afazalif' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
