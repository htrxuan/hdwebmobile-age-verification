<?php

/**
 * Plugin Name: HDWebmobile Age Verification
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-age-verification/
 * Description: Age verification for restricted product categories, held server-side and re-checked at checkout, never trusted from a cookie.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-age-verification
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdage;

if (!defined('ABSPATH')) {
    exit;
}

define('HDAGE_VERSION', '1.0.0');
define('HDAGE_PLUGIN_FILE', __FILE__);
define('HDAGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDAGE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDAGE_PLUGIN_DIR . 'includes/class-hdage-activator.php';

register_activation_hook(__FILE__, array(HDAGE_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDAGE_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDAGE_PLUGIN_DIR . 'includes/class-hdage-core.php';
    HDAGE_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-age-verification') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
