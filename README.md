# 965toys storefront rebuild

**An Arabic-first toy shopping experience for Kuwait.**

[Live site](https://965toys.com/) · [Portfolio](https://tarek-portfolio-phi.vercel.app/#brands)

> **Project status:** The public site currently shows a coming-soon page. The code in this repository is local rebuild work and is not presented as the code running on the public site.

## The brief

965toys needed a playful storefront that still felt easy to shop. The rebuild uses a WordPress child theme so the store's own design and behavior can be maintained separately from the commercial Woodmart parent theme.

## What the rebuild covers

- Arabic RTL layout and typography for the storefront.
- Dedicated templates for the home page, categories, and brands.
- WooCommerce presentation and navigation work.
- Accessibility, performance, and Open Graph components in the child theme.
- A clear boundary between custom code and the commercial parent theme.

These points describe the local implementation. They are not a claim that every component is deployed on the current public site.

## Repository contents

`source/woodmart-child/` contains selected, reviewed text source from the local child theme: styling, front-end behavior, and setup components. The full theme includes operational and catalog details that are not published. This repository also excludes the Woodmart parent theme, customer data, store configuration, generated archives, and font binaries. The source is shared for portfolio review. It is not a complete installable store.

## Technical notes

The child theme declares `woodmart` as its parent. It depends on a licensed Woodmart installation, WordPress, WooCommerce, and the store's own content and media. No production database, orders, customers, or credentials are included.

## العربية

إعادة بناء واجهة متجر 965toys للكويت بتصميم عربي واتجاه من اليمين إلى اليسار. يضم المستودع دراسة حالة وشيفرة القالب الابن التي أمكن مراجعتها محليًا. الموقع العام يعرض حاليًا صفحة «قريبًا»، لذلك لا ننسب إليه تنفيذًا لم يتم التحقق من نشره.

## Rights and attribution

The original child-theme code is shared under GPL-2.0-or-later. Brand marks, product images, store content, third-party themes, and fonts are outside that code license. See [LICENSE-SCOPE.md](LICENSE-SCOPE.md).
