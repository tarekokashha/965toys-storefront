<?php
/**
 * 965toys — Toy Box child theme.
 *
 * Implements design_handoff_965toys_homepage across the store. Every
 * customisation lives here; nothing is edited in the Woodmart parent, so a
 * parent update can never destroy the work.
 *
 * Modules load from inc/ and each is independently switchable, so one
 * misbehaving feature can be disabled without unpicking the rest.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

define( 'TOYS965_VERSION', '4.3.0' );
define( 'TOYS965_DIR', get_stylesheet_directory() );
define( 'TOYS965_URI', get_stylesheet_directory_uri() );

/**
 * Store identity, in one place. Each is a constant so one value feeds every page that shows
 * it: the footer, the homepage, the WhatsApp order button.
 *
 * The values below are placeholders. Set the real ones in wp-config.php, above the line that
 * says "That's all, stop editing", and these defaults are skipped:
 *
 *     define( 'TOYS965_WHATSAPP',  '965XXXXXXXX' );                    // digits only
 *     define( 'TOYS965_EMAIL',     'hello@your-store.example' );
 *     define( 'TOYS965_INSTAGRAM', 'https://www.instagram.com/your-handle' );
 *     define( 'TOYS965_TIKTOK',    'https://www.tiktok.com/@your-handle' );
 *
 * Contact details live in configuration, not in the repository, so the code can be public
 * while the store's numbers stay in the store's own wp-config.php.
 */
foreach (
	array(
		'TOYS965_WHATSAPP'  => '96500000000',
		'TOYS965_EMAIL'     => 'hello@example.com',
		'TOYS965_INSTAGRAM' => 'https://www.instagram.com/example',
		'TOYS965_TIKTOK'    => 'https://www.tiktok.com/@example',
	) as $toys965_const => $toys965_default
) {
	if ( ! defined( $toys965_const ) ) {
		define( $toys965_const, $toys965_default );
	}
}
unset( $toys965_const, $toys965_default );

/**
 * Load a module from inc/, tolerating a missing file rather than fatalling the
 * whole site. A storefront should degrade, not white-screen.
 */
function toys965_module( $name ) {
	$path = TOYS965_DIR . '/inc/' . $name . '.php';
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

toys965_module( 'settings' );       // theme options + Customizer
toys965_module( 'taxonomies' );   // pa_gender / pa_age / pa_brand helpers
toys965_module( 'assets' );         // media resolver (filename -> attachment)
toys965_module( 'enqueue' );        // stylesheets + scripts + template registration
toys965_module( 'homepage-data' );  // queries + product card renderer
toys965_module( 'shelves' );        // the 12 collection shelves + brands
toys965_module( 'navigation' );     // mobile bottom nav, gender heroes, empty cart
toys965_module( 'footer' );         // design footer, sitewide
toys965_module( 'performance' );    // LCP, lazy-load and dead-weight fixes
toys965_module( 'seo-open-graph' ); // OG + Twitter tags (the WhatsApp fix)
toys965_module( 'accessibility' );  // viewport, alt text, skip link
toys965_module( 'woocommerce' );    // shop/PDP behaviour
toys965_module( 'shipping' );       // remote-area rates + express cut-off
