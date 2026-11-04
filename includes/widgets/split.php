<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Split extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-split';
	}

	public function get_title() {
		return __( 'AW Image + text', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-image-box';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->media( 'image', __( 'Image', 'afazalif' ), 'life-reading.webp' );
		$this->text( 'caption', __( 'Image caption (optional)', 'afazalif' ), '' );
		$this->text( 'number', __( 'Chapter number (optional)', 'afazalif' ), '', 'text', array( 'description' => __( 'e.g. 01. Shown large and faint behind the text.', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Where it all began' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "A personal need,\n{a better answer}", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->add_control( 'body', array( 'label' => __( 'Text', 'afazalif' ), 'type' => Controls_Manager::WYSIWYG, 'default' => '<p>Write here.</p>' ) );
		$this->text( 'quote', __( 'Pull quote (optional)', 'afazalif' ), '', 'textarea' );
		$this->button_controls( 'btn', __( 'Button (optional)', 'afazalif' ), '', '', 'ghost' );
		$this->add_control( 'side', array(
			'label'   => __( 'Image side', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'left',
			'options' => array( 'left' => __( 'Left', 'afazalif' ), 'right' => __( 'Right', 'afazalif' ) ),
		) );
		$this->add_control( 'theme', array(
			'label'   => __( 'Background', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'dark',
			'options' => array( 'dark' => __( 'Candlelit dark', 'afazalif' ), 'light' => __( 'Parchment', 'afazalif' ) ),
		) );
		$this->section_id();
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$light = 'light' === $s['theme'];
		$id    = (int) ( $s['image']['id'] ?? 0 );
		$this->open( $light ? 'light' : '' );
		?>
		<section class="split split--<?php echo esc_attr( $s['side'] ); ?><?php echo $light ? ' light' : ''; ?>"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap split__grid">
				<figure class="split__figure story__figure"<?php echo $id ? ' style="--lqip:url(' . esc_attr( afz_lqip( $id ) ) . ')"' : ''; ?>>
					<?php echo afz_img( $s['image'], '', get_post_meta( $id, '_wp_attachment_image_alt', true ), 'large', array( 'data-parallax-img' => '' ) ); // phpcs:ignore ?>
					<?php if ( $s['caption'] ) : ?><figcaption class="mono"><?php echo esc_html( $s['caption'] ); ?></figcaption><?php endif; ?>
				</figure>
				<div class="split__copy">
					<?php if ( $s['number'] ) : ?><span class="display split__num" aria-hidden="true"><?php echo esc_html( $s['number'] ); ?></span><?php endif; ?>
					<p class="eyebrow<?php echo $light ? ' eyebrow--dark' : ''; ?>"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display split__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<div class="prose split__body"><?php echo $this->rich( $s['body'] ); // phpcs:ignore ?></div>
					<?php if ( $s['quote'] ) : ?><blockquote class="repel__quote split__quote"><p>“<?php echo esc_html( $s['quote'] ); ?>”</p></blockquote><?php endif; ?>
					<?php echo $this->button_html( $s, 'btn', '' ); // phpcs:ignore ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
