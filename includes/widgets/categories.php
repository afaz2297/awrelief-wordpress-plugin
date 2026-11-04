<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Categories extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-categories';
	}

	public function get_title() {
		return __( 'AW Shop by range', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	private static function cat_options() {
		$opts = array( '' => __( '— Custom link —', 'afazalif' ) );
		foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
			$opts[ (string) $t->term_id ] = $t->name;
		}
		return $opts;
	}

	private static function cat_id( $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		return $t ? (string) $t->term_id : '';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Shop by range' );
		$this->text( 'title', __( 'Headline', 'afazalif' ), 'Find your {ritual}', 'textarea', array( 'description' => $this->gold_help() ) );
		$this->text( 'link_text', __( 'Link text', 'afazalif' ), 'View all {count} products', 'text', array( 'description' => __( '{count} becomes the number of products in the shop.', 'afazalif' ) ) );

		$r = new Repeater();
		$r->add_control( 'cat', array( 'label' => __( 'Product category', 'afazalif' ), 'type' => Controls_Manager::SELECT, 'options' => self::cat_options(), 'description' => __( 'Name, link, number of scents and starting price come from the category.', 'afazalif' ) ) );
		$r->add_control( 'name', array( 'label' => __( 'Name (optional)', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'meta', array( 'label' => __( 'Small line (optional)', 'afazalif' ), 'type' => Controls_Manager::TEXT, 'description' => __( 'Leave empty for "08 scents · £24.99".', 'afazalif' ) ) );
		$r->add_control( 'link', array( 'label' => __( 'Custom link', 'afazalif' ), 'type' => Controls_Manager::URL ) );
		$r->add_control( 'image', array( 'label' => __( 'Product image (transparent)', 'afazalif' ), 'type' => Controls_Manager::MEDIA ) );
		$r->add_control( 'aura', array( 'label' => __( 'Glow colour', 'afazalif' ), 'type' => Controls_Manager::COLOR, 'default' => '#c58a4d' ) );
		$this->repeater( 'cards', __( 'Cards', 'afazalif' ), $r, array(
			array( 'cat' => self::cat_id( 'candles' ), 'name' => 'Candles', 'image' => afz_seed_media( 'candle.webp' ), 'aura' => '#c58a4d' ),
			array( 'cat' => self::cat_id( 'reed-diffusers' ), 'name' => 'Reed Diffusers', 'image' => afz_seed_media( 'diffuser.webp' ), 'aura' => '#b9a6d9' ),
			array( 'cat' => self::cat_id( 'diffuser-refill' ), 'name' => 'Refills', 'image' => afz_seed_media( 'refill.webp' ), 'aura' => '#8fa77a' ),
			array( 'cat' => self::cat_id( 'repellent' ), 'name' => 'Insect Repellent', 'meta' => '100ml · £19.99', 'image' => afz_seed_media( 'repellent.webp' ), 'aura' => '#d8b250' ),
		), '{{{ name }}}' );
		$this->section_id( 'categories' );
		$this->end_controls_section();
	}

	/** Number of products and lowest price in a category (or a name search when no category is set). */
	private static function summary( $cat_id, $name ) {
		$args = array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' );
		if ( $cat_id ) {
			// count the category's own products only: refills sit in a sub-category of Reed Diffusers
			$children          = get_term_children( (int) $cat_id, 'product_cat' );
			$args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'terms' => (int) $cat_id, 'include_children' => false ) ); // phpcs:ignore
			if ( $children && ! is_wp_error( $children ) ) {
				$args['tax_query'][] = array( 'taxonomy' => 'product_cat', 'terms' => $children, 'operator' => 'NOT IN' );
			}
		} elseif ( $name ) {
			$args['s'] = rtrim( $name, 's' );
		} else {
			return array( 0, 0 );
		}
		$ids = get_posts( $args );
		$min = 0;
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			$v = $p ? (float) wc_get_price_to_display( $p ) : 0;
			$min = $v && ( ! $min || $v < $min ) ? $v : $min;
		}
		return array( count( $ids ), $min );
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$total = (int) wp_count_posts( 'product' )->publish;
		$shop  = get_permalink( wc_get_page_id( 'shop' ) );
		$this->open();
		?>
		<section class="cats"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap">
				<div class="sec-head">
					<p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<h2 class="display sec-head__title" data-split="lines"><?php echo $this->split_lines( $s['title'] ); // phpcs:ignore ?></h2>
					<?php if ( $s['link_text'] ) : ?><a href="<?php echo esc_url( $shop ); ?>" class="link-arrow hide-sm" data-hover><?php echo esc_html( str_replace( '{count}', (string) $total, $s['link_text'] ) ); ?> <span>→</span></a><?php endif; ?>
				</div>
				<div class="cats__grid">
					<?php
					foreach ( $s['cards'] as $c ) :
						$term = $c['cat'] ? get_term( (int) $c['cat'], 'product_cat' ) : null;
						$name = $c['name'] ? $c['name'] : ( $term && ! is_wp_error( $term ) ? $term->name : '' );
						$url  = ! empty( $c['link']['url'] ) ? $c['link']['url'] : ( $term && ! is_wp_error( $term ) ? get_term_link( $term ) : $shop );
						$meta = $c['meta'];
						if ( '' === trim( (string) $meta ) ) {
							list( $n, $min ) = self::summary( $c['cat'], $name );
							$meta            = $n ? sprintf( '%02d %s · %s', $n, _n( 'scent', 'scents', $n, 'afazalif' ), afz_money( $min ) ) : '';
						}
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="cat" style="--aura:<?php echo esc_attr( $c['aura'] ?: '#c58a4d' ); ?>" data-cursor="Shop" data-tilt>
							<span class="cat__aura"></span>
							<?php echo afz_img( $c['image'], 'cat__img', $name, 'medium_large' ); // phpcs:ignore ?>
							<span class="cat__meta"><span class="mono"><?php echo esc_html( $meta ); ?></span><span class="display cat__name"><?php echo esc_html( $name ); ?></span><span class="cat__go">→</span></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
