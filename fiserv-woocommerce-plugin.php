<?php

use Fiserv\CheckoutSolution;

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

use FiservWoocommercePlugin\Admin\Setup;

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
		 * Reference to SDK for API calls.
		 */
		public $sdk;

		/**
		 * Reference to client which binds to SDK calls.
		 */
		public $client;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			if (is_admin()) {
				new Setup();
			}
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


add_filter('woocommerce_get_sections_shipping', 'tester_add_section');

function tester_add_section($sections)
{

	$sections['tester'] = __('TEST MENU', 'text-domain');

	return $sections;
}

add_filter('woocommerce_get_settings_shipping', 'tester_all_settings', 10, 2);

function wcslider_all_settings($settings, $current_section)
{

	if ($current_section == 'tester') {

		$settings_slider = array();

		// Add Title to the Settings

		$settings_slider[] = [
			'name' => __('WC Slider Settings', 'text-domain'),
			'type' => 'title',
			'desc' => __('The following options are used to configure WC Slider', 'text-domain'),
			'id' => 'wcslider'
		];

		// Add first checkbox option

		$settings_slider[] = [
			'name' => __('Auto-insert into single product page', 'text-domain'),
			'desc_tip' => __('This will automatically insert your slider into the single product page', 'text-domain'),
			'id' => 'wcslider_auto_insert',
			'type' => 'checkbox',
			'css' => 'min-width:300px;',
			'desc' => __('Enable Auto-Insert', 'text-domain'),
		];


		$settings_slider[] = [
			'name' => __('Slider Title', 'text-domain'),
			'desc_tip' => __('This will add a title to your slider', 'text-domain'),
			'id' => 'wcslider_title',
			'type' => 'text',
			'desc' => __('Any title you want can be added to your slider with this option!', 'text-domain'),
		];

		$settings_slider[] = array('type' => 'sectionend', 'id' => 'wcslider');
		return $settings_slider;
	}

	return $settings;
}

remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
add_action('woocommerce_proceed_to_checkout', 'inject_fiserv_checkout_button', 1);

function inject_fiserv_checkout_button()
{
	global $woocommerce;
	$cart_subtotal = $woocommerce->cart->get_cart_subtotal();

	// $checkout_handler = new CheckoutHandler();
	// $checkout_link = createCheckoutLink();
	$checkout_link = "#";

	// wp_redirect(home_url("/sample-page/"));

	echo '
		<a href="' . $checkout_link . '" class="checkout-button button" style="background-color: #ff6600;"> Checkout with fiserv </a>
	';
}

const paymentLinksRequestContent = [
	'transactionOrigin' => 'ECOM',
	'transactionType' => 'SALE',
	'transactionAmount' => ['total' => 130, 'currency' => 'EUR'],
	'checkoutSettings' => [
		'locale' => 'en_GB',
		"redirectBackUrls" => [
			"successUrl" => "https://www.successexample.com",
			"failureUrl" => "https://www.failureexample.com"
		]
	],
	'paymentMethodDetails' => [
		'cards' => [
			'authenticationPreferences' => [
				'challengeIndicator' => '01',
				'skipTra' => false,
			],
			'createToken' => [
				'declineDuplicateToken' => false,
				'reusable' => true,
				'toBeUsedFor' => 'UNSCHEDULED',
			],
			'tokenBasedTransaction' => ['transactionSequence' => 'FIRST']
		],
		'sepaDirectDebit' => ['transactionSequenceType' => 'SINGLE']
	],
	'merchantTransactionId' => 'AB-1234',
	'storeId' => '72305408',
];


/**
 * Get cart data from WC stub to be served to Checkout Solution.
 */
function createCheckoutLink(): string
{
	$req = new PaymentLinkRequestBody(paymentLinksRequestContent);
	$res = CheckoutSolution::postCheckouts($req);

	return $res->checkout->redirectionUrl;
}
