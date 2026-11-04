<?php
defined( 'ABSPATH' ) || exit;

class AFZ_Widget_Scent_Library extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-scent-library';
	}

	public function get_title() {
		return __( 'AW Scent library', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-post-slider';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->add_control( 'note', array(
			'type' => \Elementor\Controls_Manager::RAW_HTML,
			'raw'  => sprintf( '<p style="line-height:1.5">%s <a href="%s" target="_blank">%s</a></p>', esc_html__( 'Scents, notes, colours and images come from', 'afazalif' ), esc_url( admin_url( 'edit-tags.php?taxonomy=afz_scent&post_type=product' ) ), esc_html__( 'Products → Scents.', 'afazalif' ) ),
		) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'The scent library' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), "{count} ways\nto {feel at peace}", 'textarea', array( 'description' => __( '{count} becomes the number of scents written as a word, e.g. Thirteen.', 'afazalif' ) . ' ' . $this->gold_help() ) );
		$this->text( 'aside', __( 'Text', 'afazalif' ), 'Can\'t smell it through the screen? Every scent is built in three layers. Read the notes, find the mood, and choose the one that sounds like home.', 'textarea' );
		$this->section_id( 'library' );
		$this->end_controls_section();
	}

	private static function number_word( $n ) {
		$w = array( '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen', 'Twenty' );
		return $w[ $n ] ?? (string) $n;
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$scents = AFZ_Scents::all();
		$n      = count( $scents );
		$title  = str_replace( '{count}', self::number_word( $n ), (string) $s['title'] );
		$this->open( 'light' );
		?>
		<section class="library light"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="library__pin">
				<div class="library__head wrap">
					<div>
						<p class="eyebrow eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p>
						<h2 class="display library__title"><?php echo $this->split_lines( $title ); // phpcs:ignore ?></h2>
					</div>
					<div class="library__aside">
						<p><?php echo esc_html( $s['aside'] ); ?></p>
						<div class="library__progress"><span class="mono library__count"><b>01</b> / <?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span><span class="library__bar"><i></i></span></div>
					</div>
				</div>
				<div class="library__viewport" data-cursor="Drag">
					<div class="library__track">
						<?php foreach ( $scents as $i => $sc ) : ?>
							<article class="scent" style="--a1:<?php echo esc_attr( $sc['aura'] ); ?>;--a2:<?php echo esc_attr( $sc['aura2'] ); ?>" data-cursor="Discover">
								<div class="scent__visual">
									<span class="scent__aura"></span>
									<?php echo $sc['image'] ? afz_img( (int) $sc['image'], 'scent__img', sprintf( __( '%s scent notes', 'afazalif' ), $sc['name'] ), 'medium_large', array( 'loading' => 'eager' ) ) : ''; // phpcs:ignore ?>
									<span class="mono scent__num">N° <?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<?php if ( $sc['family'] ) : ?><span class="mono scent__fam"><?php echo esc_html( $sc['family'] ); ?></span><?php endif; ?>
								</div>
								<div class="scent__body">
									<h3 class="display scent__name"><a href="<?php echo esc_url( $sc['url'] ); ?>"><?php echo esc_html( $sc['name'] ); ?></a></h3>
									<dl class="scent__notes">
										<?php foreach ( array( 'top' => __( 'Top', 'afazalif' ), 'heart' => __( 'Heart', 'afazalif' ), 'base' => __( 'Base', 'afazalif' ) ) as $k => $label ) : ?>
											<?php if ( $sc[ $k ] ) : ?><div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $sc[ $k ] ); ?></dd></div><?php endif; ?>
										<?php endforeach; ?>
									</dl>
									<div class="scent__formats">
										<?php foreach ( AFZ_Scents::formats( $sc['id'] ) as $f ) : ?>
											<a href="<?php echo esc_url( $f['url'] ); ?>" class="chip"><?php echo esc_html( $f['label'] ); ?><b><?php echo esc_html( afz_money( $f['price'] ) ); ?></b></a>
										<?php endforeach; ?>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
