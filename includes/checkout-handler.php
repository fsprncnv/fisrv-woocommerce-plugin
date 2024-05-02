<?php

namespace FiservWoocommercePlugin;

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


    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     */
    public function createCheckoutLink(): string
    {
        $req = new PaymentLinkRequestBody(self::paymentLinksRequestContent);
        $res = CheckoutSolution::postCheckouts($req);

        return $res->checkout->redirectionUrl;
    }
}
