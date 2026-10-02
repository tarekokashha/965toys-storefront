# The audit, and the fixes it drove

The redesign did not start from taste. It started from measuring the live store, and the theme's performance, accessibility, sharing and catalogue modules each exist because of a specific finding. This page records the findings, the fix and where it lives. Audit and engineering by [Tarek Okasha](https://github.com/tarekokashha).

All measurements are from **13 August 2026**, against the store as it was then, on its previous theme setup. The target device was a mid-range Android phone (Galaxy A15 or Redmi Note 13) on Kuwaiti 4G, where bandwidth is good and CPU is not, so the goal was **fewer bytes of JavaScript**, not merely fewer bytes.

## Summary

| Area | Finding | Fix | Module |
|---|---|---|---|
| Performance | The logo and 62 other images shipped a placeholder as `src` with the real URL parked in `data-src`, so the largest paint waited on a JavaScript download | Eager, high-priority loading for the logo and the first product images, plus lazy-load exclusions | `performance` |
| Performance | No preload hints, one `fetchpriority="high"` on the page | Preload the two Arabic font subsets and the product image | `performance` |
| Performance | An unused slider library loaded on every page; WooCommerce block styles loaded off the commerce path; jQuery blocked the parser | Dequeue the slider unless a page uses it; scope block styles; defer the jQuery chain | `performance` |
| Accessibility | The viewport tag blocked pinch-zoom (`user-scalable=no`, `maximum-scale=1.0`) | Rewrite the rendered viewport tag | `accessibility` |
| Accessibility | All 216 product images had empty alt text | A safety-net alt from the product title, and a backfill of real alt text | `accessibility` |
| Accessibility | No skip link, 26 items of navigation to tab through | A skip link as the first focusable element | `accessibility` |
| Accessibility | 27% of product titles mix Arabic and Latin, so an Arabic screen reader mispronounces the Latin | Wrap Latin runs in `lang="en" dir="ltr"` | `accessibility` |
| Sharing | The store emitted **zero** Open Graph tags, so every shared link was a bare grey URL | Open Graph and Twitter cards sized for WhatsApp | `seo-open-graph` |
| Commerce | A price-entry bug made 43 of 193 products effectively unsellable | Configuration fix, a guard that refuses implausible prices, and an audit tool | `woocommerce`, `tools/` |
| Catalogue | 10 of 26 main-menu items led to dead ends | Empty categories and shelves are never rendered | `woocommerce`, `shelves` |

## Performance

**Baseline:** time to first byte 0.86 to 1.67 s (the CDN reported every response as dynamic), HTML 340 KB uncompressed, about 352 KB of gzipped JavaScript plus 30 KB of jQuery, about 169 KB of gzipped CSS, and about 603 KB of gzipped text assets before the first view.

**The headline problem.** 63 images carried a base64 grey SVG as their `src`, with the real address in `data-src`, because the host's JavaScript lazy-load was applied to everything, **including the logo**. The largest contentful paint therefore could not happen until a script had downloaded, parsed and executed. Fixing it was the largest LCP gain available and cost no redesign.

- `toys965_prioritise_hero_images()` marks the logo and the first N product images as eager and high priority. WordPress already skips its own lazy-load for the first image, but the host's JavaScript lazy-load runs independently and ignores that, so the `data-no-lazy` attribute and the host's exclusion filter are what actually stop it.
- `toys965_preloads()` preloads **only the two Arabic font subsets** (the store is Arabic-first, so they are certain to be needed; the Latin subsets are fetched on demand through `unicode-range`) and, on a product page, the product image with `fetchpriority="high"`. Preloading every subset would waste about 70 KB on a typical view.
- The **font** preloads only fire when the design layer is on. Preloading a font that no stylesheet references is pure waste, and it triggers a console warning on every page.
- `toys965_drop_unused_slider()` removes the slider library's scripts and styles everywhere except pages that genuinely contain a slider. Deactivating the plugin is the better end state; the filter realises the saving immediately and safely.
- `toys965_scope_woo_assets()` drops the block-based cart and checkout styles off the commerce path. It **never** touches anything on the commerce path itself.
- `toys965_defer_jquery()` cannot remove jQuery (Woodmart and WooCommerce both depend on it) but stops it blocking the parser. It defers the whole chain or nothing, because deferring only the core while its dependants stay blocking would reorder execution and break them.
- The `<head>` loses the generator tag, the RSD and WLW links, shortlinks, adjacent-post links and emoji scripts.

## Accessibility

- **Viewport.** The tag contained `maximum-scale=1.0, user-scalable=no`, which fails WCAG 2.2 success criterion 1.4.4 (Resize Text). It also stops a parent pinch-zooming to read an age warning or a small product photo, which on a toy store is a safety matter as well as a compliance one. Woodmart prints the tag itself, so the fix intercepts the rendered head instead of adding a competing tag.
- **Alt text.** Every product image had an empty alt, a failure of criterion 1.1.1, and it also forfeits image search traffic. The fallback is a safety net, not a substitute: a migration backfills the real alt text. It deliberately does not prefix "Image of", because screen readers already announce an image, and it leaves decorative images empty on purpose.
- **Skip link.** There was none, so a keyboard user had to tab through the whole navigation on every page load.
- **Mixed scripts.** A title like "متجر البيتزا الصغير — MINI PIZZA SHOP" would have its Latin run read by an Arabic screen reader using Arabic phonetics. Latin runs are wrapped in `<span lang="en" dir="ltr">`.

One bug found while writing this documentation, and fixed in 1.0.0: the alt-text fallback copied the product title through `get_the_title()`, which runs the very filter that adds those `<span>` wrappers, so for a mixed-script title the **alt attribute contained markup**. It now strips tags first. See the [changelog](../CHANGELOG.md).

## Sharing: the highest-value file in the theme

The store emitted no Open Graph tags on any page, including product pages. Product links travel by WhatsApp and Instagram messages far more than by search, and every one of those shares rendered as a bare grey URL with no image, no title and no price.

`inc/seo-open-graph.php` fixes it, and its header records the details that "cost real money if ignored":

- WhatsApp needs `og:image:width` and `og:image:height`, or it frequently skips the preview entirely.
- WhatsApp is unreliable above roughly 600 KB, so the share card targets **under 300 KB**.
- WhatsApp caches previews hard, so when an image changes its URL must change too. The tag appends the attachment's modified time as `?v=`.
- Product structured data is deliberately **not** emitted: WooCommerce already outputs Product, Offer and BreadcrumbList JSON-LD, and a second copy creates duplicate-schema errors in Search Console.
- The site title was just "965toy", which tells a WhatsApp recipient nothing, so the share title pairs it with the tagline.

The share card and the homepage share text are set in the Customizer.

## Commerce: the price-entry bug

The store ran with two decimals and a comma as the thousands separator. Kuwait's currency has **three** decimals (one dinar is 1000 fils). WooCommerce's `wc_format_decimal()` strips every character outside `[0-9.-]`, so a price typed as "7,000", meaning 7 KWD in the Kuwaiti convention, was stored as **7000**. 43 of 193 products were affected and were effectively unsellable.

Three layers fix it, so the same mistake cannot return silently:

1. **Configuration:** three decimals and no thousands separator.
2. **A guard** (`toys965_guard_price_entry()`): it refuses to save an implausible price and logs it, with a deliberately generous threshold because the store legitimately sells wooden outdoor playsets in the hundreds of dinars.
3. **An audit** (`tools/audit_prices.py`): a read-only script that pulls every product from the public WooCommerce Store API and flags prices that are almost certainly 1000 times too high. It treats the premium playset band separately, so correct high prices are never "fixed" by dividing them by a thousand.

After the fix, the audit scanned all 193 products and found **no** price matching the 1000x signature. The ten products above 100 KWD were all premium wooden playsets with plausible prices, and were left untouched.

The tool also records a lesson in its source: the store sat behind a CDN, and the Store API responses were cached. Without a cache-buster it silently reported a fix as not applied. The nonce and the no-cache header are load-bearing.

```bash
python tools/audit_prices.py --store https://your-store.example --threshold 100
```

## Catalogue hygiene

The design was drawn against a richer catalogue than the store had. Measured against the live 193 products, seven of twelve shelves were healthy, four were thin (one to three products) and one was empty. Of the brands in the design's manifest, only a handful matched any product. Two rules follow, and both are enforced in code:

- A shelf or brand with **no products is never rendered**, and the thin ones render honestly with whatever exists instead of being padded with unrelated stock.
- Empty categories are hidden from the storefront, because an empty category page is a bounce and thin content. Ten of the 26 main-menu items led to a dead end, and four were not live categories at all.

The brand terms are still created, so the moment stock is tagged, the card appears with no code change.
