# Install and make it yours

## Requirements

| | |
|---|---|
| WordPress | 5.3 or newer (the delivery rules use `wp_date()`); a current release is recommended |
| PHP | 7.4 or newer |
| WooCommerce | Active, with the classic shortcode cart and checkout |
| **Woodmart** | The parent theme. It is a **commercial theme and is not included**. Install and activate your licensed copy first |
| Language | Built for Arabic (right to left). It works for a store in any language, but the copy is Arabic |

## Install

1. Install and activate **Woodmart** (the parent).
2. Copy the `965toys-child` folder to `wp-content/themes/`, or zip it and use **Appearance → Themes → Add New → Upload Theme**.
3. **Activate** the child theme. Nothing changes on the storefront yet: the design layer is off by default.

## Configure the store identity

Set these in `wp-config.php`, above the line that says *That's all, stop editing*:

```php
define( 'TOYS965_WHATSAPP',  '965XXXXXXXX' );   // digits only, with country code
define( 'TOYS965_EMAIL',     'hello@your-store.example' );
define( 'TOYS965_INSTAGRAM', 'https://www.instagram.com/your-handle' );
define( 'TOYS965_TIKTOK',    'https://www.tiktok.com/@your-handle' );
```

They feed the footer, the homepage, and the "order via WhatsApp" button on every product page.

## Set up the store

**Currency and prices.** For a three-decimal currency such as the Kuwaiti dinar, configure **three decimals and no thousands separator** (WooCommerce → Settings → General). The reason is in [audit-and-fixes.md](audit-and-fixes.md): a thousands separator turned "7,000" into 7000.

**Delivery.** In WooCommerce → Settings → Shipping, create your delivery zone with the flat rates you want. Two details matter for the rules in [delivery.md](delivery.md):

- The express rate is recognised by its **label**: it must contain the word `المستعجل` or the rocket emoji `🚀`. Rename it freely as long as one of those stays.
- Edit remote-area names and surcharges in `toys965_remote_areas()` in `inc/shipping.php`.

**Product axes.** The design filters by gender, age and brand. Create the attributes and their terms once, with WP-CLI (it is idempotent, so running it again is safe):

```bash
wp eval 'echo implode( PHP_EOL, toys965_install_attributes() ) . PHP_EOL;'
```

Then tag products with `pa_gender`, `pa_age` and `pa_brand` in the normal product editor. A brand or shelf with no products is never shown, so the cards appear as soon as stock is tagged.

## Add the design images

The theme resolves its images **by filename** from the Media Library, so upload them with these names (it does not matter in what order):

| Filename pattern | Used for |
|---|---|
| `box-closed`, `box-open`, `teddy`, `rocket`, `house`, `car`, `scooter`, `blocks`, `palette` | The Wonder Box sprites |
| `cat-01-plush` to `cat-14` | The clay category icons |
| `banner-01-...` to `banner-12-...` | The shelf banners. Desktop is 1200 by 420; the mobile portrait version has the same filename, and the resolver tells them apart by aspect ratio |
| `feature-01` to `feature-04`, `hero-main`, `sale-30-percent`, `state-*` | Feature art, the hero and state illustrations |

The repository does not include the images: they belong to the brand.

## Choose the pages

Under **Pages → Page Attributes → Template**, assign:

| Template | Page |
|---|---|
| **965toys Homepage** | The front page |
| **965toys — الفئات** | The all-categories page |
| **965toys — الماركات** | The brands page |

## Turn the design on

1. **Preview it first.** While logged in as an administrator, add `?toybox=1` to any URL. Visitors never see the preview. Use `?toybox=0` to see the old design again.
2. Set the **share card** (1200 by 630, under 300 KB) and the homepage share text in **Appearance → Customize → 965toys**.
3. When you are happy, tick **Enable the TOY BOX design layer** in the same panel. To fix it per environment instead, add `define( 'TOYS965_DESIGN', true );` to `wp-config.php`.

## Verify

- [ ] `?toybox=1` shows the new design for an administrator and not for a visitor
- [ ] The Wonder Box plays on the homepage, and with "reduce motion" switched on the scene is a still
- [ ] A shelf with no products is not rendered
- [ ] The cart shows the delivery table; the product page shows the delivery hint
- [ ] An address in a remote area replaces the standard rate, and express is not offered
- [ ] After 19:00 local time express disappears, even for a cart loaded earlier
- [ ] Pasting a product link into WhatsApp renders a card with an image
- [ ] The viewport allows pinch-zoom, and the first Tab press reaches the skip link

## Rolling back

Untick the design layer, or remove `TOYS965_DESIGN`, and the storefront is the previous design again. The parent theme's footer is hidden, never deleted, so the footer returns with the rest.
