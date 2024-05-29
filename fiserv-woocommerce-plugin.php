<?php


/**
 * Plugin Name: Fiserv Woocommerce Plugin
 * Version: 0.1.0
 * Author: Fiserv
 * Author URI: https://fiserv.com
 * Text Domain: fiserv-woocommerce-plugin
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package extension
 */


defined('ABSPATH') || exit;

if (!defined('MAIN_PLUGIN_FILE')) {
	define('MAIN_PLUGIN_FILE', __FILE__);
}

require_once plugin_dir_path(__FILE__) . '/vendor/autoload_packages.php';

use FiservWoocommercePlugin\Admin\Dashboard;
use FiservWoocommercePlugin\CheckoutHandler;

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function fiserv_woocommerce_plugin_missing_wc_notice()
{
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf(esc_html__('Fiserv Woocommerce Plugin requires WooCommerce to be installed and active. You can download %s here.', 'fiserv_woocommerce_plugin'), '<a href="https://woo.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

register_activation_hook(__FILE__, 'fiserv_woocommerce_plugin_activate');

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function fiserv_woocommerce_plugin_activate()
{
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'fiserv_woocommerce_plugin_missing_wc_notice');
		return;
	}
}

if (!class_exists('fiserv_woocommerce_plugin')) :
	/**
	 * The fiserv_woocommerce_plugin class.
	 */
	class fiserv_woocommerce_plugin
	{
		/**
		 * This class instance.
		 *
		 * @var \fiserv_woocommerce_plugin single instance of this class.
		 */
		private static $instance;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			if (is_admin()) {
				new Dashboard();
			}

			new CheckoutHandler();
			new WebhookHandler();

			add_filter('woocommerce_payment_gateways', [$this, 'payment_gateways_callback']);
			add_filter('woocommerce_get_settings_pages',  [$this, 'get_checkout_settings']);
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone()
		{
			wc_doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'fiserv_woocommerce_plugin'), $this->version);
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup()
		{
			wc_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'fiserv_woocommerce_plugin'), $this->version);
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
		 * @return \fiserv_woocommerce_plugin
		 */
		public static function instance()
		{
			if (null === self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function payment_gateways_callback($methods)
		{
			new CheckoutGateway();
			$methods[] = 'CheckoutGateway';
			return $methods;
		}


		function get_checkout_settings($woocommerce_settings)
		{
			$woocommerce_settings[] = new PluginSettings();
			return $woocommerce_settings;
		}
	}
endif;

add_action('plugins_loaded', 'fiserv_woocommerce_plugin_init', 10);

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function fiserv_woocommerce_plugin_init()
{
	load_plugin_textdomain('fiserv_woocommerce_plugin', false, plugin_basename(dirname(__FILE__)) . '/languages');

	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'fiserv_woocommerce_plugin_missing_wc_notice');
		return;
	}

	fiserv_woocommerce_plugin::instance();
}
