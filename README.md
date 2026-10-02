<div align="center">

<img src="docs/img/social-preview.png" alt="965toys: an Arabic-first WooCommerce child theme for Woodmart" width="100%">

# 965toys

**An Arabic-first WooCommerce child theme for Woodmart, built for a toy store in Kuwait.**<br>
Designed and built by [Tarek Okasha](https://github.com/tarekokashha).

[![CI](https://github.com/tarekokashha/965toys-storefront/actions/workflows/ci.yml/badge.svg)](https://github.com/tarekokashha/965toys-storefront/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/code-GPL--2.0--or--later-blue.svg)](LICENSE)
[![Docs: CC BY 4.0](https://img.shields.io/badge/docs-CC%20BY%204.0-lightgrey.svg)](NOTICE.md)
![WooCommerce](https://img.shields.io/badge/WooCommerce-child%20theme-7f54b3.svg)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)
![RTL first](https://img.shields.io/badge/RTL-first-ff6b6b.svg)

[العربية](README.ar.md) · [Documentation](docs/) · [Install](docs/install.md) · [Portfolio](https://tarek-portfolio-phi.vercel.app)

</div>

---

> **Status.** The redesign is built. [965toys.com](https://965toys.com) currently shows a coming-soon page while the store prepares to launch, so the screenshots below are of the **design prototype this theme implements**, not of a live deployment.

![The Wonder Box opening scene: toys fly out of the box among balloons, then settle into the hero](docs/img/wonderbox-sequence.png)

## The project

965toys is a toy store for Kuwait. I designed and built its redesign as a **WooCommerce child theme for Woodmart**: an Arabic-first, right-to-left storefront with a playful motion layer, a shelf-based homepage, delivery rules written for Kuwait, share cards that render properly in WhatsApp, and a set of performance and accessibility fixes that came out of auditing the live store first.

It is one of three stores in the 965 collection, alongside [965gym](https://github.com/tarekokashha/965gym-storefront) and [965play](https://github.com/tarekokashha/965play-storefront).

## At a glance

| | |
|---|---|
| **Role** | Design direction, theme engineering, store audit, tooling: [Tarek Okasha](https://github.com/tarekokashha) |
| **Client** | 965toys, Kuwait |
| **Stack** | WordPress, WooCommerce, the Woodmart parent theme, PHP 7.4+, vanilla JavaScript |
| **Language** | Arabic first, right to left |
| **Shape** | Thirteen independent modules. The design is a switch, off by default, with an administrator-only preview |
| **Status** | Built. The public domain shows a coming-soon page |

## Screens

| Shop by category and age | Mobile |
|---|---|
| ![Clay category icons and age cards](docs/img/proto-categories.png) | ![The mobile homepage](docs/img/proto-mobile-home.png) |

## What I built

### A launch you can reverse
Activating the theme changes nothing. The whole redesign sits behind one switch that defaults to **off**, with an administrator-only preview (`?toybox=1`), so launching is a single reversible action and the store is never half-redesigned. → [Architecture](docs/architecture.md)

### Delivery rules for Kuwait
Kuwait's remote areas are not WooCommerce states, and several sit inside the same governorate as ordinary areas, so a shipping zone cannot tell them apart. I match them from the address text, after Arabic normalisation, with the longest match winning. Express delivery disappears after 19:00 and is never offered to a remote area, and I fixed a WooCommerce caching problem that would have kept offering it after the cut-off. → [Delivery rules](docs/delivery.md)

### Fixes that came from measuring the store first
The live store's largest paint waited on a JavaScript download because the logo was lazy-loaded. Its viewport tag blocked pinch-zoom. All 216 product images had empty alt text. It emitted no Open Graph tags, so every shared link was a grey URL. A price-entry bug made 43 of 193 products unsellable. Each finding has a fix in code, and the price bug has an audit tool. → [The audit and its fixes](docs/audit-and-fixes.md)

### WhatsApp as a first-class channel
Share cards sized for WhatsApp's limits (dimensions present, under 300 KB, cache-busted when the image changes), and an "order via WhatsApp" button on every product page with the product name, SKU and link pre-filled. → [Decisions](docs/decisions.md)

### A motion layer that respects the visitor
A five-second "Wonder Box" opening scene, balloons, reveals, parallax, tilt and an add-to-cart celebration: vanilla JavaScript, animating only `transform` and `opacity`, fully gated on reduced motion. → [Motion](docs/motion.md)

### A catalogue-aware homepage
Twelve collection shelves resolved to real categories, a brand rail, age cards and clay category icons. A shelf, brand or category with no products is never rendered, and images are resolved by filename so nothing depends on attachment IDs.

## Quick start

1. Install and activate your licensed **Woodmart** theme. It is not included here.
2. Copy `965toys-child/` into `wp-content/themes/` and activate it.
3. Set your contact details in `wp-config.php`:

   ```php
   define( 'TOYS965_WHATSAPP',  '965XXXXXXXX' );
   define( 'TOYS965_EMAIL',     'hello@your-store.example' );
   define( 'TOYS965_INSTAGRAM', 'https://www.instagram.com/your-handle' );
   define( 'TOYS965_TIKTOK',    'https://www.tiktok.com/@your-handle' );
   ```

4. While logged in as an administrator, open any page with `?toybox=1` to preview the design.

The full guide, including the images to upload and how to turn the design on, is **[docs/install.md](docs/install.md)**.

## Tools

`tools/audit_prices.py` is a read-only price-integrity audit for **any** WooCommerce store. It pulls every product from the public Store API and flags prices that are almost certainly 1000 times too high, which is what a thousands separator does to a three-decimal currency.

```bash
python tools/audit_prices.py --store https://your-store.example --threshold 100
```

## Quality gates

```bash
node scripts/check-repo.mjs                                   # no leaked credentials, personal data, forbidden files or broken links
find 965toys-child -name '*.php' -print0 | xargs -0 -n1 php -l   # every PHP file parses
python -m py_compile tools/audit_prices.py                    # the tooling compiles
```

CI runs the PHP lint on 7.4, 8.0, 8.1, 8.2, 8.3 and 8.4, plus the repository check.

## Repository map

```text
965toys-child/            the WordPress child theme
  inc/                    thirteen modules (settings, shelves, shipping, performance, ...)
  page-templates/         homepage, categories and brands
  assets/                 stylesheets, the motion layer, self-hosted fonts
tools/audit_prices.py     read-only price audit for any WooCommerce store
docs/                     architecture, delivery, audit, motion, install, decisions
```

## What is not in this repository

| Not included | Why |
|---|---|
| Woodmart | A commercial parent theme with its own licence |
| The 56 design images (sprites, category icons, banners) | They belong to the brand. The theme resolves them by filename |
| The audit's reports | They contain store data |
| The store's phone number, email and social links | They live in `wp-config.php` |

## License and credit

- **Code** is [GPL-2.0-or-later](LICENSE).
- **Documentation** is [CC BY 4.0](LICENSES/CC-BY-4.0.txt). Reuse must credit **Tarek Okasha** and link to this repository.
- **Fonts**: Baloo Bhaijaan 2 and Rubik are included under the SIL Open Font License 1.1, with their licence texts.
- 965toys's name, logo and imagery are **not** licensed here. See [NOTICE](NOTICE.md).

Copyright (c) 2026 Tarek Okasha.

## About the author

I am **Tarek Okasha**, a robotics and automation engineer in Cairo who builds systems that run without supervision: six-axis robots, AI automations, and the custom software and brand presences that companies actually operate on. More of my work is in my [portfolio](https://tarek-portfolio-phi.vercel.app) and on [GitHub](https://github.com/tarekokashha).
