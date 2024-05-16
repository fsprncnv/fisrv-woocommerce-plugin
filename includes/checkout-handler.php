<?php

namespace FiservWoocommercePlugin;

use CheckoutViewRenderer;
use Exception;
use Fiserv\CheckoutSolution;
use PaymentLinkRequestBody;
use PostCheckoutsResponse;
use Util;

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
     */
    public function __construct()
    {
        self::$domain = get_site_url();

        remove_action('woocommerce_order_button_html', [$this, 'woocommerce_order_button_html']);
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        add_action('woocommerce_checkout_after_customer_details', [$this, 'inject_fiserv_checkout_button'], 1);
        add_action('init', [$this, 'output_buffer']);

        add_filter('woocommerce_checkout_fields', [CheckoutViewRenderer::class, 'set_input_placeholders']);
        add_action('woocommerce_review_order_before_payment', [CheckoutViewRenderer::class, 'inject_payment_options'], 1);
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

        if (isset($_POST['action'])) {
            self::redirect_to_checkout();
        }

        $refer = esc_url(admin_url('admin-post.php'));
        $nonce = wp_create_nonce('fiserv_plugin_some_action_nonce');

        CheckoutViewRenderer::render_checkout_button();
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
        }

        wp_redirect(self::$checkout_link, 301);
        // exit();
    }

    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     * 
     * @return string URL of hosted payment page
     */
    private function create_checkout_link(): string
    {
        $req = new PaymentLinkRequestBody(self::paymentLinksRequestContent);
        $req = self::configure_checkout_request($req);
        $res = self::invoke_request($req);

        if (!$res) {
            return "#";
        }

        return $res->checkout->redirectionUrl;
    }

    private static bool $requestFailed = false;

    public static function hasRequestFailed(): bool
    {
        return self::$requestFailed;
    }

    /**
     * Block handling requests via SDK. Possible SDK exceptions are caught
     * so that fail and loading state can be shown on view.
     * 
     * @param PaymentLinkRequestBody $req Request to be sent to SDK
     * @return PostCheckoutsRepsonse $res Response from SDK
     * @return bool false if request has failed
     */
    private function invoke_request(PaymentLinkRequestBody $req): PostCheckoutsResponse | false
    {
        try {
            $res = CheckoutSolution::postCheckouts($req);
            return $res;
        } catch (Exception $th) {
            Util::log("Fiserv SDK Error: " . $th->getMessage(), 1);
            $this->requestFailed = true;
        }

        return false;
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
        $successUrl = self::$domain . '/checkout/order-received';
        $failureUrl = self::$domain . '/checkout/order-failed';

        $req->checkoutSettings->redirectBackUrls->successUrl = $successUrl;
        $req->checkoutSettings->redirectBackUrls->failureUrl = $failureUrl;
        // $req->checkoutSettings->webHooksUrl = WebhookHandler::$webhook_endpoint;

        $req->transactionAmount->total = intval(self::$reference_total);

        return $req;
    }
}
