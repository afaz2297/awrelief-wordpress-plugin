<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="search" aria-hidden="true" role="dialog" aria-label="<?php esc_attr_e( 'Search the shop', 'afazalif' ); ?>">
	<button class="round-btn search__close" type="button" data-close-search aria-label="<?php esc_attr_e( 'Close search', 'afazalif' ); ?>">✕</button>
	<form class="search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="sr-only" for="afz-search"><?php esc_html_e( 'Search products', 'afazalif' ); ?></label>
		<input id="afz-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Search scents & products', 'afazalif' ); ?>" autocomplete="off">
		<input type="hidden" name="post_type" value="product">
		<button class="round-btn" type="submit" aria-label="<?php esc_attr_e( 'Search', 'afazalif' ); ?>">→</button>
	</form>
	<p class="mono search__hint"><?php esc_html_e( 'Try “Heaven”, “diffuser” or “repellent”', 'afazalif' ); ?></p>
</div>
