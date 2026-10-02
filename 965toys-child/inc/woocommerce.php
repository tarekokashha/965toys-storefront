<?php
/**
 * WooCommerce behaviour.
 *
 * Scope note: this file changes PRESENTATION and adds trust/ordering affordances.
 * It never touches pricing, tax, the cart object, or the checkout submission
 * path. Tahseeelpay (KNET + credit card) is the only gateway on this store and
 * runs on the classic shortcode checkout; nothing here goes near it.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Price rendering
 * ---------------------------------------------------------------------- */

/**
 * Guard against the price-entry bug recurring.
 *
 * ROOT CAUSE (measured): the store ran with 2 decimals and "," as the
 * thousands separator. wc_format_decimal() strips every character outside
 * [0-9.-], so a price typed as "7,000" — meaning 7 KWD in the Kuwaiti
 * convention — was stored as 7000. 43 of 193 products were affected and
 * were effectively unsellable.
 *
 * The configuration fix is 3 decimals with no thousands separator. This filter
 * is the belt-and-braces: it refuses to save an implausible price and logs it,
 * so the same mistake surfaces immediately instead of silently.
 *
 * Threshold is deliberately generous — the store legitimately sells wooden
 * outdoor playsets up to ~700 KWD.
 */
add_filter( 'woocommerce_admin_process_product_object', 'toys965_guard_price_entry', 10, 1 );

function toys965_guard_price_entry( $product ) {

	$ceiling = (float) apply_filters( 'toys965_price_ceiling', 1500 );

	foreach ( array( 'regular_price', 'sale_price' ) as $field ) {

		$getter = "get_{$field}";
		$value  = (float) $product->$getter( 'edit' );

		if ( $value > $ceiling ) {
			// Do not silently "correct" it — a wrong auto-fix is worse than a
			// visible refusal. Flag it for the person who typed it.
			$product->add_meta_data( '_toys965_price_flag', sprintf(
				'%s = %s exceeds the %s KWD sanity ceiling. Check for a comma: type 7.000, never 7,000.',
				$field,
				$value,
				$ceiling
			), true );

			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					sprintf( 'Implausible %s (%s KWD) on product #%d', $field, $value, $product->get_id() ),
					array( 'source' => '965toys-price-guard' )
				);
			}
		}
	}

	return $product;
}

/* -------------------------------------------------------------------------
 * Product card
 * ---------------------------------------------------------------------- */

/**
 * Show the age band on the product card.
 *
 * Age is the single most useful filter for a toy shopper, and parents scan for
 * it before they scan for price. Rendered from the pa_age attribute created by
 * the migration script.
 */
add_action( 'woocommerce_before_shop_loop_item_title', 'toys965_age_badge', 15 );

function toys965_age_badge() {

	global $product;
	if ( ! $product ) {
		return;
	}

	$terms = wc_get_product_terms( $product->get_id(), 'pa_age', array( 'fields' => 'names' ) );
	if ( empty( $terms ) ) {
		return;
	}

	printf(
		'<span class="t-agebadge">%s</span>',
		esc_html( $terms[0] )
	);
}

/* -------------------------------------------------------------------------
 * WhatsApp ordering
 * ---------------------------------------------------------------------- */

/**
 * "Order via WhatsApp" on every product page.
 *
 * This is not a support link. In Kuwait a meaningful share of shoppers will
 * never complete a web checkout but will happily order in chat, so this is a
 * parallel conversion path — which is also why it is instrumented separately
 * in analytics.
 *
 * The message is pre-filled with the product name, SKU and URL so the shop can
 * answer without a round trip.
 */
add_action( 'woocommerce_after_add_to_cart_button', 'toys965_whatsapp_order', 20 );

function toys965_whatsapp_order() {

	global $product;
	if ( ! $product ) {
		return;
	}

	$number = apply_filters( 'toys965_whatsapp_number', TOYS965_WHATSAPP );

	$message = sprintf(
		"مرحباً، أرغب بطلب هذا المنتج:\n%s\n%s\n%s",
		$product->get_name(),
		$product->get_sku() ? 'SKU: ' . $product->get_sku() : '',
		get_permalink( $product->get_id() )
	);

	printf(
		'<a class="t-btn t-btn--wa" href="https://wa.me/%s?text=%s" target="_blank" rel="noopener"
		    data-analytics="whatsapp_order" data-product-id="%d">
		   <svg class="icon" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
		     <path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z"/>
		   </svg>
		   %s
		 </a>',
		esc_attr( $number ),
		rawurlencode( $message ),
		(int) $product->get_id(),
		esc_html__( 'اطلب عبر واتساب', '965toys' )
	);
}

/* -------------------------------------------------------------------------
 * Free-delivery progress
 * ---------------------------------------------------------------------- */

/**
 * Progress bar toward the free-delivery threshold.
 *
 * Highest-ROI average-order-value lever available to a store whose median
 * price sits between 1 and 9 KWD: the gap to the threshold is usually one
 * more small item.
 */
add_action( 'woocommerce_before_cart_table', 'toys965_free_delivery_progress' );
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'toys965_free_delivery_progress' );

function toys965_free_delivery_progress() {

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$threshold = (float) apply_filters( 'toys965_free_delivery_threshold', 15 );
	if ( $threshold <= 0 ) {
		return;
	}

	$subtotal  = (float) WC()->cart->get_displayed_subtotal();
	$remaining = max( 0, $threshold - $subtotal );
	$pct       = min( 100, ( $subtotal / $threshold ) * 100 );

	$message = $remaining > 0
		? sprintf(
			/* translators: %s: remaining amount */
			__( 'باقي %s للتوصيل المجاني', '965toys' ),
			wp_strip_all_tags( wc_price( $remaining ) )
		)
		: __( 'مبروك! توصيلك مجاني', '965toys' );

	printf(
		'<div class="t-freeship"><div class="t-freeship__bar"><div class="t-freeship__fill" style="--pct:%s"></div></div>
		 <p class="t-freeship__msg" role="status">%s</p></div>',
		esc_attr( round( $pct ) ),
		esc_html( $message )
	);
}

/* -------------------------------------------------------------------------
 * Catalogue hygiene
 * ---------------------------------------------------------------------- */

/**
 * Hide empty categories from the storefront.
 *
 * 10 of 26 main-menu items currently lead to a dead end and 4 are not live
 * categories at all. An empty category page is a bounce and a thin-content
 * liability; suppress it until it has stock.
 */
add_filter( 'woocommerce_product_subcategories_hide_empty', '__return_true' );

/**
 * Sale badge that states the actual saving.
 *
 * "-30%" is far more persuasive than "SALE", and it is honest — which matters
 * because the homepage currently advertises "خصم حتى 30%" while zero products
 * are on sale.
 */
add_filter( 'woocommerce_sale_flash', 'toys965_sale_percentage', 10, 3 );

function toys965_sale_percentage( $html, $post, $product ) {

	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();

	if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
		return $html;
	}

	$pct = round( ( ( $regular - $sale ) / $regular ) * 100 );

	return sprintf(
		'<span class="badge badge--sale">%s%%-</span>',
		esc_html( $pct )
	);
}

/* -------------------------------------------------------------------------
 * Product page — surface the description that already exists
 * ---------------------------------------------------------------------- */

/**
 * Show the short description in the product tabs when there is no long one.
 *
 * MEASURED: 171 of 193 products carry a rich Arabic short description (median
 * ~381 characters, good Kuwaiti dialect, feature bullets) but only 23 have any
 * post_content. Woodmart's Elementor product layout renders the tabs widget
 * from post_content alone, so on 148 products the tabs block collapsed to
 * height 0 and the selling copy the shop already paid to write was invisible.
 *
 * This is presentation only — it reads existing data and writes nothing.
 */
add_filter( 'woocommerce_product_tabs', 'toys965_description_tab_fallback', 20 );

function toys965_description_tab_fallback( $tabs ) {

	global $post, $product;

	if ( ! $product instanceof WC_Product ) {
		return $tabs;
	}

	// A real long description is already handled by WooCommerce.
	if ( $post && trim( (string) $post->post_content ) !== '' ) {
		return $tabs;
	}

	$short = trim( (string) $product->get_short_description() );
	if ( '' === $short ) {
		return $tabs;
	}

	$tabs['description'] = array(
		'title'    => __( 'الوصف', '965toys' ),
		'priority' => 10,
		'callback' => 'toys965_render_short_description_tab',
	);

	return $tabs;
}

function toys965_render_short_description_tab() {

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	echo '<div class="t965-desc">';
	echo wp_kses_post( wpautop( $product->get_short_description() ) );
	echo '</div>';
}

/**
 * Reassurance strip under the add-to-cart button.
 *
 * Delivery time, payment methods and the returns window are the three things a
 * Kuwaiti parent checks before committing. Woodmart's layout has nowhere to put
 * them, so they ride along after the button (priority 30 keeps them below the
 * WhatsApp CTA at 20).
 */
add_action( 'woocommerce_after_add_to_cart_button', 'toys965_pdp_reassurance', 30 );

function toys965_pdp_reassurance() {

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$items = array(
		array( '🚚', 'توصيل خلال 24 ساعة لجميع مناطق الكويت' ),
		array( '💳', 'كي نت أو نقدًا عند الاستلام' ),
		array( '✅', 'منتج أصلي 100%' ),
	);

	echo '<ul class="t965-pdp-trust">';
	foreach ( $items as $i ) {
		printf(
			'<li><span aria-hidden="true">%s</span>%s</li>',
			esc_html( $i[0] ),
			esc_html( $i[1] )
		);
	}
	echo '</ul>';
}
