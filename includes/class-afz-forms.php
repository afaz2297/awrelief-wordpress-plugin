<?php
/**
 * Newsletter sign-ups (to MailerLite, with a local copy) and the contact form with stored
 * messages. Replaces the WPForms and MailerLite sign-up form plugins.
 */

defined( 'ABSPATH' ) || exit;

class AFZ_Forms {

	const DB_VERSION = '1';

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wc_ajax_afz_subscribe', array( $this, 'subscribe' ) );
		add_action( 'wc_ajax_afz_contact', array( $this, 'contact' ) );
		add_action( 'wc_ajax_afz_return', array( $this, 'return_request' ) );
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'admin_post_afz_export_subscribers', array( $this, 'export_subscribers' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_install' ) );
	}

	/* ---------- storage ---------- */

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'afz_' . $name;
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		dbDelta( 'CREATE TABLE ' . self::table( 'entries' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form varchar(40) NOT NULL DEFAULT 'contact',
			name varchar(190) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			message longtext NOT NULL,
			meta longtext NULL,
			status varchar(20) NOT NULL DEFAULT 'unread',
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $c;" );
		dbDelta( 'CREATE TABLE ' . self::table( 'subscribers' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			source varchar(40) NOT NULL DEFAULT '',
			synced tinyint(1) NOT NULL DEFAULT 0,
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) $c;" );
		update_option( 'afz_db_version', self::DB_VERSION );
	}

	public function maybe_install() {
		if ( get_option( 'afz_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	public static function count_entries( $status = '' ) {
		global $wpdb;
		$t = self::table( 'entries' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) !== $t ) {
			return 0;
		}
		return (int) ( $status
			? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE status = %s", $status ) ) // phpcs:ignore
			: $wpdb->get_var( "SELECT COUNT(*) FROM $t" ) ); // phpcs:ignore
	}

	public static function count_subscribers() {
		global $wpdb;
		$t = self::table( 'subscribers' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) !== $t ) {
			return 0;
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ); // phpcs:ignore
	}

	/* ---------- spam guards shared by both forms ---------- */

	private function guard( $bucket, $limit = 5 ) {
		check_ajax_referer( 'afz', 'nonce' );
		if ( ! empty( $_POST['website'] ) ) { // honeypot: people never see this field
			wp_send_json_success( array( 'message' => __( 'Thank you!', 'afazalif' ) ) );
		}
		$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$key = 'afz_rl_' . $bucket . '_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= $limit ) {
			wp_send_json_error( array( 'message' => __( 'Too many attempts. Please try again in a few minutes.', 'afazalif' ) ), 429 );
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	}

	/* ---------- newsletter ---------- */

	public static function newsletter_form( $source = 'newsletter', $button = '' ) {
		$button = $button ? $button : __( 'Join', 'afazalif' );
		$id     = 'afz-nl-' . sanitize_key( $source ) . '-' . wp_rand( 100, 999 );
		$code   = trim( (string) afz_opt( 'popup_code', '' ) );
		?>
		<form class="news__form" data-afz-subscribe data-source="<?php echo esc_attr( $source ); ?>" novalidate>
			<label class="sr-only" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Email address', 'afazalif' ); ?></label>
			<input id="<?php echo esc_attr( $id ); ?>" type="email" name="email" required placeholder="<?php esc_attr_e( 'Your email address', 'afazalif' ); ?>" autocomplete="email">
			<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
			<button class="btn btn--gold magnetic" type="submit"><span class="btn__label"><?php echo esc_html( $button ); ?></span><span class="btn__icon">→</span></button>
			<p class="news__done mono" role="status" aria-live="polite"><?php echo $code ? esc_html( sprintf( __( 'Welcome! Your code: %s', 'afazalif' ), $code ) ) : esc_html__( 'Welcome to the circle ✦', 'afazalif' ); ?></p>
		</form>
		<?php
	}

	public function subscribe() {
		$this->guard( 'nl', 5 );
		$email  = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$source = sanitize_key( $_POST['source'] ?? 'site' );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'afazalif' ) ), 400 );
		}
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . self::table( 'subscribers' ) . ' (email, source, created) VALUES (%s, %s, %s)', $email, $source, current_time( 'mysql' ) ) ); // phpcs:ignore
		if ( $this->mailerlite( $email ) ) {
			$wpdb->update( self::table( 'subscribers' ), array( 'synced' => 1 ), array( 'email' => $email ) );
		}
		wp_send_json_success( array( 'code' => (string) afz_opt( 'popup_code', '' ) ) );
	}

	/** Send to MailerLite. Works with new (connect.mailerlite.com) and classic API keys. */
	private function mailerlite( $email ) {
		$key = trim( (string) afz_opt( 'ml_key', '' ) );
		if ( ! $key ) {
			$key = trim( (string) get_option( 'mailerlite_api_key', '' ) );
		}
		if ( ! $key ) {
			$woo = get_option( 'woo_mailerlite_options', array() );
			$key = is_array( $woo ) ? trim( (string) ( $woo['api_key'] ?? '' ) ) : '';
		}
		if ( ! $key ) {
			return false;
		}
		$group = trim( (string) afz_opt( 'ml_group', '' ) );
		if ( strlen( $key ) > 60 ) {
			$body = array( 'email' => $email );
			if ( $group ) {
				$body['groups'] = array( $group );
			}
			$res = wp_remote_post( 'https://connect.mailerlite.com/api/subscribers', array(
				'timeout' => 8,
				'headers' => array( 'Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			) );
		} else {
			$url = $group ? 'https://api.mailerlite.com/api/v2/groups/' . rawurlencode( $group ) . '/subscribers' : 'https://api.mailerlite.com/api/v2/subscribers';
			$res = wp_remote_post( $url, array(
				'timeout' => 8,
				'headers' => array( 'X-MailerLite-ApiKey' => $key, 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'email' => $email ) ),
			) );
		}
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		return $code >= 200 && $code < 300;
	}

	/* ---------- contact form ---------- */

	public function contact() {
		$this->guard( 'ct', 4 );
		$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$order   = sanitize_text_field( wp_unslash( $_POST['order'] ?? '' ) );
		if ( '' === $name || ! is_email( $email ) || strlen( $message ) < 3 ) {
			wp_send_json_error( array( 'message' => __( 'Please add your name, a valid email and a message.', 'afazalif' ) ), 400 );
		}
		global $wpdb;
		$wpdb->insert( self::table( 'entries' ), array(
			'form'    => 'contact',
			'name'    => $name,
			'email'   => $email,
			'subject' => $subject,
			'message' => $message,
			'meta'    => wp_json_encode( array( 'order' => $order, 'page' => esc_url_raw( wp_get_referer() ) ) ),
			'created' => current_time( 'mysql' ),
		) );
		$to = afz_opt( 'email', get_option( 'admin_email' ) );
		wp_mail(
			$to,
			sprintf( '[AWRELIEF] %s', $subject ? $subject : __( 'New message', 'afazalif' ) ),
			sprintf( "%s <%s>\n%s\n\n%s", $name, $email, $order ? 'Order: ' . $order : '', $message ),
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);
		wp_send_json_success( array( 'message' => __( 'Thank you, we\'ll reply within one working day.', 'afazalif' ) ) );
	}

	/* ---------- return requests ---------- */

	public function return_request() {
		$this->guard( 'rt', 4 );
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email  = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$order  = sanitize_text_field( wp_unslash( $_POST['order'] ?? '' ) );
		$items  = sanitize_textarea_field( wp_unslash( $_POST['items'] ?? '' ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
		$notes  = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$number = absint( preg_replace( '/\D/', '', $order ) );
		if ( '' === $name || ! is_email( $email ) || ! $number || '' === $items ) {
			wp_send_json_error( array( 'message' => __( 'Please add your name, email, order number and the items you are returning.', 'afazalif' ) ), 400 );
		}
		// the result is only for the store: telling visitors would let them probe order numbers
		$found   = wc_get_order( $number );
		$matches = $found instanceof WC_Order && 0 === strcasecmp( (string) $found->get_billing_email(), $email );
		$check   = $matches ? __( 'Order and email match', 'afazalif' ) : __( 'Could not match this order and email, please check', 'afazalif' );
		$subject = sprintf( __( 'Return request, order #%d', 'afazalif' ), $number );
		$message = sprintf( "%s\n\n%s: %s\n%s: %s\n%s: %s", $check, __( 'Items', 'afazalif' ), $items, __( 'Reason', 'afazalif' ), $reason, __( 'Notes', 'afazalif' ), $notes );
		global $wpdb;
		$wpdb->insert( self::table( 'entries' ), array(
			'form'    => 'return',
			'name'    => $name,
			'email'   => $email,
			'subject' => $subject,
			'message' => $message,
			'meta'    => wp_json_encode( array( 'order' => $number, 'matches' => $matches ) ),
			'created' => current_time( 'mysql' ),
		) );
		if ( $matches ) {
			$found->add_order_note( sprintf( __( 'Return requested by the customer. Items: %1$s. Reason: %2$s.', 'afazalif' ), $items, $reason ) );
		}
		wp_mail(
			afz_opt( 'email', get_option( 'admin_email' ) ),
			'[AWRELIEF] ' . $subject,
			sprintf( "%s <%s>\n\n%s", $name, $email, $message ),
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);
		wp_send_json_success( array( 'message' => __( 'Thank you, we have your return request and will email you the next steps within one working day.', 'afazalif' ) ) );
	}

	/* ---------- dashboard screens ---------- */

	public function menu() {
		$unread = self::count_entries( 'unread' );
		$badge  = $unread ? ' <span class="awaiting-mod">' . (int) $unread . '</span>' : '';
		add_submenu_page( AFZ_Settings::SLUG, __( 'Messages', 'afazalif' ), __( 'Messages', 'afazalif' ) . $badge, 'manage_options', 'afz-entries', array( $this, 'entries_page' ) );
		add_submenu_page( AFZ_Settings::SLUG, __( 'Subscribers', 'afazalif' ), __( 'Subscribers', 'afazalif' ), 'manage_options', 'afz-subscribers', array( $this, 'subscribers_page' ) );
	}

	private function screen_head( $title, $intro ) {
		echo '<div class="afz-admin"><header class="afz-admin__hero"><div class="afz-admin__brand"><span class="afz-admin__emblem" aria-hidden="true"></span><div><p class="afz-admin__eyebrow">AWRELIEF</p><h1>' . esc_html( $title ) . '</h1></div></div></header><div class="afz-admin__panel" style="margin-top:22px"><p class="afz-admin__intro">' . esc_html( $intro ) . '</p>';
	}

	public function entries_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$t = self::table( 'entries' );
		if ( isset( $_GET['view'] ) ) {
			$id = absint( $_GET['view'] );
			$wpdb->update( $t, array( 'status' => 'read' ), array( 'id' => $id ) );
		}
		if ( isset( $_GET['delete'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'afz-del-' . absint( $_GET['delete'] ) ) ) {
			$wpdb->delete( $t, array( 'id' => absint( $_GET['delete'] ) ) );
		}
		$rows = $wpdb->get_results( "SELECT * FROM $t ORDER BY created DESC LIMIT 200" ); // phpcs:ignore
		$this->screen_head( __( 'Messages', 'afazalif' ), __( 'Everything sent through the contact and return forms. Each message is also emailed to the store address.', 'afazalif' ) );
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Received', 'afazalif' ) . '</th><th>' . esc_html__( 'From', 'afazalif' ) . '</th><th>' . esc_html__( 'Subject', 'afazalif' ) . '</th><th>' . esc_html__( 'Message', 'afazalif' ) . '</th><th></th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No messages yet.', 'afazalif' ) . '</td></tr>';
		}
		foreach ( $rows as $r ) {
			$open = isset( $_GET['view'] ) && absint( $_GET['view'] ) === (int) $r->id;
			$del  = wp_nonce_url( add_query_arg( array( 'page' => 'afz-entries', 'delete' => $r->id ), admin_url( 'admin.php' ) ), 'afz-del-' . $r->id );
			echo '<tr class="' . ( 'unread' === $r->status ? 'afz-unread' : '' ) . '"><td>' . esc_html( mysql2date( 'j M Y, H:i', $r->created ) ) . '</td><td>' . esc_html( $r->name ) . '<br><a href="mailto:' . esc_attr( $r->email ) . '">' . esc_html( $r->email ) . '</a></td><td>' . esc_html( $r->subject ) . '</td><td>' . ( $open ? nl2br( esc_html( $r->message ) ) : esc_html( wp_trim_words( $r->message, 14 ) ) . ' <a href="' . esc_url( add_query_arg( array( 'page' => 'afz-entries', 'view' => $r->id ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Read', 'afazalif' ) . '</a>' ) . '</td><td><a class="button-link-delete" href="' . esc_url( $del ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this message?', 'afazalif' ) ) . '\')">' . esc_html__( 'Delete', 'afazalif' ) . '</a></td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	public function subscribers_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table( 'subscribers' ) . ' ORDER BY created DESC LIMIT 500' ); // phpcs:ignore
		$this->screen_head( __( 'Subscribers', 'afazalif' ), __( 'Newsletter sign-ups from the website. “Sent to MailerLite” shows whether MailerLite received each one.', 'afazalif' ) );
		echo '<p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=afz_export_subscribers' ), 'afz-export' ) ) . '">' . esc_html__( 'Download CSV', 'afazalif' ) . '</a></p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Email', 'afazalif' ) . '</th><th>' . esc_html__( 'Signed up from', 'afazalif' ) . '</th><th>' . esc_html__( 'Sent to MailerLite', 'afazalif' ) . '</th><th>' . esc_html__( 'Date', 'afazalif' ) . '</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No sign-ups yet.', 'afazalif' ) . '</td></tr>';
		}
		foreach ( $rows as $r ) {
			echo '<tr><td>' . esc_html( $r->email ) . '</td><td>' . esc_html( $r->source ) . '</td><td>' . ( $r->synced ? '✓' : '—' ) . '</td><td>' . esc_html( mysql2date( 'j M Y', $r->created ) ) . '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	public function export_subscribers() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'afz-export' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'afazalif' ) );
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT email, source, synced, created FROM ' . self::table( 'subscribers' ) . ' ORDER BY created DESC', ARRAY_A ); // phpcs:ignore
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=awrelief-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore
		fputcsv( $out, array( 'email', 'source', 'sent_to_mailerlite', 'date' ) );
		foreach ( $rows as $r ) {
			fputcsv( $out, $r );
		}
		exit;
	}
}
