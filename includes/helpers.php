<?php
/**
 * Small shared helpers.
 */

defined( 'ABSPATH' ) || exit;

/** One saved setting, with the built-in default as fallback. */
function afz_opt( $key, $fallback = null ) {
	$all = AFZ_Settings::get();
	if ( array_key_exists( $key, $all ) && '' !== $all[ $key ] && null !== $all[ $key ] ) {
		return $all[ $key ];
	}
	$defaults = AFZ_Settings::defaults();
	return $defaults[ $key ] ?? $fallback;
}

/** Feature switches stored as '1' / '0'. */
function afz_on( $key ) {
	return '1' === (string) afz_opt( $key, '0' );
}

/** "Light a moment of {peace.}" → gold italic <em> for the words in curly brackets. Line breaks become <br>. */
function afz_hl( $text ) {
	$text = esc_html( (string) $text );
	$text = preg_replace( '/\{(.+?)\}/s', '<em>$1</em>', $text );
	return nl2br( $text, false );
}

/** Same as afz_hl() but returns one <span class="line"> per line, for the hero-style char split. */
function afz_lines( $text ) {
	$out = '';
	foreach ( preg_split( '/\r\n|\r|\n/', trim( (string) $text ) ) as $line ) {
		if ( '' !== trim( $line ) ) {
			$out .= '<span class="line">' . afz_hl( trim( $line ) ) . '</span>';
		}
	}
	return $out;
}

/** Price in the shop currency, e.g. £24.99. */
function afz_money( $amount ) {
	$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '£';
	return $symbol . number_format( (float) $amount, 2 );
}

/**
 * Site-relative links ("/about-us/") point at this WordPress install, wherever it lives
 * (the local copy sits in /awrelief/, the live site at the domain root).
 */
function afz_url( $url ) {
	$url = trim( (string) $url );
	if ( '' !== $url && '/' === $url[0] && ( ! isset( $url[1] ) || '/' !== $url[1] ) ) {
		return home_url( $url );
	}
	return $url;
}

/** href/target/rel attributes from an Elementor URL control (or a plain string). */
function afz_link_attrs( $link, $extra = array() ) {
	if ( is_string( $link ) ) {
		$link = array( 'url' => $link );
	}
	$url   = afz_url( $link['url'] ?? '' );
	$attrs = ' href="' . esc_url( $url ? $url : '#' ) . '"';
	if ( ! empty( $link['is_external'] ) ) {
		$attrs .= ' target="_blank"';
	}
	$rel = array();
	if ( ! empty( $link['is_external'] ) ) {
		$rel[] = 'noopener';
	}
	if ( ! empty( $link['nofollow'] ) ) {
		$rel[] = 'nofollow';
	}
	if ( $rel ) {
		$attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
	}
	foreach ( $extra as $k => $v ) {
		$attrs .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return $attrs;
}

/** URL of an Elementor MEDIA control value, an attachment ID, or a plain URL. */
function afz_media_url( $media, $size = 'full' ) {
	if ( is_array( $media ) ) {
		if ( ! empty( $media['id'] ) ) {
			$src = wp_get_attachment_image_url( (int) $media['id'], $size );
			if ( $src ) {
				return $src;
			}
		}
		return $media['url'] ?? '';
	}
	if ( is_numeric( $media ) ) {
		return (string) wp_get_attachment_image_url( (int) $media, $size );
	}
	return (string) $media;
}

/** <img> for an Elementor MEDIA value with srcset, sizes and lazy loading handled by WordPress. */
function afz_img( $media, $class = '', $alt = null, $size = 'large', $attrs = array() ) {
	$id   = is_array( $media ) ? (int) ( $media['id'] ?? 0 ) : (int) $media;
	$base = array_merge( array( 'class' => $class, 'loading' => 'lazy', 'decoding' => 'async' ), $attrs );
	if ( null !== $alt ) {
		$base['alt'] = $alt;
	}
	if ( $id && wp_attachment_is_image( $id ) ) {
		return wp_get_attachment_image( $id, $size, false, $base );
	}
	$url = afz_media_url( $media, $size );
	if ( ! $url ) {
		return '';
	}
	$html = '<img src="' . esc_url( $url ) . '"';
	foreach ( $base as $k => $v ) {
		$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return $html . '>';
}

/** Find an attachment the seed script imported, by its original file name. */
function afz_seed_media( $name ) {
	$map = get_option( 'afz_seed_media', array() );
	return isset( $map[ $name ] ) ? array( 'id' => (int) $map[ $name ], 'url' => wp_get_attachment_url( (int) $map[ $name ] ) ) : array( 'id' => 0, 'url' => '' );
}

/** Tiny blurred base64 preview of an image, cached on the attachment (used while the real image loads). */
function afz_lqip( $id ) {
	$id = (int) $id;
	if ( ! $id ) {
		return '';
	}
	$cached = get_post_meta( $id, '_afz_lqip', true );
	if ( $cached ) {
		return $cached;
	}
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) ) {
		return '';
	}
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return '';
	}
	$editor->resize( 24, 30, false );
	$tmp   = wp_tempnam( 'afz-lqip' ) . '.webp';
	$saved = $editor->save( $tmp, 'image/webp' );
	if ( is_wp_error( $saved ) ) {
		return '';
	}
	$data = 'data:image/webp;base64,' . base64_encode( file_get_contents( $saved['path'] ) ); // phpcs:ignore
	wp_delete_file( $saved['path'] );
	update_post_meta( $id, '_afz_lqip', $data );
	return $data;
}

/** Social profiles from the settings, in display order. */
function afz_socials() {
	$out = array();
	foreach ( array( 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook', 'whatsapp' => 'WhatsApp' ) as $key => $label ) {
		$url = trim( (string) afz_opt( $key ) );
		if ( 'whatsapp' === $key && $url && ! preg_match( '#^https?://#', $url ) ) {
			$url = 'https://wa.me/' . preg_replace( '/\D/', '', $url );
		}
		if ( $url ) {
			$out[ $key ] = array( 'label' => $label, 'url' => $url );
		}
	}
	return $out;
}
