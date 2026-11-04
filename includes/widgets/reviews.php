<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class AFZ_Widget_Reviews extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-reviews';
	}

	public function get_title() {
		return __( 'AW Reviews slider', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-testimonial-carousel';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'eyebrow', __( 'Small heading', 'afazalif' ), 'Kind words' );
		$this->text( 'score_text', __( 'Score line', 'afazalif' ), 'Every review, five stars' );
		$this->add_control( 'source', array(
			'label'   => __( 'Reviews', 'afazalif' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'shop',
			'options' => array( 'shop' => __( 'Latest 5-star reviews from the shop', 'afazalif' ), 'manual' => __( 'Hand-picked (below)', 'afazalif' ) ),
		) );
		$this->add_control( 'limit', array( 'label' => __( 'How many', 'afazalif' ), 'type' => Controls_Manager::NUMBER, 'default' => 6, 'min' => 2, 'max' => 12, 'condition' => array( 'source' => 'shop' ) ) );
		$this->add_control( 'min_words', array( 'label' => __( 'Skip reviews shorter than (words)', 'afazalif' ), 'type' => Controls_Manager::NUMBER, 'default' => 8, 'condition' => array( 'source' => 'shop' ) ) );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => __( 'Review', 'afazalif' ), 'type' => Controls_Manager::TEXTAREA ) );
		$r->add_control( 'name', array( 'label' => __( 'Name', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'product', array( 'label' => __( 'Product', 'afazalif' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'verified', array( 'label' => __( 'Verified buyer', 'afazalif' ), 'type' => Controls_Manager::SWITCHER ) );
		$this->add_control( 'items', array(
			'label'       => __( 'Hand-picked reviews', 'afazalif' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'default'     => array(),
			'title_field' => '{{{ name }}}',
			'condition'   => array( 'source' => 'manual' ),
		) );
		$this->section_id( 'reviews' );
		$this->end_controls_section();
	}

	/** Latest approved 5-star product reviews with enough to say. */
	private static function shop_reviews( $limit, $min_words ) {
		$out      = array();
		$comments = get_comments( array( 'type' => 'review', 'status' => 'approve', 'number' => 60, 'meta_query' => array( array( 'key' => 'rating', 'value' => 5 ) ) ) ); // phpcs:ignore
		$seen     = array();
		foreach ( $comments as $c ) {
			$text = trim( wp_strip_all_tags( $c->comment_content ) );
			$key  = md5( strtolower( $text ) );
			if ( isset( $seen[ $key ] ) || str_word_count( $text ) < $min_words ) {
				continue; // the live shop has the same review posted twice on one product
			}
			$seen[ $key ] = 1;
			$out[]        = array(
				'text'     => wp_trim_words( $text, 34, '…' ),
				'name'     => $c->comment_author,
				'product'  => get_the_title( $c->comment_post_ID ),
				'verified' => (bool) get_comment_meta( $c->comment_ID, 'verified', true ),
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = 'manual' === $s['source'] ? (array) $s['items'] : self::shop_reviews( (int) $s['limit'], (int) $s['min_words'] );
		if ( ! $items ) {
			return;
		}
		$avg = 5.0;
		if ( 'shop' === $s['source'] ) {
			global $wpdb;
			$avg = (float) $wpdb->get_var( "SELECT AVG(meta_value) FROM $wpdb->commentmeta cm JOIN $wpdb->comments c ON c.comment_ID = cm.comment_id WHERE cm.meta_key = 'rating' AND c.comment_approved = '1' AND c.comment_type = 'review'" ); // phpcs:ignore
			$avg = $avg ? round( $avg, 1 ) : 5.0;
		}
		$this->open();
		?>
		<section class="reviews"<?php echo $this->id_attr( $s ); // phpcs:ignore ?>>
			<div class="wrap">
				<div class="reviews__head">
					<p class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
					<div class="reviews__score"><span class="display"><?php echo esc_html( number_format( $avg, 1 ) ); ?></span><span class="stars" aria-label="<?php echo esc_attr( sprintf( __( '%s out of 5 stars', 'afazalif' ), number_format( $avg, 1 ) ) ); ?>">★★★★★</span><span class="mono"><?php echo esc_html( $s['score_text'] ); ?></span></div>
				</div>
				<div class="reviews__stage" data-cursor="Drag">
					<div class="reviews__track">
						<?php foreach ( $items as $i => $r ) : ?>
							<figure class="review<?php echo 0 === $i ? ' is-active' : ''; ?>">
								<span class="review__stars" aria-hidden="true">★★★★★</span>
								<blockquote class="review__text">“<?php echo esc_html( $r['text'] ); ?>”</blockquote>
								<figcaption class="review__by">
									<span class="mono"><?php echo esc_html( $r['name'] ); ?></span>
									<?php if ( ! empty( $r['verified'] ) && 'no' !== $r['verified'] ) : ?><span class="mono verified"><?php esc_html_e( 'Verified buyer', 'afazalif' ); ?></span><?php endif; ?>
									<span class="mono"><?php echo esc_html( $r['product'] ); ?></span>
								</figcaption>
							</figure>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="reviews__nav">
					<button class="round-btn magnetic" type="button" data-rev="-1" aria-label="<?php esc_attr_e( 'Previous review', 'afazalif' ); ?>">←</button>
					<span class="mono reviews__count"><b>01</b> / <?php echo esc_html( str_pad( (string) count( $items ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<button class="round-btn magnetic" type="button" data-rev="1" aria-label="<?php esc_attr_e( 'Next review', 'afazalif' ); ?>">→</button>
				</div>
			</div>
		</section>
		<?php
		$this->close();
	}
}
