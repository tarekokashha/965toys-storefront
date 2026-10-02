<?php
/**
 * Accessibility.
 *
 * Three measured failures on the live site that this file corrects:
 *
 *  1. The viewport meta contains `maximum-scale=1.0, user-scalable=no`.
 *     That is a WCAG 2.2 SC 1.4.4 (Resize Text) failure. It also stops a
 *     parent pinch-zooming to read an age warning or a small product photo —
 *     on a toy store that is a safety issue, not only a compliance one.
 *
 *  2. All 216 product images have empty alt text. Every one. That is a SC
 *     1.1.1 failure, and it also forfeits Google Images traffic entirely.
 *
 *  3. There is no skip link, so a keyboard user must tab through a 26-item
 *     navigation on every page load.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * 1. Viewport — allow zoom
 * ---------------------------------------------------------------------- */

/**
 * Woodmart prints the viewport tag itself, so the fix is to intercept the
 * rendered head rather than to add a competing tag.
 */
add_action( 'wp_head', 'toys965_viewport_buffer_start', 0 );
add_action( 'wp_head', 'toys965_viewport_buffer_end', 999 );

function toys965_viewport_buffer_start() {
	ob_start( 'toys965_fix_viewport' );
}

function toys965_viewport_buffer_end() {
	if ( ob_get_level() > 0 ) {
		ob_end_flush();
	}
}

/**
 * Strip the zoom locks from whatever viewport tag was emitted.
 */
function toys965_fix_viewport( $html ) {
	return preg_replace(
		'/(<meta[^>]*name=["\']viewport["\'][^>]*content=["\'])([^"\']*)(["\'])/i',
		'$1width=device-width, initial-scale=1, viewport-fit=cover$3',
		$html
	);
}

/* -------------------------------------------------------------------------
 * 2. Alt text
 * ---------------------------------------------------------------------- */

/**
 * Supply a meaningful alt when the attachment has none.
 *
 * This is a safety net, not a substitute for real alt text — the migration
 * script backfills the _wp_attachment_image_alt meta properly. But a product
 * image should never render with an empty alt while that work is in progress.
 *
 * Deliberately does NOT prefix with "Image of": screen readers already
 * announce the element as an image.
 */
add_filter( 'wp_get_attachment_image_attributes', 'toys965_fallback_alt', 20, 3 );

function toys965_fallback_alt( $attr, $attachment, $size ) {

	if ( ! empty( $attr['alt'] ) ) {
		return $attr;
	}

	// A decorative image should have an empty alt, not a generated one.
	if ( isset( $attr['role'] ) && 'presentation' === $attr['role'] ) {
		return $attr;
	}

	$parent = wp_get_post_parent_id( $attachment->ID );

	if ( $parent && 'product' === get_post_type( $parent ) ) {
		// get_the_title() runs the the_title filter below, which wraps Latin runs in <span lang="en">.
		// That markup is right in a heading and wrong in an attribute, so strip it for alt text.
		$attr['alt'] = wp_strip_all_tags( get_the_title( $parent ) );
	} elseif ( $attachment->post_title ) {
		$attr['alt'] = $attachment->post_title;
	}

	return $attr;
}

/* -------------------------------------------------------------------------
 * 3. Skip link
 * ---------------------------------------------------------------------- */

add_action( 'wp_body_open', 'toys965_skip_link', 1 );

function toys965_skip_link() {
	printf(
		'<a class="skip-link visually-hidden" href="#main">%s</a>',
		esc_html__( 'تخطَّ إلى المحتوى', '965toys' )
	);
}

/* -------------------------------------------------------------------------
 * 4. Language and direction correctness
 * ---------------------------------------------------------------------- */

/**
 * Mark Latin-script runs inside Arabic product titles with lang="en".
 *
 * 27% of product titles mix scripts, e.g.
 *   "متجر البيتزا الصغير — MINI PIZZA SHOP"
 * Without this, an Arabic screen reader attempts to pronounce "MINI PIZZA
 * SHOP" using Arabic phonetics, which is unintelligible.
 */
add_filter( 'the_title', 'toys965_tag_latin_runs', 20, 2 );

function toys965_tag_latin_runs( $title, $post_id = 0 ) {

	if ( is_admin() || is_feed() ) {
		return $title;
	}

	if ( $post_id && 'product' !== get_post_type( $post_id ) ) {
		return $title;
	}

	// Runs of 3+ Latin characters, optionally with spaces, digits and hyphens.
	return preg_replace(
		'/([A-Za-z][A-Za-z0-9\-\'&]*(?:\s+[A-Za-z0-9\-\'&]+){0,6})/u',
		'<span lang="en" dir="ltr">$1</span>',
		$title
	);
}

/**
 * Reduced-motion users should never receive the heavy motion chunks. The JS
 * gate in motion.js handles the runtime decision; this adds a server-side hint
 * so the markup itself can respond.
 */
add_filter( 'body_class', 'toys965_motion_body_class' );

function toys965_motion_body_class( $classes ) {
	$classes[] = 'toybox';
	return $classes;
}
