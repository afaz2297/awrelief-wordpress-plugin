<?php
/**
 * One-off content updates that ship with the plugin. Each step runs once, the first time an
 * administrator opens the dashboard after the plugin files are updated, so the review copy and
 * the live site get the same change without a database import.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Updates {

	const OPTION = 'afz_content_updates';

	/**
	 * Step name => method. New steps go at the end.
	 * A new set of scent pictures: put the files in assets/img/scents/, update SCENTS and rename
	 * the scent step (e.g. -v3) so it runs again on sites that already had the earlier set.
	 */
	const STEPS = array(
		'2026-11-scent-images-v3'  => 'scent_images',
		'2026-11-delivery-uk-only' => 'delivery_uk_only',
		'2026-11-site-name'        => 'site_name',
		'2026-11-scent-library-v3' => 'scent_library', // v3: waits for the scents, so a fresh go-live does not skip them
		'2026-11-care-repellent'   => 'care_repellent',
		'2026-11-tcs-email'        => 'tcs_email',
		'2026-11-hello-world'      => 'hello_world',
		'2026-11-tcs-gift-cards'   => 'tcs_gift_cards',
	);

	/** Gift card terms the client asked for (27 Nov). tools/pages.php adds them too when it builds the page. */
	const GIFT_TERMS = '<h2>17. Gift cards</h2><ol><li>AWRELIEF gift cards are sent electronically by email. There is no physical delivery.</li><li>Gift card purchases are non-refundable once purchased and cannot be exchanged for cash.</li><li>This does not affect your statutory rights.</li></ol>';

	/** The Terms text with the gift card section at the end and a pointer to it under Returns. */
	public static function with_gift_card_terms( $html ) {
		if ( false !== stripos( $html, 'Gift cards</h2>' ) ) {
			return $html;
		}
		$html = preg_replace( '#(\d+\.\s*Returns</h2>.*?<ol[^>]*>)#s', '$1<li>Gift cards are non-refundable once purchased (see clause 17).</li>', $html, 1 );
		// before the closing tags the old page left at the end, so it sits beside section 16
		return preg_replace( '#((?:\s*</div>)*\s*)$#', self::GIFT_TERMS . '$1', $html, 1 );
	}

	/**
	 * Scent => current picture in assets/img/scents/ and every earlier file it replaces.
	 * A plain file name matches in any upload folder; a name with a folder ("2024/03/1.png")
	 * matches only that file, for generic names that other uploads share.
	 */
	const SCENTS = array(
		'heaven'              => array( 'scent-notes-heaven-v3.webp', array( '2024/03/1.png' ) ),
		'cozy'                => array( 'scent-notes-cozy-v3.webp', array( '2024/03/2.png' ) ),
		'tropical-samba'      => array( 'scent-notes-tropical-samba-v3.webp', array( '2024/03/3.png' ) ),
		'lime-basil-mandarin' => array( 'scent-notes-lime-basil-mandarin-v3.webp', array( '2024/03/4.png' ) ),
		'rose-trilogy'        => array( 'scent-notes-rose-trilogy-v3.webp', array( '2024/03/5.png' ) ),
		'seychelles'          => array( 'scent-notes-seychelles-v3.webp', array( '2024/03/6.png' ) ),
		'serene'              => array( 'scent-notes-serene-v3.webp', array( '2024/03/7.png' ) ),
		'apple'               => array( 'scent-notes-apple-v3.webp', array( '2024/03/9.png' ) ),
		'spice'               => array( 'scent-notes-spice-v3.webp', array( 'website-scents.png', 'scent-notes-spice.webp', 'scent-notes-spice-v2.webp' ) ),
		'chill'               => array( 'scent-notes-chill-v3.webp', array( 'website-scents-2.png', 'scent-notes-chill.webp', 'scent-notes-chill-v2.webp' ) ),
		'snuggles'            => array( 'scent-notes-snuggles-v3.webp', array( 'website-scents-2-1.png', 'scent-notes-snuggles.webp', 'scent-notes-snuggles-v2.webp' ) ),
		'vines'               => array( 'scent-notes-vines-v3.webp', array( 'website-scents-3.png', 'scent-notes-vines.webp', 'scent-notes-vines-v2.webp' ) ),
		'cashmere'            => array( 'scent-notes-cashmere-v3.webp', array( 'website-scents-4.png', 'scent-notes-cashmere.webp', 'scent-notes-cashmere-v2.webp' ) ),
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
			if ( isset( $done[ $step ] ) && 'running' !== $done[ $step ]['result'] ) {
				continue;
			}
			if ( ! isset( $done[ $step ] ) ) {
				$done[ $step ] = array( 'time' => time(), 'result' => 'running' );
				update_option( self::OPTION, $done, false );
			}
			$result = call_user_func( array( __CLASS__, $method ), $step );
			if ( null === $result ) {
				return; // more to do: carries on at the next dashboard page load
			}
			$done[ $step ]['result'] = $result;
			update_option( self::OPTION, $done, false );
		}
	}

	/**
	 * Scent-notes pictures with a transparent background (the originals were on white). Every
	 * product that shows an earlier version gets the current file from assets/img/scents/ in its
	 * place. Earlier versions stay in the media library.
	 */
	private static function scent_images( $step ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;
		// each picture is uploaded and resized, so a few per page load keeps under the host's time limit;
		// progress is saved before each one, so a time-out never repeats or skips a picture
		$key    = self::OPTION . '_' . $step;
		$log    = (array) get_option( $key, array() );
		$budget = 4;
		foreach ( self::SCENTS as $scent => $files ) {
			if ( isset( $log[ $scent ] ) ) {
				continue;
			}
			if ( $budget-- <= 0 ) {
				return null;
			}
			$log[ $scent ] = 'started';
			update_option( $key, $log, false );
			list( $new, $olds ) = $files;
			$old_ids = array();
			foreach ( $olds as $old ) {
				$old_ids = array_merge( $old_ids, array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND ( meta_value = %s OR meta_value LIKE %s )",
					$old,
					'%/' . $wpdb->esc_like( $old )
				) ) ) );
			}
			$old_ids = array_values( array_unique( $old_ids ) );
			$old     = $scent;
			$src     = AFZ_DIR . 'assets/img/scents/' . $new;
			if ( ! $old_ids || ! file_exists( $src ) ) {
				$log[ $old ] = 'skipped';
				update_option( $key, $log, false );
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
				update_option( $key, $log, false );
				continue;
			}
			$tmp = wp_tempnam( $new );
			copy( $src, $tmp );
			$new_id = media_handle_sideload( array( 'name' => $new, 'tmp_name' => $tmp ), 0, ucwords( str_replace( '-', ' ', preg_replace( '/-v\d+$/', '', pathinfo( $new, PATHINFO_FILENAME ) ) ) ) );
			if ( is_wp_error( $new_id ) ) {
				wp_delete_file( $tmp );
				$log[ $old ] = 'upload failed: ' . $new_id->get_error_message();
				update_option( $key, $log, false );
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
			update_option( $key, $log, false );
		}
		return $log;
	}

	/** Uploads a file that ships in the plugin to the media library. Returns the attachment ID or WP_Error. */
	private static function sideload( $src, $name, $title, $alt ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $name );
		copy( $src, $tmp );
		$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $title );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return $id;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		return $id;
	}

	/** Saves an Elementor layout and clears the caches that hold the old one. */
	private static function save_layout( $post_id, $json ) {
		update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );
		delete_post_meta( $post_id, '_elementor_element_cache' );
		delete_post_meta( $post_id, '_elementor_css' );
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Homepage Scent Library: each scent's ingredient picture, trimmed and centred on the same
	 * square so the cards look alike (client, 25 Nov). Files in assets/img/scents/library/.
	 */
	private static function scent_library( $step ) {
		// on a site going live the plugin is switched on before Go live step 1 makes the scents: wait for them
		if ( ! taxonomy_exists( AFZ_Scents::TAX ) || ! get_term_by( 'slug', 'heaven', AFZ_Scents::TAX ) ) {
			return null;
		}
		$key    = self::OPTION . '_' . $step;
		$log    = (array) get_option( $key, array() );
		$map    = (array) get_option( 'afz_seed_media', array() );
		$budget = 4;
		foreach ( array_keys( self::SCENTS ) as $slug ) {
			if ( isset( $log[ $slug ] ) ) {
				continue;
			}
			$term = get_term_by( 'slug', $slug, AFZ_Scents::TAX );
			$src  = AFZ_DIR . 'assets/img/scents/library/' . $slug . '.webp';
			// already has this picture (sites that ran the earlier version of this step)
			$has = $term ? (int) get_term_meta( $term->term_id, 'afz_image', true ) : 0;
			if ( $has && 0 === strpos( basename( (string) get_attached_file( $has ) ), 'scent-library-' . $slug ) ) {
				$log[ $slug ] = (string) $has;
				update_option( $key, $log, false );
				continue;
			}
			if ( $budget-- <= 0 ) {
				return null;
			}
			$log[ $slug ] = 'started';
			update_option( $key, $log, false );
			if ( ! $term || ! file_exists( $src ) ) {
				$log[ $slug ] = 'skipped';
				update_option( $key, $log, false );
				continue;
			}
			$id = self::sideload( $src, 'scent-library-' . $slug . '.webp', $term->name . ' scent', $term->name . ' scent ingredients' );
			if ( is_wp_error( $id ) ) {
				$log[ $slug ] = 'upload failed: ' . $id->get_error_message();
				update_option( $key, $log, false );
				continue;
			}
			update_term_meta( $term->term_id, 'afz_image', (string) $id );
			$map[ $slug . '.webp' ] = $id;
			update_option( 'afz_seed_media', $map, false );
			$log[ $slug ] = (string) $id;
			update_option( $key, $log, false );
		}
		return $log;
	}

	/** Care Guide: the client's new insect repellent photo (25 Nov) in place of the old one. */
	private static function care_repellent() {
		$page = get_page_by_path( 'care-guide' );
		if ( ! $page ) {
			return 'no page';
		}
		$data = json_decode( (string) get_post_meta( $page->ID, '_elementor_data', true ), true );
		if ( ! is_array( $data ) ) {
			return 'no layout';
		}
		$map = (array) get_option( 'afz_seed_media', array() );
		$old = (int) ( $map['care-repellent.webp'] ?? 0 );
		$id  = self::sideload( AFZ_DIR . 'assets/img/care-repellent-v2.webp', 'care-repellent-garden.webp', 'AWRELIEF insect repellent in a sunlit garden', 'AWRELIEF natural insect repellent 100ml among citrus, mint and frangipani in a sunlit garden' );
		if ( is_wp_error( $id ) ) {
			return 'upload failed: ' . $id->get_error_message();
		}
		$url     = wp_get_attachment_url( $id );
		$swapped = 0;
		$walk    = function ( &$node ) use ( &$walk, $old, $id, $url, &$swapped ) {
			if ( ! is_array( $node ) ) {
				return;
			}
			if ( isset( $node['url'], $node['id'] ) && ( ( $old && (int) $node['id'] === $old ) || 0 === strpos( basename( (string) $node['url'] ), 'care-repellent' ) ) ) {
				$node['id']  = $id;
				$node['url'] = $url;
				++$swapped;
				return;
			}
			foreach ( $node as &$child ) {
				$walk( $child );
			}
		};
		$walk( $data );
		$map['care-repellent.webp'] = $id;
		update_option( 'afz_seed_media', $map, false );
		if ( ! $swapped ) {
			return 'uploaded ' . $id . ', no old picture on the page';
		}
		self::save_layout( $page->ID, wp_json_encode( $data ) );
		return $id . ' on care guide';
	}

	/**
	 * Terms & Conditions: copies of the site rewrote the domain inside the contact address
	 * (hello@localhost, hello@awrelief.afazalif.com). Put the real address back.
	 */
	private static function tcs_email() {
		$page = get_page_by_path( 'tcs' );
		if ( ! $page ) {
			return 'no page';
		}
		$json  = (string) get_post_meta( $page->ID, '_elementor_data', true );
		$email = (string) ( AFZ_Settings::get()['email'] ?? 'hello@awrelief.com' );
		$fixed = preg_replace( '#\bhello@(?!awrelief\.com\b)[a-z0-9.-]*[a-z0-9]#i', $email, $json, -1, $count );
		if ( ! $count ) {
			return 'nothing to fix';
		}
		self::save_layout( $page->ID, $fixed );
		return 'fixed ' . $count;
	}

	private static function tcs_gift_cards() {
		$page = get_page_by_path( 'tcs' );
		$data = $page ? json_decode( (string) get_post_meta( $page->ID, '_elementor_data', true ), true ) : null;
		if ( ! is_array( $data ) ) {
			return 'no page';
		}
		$done = false;
		$walk = function ( &$els ) use ( &$walk, &$done ) {
			foreach ( $els as &$e ) {
				if ( ! $done && 'afz-rich' === ( $e['widgetType'] ?? '' ) && ! empty( $e['settings']['body'] ) && false !== strpos( $e['settings']['body'], '</h2>' ) ) {
					$new  = self::with_gift_card_terms( $e['settings']['body'] );
					$done = $new !== $e['settings']['body'] ? 'added' : 'already there';
					$e['settings']['body'] = $new;
				}
				if ( ! empty( $e['elements'] ) ) {
					$walk( $e['elements'] );
				}
			}
		};
		$walk( $data );
		if ( 'added' === $done ) {
			self::save_layout( $page->ID, wp_json_encode( $data ) );
		}
		return $done ? $done : 'no terms text';
	}

	/** Emails say "Your {site_title} order…": the store had no site name, so give it one if it is still empty. */
	private static function site_name() {
		if ( '' !== trim( (string) get_option( 'blogname' ) ) ) {
			return 'kept';
		}
		update_option( 'blogname', 'AWRELIEF' );
		return 'set';
	}

	/**
	 * WordPress's sample "Hello world!" post was still published, so it sat in the sitemap and in
	 * search results. Moved to drafts only while it is the untouched sample.
	 */
	private static function hello_world() {
		$post = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( ! $post || 'publish' !== $post->post_status || false === strpos( $post->post_content, 'Welcome to WordPress' ) ) {
			return 'none';
		}
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
		return 'drafted';
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
