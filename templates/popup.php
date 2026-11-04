<?php
defined( 'ABSPATH' ) || exit;

$afz_img = (int) afz_opt( 'popup_image', 0 );
if ( ! $afz_img ) {
	$afz_img = afz_seed_media( 'life-sofa.webp' )['id'];
}
?>
<div class="popup" aria-hidden="true">
	<div class="popup__veil" data-close-popup></div>
	<div class="popup__card" role="dialog" aria-modal="true" aria-labelledby="afz-popup-title">
		<button class="round-btn popup__close" type="button" data-close-popup aria-label="<?php esc_attr_e( 'Close', 'afazalif' ); ?>">✕</button>
		<div class="popup__media"><?php echo $afz_img ? afz_img( $afz_img, '', '', 'large' ) : ''; // phpcs:ignore ?></div>
		<div class="popup__body">
			<p class="eyebrow"><?php echo esc_html( afz_opt( 'popup_eyebrow' ) ); ?></p>
			<h2 class="display popup__title" id="afz-popup-title"><?php echo afz_hl( afz_opt( 'popup_title' ) ); // phpcs:ignore ?></h2>
			<p class="popup__text"><?php echo esc_html( afz_opt( 'popup_text' ) ); ?></p>
			<?php AFZ_Forms::newsletter_form( 'popup', __( 'Join', 'afazalif' ) ); ?>
			<p class="mono popup__small"><?php esc_html_e( 'Unsubscribe any time.', 'afazalif' ); ?></p>
		</div>
	</div>
</div>
