<?php

use PHPUnit\Framework\TestCase;
use Fiserv\CheckoutSolution;

/**
 * Basic unit tests.
 */
final class SDKTest extends TestCase
{
	public const paymentLinksRequestContent = [
		'transactionOrigin' => 'ECOM',
		'transactionType' => 'SALE',
		'transactionAmount' => [
			'total' => 0,
			'currency' => 'EUR'
		],
		'checkoutSettings' => [
			'locale' => 'en_GB',
			'redirectBackUrls' => [
				'successUrl' => "http://example.com/",
				'failureUrl' => "http://example.com/"
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

	public function testPaymentLinkRequestBody(): void
	{
		$req = new PaymentLinkRequestBody(self::paymentLinksRequestContent);
		$this->assertIsObject($req);
	}

	public function testCreateSEPACheckout(): void
	{
		$res = CheckoutSolution::createSEPACheckout(
			14.99,
			"https://success.com",
			"https://noooo.com",
		);


		$this->assertInstanceOf(PostCheckoutsResponse::class, $res);
	}
}
