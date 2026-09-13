<?php

namespace htrxuan\hdage;

if (!defined('ABSPATH')) {
    exit;
}

class HDAGE_Admin
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        require_once HDAGE_PLUGIN_DIR . 'includes/class-hdage-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['age-verification'] = array(
            'label'  => __('Age Verification', 'hdwebmobile-age-verification'),
            'order'  => 153,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function register_settings()
    {
        register_setting('hdage_settings_group', 'hdage_settings', array('htrxuan\\hdage\\HDAGE_Repository', 'save_settings'));
    }

    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-age-verification'));
        }

        $settings   = HDAGE_Repository::get_settings();
        $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        ?>
        <p><?php esc_html_e('Require a confirmation before a customer can buy anything in the categories you choose below. Verification is stored server-side for the customer\'s session -- it is re-checked at add-to-cart and again at checkout, so it can never be skipped by editing a cookie.', 'hdwebmobile-age-verification'); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields('hdage_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'hdwebmobile-age-verification'); ?></th>
                    <td><label><input type="checkbox" name="hdage_settings[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> /> <?php esc_html_e('Require age verification', 'hdwebmobile-age-verification'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="hdage_min_age"><?php esc_html_e('Minimum age', 'hdwebmobile-age-verification'); ?></label></th>
                    <td><input type="number" id="hdage_min_age" name="hdage_settings[min_age]" min="1" step="1" value="<?php echo esc_attr($settings['min_age']); ?>" style="width:100px;" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Restricted categories', 'hdwebmobile-age-verification'); ?></th>
                    <td>
                        <?php if (empty($categories) || is_wp_error($categories)) : ?>
                            <p><?php esc_html_e('No product categories found.', 'hdwebmobile-age-verification'); ?></p>
                        <?php else : ?>
                            <?php foreach ($categories as $category) : ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="hdage_settings[category_ids][]" value="<?php echo esc_attr($category->term_id); ?>" <?php checked(in_array((int) $category->term_id, array_map('intval', $settings['category_ids']), true)); ?> />
                                    <?php echo esc_html($category->name); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hdage_prompt_text"><?php esc_html_e('Prompt text', 'hdwebmobile-age-verification'); ?></label></th>
                    <td><textarea id="hdage_prompt_text" name="hdage_settings[prompt_text]" rows="3" style="width:100%;max-width:500px;"><?php echo esc_textarea($settings['prompt_text']); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(__('Save Settings', 'hdwebmobile-age-verification')); ?>
        </form>
        <?php
    }
}
