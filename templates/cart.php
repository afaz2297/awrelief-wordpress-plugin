<?php
/**
 * Basket drawer. Lines are rendered by afazalif.js from the cart JSON; the server prints the
 * first state so the count and totals are right before any script runs.
 */
defined( 'ABSPATH' ) || exit;

$afz_state = AFZ_Cart::state();
$afz_free  = (float) afz_opt( 'free_ship', 40 );
?>
<aside class="cart-drawer<?php echo $afz_state['count'] ? ' has-items' : ''; ?>" aria-hidden="true" aria-label="<?php esc_attr_e( 'Your basket', 'afazalif' ); ?>">
	<div class="cart__veil" data-close-cart></div>
	<div class="cart__panel" role="dialog" aria-modal="true" aria-labelledby="afz-cart-title">
		<div class="cart__head">
			<p class="display" id="afz-cart-title"><?php esc_html_e( 'Your basket', 'afazalif' ); ?></p>
			<button class="round-btn" type="button" data-close-cart aria-label="<?php esc_attr_e( 'Close basket', 'afazalif' ); ?>">✕</button>
		</div>
		<?php if ( $afz_free > 0 ) : ?>
		<div class="cart__ship">
			<div class="cart__ship-top">
				<?php echo afz_icon( 'truck' ); // phpcs:ignore ?>
				<p class="cart__ship-msg"></p>
			</div>
			<span class="cart__ship-bar" role="progressbar" aria-label="<?php esc_attr_e( 'Progress to free delivery', 'afazalif' ); ?>" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $afz_free ); ?>"><i></i></span>
			<div class="cart__ship-scale mono"><span><?php echo esc_html( afz_money( 0 ) ); ?></span><span><?php echo esc_html( sprintf( __( 'Free delivery at %s', 'afazalif' ), afz_money( $afz_free ) ) ); ?></span></div>
		</div>
		<?php endif; ?>
		<div class="cart__scroll" data-lenis-prevent><ul class="cart__items"></ul><div class="cart__upsell" hidden></div></div>
		<p class="cart__empty"><?php esc_html_e( 'Your basket is empty.', 'afazalif' ); ?> <a href="<?php echo esc_url( $afz_state['shop'] ); ?>" data-close-cart><?php esc_html_e( 'Explore the scents →', 'afazalif' ); ?></a></p>
		<div class="cart__foot">
			<div class="cart__total"><span class="mono"><?php esc_html_e( 'Subtotal', 'afazalif' ); ?></span><span class="display cart__sum"><?php echo esc_html( afz_money( $afz_state['subtotal'] ) ); ?></span></div>
			<a class="btn btn--gold btn--full magnetic" href="<?php echo esc_url( $afz_state['checkout'] ); ?>"><span class="btn__label"><?php esc_html_e( 'Checkout securely', 'afazalif' ); ?></span><span class="btn__icon">→</span></a>
			<p class="mono cart__note"><?php esc_html_e( 'Taxes included · UK delivery only', 'afazalif' ); ?></p>
		</div>
	</div>
	<script type="application/json" id="afz-cart-state"><?php echo wp_json_encode( $afz_state ); ?></script>
</aside>
