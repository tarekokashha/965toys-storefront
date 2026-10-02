<?php
/**
 * Performance.
 *
 * MEASURED BASELINE (13 Aug 2026, live site)
 *   TTFB              0.86 - 1.67 s   (Cloudflare cf-cache-status: DYNAMIC)
 *   HTML              340 KB uncompressed
 *   JS  (LiteSpeed combined)  ~352 KB gz  + 30 KB jQuery
 *   CSS (LiteSpeed combined)  ~169 KB gz
 *   First-view text assets    ~603 KB gz
 *
 * THE HEADLINE PROBLEM
 *   63 images ship a base64 grey SVG as their src with the real URL parked in
 *   data-src, because LiteSpeed's JS lazy-load is applied indiscriminately.
 *   The logo is one of them. That means the largest contentful paint cannot
 *   happen until a JavaScript file has downloaded, parsed and executed. There
 *   are also zero preload hints and only one fetchpriority="high" on the page.
 *
 *   Fixing this is the single largest LCP win available and costs no redesign.
 *
 * Target device is a Galaxy A15 / Redmi Note 13 on Kuwaiti 4G. Bandwidth there
 * is good; CPU is not. Optimise for fewer bytes of JAVASCRIPT, not fewer bytes.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * 1. Stop lazy-loading above-the-fold imagery
 * ---------------------------------------------------------------------- */

/**
 * Mark the logo and the first N product images as eager + high priority.
 *
 * WordPress core already skips its own lazy-load for the first image, but
 * LiteSpeed's JS lazy-load runs independently and ignores that. The
 * data-no-lazy attribute and the litespeed_optimize_js_excludes filter are
 * what actually stop it.
 */
add_filter( 'wp_get_attachment_image_attributes', 'toys965_prioritise_hero_images', 10, 3 );

function toys965_prioritise_hero_images( $attr, $attachment, $size ) {

	// Only ever eager-load in the main request, never in a REST/AJAX context.
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $attr;
	}

	$counter = toys965_image_counter();

	// How many leading images count as "above the fold".
	//
	// Measured on this site: the header renders the logo TWICE (desktop and
	// mobile variants), so a threshold of 2 exempted only the logo and left the
	// actual LCP element — the hero banner, which is image #4 — still gated
	// behind the lazy-load script. Four covers logo x2, the first decorative
	// icon, and the hero.
	//
	// Do not raise this much further: every eager image competes with the LCP
	// element for bandwidth, so an over-generous threshold makes things worse.
	$eager = (int) apply_filters( 'toys965_eager_image_count', 4 );

	if ( $counter <= $eager ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'sync';
		$attr['data-no-lazy']  = '1';   // LiteSpeed
		$attr['class']         = isset( $attr['class'] )
			? $attr['class'] . ' no-lazy skip-lazy'
			: 'no-lazy skip-lazy';
	}

	return $attr;
}

/**
 * Simple per-request counter of rendered attachment images.
 */
function toys965_image_counter() {
	static $n = 0;
	return ++$n;
}

/**
 * Tell LiteSpeed explicitly which classes must never be lazy-loaded.
 */
add_filter( 'litespeed_media_lazy_img_excludes', 'toys965_lazy_excludes' );

function toys965_lazy_excludes( $excludes ) {
	return array_merge(
		(array) $excludes,
		array( 'no-lazy', 'skip-lazy', 'custom-logo', 'wd-logo', 't-stage__img' )
	);
}

/* -------------------------------------------------------------------------
 * 2. Preload what actually paints
 * ---------------------------------------------------------------------- */

/**
 * Preload the two variable fonts and the LCP image.
 *
 * Fonts are preloaded because a swap-in of the Arabic display face after first
 * paint is a visible, ugly reflow. Only the two primaries are preloaded — the
 * accent face is badge-only and can arrive late.
 */
add_action( 'wp_head', 'toys965_preloads', 1 );

function toys965_preloads() {

	// Only preload our fonts when the design layer is actually serving them.
	// Preloading a font that no stylesheet references is pure waste and
	// triggers a console warning on every page.
	if ( function_exists( 'toys965_design_enabled' ) && toys965_design_enabled() ) {

		// Arabic subsets only. The site is Arabic-first so these are certain to
		// be needed; the Latin subsets are fetched on demand via unicode-range
		// only when a product title actually contains Latin text. Preloading
		// every subset would waste ~70 KB on a typical page view.
		//
		// These are the two faces the design handoff specifies: Baloo Bhaijaan 2
		// for display, Rubik for body/UI.
		$fonts = array(
			'/assets/fonts/Rubik-arabic.woff2',
			'/assets/fonts/BalooBhaijaan2-arabic.woff2',
		);

		foreach ( $fonts as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
				esc_url( TOYS965_URI . $font )
			);
		}
	}

	// Preconnect only where a third-party origin is genuinely on the critical
	// path. Speculative preconnects cost a TLS handshake each.
	echo '<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin />' . "\n";

	if ( is_singular( 'product' ) ) {
		$id  = get_post_thumbnail_id();
		$src = $id ? wp_get_attachment_image_src( $id, 'woocommerce_single' ) : false;
		if ( $src ) {
			printf(
				'<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n",
				esc_url( $src[0] )
			);
		}
	}
}

/* -------------------------------------------------------------------------
 * 3. Shed dead weight
 * ---------------------------------------------------------------------- */

/**
 * Slider Revolution ships its full engine — 14 modules and the WebGL library —
 * on pages that render zero sliders. Measured on the live homepage: the
 * SR7 bootstrap is present, rev_slider containers are not.
 *
 * This dequeues it everywhere except pages that genuinely contain a slider.
 * Deactivating the plugin outright is the better end state; this filter exists
 * so the saving is realised immediately and safely, and so nothing breaks if a
 * slider is added back later.
 */
add_action( 'wp_enqueue_scripts', 'toys965_drop_unused_slider', 100 );

function toys965_drop_unused_slider() {

	if ( is_admin() ) {
		return;
	}

	global $post;
	$content = ( $post instanceof WP_Post ) ? $post->post_content : '';

	$uses_slider = has_shortcode( $content, 'rev_slider' )
		|| false !== strpos( $content, 'rev_slider' )
		|| false !== strpos( $content, 'revslider' );

	if ( $uses_slider ) {
		return;
	}

	foreach ( array( 'rbtools', 'revmin', 'tp-tools', 'revbuilder' ) as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
		wp_dequeue_style( $handle );
	}
	wp_dequeue_style( 'rs-plugin-settings' );
}

/**
 * WooCommerce loads cart/checkout scripts on every page. They are only needed
 * where a cart actually exists.
 */
add_action( 'wp_enqueue_scripts', 'toys965_scope_woo_assets', 100 );

function toys965_scope_woo_assets() {

	if ( ! class_exists( 'WooCommerce' ) || is_admin() ) {
		return;
	}

	// Never touch anything on the commerce path. Breaking checkout to save a
	// few KB is never a good trade.
	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		return;
	}

	// The block-based cart/checkout bundles are large and irrelevant elsewhere.
	wp_dequeue_style( 'wc-blocks-style' );
}

/**
 * jQuery cannot be removed — Woodmart and WooCommerce both hard-depend on it —
 * but it can stop blocking the parser.
 */
add_filter( 'script_loader_tag', 'toys965_defer_jquery', 10, 3 );

function toys965_defer_jquery( $tag, $handle, $src ) {

	if ( is_admin() ) {
		return $tag;
	}

	// Deferring only jquery-core while its dependants stay blocking would
	// reorder execution and break them, so defer the whole chain or nothing.
	$defer = array( 'jquery-core', 'jquery-migrate' );

	if ( in_array( $handle, $defer, true ) && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}

	return $tag;
}

/* -------------------------------------------------------------------------
 * 4. Trim head noise
 * ---------------------------------------------------------------------- */

remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

// Emoji support costs a script plus a DNS lookup and this store does not use it.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

add_filter( 'emoji_svg_url', '__return_false' );
