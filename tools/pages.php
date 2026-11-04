<?php
/**
 * Builds the inner pages with the AWRELIEF widgets. Safe to run again: each page is rebuilt,
 * the previous layout is kept as a revision.
 *
 *   php tools/pages.php <path/to/wp-load.php>
 *
 * Text comes from the client's existing pages and questionnaire answers. Site-relative links
 * ("/delivery/") are used so the layouts work on the local copy and on the live domain.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'CLI only' );
}
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
define( 'WP_ADMIN', true );
require $argv[1];
wp_set_current_user( 1 );

$map = get_option( 'afz_seed_media', array() );
$m   = function ( $name ) use ( $map ) {
	return isset( $map[ $name ] ) ? array( 'id' => (int) $map[ $name ], 'url' => wp_get_attachment_url( (int) $map[ $name ] ) ) : array( 'id' => 0, 'url' => '' );
};
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
$page = function ( $slug, $title, $data ) {
	$p  = get_page_by_path( $slug );
	$id = $p ? $p->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ) );
	if ( $p ) {
		wp_save_post_revision( $id );
	}
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	delete_post_meta( $id, '_elementor_css' );
	delete_post_meta( $id, '_elementor_element_cache' );
	echo "page /$slug/ -> $id\n";
	return $id;
};
$old_editor = function ( $id ) {
	$find = function ( $els ) use ( &$find ) {
		foreach ( (array) $els as $e ) {
			if ( ( $e['widgetType'] ?? '' ) === 'text-editor' ) {
				return $e['settings']['editor'] ?? '';
			}
			if ( ! empty( $e['elements'] ) && ( $r = $find( $e['elements'] ) ) ) {
				return $r;
			}
		}
		return '';
	};
	// read from the earliest layout we have (before this tool ever ran)
	$saved = get_post_meta( $id, '_afz_original_editor', true );
	if ( $saved ) {
		return $saved;
	}
	$html = $find( json_decode( (string) get_post_meta( $id, '_elementor_data', true ), true ) );
	update_post_meta( $id, '_afz_original_editor', wp_slash( $html ) );
	return $html;
};
$clean = function ( $html ) {
	$html = preg_replace( '#https?://(?:localhost/awrelief|(?:www\.)?awrelief\.com)/#i', '/', $html );
	$html = preg_replace( '#\s?class="[ps]\d"#', '', $html );
	$html = preg_replace( '#<span>(.*?)</span>#s', '$1', $html );
	$html = str_replace( array( '<p class="p1"> </p>', '<p> </p>' ), '', $html );
	return trim( $html );
};
$hero = function ( $eyebrow, $title, $lede = '', $extra = array() ) use ( $el ) {
	return $el( 'afz-page-hero', array_merge( array( 'eyebrow' => $eyebrow, 'title' => $title, 'lede' => $lede, 'size' => 'compact', 'image' => array( 'id' => 0, 'url' => '' ) ), $extra ) );
};

/* ---------- About ---------- */
$page( 'about-us', 'Our Story', array(
	$box( array(
		$hero( 'Our story · Since 2024', "Created with purpose.\n{Inspired by travel.}", 'Thoughtful products, beautiful fragrances and everyday moments of relief.', array( 'size' => 'tall', 'image' => $m( 'founder-synita.webp' ), 'image_style' => 'photo' ) ),
		$el( 'afz-split', array(
			'image'   => $m( 'repel-poster.webp' ),
			'number'  => '01',
			'eyebrow' => 'Where it all began',
			'title'   => "A personal need,\n{a better answer}",
			'body'    => '<p>AWRELIEF was born from a personal experience and a desire to create better everyday products. Founded by Synita, the idea began during her frequent travels to Ghana.</p><p>Wanting to protect herself from mosquitoes and other biting insects, she found that many conventional insect repellents had harsh chemicals and unpleasant smells. She wanted an alternative that offered protection while smelling fresh and feeling more pleasant to use. Rather than settle for what was available, Synita decided to create her own.</p><p>The first AWRELIEF product was a natural, plant-based insect repellent, formulated without DEET or alcohol.</p>',
			'btn_text' => 'Discover the repellent',
			'btn_link' => array( 'url' => '/product/insect-repellent/' ),
		) ),
	) ),
	$box( array(
		$el( 'afz-split', array(
			'image'   => $m( 'life-reading.webp' ),
			'number'  => '02',
			'side'    => 'right',
			'theme'   => 'light',
			'eyebrow' => 'Growing beyond insect repellent',
			'title'   => "From the road\nto {the home}",
			'body'    => '<p>What began as a personal need soon grew into a bigger ambition: to develop thoughtful products that bring comfort, enjoyment and wellbeing into everyday life.</p><p>Inspired by her passion for beautiful fragrances and creating relaxing environments, Synita expanded AWRELIEF into home fragrance, introducing scented candles, reed diffusers and diffuser refills. Every one is handmade.</p>',
			'quote'   => 'Thoughtful products, beautiful fragrances and everyday moments of relief.',
		) ),
		$el( 'afz-cards', array(
			'eyebrow' => 'What we stand for',
			'title'   => 'The little things {matter}',
			'intro'   => 'At AWRELIEF we believe the little things can make a meaningful difference.',
			'columns' => '4',
			'items'   => array(
				array( '_id' => 'v1', 'label' => '01', 'title' => 'Thoughtful products', 'text' => 'Carefully considering ingredients, quality and everyday needs.' ),
				array( '_id' => 'v2', 'label' => '02', 'title' => 'Beautiful fragrances', 'text' => 'Creating inviting aromas that enhance daily routines and living spaces.' ),
				array( '_id' => 'v3', 'label' => '03', 'title' => 'Vegan & cruelty-free', 'text' => 'Products aligned with our commitment to avoiding animal-derived ingredients and animal testing.' ),
				array( '_id' => 'v4', 'label' => '04', 'title' => 'Everyday wellbeing', 'text' => 'Encouraging moments of comfort, relaxation and enjoyment.' ),
			),
		) ),
	), 'afz-sheet light' ),
	$box( array(
		$el( 'afz-split', array(
			'image'    => $m( 'life-sofa.webp' ),
			'number'   => '03',
			'eyebrow'  => 'Our mission',
			'title'    => "From Synita's travels\n{to your home}",
			'body'     => '<p>Our mission is to create thoughtfully developed products that combine practicality, fragrance and everyday wellbeing.</p><p>Whether it is our original insect repellent accompanying you on your travels or a beautifully scented diffuser transforming your home, AWRELIEF aims to make everyday experiences that little bit better.</p>',
			'btn_text'  => 'Shop the collection',
			'btn_link'  => array( 'url' => '/shop/' ),
			'btn_style' => 'gold',
		) ),
		$el( 'afz-newsletter' ),
	) ),
) );

/* ---------- FAQ ---------- */
$faq = array(
	array( 'Orders & delivery', 'What are the delivery options and times?', '<table><thead><tr><th>Option</th><th>Time</th><th>Cost</th></tr></thead><tbody><tr><td>UK Standard Delivery</td><td>Up to 4 working days, Monday to Saturday</td><td>£3.99, free over £40</td></tr><tr><td>UK Next Day Delivery</td><td>Order before 4pm GMT to receive it the next day, Monday to Saturday</td><td>£6.99, free over £80</td></tr></tbody></table><p>Free delivery is applied automatically at checkout. See our <a href="/delivery/">Delivery</a> page for more.</p>' ),
	array( 'Orders & delivery', 'How do I track my order?', '<p>Once your order is dispatched we email you a Royal Mail tracking number. You can also check your order status on our <a href="/track-order/">Track my order</a> page.</p>' ),
	array( 'Orders & delivery', 'Can I change the delivery address on my order?', '<p>As soon as you place your order, we’re on it. We know you need it fast! Unfortunately this means we are unable to make any changes to your address.</p>' ),
	array( 'Orders & delivery', 'Can my order be cancelled or changed?', '<p>As soon as you place your order, we’re on it. Unfortunately this means we are unable to make any changes, as it’s already being processed (literally within seconds!). You’ll need to place another order or return any unwanted items.</p>' ),
	array( 'Orders & delivery', 'Where is my receipt?', '<p>We are a paperless brand, which is better for the environment. All receipts are emailed to you.</p>' ),
	array( 'Returns & refunds', 'Can I return an order?', '<p>Every customer has the right to return any product within the 14 day period after point of sale.</p><p>The returned product must be in its original, resalable condition to qualify for a full refund. Products which have been opened are not eligible under AWRELIEF’s return policy, for hygiene reasons.</p><p>Please note that original shipping costs are non-refundable, and we do not cover handling charges or return shipping. See our <a href="/returns/">Returns</a> page.</p>' ),
	array( 'Returns & refunds', 'When will I receive my refund?', '<p>A typical refund takes up to 14 working days.</p><p>If you paid with a gift card, store credit or a voucher, a replacement to the value of the refund will be issued. We’ll email you when your parcel arrives back with us, and again when your refund has been processed.</p>' ),
	array( 'Returns & refunds', 'Can I exchange instead of a refund?', '<p>Unfortunately we don’t offer exchanges. Simply return your item(s) and reorder a replacement. Your refund will be processed once we’ve received your unwanted item(s).</p>' ),
	array( 'Returns & refunds', 'I received an incorrect item. What do I do?', '<p>To get this fixed for you as soon as possible, reply to your order confirmation email with a picture and a short description of the incorrect item you received.</p>' ),
	array( 'Products & care', 'How long does a reed diffuser last?', '<p>A 200ml reed diffuser lasts for approximately 6 to 12 weeks, depending on room temperature and airflow. Our diffuser refills last the same.</p><p>Read our <a href="/care-guide/">Care guide</a> for tips to make every scent last longer.</p>' ),
	array( 'Products & care', 'What are your candles made from?', '<p>Our candles are hand-poured with soy wax.</p>' ),
	array( 'Products & care', 'Are your products vegan?', '<p>Yes. Our products are vegan and cruelty-free, and free from parabens and sulfates.</p>' ),
	array( 'Products & care', 'Are your products safe around pets and children?', '<p>Please keep all of our products out of reach of children and pets, and never leave a burning candle unattended. If you have a pet with a sensitivity, check with your vet before using fragrance products around them.</p>' ),
	array( 'Products & care', 'What is in the insect repellent?', '<p>Our insect repellent is 100% plant-based and made without DEET or alcohol, with a fresh, natural scent. It protects against biting insects such as mosquitoes, midges, horseflies and ticks, and doubles as a room spray.</p>' ),
);
$items = array();
foreach ( $faq as $i => $f ) {
	$items[] = array( '_id' => 'f' . $i, 'group' => $f[0], 'q' => $f[1], 'a' => $f[2] );
}
$page( 'faq', 'FAQ', array(
	$box( array( $hero( 'Help', 'Questions, {answered.}', 'Delivery, returns and caring for your scents. If you can’t find what you need, we’re one message away.' ) ) ),
	$box( array( $el( 'afz-faq', array( 'items' => $items ) ) ), 'afz-sheet light' ),
) );

/* ---------- Contact ---------- */
$page( 'contact-us', 'Contact', array(
	$box( array( $hero( 'Contact', 'Let’s {talk.}', 'About a scent, an order, a gift or a collaboration.', array( 'image' => $m( 'life-books.webp' ), 'image_style' => 'photo' ) ) ) ),
	$box( array( $el( 'afz-contact' ) ), 'afz-sheet light' ),
) );

/* ---------- Delivery ---------- */
$page( 'delivery', 'Delivery', array(
	$box( array( $hero( 'Delivery', "Wrapped with care,\n{sent with Royal Mail.}", 'Delivery Monday to Saturday, excluding bank holidays. Free delivery is applied automatically at checkout.' ) ) ),
	$box( array(
		$el( 'afz-cards', array(
			'eyebrow' => 'Delivery options',
			'title'   => 'Choose your {pace}',
			'columns' => '3',
			'items'   => array(
				array( '_id' => 'd1', 'label' => 'UK', 'title' => 'Standard Delivery', 'text' => "Up to 4 working days\nMonday to Saturday, excluding bank holidays", 'meta' => '£3.99', 'note' => 'Free over £40' ),
				array( '_id' => 'd2', 'label' => 'UK', 'title' => 'Next Day Delivery', 'text' => "Order before 4pm GMT to receive your order the next day\nMonday to Saturday", 'meta' => '£6.99', 'note' => 'Free over £80' ),
				array( '_id' => 'd3', 'label' => 'International', 'title' => 'Europe Standard Delivery', 'text' => 'Up to 6 working days', 'meta' => '£10.00', 'note' => '' ),
			),
		) ),
		$el( 'afz-rich', array(
			'eyebrow' => 'Good to know',
			'body'    => '<ul><li>All orders placed after the cut-off time are sent out on the next available day.</li><li>Every order is sent with Royal Mail. Once it is dispatched we email you a tracking number. You can also <a href="/track-order/">track your order here</a>.</li><li>Delivery days are Monday to Saturday and exclude bank holidays.</li><li>Questions about an order? <a href="/contact-us/">Contact us</a> and we’ll help.</li></ul>',
		) ),
	), 'afz-sheet light' ),
) );

/* ---------- Returns ---------- */
$returns = $clean( $old_editor( get_page_by_path( 'returns' )->ID ) );
// the returns portal plugin was retired: returns now start with a message to the store
$returns = preg_replace( '#<p>All returns must be completed via the return portal.*?</p>#s', '<p>To start a return, <a href="/contact-us/">contact us</a> with your order number and clearly indicate which items you are returning. If this is not filled out correctly, your return may experience issues in processing.</p>', $returns );
$page( 'returns', 'Returns', array(
	$box( array( $hero( 'Returns', 'Changed your {mind?}', 'You have 14 days from delivery to return unused items in their original condition.' ) ) ),
	$box( array( $el( 'afz-rich', array( 'eyebrow' => 'Our returns policy', 'body' => $returns ) ) ), 'afz-sheet light' ),
) );

/* ---------- Terms ---------- */
$tcs = $clean( $old_editor( get_page_by_path( 'tcs' )->ID ) );
// numbered capitalised lines ("4. DELIVERY") become real headings, so they show in the contents list
// (the old page marked them up several ways: bold, plain, or as an accordion paragraph)
$tcs = str_replace( array( "\u{FFFD}", "\x7F", "\u{00A0}" ), array( '', ' ', ' ' ), wp_check_invalid_utf8( $tcs, true ) );
$tcs = preg_replace_callback( '#<(p|div)[^>]*>\s*(?:<(?:b|strong)>)?\s*(\d{1,2})\s*\.\s*([A-Z](?:[A-Z0-9 ,;\'’()\-]|&amp;)+?)\s*(?:</(?:b|strong)>)?\s*</\1>#u', function ( $x ) {
	return '<h2>' . $x[2] . '. ' . esc_html( ucfirst( strtolower( html_entity_decode( $x[3] ) ) ) ) . '</h2>';
}, $tcs );
$page( 'tcs', 'Terms & Conditions', array(
	$box( array( $hero( 'Legal', 'Terms & {conditions}', 'The terms under which we supply our products to you. Please read them before ordering.' ) ) ),
	$box( array( $el( 'afz-rich', array( 'body' => $tcs, 'toc' => 'yes' ) ) ), 'afz-sheet light' ),
) );

/* ---------- Privacy ---------- */
$co      = AFZ_Settings::get();
$company = esc_html( ( $co['company_name'] ?? 'AWRELIEF' ) . ' (company number ' . ( $co['company_no'] ?? '15435755' ) . ')' );
$email   = esc_html( $co['email'] ?? 'hello@awrelief.com' );
$privacy = <<<HTML
<h2>Who we are</h2><p>This website is run by {$company}, trading as AWRELIEF. We are the controller of the personal information described here. You can contact us about privacy at <a href="mailto:{$email}">{$email}</a>.</p>
<h2>What we collect</h2><ul><li><strong>Orders:</strong> your name, delivery and billing address, email, phone number and what you bought.</li><li><strong>Your account:</strong> if you create one, your login details and order history.</li><li><strong>Payments:</strong> card and PayPal payments are handled by our payment providers. We never see or store your full card number.</li><li><strong>Newsletter:</strong> your email address if you join the AWRELIEF circle.</li><li><strong>Messages:</strong> what you send us through the contact form or by email.</li><li><strong>Reviews:</strong> your name, rating, review and any photos you choose to upload.</li><li><strong>Technical data:</strong> basic information your browser sends, such as IP address and device type, used to keep the site secure and working.</li></ul>
<h2>Why we use it</h2><ul><li>To process, deliver and support your order (contract).</li><li>To keep accounting and tax records (legal obligation).</li><li>To send newsletters and offers, only if you have signed up. You can unsubscribe at any time (consent).</li><li>To reply to your messages, invite reviews of products you bought, prevent fraud and improve our shop (legitimate interests).</li></ul>
<h2>Who we share it with</h2><p>We only share what each service needs to do its job: our payment providers (WooPayments by Stripe and PayPal), Royal Mail for delivery, MailerLite for our newsletter, our review service for review invitations, and our website host. We never sell your personal information.</p>
<h2>How long we keep it</h2><p>Order records are kept for six years for accounting purposes. Newsletter details are kept until you unsubscribe. Messages are kept for as long as needed to help you, and then deleted.</p>
<h2>Your rights</h2><p>Under UK data protection law you can ask to see the information we hold about you, ask us to correct or delete it, object to how we use it, or ask us to stop marketing to you. Email <a href="mailto:{$email}">{$email}</a> and we will reply within one month. If you are unhappy with how we have handled your information you can complain to the Information Commissioner’s Office at <a href="https://ico.org.uk" target="_blank" rel="noopener">ico.org.uk</a>.</p>
<h2>Cookies</h2><p>We use essential cookies to run the shop, such as remembering your basket, keeping you logged in and protecting the checkout. These are always on. With your permission, we may also use cookies that help us understand how the site is used. You can change your choice at any time by clearing your browser’s cookies, and the banner will ask you again.</p>
<h2>Changes to this policy</h2><p>We may update this policy from time to time. The latest version will always be on this page.</p>
HTML;
$page( 'privacy-policy', 'Privacy & Cookies', array(
	$box( array( $hero( 'Legal', 'Privacy & {cookies}', 'How we look after your personal information.' ) ) ),
	$box( array( $el( 'afz-rich', array( 'body' => $privacy, 'toc' => 'yes', 'updated' => 'Last updated ' . wp_date( 'F Y' ) ) ) ), 'afz-sheet light' ),
) );

/* ---------- Track my order (new) ---------- */
$page( 'track-order', 'Track my order', array(
	$box( array( $hero( 'Track my order', 'Where is {my parcel?}', 'Every order is sent with Royal Mail.' ) ) ),
	$box( array( $el( 'afz-track', array( 'eyebrow' => 'Order status', 'title' => 'Check {your order}' ) ) ), 'afz-sheet light' ),
) );

/* ---------- Care guide (new) ---------- */
$page( 'care-guide', 'Care Guide', array(
	$box( array( $hero( 'Care guide', 'Make every scent {last longer}', 'A few simple habits keep candles burning evenly and diffusers fragrant for weeks.', array( 'image' => $m( 'candle.webp' ), 'image_style' => 'cutout', 'hue' => '#b9a6d9' ) ) ) ),
	$box( array(
		$el( 'afz-cards', array(
			'eyebrow' => 'Candles',
			'title'   => 'Hand-poured {soy wax}',
			'intro'   => 'Burn time approximately 45 hours.',
			'columns' => '3',
			'items'   => array(
				array( '_id' => 'c1', 'label' => '01', 'title' => 'The first burn', 'text' => 'Let the top layer melt fully on the first burn so the candle forms a full wax pool and burns evenly all the way down.' ),
				array( '_id' => 'c2', 'label' => '02', 'title' => 'Trim the wick', 'text' => 'Trim your wick to 5mm every time before you light your candle, and again after each burn for a cleaner flame.' ),
				array( '_id' => 'c3', 'label' => '03', 'title' => 'Know when to stop', 'text' => 'Burn for no longer than 4 hours at a time, and never burn completely: always leave half an inch of wax at the bottom.' ),
			),
		) ),
		$el( 'afz-cards', array(
			'eyebrow' => 'Reed diffusers',
			'title'   => '6 to 12 weeks {of fragrance}',
			'intro'   => 'How long a diffuser lasts depends on room temperature and airflow.',
			'columns' => '3',
			'items'   => array(
				array( '_id' => 'r1', 'label' => '01', 'title' => 'Turn the reeds', 'text' => 'Flip the reeds every week or so to refresh the scent. Fewer reeds give a softer fragrance and make the oil last longer.' ),
				array( '_id' => 'r2', 'label' => '02', 'title' => 'Find the right spot', 'text' => 'Keep your diffuser away from direct sunlight, radiators and draughts, which make the oil evaporate faster.' ),
				array( '_id' => 'r3', 'label' => '03', 'title' => 'Refill, don’t replace', 'text' => 'When it runs low, top up with a matching refill. The spout pours cleanly, without spills or wastage.' ),
			),
		) ),
		$el( 'afz-rich', array(
			'eyebrow' => 'Keep in mind',
			'body'    => '<ul><li>Never leave candles burning unattended or near draughts, open windows, air vents or fans.</li><li>When it’s time to extinguish your candle, use a candle snuffer, or blow it out gently and carefully.</li><li>Do not eat or drink any of the products. Keep all products away from pets and children at all times.</li><li>Our products are vegan and cruelty-free, and contain no silicone or parabens.</li></ul>',
		) ),
	), 'afz-sheet light' ),
) );

/* ---------- Journal (new, until the first stories are published) ---------- */
$page( 'journal', 'Journal', array(
	$box( array( $hero( 'The journal', 'Notes on {slow living}', 'Scent stories, care rituals and calmer homes. Our first stories are on their way.' ) ) ),
	$box( array( $el( 'afz-newsletter', array( 'eyebrow' => 'Be the first to read', 'title' => "Stories, straight\nto {your inbox}" ) ) ) ),
) );

// the old theme's demo "Store" page should not be public
$store = get_page_by_path( 'store' );
if ( $store && 'publish' === $store->post_status ) {
	wp_update_post( array( 'ID' => $store->ID, 'post_status' => 'draft' ) );
	echo "old demo page /store/ set to draft\n";
}
update_option( 'wp_page_for_privacy_policy', get_page_by_path( 'privacy-policy' )->ID );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
echo "done\n";
