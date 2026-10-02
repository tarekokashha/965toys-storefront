<?php
/**
 * Data providers and renderers for the homepage.
 *
 * Every query lives here so the template stays readable, and results are
 * cached — the homepage is the most requested page on the site.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Product card — used by the shelves and any rail
 * ---------------------------------------------------------------------- */

/**
 * Render one product card in the v2 design.
 *
 * Add-to-cart uses WooCommerce's AJAX classes so the confetti/balloon
 * celebration hooks the real `added_to_cart` event rather than a bare click.
 * Variable products link through to the PDP instead, since they need options
 * chosen before they can be added.
 *
 * @param WC_Product $p
 */
function toys965_product_card( $p ) {

	if ( ! $p instanceof WC_Product ) {
		return;
	}

	$id     = $p->get_id();
	$img_id = $p->get_image_id();
	$img    = $img_id ? wp_get_attachment_image_src( $img_id, 'woocommerce_thumbnail' ) : false;
	$rating = (float) $p->get_average_rating();
	$addable = $p->is_purchasable() && $p->is_in_stock() && ! $p->is_type( 'variable' );
	?>
	<div class="t965-prod" data-t965-tilt>
		<div class="t965-prod__media">
			<?php if ( $img ) : ?>
				<img src="<?php echo esc_url( $img[0] ); ?>"
				     alt="<?php echo esc_attr( $p->get_name() ); ?>"
				     width="<?php echo esc_attr( $img[1] ); ?>"
				     height="<?php echo esc_attr( $img[2] ); ?>"
				     loading="lazy" decoding="async">
			<?php endif; ?>

			<?php if ( $rating > 0 ) : ?>
				<span class="t965-prod__rating">⭐ <?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="t965-ptitle">
			<a class="t965-prod__link" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
				<?php echo esc_html( $p->get_name() ); ?>
			</a>
		</h3>

		<div class="t965-prod__foot">
			<span class="t965-price"><?php echo wp_kses_post( $p->get_price_html() ); ?></span>

			<?php if ( $addable ) : ?>
				<a href="<?php echo esc_url( $p->add_to_cart_url() ); ?>"
				   class="t965-btn t965-btn--ink add_to_cart_button ajax_add_to_cart"
				   data-product_id="<?php echo esc_attr( $id ); ?>"
				   data-quantity="1" rel="nofollow"
				   aria-label="<?php echo esc_attr( sprintf( 'أضف %s إلى السلة', $p->get_name() ) ); ?>">+ أضف</a>
			<?php else : ?>
				<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" class="t965-btn t965-btn--ink">التفاصيل</a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Age bands
 * ---------------------------------------------------------------------- */

/**
 * The four age cards.
 *
 * Now that pa_age exists and the catalogue is tagged, these link to REAL
 * attribute archives. A band with no products is dropped rather than linking
 * to an empty result (rule 2).
 *
 * Sprites reuse the Wonder Box toys, as the design specifies:
 * teddy / blocks / scooter / rocket.
 */
function toys965_age_bands() {

	$spec = array(
		array( 'key' => '0-2',  'slug' => '0-2',    'label' => '0 – 2 سنة',   'desc' => 'ألعاب الرضع الآمنة والحسّية', 'sprite' => 'teddy' ),
		array( 'key' => '3-5',  'slug' => '3-5',    'label' => '3 – 5 سنوات', 'desc' => 'تعليم مبكر ولعب تخيّلي',      'sprite' => 'blocks' ),
		array( 'key' => '6-8',  'slug' => '6-8',    'label' => '6 – 8 سنوات', 'desc' => 'بازل، حركة، وإبداع',          'sprite' => 'scooter' ),
		array( 'key' => '9',    'slug' => '9-plus', 'label' => '9+ سنوات',    'desc' => 'تحكم عن بعد وتحديات',         'sprite' => 'rocket' ),
	);

	$out = array();

	foreach ( $spec as $b ) {

		$term = taxonomy_exists( 'pa_age' ) ? get_term_by( 'slug', $b['slug'], 'pa_age' ) : null;

		if ( ! $term || is_wp_error( $term ) || $term->count < 1 ) {
			continue;   // no stock in this band — do not render a dead card
		}

		$b['url']    = toys965_term_url( 'age', $term );
		$b['count']  = $term->count;
		$b['sprite'] = toys965_asset_url( $b['sprite'] );
		$out[]       = $b;
	}

	return $out;
}

/* -------------------------------------------------------------------------
 * Categories
 * ---------------------------------------------------------------------- */

/**
 * Categories for the rail, largest first, only ones with stock.
 *
 * @param int $limit
 */
function toys965_home_categories( $limit = 14 ) {

	$cached = get_transient( 'toys965_home_cats_' . (int) $limit );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	// Gender lives in pa_gender now; these two as categories are legacy noise.
	$skip = array( 'uncategorized', 'بنات', 'أولاد' );

	$terms = array_filter( $terms, function ( $t ) use ( $skip ) {
		return $t->count > 0 && ! in_array( $t->slug, $skip, true )
			&& ! in_array( toys965_normalise_ar( $t->name ), array_map( 'toys965_normalise_ar', $skip ), true );
	} );

	usort( $terms, function ( $a, $b ) { return $b->count - $a->count; } );

	$out = array();
	foreach ( array_slice( $terms, 0, $limit ) as $t ) {
		$thumb = get_term_meta( $t->term_id, 'thumbnail_id', true );
		$src   = $thumb ? wp_get_attachment_image_src( $thumb, 'medium' ) : false;
		$link  = get_term_link( $t );

		$out[] = array(
			'name'  => $t->name,
			'url'   => is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link,
			'image' => $src ? $src[0] : '',
			'emoji' => '🎁',
			'count' => $t->count,
		);
	}

	set_transient( 'toys965_home_cats_' . (int) $limit, $out, 6 * HOUR_IN_SECONDS );

	return $out;
}

/* -------------------------------------------------------------------------
 * Misc
 * ---------------------------------------------------------------------- */

/**
 * Where the sale CTA points.
 *
 * If nothing is genuinely on sale, send shoppers to the shop rather than an
 * empty on-sale archive — an empty result proves the banner is a lie.
 */
function toys965_sale_url() {

	$on_sale = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();

	return ! empty( $on_sale )
		? add_query_arg( 'on_sale', '1', wc_get_page_permalink( 'shop' ) )
		: wc_get_page_permalink( 'shop' );
}

/**
 * Normalise Arabic for comparison.
 *
 * The catalogue mixes hamza forms (ا/أ/إ/آ), ta marbuta vs ha, and uses
 * tatweel padding for visual stretching — the menu contains both "بنات" and
 * "بنـــــــات". Without this, name lookups silently miss.
 */
function toys965_normalise_ar( $s ) {

	$s = (string) $s;
	$s = preg_replace( '/\x{0640}+/u', '', $s );
	$s = preg_replace( '/[\x{0622}\x{0623}\x{0625}]/u', 'ا', $s );
	$s = preg_replace( '/\x{0629}/u', 'ه', $s );
	$s = preg_replace( '/\x{0649}/u', 'ي', $s );
	$s = preg_replace( '/[\x{064B}-\x{065F}]/u', '', $s );

	return trim( preg_replace( '/\s+/u', '', $s ) );
}

add_action( 'woocommerce_update_product', 'toys965_flush_home_cache' );
add_action( 'edited_product_cat', 'toys965_flush_home_cache' );

function toys965_flush_home_cache() {
	foreach ( array( 10, 14 ) as $n ) {
		delete_transient( 'toys965_home_cats_' . $n );
	}
}
