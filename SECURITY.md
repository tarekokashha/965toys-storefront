# Security policy

## Reporting a vulnerability

Please **do not open a public issue** for a security problem.

Report it privately through GitHub: open the **Security** tab of this repository and choose **Report a vulnerability** (<https://github.com/tarekokashha/965toys-storefront/security/advisories/new>). That creates a private conversation with the maintainer, Tarek Okasha.

Include what you found, how to reproduce it, and the impact you expect. A short proof of concept is plenty.

## What to expect

- An acknowledgement within **7 days**.
- A fix or a clear explanation within **30 days** for confirmed issues, sooner for serious ones.
- Credit in the release notes if you would like it.

## Supported versions

Only the latest release receives fixes.

## Scope

The child theme in `965toys-child/` (output escaping, request handling such as the administrator-only `?toybox=` preview, and the WooCommerce hooks) and the audit tool in `tools/`. The audit tool is read-only and talks only to a store's public Store API.

Out of scope: vulnerabilities in WordPress core, WooCommerce, third-party themes and plugins (report those to their own maintainers), and findings that need an already-compromised administrator account.
