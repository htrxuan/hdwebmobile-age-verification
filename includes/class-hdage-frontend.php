<?php

namespace htrxuan\hdage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the confirmation prompt on a restricted product's page, handles its (guest-friendly)
 * confirmation submission, and enforces verification again at add-to-cart and
 * woocommerce_check_cart_items -- the same dual-hook, no-bypass-via-checkout pattern used
 * elsewhere in this suite (see HDMOQ_Repository's docblock for the general rationale).
 */
final class HDAGE_Frontend
{

    const NONCE_VERIFY = 'hdage_verify';

    private static $instance = null;
    private static $prompt_done = false;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_post_hdage_verify', array($this, 'handle_verify'));
        add_action('admin_post_nopriv_hdage_verify', array($this, 'handle_verify'));

        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 2);
        add_action('woocommerce_check_cart_items', array($this, 'validate_cart_contents'));

        add_action('woocommerce_before_single_product_summary', array($this, 'render_prompt'), 1);
        add_filter('render_block', array($this, 'block_fallback'), 10, 2);
    }

    public function validate_add_to_cart($passed, $product_id)
    {
        if (HDAGE_Repository::is_product_restricted($product_id) && !HDAGE_Repository::is_verified()) {
            wc_add_notice(__('Please confirm your age before adding this product to your cart.', 'hdwebmobile-age-verification'), 'error');
            return false;
        }
        return $passed;
    }

    public function validate_cart_contents()
    {
        if (!WC()->cart || HDAGE_Repository::is_verified()) {
            return;
        }
        foreach (WC()->cart->get_cart() as $item) {
            if (HDAGE_Repository::is_product_restricted((int) $item['product_id'])) {
                wc_add_notice(__('Your cart contains an age-restricted product. Please confirm your age to check out.', 'hdwebmobile-age-verification'), 'error');
                return;
            }
        }
    }

    public function handle_verify()
    {
        check_admin_referer(self::NONCE_VERIFY, 'hdage_nonce');

        if (!empty($_POST['hdage_confirm'])) {
            HDAGE_Repository::mark_verified();
        }

        $redirect = !empty($_POST['hdage_redirect']) ? esc_url_raw(wp_unslash($_POST['hdage_redirect'])) : home_url('/');
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Hooked at a priority BEFORE WooCommerce's own classic add-to-cart template (30), so
     * when the gate applies, this can remove that action for the rest of this page load --
     * hiding the purchase form is purely a UX nicety on top of the real server-side
     * enforcement in validate_add_to_cart()/validate_cart_contents() above, which is what
     * actually stops a purchase either way.
     */
    public function render_prompt()
    {
        global $product;
        if (self::$prompt_done || !$product instanceof \WC_Product) {
            return;
        }
        if (!HDAGE_Repository::is_product_restricted($product->get_id()) || HDAGE_Repository::is_verified()) {
            return;
        }
        self::$prompt_done = true;
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        $this->output_prompt();
    }

    public function block_fallback($block_content, $block)
    {
        $name = is_array($block) && isset($block['blockName']) ? $block['blockName'] : '';
        if ('woocommerce/add-to-cart-form' !== $name || !is_singular('product')) {
            return $block_content;
        }
        if (self::$prompt_done) {
            return $block_content;
        }
        global $product;
        if (!$product instanceof \WC_Product || !HDAGE_Repository::is_product_restricted($product->get_id()) || HDAGE_Repository::is_verified()) {
            return $block_content;
        }
        self::$prompt_done = true;
        ob_start();
        $this->output_prompt();
        // Replace the purchase form entirely, not just prepend to it -- same UX nicety as the
        // classic-theme path above, on top of the real server-side enforcement.
        return ob_get_clean();
    }

    private function output_prompt()
    {
        $settings = HDAGE_Repository::get_settings();
        ?>
        <div class="hdage-gate">
            <p class="hdage-gate__text">
                <?php
                printf(
                    /* translators: %d: minimum age */
                    esc_html__('You must be at least %d years old to purchase this product.', 'hdwebmobile-age-verification'),
                    (int) $settings['min_age']
                );
                ?>
            </p>
            <p class="hdage-gate__text"><?php echo esc_html($settings['prompt_text']); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="hdage_verify" />
                <input type="hidden" name="hdage_redirect" value="<?php echo esc_url(get_permalink()); ?>" />
                <?php wp_nonce_field(self::NONCE_VERIFY, 'hdage_nonce'); ?>
                <button type="submit" name="hdage_confirm" value="1" class="button hdage-gate__yes"><?php esc_html_e('Yes, I meet the minimum age', 'hdwebmobile-age-verification'); ?></button>
                <a class="button hdage-gate__no" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('No, take me back', 'hdwebmobile-age-verification'); ?></a>
            </form>
        </div>
        <?php
    }
}
