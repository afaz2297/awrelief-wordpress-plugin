<?php
/**
 * Basket, checkout, order received and account pages: a calm frame around WooCommerce's own forms.
 */
defined( 'ABSPATH' ) || exit;

get_header();

if ( is_wc_endpoint_url( 'order-received' ) ) {
	$afz_eyebrow = __( 'Order received', 'afazalif' );
	$afz_title   = __( 'Thank you, <em>truly.</em>', 'afazalif' );
} elseif ( is_checkout() ) {
	$afz_eyebrow = __( 'Secure checkout', 'afazalif' );
	$afz_title   = __( 'Almost <em>yours.</em>', 'afazalif' );
} elseif ( is_cart() ) {
	$afz_eyebrow = __( 'Your basket', 'afazalif' );
	$afz_title   = __( 'Ready when <em>you are.</em>', 'afazalif' );
} else {
	$afz_eyebrow = __( 'My account', 'afazalif' );
	$afz_title   = is_user_logged_in() ? sprintf( __( 'Hello, <em>%s.</em>', 'afazalif' ), esc_html( wp_get_current_user()->first_name ? wp_get_current_user()->first_name : wp_get_current_user()->display_name ) ) : __( 'Welcome <em>back.</em>', 'afazalif' );
}
$afz_step = is_cart() ? 1 : ( is_wc_endpoint_url( 'order-received' ) ? 3 : ( is_checkout() ? 2 : 0 ) );
?>
<main id="content" class="afz afz-shop-page<?php echo is_checkout() ? ' afz-checkout' : ''; ?>">
	<section class="page-hero page-hero--compact">
		<div class="page-hero__aura" aria-hidden="true"></div>
		<div class="wrap page-hero__inner">
			<p class="eyebrow"><?php echo esc_html( $afz_eyebrow ); ?></p>
			<h1 class="display page-hero__title"><?php echo wp_kses( $afz_title, array( 'em' => array() ) ); ?></h1>
			<?php if ( $afz_step ) : ?>
				<ol class="steps mono" aria-label="<?php esc_attr_e( 'Checkout steps', 'afazalif' ); ?>">
					<?php foreach ( array( 1 => __( 'Basket', 'afazalif' ), 2 => __( 'Details & payment', 'afazalif' ), 3 => __( 'Confirmation', 'afazalif' ) ) as $n => $label ) : ?>
						<li class="<?php echo $n === $afz_step ? 'is-current' : ( $n < $afz_step ? 'is-done' : '' ); ?>"<?php echo $n === $afz_step ? ' aria-current="step"' : ''; ?>><b><?php echo esc_html( '0' . $n ); ?></b><?php echo esc_html( $label ); ?></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
	</section>
	<div class="sheet light woo-sheet">
		<div class="wrap woo-wrap">
			<?php
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			?>
		</div>
	</div>
</main>
<?php
get_footer();
