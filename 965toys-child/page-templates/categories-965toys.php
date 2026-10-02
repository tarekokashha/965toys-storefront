<?php
/**
 * Template Name: 965toys — الفئات
 *
 * Grid of every product category that has stock, each with its clay 3D icon
 * (falling back to an emoji when a term has no thumbnail) linking to the real
 * product_cat archive.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

// See brands template: kill Woodmart's duplicate page-title banner.
toys965_suppress_page_title();

get_header();

$cats = toys965_all_categories();
?>

<div class="t965-page" id="main">

	<div class="t965-wrap">
		<div class="t965-pagehead">
			<h1>كل الفئات 🧩</h1>
			<p>كل ما يحتاجه طفلك في مكان واحد</p>
		</div>
	</div>

	<section class="t965-wrap t965-sec">
		<?php if ( $cats ) : ?>
			<div class="t965-catgrid">
				<?php foreach ( $cats as $c ) : ?>
					<a class="t965-hcat-page" data-t965-tilt data-t965-reveal
					   href="<?php echo esc_url( $c['url'] ); ?>"
					   style="display:block;text-decoration:none">
						<div class="t965-cat__box">
							<?php if ( $c['image'] ) : ?>
								<img src="<?php echo esc_url( $c['image'] ); ?>" alt="" loading="lazy" decoding="async">
							<?php else : ?>
								<span style="font-size:34px"><?php echo esc_html( $c['emoji'] ); ?></span>
							<?php endif; ?>
						</div>
						<div class="t965-cat__name"><?php echo esc_html( $c['name'] ); ?></div>
						<span class="t965-catcount"><?php echo esc_html( $c['count'] ); ?> منتج</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="t965-empty">
				<h2>لا توجد فئات متاحة حاليًا</h2>
				<a class="t965-btn t965-btn--coral" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">تصفح المتجر</a>
			</div>
		<?php endif; ?>
	</section>

</div>

<?php
get_footer();
