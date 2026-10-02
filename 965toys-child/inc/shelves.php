<?php
/**
 * The 12 collection shelves, and the brand rail.
 *
 * Order and Arabic copy come verbatim from data/BANNER_COPY_MAP.csv — that
 * copy is approved and must not be rewritten.
 *
 * WHERE THE PRODUCTS COME FROM
 * The design was drawn against a richer catalogue than this store has. Measured
 * against the live 193 products:
 *
 *   7 shelves are healthy      interactive, little-artist, plush, accessories,
 *                              heroes, science, classic-dolls
 *   4 shelves are thin (1-3)   slime-experiments, electric-rides, rc-cars,
 *                              mother-baby
 *   1 shelf is empty           surprise-eggs  -> suppressed entirely
 *
 * Per the brief's rule 2 a shelf with no products is never rendered, and per
 * the owner's decision the thin ones render honestly with whatever exists
 * rather than being padded with unrelated stock.
 *
 * Each shelf resolves to a REAL product_cat archive where one exists, so the
 * banner and the "اظهار الكل" link always point somewhere that works.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shelf definitions.
 *
 * cats  : product_cat names to draw from, in priority order
 * terms : title keywords used when the categories alone are too coarse
 */
function toys965_shelf_spec() {

	return array(
		array( 'slug' => 'interactive',       'banner' => 'banner-01-interactive',
		       'title' => 'اللعب يبدأ بخيالهم',  'sub' => 'ألعاب تفاعلية وتمثيلية تحوّل كل لحظة إلى مغامرة',
		       'cats' => array( 'ألعاب تفاعلية', 'ألعاب جماعية' ), 'terms' => array( 'تفاعلي', 'تمثيلي' ) ),

		array( 'slug' => 'slime-experiments', 'banner' => 'banner-02-slime-experiments',
		       'title' => 'اخلط، جرّب واستمتع', 'sub' => 'سلايم وتجارب مدهشة لفضول لا يتوقف',
		       'cats' => array( 'سلايم ورمل وصلصال' ), 'terms' => array( 'سلايم', 'صلصال', 'رمل' ) ),

		array( 'slug' => 'little-artist',     'banner' => 'banner-03-little-artist',
		       'title' => 'أطلق إبداعهم بالألوان', 'sub' => 'أدوات رسم وأشغال يدوية لأفكار بلا حدود',
		       'cats' => array( 'رسوم وفنون', 'القرطاسية' ), 'terms' => array( 'رسم', 'تلوين', 'ألوان' ) ),

		array( 'slug' => 'electric-rides',    'banner' => 'banner-04-electric-rides',
		       'title' => 'مغامرتهم تبدأ من هنا', 'sub' => 'سيارات ركوب كهربائية للمرح في كل جولة',
		       'cats' => array( 'سكوترات ودراجات هوائية' ), 'terms' => array( 'كهربائي', 'ركوب', 'سكوتر', 'دراج' ) ),

		array( 'slug' => 'rc-cars',           'banner' => 'banner-05-rc-cars',
		       'title' => 'تحكّم. تسابق. انطلق.', 'sub' => 'سيارات سريعة لعشاق السباق والتحدي',
		       'cats' => array( 'التحكم عن بعد', 'سيارات شحن' ), 'terms' => array( 'ريموت', 'تحكم' ) ),

		array( 'slug' => 'plush',             'banner' => 'banner-06-plush',
		       'title' => 'أصدقاء للعناق دائمًا', 'sub' => 'دمى ناعمة ترافقهم في اللعب والنوم',
		       'cats' => array( 'ألعاب قطيفة', 'دمى وشخصيات', 'ألعاب حيوانات' ), 'terms' => array( 'قطيفة', 'دمية' ) ),

		array( 'slug' => 'accessories',       'banner' => 'banner-07-accessories',
		       'title' => 'تفاصيل صغيرة… وفرحة أكبر', 'sub' => 'إكسسوارات مرحة تكمل شخصيتهم المفضلة',
		       'cats' => array( 'الموضة والميكاب', 'ملابس تنكرية' ), 'terms' => array( 'اكسسوار', 'ميكاب' ) ),

		// بيض المفاجآت — zero products in this catalogue. Deliberately omitted;
		// see toys965_shelves() which skips anything that resolves to nothing.
		array( 'slug' => 'surprise-eggs',     'banner' => 'banner-08-surprise-eggs',
		       'title' => 'افتح المفاجأة!',    'sub' => 'كل بيضة تخبّئ لعبة ولحظة فرح جديدة',
		       'cats' => array(), 'terms' => array( 'بيضة', 'مفاجأ' ) ),

		array( 'slug' => 'heroes',            'banner' => 'banner-09-heroes',
		       'title' => 'أبطالهم المفضلون بانتظارهم', 'sub' => 'شخصيات ومغامرات تجعلهم أبطال القصة',
		       'cats' => array( 'ألعاب شخصيات', 'دمى وشخصيات' ), 'terms' => array( 'مارفل', 'سبايدر', 'سونيك' ) ),

		array( 'slug' => 'science',           'banner' => 'banner-10-science',
		       'title' => 'اكتشف العالم باللعب', 'sub' => 'علوم وتجارب تشعل الفضول وتبني المعرفة',
		       'cats' => array( 'ألعاب تعليمية', 'ألعاب تركيب وبازل' ), 'terms' => array( 'تعليمي', 'علوم' ) ),

		array( 'slug' => 'mother-baby',       'banner' => 'banner-11-mother-baby',
		       'title' => 'كل ما يحتاجه طفلك', 'sub' => 'أساسيات مختارة بعناية لراحة الأم والصغير',
		       'cats' => array( 'الأطفال والرضع', 'ألعاب السن المبكر' ), 'terms' => array( 'رضع', 'بيبي' ) ),

		array( 'slug' => 'classic-dolls',     'banner' => 'banner-12-classic-dolls',
		       'title' => 'حكايات لا تنتهي',   'sub' => 'دمى محبوبة تلهم الخيال كل يوم',
		       'cats' => array( 'دمى وشخصيات', 'ألعاب قطيفة' ), 'terms' => array( 'دمية', 'باربي' ) ),
	);
}

/**
 * Shelves that actually have products, with their products and archive URL.
 *
 * @param int $per How many products per shelf.
 * @return array
 */
function toys965_shelves( $per = 8 ) {

	$cached = get_transient( 'toys965_shelves_' . (int) $per );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$out = array();

	foreach ( toys965_shelf_spec() as $shelf ) {

		$term_ids = array();
		$archive  = '';

		foreach ( $shelf['cats'] as $name ) {
			$t = toys965_find_cat( $name );
			if ( $t && $t->count > 0 ) {
				$term_ids[] = $t->term_id;
				if ( ! $archive ) {
					$link    = get_term_link( $t );
					$archive = is_wp_error( $link ) ? '' : $link;
				}
			}
		}

		if ( empty( $term_ids ) ) {
			continue;   // rule 2: nothing to show, so no card at all
		}

		$ids = wc_get_products( array(
			'status'       => 'publish',
			'limit'        => $per,
			'orderby'      => 'popularity',
			'order'        => 'DESC',
			'stock_status' => 'instock',
			'category'     => array_map( function ( $id ) {
				$t = get_term( $id, 'product_cat' );
				return $t && ! is_wp_error( $t ) ? $t->slug : '';
			}, $term_ids ),
			'return'       => 'ids',
		) );

		if ( empty( $ids ) ) {
			continue;
		}

		$products = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p && $p->is_visible() ) {
				$products[] = $p;
			}
		}

		if ( empty( $products ) ) {
			continue;
		}

		$shelf['products'] = $products;
		$shelf['archive']  = $archive ? $archive : wc_get_page_permalink( 'shop' );
		$out[]             = $shelf;
	}

	set_transient( 'toys965_shelves_' . (int) $per, $out, 2 * HOUR_IN_SECONDS );

	return $out;
}

/**
 * Find a product_cat by name, tolerating the store's inconsistent Arabic.
 *
 * The catalogue mixes hamza forms and uses tatweel padding for visual
 * stretching ("بنـــــــات"), so a plain get_term_by('name') misses.
 */
function toys965_find_cat( $name ) {

	static $all = null;

	if ( null === $all ) {
		$all   = array();
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$all[ toys965_normalise_ar( $t->name ) ] = $t;
			}
		}
	}

	$k = toys965_normalise_ar( $name );

	return isset( $all[ $k ] ) ? $all[ $k ] : null;
}

/**
 * Brands that have products, for the carousel and the brands page.
 *
 * Measured: only 5 of the 30 brands in BRAND_MANIFEST.csv match this
 * catalogue. The rest are never rendered.
 */
function toys965_brands() {

	$terms = toys965_live_terms( 'brand', 1 );

	$out = array();
	foreach ( $terms as $t ) {
		$out[] = array(
			'name'  => $t->name,
			'slug'  => $t->slug,
			'count' => $t->count,
			'url'   => toys965_term_url( 'brand', $t ),
		);
	}

	return $out;
}

add_action( 'woocommerce_update_product', 'toys965_flush_shelves' );
add_action( 'woocommerce_new_product', 'toys965_flush_shelves' );

function toys965_flush_shelves() {
	foreach ( array( 4, 8, 12 ) as $n ) {
		delete_transient( 'toys965_shelves_' . $n );
	}
}

/* -------------------------------------------------------------------------
 * Full listings for the الفئات / الماركات pages
 * ---------------------------------------------------------------------- */

/**
 * Every product category that has stock, largest first.
 *
 * Gender lives in pa_gender now, so the legacy بنات / أولاد category terms are
 * excluded here — they would otherwise dominate the grid with 135 and 115.
 */
function toys965_all_categories() {

	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$skip = array( 'uncategorized', 'بنات', 'أولاد' );
	$skip_n = array_map( 'toys965_normalise_ar', $skip );

	$terms = array_filter( $terms, function ( $t ) use ( $skip, $skip_n ) {
		return $t->count > 0
			&& ! in_array( $t->slug, $skip, true )
			&& ! in_array( toys965_normalise_ar( $t->name ), $skip_n, true );
	} );

	usort( $terms, function ( $a, $b ) { return $b->count - $a->count; } );

	$out = array();
	foreach ( $terms as $t ) {
		$thumb = get_term_meta( $t->term_id, 'thumbnail_id', true );
		$src   = $thumb ? wp_get_attachment_image_src( $thumb, 'medium' ) : false;
		$link  = get_term_link( $t );
		$out[] = array(
			'name'  => $t->name,
			'count' => $t->count,
			'url'   => is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link,
			'image' => $src ? $src[0] : '',
			'emoji' => '🎁',
		);
	}

	return $out;
}

/**
 * EVERY brand term, stocked or not, with its logo.
 *
 * The homepage rail still shows only stocked brands; this full list is for the
 * الماركات page, where the owner asked for the complete wall. Empty brands are
 * marked so the template can dim them rather than pretend they have stock.
 *
 * Logos resolve by filename convention brand-{slug} via the media index, so no
 * attachment IDs are stored anywhere.
 */
function toys965_all_brands() {

	if ( ! taxonomy_exists( 'pa_brand' ) ) {
		return array();
	}

	$terms = get_terms( array( 'taxonomy' => 'pa_brand', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	// Stocked brands first, then alphabetical, so the wall leads with reality.
	usort( $terms, function ( $a, $b ) {
		if ( ( $a->count > 0 ) !== ( $b->count > 0 ) ) {
			return $b->count - $a->count;
		}
		return strcmp( $a->slug, $b->slug );
	} );

	$out = array();
	foreach ( $terms as $t ) {
		$out[] = array(
			'name'  => $t->name,
			'slug'  => $t->slug,
			'count' => (int) $t->count,
			'url'   => toys965_term_url( 'brand', $t ),
			'logo'  => toys965_asset_url( 'brand-' . $t->slug ),
		);
	}

	return $out;
}
