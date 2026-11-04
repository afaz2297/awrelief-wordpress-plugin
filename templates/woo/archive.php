<?php
/**
 * Shop, product category and scent pages.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$afz_obj   = get_queried_object();
$afz_term  = $afz_obj instanceof WP_Term ? $afz_obj : null;
$afz_scent = $afz_term && AFZ_Scents::TAX === $afz_term->taxonomy ? AFZ_Scents::data( $afz_term ) : null;
$afz_total = (int) $GLOBALS['wp_query']->found_posts;
$afz_query = is_search() ? get_search_query( false ) : '';

if ( is_search() ) {
	$afz_eyebrow = __( 'Search', 'afazalif' );
	/* translators: %s: what the visitor searched for */
	$afz_title = sprintf( __( 'Results for <em>“%s”</em>', 'afazalif' ), esc_html( $afz_query ) );
	$afz_lede  = '';
} elseif ( $afz_term ) {
	$afz_eyebrow = $afz_scent ? ( $afz_scent['family'] ? $afz_scent['family'] : __( 'Scent', 'afazalif' ) ) : __( 'The shop', 'afazalif' );
	$afz_title   = esc_html( $afz_term->name );
	$afz_lede    = $afz_term->description ? wp_strip_all_tags( $afz_term->description ) : '';
	if ( $afz_scent && ! $afz_lede ) {
		$afz_lede = implode( ' · ', array_filter( array( $afz_scent['top'], $afz_scent['heart'], $afz_scent['base'] ) ) );
	}
} else {
	$afz_eyebrow = __( 'The shop', 'afazalif' );
	$afz_title   = 'Every scent, <em>every ritual</em>';
	$afz_lede    = __( 'Hand-poured candles, reed diffusers, refills and our plant-based insect repellent. Handmade, vegan and cruelty-free, with free UK delivery over £40.', 'afazalif' );
}
$afz_aura = $afz_scent ? $afz_scent['aura'] : '#c9a04a';
?>
<main id="content" class="afz afz-shop">
	<section class="page-hero page-hero--shop" style="--hue:<?php echo esc_attr( $afz_aura ); ?>">
		<div class="page-hero__aura" aria-hidden="true"></div>
		<?php if ( $afz_scent && $afz_scent['image'] ) : ?>
			<?php echo afz_img( (int) $afz_scent['image'], 'page-hero__art', '', 'large', array( 'loading' => 'eager' ) ); // phpcs:ignore ?>
		<?php endif; ?>
		<div class="wrap page-hero__inner">
			<?php woocommerce_breadcrumb(); ?>
			<p class="eyebrow"><?php echo esc_html( $afz_eyebrow ); ?></p>
			<h1 class="display page-hero__title" data-split="lines"><?php echo wp_kses( $afz_title, array( 'em' => array() ) ); ?></h1>
			<?php if ( $afz_lede ) : ?><p class="page-hero__lede"><?php echo esc_html( $afz_lede ); ?></p><?php endif; ?>
			<?php if ( is_search() ) : ?>
				<form class="shop-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="sr-only" for="afz-search-again"><?php esc_html_e( 'Search products', 'afazalif' ); ?></label>
					<input id="afz-search-again" type="search" name="s" value="<?php echo esc_attr( $afz_query ); ?>" placeholder="<?php esc_attr_e( 'Search scents & products', 'afazalif' ); ?>">
					<input type="hidden" name="post_type" value="product">
					<button class="round-btn" type="submit" aria-label="<?php esc_attr_e( 'Search', 'afazalif' ); ?>">→</button>
				</form>
			<?php endif; ?>
			<p class="mono page-hero__count"><?php echo esc_html( sprintf( _n( '%d product', '%d products', $afz_total, 'afazalif' ), $afz_total ) ); ?></p>
		</div>
	</section>

	<div class="sheet light shop-sheet">
		<div class="shop-bar wrap">
			<nav class="shop-bar__cats" aria-label="<?php esc_attr_e( 'Product categories', 'afazalif' ); ?>" data-lenis-prevent>
				<?php foreach ( AFZ_Woo::category_chips() as $c ) : ?>
					<a href="<?php echo esc_url( $c[1] ); ?>" class="chip<?php echo $c[2] ? ' is-active' : ''; ?>"<?php echo $c[2] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="shop-bar__tools">
				<label class="sr-only" for="afz-scent-jump"><?php esc_html_e( 'Shop by scent', 'afazalif' ); ?></label>
				<select id="afz-scent-jump" class="shop-bar__select" data-afz-jump>
					<option value="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'All scents', 'afazalif' ); ?></option>
					<?php foreach ( AFZ_Scents::all() as $s ) : ?>
						<option value="<?php echo esc_url( $s['url'] ); ?>" data-aura="<?php echo esc_attr( $s['aura'] ); ?>"<?php selected( $afz_scent && $afz_scent['id'] === $s['id'] ); ?>><?php echo esc_html( $s['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php woocommerce_catalog_ordering(); ?>
			</div>
		</div>

		<div class="wrap shop-body">
			<?php woocommerce_output_all_notices(); ?>
			<?php if ( have_posts() ) : ?>
				<div class="best__grid shop-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						echo AFZ_Woo::card( get_the_ID() ); // phpcs:ignore
					}
					?>
				</div>
				<?php
				$afz_links = paginate_links( array(
					'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'prev_text' => '←',
					'next_text' => '→',
					'type'      => 'array',
				) );
				if ( $afz_links ) {
					echo '<nav class="pager mono" aria-label="' . esc_attr__( 'Pages', 'afazalif' ) . '">' . implode( '', $afz_links ) . '</nav>'; // phpcs:ignore
				}
				?>
			<?php else : ?>
				<div class="shop-empty">
					<p class="display"><?php echo is_search() ? esc_html__( 'No products match that search.', 'afazalif' ) : esc_html__( 'Nothing here yet.', 'afazalif' ); ?></p>
					<a class="btn btn--gold" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><span class="btn__label"><?php esc_html_e( 'See every product', 'afazalif' ); ?></span><span class="btn__icon">→</span></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
