<?php

/**
 * Plugin Name: Fiserv Checkout for WooCommerce
 * Version: 1.1.1
 * Description: Official Fiserv Checkout WooCommerce plugin
 * Author: Fiserv
 * Author URI: https://developer.fiserv.com/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: fiserv-checkout-for-woocommerce
 * Domain Path: /languages
 *
 * @package extension
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';

const FISRV_PLUGIN_VERSION = '1.1.1';

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function fiserv_checkout_for_woocommerce_missing_wc_notice(): void
{
    /* translators: %s WC download URL link. */
    echo '<div class="error"><p><strong>' . sprintf(esc_html__('Fiserv WooCommerce Plugin requires WooCommerce to be installed and active. You can download %s here.', 'fiserv-checkout-for-woocommerce'), '<a href="https://woo.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

register_activation_hook(__FILE__, 'fiserv_checkout_for_woocommerce_activate');

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function fiserv_checkout_for_woocommerce_activate(): void
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'fiserv_checkout_for_woocommerce_missing_wc_notice');

        return;
    }
}

if (!class_exists('fiserv_checkout_for_woocommerce')) {
    /**
     * The fiserv_checkout_for_woocommerce class.
     */
    class fiserv_checkout_for_woocommerce
    {
        /**
         * This class instance.
         *
         * @var \fiserv_checkout_for_woocommerce single instance of this class.
         */
        private static $instance;

        /**
         * Constructor.
         */
        private function __construct()
        {
            add_filter('woocommerce_payment_gateways', [$this, 'payment_gateways_callback']);

            /** Callback on failed payment, retry flow on checkout */
            add_action('before_woocommerce_pay_form', [WC_Fiserv_Redirect_Back_Handler::class, 'retry_payment_on_checkout'], 1, 3);

            /** Callback on failed payment, retry flow on shopping cart */
            add_action('woocommerce_before_cart_table', [WC_Fiserv_Redirect_Back_Handler::class, 'retry_payment_on_cart'], 1);

            /** Callback on completed order */
            add_action('woocommerce_thankyou', [WC_Fiserv_Redirect_Back_Handler::class, 'order_complete_callback'], 1, 1);

            /** Register webhook consumer */
            add_action('rest_api_init', [WC_Fiserv_Webhook_Handler::class, 'register_consume_events']);

            /** Register health check endpoint */
            add_action('rest_api_init', [WC_Fiserv_Rest_Routes::class, 'register_health_report']);

            /** Register add icon endpoint */
            add_action('rest_api_init', [WC_Fiserv_Rest_Routes::class, 'register_add_image']);

            /** Register remove icon endpoint */
            add_action('rest_api_init', [WC_Fiserv_Rest_Routes::class, 'register_remove_image']);

            /** Register checkout details endpoint */
            add_action('rest_api_init', [WC_Fiserv_Rest_Routes::class, 'register_checkout_report']);

            /** Safely inject style sheet */
            wp_enqueue_style('fiserv-custom-style', plugins_url('assets\styles\fiserv-custom-style.css', __FILE__), [], FISRV_PLUGIN_VERSION);

            /** Safely inject scripts */
            wp_register_script('fiserv-custom-script', plugins_url('assets\scripts\fiserv-custom-script.js', __FILE__), [], FISRV_PLUGIN_VERSION, ['in_footer' => 'true']);
        }

        /**
         * Cloning is forbidden.
         */
        public function __clone()
        {
            wc_doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'fiserv-checkout-for-woocommerce'), FISRV_PLUGIN_VERSION);
        }

        /**
         * Unserializing instances of this class is forbidden.
         */
        public function __wakeup()
        {
            wc_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'fiserv-checkout-for-woocommerce'), FISRV_PLUGIN_VERSION);
        }

        /**
         * Gets the main instance.
         *
         * Ensures only one instance can be loaded.
         *
         * @return \fiserv_checkout_for_woocommerce
         */
        public static function instance(): object
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Load in payment gateways
         *
         * @param array<string> $methods Current methods
         * @return array<string> Methods to return
         */
        public function payment_gateways_callback(array $methods): array
        {
            array_push(
                $methods,
                WC_Fiserv_Gateway_Applepay::class,
                WC_Fiserv_Gateway_Googlepay::class,
                WC_Fiserv_Gateway_Cards::class,
                WC_Fiserv_Payment_Generic::class
            );

            return $methods;
        }
    }
}

add_action('plugins_loaded', 'fiserv_checkout_for_woocommerce_init', 10);

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function fiserv_checkout_for_woocommerce_init(): void
{
    load_plugin_textdomain('fiserv-checkout-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . '/languages');

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'fiserv_checkout_for_woocommerce_missing_wc_notice');

        return;
    }

    fiserv_checkout_for_woocommerce::instance();
}
