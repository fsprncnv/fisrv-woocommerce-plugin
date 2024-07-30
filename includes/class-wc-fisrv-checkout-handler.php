<?php

use Fisrv\Checkout\CheckoutClient;
use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\Currency;
use Fisrv\Models\LineItem;
use Fisrv\Models\Locale;
use Fisrv\Models\PreSelectedPaymentMethod;

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
	 * @param WC_Order $order              The order that is being paid for.
	 * @param string   $order_button_text  The text for the submit button.
	 * @param array<WC_Payment_Gateway>    $available_gateways All available gateways.
	 *
	 * @return array<string, mixed>    Passed (and modified) function params
	 */
	public static function retry_payment(WC_Order $order, string $order_button_text, array $available_gateways): array
	{
		self::verify_nonce($order);

		$order_button_text = esc_html__('Retry payment', 'fisrv-checkout-for-woocommerce');
		$order->update_status('wc-pending', esc_html__('Retrying payment', 'fisrv-checkout-for-woocommerce'));

		$fisrv_error_message = sanitize_text_field(wp_unslash($_GET['message'])) ?? esc_html__('Internal error', 'fisrv-checkout-for-woocommerce');
		$fisrv_error_code = sanitize_text_field(wp_unslash($_GET['code'])) ?? esc_html__('No code provided', 'fisrv-checkout-for-woocommerce');

		/* translators: %s: Fisrv error message */
		wc_add_notice(sprintf(esc_html__('Payment has failed: %s', 'fisrv-checkout-for-woocommerce'), $fisrv_error_message), 'error');
		wc_print_notices();
		/* translators: %1$s: Fisrv error message %2$s: Fisrv error message */
		WC_Fisrv_Logger::error($order, sprintf(esc_html__('Payment validation failed, retrying on checkout page: %1$s -- %2$s', 'fisrv-checkout-for-woocommerce'), $fisrv_error_message, $fisrv_error_code));

		return array(
			'order' => $order,
			'available_gateways' => $available_gateways,
			'order_button_text' => $order_button_text,
		);
	}

	/**
	 * Verify that origin of incoming requests (such as passed query parameters)
	 * are from trusted source (from plugin itself). This is done by checking WP nonces which is set
	 * before checkout creation.
	 *
	 * @param WC_Order $order WC order object
	 */
	private static function verify_nonce(WC_Order $order): void
	{
		if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], self::FISRV_NONCE)) {
			WC_Fisrv_Logger::error($order, __('Security check: Nonce was invalid when checkout redirected back to failure URL.', 'fisrv-checkout-for-woocommerce'));
			die();
		}
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

		self::verify_nonce($order);

		if (sanitize_text_field($_GET['transaction_approved'])) {
			$has_completed = $order->payment_complete();
			if ($has_completed) {
				$order->update_status('wc-completed', __('Order has completed', 'fisrv-checkout-for-woocommerce'));
				WC_Fisrv_Logger::log($order, __('Order completed with card payment.', 'fisrv-checkout-for-woocommerce'));
			}
		}
	}

	/**
	 * Inititalize configuraiton parameters of fisrv SDK.
	 *
	 * @param string $api_key API key
	 * @param string $api_secret API secret
	 * @param string $store_id Store ID
	 */
	public static function init_fisrv_sdk(string $api_key, string $api_secret, string $store_id): void
	{
		$plugin_data = get_plugin_data(__DIR__ . '..//fisrv-checkout-for-woocommerce.php');
		$plugin_version = $plugin_data['Version'];

		self::$client = new CheckoutClient(
			array(
				'user' => 'WooCommercePlugin/' . $plugin_version,
				'is_prod' => false,
				'api_key' => $api_key,
				'api_secret' => $api_secret,
				'store_id' => $store_id,
			)
		);
	}

	/**
	 * Create a checkout link
	 *
	 * @param WC_Order $order WC order
	 * @param PreSelectedPaymentMethod $method Selected payment method
	 *
	 * @return string URL of hosted payment page
	 *
	 * @throws Exception Error thrown from fisrv SDK (Request Errors). Error is caught by setting
	 * returned checkout link to '#' (no redirect)
	 */
	public static function create_checkout_link(WC_Order $order, PreSelectedPaymentMethod $method, WC_Fisrv_Payment_Gateway $gateway): string
	{
		try {
			WC_Fisrv_Checkout_Handler::init_fisrv_sdk(
				$gateway->get_option('api_key'),
				$gateway->get_option('api_secret'),
				$gateway->get_option('store_id'),
			);

			/** @todo This is weird */
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
			$order->add_order_note(sprintf(esc_html__('Fisrv checkout link %1$s created with checkout ID %2$s and trace ID %3$s.', 'fisrv-checkout-for-woocommerce'), $checkout_link, $checkout_id, $trace_id));

			return $checkout_link;
		} catch (Throwable $th) {
			if (str_starts_with($th->getMessage(), '401')) {
				/* translators: %s: Method value */
				throw new Exception(sprintf(esc_html__('Payment method %s failed. Please check on settings page if API credentials are set correctly.', 'fisrv-checkout-for-woocommerce'), $method->value));
			}

			throw $th;
		}
	}

	/**
	 * Pass line items from WC to checkout
	 *
	 * @param CheckoutClientRequest $req    Request object to modify
	 * @param WC_Order $order               WooCommerce order object
	 * @return CheckoutClientRequest        Modified request object
	 */
	private static function pass_basket(CheckoutClientRequest $req, WC_Order $order): CheckoutClientRequest
	{
		$wc_items = $order->get_items();

		foreach ($wc_items as $item) {
			$item_data = $item->get_data();

			$req->order->basket->lineItems[] = new LineItem(
				array(
					'itemIdentifier' => $item->get_id(),
					'name' => $item->get_name(),
					'price' => $item_data['total'],
					'quantity' => $item->get_quantity(),
					'shippingCost' => 0,
					'valueAddedTax' => 0,
					'miscellaneousFee' => 0,
					'total' => $item_data['total'],
				)
			);
		}

		return $req;
	}

	/**
	 * Pass checkout data (totals, redirects, language etc.) to request object of checkout
	 *
	 * @param CheckoutClientRequest $req    Request object to modify
	 * @param WC_Order $order               WooCommerce order object
	 * @return CheckoutClientRequest        Modified request object
	 */
	private static function pass_checkout_data(CheckoutClientRequest $req, WC_Order $order, PreSelectedPaymentMethod $method): CheckoutClientRequest
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

		/** Order totals */
		$req->transactionAmount->total = floatval($order->get_total());
		$req->transactionAmount->components->subtotal = floatval($order->get_subtotal());
		$req->transactionAmount->components->vatAmount = floatval($order->get_total_tax());
		$req->transactionAmount->components->shipping = floatval($order->get_shipping_total());

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
		$req->checkoutSettings->preSelectedPaymentMethod = $method;

		return $req;
	}

	/**
	 * Pass billing data from WC billing form to request object of checkout
	 *
	 * @param CheckoutClientRequest $req    Request object to modify
	 * @param WC_Order $order               WooCommerce order object
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
