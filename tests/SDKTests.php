<?php

use PHPUnit\Framework\TestCase;
use Fiserv\CheckoutSolution;

/**
 * Basic unit tests.
 */
final class SDKTests extends TestCase
{
	public function testEquals(): void
	{
		$this->assertTrue(true);
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
