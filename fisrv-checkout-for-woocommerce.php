<?php

/**
 * Plugin Name: Fiserv Checkout for WooCommerce
 * Version: 1.0.0
 * Description: Official Fiserv Checkout WooCommerce plugin
 * Author: Fiserv
 * Author URI: https://developer.fiserv.com/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: fisrv-checkout-for-woocommerce
 * Domain Path: /languages
 *
 * @package extension
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';

const FISRV_PLUGIN_VERSION = '1.0.0';

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function fisrv_checkout_for_woocommerce_missing_wc_notice(): void
{
    /* translators: %s WC download URL link. */
    echo '<div class="error"><p><strong>' . sprintf(esc_html__('Fiserv WooCommerce Plugin requires WooCommerce to be installed and active. You can download %s here.', 'fisrv-checkout-for-woocommerce'), '<a href="https://woo.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

register_activation_hook(__FILE__, 'fisrv_checkout_for_woocommerce_activate');

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function fisrv_checkout_for_woocommerce_activate(): void
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'fisrv_checkout_for_woocommerce_missing_wc_notice');

        return;
    }
}

if (!class_exists('fisrv_checkout_for_woocommerce')) {
    /**
     * The fisrv_checkout_for_woocommerce class.
     */
    class fisrv_checkout_for_woocommerce
    {
        /**
         * This class instance.
         *
         * @var \fisrv_checkout_for_woocommerce single instance of this class.
         */
        private static $instance;

        /**
         * Constructor.
         */
        private function __construct()
        {
            add_filter('woocommerce_payment_gateways', [$this, 'payment_gateways_callback']);

            /** Callback on failed payment, retry flow */
            add_action('before_woocommerce_pay_form', [WC_Fisrv_Checkout_Handler::class, 'retry_payment'], 1, 3);

            /** Callback on completed order */
            add_action('woocommerce_thankyou', [WC_Fisrv_Checkout_Handler::class, 'order_complete_callback'], 1, 1);

            /** Register webhook consumer */
            add_action('rest_api_init', [WC_Fisrv_Webhook_Handler::class, 'register_consume_events']);
        }

        /**
         * Cloning is forbidden.
         */
        public function __clone()
        {
            wc_doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'fisrv-checkout-for-woocommerce'), FISRV_PLUGIN_VERSION);
        }

        /**
         * Unserializing instances of this class is forbidden.
         */
        public function __wakeup()
        {
            wc_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'fisrv-checkout-for-woocommerce'), FISRV_PLUGIN_VERSION);
        }

        /**
         * Gets the main instance.
         *
         * Ensures only one instance can be loaded.
         *
         * @return \fisrv_checkout_for_woocommerce
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
                WC_Fisrv_Gateway_Applepay::class,
                WC_Fisrv_Gateway_Cards::class,
                WC_Fisrv_Gateway_Googlepay::class
            );

            return $methods;
        }
    }
}

add_action('plugins_loaded', 'fisrv_checkout_for_woocommerce_init', 10);

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function fisrv_checkout_for_woocommerce_init(): void
{
    load_plugin_textdomain('fisrv-checkout-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . '/languages');

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'fisrv_checkout_for_woocommerce_missing_wc_notice');

        return;
    }

    fisrv_checkout_for_woocommerce::instance();
}
