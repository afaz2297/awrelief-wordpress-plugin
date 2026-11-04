<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Story extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-story';
	}

	public function get_title() {
		return __( 'AW Story (photo + stats)', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-image-box';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->media( 'image', __( 'Photo', 'afazalif' ), 'founder-synita.webp' );
		$this->text( 'caption', __( 'Photo caption', 'afazalif' ), 'Synita · Founder' );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Our story · Since 2024' );
		$this->heading_tag( 'h2' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "Created with purpose.\n{Inspired by travel.}", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'lede', __( 'Text', 'afazalif' ), 'It began on Synita\'s travels to Ghana. Unable to find an insect repellent without harsh chemicals and unpleasant smells, she made her own: plant-based, DEET-free and alcohol-free. That first bottle grew into candles, reed diffusers and refills, each one handmade to bring a little more calm to everyday life.', 'textarea', array( 'rows' => 6 ) );
		$this->button_controls( 'btn', __( 'Button', 'afazalif' ), 'Read our story', '/about-us/', 'ghost' );
		$r = new Repeater();
		$r->add_control( 'value', array( 'label' => __( 'Number', 'afazalif' ), 'type' => Controls_Manager::NUMBER, 'default' => 13 ) );
		$r->add_control( 'from', array( 'label' => __( 'Count up from', 'afazalif' ), 'type' => Controls_Manager::NUMBER, 'default' => 0 ) );
		$r->add_control( 'decimals', array( 'label' => __( 'Decimals', 'afazalif' ), 'type' => Controls_Manager::NUMBER, 'default' => 0, 'min' => 0, 'max' => 2 ) );
		$r->add_control( 'suffix', array( 'label' => __( 'After the number', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'description' => __( 'e.g. % or ★', 'afazalif' ) ) );
		$r->add_control( 'label', array( 'label' => __( 'Label', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$this->repeater( 'stats', __( 'Numbers', 'afazalif' ), $r, array(
			array( 'value' => 2024, 'from' => 2000, 'decimals' => 0, 'suffix' => '', 'label' => 'Founded' ),
			array( 'value' => 13, 'from' => 0, 'decimals' => 0, 'suffix' => '', 'label' => 'Signature scents' ),
			array( 'value' => 5, 'from' => 0, 'decimals' => 1, 'suffix' => '★', 'label' => 'Average rating' ),
			array( 'value' => 100, 'from' => 0, 'decimals' => 0, 'suffix' => '%', 'label' => 'Handmade' ),
		), '{{{ label }}}' );
		$this->section_id( 'story' );
		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$id  = (int) ( $s['image']['id'] ?? 0 );
		$tag = $this->tag( $s );
		$this->open( 'light' );
		?>
		<section class="story light"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap story__grid">
				<figure class="story__figure"<?php echo $id ? ' style="--lqip:url(' . esc_attr( afz_lqip( $id ) ) . ')"' : ''; ?>>
					<?php echo afz_img( $s['image'], '', get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: $s['caption'], 'large', array( 'loading' => 'eager', 'fetchpriority' => 'low', 'data-parallax-img' => '' ) ); // phpcs:ignore ?>
					<?php if ( $s['caption'] ) : ?><figcaption class="mono"><?php echo esc_html( $s['caption'] ); ?></figcaption><?php endif; ?>
				</figure>
				<div class="story__copy">
					<p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<<?php echo $tag; ?> class="display story__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></<?php echo $tag; ?>>
					<p class="story__lede"><?php echo esc_html( $s['lede'] ); ?></p>
					<?php echo $this->button_html( $s, 'btn', '' ); // phpcs:ignore ?>
					<?php if ( $s['stats'] ) : ?>
						<dl class="stats">
							<?php foreach ( $s['stats'] as $st ) : ?>
								<div class="stat"><dt class="display"><span data-count="<?php echo esc_attr( $st['value'] ); ?>" data-from="<?php echo esc_attr( $st['from'] ); ?>" data-decimals="<?php echo esc_attr( $st['decimals'] ); ?>"><?php echo esc_html( number_format( (float) $st['value'], (int) $st['decimals'], '.', '' ) ); ?></span><?php echo $st['suffix'] ? '<sup>' . esc_html( $st['suffix'] ) . '</sup>' : ''; ?></dt><dd class="mono"><?php echo esc_html( $st['label'] ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
