<?php

class WC_Fisrv_Payment_Generic extends WC_Fisrv_Payment_Gateway
{
	public function __construct()
	{
		$this->id = 'fisrv-gateway-generic';

		$this->method_title = 'Generic - Fiserv Checkout';
		$this->method_description = __('Generic option shows all supported methods on redirect page', 'fisrv-checkout-for-woocommerce');

		$this->default_title = 'Generic';
		$this->supported_methods = ['visa', 'mastercard', 'amex', 'paypal', 'googlepay', 'applepay'];

		parent::__construct();
	}
}
