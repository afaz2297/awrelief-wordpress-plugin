<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Newsletter extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-newsletter';
	}

	public function get_title() {
		return __( 'AW Newsletter sign-up', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-mail';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'The AWRELIEF circle' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "{15% off} your\nfirst order", 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'lede', __( 'Text', 'afazalif' ), 'Join the circle for your welcome code, new scent launches and members-only offers. Gentle emails, never too many.', 'textarea' );
		$this->text( 'button', __( 'Button text', 'afazalif' ), 'Join' );
		$this->add_control( 'smoke', array( 'label' => __( 'Smoke and candle glow', 'afazalif' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => '<p style="line-height:1.5">' . esc_html__( 'Sign-ups go to MailerLite and AWRELIEF → Subscribers. The welcome code is set in AWRELIEF → Site settings → Welcome offer.', 'afazalif' ) . '</p>' ) );
		$this->section_id( 'newsletter' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$this->open();
		?>
		<section class="news"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<?php if ( 'yes' === $s['smoke'] ) : ?>
				<?php if ( afz_on( 'smoke' ) ) : ?><canvas class="smoke smoke--news" data-smoke data-smoke-strength="0.5"></canvas><?php endif; ?>
				<div class="news__flame" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="wrap news__inner">
				<p class="eyebrow center"><?php echo esc_html( $s['eyebrow'] ); ?></p>
				<h2 class="display news__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
				<p class="news__lede"><?php echo esc_html( $s['lede'] ); ?></p>
				<?php AFZ_Forms::newsletter_form( 'newsletter', $s['button'] ); ?>
			</div>
		</section>
		<?php
		$this->close();
	}
}
