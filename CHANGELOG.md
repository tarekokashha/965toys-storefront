# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0] - 2026-10-02

First public release of the repository. The child theme inside is version **4.3.0**.

### Added

- The complete child theme source: thirteen independent modules, the homepage, categories and brands templates, the motion layer and the stylesheets.
- The two typefaces the design uses (Baloo Bhaijaan 2 and Rubik) as self-hosted WOFF2, each with its SIL Open Font License text.
- `tools/audit_prices.py`, a read-only price-integrity audit for any WooCommerce store, now taking the store URL as `--store`.
- Documentation: architecture, delivery rules, the audit and its fixes, the motion layer, installation and design decisions.
- `scripts/check-repo.mjs`, a zero-dependency gate that fails on leaked credentials, personal contact data, forbidden files and broken Markdown links, and continuous integration on PHP 7.4 to 8.4.

### Changed

- The WhatsApp number, email address and social links moved from hard-coded values to constants (`TOYS965_WHATSAPP`, `TOYS965_EMAIL`, `TOYS965_INSTAGRAM`, `TOYS965_TIKTOK`) that are set in `wp-config.php`.

### Fixed

- The alt-text fallback copied the product title through `get_the_title()`, which runs the filter that wraps Latin runs in `<span lang="en">`. For a title that mixes Arabic and Latin, the alt attribute therefore contained markup. It now strips tags first.

### Not distributed

- Woodmart, which is a commercial parent theme.
- The 56 design images (sprites, category icons, banners), which belong to the brand.
- The audit reports, which contain store data.
