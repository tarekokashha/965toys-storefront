# Design decisions

The non-obvious choices in the 965toys child theme, why each was made, and what it costs. Decisions by [Tarek Okasha](https://github.com/tarekokashha).

## 1. A child theme, and nothing edited in the parent

**Decision.** Every customisation lives in the child theme.
**Why.** A parent update can then never destroy the work, and the redesign can be removed by switching themes.

## 2. The design layer is off by default, with an administrator-only preview

**Decision.** Activating the theme changes nothing. The design sits behind one switch, previewable with `?toybox=1` by administrators only.
**Why.** A storefront redesign should be a reversible launch, not a surprise. Every module that depends on the new design checks the same function, so the store is entirely the old design or entirely the new one, never a half-state.

## 3. Modules that fail soft

**Decision.** `functions.php` loads thirteen modules through a loader that skips a missing file.
**Why.** A storefront should degrade, not white-screen. Each module does one job and can be switched off without unpicking the others.

## 4. Resolve images by filename, not by ID

**Decision.** The media resolver finds each design image by its base filename at runtime and caches the result.
**Why.** Attachment IDs differ between environments and break the moment anything is re-uploaded. The resolver also tells desktop and mobile banners apart by aspect ratio, not by the `-1` suffix WordPress adds, because the suffix depends on upload order.

## 5. The bridge stylesheet loads last

**Decision.** `woodmart-bridge.css`, which maps the design's tokens onto Woodmart's `--wd-*` variables, is enqueued after everything else.
**Why.** Woodmart prints its variable block inline in `<head>` after the enqueued styles. Only a stylesheet that arrives last makes the whole store adopt the design rather than only the homepage template.

## 6. Render a second footer and hide the first

**Decision.** The design footer is rendered on `wp_footer`, and Woodmart's is hidden with `display:none`.
**Why.** The parent's footer is an Elementor template that cannot be unhooked. Rendering two footers produced two landmarks, and buffering the output to swap one blanked the whole page, because a failed output-buffer callback emits nothing. `display:none` removes the hidden footer from the accessibility tree, so screen readers still see exactly one `contentinfo`. The history is in the file header so nobody retries the buffering approach.

## 7. Delivery rules match the address text

**Decision.** Remote areas are recognised from the shipping address, after Arabic normalisation, with the longest match winning.
**Why.** Kuwait's remote areas are not WooCommerce states, and several sit inside the same governorate as ordinary areas, so a shipping zone cannot separate them. See [delivery.md](delivery.md).

## 8. Put the clock in the shipping cache key

**Decision.** The package carries the state of the express window.
**Why.** WooCommerce caches rates against a hash of the package, and the hash knows nothing about the time. Without this a cart loaded at 18:55 would still be offered express at 19:30.

## 9. Never render an empty shelf, brand or category

**Decision.** Shelves and brands without products are not shown, and empty categories are hidden. Thin shelves render honestly with what exists.
**Why.** An empty page is a bounce and thin content, and padding a shelf with unrelated stock misleads the shopper. The terms still exist, so a card appears the moment stock is tagged.

## 10. Do not emit product structured data

**Decision.** The Open Graph module emits share tags, and no Product schema.
**Why.** WooCommerce already outputs Product, Offer and BreadcrumbList JSON-LD, and a second copy creates duplicate-schema errors in Search Console.

## 11. Treat WhatsApp as a conversion path, not a support link

**Decision.** Every product page has an "order via WhatsApp" button with the product name, SKU and URL pre-filled, and it is instrumented separately in analytics. Share cards are sized for WhatsApp.
**Why.** A meaningful share of Kuwaiti shoppers will never finish a web checkout but will happily order in chat, and product links travel by messaging far more than by search.

## 12. Animate only `transform` and `opacity`

**Decision.** The motion layer never animates layout properties, and the Wonder Box is driven by time, not scroll.
**Why.** Those two properties animate on the compositor, which keeps the motion smooth on a mid-range phone. Time-driven means the page is never scroll-jacked.

## 13. Preload only what the design is serving

**Decision.** Font preloads only fire when the design layer is on, and only for the two Arabic subsets.
**Why.** Preloading a font no stylesheet references is pure waste and triggers a console warning on every page. Latin subsets load on demand through `unicode-range`.

## 14. Leave the payment step alone

**Decision.** No module touches the cart object, the payment gateway or the checkout submission path.
**Why.** Breaking checkout to improve presentation is never a good trade. The theme changes how the store looks and what delivery it offers, and not how it takes money. (It does adjust shipping rates, and it validates a price when a product is saved in wp-admin. Neither is part of the payment step.)
