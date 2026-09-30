# 965toys storefront

**An Arabic-first toy storefront for Kuwait, built by Tarek Okasha.**

[Live site](https://965toys.com/) · [Portfolio](https://tarek-portfolio-phi.vercel.app/#brands)

> **Current public status:** The domain currently shows a coming-soon page. This repository documents the storefront I built and includes selected code from my local project. I have not verified that this code is currently deployed at the public domain.

## What I built

I designed and developed a playful shopping experience for 965toys, with a WordPress child theme that keeps the store's custom design and behavior separate from the commercial Woodmart parent theme.

## Implementation highlights

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

صممت وطورت واجهة متجر 965toys للكويت بتصميم عربي واتجاه من اليمين إلى اليسار. يضم المستودع توثيقًا للمشروع وأجزاء مختارة من شيفرة القالب الابن التي راجعتها محليًا. الموقع العام يعرض حاليًا صفحة «قريبًا»، ولم أتحقق من نشر هذه الشيفرة عليه حاليًا.

## Rights and attribution

The original child-theme code is shared under GPL-2.0-or-later. Brand marks, product images, store content, third-party themes, and fonts are outside that code license. See [LICENSE-SCOPE.md](LICENSE-SCOPE.md).
