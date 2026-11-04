<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Ritual extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-ritual';
	}

	public function get_title() {
		return __( 'AW Anatomy of a scent', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-flip-box';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->add_control( 'product', array(
			'label'       => __( 'Featured product', 'afazalif' ),
			'type'        => Controls_Manager::SELECT2,
			'options'     => self::product_options(),
			'default'     => self::product_id_by_title( 'Heaven Candle 220g' ),
			'description' => __( 'Its scent, price and link fill the stage.', 'afazalif' ),
		) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Anatomy of a scent' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "Three layers.\n{One feeling.}", 'textarea', array( 'description' => $this->gold_help() ) );
		$r = new Repeater();
		$r->add_control( 'label', array( 'label' => __( 'Label', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'default' => '01 — Top notes' ) );
		$r->add_control( 'title', array( 'label' => __( 'Notes', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'text', array( 'label' => __( 'Text', 'afazalif' ), 'type' => Controls_Manager::TEXTAREA ) );
		$r->add_control( 'hue', array( 'label' => __( 'Glow colour', 'afazalif' ), 'type' => Controls_Manager::COLOR, 'default' => '#b9a6d9' ) );
		$this->repeater( 'steps', __( 'Layers (scroll through them)', 'afazalif' ), $r, array(
			array( 'label' => '01 — Top notes', 'title' => 'Iris & Orange Blossom', 'text' => 'The first breath when you light the wick. Bright, airy and soft, it greets you at the door.', 'hue' => '#b9a6d9' ),
			array( 'label' => '02 — Heart notes', 'title' => 'Pear & Powdered Almond', 'text' => 'As the wax pool forms, the heart opens: juicy pear wrapped in a soft, powdery sweetness.', 'hue' => '#c7d17a' ),
			array( 'label' => '03 — Base notes', 'title' => 'Patchouli & Tonka Bean', 'text' => 'What lingers long after the flame is out. Warm, deep and comforting, for up to 45 hours of burn.', 'hue' => '#c58a4d' ),
		), '{{{ title }}}' );
		$this->text( 'link_text', __( 'Link text', 'afazalif' ), 'Shop Heaven' );
		$this->end_controls_section();

		$this->start_controls_section( 'stage', array( 'label' => __( 'Stage', 'afazalif' ) ) );
		$this->media( 'candle', __( 'Candle image (transparent)', 'afazalif' ), 'candle.webp' );
		$this->media( 'ingredients', __( 'Ingredients image', 'afazalif' ), '', 'image', array( 'description' => __( 'Leave empty to use the scent\'s ingredient image.', 'afazalif' ) ) );
		$this->add_control( 'smoke', array( 'label' => __( 'Smoke rising from the wick', 'afazalif' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->section_id( 'ritual' );
		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = ! empty( $s['product'] ) ? wc_get_product( (int) $s['product'] ) : null;
		$scent   = $product ? AFZ_Scents::of_product( $product->get_id() ) : null;
		$ingr    = ! empty( $s['ingredients']['id'] ) || ! empty( $s['ingredients']['url'] ) ? $s['ingredients'] : ( $scent ? (int) $scent['image'] : 0 );
		$steps   = $s['steps'] ?: array();
		$tag     = $product ? implode( ' · ', array_filter( array( $scent ? $scent['name'] : '', AFZ_Scents::format_label( $product ) . ( preg_match( '/\d+\s?(g|ml)/i', $product->get_name(), $m ) ? ' ' . $m[0] : '' ), afz_money( wc_get_price_to_display( $product ) ) ) ) ) : '';
		$this->open();
		?>
		<section class="ritual"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="ritual__pin">
				<div class="ritual__aura" aria-hidden="true"></div>
				<div class="wrap ritual__grid">
					<div class="ritual__stage">
						<?php echo $ingr ? afz_img( $ingr, 'ritual__ingredients', '', 'large' ) : ''; // phpcs:ignore ?>
						<div class="ritual__halo" aria-hidden="true"></div>
						<?php if ( 'yes' === $s['smoke'] && afz_on( 'smoke' ) ) : ?><canvas class="smoke smoke--ritual" data-smoke data-smoke-strength="0.9" data-smoke-mode="plume"></canvas><?php endif; ?>
						<?php echo afz_img( $s['candle'], 'ritual__candle', $product ? $product->get_name() : '', 'large' ); // phpcs:ignore ?>
						<span class="ritual__flame" aria-hidden="true"></span>
						<?php if ( $tag ) : ?><p class="ritual__tag mono"><?php echo esc_html( $tag ); ?></p><?php endif; ?>
					</div>
					<div class="ritual__copy">
						<p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
						<h2 class="ritual__title display"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
						<div class="ritual__steps">
							<?php foreach ( $steps as $i => $st ) : ?>
								<article class="ritual__step<?php echo 0 === $i ? ' is-active' : ''; ?>" data-hue="<?php echo esc_attr( $st['hue'] ?: '#b9a6d9' ); ?>">
									<p class="mono ritual__num"><?php echo esc_html( $st['label'] ); ?></p>
									<h3 class="display"><?php echo esc_html( $st['title'] ); ?></h3>
									<p><?php echo esc_html( $st['text'] ); ?></p>
								</article>
							<?php endforeach; ?>
						</div>
						<div class="ritual__progress" aria-hidden="true">
							<span class="ritual__bar"><i></i></span>
							<span class="mono ritual__count"><b>01</b> / <?php echo esc_html( str_pad( (string) count( $steps ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						</div>
						<?php if ( $product && $s['link_text'] ) : ?>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="link-arrow" data-hover><?php echo esc_html( $s['link_text'] ); ?> <span>→</span></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
