<?php
defined( 'ABSPATH' ) || exit;

$afz_policy = get_privacy_policy_url();
?>
<div class="cookies" role="region" aria-label="<?php esc_attr_e( 'Cookie consent', 'afazalif' ); ?>" hidden>
	<p><?php echo esc_html( afz_opt( 'cookies_text' ) ); ?><?php if ( $afz_policy ) : ?> <a href="<?php echo esc_url( $afz_policy ); ?>"><?php esc_html_e( 'Privacy & cookies', 'afazalif' ); ?></a><?php endif; ?></p>
	<div class="cookies__actions">
		<button class="btn btn--gold" type="button" data-consent="all"><span class="btn__label"><?php esc_html_e( 'Accept all', 'afazalif' ); ?></span></button>
		<button class="btn btn--ghost" type="button" data-consent="essential"><span class="btn__label"><?php esc_html_e( 'Essential only', 'afazalif' ); ?></span></button>
	</div>
</div>
