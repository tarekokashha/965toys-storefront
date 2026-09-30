<?php
/**
 * Media resolver.
 *
 * The design ships 56 images that were uploaded to the Media Library. Rather
 * than hardcode attachment IDs — which differ between environments and break
 * the moment anything is re-uploaded — everything is resolved by FILENAME at
 * runtime and cached.
 *
 * Filename conventions from the package:
 *   box-closed / box-open / teddy / rocket / house / car / scooter /
 *   blocks / palette        Wonder Box sprites
 *   cat-01-plush … cat-14   clay category icons
 *   banner-01-…             shelf banners. Desktop is 1200x420; the mobile
 *                           portrait version has the SAME filename, so
 *                           WordPress suffixed it "-1" on upload. They are
 *                           told apart by aspect ratio, never by the suffix,
 *                           because the suffix depends on upload order.
 *   feature-01…04, hero-main, sale-30-percent, state-*
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve an uploaded image by its base filename.
 *
 * @param string $basename e.g. "box-open", "cat-03-educational"
 * @param bool   $square   true to prefer the square (mobile) variant
 * @return array{id:int,url:string,w:int,h:int}|null
 */
function toys965_asset( $basename, $square = false ) {

	$index = toys965_asset_index();
	$key   = $square ? $basename . '@mobile' : $basename;

	return isset( $index[ $key ] ) ? $index[ $key ] : null;
}

/**
 * Just the URL, or '' — convenience for templates.
 */
function toys965_asset_url( $basename, $square = false ) {
	$a = toys965_asset( $basename, $square );
	return $a ? $a['url'] : '';
}

/**
 * Build (and cache) the filename → attachment index.
 *
 * One query for the whole library section we care about, cached for a day and
 * busted whenever an attachment is added or removed.
 *
 * @return array<string,array>
 */
function toys965_asset_index() {

	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}

	$cached = get_transient( 'toys965_asset_index' );
	if ( is_array( $cached ) ) {
		return $memo = $cached;
	}

	/*
	 * posts_per_page MUST NOT be capped.
	 *
	 * This was 400, newest-first. It worked until the Mattel catalogue import
	 * added 313 product photos and the library passed 1,600 attachments — at
	 * which point the design sprites (box-open, box-closed, teddy, rocket…),
	 * being among the OLDEST uploads, fell outside the window and every
	 * toys965_asset() lookup started returning null. The visible symptom was
	 * the Wonder Box 3D scene rendering empty on the homepage.
	 *
	 * The whole result is cached in a transient for a day, so one unbounded
	 * ID query is cheaper than the class of bug a cap reintroduces.
	 */
	$q = new WP_Query( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => array( 'image/png', 'image/jpeg', 'image/svg+xml' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	// Prime _wp_attached_file for every id in one query instead of N.
	if ( $q->posts ) {
		update_meta_cache( 'post', $q->posts );
	}

	$index = array();

	foreach ( $q->posts as $id ) {

		$file = basename( get_post_meta( $id, '_wp_attached_file', true ) );
		if ( ! $file ) {
			continue;
		}

		// Strip extension and any WordPress duplicate suffix.
		$base = preg_replace( '/\.(png|jpe?g|svg)$/i', '', $file );
		$base = preg_replace( '/-\d+$/', '', $base );

		if ( ! preg_match( '/^(box-|teddy|rocket|house|car|scooter|blocks|palette|cat-\d|banner-\d|feature-\d|hero-main|sale-30|state-|brand-)/', $base ) ) {
			continue;
		}

		$meta = wp_get_attachment_metadata( $id );
		$w    = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
		$h    = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

		// Shelf banners exist twice under one name; the square one is mobile.
		$key = $base;
		if ( 0 === strpos( $base, 'banner-' ) && $w && $w === $h ) {
			$key = $base . '@mobile';
		}

		// First match wins, so the canonical (unsuffixed, earliest) file is kept.
		if ( ! isset( $index[ $key ] ) ) {
			$index[ $key ] = array(
				'id'  => $id,
				'url' => wp_get_attachment_url( $id ),
				'w'   => $w,
				'h'   => $h,
			);
		}
	}

	set_transient( 'toys965_asset_index', $index, DAY_IN_SECONDS );

	return $memo = $index;
}

add_action( 'add_attachment', 'toys965_flush_asset_index' );
add_action( 'delete_attachment', 'toys965_flush_asset_index' );

function toys965_flush_asset_index() {
	delete_transient( 'toys965_asset_index' );
}

/**
 * Render a responsive <picture> for a shelf banner.
 *
 * The mobile file is portrait/square and serves ≤767px; the desktop file is
 * 1200x420 and serves above that. Only the visible one is fetched.
 *
 * @param string $slug   banner basename, e.g. "banner-09-heroes"
 * @param string $alt    accessible name
 * @param bool   $eager  first shelf paints eagerly, the rest lazily
 */
function toys965_banner_picture( $slug, $alt, $eager = false ) {

	$desk = toys965_asset( $slug );
	$mob  = toys965_asset( $slug, true );

	if ( ! $desk && ! $mob ) {
		return '';
	}

	$primary = $desk ? $desk : $mob;

	ob_start();
	?>
	<picture>
		<?php if ( $mob ) : ?>
			<source media="(max-width: 767px)" srcset="<?php echo esc_url( $mob['url'] ); ?>">
		<?php endif; ?>
		<img src="<?php echo esc_url( $primary['url'] ); ?>"
		     alt="<?php echo esc_attr( $alt ); ?>"
		     width="<?php echo esc_attr( $primary['w'] ); ?>"
		     height="<?php echo esc_attr( $primary['h'] ); ?>"
		     loading="<?php echo $eager ? 'eager' : 'lazy'; ?>"
		     decoding="<?php echo $eager ? 'sync' : 'async'; ?>"
		     <?php echo $eager ? 'fetchpriority="high"' : ''; ?>>
	</picture>
	<?php
	return (string) ob_get_clean();
}
