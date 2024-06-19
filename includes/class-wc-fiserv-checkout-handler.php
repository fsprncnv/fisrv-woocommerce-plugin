<?php

use Fiserv\Checkout\CheckoutClient;
use Fiserv\Models\CheckoutClientRequest;
use Fiserv\Models\Currency;
use Fiserv\Models\LineItem;
use Fiserv\Models\Locale;
use Fiserv\Models\PreSelectedPaymentMethod;

/**
 * Class that handles creation of redirection link
 * of checkout solution and further checkout related data handling.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     Fiserv
 * @since      1.0.0
 */
final class WC_Fiserv_Checkout_Handler
{
    private static bool $REQUEST_FAILED = false;
    private static string $IPG_NONCE = 'ipg-nonce';
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
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], self::$IPG_NONCE)) {
            WC_Fiserv_Logger::error($order, 'Security check: Nonce was invalid when checkout redirected back to failure URL.');
            die();
        }

        $order_button_text = 'Retry payment';
        $order->update_status('wc-pending', 'Retrying payment');

        $ipg_message = $_GET['message'] ?? "Internal error";
        $ipg_code = $_GET['code'] ?? "No code provided";

        wc_add_notice('Payment has failed: ' . $ipg_message, 'error');
        wc_print_notices();
        WC_Fiserv_Logger::error($order, 'Payment validation failed, retrying on checkout page: ' . $ipg_message . ' -- ' . $ipg_code);

        return [
            'order'              => $order,
            'available_gateways' => $available_gateways,
            'order_button_text'  => $order_button_text,
        ];
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
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], self::$IPG_NONCE)) {
            WC_Fiserv_Logger::error($order, 'Security check: Nonce was invalid when checkout redirected back to failure URL.');
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

        $is_transaction_approved = $_GET['transaction_approved'];

        if ($is_transaction_approved) {
            $has_completed = $order->payment_complete();
            if ($has_completed) {
                $order->update_status('wc-completed', 'Order has completed');
                WC_Fiserv_Logger::log($order, 'Order completed with card payment.');
            }
        }
    }

    /**
     * Fill out text fields on billing section on checkout
     * with default values.
     * 
     * @param array<string, array<string, array<string, string>>> $fields
     * @return array<string, array<string, array<string, string>>> 
     */
    public static function fill_out_fields(array $fields): array
    {
        $fields['billing']['billing_first_name']['default'] = 'Eartha';
        $fields['billing']['billing_last_name']['default'] = 'Kitt';
        $fields['billing']['billing_address_1']['default'] = 'Oskar Schindler Strasse';
        $fields['billing']['billing_postcode']['default'] = '60359';
        $fields['billing']['billing_city']['default'] = 'Frankfurt';
        $fields['billing']['billing_phone']['default'] = '0162345678';
        $fields['billing']['billing_email']['default'] = 'earth.kitt@dev.com';
        return $fields;
    }

    /**
     * Inititalize configuraiton parameters of Fiserv SDK.
     * @todo This is subject to change, since Config API will change in coming
     * versions. 
     * 
     * @param string $api_key API key
     * @param string $api_secret API secret
     * @param string $store_id Store ID
     */
    public static function init_fiserv_sdk(string $api_key, string $api_secret, string $store_id): void
    {
        $plugin_data = get_plugin_data(__FILE__);
        $plugin_version = $plugin_data['Version'];

        self::$client = new CheckoutClient([
            'user' => 'WoocommercePlugin/' . $plugin_version,
            'is_prod' => false,
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'store_id' => $store_id,
        ]);
    }

    /**
     * Create a checkout link
     * 
     * @param WC_Order $order WC order
     * @param PreSelectedPaymentMethod $method Selected payment method 
     * 
     * @return string URL of hosted payment page
     * 
     * @throws Exception Error thrown from Fiserv SDK (Request Errors). Error is caught by setting 
     * returned checkout link to '#' (no redirect)
     */
    public static function create_checkout_link(WC_Order $order, PreSelectedPaymentMethod $method): string
    {
        try {
            /** @todo This is weird */
            $request = self::$client->createBasicCheckoutRequest(0, '', '');

            $request = self::pass_checkout_data($request, $order, $method);
            $request = self::pass_billing_data($request, $order);
            $request = self::pass_basket($request, $order);

            $response = self::$client->createCheckout($request);

            $checkout_id = $response->checkout->checkoutId;
            $checkout_link = $response->checkout->redirectionUrl;
            $trace_id = $response->traceId;

            $order->update_meta_data('_fiserv_plugin_checkout_link', $checkout_link);
            $order->update_meta_data('_fiserv_plugin_checkout_id', $checkout_id);
            $order->update_meta_data('_fiserv_plugin_trace_id', $response->traceId);
            $order->save_meta_data();
            $order->add_order_note('Fiserv checkout link ' . $checkout_link . ' created with checkout ID ' . $checkout_id . ' and trace ID ' . $trace_id . '.');

            return $checkout_link;
        } catch (Throwable $th) {
            self::$REQUEST_FAILED = true;
            throw $th;
        }
    }

    /**
     * Pass line items from WC to checkout
     * 
     * @param CheckoutClientRequest $req    Request object to modify
     * @param WC_Order $order               Woocommerce order object
     * @return CheckoutClientRequest        Modified request object
     */
    private static function pass_basket(CheckoutClientRequest $req, WC_Order $order): CheckoutClientRequest
    {
        $wc_items = $order->get_items();

        foreach ($wc_items as $item) {
            $item_data = $item->get_data();

            $req->order->basket->lineItems[] = new LineItem([
                'itemIdentifier'    => $item->get_id(),
                'name'              => $item->get_name(),
                'price'             => $item_data['total'],
                'quantity'          => $item->get_quantity(),
                'shippingCost'      => 0,
                'valueAddedTax'     => 0,
                'miscellaneousFee'  => 0,
                'total'             => $item_data['total'],
            ]);
        }
        return $req;
    }

    /**
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     * 
     * @param CheckoutClientRequest $req    Request object to modify
     * @param WC_Order $order               Woocommerce order object
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
        $nonce = wp_create_nonce(self::$IPG_NONCE);

        $req->checkoutSettings->redirectBackUrls->successUrl = add_query_arg([
            '_wpnonce' => $nonce,
            'transaction_approved' => true,
        ], $order->get_checkout_order_received_url());

        $req->checkoutSettings->redirectBackUrls->failureUrl = add_query_arg([
            '_wpnonce' => $nonce,
            'transaction_failed' => true,
            'wc_order_id' => $order->get_id(),
        ], $order->get_checkout_payment_url());

        /** Append ampersand to allow checkout solution to append query params */
        $req->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        $req->checkoutSettings->webHooksUrl = add_query_arg([
            '_wpnonce' => $nonce,
            'wc_order_id' => $order->get_id(),
        ], WC_Fiserv_Webhook_Handler::$webhook_endpoint . '/events');
        $req->checkoutSettings->preSelectedPaymentMethod = $method;

        return $req;
    }

    /**
     * Pass billing data from WC billing form to request object of checkout
     * 
     * @param CheckoutClientRequest $req    Request object to modify
     * @param WC_Order $order               Woocommerce order object
     * @return CheckoutClientRequest        Modified request object
     */
    private static function pass_billing_data(CheckoutClientRequest $req, WC_Order $order): CheckoutClientRequest
    {
        $req->order->billing->person->firstName     = $order->get_billing_first_name();
        $req->order->billing->person->lastName      = $order->get_billing_last_name();
        $req->order->billing->contact->email        = $order->get_billing_email();
        $req->order->billing->address->address1     = $order->get_billing_address_1();
        $req->order->billing->address->address2     = $order->get_billing_address_2();
        $req->order->billing->address->city         = $order->get_billing_city();
        $req->order->billing->address->country      = $order->get_billing_country();
        $req->order->billing->address->postalCode   = $order->get_billing_postcode();

        return $req;
    }

    /**
     * Getter for requestFailed flag
     * 
     * @return bool True if request has failed
     */
    public function get_request_failed(): bool
    {
        return self::$REQUEST_FAILED;
    }

    /**
     * Valid
     * 5579346132831154
     * 
     * Invalid
     * 4182917993774394
     */
}
