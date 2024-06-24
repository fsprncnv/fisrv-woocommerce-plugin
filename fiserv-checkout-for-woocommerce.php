<?php

/**
 * Plugin Name: Fisrv Checkout for Woocommerce
 * Version: 0.0.4
 * Description: Officail Fisrv Checkout Woocommerce plugin
 * Author: fisrv
 * Text Domain: fisrv-checkout-for-woocommerce
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package extension
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';

// phpcs:disable WordPress.Files.FileName

const PLUGIN_SLUG = 'fisrv_checkout_for_woocommerce';

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function fisrv_checkout_for_woocommerce_missing_wc_notice(): void
{
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf(esc_html__('Fisrv Woocommerce Plugin requires WooCommerce to be installed and active. You can download %s here.', 'fisrv_checkout_for_woocommerce'), '<a href="https://woo.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

register_activation_hook(__FILE__, PLUGIN_SLUG . '_activate');

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function fisrv_checkout_for_woocommerce_activate(): void
{
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', PLUGIN_SLUG . '_missing_wc_notice');
		return;
	}
}

if (!class_exists(PLUGIN_SLUG)) {
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
		private string $version = '0.0.4';

		private static bool $IS_DEV = true;

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

			if (self::$IS_DEV) {
				/** Fill out fields with default values for testing */
				add_filter('woocommerce_checkout_fields', [WC_Fisrv_Checkout_Handler::class, 'fill_out_fields']);
			}

			/** Register webhook consumer */
			add_action('rest_api_init', [WC_fisrv_Webhook_Handler::class, 'register_consume_events']);
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone()
		{
			wc_doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', PLUGIN_SLUG), $this->version);
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup()
		{
			wc_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', PLUGIN_SLUG), $this->version);
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
				WC_Fisrv_Gateway_Apple::class,
				WC_Fisrv_Gateway_Cards::class,
				WC_Fisrv_Gateway_GPay::class
			);
			return $methods;
		}
	}
}

add_action('plugins_loaded',  PLUGIN_SLUG . '_init', 10);

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function fisrv_checkout_for_woocommerce_init(): void
{
	load_plugin_textdomain(PLUGIN_SLUG, false, plugin_basename(dirname(__FILE__)) . '/languages');

	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', PLUGIN_SLUG . '_missing_wc_notice');
		return;
	}

	fisrv_checkout_for_woocommerce::instance();
}
