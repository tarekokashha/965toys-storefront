<?php
/**
 * Footer.
 *
 * HISTORY — READ BEFORE CHANGING THE APPROACH
 *
 * Woodmart's footer is an Elementor template painted into the parent theme's
 * footer area. It cannot be unhooked: remove_all_actions('woodmart_footer')
 * leaves it in place. Two earlier attempts were both wrong:
 *
 *   1. Rendering ours alongside it — two visible <footer> landmarks.
 *   2. Buffering get_footer() output and swapping the element — blanked the
 *      whole page. Output-buffer callbacks emit NOTHING when the callback
 *      fails, and it fought LiteSpeed's own buffering. Two variants tested,
 *      both produced a zero-length response. Do not try this again.
 *
 * What works, and is what runs now: render ours on wp_footer (which lands as a
 * body child after .wd-page-wrapper) and hide Woodmart's with display:none in
 * CSS. Nothing is buffered, so the page cannot blank; and a display:none
 * element is dropped from the accessibility tree, so screen readers still see
 * exactly one contentinfo landmark.
 *
 * The owner's Elementor footer is only hidden, never deleted — every piece of
 * its content (logo, blurb, socials, contacts) is carried over below, and
 * removing the CSS rule restores the original instantly.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/**
 * Footer logo.
 *
 * The Elementor footer used primary-logo-1@300x-4.png — the round mascot badge
 * (136x156). The owner asked for the site logo proper: the 965TOYS wordmark the
 * header carries.
 *
 * Ask the THEME for its configured logo rather than hunting by filename. The
 * media library holds two byte-identical 408x102 copies of the wordmark (ids
 * 29807 and 30126) and a filename search returns them in an order nobody
 * controls. Picking the wrong one still looks right but costs a second image
 * download, because the header has already fetched the other URL. Reading the
 * theme option guarantees one file, one request, and a footer that follows the
 * header automatically if the owner ever changes the logo.
 *
 * @return string URL, or '' when nothing resolves.
 */
function toys965_footer_logo() {

	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}

	// 1. The widest "primary-logo" in the library — the wordmark.
	//
	// woodmart_get_opt('logo') is NOT the header's logo here: it returns the
	// 340x368 portrait variant while the header renders the 408x102 wordmark,
	// so the header logo lives in the theme's header-builder element instead.
	// Selecting on SHAPE is what actually identifies a wordmark, and it keeps
	// working if the files are renamed. Ties break on the lowest ID so the
	// choice is identical on every request and shares the header's cache entry.
	$candidates = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => 'image',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		's'              => 'primary-logo',
	) );

	// WP_Query ignores `orderby` once `s` is set — it sorts by search relevance
	// instead — so the tie-break has to happen here to stay deterministic.
	sort( $candidates, SORT_NUMERIC );

	foreach ( $candidates as $id ) {
		$meta = wp_get_attachment_metadata( $id );
		$w    = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
		$h    = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

		// A wordmark is markedly wider than it is tall; the mascot badge
		// (136x156) and the portrait lockup (340x368) are not.
		if ( $w && $h && $w >= $h * 3 ) {
			$src = wp_get_attachment_image_src( $id, 'full' );
			if ( $src ) {
				return $memo = $src[0];
			}
		}
	}

	// 2. The Customizer's site logo.
	$custom = get_theme_mod( 'custom_logo' );
	if ( $custom ) {
		$src = wp_get_attachment_image_src( $custom, 'full' );
		if ( $src ) {
			return $memo = $src[0];
		}
	}

	return $memo = '';
}

/**
 * The footer itself.
 *
 * Priority 5 so it renders before the mobile bottom nav (priority 30) and sits
 * above it in the document.
 */
add_action( 'wp_footer', 'toys965_footer', 5 );

function toys965_footer() {

	if ( ! toys965_design_enabled() || is_admin() ) {
		return;
	}

	$logo  = toys965_footer_logo();
	$wa    = TOYS965_WHATSAPP;
	$phone = '+' . $wa;
	$mail  = TOYS965_EMAIL;

	// Every destination is a route that exists.
	$links = array(
		array( 'الرئيسية', home_url( '/' ) ),
		array( 'كل الفئات', toys965_categories_url() ),
		array( 'الماركات', toys965_brands_url() ),
		array( 'المتجر', wc_get_page_permalink( 'shop' ) ),
	);

	$account = array(
		array( 'حسابي', wc_get_page_permalink( 'myaccount' ) ),
		array( 'سلة المشتريات', wc_get_page_permalink( 'cart' ) ),
		array( 'ألعاب الأولاد', toys965_gender_url( 'boys' ) ),
		array( 'ألعاب البنات', toys965_gender_url( 'girls' ) ),
	);
	?>
	<footer class="t965-footer" role="contentinfo">

		<div class="t965-footer__inner">

			<!-- Brand -->
			<div class="t965-footer__brand">
				<?php if ( $logo ) : ?>
					<a class="t965-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="965toys — الرئيسية">
						<img src="<?php echo esc_url( $logo ); ?>" alt="965toys" width="408" height="102" loading="lazy" decoding="async">
					</a>
				<?php endif; ?>

				<p class="t965-footer__blurb">
					أفضل وسيلة لعالمك الخاص. متجر ألعاب أطفال مليء بالمرح والسعادة —
					اختيارك في سعادة طفلك أجمل قرار.
				</p>

				<div class="t965-footer__social">
					<a href="<?php echo esc_url( TOYS965_INSTAGRAM ); ?>" target="_blank" rel="noopener" aria-label="إنستغرام">
						<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4 1.3-.1 1.7-.1 4.9-.1M12 0C8.7 0 8.3 0 7 .1 5.7.1 4.8.3 4.1.6c-.8.3-1.4.7-2.1 1.4C1.3 2.7.9 3.3.6 4.1.3 4.8.1 5.7.1 7 0 8.3 0 8.7 0 12s0 3.7.1 5c.1 1.3.2 2.2.5 2.9.3.8.7 1.4 1.4 2.1.7.7 1.3 1.1 2.1 1.4.7.3 1.6.5 2.9.5 1.3.1 1.7.1 5 .1s3.7 0 5-.1c1.3-.1 2.2-.2 2.9-.5.8-.3 1.4-.7 2.1-1.4.7-.7 1.1-1.3 1.4-2.1.3-.7.5-1.6.5-2.9.1-1.3.1-1.7.1-5s0-3.7-.1-5c-.1-1.3-.2-2.2-.5-2.9-.3-.8-.7-1.4-1.4-2.1C20.7 1.3 20.1.9 19.3.6 18.6.3 17.7.1 16.4.1 15.1 0 14.7 0 12 0Z"/><path d="M12 5.8a6.2 6.2 0 1 0 0 12.4 6.2 6.2 0 0 0 0-12.4Zm0 10.2a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/><circle cx="18.4" cy="5.6" r="1.4"/></svg>
						<span>إنستغرام</span>
					</a>
					<a href="<?php echo esc_url( TOYS965_TIKTOK ); ?>" target="_blank" rel="noopener" aria-label="تيك توك">
						<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M16.6 5.8a4.8 4.8 0 0 1-1-2.8h-3v12.2a2.5 2.5 0 1 1-2.5-2.5c.26 0 .5.04.74.11V9.7a5.6 5.6 0 0 0-.74-.05 5.6 5.6 0 1 0 5.6 5.6V9.01a7.8 7.8 0 0 0 4.55 1.46V7.4a4.8 4.8 0 0 1-3.65-1.6Z"/></svg>
						<span>تيك توك</span>
					</a>
				</div>
			</div>

			<!-- Links -->
			<nav class="t965-footer__col" aria-label="روابط سريعة">
				<h2>تسوّق</h2>
				<ul>
					<?php foreach ( $links as $l ) : ?>
						<li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<nav class="t965-footer__col" aria-label="حسابك">
				<h2>حسابك</h2>
				<ul>
					<?php foreach ( $account as $l ) : ?>
						<li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<!-- Contact -->
			<div class="t965-footer__col t965-footer__contact">
				<h2>تواصل معنا</h2>
				<p class="t965-footer__note">نوفّر لك الأفضل والأنسب</p>
				<ul>
					<li>
						<a href="<?php echo esc_url( 'https://wa.me/' . $wa ); ?>" target="_blank" rel="noopener" class="t965-footer__wa">
							<svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z"/></svg>
							<span><bdi><?php echo esc_html( $phone ); ?></bdi><small>واتساب</small></span>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( 'tel:' . $phone ); ?>">
							<svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.2.4 2.4.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2Z"/></svg>
							<span><bdi><?php echo esc_html( $phone ); ?></bdi><small>اتصال</small></span>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( 'mailto:' . $mail ); ?>">
							<svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5-8-5V6l8 5 8-5Z"/></svg>
							<span><bdi><?php echo esc_html( $mail ); ?></bdi><small>البريد الإلكتروني</small></span>
						</a>
					</li>
				</ul>
			</div>
		</div>

		<!-- Trust strip -->
		<div class="t965-footer__trust">
			<div class="t965-footer__trustinner">
				<span>🚚 توصيل خلال 24 ساعة</span>
				<span>💳 كي نت أو نقدًا عند الاستلام</span>
				<span>✅ منتجات أصلية 100%</span>
				<span>🇰🇼 جميع مناطق الكويت</span>
			</div>
		</div>

		<div class="t965-footer__bar">
			<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> 965toys — جميع الحقوق محفوظة</p>
		</div>
	</footer>
	<?php
}
