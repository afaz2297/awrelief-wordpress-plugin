<?php
/**
 * Inline SVG icons (stroke icons share one style in the stylesheet).
 */

defined( 'ABSPATH' ) || exit;

function afz_icon( $name ) {
	$icons = array(
		'search'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/></svg>',
		'account'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20c1.4-3.6 4.2-5.4 7.5-5.4s6.1 1.8 7.5 5.4"/></svg>',
		'basket'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r=".6" class="fill"/></svg>',
		'tiktok'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3.5v11.2a3.6 3.6 0 1 1-3.6-3.6"/><path d="M14 3.5c.4 2.6 2.2 4.3 5 4.5"/></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 8.5h2V5.2h-2.4c-2.6 0-3.8 1.6-3.8 4v2.1H8v3.3h2.3V21h3.4v-6.4h2.4l.5-3.3h-2.9V9.6c0-.7.3-1.1.8-1.1z"/></svg>',
		'whatsapp'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 19.5l1.2-3.6A8 8 0 1 1 8.6 18.6z"/><path d="M9.2 8.6c.3 2.6 2.4 4.9 5.2 5.4l1-1.2-1.6-.9-.8.7c-1-.4-1.8-1.2-2.2-2.2l.7-.8-.9-1.6z" class="fill"/></svg>',
		'lock'      => '<svg class="footer__lock" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10.5" width="14" height="10" rx="2.5"/><path d="M8.5 10.5V7.8a3.5 3.5 0 0 1 7 0v2.7"/><circle cx="12" cy="15.5" r="1.3" class="fill"/></svg>',
		'truck'     => '<svg class="cart__truck" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 6.5h11v9h-11z"/><path d="M13.5 9.5h4l3 3.2v2.8h-7"/><circle cx="6.5" cy="17" r="1.8"/><circle cx="17" cy="17" r="1.8"/></svg>',
	);
	return $icons[ $name ] ?? '';
}
