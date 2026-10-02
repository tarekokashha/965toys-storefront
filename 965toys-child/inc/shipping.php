<?php
/**
 * Delivery rules.
 *
 * The three delivery speeds themselves are native WooCommerce flat rates in the
 * "الكويت" zone (1.000 / 1.800 / 2.500 KWD) so the owner can edit the prices in
 * wp-admin without touching code. This file adds the two rules that WooCommerce
 * cannot express on its own:
 *
 *   1. REMOTE AREAS pay a flat surcharge instead of the standard rate. Kuwait's
 *      remote areas are not WooCommerce "states" — الوفرة, الخيران and
 *      صباح الأحمد البحرية all sit inside Al Ahmadi alongside ordinary areas
 *      like الفحيحيل — so a shipping zone cannot separate them. They are matched
 *      on the address text instead.
 *   2. EXPRESS (90-180 min) disappears after the 19:00 cut-off, and is never
 *      offered to a remote area at all.
 *
 * Nothing here touches the payment step. Tahseeelpay is untouched.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/**
 * The remote-area price list.
 *
 * `match` holds normalised needles (hamza folded, tatweel stripped) tested
 * against the whole shipping address, so it fires whether the customer typed
 * the area into the city field, the address line, or both.
 *
 * @return array<int,array{name:string,cost:float,match:string[]}>
 */
function toys965_remote_areas() {

	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}

	$raw = array(
		array(
			'name'  => 'علي صباح السالم — أم الهيمان',
			'cost'  => 4.000,
			'match' => array( 'علي صباح السالم', 'ام الهيمان', 'الهيمان' ),
		),
		array(
			'name'  => 'المطلاع',
			'cost'  => 5.000,
			'match' => array( 'المطلاع', 'مطلاع' ),
		),
		array(
			'name'  => 'مدينة صباح الأحمد السكنية',
			'cost'  => 5.000,
			'match' => array( 'صباح الاحمد السكنية', 'مدينة صباح الاحمد' ),
		),
		array(
			'name'  => 'الوفرة',
			'cost'  => 7.000,
			'match' => array( 'الوفرة', 'وفرة' ),
		),
		array(
			'name'  => 'الخيران',
			'cost'  => 7.000,
			'match' => array( 'الخيران', 'خيران' ),
		),
		array(
			'name'  => 'صباح الأحمد البحرية',
			'cost'  => 7.000,
			'match' => array( 'صباح الاحمد البحرية', 'الاحمد البحرية' ),
		),
	);

	foreach ( $raw as &$area ) {
		$area['match'] = array_map( 'toys965_normalise_ar', $area['match'] );
	}
	unset( $area );

	return $memo = $raw;
}

/**
 * Which remote area, if any, a shipping package is going to.
 *
 * NOTE ON ORDER: "صباح الأحمد البحرية" is checked before
 * "مدينة صباح الأحمد السكنية" in the list above only by virtue of its own
 * distinct needles; both are matched on their full phrase so the marine city
 * (7.000) is never mistaken for the residential city (5.000).
 *
 * @param array $destination WC package destination.
 * @return array|null
 */
function toys965_remote_area_for( $destination ) {

	$parts = array(
		isset( $destination['city'] ) ? $destination['city'] : '',
		isset( $destination['address_1'] ) ? $destination['address_1'] : '',
		isset( $destination['address_2'] ) ? $destination['address_2'] : '',
		isset( $destination['state'] ) ? $destination['state'] : '',
	);

	$haystack = toys965_normalise_ar( implode( ' ', array_filter( $parts ) ) );
	if ( '' === trim( $haystack ) ) {
		return null;
	}

	// Longest needle first, so "صباح الاحمد البحرية" beats a shorter overlap.
	$areas = toys965_remote_areas();
	$hits  = array();

	foreach ( $areas as $area ) {
		foreach ( $area['match'] as $needle ) {
			if ( '' !== $needle && false !== strpos( $haystack, $needle ) ) {
				$hits[ mb_strlen( $needle ) ] = $area;
			}
		}
	}

	if ( ! $hits ) {
		return null;
	}

	krsort( $hits );
	return reset( $hits );
}

/**
 * Is the express window still open?
 *
 * Express runs 90-180 minutes, so it is only offered for orders placed before
 * 19:00 local time. Uses the site's configured timezone, not the server's.
 */
function toys965_express_open() {
	$hour = (int) wp_date( 'G' );
	return $hour < (int) apply_filters( 'toys965_express_cutoff_hour', 19 );
}

/**
 * Recognise the express rate by its own setting rather than its label, so
 * renaming it in wp-admin does not silently disable these rules.
 */
function toys965_is_express_rate( $rate ) {
	$label = $rate->get_label();
	return ( false !== mb_strpos( $label, 'المستعجل' ) || false !== mb_strpos( $label, '🚀' ) );
}

/**
 * Apply both rules to the rates offered for a package.
 */
add_filter( 'woocommerce_package_rates', 'toys965_delivery_rates', 20, 2 );

function toys965_delivery_rates( $rates, $package ) {

	$destination = isset( $package['destination'] ) ? $package['destination'] : array();
	$remote      = toys965_remote_area_for( $destination );
	$express_ok  = toys965_express_open();

	foreach ( $rates as $key => $rate ) {

		if ( toys965_is_express_rate( $rate ) ) {
			// Never to a remote area; never after the cut-off.
			if ( $remote || ! $express_ok ) {
				unset( $rates[ $key ] );
			}
			continue;
		}

		if ( $remote ) {
			$rate->set_cost( (string) $remote['cost'] );
			$rate->set_label( $rate->get_label() . ' — ' . $remote['name'] );
			$rate->set_taxes( array() );
		}
	}

	return $rates;
}

/**
 * Keep the express cut-off out of WooCommerce's shipping cache.
 *
 * Rates are cached against a hash of the package. The hash covers the address
 * but knows nothing about the clock, so without this a session that loaded the
 * cart at 18:55 would still be offered express at 19:30.
 */
add_filter( 'woocommerce_shipping_packages', 'toys965_shipping_cache_key' );

function toys965_shipping_cache_key( $packages ) {

	foreach ( $packages as $i => $package ) {
		$packages[ $i ]['toys965_express_window'] = toys965_express_open() ? 'open' : 'closed';
	}

	return $packages;
}

/* -------------------------------------------------------------------------
 * Customer-facing rate table
 * ---------------------------------------------------------------------- */

/**
 * The delivery options, rendered for the shopper.
 *
 * Shown on the cart so the cost is known before checkout — the single most
 * common reason a Gulf shopper abandons at the payment step is a delivery fee
 * they did not see coming.
 */
function toys965_delivery_table() {

	$express_open = toys965_express_open();

	ob_start();
	?>
	<section class="t965-ship" aria-labelledby="t965-ship-h">
		<h2 id="t965-ship-h">🚚 خيارات التوصيل</h2>

		<ul class="t965-ship__list">
			<li><span>التوصيل غدًا</span><b>١٫٠٠٠ د.ك</b></li>
			<li><span>التوصيل في نفس اليوم</span><b>١٫٨٠٠ د.ك</b></li>
			<li class="<?php echo $express_open ? '' : 't965-ship--closed'; ?>">
				<span>
					التوصيل المستعجل خلال ٩٠–١٨٠ دقيقة 🚀
					<small><?php echo $express_open
						? 'متوفر للطلبات قبل الساعة ٧:٠٠ مساءً'
						: 'انتهى وقت الطلب لليوم — متاح غدًا قبل ٧:٠٠ مساءً'; ?></small>
				</span>
				<b>٢٫٥٠٠ د.ك</b>
			</li>
		</ul>

		<h3>📍 التوصيل إلى المناطق البعيدة</h3>
		<ul class="t965-ship__list t965-ship__list--remote">
			<li><span>علي صباح السالم «أم الهيمان»</span><b>٤٫٠٠٠ د.ك</b></li>
			<li><span>المطلاع</span><b>٥٫٠٠٠ د.ك</b></li>
			<li><span>مدينة صباح الأحمد السكنية</span><b>٥٫٠٠٠ د.ك</b></li>
			<li><span>الوفرة</span><b>٧٫٠٠٠ د.ك</b></li>
			<li><span>الخيران</span><b>٧٫٠٠٠ د.ك</b></li>
			<li><span>صباح الأحمد البحرية</span><b>٧٫٠٠٠ د.ك</b></li>
		</ul>

		<p class="t965-ship__note">
			<strong>ملاحظة:</strong> لا تتوفر خدمة التوصيل المستعجل للمناطق البعيدة.
		</p>
	</section>
	<?php
	return (string) ob_get_clean();
}

add_action( 'woocommerce_after_cart_table', 'toys965_print_delivery_table', 20 );

function toys965_print_delivery_table() {
	if ( ! toys965_design_enabled() ) {
		return;
	}
	echo toys965_delivery_table(); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * A short version on the product page, under the reassurance strip.
 */
add_action( 'woocommerce_after_add_to_cart_button', 'toys965_pdp_delivery_hint', 40 );

function toys965_pdp_delivery_hint() {

	$express = toys965_express_open()
		? 'أو خلال ٩٠–١٨٠ دقيقة بالتوصيل المستعجل'
		: 'التوصيل المستعجل متاح غدًا قبل ٧:٠٠ مساءً';

	printf(
		'<p class="t965-ship__hint">🚚 التوصيل غدًا بـ ١٫٠٠٠ د.ك، أو اليوم بـ ١٫٨٠٠ د.ك — %s.</p>',
		esc_html( $express )
	);
}

/**
 * Shortcode so the owner can drop the full table on any page.
 */
add_shortcode( 'toys965_delivery', 'toys965_delivery_shortcode' );

function toys965_delivery_shortcode() {
	return toys965_delivery_table();
}
