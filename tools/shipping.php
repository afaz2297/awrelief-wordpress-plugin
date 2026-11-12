<?php
/**
 * UK-only delivery, with the free option listed first. Safe to run again.
 *
 *   php tools/shipping.php <path/to/wp-load.php>
 *
 * Turns off "Europe Standard Delivery", removes Europe from the delivery zone, ships to the UK
 * only, and orders the methods: Free Standard, Standard, Next Day, Free Next Day. WooCommerce
 * shows only the ones that apply, so a basket over £40 sees Free Standard Delivery, then Next Day.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'CLI only' );
}
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require $argv[1];

$order = array( 'free standard' => 1, 'standard' => 2, 'next day' => 3, 'free next day' => 4 );

foreach ( WC_Shipping_Zones::get_zones() as $data ) {
	$zone = new WC_Shipping_Zone( $data['id'] );

	$kept = array_values( array_filter( $zone->get_zone_locations(), function ( $loc ) {
		return ! ( 'continent' === $loc->type && 'EU' === $loc->code );
	} ) );
	if ( count( $kept ) !== count( $zone->get_zone_locations() ) ) {
		$zone->set_locations( array_map( function ( $loc ) {
			return array( 'code' => $loc->code, 'type' => $loc->type );
		}, $kept ) );
		$zone->set_zone_name( 'United Kingdom (UK)' );
		$zone->save();
		echo "zone {$data['id']}: Europe removed\n";
	}

	foreach ( $zone->get_shipping_methods() as $method ) {
		$title = strtolower( trim( preg_replace( '/\s+/', ' ', $method->get_title() ) ) );
		$title = trim( str_replace( array( 'delivery', 'uk ' ), '', $title ) );
		if ( false !== strpos( $title, 'europe' ) ) {
			if ( 'yes' === $method->enabled ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->prefix . 'woocommerce_shipping_zone_methods', array( 'is_enabled' => 0 ), array( 'instance_id' => $method->instance_id ) );
				echo "turned off: {$method->get_title()}\n";
			}
			continue;
		}
		if ( isset( $order[ $title ] ) ) {
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->prefix . 'woocommerce_shipping_zone_methods', array( 'method_order' => $order[ $title ] ), array( 'instance_id' => $method->instance_id ) );
			echo "order {$order[ $title ]}: {$method->get_title()}\n";
		}
	}
}

update_option( 'woocommerce_ship_to_countries', 'specific' );
update_option( 'woocommerce_specific_ship_to_countries', array( 'GB' ) );
WC_Cache_Helper::get_transient_version( 'shipping', true );
echo "ships to: United Kingdom only\n";
