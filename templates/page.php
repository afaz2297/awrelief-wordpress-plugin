<?php
/**
 * Any page or post not built with Elementor (gift card pages, blog posts, 404): the same
 * header + cream sheet frame as the rest of the site.
 */
defined( 'ABSPATH' ) || exit;

get_header();
$afz_404 = is_404();
?>
<main id="content" class="afz afz-shop-page afz-plain">
	<section class="page-hero page-hero--compact">
		<div class="page-hero__aura" aria-hidden="true"></div>
		<div class="wrap page-hero__inner">
			<?php if ( $afz_404 ) : ?>
				<p class="eyebrow"><?php esc_html_e( 'Page not found', 'afazalif' ); ?></p>
				<h1 class="display page-hero__title"><?php echo wp_kses( __( 'This scent has <em>drifted away.</em>', 'afazalif' ), array( 'em' => array() ) ); ?></h1>
				<p class="page-hero__lede"><?php esc_html_e( 'The page you were looking for has moved or no longer exists.', 'afazalif' ); ?></p>
			<?php else : ?>
				<p class="eyebrow"><?php echo is_singular( 'post' ) ? esc_html( get_the_date() ) : esc_html( get_bloginfo( 'name' ) ); ?></p>
				<h1 class="display page-hero__title"><?php single_post_title(); ?></h1>
			<?php endif; ?>
		</div>
	</section>
	<div class="sheet light woo-sheet">
		<div class="wrap woo-wrap prose-sec__main">
			<?php if ( $afz_404 ) : ?>
				<p style="display:flex;gap:12px;flex-wrap:wrap">
					<a class="btn btn--gold" href="<?php echo esc_url( function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' ) ); ?>"><span class="btn__label"><?php esc_html_e( 'Visit the shop', 'afazalif' ); ?></span><span class="btn__icon">→</span></a>
					<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="btn__label"><?php esc_html_e( 'Back home', 'afazalif' ); ?></span></a>
				</p>
			<?php else : ?>
				<div class="prose">
					<?php
					while ( have_posts() ) {
						the_post();
						if ( is_singular( 'post' ) && has_post_thumbnail() ) {
							echo afz_img( (int) get_post_thumbnail_id(), 'prose__cover', '', 'large', array( 'loading' => 'eager' ) ); // phpcs:ignore
						}
						the_content();
					}
					$afz_journal = is_singular( 'post' ) ? get_page_by_path( 'journal' ) : null;
					if ( $afz_journal ) {
						echo '<p><a class="link-arrow" href="' . esc_url( get_permalink( $afz_journal ) ) . '">' . esc_html__( 'Back to the Journal', 'afazalif' ) . ' <span>→</span></a></p>';
					}
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
