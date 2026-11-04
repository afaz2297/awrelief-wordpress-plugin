<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Page_Hero extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-page-hero';
	}

	public function get_title() {
		return __( 'AW Page header', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-header';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Our story' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "Created with purpose.\n{Inspired by travel.}", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'lede', __( 'Intro', 'afazalif' ), '', 'textarea' );
		$this->media( 'image', __( 'Image (optional)', 'afazalif' ), '', 'image', array( 'description' => __( 'Shown on the right. Transparent cut-outs and photos both work.', 'afazalif' ) ) );
		$this->add_control( 'image_style', array(
			'label'   => __( 'Image style', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'photo',
			'options' => array( 'photo' => __( 'Photo in a rounded frame', 'afazalif' ), 'cutout' => __( 'Floating cut-out', 'afazalif' ) ),
		) );
		$this->add_control( 'hue', array( 'label' => __( 'Glow colour', 'afazalif' ), 'type' => Controls_Manager::COLOR, 'default' => '#c9a04a' ) );
		$this->add_control( 'size', array(
			'label'   => __( 'Height', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'normal',
			'options' => array( 'normal' => __( 'Normal', 'afazalif' ), 'tall' => __( 'Tall', 'afazalif' ), 'compact' => __( 'Compact', 'afazalif' ) ),
		) );
		$this->add_control( 'crumbs', array( 'label' => __( 'Breadcrumb', 'afazalif' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$img   = ! empty( $s['image']['id'] ) || ! empty( $s['image']['url'] );
		$title = get_the_title();
		$this->open();
		?>
		<section class="page-hero page-hero--<?php echo esc_attr( $s['size'] ); ?><?php echo $img ? ' page-hero--has-img page-hero--' . esc_attr( $s['image_style'] ) : ''; ?>" style="--hue:<?php echo esc_attr( $s['hue'] ?: '#c9a04a' ); ?>">
			<div class="page-hero__aura" aria-hidden="true"></div>
			<div class="wrap page-hero__grid">
				<div class="page-hero__inner">
					<?php if ( 'yes' === $s['crumbs'] ) : ?>
						<nav class="crumbs mono" aria-label="<?php esc_attr_e( 'Breadcrumb', 'afazalif' ); ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'afazalif' ); ?></a><span class="crumbs__sep">/</span><span><?php echo esc_html( $title ); ?></span></nav>
					<?php endif; ?>
					<?php if ( $s['eyebrow'] ) : ?><p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p><?php endif; ?>
					<h1 class="display page-hero__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h1>
					<?php if ( $s['lede'] ) : ?><p class="page-hero__lede"><?php echo esc_html( $s['lede'] ); ?></p><?php endif; ?>
				</div>
				<?php if ( $img ) : ?>
					<div class="page-hero__media"><?php echo afz_img( $s['image'], '', '', 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?></div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		$this->close();
	}
}
