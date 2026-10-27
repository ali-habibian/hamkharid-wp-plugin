=== Smart Category Complement – Hamkharid ===
Contributors: alihabibian
Tags: woocommerce, recommendations, complementary-products, cross-sell
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Create category-based product complement rules and place recommendations with a shortcode, dynamic block, or optional WooCommerce product-page placement.

== Description ==

Hamkharid lets a store manager connect source product categories to one complementary category. The matching rule selects published, catalog-visible, purchasable simple or variable products and renders them with WooCommerce's native product loop.

Placement options:

* Add the `[hamkharid_complements]` shortcode to a page, existing widget area, or a page builder that supports shortcodes.
* Add a product ID when the embed is outside a single-product context: `[hamkharid_complements product_id="123"]`.
* Insert the “Hamkharid complements” dynamic block and select a product in the editor.
* Choose an optional automatic location in WooCommerce → Complement Display settings.

The first installation uses manual placement. Existing installations keep the previous automatic placement after the plugin is upgraded. This plugin does not provide an Elementor-specific widget and does not claim compatibility with every theme or builder without testing.

== Installation ==

1. Upload the `hamkharid` folder to `/wp-content/plugins/` or install the pilot ZIP from the WordPress Plugins screen.
2. Activate WooCommerce, then activate Smart Category Complement – Hamkharid.
3. Create product categories and rules under WooCommerce → Complement Rules.
4. Use the shortcode or dynamic block, or select an automatic placement under WooCommerce → Complement Display.

== Frequently Asked Questions ==

= Why do no recommendations appear? =

Check that the current product matches an enabled rule, the target category exists, and products in that category are published, visible, purchasable, and in stock (or permitted to be backordered).

= Does enabling a rule automatically insert it into a page? =

New installations start in manual mode. Place the shortcode or block, or explicitly enable an automatic location in the display settings.

= Does Hamkharid change prices or order data? =

No. WooCommerce supplies product prices and handles cart operations. Hamkharid does not modify orders.

== Privacy ==

Hamkharid does not send store or customer data to an external service. It stores rule configuration in WordPress posts and post metadata. By default, uninstalling the plugin retains rules and settings; an administrator can opt in to deleting Hamkharid rules and settings before uninstalling. Products, categories, and orders are never deleted by that option.

== Changelog ==

= 0.1.0 =
* Initial limited-pilot package.
* Category rules, WooCommerce product recommendations, shortcode, dynamic block, and optional automatic placement.
