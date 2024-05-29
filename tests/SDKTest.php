<?php

use PHPUnit\Framework\TestCase;
use Fiserv\CheckoutSolution;

/**
 * Basic unit tests.
 */
final class SDKTest extends TestCase
{
	public function setUp(): void
	{
		Config::$ORIGIN = 'PHP Unit';
		Config::$API_KEY = '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP';
		Config::$API_SECRET = 'KCFGSj3JHY8CLOLzszFGHmlYQ1qI9OSqNEOUj24xTa0';
		Config::$STORE_ID = '72305408';
	}

	public const paymentLinksRequestContentDefault = [
		"transactionOrigin" => "ECOM",
		"transactionType" => "SALE",
		"transactionAmount" => [
			"total" => 130,
			"currency" => "EUR"
		],
		"checkoutSettings" => [
			"locale" => "en_GB"
		],
		"paymentMethodDetails" => [
			"cards" => [
				"authenticationPreferences" => [
					"challengeIndicator" => "01",
					"skipTra" => false
				],
				"createToken" => [
					"declineDuplicateToken" => false,
					"reusable" => true,
					"toBeUsedFor" => "UNSCHEDULED"
				],
				"tokenBasedTransaction" => [
					"transactionSequence" => "FIRST"
				],
			],
			"sepaDirectDebit" => [
				"transactionSequenceType" => "SINGLE"
			],
		],
		"storeId" => "72305408"
	];

	public const paymentLinksRequestContentAdjusted = [
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
	];

	public function testPaymentLinkRequestBody(): void
	{
		$req = new PaymentLinkRequestBody(self::paymentLinksRequestContentDefault);
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

	public function testCreateManualCheckout(): void
	{
		$req = new PaymentLinkRequestBody(self::paymentLinksRequestContentAdjusted);
		$res = CheckoutSolution::postCheckouts($req);
		$this->assertInstanceOf(PostCheckoutsResponse::class, $res);
	}
}
