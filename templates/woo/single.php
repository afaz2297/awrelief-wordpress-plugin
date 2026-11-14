<?php
/**
 * Product page.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	$afz_scent   = AFZ_Scents::of_product( $product->get_id() );
	$afz_aura    = AFZ_Scents::aura( $product, false, $afz_scent );
	$afz_aura2   = $afz_scent ? $afz_scent['aura2'] : '#e8c9a0';
	$afz_images  = array_values( array_unique( array_filter( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) ) ) );
	$afz_label   = AFZ_Scents::format_label( $product );
	$afz_count   = (int) $product->get_review_count();
	$afz_free    = (float) afz_opt( 'free_ship', 40 );
	$afz_delivery = get_page_by_path( 'delivery' );
	$afz_returns = get_page_by_path( 'returns' );
	?>
	<main id="content" class="afz afz-product">
		<?php do_action( 'woocommerce_before_single_product' ); ?>
		<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pdp-wrap', $product ); ?>>
			<section class="pdp" style="--a1:<?php echo esc_attr( $afz_aura ); ?>;--a2:<?php echo esc_attr( $afz_aura2 ); ?>" data-type="<?php echo esc_attr( $product->get_type() ); ?>">
				<div class="pdp__aura" aria-hidden="true"></div>
				<div class="wrap pdp__grid">
					<div class="pdp__gallery" data-afz-gallery>
						<div class="pdp__stage" data-cursor="<?php esc_attr_e( 'Zoom', 'afazalif' ); ?>">
							<?php foreach ( $afz_images as $i => $img ) : ?>
								<figure class="pdp__shot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-shot="<?php echo esc_attr( $i ); ?>">
									<?php echo afz_img( $img, '', 0 === $i ? $product->get_name() : '', 'woocommerce_single', array( 'loading' => 0 === $i ? 'eager' : 'lazy', 'fetchpriority' => 0 === $i ? 'high' : 'auto', 'data-full' => wp_get_attachment_image_url( $img, 'full' ) ) ); // phpcs:ignore ?>
								</figure>
							<?php endforeach; ?>
							<?php if ( ! $afz_images ) : ?><figure class="pdp__shot is-active"><?php echo wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore ?></figure><?php endif; ?>
							<?php if ( $product->is_on_sale() ) : ?><span class="mono prod__flag"><?php esc_html_e( 'Offer', 'afazalif' ); ?></span><?php endif; ?>
						</div>
						<?php if ( count( $afz_images ) > 1 ) : ?>
							<div class="pdp__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'afazalif' ); ?>">
								<?php foreach ( $afz_images as $i => $img ) : ?>
									<button type="button" class="pdp__thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" data-to="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Image %d', 'afazalif' ), $i + 1 ) ); ?>"><?php echo afz_img( $img, '', '', 'woocommerce_gallery_thumbnail' ); // phpcs:ignore ?></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="pdp__summary summary entry-summary">
						<?php woocommerce_breadcrumb(); ?>
						<p class="eyebrow"><?php echo esc_html( trim( ( $afz_scent ? $afz_scent['family'] . ' · ' : '' ) . ( 'Diffuser' === $afz_label ? 'Reed Diffuser' : $afz_label ), ' ·' ) ); ?></p>
						<h1 class="display pdp__title product_title entry-title"><?php the_title(); ?></h1>
						<div class="pdp__meta">
							<p class="price pdp__price"><?php echo $product->get_price_html(); // phpcs:ignore ?></p>
							<?php if ( $afz_count ) : ?>
								<a href="#reviews" class="pdp__rating"><?php echo AFZ_Woo::stars( (float) $product->get_average_rating() ); // phpcs:ignore ?><span class="mono"><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $afz_count, 'afazalif' ), $afz_count ) ); ?></span></a>
							<?php endif; ?>
						</div>
						<?php if ( $product->get_short_description() ) : ?>
							<div class="pdp__lede"><?php echo wp_kses_post( wpautop( AFZ_Woo::without_notes( $product->get_short_description() ) ) ); ?></div>
						<?php endif; ?>

						<?php if ( $afz_scent && ( $afz_scent['top'] || $afz_scent['heart'] || $afz_scent['base'] ) ) : ?>
							<dl class="pdp__notes">
								<?php foreach ( array( 'top' => __( 'Top', 'afazalif' ), 'heart' => __( 'Heart', 'afazalif' ), 'base' => __( 'Base', 'afazalif' ) ) as $k => $l ) : ?>
									<?php if ( $afz_scent[ $k ] ) : ?><div><dt class="mono"><?php echo esc_html( $l ); ?></dt><dd><?php echo esc_html( $afz_scent[ $k ] ); ?></dd></div><?php endif; ?>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>

						<?php $afz_sib = AFZ_Woo::siblings( $product ); ?>
						<?php if ( $afz_sib ) : ?>
							<div class="pdp__formats">
								<p class="pdp__formats-title"><?php esc_html_e( 'Also comes in', 'afazalif' ); ?></p>
								<div class="pdp__formats-list">
									<?php foreach ( $afz_sib as $f ) : ?>
										<a class="pdp__format" href="<?php echo esc_url( $f['url'] ); ?>">
											<?php if ( ! empty( $f['img'] ) ) : ?><?php echo afz_img( (int) $f['img'], 'pdp__format-img', '', 'woocommerce_thumbnail' ); // phpcs:ignore ?><?php endif; ?>
											<span class="pdp__format-name"><?php echo esc_html( 'Diffuser' === $f['label'] ? __( 'Reed Diffuser', 'afazalif' ) : $f['label'] ); ?><b><?php echo esc_html( afz_money( $f['price'] ) ); ?></b></span>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<div class="pdp__buy">
							<?php woocommerce_template_single_add_to_cart(); ?>
						</div>

						<?php do_action( 'woocommerce_single_product_summary' ); ?>

						<ul class="pdp__trust mono">
							<?php if ( $afz_free ) : ?><li><?php echo afz_icon( 'truck' ); // phpcs:ignore ?><?php echo esc_html( sprintf( __( 'Free UK delivery over %s', 'afazalif' ), afz_money( $afz_free ) ) ); ?></li><?php endif; ?>
							<li><span class="pdp__tick">✦</span><?php esc_html_e( 'Handmade · Vegan · Cruelty-free', 'afazalif' ); ?></li>
							<li><?php echo afz_icon( 'lock' ); // phpcs:ignore ?><?php esc_html_e( 'Secure checkout', 'afazalif' ); ?></li>
						</ul>

						<div class="pdp__acc">
							<?php if ( get_the_content() ) : ?>
								<details open><summary><?php esc_html_e( 'Details & care', 'afazalif' ); ?></summary><div class="pdp__acc-body"><?php echo apply_filters( 'the_content', AFZ_Woo::without_notes( get_the_content() ) ); // phpcs:ignore ?></div></details>
							<?php endif; ?>
							<details><summary><?php esc_html_e( 'Delivery & returns', 'afazalif' ); ?></summary><div class="pdp__acc-body">
								<p><?php esc_html_e( 'UK Standard Delivery £3.99 (up to 4 working days), free over £40.', 'afazalif' ); ?></p>
								<p><?php esc_html_e( 'UK Next Day Delivery £6.99 when you order before 4pm, free over £80.', 'afazalif' ); ?>
								<?php if ( $afz_delivery ) : ?> <a href="<?php echo esc_url( get_permalink( $afz_delivery ) ); ?>"><?php esc_html_e( 'Delivery', 'afazalif' ); ?></a><?php endif; ?></p>
								<p><?php esc_html_e( 'Changed your mind? You can return unused items within 14 days.', 'afazalif' ); ?>
								<?php if ( $afz_returns ) : ?> <a href="<?php echo esc_url( get_permalink( $afz_returns ) ); ?>"><?php esc_html_e( 'Returns', 'afazalif' ); ?></a><?php endif; ?></p>
							</div></details>
						</div>
					</div>
				</div>
			</section>

			<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

			<div class="sheet light pdp-sheet">
				<?php if ( comments_open() || $afz_count ) : ?>
					<section class="pdp-reviews wrap">
						<div class="sec-head">
							<p class="eyebrow eyebrow--dark"><?php esc_html_e( 'Kind words', 'afazalif' ); ?></p>
							<h2 class="display sec-head__title" data-split="lines"><?php echo $afz_count ? esc_html( number_format( (float) $product->get_average_rating(), 1 ) ) . ' <em>' . esc_html__( 'out of five', 'afazalif' ) . '</em>' : esc_html__( 'Be the first', 'afazalif' ) . ' <em>' . esc_html__( 'to review', 'afazalif' ) . '</em>'; ?></h2>
						</div>
						<?php comments_template(); ?>
					</section>
				<?php endif; ?>

				<?php
				$afz_related = wc_get_related_products( $product->get_id(), 4 );
				if ( $afz_related ) :
					?>
					<section class="best pdp-related">
						<div class="wrap">
							<div class="sec-head sec-head--dark">
								<p class="eyebrow eyebrow--dark"><?php esc_html_e( 'You may also love', 'afazalif' ); ?></p>
								<h2 class="display sec-head__title" data-split="lines"><?php echo wp_kses( __( 'More ways to <em>feel at peace</em>', 'afazalif' ), array( 'em' => array() ) ); ?></h2>
							</div>
							<div class="best__grid"><?php foreach ( $afz_related as $rid ) { echo AFZ_Woo::card( $rid ); } // phpcs:ignore ?></div>
						</div>
					</section>
				<?php endif; ?>
			</div>
		</div>
		<?php do_action( 'woocommerce_after_single_product' ); ?>
	</main>
	<?php
endwhile;

get_footer();
