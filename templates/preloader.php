<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="preloader" id="preloader" aria-hidden="true">
	<div class="preloader__curtain preloader__curtain--a"></div>
	<div class="preloader__curtain preloader__curtain--b"></div>
	<div class="preloader__inner">
		<div class="preloader__emblem">
			<span class="gold-mask gold-mask--emblem"></span>
			<span class="gold-mask gold-mask--emblem preloader__emblem-fill"></span>
		</div>
		<span class="gold-mask gold-mask--wordmark preloader__word"></span>
		<div class="preloader__meta">
			<span class="mono"><?php esc_html_e( 'Feel at peace', 'afazalif' ); ?></span>
			<span class="mono preloader__count"><b>000</b></span>
		</div>
	</div>
</div>
