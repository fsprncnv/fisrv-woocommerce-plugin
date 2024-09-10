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
class WC_Fisrv_Payment_Gateway extends WC_Payment_Gateway
{

	protected ?PreSelectedPaymentMethod $selected_method = null;

	protected string $default_title = '';

	protected string $default_description = '';

	protected array $supported_methods = [];

	public function __construct()
	{
		$this->has_fields = false;
		$this->init_form_fields();
		// $this->init_settings();
		$this->init_properties();
		$this->supports = [
			'products',
			'refunds'
		];

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_filter('woocommerce_gateway_icon', [$this, 'custom_payment_gateway_icons'], 10, 2);
		add_filter("woocommerce_generate_custom_icon_html", [$this, 'custom_icon_settings_field'], 1, 4);
		add_filter("woocommerce_generate_healthcheck_html", [$this, 'healthcheck_settings_field'], 1, 4);
		add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, [$this, 'custom_save_icon_value'], 10, 1);
		// add_filter('woocommerce_locate_template', [$this, 'custom_woocommerce_locate_template'], 10, 3);
	}


	public function custom_save_icon_value($settings)
	{
		return $settings;
	}

	/**
	 * 
	 * @param string $field_html The markup of the field being generated (initiated as an empty string).
	 * @param string $key The key of the field.
	 * @param array  $data The attributes of the field as an associative array.
	 * @param WC_Settings_API $wc_settings The current WC_Settings_API object.
	 */
	public function custom_icon_settings_field(string $field_html, string $key, array $data, WC_Settings_API $wc_settings)
	{
		$html_identifier = "woocommerce_{$wc_settings->id}_{$key}";
		// $variable_icon = esc_attr($wc_settings->get_option('icon', $this->get_default_icon()));

		$field_html = '<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="' . $html_identifier . '">' . $data['title'] . ' <span class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span></label>
			</th>
			<td class="forminp">
				<fieldset style="display: flex; flex-direction: row;">
					<legend class="screen-reader-text"><span>Gateway Name</span></legend>
					' . $this->render_icons_of_supported_methods($wc_settings->id, '', $height = '4rem') . '
					<div class="fs-add-button" onclick="console.log(' . "'clicked'" . ')">+</div>
				</fieldset>
			</td>
			<style>
				.fs-add-button {
					display: flex;
					justify-content: center;
					align-items: center;
					width: ' . $height . ';
					height: ' . $height . ';
					background-color: white;
					cursor: pointer;
					border-radius: 10px;
					font-size: 1.5em;
				}

				.fs-add-button:hover {
					opacity: 0.6;
					transition-duration: 0.1s;
				}
			</style>
		</tr>';

		return $field_html;
	}

	public function healthcheck_settings_field(string $field_html, string $key, array $data, WC_Settings_API $wc_settings)
	{
		$html_identifier = "woocommerce_{$wc_settings->id}_{$key}";

		$field_html = '<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="' . $html_identifier . '">' . $data['title'] . ' <span class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span></label>
			</th>
			<td class="forminp">
				<fieldset style="display: flex; flex-direction: row; align-items: center;">
					<legend class="screen-reader-text"><span>Gateway Name</span></legend>
					<div id="fs-fetcher-icon" class="fs-add-button" onclick="fetchHealth()">+</div>
					<div style="display: flex; flex-direction: row; margin-left: 1rem; align-items: center;">
						<div id="fs-status-indicator" style="background-color: lightblue; border-radius: 100%; width: 0.8em; height: 0.8em; margin-right: 1em;"></div>
						<div id="fs-status-text">Check status</div>
					</div>
				</fieldset>
			</td>
			<script>
				async function fetchHealth() {
					const indicator = document.getElementById("fs-status-indicator");
					const text = document.getElementById("fs-status-text");
					const fetcherIcon = document.getElementById("fs-fetcher-icon");

					text.innerHTML = ' . "'<span class=\"fs-loader-status\"></span>';" . '
					const res = await fetch("/wp-json/fisrv_woocommerce_plugin/v1/health", {
                        method: "GET",
                    });
                    const data = await res.json();
					text.innerHTML = data["message"];

					if(data["status"] !== "ok") {
                        indicator.style.background = "OrangeRed";
                    } else {
                        indicator.style.background = "LightGreen";
                    }
				}
			</script>
			<style>
				.fs-loader-status {
					width: 24px;
					height: 24px;
					border: 2px solid gray;
					border-bottom-color: transparent;
					border-radius: 50%;
					display: inline-block;
					box-sizing: border-box;
					animation: rotation 1s linear infinite;
				}
			</style>
		</tr>';
		return $field_html;
	}

	private function render_text_field(string $value = '', string $key, WC_Settings_API $wc_settings)
	{
		$html_identifier = "woocommerce_{$wc_settings->id}_{$key}";
		return '<input class="input-text regular-input " type="text" 
					name="' . $html_identifier . '" id="' . $html_identifier . '" style="" value="' . $value . '" placeholder="">';
	}

	public function is_available(): bool
	{
		$generic_gateway = WC()->payment_gateways()->payment_gateways()['fisrv-gateway-generic'];

		return !in_array('', [
			$generic_gateway->get_option('api_key'),
			$generic_gateway->get_option('api_secret'),
			$generic_gateway->get_option('store_id')
		]) && parent::is_available();
	}

	public function update_option($key, $value = '')
	{
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

	public function custom_payment_gateway_icons($icon, $gateway_id)
	{
		if (!str_starts_with($gateway_id, 'fisrv')) {
			return $icon;
		}

		return $this->render_icons_of_supported_methods($gateway_id, $styles = 'margin-left: auto;');
	}

	private function render_icons_of_supported_methods(string $gateway_id, string $styles = '', string $height = '2rem')
	{
		$gateway = WC()->payment_gateways()->payment_gateways()[$gateway_id];
		$icon_html = '<div style=' . $styles . '>';

		foreach ($gateway->supported_methods as $supported_method) {
			$is_png = in_array($supported_method, ['paypal']);

			if ($supported_method === 'creditcard') {
				$image_src = "https://logowik.com/content/uploads/images/credit-card2790.jpg";
			} else {
				$image_src = "https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/" . ($is_png ? '72x72' : 'icons') . "/{$supported_method}." . ($is_png ? 'png' : 'svg');
			}

			$icon_html .= '<img style="border-radius: 10%; height: ' . $height . '; margin-right: 5px" src="' . WC_HTTPS::force_https_url($image_src) . '" alt="' . esc_attr($this->title) . '" />';
		}

		$icon_html .= '</div>';
		return $icon_html;
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
	 * Initialize form text fields on gateway options page
	 */
	public function init_form_fields(): void
	{
		if ($this->id === 'fisrv-gateway-generic') {
			$this->form_fields = [
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
				'is_prod' => array(
					'title' => 'Production Mode',
					'type' => 'checkbox',
					'description' => esc_html__('Use Live (Production) Mode or Test (Sandbox) Mode', 'fisrv-checkout-for-woocommerce'),
					'desc_tip' => true,
				),
				'healthcheck' => array(
					'title' => 'API Health',
					'type' => 'healthcheck',
					'description' => esc_html__('Get current status of Fiserv API and your configuration', 'fisrv-checkout-for-woocommerce'),
					'desc_tip' => true,
				)
			];
		}


		$this->form_fields += array(
			'icon' => array(
				'title' => 'Gateway Icon',
				'description' => esc_html__('Link of image asset', 'fisrv-checkout-for-woocommerce'),
				// 'default' => $this->get_default_icon(),
				'type' => 'custom_icon',
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
			$response = WC_Fisrv_Checkout_Handler::refund_checkout($order, $amount);

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
