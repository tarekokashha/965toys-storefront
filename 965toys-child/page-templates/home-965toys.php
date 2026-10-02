<?php
/**
 * Template Name: 965toys Homepage
 *
 * Implements "965Toys Store v2.dc.html".
 *
 * Everything dynamic comes from WooCommerce. No product, price, rating or
 * shelf is hardcoded — the products in the prototype were real store items
 * used as reference only. Prices render through WooCommerce so the store's
 * 0.000 د.ك format is preserved exactly.
 *
 * Arabic copy is verbatim from the prototype and BANNER_COPY_MAP.csv.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

get_header();

$wa   = TOYS965_WHATSAPP;
$shop = wc_get_page_permalink( 'shop' );
?>

<div class="t965-page" id="main">

	<div class="t965-balloons" id="t965-balloons" aria-hidden="true"></div>

	<!-- ================= 1. MARQUEE ================= -->
	<div class="t965-marquee" role="complementary" aria-label="إعلانات المتجر">
		<div class="t965-marquee__track">
			<?php
			$ticker = array(
				'🚚 توصيل سريع لجميع مناطق الكويت',
				'🎁 خصم حتى 30% على تشكيلة مختارة',
				'💬 اطلب مباشرة عبر واتساب +' . $wa,
				'⭐ أكثر من 25 قسمًا من الألعاب',
			);
			for ( $i = 0; $i < 2; $i++ ) :
				?>
				<span class="t965-marquee__group"<?php echo $i ? ' aria-hidden="true"' : ''; ?>>
					<?php foreach ( $ticker as $item ) : ?>
						<span><?php echo esc_html( $item ); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endfor; ?>
		</div>
	</div>

	<!-- ================= 2. WONDER BOX HERO ================= -->
	<section class="t965-wb-pin" id="hero">
		<div class="t965-wb-stick">
			<div class="t965-wrap t965-hero-grid">

				<div class="t965-hero-copy">
					<div class="t965-eyebrow">عالم ألعاب مختار لأطفال الكويت 🇰🇼</div>
					<h1 class="t965-h1">كل لعبة تفتح عالماً من الفرح</h1>
					<p class="t965-lede">ألعاب مختارة لكل عمر، توصيل سريع لجميع مناطق الكويت، وتجربة شراء سهلة وآمنة.</p>
					<div style="display:flex;gap:12px;flex-wrap:wrap">
						<a class="t965-btn t965-btn--coral" href="<?php echo esc_url( $shop ); ?>">اكتشف الألعاب</a>
						<a class="t965-btn t965-btn--outline" href="#ages">تسوق حسب العمر</a>
					</div>
					<div class="t965-chips">
						<span class="t965-chip">🚚 توصيل سريع</span>
						<span class="t965-chip">💳 كي نت والدفع عند الاستلام</span>
						<span class="t965-chip">✅ منتجات أصلية 100%</span>
					</div>
				</div>

				<?php
				// Scene geometry is copied verbatim from the prototype's #wbscene:
				// data-s = launch stagger, data-d = travel duration, data-r = resting tilt.
				$toys = array(
					array( 'teddy',   '0.06', '0.34', '-6', 'left:0;top:5%;width:33%' ),
					array( 'rocket',  '0.13', '0.36', '7',  'left:34%;top:0;width:21%' ),
					array( 'house',   '0.20', '0.36', '4',  'left:61%;top:8%;width:29%' ),
					array( 'car',     '0.27', '0.36', '-5', 'left:37%;top:28%;width:29%' ),
					array( 'scooter', '0.34', '0.38', '6',  'left:68%;top:40%;width:24%' ),
					array( 'blocks',  '0.41', '0.36', '-4', 'left:36%;top:52%;width:23%' ),
					array( 'palette', '0.48', '0.36', '8',  'left:60%;top:66%;width:22%' ),
				);
				?>
				<div class="t965-wb-sc" id="t965-wb" aria-hidden="true">

					<div style="position:absolute;left:24%;top:10%;width:9px;height:9px;border-radius:50%;background:#FFC94A;animation:t965-twinkle 3.2s ease-in-out infinite"></div>
					<div style="position:absolute;left:88%;top:30%;width:8px;height:8px;border-radius:50%;background:#33C7C9;animation:t965-twinkle 4.1s ease-in-out .8s infinite"></div>
					<div style="position:absolute;left:8%;top:58%;width:7px;height:7px;border-radius:50%;background:#7567E8;animation:t965-twinkle 3.6s ease-in-out 1.4s infinite"></div>

					<div id="t965-wb-glow" style="position:absolute;left:8%;top:30%;width:52%;height:50%;border-radius:50%;background:radial-gradient(closest-side,rgba(255,201,74,.85),rgba(255,201,74,0));opacity:0;transform:scale(.35)"></div>

					<img id="t965-wb-closed" src="<?php echo esc_url( toys965_asset_url( 'box-closed' ) ); ?>" alt="" fetchpriority="high" decoding="sync" style="position:absolute;left:2%;bottom:2%;width:56%">
					<img id="t965-wb-open"   src="<?php echo esc_url( toys965_asset_url( 'box-open' ) ); ?>"   alt="" decoding="sync" style="position:absolute;left:4%;bottom:1%;width:52%;opacity:0">
					<div id="t965-wb-mouth" style="position:absolute;left:30%;top:58%;width:1px;height:1px"></div>

					<?php foreach ( $toys as $t ) : ?>
						<div data-t965-toy data-s="<?php echo esc_attr( $t[1] ); ?>" data-d="<?php echo esc_attr( $t[2] ); ?>" data-r="<?php echo esc_attr( $t[3] ); ?>" style="<?php echo esc_attr( $t[4] ); ?>">
							<img src="<?php echo esc_url( toys965_asset_url( $t[0] ) ); ?>" alt="" decoding="async">
						</div>
					<?php endforeach; ?>
				</div>

			</div>
		</div>
	</section>

	<!-- ================= 3. CATEGORY RAIL ================= -->
	<?php $cats = toys965_home_categories( 14 ); ?>
	<?php if ( $cats ) : ?>
	<section class="t965-wrap t965-sec" id="cats">
		<div data-t965-reveal style="display:flex;align-items:end;justify-content:space-between;gap:14px;margin-bottom:16px">
			<div>
				<h2 style="font-family:var(--t965-font-d);font-weight:800;font-size:clamp(24px,3.2vw,36px);margin:0 0 4px">تسوق حسب القسم 🧸</h2>
				<p style="color:var(--t965-soft);margin:0;font-size:15px">كل ما يحتاجه طفلك في مكان واحد</p>
			</div>
			<a href="<?php echo esc_url( $shop ); ?>" style="font-weight:700;font-size:14.5px;white-space:nowrap">اظهار الكل</a>
		</div>
		<div class="t965-hrow">
			<?php foreach ( $cats as $c ) : ?>
				<a class="t965-hcat" data-t965-tilt href="<?php echo esc_url( $c['url'] ); ?>">
					<div class="t965-cat__box">
						<?php if ( $c['image'] ) : ?>
							<img src="<?php echo esc_url( $c['image'] ); ?>" alt="" loading="lazy" decoding="async">
						<?php else : ?>
							<span style="font-size:38px"><?php echo esc_html( $c['emoji'] ); ?></span>
						<?php endif; ?>
					</div>
					<div class="t965-cat__name"><?php echo esc_html( $c['name'] ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- ================= 4. SHOP BY AGE ================= -->
	<?php $bands = toys965_age_bands(); ?>
	<?php if ( $bands ) : ?>
	<section class="t965-wrap t965-sec" id="ages">
		<div class="t965-head" data-t965-reveal style="text-align:center;margin-bottom:24px">
			<h2 style="font-family:var(--t965-font-d);font-weight:800;font-size:clamp(24px,3.2vw,36px);margin:0 0 4px">تسوق حسب العمر 🎂</h2>
			<p style="color:var(--t965-soft);margin:0;font-size:15px">ألعاب مناسبة لكل مرحلة عمرية</p>
		</div>
		<div class="t965-g-age">
			<?php foreach ( $bands as $b ) : ?>
				<a class="t965-age t965-age--<?php echo esc_attr( $b['key'] ); ?>" data-t965-reveal data-t965-tilt href="<?php echo esc_url( $b['url'] ); ?>">
					<?php if ( $b['sprite'] ) : ?>
						<img src="<?php echo esc_url( $b['sprite'] ); ?>" alt="" loading="lazy" decoding="async">
					<?php endif; ?>
					<h3><?php echo esc_html( $b['label'] ); ?></h3>
					<p><?php echo esc_html( $b['desc'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- ================= 5. THE SHELVES ================= -->
	<?php $shelves = toys965_shelves( 8 ); $first = true; ?>
	<?php foreach ( $shelves as $shelf ) : ?>
		<section class="t965-wrap t965-sec" id="shelf-<?php echo esc_attr( $shelf['slug'] ); ?>">

			<a class="t965-shelf__banner" data-t965-parallax data-t965-reveal
			   href="<?php echo esc_url( $shelf['archive'] ); ?>">
				<?php echo toys965_banner_picture( $shelf['banner'], $shelf['title'], $first ); ?>
			</a>

			<div style="display:flex;align-items:end;justify-content:space-between;gap:14px;margin-bottom:14px">
				<div>
					<h2 style="font-family:var(--t965-font-d);font-weight:800;font-size:clamp(20px,2.6vw,30px);margin:0 0 4px"><?php echo esc_html( $shelf['title'] ); ?></h2>
					<p style="color:var(--t965-soft);margin:0;font-size:14.5px"><?php echo esc_html( $shelf['sub'] ); ?></p>
				</div>
				<a href="<?php echo esc_url( $shelf['archive'] ); ?>" style="font-weight:700;font-size:14.5px;white-space:nowrap">اظهار الكل</a>
			</div>

			<div class="t965-hrow">
				<?php foreach ( $shelf['products'] as $p ) : ?>
					<div class="t965-hcard"><?php toys965_product_card( $p ); ?></div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php $first = false; ?>
	<?php endforeach; ?>

	<!-- ================= 6. SALE BANNER ================= -->
	<?php $sale = toys965_asset_url( 'sale-30-percent' ); ?>
	<section class="t965-wrap t965-sec">
		<a class="t965-sale" data-t965-reveal href="<?php echo esc_url( toys965_sale_url() ); ?>">
			<?php if ( $sale ) : ?>
				<img src="<?php echo esc_url( $sale ); ?>" alt="خصم حتى 30%" loading="lazy" decoding="async">
			<?php else : ?>
				<div style="font-family:var(--t965-font-d);font-weight:800;font-size:clamp(30px,5vw,54px)">خصم حتى 30% 🎉</div>
			<?php endif; ?>
		</a>
	</section>

	<!-- ================= 7. BRANDS ================= -->
	<?php
	// The rail leads with brands that actually have stock, then fills out with
	// the rest of the wall so the row reads complete. The full list lives on the
	// الماركات page; here we cap it so the rail stays scannable on a phone.
	$brands = array_slice( toys965_all_brands(), 0, 14 );
	?>
	<?php if ( $brands ) : ?>
	<section class="t965-wrap t965-sec" id="brands">
		<div data-t965-reveal class="t965-sechead">
			<h2 style="font-family:var(--t965-font-d);font-weight:800;font-size:clamp(24px,3.2vw,36px);margin:0">تسوّق حسب الماركة</h2>
			<a class="t965-seclink" href="<?php echo esc_url( toys965_brands_url() ); ?>">كل الماركات</a>
		</div>
		<div class="t965-hrow">
			<?php foreach ( $brands as $b ) : ?>
				<a class="t965-hcat t965-brand<?php echo $b['count'] ? '' : ' t965-brandcard--empty'; ?>"
				   data-t965-tilt href="<?php echo esc_url( $b['url'] ); ?>">
					<?php if ( $b['logo'] ) : ?>
						<span class="t965-brandcard__logo">
							<img src="<?php echo esc_url( $b['logo'] ); ?>" alt="<?php echo esc_attr( $b['name'] ); ?>" loading="lazy" decoding="async">
						</span>
					<?php endif; ?>
					<span><?php echo esc_html( $b['name'] ); ?></span>
					<small style="color:var(--t965-soft);font-size:12px"><?php echo $b['count'] ? esc_html( $b['count'] ) . ' منتج' : 'قريبًا'; ?></small>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- ================= 8. TRUST ROW ================= -->
	<section class="t965-wrap t965-sec">
		<div class="t965-g-trust">
			<?php
			$trust = array(
				array( 'feature-01-delivery', 'توصيل خلال 24 ساعة', 'لجميع مناطق الكويت', '' ),
				array( 'feature-02-payment',  'دفع آمن',            'كي نت أو نقدًا عند الاستلام', '' ),
				array( 'feature-03-quality',  'منتجات أصلية 100%',  'مختارة بعناية',      '' ),
				array( 'feature-04-chat',     'اطلب عبر واتساب',    '+' . $wa,     'https://wa.me/' . $wa ),
			);
			foreach ( $trust as $t ) :
				$icon = toys965_asset_url( $t[0] );
				$tag  = $t[3] ? 'a' : 'div';
				?>
				<<?php echo $tag; ?> class="t965-trust" data-t965-reveal
					<?php echo $t[3] ? 'href="' . esc_url( $t[3] ) . '" target="_blank" rel="noopener"' : ''; ?>>
					<?php if ( $icon ) : ?>
						<img src="<?php echo esc_url( $icon ); ?>" alt="" loading="lazy" decoding="async">
					<?php endif; ?>
					<div>
						<b><?php echo esc_html( $t[1] ); ?></b>
						<span><?php echo esc_html( $t[2] ); ?></span>
					</div>
				</<?php echo $tag; ?>>
			<?php endforeach; ?>
		</div>
	</section>

</div>

<?php
get_footer();
