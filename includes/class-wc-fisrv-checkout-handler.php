<?php

use Fisrv\Checkout\CheckoutClient;
use Fisrv\Exception\ErrorResponse;
use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\Currency;
use Fisrv\Models\LineItem;
use Fisrv\Models\Locale;
use Fisrv\Models\PaymentsClientRequest;
use Fisrv\Models\PaymentsClientResponse;
use Fisrv\Models\PreSelectedPaymentMethod;
use Fisrv\Payments\PaymentsClient;

/**
 * Class that handles creation of redirection link
 * of checkout solution and further checkout related data handling.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
final class WC_Fisrv_Checkout_Handler
{

	const FISRV_NONCE = 'fisrv-nonce';

	private static CheckoutClient $client;

	/**
	 * Triggered right before the Pay for Order form, after validation of the order and customer.
	 *
	 * @param WC_Order                  $order              The order that is being paid for.
	 * @param string                    $order_button_text  The text for the submit button.
	 * @param array<WC_Payment_Gateway> $available_gateways All available gateways.
	 *
	 * @return array<string, mixed> | false   Passed (and modified) function params
	 */
	public static function retry_payment(WC_Order $order, string $order_button_text, array $available_gateways): array|false
	{

		$order_button_text = esc_html__('Retry payment', 'fisrv-checkout-for-woocommerce');
		$order->update_status('wc-pending', esc_html__('Retrying payment', 'fisrv-checkout-for-woocommerce'));


		if (isset($_GET['message']) && isset($_GET['code'])) {
			if (check_admin_referer(self::FISRV_NONCE)) {
				return false;
			}

			$fisrv_error_message = sanitize_text_field(wp_unslash($_GET['message']));
			$fisrv_error_code = sanitize_text_field(wp_unslash($_GET['code']));
		}

		/* translators: %s: Fisrv error message */
		wc_add_notice(sprintf(esc_html__('Payment has failed: %s', 'fisrv-checkout-for-woocommerce'), $fisrv_error_message ?? 'Internal error'), 'error');
		wc_print_notices();
		/* translators: %1$s: Fisrv error message %2$s: Fisrv error message */
		WC_Fisrv_Logger::error($order, sprintf('Payment failed of checkout %s, retrying on checkout page: (%s - %s)', $order->get_meta('_fisrv_plugin_checkout_id') ?? 'No checkout ID created', $fisrv_error_message ?? 'No error message provided', $fisrv_error_code ?? 'No code provided'));

		return array(
			'order' => $order,
			'available_gateways' => $available_gateways,
			'order_button_text' => $order_button_text,
		);
	}

	/**
	 * This is called when order is complete on thank you page.
	 * Set order status and payment to completed
	 *
	 * @param string $order_id WC order ID
	 */
	public static function order_complete_callback(string $order_id): void
	{
		$order = wc_get_order($order_id);

		if (!$order instanceof WC_Order) {
			return;
		}

		if (check_admin_referer(self::FISRV_NONCE)) {
			if (sanitize_text_field($_GET['transaction_approved'])) {
				$has_completed = $order->payment_complete();
				if ($has_completed) {
					$order->update_status('wc-completed', __('Order has completed', 'fisrv-checkout-for-woocommerce'));
					WC_Fisrv_Logger::log($order, __('Order completed with card payment.', 'fisrv-checkout-for-woocommerce'));
				}
			}
		}
	}

	/**
	 * Inititalize configuraiton parameters of fisrv SDK.
	 */
	public static function init_api_credentials(): void
	{
		$gateway = WC()->payment_gateways()->payment_gateways()['fisrv-gateway-generic'];

		if (!($gateway instanceof WC_Fisrv_Payment_Gateway)) {
			throw new Exception('Could not retrieve payment settings');
		}

		$plugin_data = get_plugin_data(__DIR__ . '..//fisrv-checkout-for-woocommerce.php');
		$plugin_version = $plugin_data['Version'];

		self::$client = new CheckoutClient(
			array(
				'user' => 'WooCommercePlugin/' . $plugin_version,
				'is_prod' => $gateway->get_option('is_prod'),
				'api_key' => $gateway->get_option('api_key'),
				'api_secret' => $gateway->get_option('api_secret'),
				'store_id' => $gateway->get_option('store_id'),
			)
		);
	}

	/**
	 * Create a checkout link
	 *
	 * @param WC_Order                 $order WC order
	 * @param PreSelectedPaymentMethod|null $method Selected payment method
	 *
	 * @return string URL of hosted payment page
	 *
	 * @throws Exception Error thrown from fisrv SDK (Request Errors). Error is caught by setting
	 * returned checkout link to '#' (no redirect)
	 */
	public static function create_checkout_link(WC_Order $order, ?PreSelectedPaymentMethod $method): string
	{
		try {
			self::init_api_credentials();

			$request = self::$client->createBasicCheckoutRequest(0, '', '');
			$request = self::pass_checkout_data($request, $order, $method);
			$request = self::pass_billing_data($request, $order);
			$request = self::pass_basket($request, $order);

			$response = self::$client->createCheckout($request);

			$checkout_id = $response->checkout->checkoutId;
			$checkout_link = $response->checkout->redirectionUrl;
			$trace_id = $response->traceId;

			$order->update_meta_data('_fisrv_plugin_checkout_link', $checkout_link);
			$order->update_meta_data('_fisrv_plugin_checkout_id', $checkout_id);
			$order->update_meta_data('_fisrv_plugin_trace_id', $response->traceId);
			$order->save_meta_data();
			/* translators: %1$s: Checkout link %2$s: Checkout ID %3$s: Checkout trace ID */
			$order->add_order_note(sprintf(esc_html__('Fiserv checkout link %1$s created with checkout ID %2$s and trace ID %3$s.', 'fisrv-checkout-for-woocommerce'), $checkout_link, $checkout_id, $trace_id));

			return $checkout_link;
		} catch (Throwable $th) {
			if (str_starts_with($th->getMessage(), '401')) {
				/* translators: %s: Method value */
				throw new Exception(esc_html__('Payment method failed. Please check on settings page if API credentials are set correctly.', 'fisrv-checkout-for-woocommerce'));
			}

			throw $th;
		}
	}

	public static function refund_checkout(WC_Order $order, $amount): PaymentsClientResponse
	{
		self::init_api_credentials();
		$response = self::$client->refundCheckout(new PaymentsClientRequest([
			'transactionAmount' => [
				'total' => $amount,
				'currency' => get_woocommerce_currency(),
			],
		]), $order->get_meta('_fisrv_plugin_checkout_id'));

		return $response;
	}

	public static function get_health_report(): array
	{
		$gateway = WC()->payment_gateways()->payment_gateways()['fisrv-gateway-generic'];
		$paymentsClient = new PaymentsClient([
			'is_prod' => $gateway->get_option('is_prod'),
			'api_key' => $gateway->get_option('api_key'),
			'api_secret' => $gateway->get_option('api_secret'),
			'store_id' => $gateway->get_option('store_id'),
		]);

		$report = $paymentsClient->reportHealthCheck();

		if ($report->httpCode != 200) {
			$message = $report->error->message;
			WC_Fisrv_Logger::generic_log('API health check reported following error response: ' . json_encode($report));
		}

		return [
			'status' => $report->error->code ?? 'ok',
			'message' => $message ?? "You're all set!"
		];
	}

	/**
	 * Pass line items from WC to checkout
	 *
	 * @param CheckoutClientRequest $req    Request object to modify
	 * @param WC_Order              $order	WooCommerce order object
	 * @return CheckoutClientRequest        Modified request object
	 */
	private static function pass_basket(CheckoutClientRequest $req, WC_Order $order): CheckoutClientRequest
	{
		$wc_items = $order->get_items();

		foreach ($wc_items as $item) {
			$req->order->basket->lineItems[] = new LineItem(
				array(
					'itemIdentifier' => $item->get_id(),
					'name' => $item->get_name(),
					'price' => $order->get_item_total($item),
					'total' => $order->get_line_total($item),
					'quantity' => $item->get_quantity(),
					'shippingCost' => 0,
					'valueAddedTax' => 0,
					'miscellaneousFee' => 0,
				)
			);
		}

		return $req;
	}

	private static function truncate($val)
	{
		return number_format($val, 2);
	}

	/**
	 * Pass checkout data (totals, redirects, language etc.) to request object of checkout
	 *
	 * @param CheckoutClientRequest $req    	Request object to modify
	 * @param WC_Order              $order		WooCommerce order object
	 * @param PreSelectedPaymentMethod<null $method	Selected payment method
	 * @return CheckoutClientRequest        Modified request object
	 */
	private static function pass_checkout_data(CheckoutClientRequest $req, WC_Order $order, ?PreSelectedPaymentMethod $method): CheckoutClientRequest
	{
		/** Locale */
		$wp_language = str_replace('-', '_', get_bloginfo('language'));
		$locale = Locale::tryFrom($wp_language);

		if (substr($wp_language, 0, 2) == 'de') {
			$locale = Locale::de_DE;
		}

		$req->checkoutSettings->locale = $locale ?? Locale::en_GB;

		/** Currency */
		$wp_currency = get_woocommerce_currency();
		$req->transactionAmount->currency = Currency::tryFrom($wp_currency) ?? Currency::EUR;

		/** WC order numbers, IDs */
		$req->merchantTransactionId = strval($order->get_id());
		$req->order->orderDetails->purchaseOrderNumber = strval($order->get_id());

		ini_set('precision', 8);
		ini_set('serialize_precision', -1);

		/** Order totals */
		$req->transactionAmount->total = $order->get_total();
		$req->transactionAmount->components->subtotal = $order->get_subtotal();
		$req->transactionAmount->components->vatAmount = $order->get_total_tax();
		$req->transactionAmount->components->shipping = floatval($order->get_shipping_total());

		WC_Fisrv_Logger::log(
			$order,
			'TOTAL: ' . $req->transactionAmount->total .
			' SUBTOTAL: ' . $req->transactionAmount->components->subtotal .
			' VAT: ' . $req->transactionAmount->components->vatAmount .
			' SHIP: ' . $req->transactionAmount->components->shipping
		);

		/** Redirect URLs */
		$nonce = wp_create_nonce(self::FISRV_NONCE);

		$req->checkoutSettings->redirectBackUrls->successUrl = add_query_arg(
			array(
				'_wpnonce' => $nonce,
				'transaction_approved' => true,
			),
			$order->get_checkout_order_received_url()
		);

		$req->checkoutSettings->redirectBackUrls->failureUrl = add_query_arg(
			array(
				'_wpnonce' => $nonce,
				'transaction_approved' => false,
				'wc_order_id' => $order->get_id(),
			),
			$order->get_checkout_payment_url()
		);

		/** Append ampersand to allow checkout solution to append query params */
		$req->checkoutSettings->redirectBackUrls->failureUrl .= '&';

		$req->checkoutSettings->webHooksUrl = add_query_arg(
			array(
				'_wpnonce' => $nonce,
				'wc_order_id' => $order->get_id(),
			),
			WC_Fisrv_Webhook_Handler::$webhook_endpoint . '/events'
		);

		if ($order->get_payment_method() !== 'fisrv-gateway-generic') {
			$req->checkoutSettings->preSelectedPaymentMethod = $method;
		}

		return $req;
	}

	/**
	 * Pass billing data from WC billing form to request object of checkout
	 *
	 * @param CheckoutClientRequest $req    Request object to modify
	 * @param WC_Order              $order               WooCommerce order object
	 * @return CheckoutClientRequest        Modified request object
	 */
	private static function pass_billing_data(CheckoutClientRequest $req, WC_Order $order): CheckoutClientRequest
	{
		$req->order->billing->person->firstName = $order->get_billing_first_name();
		$req->order->billing->person->lastName = $order->get_billing_last_name();
		$req->order->billing->contact->email = $order->get_billing_email();
		$req->order->billing->address->address1 = $order->get_billing_address_1();
		$req->order->billing->address->address2 = $order->get_billing_address_2();
		$req->order->billing->address->city = $order->get_billing_city();
		$req->order->billing->address->country = $order->get_billing_country();
		$req->order->billing->address->postalCode = $order->get_billing_postcode();

		return $req;
	}
}
