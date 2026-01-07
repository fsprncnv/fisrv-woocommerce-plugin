<?php

if (!defined('ABSPATH')) {
    exit;
}

use Fisrv\Checkout\CheckoutClient;
use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\Currency;
use Fisrv\Models\LineItem;
use Fisrv\Models\Locale;
use Fisrv\Models\PaymentsClientRequest;
use Fisrv\Models\PaymentsClientResponse;
use Fisrv\Models\PreSelectedPaymentMethod;
use Fisrv\Models\ToBeUsedFor;
use Fisrv\Payments\PaymentsClient;

/**
 * Class that handles creation of redirection link
 * of checkout solution and further checkout related data handling.
 *
 * @package  WooCommerce
 * @category Payment Gateways
 * @author   fiserv
 * @since    1.0.0
 */
final class WC_Fiserv_Checkout_Handler
{
    /**
     * HTTP client used by Fiserv PHP Client
     *
     * @var CheckoutClient | null
     */
    private static ?CheckoutClient $client = null;

    /**
     * Inititalize configuraiton parameters of fiserv SDK.
     *
     * @param WC_Fiserv_Payment_Gateway $generic_gateway WC payment gateway object to retrieve stored credentials
     */
    public static function init_api_credentials(WC_Fiserv_Payment_Gateway $generic_gateway): array
    {
        if (!($generic_gateway instanceof WC_Fiserv_Payment_Gateway)) {
            throw new Exception('Could not retrieve payment settings');
        }

        $plugin_data = get_plugin_data(__DIR__ . '/../fiserv-checkout-for-woocommerce.php');
        $plugin_version = $plugin_data['Version'] ?? FISRV_PLUGIN_VERSION;

        return array(
            'user' => 'WooCommercePlugin/' . $plugin_version,
            'is_prod' => ($generic_gateway->get_option('is_prod') === 'yes') ? true : false,
            'api_key' => $generic_gateway->get_option('api_key'),
            'api_secret' => $generic_gateway->get_option('api_secret'),
            'store_id' => $generic_gateway->get_option('store_id'),
        );
    }

    /**
     * Create a checkout link
     *
     * @param WC_Order                      $order  WC order
     * @param PreSelectedPaymentMethod|null $method Selected payment method
     *
     * @return string URL of hosted payment page
     *
     * @throws Exception Error thrown from fiserv SDK (Request Errors). Error is caught by setting
     * returned checkout link to '#' (no redirect)
     */
    public static function create_checkout_link(WC_Order $order, ?PreSelectedPaymentMethod $method): string
    {
        try {
            $generic_gateway = new WC_Fiserv_Payment_Generic();
            $credentials = self::init_api_credentials($generic_gateway);
            self::$client = new CheckoutClient($credentials);

            $request = self::$client->createBasicCheckoutRequest(0, '', '');
            $request = self::pass_checkout_data($request, $order, $method);
            $request = self::pass_billing_data($request, $order);
            $request = self::pass_basket($request, $order);
            // $request = self::handle_token_transaction($generic_gateway, $request, $order);

            if (isset($credentials['store_id'])) {
                $request->storeId = $credentials['store_id'];
            }

            WC_Fiserv_Logger::generic_log('Creating checkout page request...');
            WC_Fiserv_Logger::generic_log($request);
            $response = self::$client->createCheckout($request);

            $checkout_id = $response->checkout->checkoutId;
            $checkout_link = $response->checkout->redirectionUrl;
            $trace_id = $response->traceId;

            $order->update_meta_data('_fiserv_plugin_checkout_link', $checkout_link);
            $order->update_meta_data('_fiserv_plugin_checkout_id', $checkout_id);
            $order->update_meta_data('_fiserv_plugin_trace_id', $response->traceId);
            $order->save_meta_data();
            /* translators: %1$s: Checkout link %2$s: Checkout ID %3$s: Checkout trace ID */
            $order->add_order_note(sprintf(esc_html__('Fiserv checkout link %1$s created with checkout ID %2$s and trace ID %3$s.', 'fiserv-checkout-for-woocommerce'), $checkout_link, $checkout_id, $trace_id));

            return $checkout_link;
        } catch (Throwable $th) {
            if (str_starts_with($th->getMessage(), '401')) {
                /* translators: %s: Method value */
                throw new Exception(esc_html__('Payment method failed. Please check on settings page if API credentials are set correctly.', 'fiserv-checkout-for-woocommerce'));
            }
            throw $th;
        }
    }


    /**
     * Refund checkout via IPG Rest API. Retrieve order by checkout ID.
     *
     * @param  WC_Order $order  WooCommerce order object
     * @param  mixed    $amount Amount to refund
     * @return Fisrv\Models\PaymentsClientResponse Fiserv client response object
     */
    public static function refund_checkout(WC_Order $order, $amount): PaymentsClientResponse
    {
        $generic_gateway = new WC_Fiserv_Payment_Generic();
        self::$client = new CheckoutClient(self::init_api_credentials($generic_gateway));
        $response = self::$client->refundCheckout(
            new PaymentsClientRequest(
                array(
                    'transactionAmount' => array(
                        'total' => $amount,
                        'currency' => get_woocommerce_currency(),
                    ),
                )
            ),
            $order->get_meta('_fiserv_plugin_checkout_id')
        );
        WC_Fiserv_Logger::generic_log('Refund response: ' . $response);
        return $response;
    }

    /**
     * Get health report for API health status checker
     *
     * @param  array $credentials API key, secret and store ID
     * @return array JSON parsable response as array
     */
    public static function get_health_report(array $credentials): array
    {
        $paymentsClient = new PaymentsClient($credentials);
        if (!($paymentsClient instanceof PaymentsClient)) {
            return [
                'status' => 500,
                'message' => 'Failed to create client',
            ];
        }
        $report = $paymentsClient->reportHealthCheck(true);
        if ($report->httpCode != 200) {
            if (isset($report->error->message)) {
                $message = $report->error->message;
                WC_Fiserv_Logger::generic_log('API health check reported following error response: ' . $message);
            } else {
                $message = __('Store is likely not set up correctly.', 'fiserv-checkout-for-woocommerce');
                WC_Fiserv_Logger::generic_log('Verbose report log: ' . json_decode($report->requestLog, true));
            }
            return [
                'status' => $report->httpCode,
                'message' => $message,
            ];
        }
        return [
            'status' => 'ok',
            'message' => "You're all set!",
        ];
    }

    public static function get_checkout_details($checkout_id): WP_REST_Response
    {
        if (is_null(self::$client)) {
            $gateway = WC()->payment_gateways()->payment_gateways()[Fisrv_Identifiers::GATEWAY_GENERIC->value];
            self::$client = new CheckoutClient(self::init_api_credentials($gateway));
        }
        ob_start();
        $response = ['status' => 'no data'];
        try {
            $report = self::$client->getCheckoutById($checkout_id);
            $response = [
                'status' => 'ok',
                'message' => wp_json_encode($report)
            ];
        } catch (\Throwable $th) {
            $response = [
                'status' => 'error',
                'message' => $th->getMessage(),
            ];
        }
        ob_end_clean();
        return rest_ensure_response($response);
    }

    /**
     * Pass line items from WC to checkout
     *
     * @param  CheckoutClientRequest $req   Request object to modify
     * @param  WC_Order              $order WooCommerce order object
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
                    'shippingCost' => $order->get_shipping_total(),
                    'valueAddedTax' => $order->get_taxes(),
                )
            );
        }

        return $req;
    }

    /**
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     *
     * @param  CheckoutClientRequest         $req    Request object to modify
     * @param  WC_Order                      $order  WooCommerce order object
     * @param  PreSelectedPaymentMethod<null $method Selected payment method
     * @return CheckoutClientRequest        Modified request object
     */
    private static function pass_checkout_data(CheckoutClientRequest $req, WC_Order $order, ?PreSelectedPaymentMethod $method): CheckoutClientRequest
    {
        /**
         * Locale 
         */
        $wp_language = str_replace('-', '_', get_bloginfo('language'));
        $locale = Locale::tryFrom($wp_language);
        if (substr($wp_language, 0, 2) == 'de') {
            $locale = Locale::de_DE;
        }
        $req->checkoutSettings->locale = $locale ?? Locale::en_GB;
        /**
         * Currency 
         */
        $wp_currency = get_woocommerce_currency();
        $req->transactionAmount->currency = Currency::tryFrom($wp_currency) ?? Currency::EUR;
        /**
         * WC order numbers, IDs 
         */
        $req->order->orderDetails->purchaseOrderNumber = strval($order->get_id());
        /**
         * Order totals 
         */
        $req->transactionAmount->total = $order->get_total();
        $req->transactionAmount->components->subtotal = $order->get_subtotal();
        $req->transactionAmount->components->vatAmount = $order->get_total_tax();
        $req->transactionAmount->components->shipping = (float) wc_format_decimal($order->get_shipping_total(), 2);
        WC_Fiserv_Logger::log(
            $order,
            'Requesting transaction in the amount of : ' . $req->transactionAmount->currency->value . ' ' . $req->transactionAmount->total
        );
        /**
         * Redirect URLs 
         */
        $nonce = wp_create_nonce(Fisrv_Identifiers::FISRV_NONCE->value);
        $req->checkoutSettings->redirectBackUrls->successUrl = add_query_arg(
            array(
                '_wpnonce' => $nonce,
                'transaction_approved' => 'true',
            ),
            $order->get_checkout_order_received_url()
        );
        $selectedFailurePage = $order->get_checkout_payment_url();
        $gateway = WC()->payment_gateways()->payment_gateways()[Fisrv_Identifiers::GATEWAY_GENERIC->value];
        if ($gateway instanceof WC_Fiserv_Payment_Gateway && $gateway->get_option('fail_page') === 'cart') {
            $selectedFailurePage = wc_get_cart_url();
        }
        $req->checkoutSettings->redirectBackUrls->failureUrl = add_query_arg(
            array(
                '_wpnonce' => $nonce,
                'transaction_approved' => 'false',
                'wc_order_id' => $order->get_id(),
            ),
            $selectedFailurePage
        );
        // Append ampersand to allow checkout solution to append query params 
        $req->checkoutSettings->redirectBackUrls->failureUrl .= '&';
        $req->checkoutSettings->webHooksUrl = add_query_arg(
            array(
                '_sign' => base64_encode($order->get_id()),
                'wc_order_id' => $order->get_id(),
            ),
            get_rest_url(null, WC_Fiserv_Rest_Routes::$plugin_rest_path . '/events')
        );
        if ($order->get_payment_method() !== Fisrv_Identifiers::GATEWAY_GENERIC->value) {
            $req->checkoutSettings->preSelectedPaymentMethod = $method;
        }
        // Remove create token object since plugin doesnt intend support for tokens
        unset($req->paymentMethodDetails->cards->createToken);
        return $req;
    }

    /**
     * Handle token transactions. This feature is on hold currently.
     *
     * @param  WC_Fiserv_Payment_Generic          $generic_gateway
     * @param  Fisrv\Models\CheckoutClientRequest $req
     * @param  WC_Order                           $order
     * @return CheckoutClientRequest
     */
    private static function handle_token_transaction(WC_Fiserv_Payment_Generic $generic_gateway, CheckoutClientRequest $req, WC_Order $order)
    {
        $tokens_enabled = $generic_gateway->get_option('enable_tokens');

        if (!$tokens_enabled) {
            return $req;
        }

        WC_Fiserv_Logger::generic_log('Attempting token handling');

        $wp_user_id = $order->get_user_id();

        if ($wp_user_id === '' || is_null($wp_user_id)) {
            WC_Fiserv_Logger::generic_log('Token: No user logged in');
            return $req;
        }

        $token = get_user_meta($wp_user_id, '_fiserv_plugin_card_token', true);

        if ($token === '' || is_null($token)) {
            $token = wp_create_nonce();
            WC_Fiserv_Logger::generic_log("Token: Storing token $token on user $wp_user_id");
            add_user_meta($wp_user_id, '_fiserv_plugin_card_token', $token);

            $req->paymentMethodDetails->cards->createToken->toBeUsedFor = ToBeUsedFor::UNSCHEDULED;
            $req->paymentMethodDetails->cards->createToken->customTokenValue = $token;

            return $req;
        }

        WC_Fiserv_Logger::generic_log('Token: Token was found and will be used for subsequent transaction');
        $req->paymentMethodDetails->cards->tokenBasedTransaction->value = $token;

        return $req;
    }

    /**
     * Pass billing data from WC billing form to request object of checkout
     *
     * @param  CheckoutClientRequest $req   Request object to modify
     * @param  WC_Order              $order WooCommerce order object
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
