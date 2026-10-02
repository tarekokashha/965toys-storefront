<?php
/**
 * Template Name: 965toys — الماركات
 *
 * The full brand wall. Per the owner's decision this shows EVERY brand from
 * BRAND_MANIFEST.csv, including those with no stock yet — brands with zero
 * products are dimmed rather than hidden, so the wall reads complete while
 * still being honest about what is in stock.
 *
 * Logos are resolved by filename (brand-{slug}) from the Media Library and are
 * never recoloured, cropped or mirrored — only contained.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

// Woodmart prints its own blue page-title banner, which would put a second <h1>
// above ours and break the cream palette. Suppress it for this template only.
toys965_suppress_page_title();

get_header();

// toys965_all_brands() already sorts stocked-first, then alphabetical, so one
// wall is enough. An earlier two-section split ("المتوفرة" then "الكل") repeated
// every stocked brand twice, which on a phone just reads as a bug.
$brands  = toys965_all_brands();
$stocked = count( array_filter( $brands, function ( $b ) { return $b['count'] > 0; } ) );
?>

<div class="t965-page" id="main">

	<div class="t965-wrap">
		<div class="t965-pagehead">
			<h1>تسوّق حسب الماركة ⭐</h1>
			<p>ماركات عالمية يحبها الأطفال</p>
		</div>
	</div>

	<section class="t965-wrap t965-sec">
		<?php if ( $stocked ) : ?>
			<p class="t965-brandnote"><?php echo esc_html( $stocked ); ?> ماركة متوفرة الآن، والباقي في الطريق</p>
		<?php endif; ?>
		<div class="t965-brandgrid">
			<?php foreach ( $brands as $b ) : ?>
				<a class="t965-brandcard<?php echo $b['count'] ? '' : ' t965-brandcard--empty'; ?>"
				   data-t965-tilt data-t965-reveal
				   href="<?php echo esc_url( $b['url'] ); ?>">
					<span class="t965-brandcard__logo">
						<?php if ( $b['logo'] ) : ?>
							<img src="<?php echo esc_url( $b['logo'] ); ?>" alt="<?php echo esc_attr( $b['name'] ); ?>" loading="lazy" decoding="async">
						<?php endif; ?>
					</span>
					<b><?php echo esc_html( $b['name'] ); ?></b>
					<small><?php echo $b['count'] ? esc_html( $b['count'] ) . ' منتج' : 'قريبًا'; ?></small>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

</div>

<?php
get_footer();
