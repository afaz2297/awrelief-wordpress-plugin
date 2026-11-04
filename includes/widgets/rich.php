<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Rich extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-rich';
	}

	public function get_title() {
		return __( 'AW Text section', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-text';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading (optional)', 'afazalif' ), '' );
		$this->text( 'title', __( 'Heading (optional)', 'afazalif' ), '', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->add_control( 'body', array(
			'label'   => __( 'Text', 'afazalif' ),
			'type'    => Controls_Manager::WYSIWYG,
			'default' => '<p>Write here.</p>',
		) );
		$this->add_control( 'toc', array(
			'label'       => __( 'Contents list beside the text', 'afazalif' ),
			'type'        => Controls_Manager::SWITCHER,
			'default'     => '',
			'description' => __( 'Lists every Heading 2 in the text, for long pages like Terms or Privacy.', 'afazalif' ),
		) );
		$this->text( 'updated', __( 'Last updated line (optional)', 'afazalif' ), '' );
		$this->section_id();
		$this->end_controls_section();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$body = $this->rich( $s['body'] );
		$toc  = array();
		if ( 'yes' === $s['toc'] ) {
			// give every <h2> an id so the contents list can link to it
			$body = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#si', function ( $m ) use ( &$toc ) {
				$text  = trim( wp_strip_all_tags( $m[2] ) );
				$id    = sanitize_title( $text ) ?: 'part-' . ( count( $toc ) + 1 );
				$toc[] = array( $id, $text );
				return '<h2 id="' . esc_attr( $id ) . '"' . $m[1] . '>' . $m[2] . '</h2>';
			}, $body );
		}
		$this->open( 'light' );
		?>
		<section class="prose-sec light<?php echo $toc ? ' prose-sec--toc' : ''; ?>"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap prose-sec__grid">
				<?php if ( $toc ) : ?>
					<aside class="prose-toc" aria-label="<?php esc_attr_e( 'On this page', 'afazalif' ); ?>">
						<p class="eyebrow eyebrow--dark"><?php esc_html_e( 'On this page', 'afazalif' ); ?></p>
						<ol><?php foreach ( $toc as $t ) : ?><li><a href="#<?php echo esc_attr( $t[0] ); ?>"><?php echo esc_html( $t[1] ); ?></a></li><?php endforeach; ?></ol>
					</aside>
				<?php endif; ?>
				<div class="prose-sec__main">
					<?php if ( $s['eyebrow'] ) : ?><p class="eyebrow eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p><?php endif; ?>
					<?php if ( $s['title'] ) : ?><h2 class="display prose-sec__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2><?php endif; ?>
					<div class="prose"><?php echo $body; // phpcs:ignore -- Elementor WYSIWYG ?></div>
					<?php if ( $s['updated'] ) : ?><p class="mono prose-sec__updated"><?php echo esc_html( $s['updated'] ); ?></p><?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
