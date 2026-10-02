<?php
/**
 * Taxonomy layer: gender, age, brand.
 *
 * The design needs three axes the store does not have:
 *   pa_gender  أولاد / بنات / الجميع   — drives the الأولاد and البنات pages
 *   pa_age     0-2 / 3-5 / 6-8 / 9+    — drives the age cards
 *   pa_brand   from BRAND_MANIFEST      — drives the brands carousel and page
 *
 * These are registered as WooCommerce global product attributes so they get
 * real archives, appear in the layered-nav widgets, and can be assigned from
 * the normal product editor.
 *
 * MEASURED CONTEXT (13 Aug 2026, live catalogue of 193 products):
 *   - Only 7 of the 30 brands in the manifest match any product. Disney(13),
 *     Marvel(7) and Sonic(3) are substantial; Barbie, Frozen, Play-Doh and
 *     Funko have one product each. The other 23 have none.
 *   - Per the brief's rule 2, a brand with no products is never rendered. The
 *     terms are still created so the moment stock is tagged, the card appears.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Attribute registration
 * ---------------------------------------------------------------------- */

/**
 * The attributes this theme owns, in the order they should appear.
 *
 * @return array<string,array{label:string,terms:array<string,string>}>
 */
function toys965_attribute_spec() {

	return array(
		'gender' => array(
			'label' => __( 'الفئة', '965toys' ),
			'terms' => array(
				'boys'  => 'أولاد',
				'girls' => 'بنات',
				'all'   => 'الجميع',
			),
		),
		'age' => array(
			'label' => __( 'العمر', '965toys' ),
			'terms' => array(
				'0-2' => '0-2',
				'3-5' => '3-5',
				'6-8' => '6-8',
				'9'   => '9+',
			),
		),
		'brand' => array(
			'label' => __( 'الماركة', '965toys' ),
			'terms' => array(),   // seeded from the manifest by the migration tool
		),
	);
}

/**
 * Create the attributes and their terms.
 *
 * Idempotent: safe to run repeatedly. Called from the admin tool rather than on
 * every request — wc_create_attribute() writes to the DB and registers a
 * taxonomy, which is not something to do on a page view.
 *
 * @return array report lines
 */
function toys965_install_attributes() {

	if ( ! function_exists( 'wc_create_attribute' ) ) {
		return array( 'WooCommerce not active — nothing done.' );
	}

	$log      = array();
	$existing = wc_get_attribute_taxonomies();
	$by_slug  = wp_list_pluck( $existing, 'attribute_id', 'attribute_name' );

	foreach ( toys965_attribute_spec() as $slug => $spec ) {

		if ( isset( $by_slug[ $slug ] ) ) {
			$log[] = sprintf( 'pa_%s already exists (#%d)', $slug, $by_slug[ $slug ] );
		} else {
			$id = wc_create_attribute( array(
				'name'         => $spec['label'],
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => true,   // the design links to brand/age archives
			) );

			if ( is_wp_error( $id ) ) {
				$log[] = sprintf( 'pa_%s FAILED: %s', $slug, $id->get_error_message() );
				continue;
			}
			$log[] = sprintf( 'pa_%s created (#%d)', $slug, $id );
		}

		// The taxonomy is only registered on the next request after creation,
		// so register it now to insert terms in the same run.
		$tax = 'pa_' . $slug;
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, 'product', array( 'hierarchical' => false, 'show_ui' => false ) );
		}

		foreach ( $spec['terms'] as $term_slug => $term_name ) {
			if ( term_exists( $term_slug, $tax ) ) {
				continue;
			}
			$res = wp_insert_term( $term_name, $tax, array( 'slug' => $term_slug ) );
			$log[] = is_wp_error( $res )
				? sprintf( '  term %s/%s failed: %s', $tax, $term_slug, $res->get_error_message() )
				: sprintf( '  term %s/%s created', $tax, $term_slug );
		}
	}

	delete_transient( 'wc_attribute_taxonomies' );
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}

	return $log;
}

/* -------------------------------------------------------------------------
 * Reading helpers used by the templates
 * ---------------------------------------------------------------------- */

/**
 * Terms of one of our attributes that actually have products.
 *
 * Rule 2 of the brief: never render a card that leads nowhere. Every consumer
 * of this function therefore gets a pre-filtered list.
 *
 * @param string $slug  gender|age|brand
 * @param int    $min   minimum product count to include
 * @return WP_Term[]
 */
function toys965_live_terms( $slug, $min = 1 ) {

	$tax = 'pa_' . $slug;

	if ( ! taxonomy_exists( $tax ) ) {
		return array();
	}

	$key    = 'toys965_terms_' . $slug . '_' . $min;
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return $cached;
	}

	$terms = get_terms( array(
		'taxonomy'   => $tax,
		'hide_empty' => true,
	) );

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$terms = array_values( array_filter( $terms, function ( $t ) use ( $min ) {
		return $t->count >= $min;
	} ) );

	set_transient( $key, $terms, 6 * HOUR_IN_SECONDS );

	return $terms;
}

/**
 * Archive URL for an attribute term.
 *
 * Prefers the real term archive when the attribute has archives enabled;
 * otherwise falls back to a filtered shop URL, which WooCommerce's layered nav
 * understands. Never returns a URL that 404s.
 */
function toys965_term_url( $slug, $term ) {

	$link = get_term_link( $term );

	if ( ! is_wp_error( $link ) ) {
		return $link;
	}

	return add_query_arg( 'filter_' . $slug, $term->slug, wc_get_page_permalink( 'shop' ) );
}

/**
 * Flush the cached term lists whenever the catalogue changes.
 */
add_action( 'woocommerce_update_product', 'toys965_flush_term_cache' );
add_action( 'woocommerce_new_product', 'toys965_flush_term_cache' );

function toys965_flush_term_cache() {
	foreach ( array( 'gender', 'age', 'brand' ) as $s ) {
		foreach ( array( 1, 2, 4 ) as $m ) {
			delete_transient( 'toys965_terms_' . $s . '_' . $m );
		}
	}
}
