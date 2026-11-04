<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class AFZ_Widget_Products extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-products';
	}

	public function get_title() {
		return __( 'AW Product cards', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Most loved' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), 'Bestsellers, {five stars} each', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'link_text', __( 'Link text', 'afazalif' ), 'Shop all' );
		$this->add_control( 'products', array(
			'label'       => __( 'Products', 'afazalif' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => self::product_options(),
			'default'     => array_values( array_filter( array( self::product_id_by_title( 'Insect Repellent 100ml' ), self::product_id_by_title( 'Heaven Candle 220g' ), self::product_id_by_title( 'Gold Nordic Candle Holder' ), self::product_id_by_title( 'Apple Reed Diffuser 200ml' ) ) ) ),
			'description' => __( 'Pick and order the products to show. Prices, ratings and images come from the shop.', 'afazalif' ),
		) );
		$this->add_control( 'plain', array(
			'label'       => __( 'Plain background for', 'afazalif' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => self::product_options(),
			'default'     => array_values( array_filter( array( self::product_id_by_title( 'Insect Repellent 100ml' ) ) ) ),
			'description' => __( 'These cards skip the coloured glow and ingredient image.', 'afazalif' ),
		) );
		$this->section_id( 'bestsellers' );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$ids   = array_map( 'intval', (array) $s['products'] );
		$plain = array_map( 'intval', (array) $s['plain'] );
		$this->open( 'light' );
		?>
		<section class="best light"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap">
				<div class="sec-head sec-head--dark">
					<p class="eyebrow eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display sec-head__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<?php if ( $s['link_text'] ) : ?><a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="link-arrow hide-sm" data-hover><?php echo esc_html( $s['link_text'] ); ?> <span>→</span></a><?php endif; ?>
				</div>
				<div class="best__grid">
					<?php
					foreach ( $ids as $id ) :
						$p = wc_get_product( $id );
						if ( ! $p || ! $p->is_visible() ) {
							continue;
						}
						$sc       = in_array( $id, $plain, true ) ? null : AFZ_Scents::of_product( $id );
						$aura     = in_array( $id, $plain, true ) ? 'transparent' : ( $sc ? $sc['aura'] : '#c9a04a' );
						$price    = afz_money( wc_get_price_to_display( $p ) );
						list( , $count ) = self::rating( $p );
						// a short line under the name: the product's own note, or the first note of each scent layer
						$notes = trim( (string) $p->get_meta( '_afz_card_note' ) );
						if ( '' === $notes && $sc ) {
							$notes = implode( ' · ', array_filter( array( strtok( (string) $sc['top'], ',' ), strtok( (string) $sc['heart'], ',' ), strtok( (string) $sc['base'], ',' ) ) ) );
						}
						$badge    = AFZ_Scents::format_label( $p );
						$badge    = 'Diffuser' === $badge ? 'Reed Diffuser' : $badge;
						?>
						<article class="prod">
							<a href="<?php echo esc_url( $p->get_permalink() ); ?>" class="prod__media" style="--a1:<?php echo esc_attr( $aura ); ?>" data-cursor="View">
								<?php if ( $sc && $sc['image'] ) : ?><?php echo afz_img( (int) $sc['image'], 'prod__notesimg', '', 'medium_large' ); // phpcs:ignore ?><?php endif; ?>
								<?php echo afz_img( (int) $p->get_image_id(), 'prod__pack', $p->get_name(), 'woocommerce_single' ); // phpcs:ignore ?>
								<span class="mono prod__badge"><?php echo esc_html( $badge ); ?></span>
								<?php if ( $p->is_purchasable() && $p->is_in_stock() ) : ?>
									<button class="prod__add" data-add="<?php echo esc_attr( $id ); ?>" type="button"><span><?php esc_html_e( 'Add to basket', 'afazalif' ); ?></span><span><?php echo esc_html( $price ); ?></span></button>
								<?php endif; ?>
							</a>
							<div class="prod__info">
								<h3 class="display prod__name"><a href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo esc_html( $p->get_name() ); ?></a></h3>
								<span class="prod__price"><?php echo esc_html( $price ); ?></span>
								<?php if ( $notes ) : ?><span class="mono prod__notes"><?php echo esc_html( $notes ); ?></span><?php endif; ?>
								<?php if ( $count ) : ?><span class="prod__rating"><span class="stars" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'afazalif' ), $p->get_average_rating() ) ); ?>">★★★★★</span><span class="mono"><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $count, 'afazalif' ), $count ) ); ?></span></span><?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
