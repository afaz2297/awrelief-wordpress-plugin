<?php
/**
 * One-off content updates that ship with the plugin. Each step runs once, the first time an
 * administrator opens the dashboard after the plugin files are updated, so the review copy and
 * the live site get the same change without a database import.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Updates {

	const OPTION = 'afz_content_updates';

	/** Step name => method. New steps go at the end. */
	const STEPS = array(
		'2026-11-scent-cutouts'   => 'scent_cutouts',
		'2026-11-delivery-uk-only' => 'delivery_uk_only',
	);

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'run' ) );
	}

	public static function run() {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) || ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$done = (array) get_option( self::OPTION, array() );
		foreach ( self::STEPS as $step => $method ) {
			if ( isset( $done[ $step ] ) ) {
				continue;
			}
			// mark first, so a step that fails half way is never repeated on every page load
			$done[ $step ] = array( 'time' => time(), 'result' => 'started' );
			update_option( self::OPTION, $done, false );
			$done[ $step ]['result'] = call_user_func( array( __CLASS__, $method ) );
			update_option( self::OPTION, $done, false );
		}
	}

	/**
	 * The scent-notes pictures had a white background. The cut-out versions ship in
	 * assets/img/scents/; every product that used an old picture gets the new one in its place.
	 * The old pictures stay in the media library.
	 */
	private static function scent_cutouts() {
		$map = array(
			'website-scents.png'     => 'scent-notes-spice.webp',
			'website-scents-2.png'   => 'scent-notes-chill.webp',
			'website-scents-2-1.png' => 'scent-notes-snuggles.webp',
			'website-scents-3.png'   => 'scent-notes-vines.webp',
			'website-scents-4.png'   => 'scent-notes-cashmere.webp',
		);
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;
		$log = array();
		foreach ( $map as $old => $new ) {
			$old_ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND ( meta_value = %s OR meta_value LIKE %s )",
				$old,
				'%/' . $wpdb->esc_like( $old )
			) ) );
			$src = AFZ_DIR . 'assets/img/scents/' . $new;
			if ( ! $old_ids || ! file_exists( $src ) ) {
				$log[ $old ] = 'skipped';
				continue;
			}
			// products that show one of the old pictures
			$products = array();
			foreach ( $old_ids as $old_id ) {
				$like       = '%' . $wpdb->esc_like( (string) $old_id ) . '%';
				$candidates = $wpdb->get_col( $wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE ( meta_key = '_product_image_gallery' AND meta_value LIKE %s ) OR ( meta_key = '_thumbnail_id' AND meta_value = %s )",
					$like,
					(string) $old_id
				) );
				foreach ( $candidates as $pid ) {
					$products[ (int) $pid ] = true;
				}
			}
			if ( ! $products ) {
				$log[ $old ] = 'not used';
				continue;
			}
			$tmp = wp_tempnam( $new );
			copy( $src, $tmp );
			$new_id = media_handle_sideload( array( 'name' => $new, 'tmp_name' => $tmp ), 0, ucwords( str_replace( '-', ' ', basename( $new, '.webp' ) ) ) );
			if ( is_wp_error( $new_id ) ) {
				wp_delete_file( $tmp );
				$log[ $old ] = 'upload failed: ' . $new_id->get_error_message();
				continue;
			}
			update_post_meta( $new_id, '_wp_attachment_image_alt', get_post_meta( $old_ids[0], '_wp_attachment_image_alt', true ) );
			$changed = 0;
			foreach ( array_keys( $products ) as $pid ) {
				$product = wc_get_product( $pid );
				if ( ! $product ) {
					continue;
				}
				$touched = false;
				$gallery = array_map( 'intval', $product->get_gallery_image_ids() );
				foreach ( $gallery as $i => $id ) {
					if ( in_array( $id, $old_ids, true ) ) {
						$gallery[ $i ] = (int) $new_id;
						$touched       = true;
					}
				}
				if ( in_array( (int) $product->get_image_id(), $old_ids, true ) ) {
					$product->set_image_id( $new_id );
					$touched = true;
				}
				if ( $touched ) {
					$product->set_gallery_image_ids( array_values( array_unique( $gallery ) ) );
					$product->save();
					++$changed;
				}
			}
			$log[ $old ] = $new_id . ' on ' . $changed . ' products';
		}
		return $log;
	}

	/** The store only delivers in the UK: drop the Europe card from the Delivery page. */
	private static function delivery_uk_only() {
		$page = get_page_by_path( 'delivery' );
		if ( ! $page ) {
			return 'no page';
		}
		$data = json_decode( (string) get_post_meta( $page->ID, '_elementor_data', true ), true );
		if ( ! is_array( $data ) ) {
			return 'no layout';
		}
		$removed = 0;
		$walk    = function ( &$els ) use ( &$walk, &$removed ) {
			foreach ( $els as &$el ) {
				if ( isset( $el['widgetType'] ) && 'afz-cards' === $el['widgetType'] && ! empty( $el['settings']['items'] ) ) {
					$before                  = count( $el['settings']['items'] );
					$el['settings']['items'] = array_values( array_filter( $el['settings']['items'], function ( $item ) {
						return 'international' !== strtolower( trim( $item['label'] ?? '' ) ) && false === stripos( $item['title'] ?? '', 'europe' );
					} ) );
					$gone = $before - count( $el['settings']['items'] );
					if ( $gone ) {
						$removed += $gone;
						if ( count( $el['settings']['items'] ) < (int) ( $el['settings']['columns'] ?? 4 ) ) {
							$el['settings']['columns'] = (string) max( 2, count( $el['settings']['items'] ) );
						}
					}
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};
		$walk( $data );
		if ( ! $removed ) {
			return 'nothing to remove';
		}
		update_post_meta( $page->ID, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		delete_post_meta( $page->ID, '_elementor_element_cache' );
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		return 'removed ' . $removed;
	}
}
