<?php
/**
 * Mobile bottom nav, and the gender archive hero bands.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Mobile bottom nav (<=768px)
 * ---------------------------------------------------------------------- */

add_action( 'wp_footer', 'toys965_mobile_nav', 30 );

function toys965_mobile_nav() {

	if ( ! toys965_design_enabled() || is_admin() ) {
		return;
	}

	// Every destination is a route that exists after the taxonomy setup.
	$items = array(
		array( 'الرئيسية', '🏠', home_url( '/' ) ),
		array( 'الأولاد',  '🦖', toys965_gender_url( 'boys' ) ),
		array( 'بنات',     '🦄', toys965_gender_url( 'girls' ) ),
		array( 'الفئات',   '🧩', toys965_categories_url() ),
		array( 'ماركات',   '⭐', toys965_brands_url() ),
	);

	$current = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );

	echo '<nav class="t965-mobnav" aria-label="التنقل السريع">';
	foreach ( $items as $it ) {
		$active = untrailingslashit( $it[2] ) === untrailingslashit( $current ) ? ' is-active' : '';
		printf(
			'<a href="%s" class="%s"><i aria-hidden="true">%s</i><span>%s</span></a>',
			esc_url( $it[2] ),
			esc_attr( trim( $active ) ),
			esc_html( $it[1] ),
			esc_html( $it[0] )
		);
	}
	echo '</nav>';
}

/**
 * Archive URL for a gender term, or the shop if that term has no stock.
 */
function toys965_gender_url( $slug ) {

	if ( taxonomy_exists( 'pa_gender' ) ) {
		$t = get_term_by( 'slug', $slug, 'pa_gender' );
		if ( $t && ! is_wp_error( $t ) && $t->count > 0 ) {
			return toys965_term_url( 'gender', $t );
		}
	}

	return wc_get_page_permalink( 'shop' );
}

/**
 * Find the page using one of our templates.
 *
 * Looked up by _wp_page_template rather than by slug, so renaming the page in
 * wp-admin never produces a dead link. Cached because the mobile nav asks for
 * both URLs on every request.
 *
 * @param string $tpl e.g. "page-templates/brands-965toys.php"
 * @return string permalink, or '' when the page has not been created
 */
function toys965_template_url( $tpl ) {

	static $memo = array();
	if ( isset( $memo[ $tpl ] ) ) {
		return $memo[ $tpl ];
	}

	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_key'       => '_wp_page_template',
		'meta_value'     => $tpl,
	) );

	return $memo[ $tpl ] = $pages ? get_permalink( $pages[0] ) : '';
}

/**
 * Brands landing. Falls back to the shop while the page does not exist.
 */
function toys965_brands_url() {
	$url = toys965_template_url( 'page-templates/brands-965toys.php' );
	return $url ? $url : wc_get_page_permalink( 'shop' );
}

/**
 * Categories landing, same fallback.
 */
function toys965_categories_url() {
	$url = toys965_template_url( 'page-templates/categories-965toys.php' );
	return $url ? $url : wc_get_page_permalink( 'shop' );
}

/**
 * Suppress Woodmart's page-title banner on our own page templates.
 *
 * Our templates print their own <h1>; Woodmart's banner would add a second one
 * (an accessibility and SEO problem) in a blue that fights the cream palette.
 * The theme's filter is the clean route, and the body class is a belt-and-braces
 * hook for the CSS in case a Woodmart update drops the filter.
 */
function toys965_suppress_page_title() {

	add_filter( 'woodmart_page_title', '__return_false' );

	add_filter( 'body_class', function ( $classes ) {
		$classes[] = 't965-no-page-title';
		return $classes;
	} );
}

/* -------------------------------------------------------------------------
 * Gender archive hero bands
 * ---------------------------------------------------------------------- */

/**
 * Print the blue/teal or pink/violet band above the الأولاد / البنات archives.
 *
 * The design gives each gendered archive its own hero. Products tagged
 * "الجميع" appear in both, which is why each page still has ~165 items.
 */
add_action( 'woocommerce_before_main_content', 'toys965_gender_hero', 5 );

function toys965_gender_hero() {

	if ( ! toys965_design_enabled() || ! is_tax( 'pa_gender' ) ) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}

	$map = array(
		'boys'  => array( 'boys',  'عالم الأولاد 🚀', 'مغامرات، سيارات وأبطال — كل ما يحبه' ),
		'girls' => array( 'girls', 'عالم البنات ✨',  'دمى، إبداع وألوان — لكل يوم حكاية' ),
	);

	if ( ! isset( $map[ $term->slug ] ) ) {
		return;
	}

	list( $mod, $title, $sub ) = $map[ $term->slug ];

	printf(
		'<div class="t965-wrap"><div class="t965-genderhero t965-genderhero--%s"><h1>%s</h1><p>%s</p></div></div>',
		esc_attr( $mod ),
		esc_html( $title ),
		esc_html( $sub )
	);
}

/* -------------------------------------------------------------------------
 * Empty cart state
 * ---------------------------------------------------------------------- */

add_action( 'woocommerce_cart_is_empty', 'toys965_empty_cart', 5 );

function toys965_empty_cart() {

	if ( ! toys965_design_enabled() ) {
		return;
	}

	$art = function_exists( 'toys965_asset_url' ) ? toys965_asset_url( 'state-empty-cart' ) : '';
	?>
	<div class="t965-empty">
		<?php if ( $art ) : ?>
			<img src="<?php echo esc_url( $art ); ?>" alt="" loading="lazy">
		<?php endif; ?>
		<h2>سلتك فارغة!</h2>
		<a class="t965-btn t965-btn--coral" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">اكتشف الألعاب</a>
	</div>
	<?php
}
