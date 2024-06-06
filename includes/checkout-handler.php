<?php

namespace FiservWoocommercePlugin;

use Config;
use CreateCheckoutRequest;
use Exception;
use Fiserv\CheckoutSolution;
use Throwable;
use WebhookHandler;

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
            ]
        ]
    ];

    private static string $domain;
    private static bool $requestFailed = false;

    /**
     * Constuctor mounting the checkout logic and button UI injection.
     * Set origin as plugin in request header (useeg agent).
     */
    public function __construct()
    {
        self::$domain = get_site_url();

        /** On init, active output buffer (to enable redirects) */
        add_action('init', [$this, 'output_buffer']);

        /** Show transaction error message */
        add_action('woocommerce_before_checkout_form', [$this, 'maybe_failed_transaction'], 1);

        /** Remove payment original Place Order button */
        //add_filter('woocommerce_order_button_html', '__return_false', 1);

        /** Remove payment method selection section */
        // add_filter('woocommerce_cart_needs_payment', '__return_false');

        // add_action('woocommerce_after_order_notes', [CheckoutViewRenderer::class, 'render_checkout_button_as_button']);
        add_filter('woocommerce_checkout_fields', [$this, 'fill_out_fields']);
        add_action('woocommerce_thankyou', [$this, 'order_complete_callback']);
    }

    public static function remove_fields(): array
    {
        return [];
    }

    /**
     * Callback hook that gets called on checkout page. Check if query params
     * are present that indicate a failed transaction (failUrl from checkout solution).
     * If redirected from failed transaction, display error notice and set
     * order status accordingly.
     */
    public static function maybe_failed_transaction(): void
    {
        if (!isset($_GET['wc_order_id'])) {
            return;
        }

        $order_id = $_GET['wc_order_id'];
        $order = wc_get_order($order_id);
        $order->update_status('wc-pending', 'Retrying payment');

        if (!isset($_GET['code']) || !isset($_GET['message'])) {
            return;
        }

        add_action('woocommerce_checkout_fields', [self::class, 'remove_fields']);
        wc_add_notice('Payment has failed: ' . $_GET['message'], 'error');
        self::log('Payment has failed: ' . $_GET['message'] . ' -- ' . $_GET['code']);
    }

    private static function retrieve_order_from_page(): object | false
    {
        $order_key = $_GET['key'];
        $order_id = wc_get_order_id_by_order_key($order_key);
        $order = wc_get_order($order_id);

        if (!$order) {
            throw new Exception('Order ID ' . $order_id . ' not found');
        }

        return $order;
    }

    public function order_complete_callback(): void
    {
        $is_transaction_approved = $_GET['transaction_approved'];

        if ($is_transaction_approved) {
            $order = self::retrieve_order_from_page();
            $has_completed = $order->payment_complete();
            if ($has_completed) {
                $order->update_status('wc-completed', 'Order has completed');
                CheckoutHandler::log('Order ' . $order->get_id() . ' completed');
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
     * CC Sample Data
     * 5579346132831154
     * Japheth Massaro
     * 372
     * 04/29
     * 
     * INVALID
     * 4182917993774394
     */

    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     * Block handling requests via SDK. Possible SDK exceptions are caught
     * so that fail and loading state can be shown on view.
     * 
     * @throws Exception Error thrown from Fiserv SDK (Request Errors). Error is caught by setting 
     * returned checkout link to '#' (no redirect)
     * @return string URL of hosted payment page
     * 
     * @todo Currently, passing floats (to transaction amount) causes unexpected behaviour.
     * Until float parameters are fixed, the total is cast to integers (amount is floored).
     */
    public static function create_checkout_link(object $order): string
    {
        $total = intval($order->get_total());
        $successUrl = $order->get_checkout_order_received_url();
        $failureUrl = wc_get_page_permalink('checkout');
        $paymentUrl = $order->get_checkout_payment_url();

        try {
            $req = new CreateCheckoutRequest(self::createCheckoutRequestParams);
            $req->merchantTransactionId = $order->get_id();
            $req->transactionAmount->total = $total;

            $req->checkoutSettings->redirectBackUrls->successUrl = $successUrl . '&transaction_approved=true';
            $req->checkoutSettings->redirectBackUrls->failureUrl = $failureUrl . '?wc_order_id=' . $order->get_id() . '&';
            $req->checkoutSettings->webHooksUrl = self::$domain . WebhookHandler::$webhook_endpoint . '/events?wc_order_id=' . $order->get_id();;

            $res = CheckoutSolution::postCheckouts($req);

            $checkout_id = $res->checkout->checkoutId;
            $checkout_link = $res->checkout->redirectionUrl;

            $order->update_meta_data('_fiserv_plugin_checkout_link', $checkout_link);
            $order->update_meta_data('_fiserv_plugin_checkout_id', $checkout_id);
            $order->update_meta_data('_fiserv_plugin_trace_id', $checkout_id);

            return $checkout_link;
        } catch (Throwable $th) {
            self::$requestFailed = true;
            throw $th;
        }
    }


    /**
     * Getter for requestFailed flag
     * 
     * @return bool True if request has failed
     */
    public function get_request_failed(): bool
    {
        return self::$requestFailed;
    }

    const WC_LOG_SOURCE = 'woocommerce-gateway-stripe';

    /**
     * Log some message to WC admin page
     * 
     * @param string $message Message to log
     */
    public static function log(string $message): void
    {
        wc_get_logger()->notice($message, ['source' => self::WC_LOG_SOURCE]);
    }
}
