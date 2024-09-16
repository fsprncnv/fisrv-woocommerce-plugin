<?php

use Fisrv\Exception\ErrorResponse;
use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Custom WooCommerce payment gateway.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
abstract class WC_Fisrv_Payment_Gateway extends WC_Fisrv_Payment_Settings
{
	protected ?PreSelectedPaymentMethod $selected_method = null;

	protected array $supported_methods = [];

	public function __construct()
	{
		$this->has_fields = false;
		$this->init_form_fields();
		$this->init_properties();
		$this->supports = [
			'products',
			'refunds'
		];

		add_filter('woocommerce_gateway_icon', [$this, 'custom_payment_gateway_icons'], 10, 2);

		add_filter("woocommerce_generate_custom_icon_html", [WC_Fisrv_Payment_Settings::class, 'custom_icon_settings_field'], 1, 4);
		add_filter("woocommerce_generate_healthcheck_html", [WC_Fisrv_Payment_Settings::class, 'healthcheck_settings_field'], 1, 4);
		add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, [WC_Fisrv_Payment_Settings::class, 'custom_save_icon_value'], 10, 1);
		add_filter('woocommerce_locate_template', [$this, 'custom_woocommerce_locate_template'], 10, 3);
	}

	public function is_available(): bool
	{
		// $generic_gateway = WC()->payment_gateways()->payment_gateways()['fisrv-gateway-generic'];

		// return !in_array('', [
		// 	$generic_gateway->get_option('api_key'),
		// 	$generic_gateway->get_option('api_secret'),
		// 	$generic_gateway->get_option('store_id')
		// ]) && parent::is_available();
		// return true;
		return parent::is_available();
	}

	public function update_option($key, $value = '')
	{
		WC_Fisrv_Logger::generic_log('update_option() fired');

		if ($key === 'icons') {
			// parent::update_option('icons', 'blub');
			return;
		}

		// WC_Fisrv_Logger::generic_log($key . ' ' . $value);

		parent::update_option($key, $value);

		if ($key === 'enabled' && $value === 'yes') {
			if ($this->id === 'fisrv-gateway-generic') {
				$this->disable_gateway(new WC_Fisrv_Gateway_Applepay());
				$this->disable_gateway(new WC_Fisrv_Gateway_Googlepay());
				$this->disable_gateway(new WC_Fisrv_Gateway_Cards());
				wc_add_notice(__('Disabled specific gateways since generic gateway was enabled.', 'fisrv-checkout-for-woocommerce'));
			} else {
				$this->disable_gateway(new WC_Fisrv_Payment_Generic());
				wc_add_notice(__('Disabled generic gateway since specific gateways were enabled.', 'fisrv-checkout-for-woocommerce'));
			}
		}
	}

	private function disable_gateway(WC_Payment_Gateway $gateway): void
	{
		if ($gateway->get_option('enabled') === 'yes') {
			$gateway->update_option('enabled', 'no');
		}
	}

	/**
	 * Initialize properties from options
	 */
	protected function init_properties(): void
	{
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		// $this->icon = $this->get_option('icon', $this->get_default_icon());
	}

	public static function custom_payment_gateway_icons($icon, $gateway_id)
	{
		if (!str_starts_with($gateway_id, 'fisrv')) {
			return $icon;
		}

		return self::render_gateway_icons(true, $gateway_id, $styles = 'margin-left: auto;');
	}

	public function custom_woocommerce_locate_template($template, $template_name, $template_path)
	{
		if (!str_starts_with($this->id, 'fisrv')) {
			return $template;
		}

		$custom_template_path = plugin_dir_path(__FILE__) . '../templates/checkout/payment-method.php';

		if ($template_name === 'checkout/payment-method.php') {
			return $custom_template_path;
		}

		return $template;
	}

	/**
	 * @return array<string, string>
	 */
	public function process_payment($order_id): array
	{
		$order = wc_get_order($order_id);

		if (!$order instanceof WC_Order) {
			throw new Exception(esc_html__('Processing payment failed. Order is invalid.', 'fisrv-checkout-for-woocommerce'));
		}

		try {
			$checkout_link = WC_Fisrv_Checkout_Handler::create_checkout_link($order, $this->selected_method);
			return array(
				'result' => 'success',
				'redirect' => $checkout_link,
			);
		} catch (\Throwable $th) {
			WC_Fisrv_Logger::error($order, $th->getMessage());
			return array(
				'result' => 'failure',
			);
		}
	}

	public function process_refund($order_id, $amount = null, $reason = ''): bool|WP_Error
	{
		$order = wc_get_order($order_id);

		if (!$order instanceof WC_Order) {
			throw new Exception(esc_html__('Processing payment failed. Order is invalid.', 'fisrv-checkout-for-woocommerce'));
		}

		try {
			$response = WC_Fisrv_Checkout_Handler::refund_checkout($this, $order, $amount);

			if (isset($response->error)) {
				$order->add_order_note("Refund failed due to {($response->error->title ?? 'server error')}. Check debug logs for detailed report." . (($reason !== '') ? (" Refund reason given: $reason") : ''));
				return false;
			}

			$order->add_order_note("Order refunded via Fiserv Gateway. Refunded amount: {$response->approvedAmount->total} {$response->approvedAmount->currency->value} Transaction ID: {$response->ipgTransactionId}" . (($reason !== '') ? (" Refund reason given: $reason") : ''));

			return true;
		} catch (ErrorResponse $e) {
			WC_Fisrv_Logger::log($order, 'Refund has failed on API client (or server) level: ' . $e->getMessage());
		} catch (\Throwable $th) {
			WC_Fisrv_Logger::log($order, 'Refund has failed on backend level: ' . $th->getMessage());
		}

		return false;
	}

	/**
	 * Summary of can_refund_order
	 * @param WC_Order $order
	 * @return bool
	 */
	public function can_refund_order(mixed $order)
	{
		if (!($order instanceof WC_Order)) {
			return false;
		}

		$refundable = $order->is_paid();
		$refundable = str_starts_with($order->get_payment_method(), 'fisrv');

		return $refundable;
	}
}
