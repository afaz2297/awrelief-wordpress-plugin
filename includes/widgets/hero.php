<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Hero extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-hero';
	}

	public function get_title() {
		return __( 'AW Hero (video)', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-slider-video';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Hand-poured in small batches' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "Light a moment\nof {peace.}", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'lede', __( 'Intro', 'afazalif' ), 'Candles, reed diffusers and refills, made with love to slow the room down.', 'textarea' );
		$this->button_controls( 'btn1', __( 'First button', 'afazalif' ), 'Shop the collection', '#bestsellers', 'gold' );
		$this->button_controls( 'btn2', __( 'Second button', 'afazalif' ), 'Find your scent', '#library', 'ghost' );
		$this->end_controls_section();

		$this->start_controls_section( 'media_s', array( 'label' => __( 'Video', 'afazalif' ) ) );
		$this->media( 'video', __( 'Video (desktop, 1080p MP4)', 'afazalif' ), 'hero-1080.mp4', 'video' );
		$this->media( 'video_m', __( 'Video (phones, 720p MP4)', 'afazalif' ), 'hero-720.mp4', 'video', array( 'description' => __( 'Optional smaller file for phones.', 'afazalif' ) ) );
		$this->media( 'poster', __( 'Poster image', 'afazalif' ), 'hero-poster.webp', 'image', array( 'description' => __( 'Shown while the video loads.', 'afazalif' ) ) );
		$this->add_control( 'smoke', array( 'label' => __( 'Candle smoke over the video', 'afazalif' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'rail_s', array( 'label' => __( 'Bottom line', 'afazalif' ) ) );
		$this->text( 'rail_left', __( 'Left text', 'afazalif' ), 'N° 01 — Heaven Candle 220g' );
		$this->text( 'rail_right', __( 'Right text', 'afazalif' ), '★ 5.0 from our customers' );
		$this->section_id( 'hero' );
		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$poster = afz_media_url( $s['poster'] ?? '', 'full' );
		$video  = afz_media_url( $s['video'] ?? '' );
		$mobile = afz_media_url( $s['video_m'] ?? '' );
		$this->open();
		?>
		<section class="hero"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="hero__media" data-cursor="Scroll">
				<?php if ( $video ) : ?>
					<video class="hero__video" autoplay muted loop playsinline preload="auto"<?php echo $poster ? ' poster="' . esc_url( $poster ) . '"' : ''; ?>>
						<?php if ( $mobile ) : ?><source src="<?php echo esc_url( $mobile ); ?>" type="video/mp4" media="(max-width: 900px)"><?php endif; ?>
						<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
					</video>
				<?php elseif ( $poster ) : ?>
					<img class="hero__video" src="<?php echo esc_url( $poster ); ?>" alt="" fetchpriority="high">
				<?php endif; ?>
				<div class="hero__shade"></div>
				<?php if ( 'yes' === ( $s['smoke'] ?? '' ) && afz_on( 'smoke' ) ) : ?><canvas class="smoke" data-smoke data-smoke-strength="0.55"></canvas><?php endif; ?>
				<div class="hero__glow" aria-hidden="true"></div>
			</div>
			<div class="hero__content wrap">
				<?php if ( $s['eyebrow'] ) : ?><p class="eyebrow hero__eyebrow"><span class="dot"></span><?php echo esc_html( $s['eyebrow'] ); ?></p><?php endif; ?>
				<h1 class="hero__title display" data-split="chars"><?php echo afz_lines( $s['title'] ); // phpcs:ignore ?></h1>
				<div class="hero__foot">
					<?php if ( $s['lede'] ) : ?><p class="hero__lede"><?php echo esc_html( $s['lede'] ); ?></p><?php endif; ?>
					<div class="hero__ctas"><?php echo $this->button_html( $s, 'btn1' ) . $this->button_html( $s, 'btn2' ); // phpcs:ignore ?></div>
				</div>
			</div>
			<div class="hero__rail wrap">
				<span class="mono"><?php echo esc_html( $s['rail_left'] ); ?></span>
				<span class="hero__scroll mono"><i></i><?php esc_html_e( 'Scroll', 'afazalif' ); ?></span>
				<span class="mono"><?php echo esc_html( $s['rail_right'] ); ?></span>
			</div>
		</section>
		<?php
		$this->close();
	}
}
