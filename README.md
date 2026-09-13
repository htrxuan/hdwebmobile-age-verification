# HDWebmobile Age Verification Gate

Age verification for restricted product categories, held server-side and re-checked at checkout, never trusted from a cookie.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-age-verification/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

Requires a shopper to confirm their age before they can buy anything in the product categories you choose. Confirmation is remembered for the rest of their visit.

## Why this plugin exists

Several competing "Age Verification for WooCommerce" plugins stored the verified/not-verified flag in a plain, unsigned cookie the visitor's own browser held, with no server-side re-check at add-to-cart or checkout (CWE-602, Client-Side Enforcement of Server-Side Security). A shopper could simply set that cookie in their browser's devtools and buy age-restricted products having confirmed nothing at all.

This plugin never has a client-controlled flag to forge in the first place:

* Verification state lives only in WooCommerce's own server-side session store -- the visitor's cookie carries only an opaque session id, never the verification value itself.
* The confirmation is re-checked again at add-to-cart AND at `woocommerce_check_cart_items`, the same hook that fires on the cart page, at classic checkout, and inside the block-based Store API's own checkout processing -- so there is no path to a completed order that skips the check.
* The one method that ever marks a session verified takes no parameters at all, and is reachable only through a nonce-checked confirmation form submission.

## Features

* Restrict any number of product categories
* A simple, server-verified "Yes, I meet the minimum age" confirmation
* Works for guests and logged-in customers alike
* Enforced identically on classic and block-based checkout

## Limitations

* This confirms an age claim, not a verified identity -- it is not a substitute for ID verification where that is legally required
* Restriction is by product category, not per individual product

## Installation

1. Upload to `/wp-content/plugins/hdwebmobile-age-verification`, or install through the WordPress plugins screen.
2. Activate. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Age Verification** to choose your restricted categories.

## Development

```
includes/
  class-hdage-activator.php   activation check
  class-hdage-core.php        bootstraps admin + frontend
  class-hdage-repository.php  settings, restriction lookup, and the ONLY read/write of verification state
  class-hdage-admin.php       hub tab and settings form
  class-hdage-frontend.php    confirmation prompt, guest-friendly admin-post handler, enforcement hooks
  class-hdage-hub.php         shared HDWebmobile admin hub (see the suite's other plugins)
```

## License

GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html
