<?php

namespace FiservWoocommercePlugin;

use Exception;
use Fiserv\CheckoutSolution;
use PaymentLinkRequestBody;
use PostCheckoutsResponse;
use SebastianBergmann\Type\VoidType;

class CheckoutHandler
{
    /**
     * Default params to be passed as request body of post checkout
     */
    public const paymentLinksRequestContent = [
        'transactionOrigin' => 'ECOM',
        'transactionType' => 'SALE',
        'transactionAmount' => [
            'total' => 130,
            'currency' => 'EUR'
        ],
        'checkoutSettings' => [
            'locale' => 'en_GB',
            'redirectBackUrls' => [
                'successUrl' => "http://fiserv-wp-dev.local/checkout/order-received/",
                'failureUrl' => "http://fiserv-wp-dev.local/checkout/order-received/"
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

        remove_action('woocommerce_proceed_to_checkout', [$this, 'woocommerce_button_proceed_to_checkout'], 20);
        add_action('woocommerce_proceed_to_checkout', [$this, 'inject_fiserv_checkout_button'], 1);
        add_action('init', [$this, 'output_buffer']);
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

        self::render_checkout_button();
    }

    /**
     * This block instantiates the HTML markup for the button component.
     */
    private function render_checkout_button(): void
    {
        $form_post_target = '#';
        $button_container = 'document.getElementById(\'checkout-btn-target\')';

        $loader_css = '
        .lds-ellipsis,
        .lds-ellipsis div {
          box-sizing: border-box;
        }
        .lds-ellipsis {
          position: relative;
          width: 20px;
          height: 20px;
        }
        .hidden {
            display: none;
        }
        .show {
            display: inline-block;
        }
        .lds-ellipsis div {
          position: absolute;
          top: 20%;
          width: 50%;
          height: 50%;
          border-radius: 50%;
          background: currentColor;
          animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }
        .lds-ellipsis div:nth-child(1) {
          left: 8px;
          animation: lds-ellipsis1 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(2) {
          left: 8px;
          animation: lds-ellipsis2 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(3) {
          left: 32px;
          animation: lds-ellipsis2 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(4) {
          left: 56px;
          animation: lds-ellipsis3 0.6s infinite;
        }
        @keyframes lds-ellipsis1 {
          0% {
            transform: scale(0);
          }
          100% {
            transform: scale(1);
          }
        }
        @keyframes lds-ellipsis3 {
          0% {
            transform: scale(1);
          }
          100% {
            transform: scale(0);
          }
        }
        @keyframes lds-ellipsis2 {
          0% {
            transform: translate(0, 0);
          }
          100% {
            transform: translate(24px, 0);
          }
        }
        ';

        $loader_html = '
        <div id="loader-spinner" class="lds-ellipsis hidden"><div></div><div></div><div></div><div></div></div>
        ';

        $button_text = $this->requestFailed ? 'Something went wrong. Try again.' : 'Checkout with Fiserv';

        $component =
            '
            <style>' . $loader_css . '</style>
            <script>
                function load() { 
                    document.getElementById(\'loader-spinner\').classList.add(\'show\');
                }
            </script>
            <form action="' . $form_post_target . '" method="post" class="checkout-button button alt" style="margin-bottom: 1rem">
                <input type="hidden" name="action" value="some_action" />
                <button 
                    id="checkout-btn-target"
                    onclick="load()"
                    type="submit"
                    style="background-color: #ff6600; font-weight: 700; padding: 1em; font-size: 1.25em; text-align: center; width: 100%;  display: flex; justify-content: center; align-items: center;">
                    ' . $button_text . '
                    ' . $loader_html . '
                </button>
            </form>
        ';

        echo $component;
    }

    /**
     * Renders an HTML component into document from a given string list.
     * 
     * @todo Move this into separate class which handles UI/markup
     */
    private function render_component(array $component): void
    {
        foreach ($component as $line) {
            echo $line;
        }
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

    private bool $requestFailed = false;

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
            self::log("Fiserv SDK Error: " . $th->getMessage(), 1);
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
        $req->transactionAmount->total = intval(self::$reference_total);

        return $req;
    }

    /**
     * Short hand to make a JS log to browser developer console.
     * 
     * @param string $msg Message to be passed to console
     * @param bool $error Optional, if true console will log as JS error
     */
    private static function log(string $msg, bool $error = false): void
    {
        $type = $error ? 'error' : 'log';

        echo '<script>console.' . $type . '("' . $msg . '")</script>';
    }

    /**
     * Save data into browser local storage (leveraging javascript).
     * This serves as cache for generated checkout URLs.
     */
    private function cache_to_storage(string $value): void
    {
        echo '<script>localStorage.setItem("checkout-url-cache", "' . $value . '");</script>';
    }

    /**
     * Check whether local storage has an entry of a given key
     * @return bool True if key exists in local storage
     * @todo Need to find workaround for the fact that checking local storage
     * occurs in Javascript. However the response from Javascript has to be passed back to PHP somehow.
     */
    private function is_cached(): bool
    {
        echo '<script>
        if (localStorage.getItem("checkout-url-cache") === null) {
            //
        }
        </script>';

        return false;
    }
}
