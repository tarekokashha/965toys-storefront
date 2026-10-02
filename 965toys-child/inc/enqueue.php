<?php
/**
 * Asset loading.
 *
 * Load order is deliberate:
 *   1. woodmart parent stylesheet  (the WooCommerce template layer)
 *   2. fonts.css                   (@font-face — must precede anything using them)
 *   3. tokens.css                  (design tokens from the handoff)
 *   4. base.css                    (reset + Arabic/RTL corrections)
 *   5. home.css                    (homepage sections + shared components)
 *   6. woodmart-bridge.css         (maps tokens onto Woodmart's --wd-* vars)
 *
 * The bridge must be LAST: Woodmart emits its variable block inline in <head>
 * after enqueued styles, so this is what actually makes the whole store adopt
 * the design rather than only the homepage template.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'toys965_assets', 20 );

function toys965_assets() {

	$uri = TOYS965_URI;
	$dir = TOYS965_DIR;

	// Version by mtime so a deploy busts the cache without a manual bump.
	$v = function ( $rel ) use ( $dir ) {
		$path = $dir . $rel;
		return is_readable( $path ) ? (string) filemtime( $path ) : TOYS965_VERSION;
	};

	$parent = wp_style_is( 'woodmart-style', 'registered' ) ? 'woodmart-style' : 'parent-style';
	if ( 'parent-style' === $parent ) {
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array(), TOYS965_VERSION );
	}

	// The design layer is gated so it can be previewed before going live.
	// See toys965_design_enabled(): option, constant, or ?toybox=1 for admins.
	if ( ! toys965_design_enabled() ) {
		return;
	}

	wp_enqueue_style( 'toys965-fonts', $uri . '/assets/fonts/fonts.css', array(), $v( '/assets/fonts/fonts.css' ) );
	wp_enqueue_style( 'toys965-main', $uri . '/assets/css/965toys.css', array( $parent, 'toys965-fonts' ), $v( '/assets/css/965toys.css' ) );
	wp_enqueue_style( 'toys965-bridge', $uri . '/assets/css/woodmart-bridge.css', array( 'toys965-main' ), $v( '/assets/css/woodmart-bridge.css' ) );

	// Motion runs sitewide EXCEPT cart/checkout/account: nothing decorative is
	// worth an INP regression on the pages where money changes hands.
	if ( ! toys965_is_commerce_path() ) {
		add_action( 'wp_footer', 'toys965_print_motion', 20 );
	}

	// WooCommerce AJAX add-to-cart powers the confetti confirmation.
	if ( function_exists( 'is_woocommerce' ) ) {
		wp_enqueue_script( 'wc-add-to-cart' );
	}
}

/**
 * Print the motion script by hand.
 *
 * LiteSpeed rewrites enqueued <script> tags to type="litespeed/javascript"
 * with data-src to delay them, which silently dropped this file from the page
 * during an earlier build. data-no-optimize keeps it intact.
 */
function toys965_print_motion() {

	$rel = '/assets/js/965toys.js';
	$ver = is_readable( TOYS965_DIR . $rel ) ? filemtime( TOYS965_DIR . $rel ) : TOYS965_VERSION;

	printf(
		'<script src="%s?ver=%s" defer data-no-optimize="1" data-no-defer="1"></script>' . "\n",
		esc_url( TOYS965_URI . $rel ),
		esc_attr( $ver )
	);
}

/**
 * Is the design layer switched on?
 *
 * Defaults to FALSE so activating the theme never changes the storefront's
 * appearance by surprise.
 */
function toys965_design_enabled() {

	// Admin-only per-request preview: ?toybox=1 to see it, ?toybox=0 to hide.
	if ( isset( $_GET['toybox'] ) && current_user_can( 'manage_options' ) ) {
		return '1' === $_GET['toybox'];
	}

	if ( defined( 'TOYS965_DESIGN' ) ) {
		return (bool) TOYS965_DESIGN;
	}

	return (bool) get_option( 'toys965_design', 0 );
}

/**
 * Cart, checkout and account are the commerce path.
 */
function toys965_is_commerce_path() {

	if ( ! function_exists( 'is_cart' ) ) {
		return false;
	}

	return is_cart() || is_checkout() || is_account_page();
}

/**
 * Drop Google Fonts — but only once our own faces are being served, or the
 * storefront would render in a system fallback.
 */
add_action( 'wp_enqueue_scripts', 'toys965_drop_google_fonts', 100 );

function toys965_drop_google_fonts() {

	if ( is_admin() || ! toys965_design_enabled() ) {
		return;
	}

	foreach ( wp_styles()->queue as $handle ) {
		$src = wp_styles()->registered[ $handle ]->src ?? '';
		if ( $src && false !== strpos( $src, 'fonts.googleapis.com' ) ) {
			wp_dequeue_style( $handle );
		}
	}
}

add_filter( 'elementor/frontend/print_google_fonts', function ( $print ) {
	return toys965_design_enabled() ? false : $print;
} );

/**
 * Register the homepage template so it can be assigned in Pages → Attributes.
 *
 * Declared explicitly because page-templates/ in a child theme is only scanned
 * when the header comment is present, and this makes the dependency obvious.
 */
add_filter( 'theme_page_templates', 'toys965_register_templates' );

function toys965_register_templates( $templates ) {
	$templates['page-templates/home-965toys.php']       = __( '965toys Homepage', '965toys' );
	$templates['page-templates/categories-965toys.php'] = __( '965toys — الفئات', '965toys' );
	$templates['page-templates/brands-965toys.php']     = __( '965toys — الماركات', '965toys' );
	return $templates;
}
