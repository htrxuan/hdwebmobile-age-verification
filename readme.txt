=== HDWebmobile Age Verification ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, age verification, age gate, restricted products, compliance
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Age verification for restricted product categories, held server-side and re-checked at checkout, never trusted from a cookie.

== Description ==

HDWebmobile Age Verification requires a shopper to confirm their age before they can buy anything in the product categories you choose. Confirmation is remembered for the rest of their visit.

= Security by design =
A common weakness in age-gate tools is storing the verified/not-verified flag in a plain, unsigned cookie held by the visitor's own browser, with no server-side re-check at add-to-cart or checkout (CWE-602, Client-Side Enforcement of Server-Side Security). A shopper could simply set that cookie in their browser's devtools and buy age-restricted products having confirmed nothing at all.

This plugin never has a client-controlled flag to forge in the first place:

* Verification state lives only in WooCommerce's own server-side session store -- the visitor's cookie carries only an opaque session id, never the verification value itself.
* The confirmation is re-checked again at add-to-cart AND at `woocommerce_check_cart_items`, the same hook that fires on the cart page, at classic checkout, and inside the block-based Store API's own checkout processing -- so there is no path to a completed order that skips the check.
* The one method that ever marks a session verified takes no parameters at all, and is reachable only through a nonce-checked confirmation form submission.

= Key Features =
* Restrict any number of product categories
* A simple, server-verified "Yes, I meet the minimum age" confirmation
* Works for guests and logged-in customers alike
* Enforced identically on classic and block-based checkout

= Limitations (please read before installing) =
* This confirms an age claim, not a verified identity -- it is not a substitute for ID verification where that is legally required
* Restriction is by product category, not per individual product

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-age-verification` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Age Verification** to choose your restricted categories.

== How to Use ==

= 1. Choose categories =
Select which product categories require age confirmation, and set the minimum age shown to shoppers.

= 2. Shoppers confirm once per visit =
Visiting a restricted product shows a confirmation prompt in place of the Add to Cart button until they confirm.

== Screenshots ==

1. The confirmation prompt on a restricted product's page.
2. The category selection in WooCommerce > HDWebmobile.

== Changelog ==

= 1.0.0 =
* Initial release: category-based age verification, stored server-side and re-checked at every checkout path.
