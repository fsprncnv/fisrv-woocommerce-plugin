<?php

use Fisrv\Checkout\CheckoutClient;
use Fisrv\Exception\ErrorResponse;
use Fisrv\HttpClient\HttpClient;
use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\Currency;
use Fisrv\Models\LineItem;
use Fisrv\Models\Locale;
use Fisrv\Models\PaymentsClientRequest;
use Fisrv\Models\PaymentsClientResponse;
use Fisrv\Models\PreSelectedPaymentMethod;
use Fisrv\Models\ToBeUsedFor;
use Fisrv\Models\TransactionSequenceType;
use Fisrv\Models\TransactionType;
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

		WC_Fisrv_Logger::log($order, __('Payment successful via Fiserv Checkout.', 'fisrv-checkout-for-woocommerce'));

		if (check_admin_referer(self::FISRV_NONCE)) {
			$generic_gateway = new WC_Fisrv_Payment_Generic();

			if (sanitize_text_field($_GET['transaction_approved'])) {
				if ($generic_gateway->get_option('autocomplete') === 'no') {
					$order->update_status('wc-processing', __('Order has completed with auto-completion', 'fisrv-checkout-for-woocommerce'));
					return;
				}

				$has_completed = $order->payment_complete();
				if ($has_completed) {
					$order->update_status('wc-completed', __('Order has completed with auto-completion', 'fisrv-checkout-for-woocommerce'));
					WC_Fisrv_Logger::log($order, __('Order auto-completed.', 'fisrv-checkout-for-woocommerce'));
				}
			}
		}
	}

	/**
	 * Inititalize configuraiton parameters of fisrv SDK.
	 */
	public static function init_api_credentials(WC_Fisrv_Payment_Generic $generic_gateway, string $clientClass = 'Fisrv\Checkout\CheckoutClient'): array
	{
		if (!($generic_gateway instanceof WC_Fisrv_Payment_Gateway)) {
			throw new Exception('Could not retrieve payment settings');
		}

		$plugin_data = get_plugin_data(__DIR__ . '\..\fisrv-checkout-for-woocommerce.php');
		$plugin_version = $plugin_data['Version'];

		return [
			'user' => 'WooCommercePlugin/' . $plugin_version,
			'is_prod' => ($generic_gateway->get_option('is_prod') === 'yes') ? true : false,
			'api_key' => $generic_gateway->get_option('api_key'),
			'api_secret' => $generic_gateway->get_option('api_secret'),
			'store_id' => $generic_gateway->get_option('store_id'),
		];
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
			$generic_gateway = new WC_Fisrv_Payment_Generic();
			self::$client = new CheckoutClient(self::init_api_credentials($generic_gateway));

			$request = self::$client->createBasicCheckoutRequest(0, '', '');
			$request = self::pass_checkout_data($request, $order, $method);
			$request = self::pass_billing_data($request, $order);
			$request = self::pass_basket($request, $order);
			// $request = self::pass_transaction_type($generic_gateway, $request);
			// $request = self::handle_token_transaction($generic_gateway, $request, $order);

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

	private static function pass_transaction_type(WC_Fisrv_Payment_Generic $generic_gateway, CheckoutClientRequest $req)
	{
		$req->transactionType = TransactionType::tryFrom($generic_gateway->get_option('transaction_type')) ?? TransactionType::SALE;
		$req->transactionType = TransactionType::SALE;
		return $req;
	}

	public static function refund_checkout(WC_Fisrv_Payment_Generic $generic_gateway, WC_Order $order, $amount): PaymentsClientResponse
	{
		self::$client = new CheckoutClient(self::init_api_credentials($generic_gateway));
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
		$paymentsClient = new PaymentsClient(self::init_api_credentials($gateway, 'Fisrv\Payments\PaymentsClient'));

		if (!($paymentsClient instanceof PaymentsClient)) {
			return [
				'status' => 500,
				'message' => "Failed to create client",
			];
		}

		$report = $paymentsClient->reportHealthCheck(true);

		if ($report->httpCode != 200) {
			$message = $report->error->message;
			WC_Fisrv_Logger::generic_log('API health check reported following error response: ' . $message);
			WC_Fisrv_Logger::generic_log('Verbose report log: ' . json_encode($report->requestLog));
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

		$selectedFailurePage = $order->get_checkout_payment_url();
		$gateway = WC()->payment_gateways()->payment_gateways()[$order->get_payment_method()];

		if ($gateway instanceof WC_Fisrv_Payment_Gateway && $gateway->get_option('fail_page') === 'cart') {
			$selectedFailurePage = $order->get_edit_order_url();
		}

		if ($gateway instanceof WC_Fisrv_Payment_Gateway && $gateway->get_option('fail_page') === 'cart') {
			$selectedFailurePage = home_url();
		}

		$req->checkoutSettings->redirectBackUrls->failureUrl = add_query_arg(
			array(
				'_wpnonce' => $nonce,
				'transaction_approved' => false,
				'wc_order_id' => $order->get_id(),
			),
			$selectedFailurePage
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

	private static function handle_token_transaction(WC_Fisrv_Payment_Generic $generic_gateway, CheckoutClientRequest $req, WC_Order $order)
	{
		$tokens_enabled = $generic_gateway->get_option('enable_tokens');

		if (!$tokens_enabled) {
			return $req;
		}

		WC_Fisrv_Logger::generic_log('Attempting token handling');

		$wp_user_id = $order->get_user_id();

		if ($wp_user_id === '' || is_null($wp_user_id)) {
			WC_Fisrv_Logger::generic_log('Token: No user logged in');
			return $req;
		}

		$token = get_user_meta($wp_user_id, '_fisrv_plugin_card_token', true);

		if ($token === '' || is_null($token)) {
			$token = wp_create_nonce();
			WC_Fisrv_Logger::generic_log("Token: Storing token $token on user $wp_user_id");
			add_user_meta($wp_user_id, '_fisrv_plugin_card_token', $token);

			$req->paymentMethodDetails->cards->createToken->toBeUsedFor = ToBeUsedFor::UNSCHEDULED;
			$req->paymentMethodDetails->cards->createToken->customTokenValue = $token;

			return $req;
		}

		WC_Fisrv_Logger::generic_log("Token: Token was found and will be used for subsequent transaction");
		$req->paymentMethodDetails->cards->tokenBasedTransaction->value = $token;

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
