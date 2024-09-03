<?php

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Custom WooCommerce payment gateway.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
class WC_Fisrv_Payment_Gateway extends WC_Payment_Gateway
{

	protected ?PreSelectedPaymentMethod $selected_method = null;

	protected string $default_title = '';

	protected string $default_description = '';

	public function __construct()
	{
		$this->has_fields = false;
		$this->init_form_fields();
		$this->init_settings();
		$this->init_properties();

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_filter('woocommerce_gateway_icon', [$this, 'custom_payment_gateway_icons'], 10, 2);
	}

	public function is_available(): bool
	{
		return !in_array('', [
			$this->get_option('api_key'),
			$this->get_option('api_secret'),
			$this->get_option('store_id')
		]) && parent::is_available();
	}

	/**
	 * Initialize properties from options
	 */
	protected function init_properties(): void
	{
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->icon = $this->get_option('icon');
	}

	function custom_payment_gateway_icons($icon, $gateway_id)
	{
		if ($gateway_id !== $this->id || $gateway_id === 'fisrv-gateway-generic') {
			return $icon;
		}

		$plugin_path = untrailingslashit(plugin_dir_url(__FILE__));
		return '<img stlye="" src="' . WC_HTTPS::force_https_url("$plugin_path/../assets/$this->id.png") . '" alt="' . esc_attr($this->title) . '" />';
	}

	/**
	 * Initialize form text fields on gateway options page
	 */
	public function init_form_fields(): void
	{
		$this->form_fields = array(
			'api_key' => array(
				'title' => 'API Key',
				'type' => 'text',
				'description' => esc_html__('Acquire API Key from Developer Portal', 'fisrv-checkout-for-woocommerce'),
				'desc_tip' => true,
			),
			'api_secret' => array(
				'title' => 'API Secret',
				'type' => 'password',
				'description' => esc_html__('Acquire API Secret from Developer Portal', 'fisrv-checkout-for-woocommerce'),
				'desc_tip' => true,
			),
			'store_id' => array(
				'title' => 'Store ID',
				'type' => 'text',
				'description' => esc_html__('Your Store ID for Checkout', 'fisrv-checkout-for-woocommerce'),
				'desc_tip' => true,
			),
			'title' => array(
				'title' => 'Gateway Name',
				'type' => 'text',
				'description' => esc_html__('Custom name of gateway', 'fisrv-checkout-for-woocommerce'),
				'default' => $this->default_title,
				'desc_tip' => true,
			),
			'description' => array(
				'title' => 'Gateway Description',
				'type' => 'text',
				'description' => esc_html__('Custom description of gateway', 'fisrv-checkout-for-woocommerce'),
				'default' => $this->default_description,
				'desc_tip' => true,
			),
			'icon' => array(
				'title' => 'Gateway Icon',
				'type' => 'text',
				'description' => esc_html__('Link of image asset', 'fisrv-checkout-for-woocommerce'),
				'default' => '',
				'desc_tip' => true,
			),
			'fail_page' => array(
				'title' => 'Redirect after payment failure',
				'type' => 'select',
				'description' => esc_html__('Where to redirect if payment failed', 'fisrv-checkout-for-woocommerce'),
				'default' => '',
				'desc_tip' => true,
				'options' => array(
					'checkout' => 'Checkout page',
					'home' => 'Home page'
				),
			),
		);
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
			$checkout_link = WC_Fisrv_Checkout_Handler::create_checkout_link($order, $this->selected_method, $this);
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
			$response = WC_Fisrv_Checkout_Handler::refund_checkout($order, $amount);

			if (isset($response->error)) {
				$order->add_order_note('Refund failed due to ' . ($response->error->title ?? 'server error') . '. Check debug logs for detailed report.'($reason !== '') ? (' Refund reason given: ' . $reason) : '');
				return false;
			}

			$order->add_order_note('Refunded order via Fiserv Gateway. Transaction ID: ' . $response->ipgTransactionId . 'Trace ID: ' . $response->traceId . ($reason !== '') ? (' Refund reason given: ' . $reason) : '');

			return true;
		} catch (\Throwable $th) {
			return false;
		}
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
