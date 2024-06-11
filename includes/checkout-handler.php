<?php

namespace FiservWoocommercePlugin;

use Config;
use CreateCheckoutRequest;
use Exception;
use Fiserv\CheckoutSolution;
use Throwable;
use WCLogger;
use WebhookHandler;

/**
 * Valid
 * 5579346132831154
 * 
 * Invalid
 * 4182917993774394
 */

/**
 * This class handles logic involving the checkout
 */
class CheckoutHandler
{
    /**
     * Default params to be passed as request body of post checkout
     */
    public const createCheckoutRequestParams = [
        'transactionOrigin' => 'ECOM',
        'transactionType' => 'SALE',
        'transactionAmount' => [
            'total' => 0,
            'currency' => 'EUR'
        ],
        'checkoutSettings' => [
            'locale' => 'en_GB',
            'webHooksUrl' => 'https://nonce.com',
            'redirectBackUrls' => [
                'successUrl' => 'https://nonce.com',
                'failureUrl' => 'https://nonce.com'
            ]
        ],
        'paymentMethodDetails' => [
            'cards' => [
                'createToken' => [
                    'toBeUsedFor' => 'UNSCHEDULED',
                ],
            ],
        ],
        'storeId' => 'NULL',
        'order' => [
            'orderDetails' => [
                'purchaseOrderNumber' => 0,
            ],
            'billing' => [
                'person' => [],
                'contact' => [],
                'address' => [],
            ]
        ]
    ];

    private static string $domain;
    private static bool $REQUEST_FAILED = false;
    private static string $IPG_NONCE = 'ipg-nonce';

    /**
     * Constuctor mounting the checkout logic and button UI injection.
     * Set origin as plugin in request header (useeg agent).
     */
    public function __construct()
    {
        self::$domain = get_site_url();

        /** On init, active output buffer (to enable redirects) */
        add_action('init', [$this, 'output_buffer']);

        /** Fill out fields with default values for testing */
        add_filter('woocommerce_checkout_fields', [$this, 'fill_out_fields']);

        /** Callback on failed payment, retry flow */
        add_action('before_woocommerce_pay_form', [$this, 'retry_payment'], 1, 3);

        /** Callback on completed order */
        add_action('woocommerce_thankyou', [$this, 'order_complete_callback'], 1, 1);
    }

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
            WCLogger::error($order, 'Security check: Nonce was invalid when checkout redirected back to failure URL.');
            die();
        }

        $order_button_text = 'Retry payment';
        $order->update_status('wc-pending', 'Retrying payment');

        $ipg_message = $_GET['message'] ?? "Internal error";
        $ipg_code = $_GET['code'] ?? "No code provided";

        wc_add_notice('Payment has failed: ' . $ipg_message, 'error');
        wc_print_notices();
        WCLogger::error($order, 'Payment validation failed, retrying on checkout page: ' . $ipg_message . ' -- ' . $ipg_code);

        return [
            'order'              => $order,
            'available_gateways' => $available_gateways,
            'order_button_text'  => $order_button_text,
        ];
    }

    /**
     * Callback hook that gets called on checkout page. Check if query params
     * are present that indicate a failed transaction (failUrl from checkout solution).
     * If redirected from failed transaction, display error notice and set
     * order status accordingly.
     */
    public static function maybe_failed_transaction(): void
    {
        $order_id = $_GET['wc_order_id'];
        $order = wc_get_order($order_id);
        $order->update_status('wc-pending', 'Retrying payment');

        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], self::$IPG_NONCE)) {
            WCLogger::error($order, 'Security check: Nonce was invalid when checkout redirected back to failure URL.');
            die();
        }

        if (!isset($_GET['wc_order_id'])) {
            return;
        }

        if (!isset($_GET['code']) || !isset($_GET['message'])) {
            return;
        }

        /** Remove billing section when on retry payment page */
        add_action('woocommerce_checkout_fields', function () {
            return [];
        });

        wc_add_notice('Payment has failed: ' . $_GET['message'], 'error');
        WCLogger::error($order, 'Payment validation failed, retrying on checkout page: ' . $_GET['message'] . ' -- ' . $_GET['code']);
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
            WCLogger::error($order, 'Security check: Nonce was invalid when checkout redirected back to failure URL.');
            die();
        }
    }

    /**
     * This is called when order is complete on thank you page.
     * Set order status and payment to completed
     * 
     * @param string $order_id WC order ID
     */
    public function order_complete_callback(string $order_id): void
    {
        $order = wc_get_order($order_id);
        self::verify_nonce($order);

        $is_transaction_approved = $_GET['transaction_approved'];

        if ($is_transaction_approved) {
            $has_completed = $order->payment_complete();
            if ($has_completed) {
                $order->update_status('wc-completed', 'Order has completed');
                WCLogger::log($order, 'Order completed with card payment.');
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
        Config::$ORIGIN = 'Woocommerce Plugin';
        Config::$API_KEY = $api_key;
        Config::$API_SECRET = $api_secret;
        Config::$STORE_ID = $store_id;
    }

    /**
     * This method turns on output buffering. See references for more info on output buffering.
     * This has to be actived to enable redirection to external locations, for instance, the hosted payment page.
     * @see ob_start()
     */
    function output_buffer()
    {
        ob_start();
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
            $req = new CreateCheckoutRequest(self::createCheckoutRequestParams);
            $req = self::pass_checkout_data($req, $order);
            $req = self::pass_billing_data($req, $order);

            $res = CheckoutSolution::postCheckouts($req);

            $checkout_id = $res->checkout->checkoutId;
            $checkout_link = $res->checkout->redirectionUrl;
            $trace_id = $res->traceId;

            $order->update_meta_data('_fiserv_plugin_checkout_link', $checkout_link);
            $order->update_meta_data('_fiserv_plugin_cache_retry', 0);
            $order->update_meta_data('_fiserv_plugin_checkout_id', $checkout_id);
            $order->update_meta_data('_fiserv_plugin_trace_id', $checkout_id);
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
     * @param CreateCheckoutRequest $req    Request object to modify
     * @param object $order                 Woocommerce order object
     * @return CreateCheckoutRequest        Modified request object
     */
    private static function pass_checkout_data(CreateCheckoutRequest $req, object $order): CreateCheckoutRequest
    {
        $wp_language = str_replace('-', '_', get_bloginfo('language'));
        $locale = 'en_GB';

        $supported_locales = [
            'en_GB', 'en_US', 'de_DE', 'nl_NL'
        ];

        if (substr($wp_language, 0, 2) == 'de') {
            $locale = 'de_DE';
        }

        if (in_array($wp_language, $supported_locales)) {
            $locale = $wp_language;
        }

        // @todo FLOAT BUG
        $req->checkoutSettings->locale = $locale;
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
        ], WebhookHandler::$webhook_endpoint . '/events');

        // $req->checkoutSettings->preSelectedPaymentMethod = 'cards';
        return $req;
    }

    /**
     * Pass billing data from WC billing form to request object of checkout
     * 
     * @param CreateCheckoutRequest $req    Request object to modify
     * @param object $order                 Woocommerce order object
     * @return CreateCheckoutRequest        Modified request object
     */
    private static function pass_billing_data(CreateCheckoutRequest $req, object $order): CreateCheckoutRequest
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
}
