<?php

namespace FiservWoocommercePlugin;

use CheckoutViewRenderer;
use Config;
use Exception;
use Fiserv\CheckoutSolution;
use JSUtil;
use PaymentLinkRequestBody;
use PostCheckoutsResponse;
use Util;
use WebhookHandler;

/**
 * This class handles logic involving the checkout
 */
class CheckoutHandler
{
    /**
     * Default params to be passed as request body of post checkout
     */
    public const paymentLinksRequestContent = [
        'transactionOrigin' => 'ECOM',
        'transactionType' => 'SALE',
        'transactionAmount' => [
            'total' => 0,
            'currency' => 'EUR'
        ],
        'checkoutSettings' => [
            'locale' => 'en_GB',
            'webHooksUrl' => 'http://example.com/',
            'redirectBackUrls' => [
                'successUrl' => 'http://example.com/',
                'failureUrl' => 'http://example.com/'
            ]
        ],
        'paymentMethodDetails' => [
            'cards' => [
                'authenticationPreferences' => [
                    'challengeIndicator' => '01',
                    'skipTra' => false,
                ],
                'createToken' => [
                    'declineDuplicateToken' => false,
                    'reusable' => true,
                    'toBeUsedFor' => 'UNSCHEDULED',
                ],
                'tokenBasedTransaction' => ['transactionSequence' => 'FIRST']
            ],
            'sepaDirectDebit' => ['transactionSequenceType' => 'SINGLE']
        ],
        'merchantTransactionId' => 'AB-1234',
        'storeId' => '72305408',
    ];

    /**
     * URL of hosted payment page. 
     * Number sign if not yet set (loading state).
     */
    private static string $checkout_link = '#';

    /**
     * Reference to current sum total of cart. 
     * This is used to compare to newer values and detect changes of cart.
     */
    private static float $reference_total = 0;

    /**
     * Hostname of Wordpress page.
     * Reference used to navigate from external sources.
     */
    private static string $domain;

    /**
     * Constuctor mounting the checkout logic and button UI injection.
     * Set origin as plugin in request header (useeg agent).
     */
    public function __construct()
    {
        self::init_fiserv_sdk();
        self::$domain = get_site_url();

        /** On init, active output buffer (to enable redirects) */
        add_action('init', [$this, 'output_buffer']);

        /** Remove default action after pressing place order (not working)*/
        remove_all_actions('woocommerce_checkout_process');
        remove_all_actions('woocommerce_review_order_after_submit');
        remove_all_actions('woocommerce_review_order_before_submit');

        /** Remove payment original Place Order button */
        // add_filter('woocommerce_order_button_html', '__return_false', 1);

        /** Remove payment method selection section */
        // add_filter('woocommerce_cart_needs_payment', '__return_false');

        /** Use CheckoutViewRenderer. Render Place Order button and fill out form fields */
        add_action('woocommerce_after_order_notes', [CheckoutViewRenderer::class, 'render_checkout_button_as_button']);
        add_filter('woocommerce_checkout_fields', [CheckoutViewRenderer::class, 'fill_out_fields']);

        /** Intercept woocommerce action after pressing Place Order button (checkout process) */
        add_action('woocommerce_checkout_process', [$this, 'after_submit'], 1);

        add_action('woocommerce_checkout_update_order_review', [$this, 'after_submit'], 1);

        // remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
        // add_action('woocommerce_thankyou', [$this, 'woocommerce_thankyou_redirect'], 1);
        // add_action('woocommerce_checkout_order_review', [$this, 'after_submit']);
    }


    /**
     * Inititalize configuraiton parameters of Fiserv SDK.
     * @todo This is subject to change, since Config API will change in coming
     * versions. 
     */
    private static function init_fiserv_sdk(): void
    {
        Config::$ORIGIN = 'Fiserv Woocommerce Plugin';
        Config::$API_KEY = get_option('api_key_id');
        Config::$API_SECRET = get_option('api_secret_id');
    }

    public function after_submit($order_id)
    {
        self::$order_id  = $order_id;
        $order = wc_get_order($order_id);
        self::$order_key = $order->get_order_key();

        // wp_redirect('https://stackoverflow.com/');
        self::redirect_to_checkout();
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
     * Inject the HTML button element onto the cart view. Also bind action method to that button.
     */
    function inject_fiserv_checkout_button()
    {

        // if (isset($_POST['action'])) {
        //     self::redirect_to_checkout();
        // }

        // $refer = esc_url(admin_url('admin-post.php'));
        // $nonce = wp_create_nonce('fiserv_plugin_some_action_nonce');

        // 5579346132831154
        // Japheth Massaro
        // 372
        // 04/29

        CheckoutViewRenderer::render_checkout_button_as_button();
    }


    /**
     * Generate checkout link (if it does not exist already)
     * and redirect to it.
     */
    private function redirect_to_checkout()
    {
        $cart_total = floatval(WC()->cart->get_total('non_view'));

        if (
            self::$checkout_link === '#' ||
            $cart_total !== self::$reference_total
        ) {
            self::$reference_total = $cart_total;
            self::$checkout_link = self::create_checkout_link();
            // self::$checkout_link = "https://stackoverflow.com/";
        }

        JSUtil::log("REDIRECT OUTPUT: " . self::$checkout_link . " END");
        // wp_redirect(self::$checkout_link, 301);
    }

    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     * Block handling requests via SDK. Possible SDK exceptions are caught
     * so that fail and loading state can be shown on view.
     * 
     * @throws Exception Error thrown from Fiserv SDK (Request Errors). Error is caught by setting 
     * returned checkout link to '#' (no redirect)
     * @return string URL of hosted payment page
     */
    private function create_checkout_link(): string
    {
        $req = new PaymentLinkRequestBody(self::paymentLinksRequestContent);
        $req = self::configure_checkout_request($req);

        try {
            $res = CheckoutSolution::postCheckouts($req);
        } catch (Exception $th) {
            Util::log("Fiserv SDK Error: " . $th->getMessage(), 1);
            self::$requestFailed = true;
            return "#";
        }

        return $res->checkout->redirectionUrl;
    }

    private static bool $requestFailed = false;

    /**
     * Getter for requestFailed flag
     * 
     * @return bool True if request has failed
     */
    public static function get_request_failed(): bool
    {
        return self::$requestFailed;
    }

    /**
     * After default request params are set, some other
     * params have to be set during run time (e.g. current cart total, success URL according to domain)
     * 
     * @todo Currently, passing floats (to transaction amount) causes unexpected behaviour.
     * Until float parameters are fixed, the total is cast to integers (amount is floored).
     * 
     * @param PaymentLinkRequestBody $req Request to be modified
     * @return PaymentLinkRequestBody Copy of request with changes applied
     */
    private static function configure_checkout_request(PaymentLinkRequestBody $req): PaymentLinkRequestBody
    {
        $successUrl = self::$domain . '/checkout/order-received/' . self::$order_id . '/?key=' . self::$order_key;
        $failureUrl = self::$domain . '/checkout/order-received/' . self::$order_id . '/?key=' . self::$order_key;

        $req->checkoutSettings->redirectBackUrls->successUrl = $successUrl;
        $req->checkoutSettings->redirectBackUrls->failureUrl = $failureUrl;
        $req->checkoutSettings->webHooksUrl = WebhookHandler::$webhook_endpoint;

        $req->transactionAmount->total = intval(self::$reference_total);

        return $req;
    }

    public static string $order_id;
    public static string $order_key;

    public function woocommerce_thankyou_redirect($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_meta_data('_thankyou_action_done', true);
        $order->save();
    }
}
