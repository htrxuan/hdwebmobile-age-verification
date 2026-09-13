<?php

namespace htrxuan\hdage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings, category-restriction lookup, and the ONLY place verification state is ever read
 * or written.
 *
 * Several competing "Age Verification for WooCommerce" plugins stored the verified/
 * not-verified flag in a plain, unsigned cookie the visitor's own browser held (e.g.
 * `age_verified=1`), with no server-side re-check at add-to-cart or checkout (CWE-602,
 * Client-Side Enforcement of Server-Side Security). A shopper could set that cookie directly
 * in their browser's devtools -- or an automated script could just send it with every request
 * -- and buy age-restricted products having confirmed nothing at all.
 *
 * This plugin never has a client-controlled flag to forge in the first place: verification
 * state lives only in `WC()->session`, WooCommerce's own server-side session store (a
 * database row keyed by an opaque session id; the cookie on the visitor's browser carries only
 * that id plus a keyed hash, never the verification boolean itself). is_verified() always
 * reads that server-side value, and mark_verified() is reachable only through
 * HDAGE_Frontend's admin-post handler after a real, nonce-checked confirmation form
 * submission -- there is no code path that sets it from a bare request parameter.
 */
class HDAGE_Repository
{
    const SESSION_KEY = 'hdage_verified';

    public static function get_settings()
    {
        $defaults = array(
            'enabled'      => 0,
            'min_age'      => 18,
            'category_ids' => array(),
            'prompt_text'  => __('This product is age-restricted. Please confirm you meet the minimum age to continue.', 'hdwebmobile-age-verification'),
        );
        $settings = get_option('hdage_settings', array());
        return wp_parse_args(is_array($settings) ? $settings : array(), $defaults);
    }

    public static function save_settings($input)
    {
        $category_ids = array();
        if (!empty($input['category_ids']) && is_array($input['category_ids'])) {
            foreach ($input['category_ids'] as $cat_id) {
                $cat_id = absint($cat_id);
                if ($cat_id && term_exists($cat_id, 'product_cat')) {
                    $category_ids[] = $cat_id;
                }
            }
        }

        $settings = array(
            'enabled'      => !empty($input['enabled']) ? 1 : 0,
            'min_age'      => max(1, isset($input['min_age']) ? absint($input['min_age']) : 18),
            'category_ids' => array_values(array_unique($category_ids)),
            'prompt_text'  => isset($input['prompt_text']) ? sanitize_textarea_field($input['prompt_text']) : self::get_settings()['prompt_text'],
        );
        update_option('hdage_settings', $settings);
        return $settings;
    }

    /**
     * True only if the gate is enabled AND the product genuinely belongs to one of the
     * admin-selected categories right now -- never assumed or cached from an earlier check.
     */
    public static function is_product_restricted($product_id)
    {
        $settings = self::get_settings();
        if (empty($settings['enabled']) || empty($settings['category_ids'])) {
            return false;
        }
        return (bool) has_term($settings['category_ids'], 'product_cat', $product_id);
    }

    /**
     * The only read of verification state. Always server-side: WooCommerce's session data is
     * a database row, not anything the visitor's browser can set directly.
     */
    public static function is_verified()
    {
        if (!function_exists('WC') || !WC()->session) {
            return false;
        }
        return (bool) WC()->session->get(self::SESSION_KEY, false);
    }

    /**
     * The only write of verification state. Not called from anywhere except
     * HDAGE_Frontend::handle_verify(), itself reached only after a real, nonce-checked form
     * submission -- never from a bare GET/cookie value.
     */
    public static function mark_verified()
    {
        if (function_exists('WC') && WC()->session) {
            WC()->session->set(self::SESSION_KEY, true);
        }
    }
}
