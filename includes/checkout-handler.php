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

    /**
     * Constuctor mounting the checkout logic and button UI injection.
     * Set origin as plugin in request header (useeg agent).
     */
    public function __construct($api_key, $api_secret, $store_id)
    {
        self::$domain = get_site_url();
        self::init_fiserv_sdk($api_key, $api_secret, $store_id);

        /** On init, active output buffer (to enable redirects) */
        // add_action('init', [$this, 'output_buffer']);

        /** Remove payment original Place Order button */
        //add_filter('woocommerce_order_button_html', '__return_false', 1);

        /** Remove payment method selection section */
        // add_filter('woocommerce_cart_needs_payment', '__return_false');

        /** Use CheckoutViewRenderer. Render Place Order button and fill out form fields */
        // add_action('woocommerce_after_order_notes', [CheckoutViewRenderer::class, 'render_checkout_button_as_button']);
        // add_filter('woocommerce_checkout_fields', [CheckoutViewRenderer::class, 'fill_out_fields']);
    }


    /**
     * Inititalize configuraiton parameters of Fiserv SDK.
     * @todo This is subject to change, since Config API will change in coming
     * versions. 
     */
    private function init_fiserv_sdk($api_key, $api_secret, $store_id): void
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
        $failureUrl = $order->get_checkout_payment_url();

        try {
            $req = new CreateCheckoutRequest(self::createCheckoutRequestParams);
            $req->merchantTransactionId = $order->get_id();
            $req->transactionAmount->total = $total;

            $req->checkoutSettings->redirectBackUrls->successUrl = $successUrl;
            $req->checkoutSettings->redirectBackUrls->failureUrl = $failureUrl . '&';
            $req->checkoutSettings->webHooksUrl = self::$domain . WebhookHandler::$webhook_endpoint . '/events?wc-order-id=' . $order->get_id();;

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
     * Parse concatenated order ID used by checkout solution (which is WC order ID + # + random UUID)
     * to just WC order id. (String before #). 
     * 
     * @param string $order_uuid Concatenated WC order ID and random UUIDv4 -> 114#ca375f32-f4ff-4ec7-a92a-15f594e1bf58
     * @return string Parsed WC order ID -> 114
     */
    private static function parse_order_uuid(string $order_uuid): string
    {
        return strtok($order_uuid, '#');
    }

    private static bool $requestFailed = false;

    /**
     * Getter for requestFailed flag
     * 
     * @return bool True if request has failed
     */
    public function get_request_failed(): bool
    {
        return self::$requestFailed;
    }
}
