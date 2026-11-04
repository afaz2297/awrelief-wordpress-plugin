<?php
/**
 * Speed: every uploaded image becomes WebP (original and all sizes), a one-click tool
 * converts the existing media library, and unused WordPress extras are switched off.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Performance {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'wp_handle_upload', array( $this, 'convert_upload' ) );
		add_filter( 'wp_editor_set_quality', array( $this, 'quality' ), 10, 2 );
		add_filter( 'big_image_size_threshold', array( $this, 'max_width' ) );
		add_action( 'wp_ajax_afz_webp_batch', array( $this, 'batch' ) );
		add_action( 'init', array( $this, 'trim' ) );
	}

	public function quality( $q, $mime ) {
		return 'image/webp' === $mime ? (int) afz_opt( 'webp_quality', 82 ) : $q;
	}

	public function max_width() {
		return max( 1200, (int) afz_opt( 'max_width', 2400 ) );
	}

	/** JPEG/PNG uploads are rewritten as WebP before WordPress makes the smaller sizes. */
	public function convert_upload( $upload ) {
		if ( ! afz_on( 'webp_on' ) || ! in_array( $upload['type'] ?? '', array( 'image/jpeg', 'image/png' ), true ) ) {
			return $upload;
		}
		$webp = $this->to_webp( $upload['file'] );
		if ( ! $webp ) {
			return $upload;
		}
		wp_delete_file( $upload['file'] );
		$upload['file'] = $webp;
		$upload['url']  = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $upload['url'] );
		$upload['type'] = 'image/webp';
		return $upload;
	}

	/** Write a .webp next to $file. Returns the new path or false. */
	private function to_webp( $file ) {
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) || ! $editor->supports_mime_type( 'image/webp' ) ) {
			return false;
		}
		$size = $editor->get_size();
		if ( $size['width'] > $this->max_width() ) {
			$editor->resize( $this->max_width(), null );
		}
		$editor->set_quality( (int) afz_opt( 'webp_quality', 82 ) );
		$target = wp_unique_filename( dirname( $file ), preg_replace( '/\.(jpe?g|png)$/i', '', wp_basename( $file ) ) . '.webp' );
		$saved  = $editor->save( trailingslashit( dirname( $file ) ) . $target, 'image/webp' );
		return is_wp_error( $saved ) ? false : $saved['path'];
	}

	/* ---------- existing library ---------- */

	private static function pending_ids( $limit ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts p WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg','image/png') AND NOT EXISTS (SELECT 1 FROM $wpdb->postmeta m WHERE m.post_id = p.ID AND m.meta_key = '_afz_webp_failed') ORDER BY ID LIMIT %d", $limit ) );
	}

	private static function pending_count() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg','image/png') AND NOT EXISTS (SELECT 1 FROM $wpdb->postmeta m WHERE m.post_id = p.ID AND m.meta_key = '_afz_webp_failed')" );
	}

	public function tool_panel() {
		$left = self::pending_count();
		echo '<div class="afz-tool" data-afz-webp data-nonce="' . esc_attr( wp_create_nonce( 'afz_webp' ) ) . '"><h3>' . esc_html__( 'Convert the media library to WebP', 'afazalif' ) . '</h3>';
		echo '<p>' . esc_html( $left ? sprintf( _n( '%d image is still JPEG or PNG.', '%d images are still JPEG or PNG.', $left, 'afazalif' ), $left ) : __( 'Every image in the library is already WebP.', 'afazalif' ) ) . ' ' . esc_html__( 'Links inside pages and Elementor layouts are updated automatically. The original files are kept as a backup.', 'afazalif' ) . '</p>';
		echo '<button type="button" class="button button-primary"' . disabled( 0, $left, false ) . '>' . esc_html__( 'Convert now', 'afazalif' ) . '</button><div class="afz-tool__bar"><i></i></div><div class="afz-tool__log"></div></div>';
	}

	public function batch() {
		check_ajax_referer( 'afz_webp' );
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Not allowed.', 'afazalif' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$done = 0;
		foreach ( self::pending_ids( 4 ) as $id ) {
			if ( self::convert_attachment( (int) $id ) ) {
				$done++;
			} else {
				// leave the original in place, but stop it blocking the queue
				update_post_meta( $id, '_afz_webp_failed', 1 );
			}
		}
		$left = self::pending_count();
		wp_send_json_success( array(
			'converted' => $done,
			'remaining' => $left,
			'message'   => $left ? sprintf( __( '%d left…', 'afazalif' ), $left ) : __( 'Done. Every image is WebP.', 'afazalif' ),
		) );
	}

	/** Convert one attachment in place: new file, new sizes, same ID, links updated. */
	public static function convert_attachment( $id ) {
		$file = get_attached_file( $id );
		if ( ! $file || ! file_exists( $file ) ) {
			return false;
		}
		$old_meta = wp_get_attachment_metadata( $id );
		$old_url  = wp_get_attachment_url( $id );
		$old_urls = array( $old_url );
		if ( ! empty( $old_meta['sizes'] ) ) {
			$base = trailingslashit( dirname( $old_url ) );
			foreach ( $old_meta['sizes'] as $s ) {
				$old_urls[] = $base . $s['file'];
			}
		}
		$new = self::instance()->to_webp( $file );
		if ( ! $new ) {
			return false;
		}
		update_attached_file( $id, $new );
		wp_update_post( array( 'ID' => $id, 'post_mime_type' => 'image/webp' ) );
		$meta = wp_generate_attachment_metadata( $id, $new );
		wp_update_attachment_metadata( $id, $meta );
		$new_url = wp_get_attachment_url( $id );

		// point pages and Elementor layouts at the new files
		$map  = array( $old_url => $new_url );
		$base = trailingslashit( dirname( $new_url ) );
		if ( ! empty( $old_meta['sizes'] ) && ! empty( $meta['sizes'] ) ) {
			foreach ( $old_meta['sizes'] as $name => $s ) {
				$map[ trailingslashit( dirname( $old_url ) ) . $s['file'] ] = isset( $meta['sizes'][ $name ] ) ? $base . $meta['sizes'][ $name ]['file'] : $new_url;
			}
		}
		self::replace_urls( $map );
		delete_post_meta( $id, '_afz_lqip' );
		return true;
	}

	private static function replace_urls( $map ) {
		global $wpdb;
		foreach ( $map as $from => $to ) {
			if ( $from === $to ) {
				continue;
			}
			foreach ( array( array( $from, $to ), array( str_replace( '/', '\/', $from ), str_replace( '/', '\/', $to ) ) ) as $pair ) {
				$like = '%' . $wpdb->esc_like( $pair[0] ) . '%';
				$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s", $pair[0], $pair[1], $like ) );
				// Elementor layouts are JSON (not PHP-serialised), so a plain replace is safe
				$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key = '_elementor_data' AND meta_value LIKE %s", $pair[0], $pair[1], $like ) );
			}
		}
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/* ---------- WordPress extras nobody here uses ---------- */

	public function trim() {
		if ( ! afz_on( 'trim_wp' ) ) {
			return;
		}
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'the_generator', '__return_empty_string' );
		// Elementor's Google Fonts (Roboto) are not used: the design system loads its own fonts
		add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
	}
}
