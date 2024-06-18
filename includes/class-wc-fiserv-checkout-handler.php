<?php

use Fiserv\Checkout\CheckoutClient;
use Fiserv\Models\CheckoutClientRequest;
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
     * @param array    $available_gateways All available gateways.
     *
     * @return array    Passed (and modified) function params
     */
    public static function retry_payment($order, $order_button_text, $available_gateways): array
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
     * Retrieve a WC order from a given order key. The order key may be passed as
     * query parameters.
     * 
     * @param string $order_key WP order key to retrieve order object from
     * @return object Order from order key
     * @return false If corresponding order does not exist 
     */
    public static function retrieve_order_from_key(string $order_key): object | false
    {
        $order_id = wc_get_order_id_by_order_key($order_key);
        $order = wc_get_order($order_id);

        if (!$order) {
            throw new Exception(esc_html('Order ID ' . $order_id . ' not found'));
        }

        return $order;
    }

    /**
     * Verify that origin of incoming requests (such as passed query parameters)
     * are from trusted source (from plugin itself). This is done by checking WP nonces which is set
     * before checkout creation.
     * 
     * @param object $order WC order object
     */
    private static function verify_nonce(object $order): void
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
     */
    public static function fill_out_fields($fields)
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
     */
    public static function init_fiserv_sdk($api_key, $api_secret, $store_id): void
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
     * Get cart data from WC stub to be served to Checkout Solution.
     * Block handling requests via SDK. Possible SDK exceptions are caught
     * so that fail and loading state can be shown on view.
     * 
     * @throws Exception Error thrown from Fiserv SDK (Request Errors). Error is caught by setting 
     * returned checkout link to '#' (no redirect)
     * @return string URL of hosted payment page
     * 
     */
    public static function create_checkout_link(object $order): string
    {
        try {
            $request = self::$client->createBasicCheckoutRequest(0, '', '');

            $request = self::pass_checkout_data($request, $order);
            $request = self::pass_billing_data($request, $order);

            $response = self::$client->createCheckout($request);

            $checkout_id = $response->checkout->checkoutId;
            $checkout_link = $response->checkout->redirectionUrl;
            $trace_id = $response->traceId;

            $order->update_meta_data('_fiserv_plugin_checkout_link', $checkout_link);
            $order->update_meta_data('_fiserv_plugin_cache_retry', 0);
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
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     * 
     * @param CheckoutClientRequest $req    Request object to modify
     * @param object $order                 Woocommerce order object
     * @return CheckoutClientRequest        Modified request object
     */
    private static function pass_checkout_data(CheckoutClientRequest $req, object $order): CheckoutClientRequest
    {
        $wp_language = str_replace('-', '_', get_bloginfo('language'));
        $locale = Locale::tryFrom($wp_language);

        if (substr($wp_language, 0, 2) == 'de') {
            $locale = Locale::de_DE;
        }

        $req->checkoutSettings->locale = $locale ?? 'en_GB';
        $total = $order->get_total();

        $wc_checkout_link = wc_get_page_permalink('checkout');
        $successUrl = $order->get_checkout_order_received_url();
        $failureUrl = $order->get_checkout_payment_url();

        $req->merchantTransactionId = $order->get_id();
        $req->transactionAmount->total = $total;

        $nonce = wp_create_nonce(self::$IPG_NONCE);

        $req->checkoutSettings->redirectBackUrls->successUrl = add_query_arg([
            '_wpnonce' => $nonce,
            'transaction_approved' => true,
        ], $successUrl);

        $req->checkoutSettings->redirectBackUrls->failureUrl = add_query_arg([
            '_wpnonce' => $nonce,
            'transaction_failed' => true,
            'wc_order_id' => $order->get_id(),
        ], $failureUrl);

        /** Append ampersand to allow checkout solution to append query params */
        $req->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        $req->checkoutSettings->webHooksUrl = add_query_arg([
            '_wpnonce' => $nonce,
            'wc_order_id' => $order->get_id(),
        ], WC_Fiserv_Webhook_Handler::$webhook_endpoint . '/events');
        $req->checkoutSettings->preSelectedPaymentMethod = PreSelectedPaymentMethod::CARDS;

        return $req;
    }

    /**
     * Pass billing data from WC billing form to request object of checkout
     * 
     * @param CheckoutClientRequest $req    Request object to modify
     * @param object $order                 Woocommerce order object
     * @return CheckoutClientRequest        Modified request object
     */
    private static function pass_billing_data(CheckoutClientRequest $req, object $order): CheckoutClientRequest
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
