# Contributing

Thanks for taking an interest. This repository documents real, shipped work by Tarek Okasha, so contributions are welcome where they make it more accurate, more accessible or easier to use.

## Good contributions

- Accessibility fixes, especially for Arabic (right-to-left) storefronts and screen readers.
- Bugs in the child theme or the audit tool, with the steps to reproduce them.
- Delivery-rule improvements that stay configurable from wp-admin.
- Corrections and clarifications to the documentation.

## Not accepted

- Brand assets (logos, fonts, photography) or any client data.
- Changes that add third-party network requests, tracking or analytics.
- Reformatting-only pull requests.

## Before you open a pull request

1. Open an issue first for anything larger than a small fix, so effort is not wasted.
2. Run the checks and make sure they pass:

- `node scripts/check-repo.mjs`
- `find 965toys-child -name '*.php' -print0 | xargs -0 -n1 php -l`
- `python -m py_compile tools/audit_prices.py`

3. Keep the change focused, and describe what you changed and why.

## Licensing of contributions

By contributing you agree that your contribution is licensed under the same terms as the material it changes (see [NOTICE.md](NOTICE.md)): GPL-2.0-or-later for code, CC BY 4.0 for documentation.

## Conduct

Everyone taking part is expected to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
