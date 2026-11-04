<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Contact extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-contact';
	}

	public function get_title() {
		return __( 'AW Contact form', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Write to us' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), 'We read {every message}', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'intro', __( 'Intro', 'afazalif' ), 'Questions about a scent, an order or a gift? Send us a note and we\'ll reply within one working day.', 'textarea' );
		$this->text( 'hours', __( 'Reply time line', 'afazalif' ), 'Replies Monday to Friday, within one working day' );
		$this->text( 'subjects', __( 'Subject choices', 'afazalif' ), "A question about an order\nProducts & scents\nGifts & gift cards\nWholesale & collaborations\nSomething else", 'textarea', array( 'rows' => 5, 'description' => __( 'One per line.', 'afazalif' ) ) );
		$this->text( 'button', __( 'Button text', 'afazalif' ), 'Send message' );
		$this->add_control( 'note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => '<p style="line-height:1.5">' . esc_html__( 'Email, WhatsApp and company number come from AWRELIEF → Site settings. Messages are saved in AWRELIEF → Messages and emailed to the store address.', 'afazalif' ) . '</p>' ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$subjects = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s['subjects'] ) ) );
		$socials  = afz_socials();
		$email    = afz_opt( 'email' );
		$uid      = 'afz-c-' . $this->get_id();
		$this->open( 'light' );
		?>
		<section class="contact light">
			<div class="wrap contact__grid">
				<div class="contact__info">
					<p class="eyebrow eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display contact__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<p class="contact__intro"><?php echo esc_html( $s['intro'] ); ?></p>
					<ul class="contact__lines">
						<?php if ( $email ) : ?><li><span class="mono"><?php esc_html_e( 'Email', 'afazalif' ); ?></span><a class="display" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
						<?php if ( isset( $socials['whatsapp'] ) ) : ?><li><span class="mono">WhatsApp</span><a class="display" href="<?php echo esc_url( $socials['whatsapp']['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Message us', 'afazalif' ); ?></a></li><?php endif; ?>
					</ul>
					<?php if ( $s['hours'] ) : ?><p class="mono contact__hours"><?php echo esc_html( $s['hours'] ); ?></p><?php endif; ?>
					<div class="footer__socials contact__socials">
						<?php foreach ( $socials as $key => $sc ) : ?>
							<a href="<?php echo esc_url( $sc['url'] ); ?>" class="social" aria-label="<?php echo esc_attr( $sc['label'] ); ?>" target="_blank" rel="noopener"><?php echo afz_icon( $key ); // phpcs:ignore ?></a>
						<?php endforeach; ?>
					</div>
					<?php if ( afz_opt( 'company_no' ) ) : ?>
						<p class="mono contact__company"><?php echo esc_html( sprintf( __( '%1$s · Registered in England & Wales · Company No. %2$s', 'afazalif' ), afz_opt( 'company_name' ), afz_opt( 'company_no' ) ) ); ?></p>
					<?php endif; ?>
				</div>
				<form class="contact__form" data-afz-contact novalidate>
					<div class="contact__row">
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Your name', 'afazalif' ); ?> *</label><input id="<?php echo esc_attr( $uid ); ?>-name" name="name" type="text" autocomplete="name" required></div>
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'afazalif' ); ?> *</label><input id="<?php echo esc_attr( $uid ); ?>-email" name="email" type="email" autocomplete="email" required></div>
					</div>
					<div class="contact__row">
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-subject"><?php esc_html_e( 'About', 'afazalif' ); ?></label>
							<select id="<?php echo esc_attr( $uid ); ?>-subject" name="subject"><?php foreach ( $subjects as $sub ) : ?><option><?php echo esc_html( $sub ); ?></option><?php endforeach; ?></select>
						</div>
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-order"><?php esc_html_e( 'Order number (optional)', 'afazalif' ); ?></label><input id="<?php echo esc_attr( $uid ); ?>-order" name="order" type="text" inputmode="numeric"></div>
					</div>
					<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Message', 'afazalif' ); ?> *</label><textarea id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="6" required></textarea></div>
					<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
					<button class="btn btn--gold magnetic" type="submit"><span class="btn__label"><?php echo esc_html( $s['button'] ); ?></span><span class="btn__icon">→</span></button>
					<p class="form-note" role="status" aria-live="polite"></p>
					<p class="mono contact__privacy"><?php echo wp_kses_post( sprintf( __( 'We only use your details to reply. See our <a href="%s">privacy policy</a>.', 'afazalif' ), esc_url( get_privacy_policy_url() ) ) ); ?></p>
				</form>
			</div>
		</section>
		<?php
		$this->close();
	}
}
