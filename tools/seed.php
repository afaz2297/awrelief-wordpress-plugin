<?php
/**
 * One-time setup for a fresh copy of the store (local clone or live). Safe to run again:
 * every step checks what already exists.
 *
 *   php tools/seed.php <path/to/wp-load.php> <path/to/html-design/assets> [--home] [--theme]
 *
 *   media + scents + categories + settings always run.
 *   --home   rebuilds the homepage layout with the AWRELIEF widgets (old layout is kept as a revision).
 *   --theme  creates the Elementor kit, header and footer templates.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'CLI only' );
}
$args = $argv;
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
define( 'WP_ADMIN', true );
require $args[1];
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
wp_set_current_user( 1 );

$src  = rtrim( $args[2], '/\\' );
$opts = array_slice( $args, 3 );
$log  = function ( $m ) {
	echo $m . "\n";
};

/* ---------- plugin active, defaults saved ---------- */
if ( ! is_plugin_active( 'afazalif/afazalif.php' ) ) {
	$r = activate_plugin( 'afazalif/afazalif.php' );
	$log( is_wp_error( $r ) ? 'activate failed: ' . $r->get_error_message() : 'plugin activated' );
}
AFZ_Settings::install_defaults();
AFZ_Forms::install();

/* ---------- media ---------- */
$files = array(
	'video/hero-1080.mp4'           => 'AWRELIEF hero film (1080p)',
	'video/hero-720.mp4'            => 'AWRELIEF hero film (phones)',
	'video/hero-poster.webp'        => 'Candle and reed diffuser in a living room',
	'video/repel-1080.mp4'          => 'Insect repellent film (1080p)',
	'video/repel-720.mp4'           => 'Insect repellent film (phones)',
	'video/repel-poster.webp'       => 'Insect repellent being sprayed',
	'video/hero-poster-m.webp'      => 'A lit AWRELIEF candle',
	'video/repel-poster-m.webp'     => 'Holding the AWRELIEF insect repellent',
	'img/care-diffuser-refill.webp' => 'AWRELIEF reed diffuser and diffuser refill pouch',
	'img/care-repellent.webp'       => 'AWRELIEF natural insect repellent 100ml',
	'img/founder-synita.webp'       => 'Synita, founder of AWRELIEF, holding an AWRELIEF gift bag',
	'img/life-sofa.webp'            => 'AWRELIEF candle and reed diffuser in a calm living room',
	'img/life-reading.webp'         => 'Reading by candlelight with an AWRELIEF candle',
	'img/life-books.webp'           => 'AWRELIEF candle on a stack of books',
	'img/products/candle.webp'      => 'AWRELIEF scented candle',
	'img/products/diffuser.webp'    => 'AWRELIEF reed diffuser 200ml',
	'img/products/refill.webp'      => 'AWRELIEF diffuser refill pouch',
	'img/products/repellent.webp'   => 'AWRELIEF natural insect repellent 100ml',
	'img/products/holder-gold.webp' => 'Gold Nordic candle holder',
	'img/products/holder-black.webp' => 'Black Nordic candle holder',
	'img/products/gift-card.webp'   => 'AWRELIEF gift card',
);
foreach ( glob( $src . '/img/scents/*.webp' ) as $f ) {
	$files[ 'img/scents/' . basename( $f ) ] = ucwords( str_replace( '-', ' ', basename( $f, '.webp' ) ) ) . ' scent ingredients';
}
$map = get_option( 'afz_seed_media', array() );
foreach ( $files as $rel => $alt ) {
	$name = basename( $rel );
	if ( ! empty( $map[ $name ] ) && get_post( $map[ $name ] ) ) {
		continue;
	}
	$path = $src . '/' . $rel;
	if ( ! file_exists( $path ) ) {
		$log( "missing $path" );
		continue;
	}
	$tmp = wp_tempnam( $name );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $alt );
	if ( is_wp_error( $id ) ) {
		$log( "upload $name failed: " . $id->get_error_message() );
		continue;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$map[ $name ] = $id;
	update_option( 'afz_seed_media', $map, false );
	$log( "media $name -> $id" );
}

/* ---------- scents ---------- */
$scents = array(
	array( 'heaven', 'Heaven', 'Floral gourmand', '#b9a6d9', '#e8c9a0', 'Iris, Orange Blossom', 'Pear, Powdered Almond', 'Patchouli, Tonka Bean' ),
	array( 'cozy', 'Cozy', 'Warm vanilla', '#c98d4a', '#f0d2a8', 'Sheer Vanilla, Buttery Balsamic', 'Himalayan Salt, Caramel', 'Sandalwood, Tonka Bean' ),
	array( 'seychelles', 'Seychelles', 'Tropical', '#7cc4b8', '#f1dca6', 'Fresh Coconut, Bergamot', 'Amber, Jasmine', 'Butter, Vanilla' ),
	array( 'lime-basil-mandarin', 'Lime, Basil & Mandarin', 'Fresh citrus', '#a7c35a', '#f2c46b', 'Mandarin', 'Basil', 'Amberwood' ),
	array( 'rose-trilogy', 'Rose Trilogy', 'Spiced rose', '#c4455a', '#f0b8a8', 'Sichuan Pepper, Turmeric', 'May, Turkish & Bulgarian Rose', 'Patchouli, Tonka Bean' ),
	array( 'serene', 'Serene', 'Aromatic calm', '#8f86c4', '#c9d6b8', 'Lavender, Juniper Water', 'Cardamom, Sage, Davana', 'Musk, White Woods, Beach Wood' ),
	array( 'vines', 'Vines', 'Green & fruity', '#6e9a4a', '#e9a36b', 'Tagette, Tomato Leaf, Cassis', 'Rose, Orange Blossom', 'Musk, Amber, Orchid' ),
	array( 'cashmere', 'Cashmere', 'Soft musk', '#d9c3a5', '#c8b4d8', 'Orange Flower, Coconut', 'White Mimosa, Rose', 'Cashmere, Sand Musk' ),
	array( 'chill', 'Chill', 'Smoky woods', '#8a5a3c', '#e0b98d', 'Pink Pepper, Orange Flower Petals, Clove Oil', 'Chestnut Accord, Guaiac Wood, Juniper', 'Vanilla Accord, Peru Balsam, Cashmeran' ),
	array( 'apple', 'Apple', 'Festive fruity', '#c9453c', '#9cc25a', 'Apple, Green Leaf', 'Brown Sugar, Cranberry', 'Musk, Sweet Raspberry' ),
	array( 'tropical-samba', 'Tropical Samba', 'Fruity floral', '#f2b36b', '#f3d9e4', 'Apple, Lemon, Almond', 'Coconut, White Florals', 'Woods, Tonka, Moss' ),
	array( 'spice', 'Spice', 'Autumn spice', '#d9782d', '#f0c58d', 'Pumpkin', 'Pumpkin, Cinnamon, Clove', 'Musk' ),
	array( 'snuggles', 'Snuggles', 'Spiced gourmand', '#7a4a2e', '#b9d3b0', 'Cocoa, Ginger, Clove, Cinnamon, Nutmeg', 'Mint', 'Cocoa, Vanilla, Biscuit' ),
);
if ( ! taxonomy_exists( AFZ_Scents::TAX ) ) {
	AFZ_Scents::instance()->register(); // the plugin was activated during this run, after init
}
$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1 ) );
foreach ( $scents as $i => $s ) {
	list( $slug, $name, $family, $aura, $aura2, $top, $heart, $base ) = $s;
	$term = get_term_by( 'slug', $slug, AFZ_Scents::TAX );
	if ( ! $term ) {
		$r = wp_insert_term( $name, AFZ_Scents::TAX, array( 'slug' => $slug ) );
		if ( is_wp_error( $r ) ) {
			$log( "scent $name: " . $r->get_error_message() );
			continue;
		}
		$term = get_term( $r['term_id'], AFZ_Scents::TAX );
	}
	$meta = array( 'family' => $family, 'top' => $top, 'heart' => $heart, 'base' => $base, 'aura' => $aura, 'aura2' => $aura2, 'order' => (string) ( $i + 1 ), 'image' => (string) ( $map[ $slug . '.webp' ] ?? '' ) );
	foreach ( $meta as $k => $v ) {
		if ( '' === get_term_meta( $term->term_id, 'afz_' . $k, true ) ) {
			update_term_meta( $term->term_id, 'afz_' . $k, $v );
		}
	}
	// products whose name starts with the scent name ("Heaven Candle 220g", "Lime Basil & Mandarin Refill")
	$needle = strtolower( str_replace( array( ',', '&amp;' ), array( '', '&' ), $name ) );
	$count  = 0;
	foreach ( $products as $p ) {
		$title = strtolower( str_replace( array( ',', '&amp;' ), array( '', '&' ), html_entity_decode( $p->post_title ) ) );
		if ( 0 === strpos( $title, $needle . ' ' ) ) {
			wp_set_object_terms( $p->ID, array( (int) $term->term_id ), AFZ_Scents::TAX, true );
			$count++;
		}
	}
	delete_transient( 'afz_formats2_' . $term->term_id );
	$log( "scent $name: $count products" );
}

/* ---------- card lines for products without a scent ---------- */
foreach ( array( 'Insect Repellent 100ml' => 'Plant-based · DEET-free', 'Gold Nordic Candle Holder' => 'Wrought iron · Gold finish', 'Black Nordic Candle Holder' => 'Wrought iron · Matt black' ) as $title => $note ) {
	$ids = get_posts( array( 'post_type' => 'product', 'title' => $title, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $ids && ! get_post_meta( $ids[0], '_afz_card_note', true ) ) {
		update_post_meta( $ids[0], '_afz_card_note', $note );
	}
}

// the repellent has no scent, so it gets its own green glow like the scented products beside it
$ids = get_posts( array( 'post_type' => 'product', 'title' => 'Insect Repellent 100ml', 'numberposts' => 1, 'fields' => 'ids' ) );
if ( $ids && ! get_post_meta( $ids[0], '_afz_aura', true ) ) {
	update_post_meta( $ids[0], '_afz_aura', '#a8cc8c' );
	update_post_meta( $ids[0], '_afz_plain', 'no' );
}

/* ---------- reed diffusers category (they had none) ---------- */
$cat = get_term_by( 'slug', 'reed-diffusers', 'product_cat' );
if ( $cat ) {
	foreach ( $products as $p ) {
		if ( false !== stripos( $p->post_title, 'Reed Diffuser' ) ) {
			wp_set_object_terms( $p->ID, array( (int) $cat->term_id ), 'product_cat', true );
		}
	}
	wp_update_term_count_now( array( $cat->term_id ), 'product_cat' );
}

/* ---------- Elementor: no Google fonts or default colours/typography of its own ---------- */
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );
update_option( 'elementor_google_font', '0' );
update_option( 'elementor_font_display', 'swap' );

$el = function ( $type, $settings = array() ) {
	return array( 'id' => substr( md5( uniqid( '', true ) ), 0, 7 ), 'elType' => 'widget', 'widgetType' => $type, 'settings' => (object) $settings, 'elements' => array() );
};
$box = function ( $children, $classes = 'afz-flush' ) {
	return array(
		'id'       => substr( md5( uniqid( '', true ) ), 0, 7 ),
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array(
			'content_width' => 'full',
			'css_classes'   => $classes,
			'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
			'flex_gap'      => array( 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0', 'isLinked' => true ),
		),
		'elements' => $children,
	);
};
$save = function ( $post_id, $data, $type = 'wp-page' ) {
	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $post_id, '_elementor_template_type', $type );
	update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );
	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $post_id, '_elementor_css' );
	delete_post_meta( $post_id, '_elementor_element_cache' );
};
$m = function ( $name ) use ( $map ) {
	return isset( $map[ $name ] ) ? array( 'id' => (int) $map[ $name ], 'url' => wp_get_attachment_url( (int) $map[ $name ] ) ) : array( 'id' => 0, 'url' => '' );
};

/* ---------- homepage ---------- */
if ( in_array( '--home', $opts, true ) ) {
	$home = (int) get_option( 'page_on_front' );
	if ( $home ) {
		// keep the previous layout as a revision before replacing it
		wp_save_post_revision( $home );
		$data = array(
			$box( array(
				$el( 'afz-hero', array( 'video' => $m( 'hero-1080.mp4' ), 'video_m' => $m( 'hero-720.mp4' ), 'poster' => $m( 'hero-poster.webp' ) ) ),
				$el( 'afz-marquee' ),
				$el( 'afz-manifesto', array( 'float_a' => $m( 'candle.webp' ), 'float_b' => $m( 'diffuser.webp' ), 'float_c' => $m( 'refill.webp' ), 'float_d' => $m( 'holder-gold.webp' ) ) ),
				$el( 'afz-ritual', array( 'candle' => $m( 'candle.webp' ) ) ),
				$el( 'afz-categories' ),
			) ),
			$box( array( $el( 'afz-scent-library' ), $el( 'afz-products' ) ), 'afz-sheet light' ),
			$box( array( $el( 'afz-feature', array( 'video' => $m( 'repel-1080.mp4' ), 'video_m' => $m( 'repel-720.mp4' ), 'poster' => $m( 'repel-poster.webp' ), 'pack' => $m( 'repellent.webp' ) ) ) ) ),
			$box( array( $el( 'afz-story', array( 'image' => $m( 'founder-synita.webp' ) ) ) ), 'afz-sheet light' ),
			$box( array( $el( 'afz-reviews' ) ) ),
			$box( array( $el( 'afz-gift', array( 'image' => $m( 'gift-card.webp' ) ) ) ), 'afz-sheet light' ),
			$box( array( $el( 'afz-newsletter' ) ) ),
		);
		$save( $home, $data );
		update_post_meta( $home, '_wp_page_template', 'elementor_header_footer' );
		$log( "homepage $home rebuilt" );
	}
}

/* ---------- kit, header, footer ---------- */
if ( in_array( '--theme', $opts, true ) ) {
	$kit_id = (int) get_option( 'afz_kit_id' );
	if ( ! $kit_id || ! get_post( $kit_id ) ) {
		$kit_id = wp_insert_post( array( 'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'AWRELIEF Kit' ) );
		wp_set_object_terms( $kit_id, 'kit', 'elementor_library_type' );
		update_option( 'afz_kit_id', $kit_id );
	}
	update_post_meta( $kit_id, '_elementor_template_type', 'kit' );
	update_post_meta( $kit_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $kit_id, '_elementor_data', '[]' );
	update_post_meta( $kit_id, '_elementor_page_settings', array(
		'system_colors'         => array(
			array( '_id' => 'primary', 'title' => 'Gold', 'color' => '#C9A04A' ),
			array( '_id' => 'secondary', 'title' => 'Ink', 'color' => '#0C0B09' ),
			array( '_id' => 'text', 'title' => 'Ivory', 'color' => '#EFE6D6' ),
			array( '_id' => 'accent', 'title' => 'Parchment', 'color' => '#EFE7DA' ),
		),
		'custom_colors'         => array(
			array( '_id' => 'afzgoldlo', 'title' => 'Deep gold', 'color' => '#8E6420' ),
			array( '_id' => 'afzgoldhi', 'title' => 'Light gold', 'color' => '#F3DC9A' ),
			array( '_id' => 'afztext', 'title' => 'Text on parchment', 'color' => '#1B1712' ),
		),
		'system_typography'     => array(
			array( '_id' => 'primary', 'title' => 'Display', 'typography_typography' => 'custom', 'typography_font_family' => 'Bodoni Moda', 'typography_font_weight' => '500' ),
			array( '_id' => 'secondary', 'title' => 'Display italic', 'typography_typography' => 'custom', 'typography_font_family' => 'Bodoni Moda', 'typography_font_weight' => '500', 'typography_font_style' => 'italic' ),
			array( '_id' => 'text', 'title' => 'Body', 'typography_typography' => 'custom', 'typography_font_family' => 'Hanken Grotesk', 'typography_font_weight' => '300' ),
			array( '_id' => 'accent', 'title' => 'Label', 'typography_typography' => 'custom', 'typography_font_family' => 'DM Mono', 'typography_font_weight' => '400' ),
		),
		'container_width'       => array( 'unit' => 'px', 'size' => 1560, 'sizes' => array() ),
		'space_between_widgets' => array( 'column' => '0', 'row' => '0', 'isLinked' => true, 'unit' => 'px', 'size' => 0 ),
		'container_padding'     => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
		'site_name'             => 'AWRELIEF',
		'site_description'      => 'Feel at peace',
	) );
	update_option( 'elementor_active_kit', $kit_id );
	$log( "kit $kit_id active" );

	foreach ( array( 'header' => 'afz-header', 'footer' => 'afz-footer' ) as $type => $widget ) {
		$opt = 'afz_' . $type . '_tpl';
		$id  = (int) get_option( $opt );
		if ( ! $id || ! get_post( $id ) ) {
			$id = wp_insert_post( array( 'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'AWRELIEF ' . ucfirst( $type ) ) );
			update_option( $opt, $id );
		}
		wp_set_object_terms( $id, $type, 'elementor_library_type' );
		$save( $id, array( $box( array( $el( $widget, 'header' === $type ? array( 'feat_image' => $m( 'life-sofa.webp' ) ) : array() ) ) ) ), $type );
		update_post_meta( $id, '_elementor_conditions', array( 'include/general' ) );
		$log( "$type template $id" );
	}
	if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
		\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate();
	}
	// From the command line Pro may not have its document types loaded, so write the cache directly too.
	$cache = get_option( 'elementor_pro_theme_builder_conditions', array() );
	$cache = is_array( $cache ) ? $cache : array();
	foreach ( array( 'header', 'footer' ) as $type ) {
		$cache[ $type ] = array( (int) get_option( 'afz_' . $type . '_tpl' ) => array( 'include/general' ) );
	}
	update_option( 'elementor_pro_theme_builder_conditions', $cache );
	$log( 'theme builder conditions saved' );
}

flush_rewrite_rules();
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
$log( 'done' );
