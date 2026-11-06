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

Built by [Afaz Alif](https://afazalif.com/).
