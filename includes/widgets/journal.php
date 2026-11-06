<?php
defined( 'ABSPATH' ) || exit;

/**
 * The Journal list: every published blog post (Posts in the WordPress dashboard), newest first.
 */
class AFZ_Widget_Journal extends AFZ_Widget_Base {

	public function get_name() {
		return 'afz-journal';
	}

	public function get_title() {
		return __( 'AW Journal list', 'afazalif' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'afazalif' ) ) );
		$this->text( 'empty', __( 'Text while there are no stories', 'afazalif' ), 'Our first stories are on their way.', 'textarea' );
		$this->end_controls_section();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$demo = get_page_by_path( 'hello-world', OBJECT, 'post' ); // WordPress's sample post is not a story
		$q    = new WP_Query( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 24,
			'ignore_sticky_posts' => true,
			'post__not_in'        => $demo ? array( $demo->ID ) : array(),
		) );
		$this->open( 'light' );
		?>
		<section class="journal journal--list light">
			<div class="wrap">
				<?php if ( $q->have_posts() ) : ?>
					<div class="journal__grid">
						<?php
						while ( $q->have_posts() ) :
							$q->the_post();
							$cats = get_the_category();
							?>
							<a class="post" href="<?php the_permalink(); ?>">
								<span class="post__img"><?php if ( has_post_thumbnail() ) { echo afz_img( (int) get_post_thumbnail_id(), '', '', 'large' ); } // phpcs:ignore ?></span>
								<span class="mono post__cat"><?php echo esc_html( $cats ? $cats[0]->name . ' · ' . get_the_date() : get_the_date() ); ?></span>
								<span class="display post__title"><?php the_title(); ?></span>
							</a>
						<?php endwhile; ?>
					</div>
				<?php else : ?>
					<p class="journal__empty"><?php echo esc_html( $s['empty'] ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
		$this->close();
	}
}
