<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Cards extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-cards';
	}

	public function get_title() {
		return __( 'AW Cards (values, options, steps)', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'What we stand for' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), 'The little things {matter}', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'intro', __( 'Intro (optional)', 'afazalif' ), '', 'textarea' );
		$r = new Repeater();
		$r->add_control( 'label', array( 'label' => __( 'Small label', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'title', array( 'label' => __( 'Title', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'text', array( 'label' => __( 'Text', 'afazalif' ), 'type' => Controls_Manager::TEXTAREA ) );
		$r->add_control( 'meta', array( 'label' => __( 'Highlight (price, time…)', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'note', array( 'label' => __( 'Small note', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$this->add_control( 'items', array(
			'label'       => __( 'Cards', 'afazalif' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'default'     => array( array( 'label' => '01', 'title' => 'Thoughtful products', 'text' => 'Carefully considering ingredients, quality and everyday needs.' ) ),
			'title_field' => '{{{ title }}}',
		) );
		$this->add_control( 'columns', array(
			'label'   => __( 'Cards per row', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '4',
			'options' => array( '2' => '2', '3' => '3', '4' => '4' ),
		) );
		$this->add_control( 'theme', array(
			'label'   => __( 'Background', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'light',
			'options' => array( 'dark' => __( 'Candlelit dark', 'afazalif' ), 'light' => __( 'Parchment', 'afazalif' ) ),
		) );
		$this->section_id();
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$light = 'light' === $s['theme'];
		$this->open( $light ? 'light' : '' );
		?>
		<section class="cards-sec<?php echo $light ? ' light' : ''; ?>"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap">
				<?php if ( $s['title'] || $s['eyebrow'] ) : ?>
					<div class="sec-head<?php echo $light ? ' sec-head--dark' : ''; ?>">
						<?php if ( $s['eyebrow'] ) : ?><p class="eyebrow<?php echo $light ? ' eyebrow--dark' : ''; ?>"><?php echo esc_html( $s['eyebrow'] ); ?></p><?php endif; ?>
						<?php if ( $s['title'] ) : ?><h2 class="display sec-head__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2><?php endif; ?>
						<?php if ( $s['intro'] ) : ?><p class="cards-sec__intro"><?php echo esc_html( $s['intro'] ); ?></p><?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="cards-grid cards-grid--<?php echo esc_attr( $s['columns'] ); ?>">
					<?php foreach ( (array) $s['items'] as $it ) : ?>
						<article class="info-card">
							<?php if ( $it['label'] ) : ?><span class="mono info-card__label"><?php echo esc_html( $it['label'] ); ?></span><?php endif; ?>
							<h3 class="display info-card__title"><?php echo esc_html( $it['title'] ); ?></h3>
							<?php if ( $it['text'] ) : ?><p class="info-card__text"><?php echo nl2br( esc_html( $it['text'] ) ); ?></p><?php endif; ?>
							<?php if ( $it['meta'] ) : ?><p class="price info-card__meta"><?php echo esc_html( $it['meta'] ); ?></p><?php endif; ?>
							<?php if ( $it['note'] ) : ?><p class="mono info-card__note"><?php echo esc_html( $it['note'] ); ?></p><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
