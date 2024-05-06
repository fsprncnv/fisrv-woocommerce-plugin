<?php

namespace FiservWoocommercePlugin;

// use Fiserv\CheckoutSolution;
// use PaymentLinkRequestBody;

class CheckoutHandler
{
    public const paymentLinksRequestContent = [
        'transactionOrigin' => 'ECOM',
        'transactionType' => 'SALE',
        'transactionAmount' => ['total' => 130, 'currency' => 'EUR'],
        'checkoutSettings' => ['locale' => 'en_GB'],
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

    public function __construct()
    {
        remove_action('woocommerce_proceed_to_checkout', [$this, 'woocommerce_button_proceed_to_checkout'], 20);
        add_action('woocommerce_proceed_to_checkout', [$this, 'inject_fiserv_checkout_button'], 1);
    }

    function inject_fiserv_checkout_button()
    {
        global $woocommerce;
        $cart_subtotal = $woocommerce->cart->get_cart_subtotal();

        // $checkout_link = createCheckoutLink();
        $checkout_link = "#";

        // wp_redirect(home_url("/sample-page/"));

        echo '
    	<a href="' . $checkout_link . '" class="checkout-button button" style="background-color: #ff6600;"> Checkout with fiserv </a>
        ';
    }

    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     */
    private function createCheckoutLink(): string
    {
        // $req = new PaymentLinkRequestBody(self::paymentLinksRequestContent);
        // $res = CheckoutSolution::postCheckouts($req);

        // return $res->checkout->redirectionUrl;
        return 'pass';
    }
}
