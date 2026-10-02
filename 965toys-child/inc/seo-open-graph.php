<?php
/**
 * Open Graph + Twitter card tags.
 *
 * WHY THIS IS THE HIGHEST-VALUE FILE IN THE THEME
 *
 * The site currently emits ZERO Open Graph tags on every page including product
 * pages. In Kuwait — 99% internet penetration, ~4.4M TikTok and ~3.0M Instagram
 * reach against a 5.3M population — product links travel by WhatsApp message and
 * Instagram DM far more than by search. Today every one of those shares renders
 * as a bare grey URL with no image, no title and no price.
 *
 * Notes that cost real money if ignored:
 *  - WhatsApp needs og:image:width and og:image:height or it frequently skips
 *    rendering the preview entirely.
 *  - WhatsApp is unreliable above roughly 600 KB, so we target a <300 KB image.
 *  - WhatsApp caches previews hard. When an OG image changes, the URL must
 *    change too, so we append the attachment's modified time as ?v=.
 *
 * Product schema is deliberately NOT emitted here: WooCommerce core already
 * outputs Product + Offer + BreadcrumbList JSON-LD, and a second copy creates
 * duplicate-schema errors in Search Console.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the tags in <head>.
 *
 * Priority 5 so these land before any plugin that might later add its own and
 * so they are easy to spot when debugging the rendered source.
 */
add_action( 'wp_head', 'toys965_open_graph', 5 );

function toys965_open_graph() {

	// If an SEO plugin is ever installed and starts emitting og:title, bail out
	// rather than duplicate it. Duplicate OG tags produce unpredictable previews.
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) ) {
		return;
	}

	$data = toys965_og_data();
	if ( empty( $data ) ) {
		return;
	}

	$tags = array(
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:locale'      => 'ar_KW',
		'og:type'        => $data['type'],
		'og:title'       => $data['title'],
		'og:description' => $data['description'],
		'og:url'         => $data['url'],
	);

	foreach ( $tags as $property => $content ) {
		if ( '' === $content ) {
			continue;
		}
		printf(
			'<meta property="%s" content="%s" />' . "\n",
			esc_attr( $property ),
			esc_attr( $content )
		);
	}

	if ( ! empty( $data['image'] ) ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $data['image'] ) );
		printf( '<meta property="og:image:secure_url" content="%s" />' . "\n", esc_url( $data['image'] ) );
		printf( '<meta property="og:image:alt" content="%s" />' . "\n", esc_attr( $data['title'] ) );

		// WhatsApp often refuses to render a preview without explicit dimensions.
		if ( ! empty( $data['image_w'] ) && ! empty( $data['image_h'] ) ) {
			printf( '<meta property="og:image:width" content="%d" />' . "\n", (int) $data['image_w'] );
			printf( '<meta property="og:image:height" content="%d" />' . "\n", (int) $data['image_h'] );
		}
	}

	// Product-specific price tags. These drive the rich preview in several
	// messaging clients and in Meta's commerce surfaces.
	if ( ! empty( $data['price'] ) ) {
		printf( '<meta property="product:price:amount" content="%s" />' . "\n", esc_attr( $data['price'] ) );
		printf( '<meta property="product:price:currency" content="%s" />' . "\n", esc_attr( get_woocommerce_currency() ) );
		printf( '<meta property="product:availability" content="%s" />' . "\n", esc_attr( $data['availability'] ) );
	}

	// Twitter falls back to OG for everything except the card type.
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";

	// The site has no meta description anywhere. Emit one from the same source.
	if ( ! empty( $data['description'] ) ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $data['description'] ) );
	}
}

/**
 * Assemble the values for the current request.
 *
 * @return array
 */
function toys965_og_data() {

	$out = array(
		'type'         => 'website',
		'title'        => '',
		'description'  => '',
		'url'          => '',
		'image'        => '',
		'image_w'      => 0,
		'image_h'      => 0,
		'price'        => '',
		'availability' => '',
	);

	if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return $out;
		}

		$out['type']  = 'product';
		$out['title'] = $product->get_name();
		$out['url']   = get_permalink();

		// The store's selling copy lives in short_description, not the long
		// description — 171 of 193 products have a rich one and only 22 have
		// any long description at all.
		$summary = $product->get_short_description();
		if ( '' === trim( $summary ) ) {
			$summary = $product->get_description();
		}
		$out['description'] = toys965_trim_words( $summary, 30 );

		$image = toys965_image_for( $product->get_image_id() );
		$out   = array_merge( $out, $image );

		// Prices must render with the store's configured decimals. KWD is a
		// 3-decimal currency, so 4.5 must appear as 4.500.
		$price = $product->get_price();
		if ( '' !== $price ) {
			$out['price'] = number_format( (float) $price, wc_get_price_decimals(), '.', '' );
		}
		$out['availability'] = $product->is_in_stock() ? 'in stock' : 'out of stock';

		return $out;
	}

	if ( is_product_category() || is_product_tag() ) {
		$term               = get_queried_object();
		$out['title']       = $term->name;
		$out['description'] = toys965_trim_words( $term->description, 30 );
		$out['url']         = get_term_link( $term );

		$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
		$out      = array_merge( $out, toys965_image_for( $thumb_id ) );

		return $out;
	}

	// Front page MUST be checked before is_singular(). The homepage here is a
	// static page, so is_singular() is true for it — falling through would
	// label the storefront as og:type "article", title it with the page's
	// internal name ("Home Toys"), and scrape its description out of the
	// page body, which currently still contains Elementor's placeholder text.
	if ( is_front_page() || is_home() ) {

		$out['type']  = 'website';
		$out['url']   = home_url( '/' );
		$out['title'] = toys965_site_share_title();

		$description = get_option( 'toys965_share_description' );
		if ( ! $description ) {
			$description = get_bloginfo( 'description' );
		}
		$out['description'] = toys965_trim_words( $description, 30 );

		return array_merge( $out, toys965_image_for( toys965_default_share_image_id() ) );
	}

	if ( is_singular() ) {
		$out['type']        = 'article';
		$out['title']       = get_the_title();
		$out['description'] = toys965_trim_words( get_the_excerpt(), 30 );
		$out['url']         = get_permalink();
		$out                = array_merge( $out, toys965_image_for( get_post_thumbnail_id() ) );

		// Never let a page fall back to an imageless share.
		if ( empty( $out['image'] ) ) {
			$out = array_merge( $out, toys965_image_for( toys965_default_share_image_id() ) );
		}

		return $out;
	}

	// Archives and everything else.
	$out['title']       = toys965_site_share_title();
	$out['description'] = toys965_trim_words( get_bloginfo( 'description' ), 30 );
	$out['url']         = home_url( '/' );

	return array_merge( $out, toys965_image_for( toys965_default_share_image_id() ) );
}

/**
 * Share title for the storefront.
 *
 * The WordPress site title is just "965toy", which tells a WhatsApp recipient
 * nothing. Pair it with the tagline so the preview says what the shop sells.
 */
function toys965_site_share_title() {

	$name    = get_bloginfo( 'name' );
	$tagline = get_bloginfo( 'description' );

	return $tagline ? $name . ' — ' . $tagline : $name;
}

/**
 * Resolve an attachment to a share-sized image plus its dimensions.
 *
 * Size choice matters more than it looks:
 *  - 'full' can be enormous. Some product uploads on this store are 879 KB,
 *    and WhatsApp becomes unreliable above roughly 600 KB.
 *  - 'large' is capped at 1024px wide by default, which would DOWNSCALE a
 *    purpose-made 1200x630 share card and waste it.
 *
 * So: use full when it is already within share dimensions, otherwise large.
 *
 * @param int $attachment_id
 * @return array{image:string,image_w:int,image_h:int}
 */
function toys965_image_for( $attachment_id ) {

	$empty = array( 'image' => '', 'image_w' => 0, 'image_h' => 0 );

	if ( ! $attachment_id ) {
		return $empty;
	}

	$full = wp_get_attachment_image_src( $attachment_id, 'full' );

	// 1200x630 is the canonical OG size; anything at or under that width is
	// already share-ready and must not be downscaled.
	if ( $full && (int) $full[1] <= 1200 ) {
		$src = $full;
	} else {
		$src = wp_get_attachment_image_src( $attachment_id, 'large' );
	}

	if ( ! $src ) {
		return $empty;
	}

	// Cache-bust on the attachment's own modification time so that replacing a
	// product photo also refreshes the preview WhatsApp has cached.
	$modified = get_post_modified_time( 'U', true, $attachment_id );
	$url      = add_query_arg( 'v', (string) $modified, $src[0] );

	return array(
		'image'   => $url,
		'image_w' => (int) $src[1],
		'image_h' => (int) $src[2],
	);
}

/**
 * Site-wide fallback share image.
 *
 * Tried in order, because an imageless share is barely better than no OG tag
 * at all — WhatsApp renders it as a small text row rather than a rich card:
 *
 *   1. an explicit share image set for this theme
 *   2. WordPress's custom_logo
 *   3. Woodmart's own logo option — theme mods are stored PER THEME, so
 *      switching from Woodmart to this child theme leaves custom_logo empty
 *      while Woodmart's own setting survives. Without this step every share
 *      on the site would silently lose its image the moment the child theme
 *      was activated.
 *   4. the site icon (favicon), which is always square but always present
 */
function toys965_default_share_image_id() {

	$id = (int) get_option( 'toys965_share_image', 0 );
	if ( $id ) {
		return $id;
	}

	$id = (int) get_theme_mod( 'custom_logo', 0 );
	if ( $id ) {
		return $id;
	}

	// Woodmart keeps its options in a single global array, independent of the
	// active stylesheet.
	$wd = get_option( 'woodmart_options', array() );
	if ( is_array( $wd ) ) {
		foreach ( array( 'logo', 'header_logo', 'logo_image' ) as $key ) {
			if ( empty( $wd[ $key ] ) ) {
				continue;
			}
			$value = $wd[ $key ];
			if ( is_array( $value ) && ! empty( $value['id'] ) ) {
				return (int) $value['id'];
			}
			if ( is_numeric( $value ) ) {
				return (int) $value;
			}
			if ( is_string( $value ) ) {
				$found = attachment_url_to_postid( $value );
				if ( $found ) {
					return (int) $found;
				}
			}
		}
	}

	return (int) get_option( 'site_icon', 0 );
}

/**
 * Strip shortcodes/markup and clamp to a word count.
 *
 * Arabic word counting via wp_trim_words is reliable here because the source
 * copy is space-separated prose.
 */
function toys965_trim_words( $text, $words ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $text ), true );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( wp_trim_words( $text, $words, '…' ) );
}
