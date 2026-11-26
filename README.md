# Afaz Alif – AWRELIEF

WordPress plugin behind [awrelief.com](https://www.awrelief.com): the design system, custom Elementor widgets, basket drawer, popups and site tools. It replaces the old page-builder add-ons, side cart, popup, form and header plugins.

Requires WordPress 6.4+, PHP 7.4+, WooCommerce, Elementor (and Pro Elements for the header/footer theme builder). The theme is Hello Elementor.

## What is inside

| Area | Where |
|---|---|
| 23 Elementor widgets (category "AWRELIEF") | `includes/widgets/` |
| Shop, product, basket, checkout and account layouts | `includes/class-afz-woo.php`, `templates/woo/` |
| Live product search (WooCommerce AJAX `wc-ajax=afz_search`) | `includes/class-afz-woo.php`, `templates/search.php` |
| Basket drawer (WooCommerce AJAX `wc-ajax=afz_cart`) | `includes/class-afz-cart.php`, `templates/cart.php` |
| Scents taxonomy (notes, aura colours, ingredient image) | `includes/class-afz-scents.php` |
| Newsletter (MailerLite + local copy), contact and return request forms with saved messages | `includes/class-afz-forms.php` |
| Dashboard: AWRELIEF → Site settings, Messages, Subscribers | `includes/class-afz-settings.php` |
| WebP conversion, trimmed WordPress extras | `includes/class-afz-performance.php` |
| Login limits, XML-RPC off, security headers | `includes/class-afz-security.php` |
| Motion (GSAP, ScrollTrigger, Lenis, WebGL smoke) | `assets/js/`, `assets/vendor/` |

## Stylesheet

`assets/css/afazalif.css` is generated. Edit the approved HTML design's `main.css` or the files in `assets/css/src/`, then run:

```
python tools/build-css.py "<path to html-design>/assets/css/main.css"
```

Every component rule is scoped under `.afz` so Elementor's own styles cannot override the design.

## Setting up a copy of the site

```
php tools/seed.php  <path/to/wp-load.php> <path/to/html-design/assets> --home --theme
php tools/pages.php <path/to/wp-load.php>
```

`seed.php` imports the design media, creates the 13 scents and links the products, builds the homepage, kit, header and footer. `pages.php` builds the inner pages (Our Story, FAQ, Contact, Delivery, Returns, Terms, Privacy, Track my order, Care guide, Journal). Both are safe to run again; previous layouts are kept as revisions.

On a host without a command line, use **AWRELIEF → Go live** in the dashboard instead. It checks the site first (Elementor, Pro Elements, theme, currency, leftover local-copy safety plugin, YayMail and the old plugins the design replaces) and runs the same three tools with a button each. Upload the design pictures and films to `wp-content/uploads/afz-design-assets/` first (`img/` and `video/` from the HTML design's `assets` folder).

## Emails

`includes/class-afz-emails.php` gives every WooCommerce email the AWRELIEF look (gold logo on black, parchment background, the site's colours and type) through WooCommerce's own filters, so WooCommerce updates keep working. The WooCommerce → Settings → Emails values are left alone. Email customiser plugins such as YayMail replace these templates and must stay off.

## Search snippets

`includes/class-afz-seo.php` works on top of All in One SEO. All in One SEO builds page descriptions from the page text, which on Elementor pages is a jumble of headings, so pages, ranges and scents get a written description instead, and anything longer than Google shows is shortened. A description typed into All in One SEO for a page always wins. The old returns plugin's pages (Refund Request Form, View Order Messages) redirect to Returns and My account once that plugin is switched off.

## One-off content updates

`includes/class-afz-updates.php` holds changes to the store's content that ship with the plugin (scent pictures, the UK-only Delivery page, the site name, and moving WordPress's sample "Hello world!" post to drafts). Each runs once, the first time an administrator opens the dashboard after an update.

Built by [Afaz Alif](https://afazalif.com/).
