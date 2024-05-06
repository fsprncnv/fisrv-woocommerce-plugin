<?php

namespace FiservWoocommercePlugin;

use SebastianBergmann\Type\VoidType;

use Fiserv\CheckoutSolution;
use PaymentLinkRequestBody;

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
        add_action('init', [$this, 'output_buffer']);

        self::log("ON MOUNT");
    }

    function output_buffer()
    {
        ob_start();
    }

    function inject_fiserv_checkout_button()
    {

        if (isset($_POST['action'])) {
            $checkout_link = self::create_checkout_link();
            wp_redirect($checkout_link, 301);
            exit();
        }

        $refer = esc_url(admin_url('admin-post.php'));
        $nonce = wp_create_nonce('fiserv_plugin_some_action_nonce');
        $target = '#';

        echo '<form action="' . $target . '" method="post">';
        echo '<input type="hidden" name="action" value="some_action" />';
        echo '<button type="submit" class="checkout-button button alt" style="background-color: #ff6600; font-weight: 700; padding: 1em; font-size: 1.25em; text-align: center; width: 100%;">Checkout with fiserv</button>';
        echo '</form>';
    }

    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     */
    private function create_checkout_link(): string
    {
        $cart_subtotal = intval(WC()->cart->get_total('data') * 100);

        $req = new PaymentLinkRequestBody(self::paymentLinksRequestContent);
        $req->transactionAmount->total = $cart_subtotal;

        $res = CheckoutSolution::postCheckouts($req);

        return $res->checkout->redirectionUrl;
    }

    /**
     * Short hand to make a log to
     */
    private function log(string $msg): void
    {
        echo '<script>console.log("' . $msg . '")</script>';
    }
}
