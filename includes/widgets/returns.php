<?php
defined( 'ABSPATH' ) || exit;

class AFZ_Widget_Returns extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-returns';
	}

	public function get_title() {
		return __( 'AW Return request form', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Start a return' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), 'Send us {your request}', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'intro', __( 'Intro', 'afazalif' ), 'Tell us your order number and what you would like to return. We will email you the next steps within one working day.', 'textarea' );
		$this->text( 'reasons', __( 'Reason choices', 'afazalif' ), "I changed my mind\nThe item arrived damaged\nI received the wrong item\nThe item is faulty\nSomething else", 'textarea', array( 'rows' => 5 ) );
		$this->text( 'button', __( 'Button text', 'afazalif' ), 'Send return request' );
		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$reasons = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s['reasons'] ) ) );
		$uid     = 'afz-r-' . $this->get_id();
		$user    = wp_get_current_user();
		$order   = absint( $_GET['order'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$this->open( 'light' );
		?>
		<section class="contact light returns-form" id="start-return">
			<div class="wrap contact__grid">
				<div class="contact__info">
					<p class="eyebrow eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display contact__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<p class="contact__intro"><?php echo esc_html( $s['intro'] ); ?></p>
				</div>
				<form class="contact__form" data-afz-contact data-endpoint="afz_return" data-invalid="<?php esc_attr_e( 'Please add your name, email, order number and the items you are returning.', 'afazalif' ); ?>" novalidate>
					<div class="contact__row">
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Your name', 'afazalif' ); ?> *</label><input id="<?php echo esc_attr( $uid ); ?>-name" name="name" type="text" autocomplete="name" value="<?php echo esc_attr( $user->display_name ); ?>" required></div>
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email used for the order', 'afazalif' ); ?> *</label><input id="<?php echo esc_attr( $uid ); ?>-email" name="email" type="email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" required></div>
					</div>
					<div class="contact__row">
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-order"><?php esc_html_e( 'Order number', 'afazalif' ); ?> *</label><input id="<?php echo esc_attr( $uid ); ?>-order" name="order" type="text" inputmode="numeric" value="<?php echo $order ? esc_attr( $order ) : ''; ?>" required></div>
						<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-reason"><?php esc_html_e( 'Reason for return', 'afazalif' ); ?></label>
							<select id="<?php echo esc_attr( $uid ); ?>-reason" name="reason"><?php foreach ( $reasons as $r ) : ?><option><?php echo esc_html( $r ); ?></option><?php endforeach; ?></select>
						</div>
					</div>
					<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-items"><?php esc_html_e( 'Which items are you returning?', 'afazalif' ); ?> *</label><textarea id="<?php echo esc_attr( $uid ); ?>-items" name="items" rows="3" required></textarea></div>
					<div class="field"><label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Anything else we should know? (optional)', 'afazalif' ); ?></label><textarea id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="3"></textarea></div>
					<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
					<button class="btn btn--gold magnetic" type="submit"><span class="btn__label"><?php echo esc_html( $s['button'] ); ?></span><span class="btn__icon">→</span></button>
					<p class="form-note" role="status" aria-live="polite"></p>
				</form>
			</div>
		</section>
		<?php
		$this->close();
	}
}
