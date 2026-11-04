<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Faq extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-faq';
	}

	public function get_title() {
		return __( 'AW FAQ', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Questions', 'afazalif' ) ) );
		$r = new Repeater();
		$r->add_control( 'group', array( 'label' => __( 'Topic', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'default' => 'Orders & delivery', 'description' => __( 'Questions with the same topic are listed together.', 'afazalif' ) ) );
		$r->add_control( 'q', array( 'label' => __( 'Question', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'a', array( 'label' => __( 'Answer', 'afazalif' ), 'type' => Controls_Manager::WYSIWYG ) );
		$this->add_control( 'items', array(
			'label'       => __( 'Questions', 'afazalif' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'default'     => array( array( 'group' => 'Orders & delivery', 'q' => 'How long does delivery take?', 'a' => '<p>UK Standard Delivery takes up to 4 working days.</p>' ) ),
			'title_field' => '{{{ q }}}',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'help', array( 'label' => __( 'Help card', 'afazalif' ) ) );
		$this->text( 'help_title', __( 'Title', 'afazalif' ), 'Still {wondering?}' );
		$this->text( 'help_text', __( 'Text', 'afazalif' ), 'We reply to every message within one working day.', 'textarea' );
		$this->button_controls( 'btn', __( 'Button', 'afazalif' ), 'Contact us', '/contact-us/', 'gold' );
		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$groups = array();
		foreach ( (array) $s['items'] as $it ) {
			$groups[ trim( (string) $it['group'] ) ?: __( 'Questions', 'afazalif' ) ][] = $it;
		}
		// FAQ structured data so search engines can show the answers
		$schema = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
		$this->open( 'light' );
		?>
		<section class="faq light">
			<div class="wrap faq__grid">
				<nav class="faq__nav" aria-label="<?php esc_attr_e( 'Topics', 'afazalif' ); ?>">
					<p class="eyebrow eyebrow--dark"><?php esc_html_e( 'Topics', 'afazalif' ); ?></p>
					<ol>
						<?php foreach ( array_keys( $groups ) as $g ) : ?>
							<li><a href="#<?php echo esc_attr( 'faq-' . sanitize_title( $g ) ); ?>"><?php echo esc_html( $g ); ?> <span class="mono"><?php echo esc_html( str_pad( (string) count( $groups[ $g ] ), 2, '0', STR_PAD_LEFT ) ); ?></span></a></li>
						<?php endforeach; ?>
					</ol>
					<div class="faq__help">
						<p class="display faq__help-title"><?php echo afz_hl( $s['help_title'] ); // phpcs:ignore ?></p>
						<p><?php echo esc_html( $s['help_text'] ); ?></p>
						<?php echo $this->button_html( $s, 'btn' ); // phpcs:ignore ?>
					</div>
				</nav>
				<div class="faq__list">
					<?php foreach ( $groups as $g => $items ) : ?>
						<div class="faq__group" id="<?php echo esc_attr( 'faq-' . sanitize_title( $g ) ); ?>">
							<h2 class="display faq__group-title"><?php echo esc_html( $g ); ?></h2>
							<?php foreach ( $items as $it ) : ?>
								<?php
								$answer                 = $this->rich( $it['a'] );
								$schema['mainEntity'][] = array( '@type' => 'Question', 'name' => wp_strip_all_tags( $it['q'] ), 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $answer ) ) );
								?>
								<details class="faq__item">
									<summary><span><?php echo esc_html( $it['q'] ); ?></span><i aria-hidden="true"></i></summary>
									<div class="faq__answer prose"><?php echo $answer; // phpcs:ignore -- Elementor WYSIWYG ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<script type="application/ld+json"><?php echo wp_json_encode( $schema ); ?></script>
		</section>
		<?php
		$this->close();
	}
}
