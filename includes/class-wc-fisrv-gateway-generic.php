<?php

class WC_Fisrv_Payment_Generic extends WC_Fisrv_Payment_Gateway
{
	private WC_Fisrv_Gateway_Applepay $gateway_applepay;
	private WC_Fisrv_Gateway_Googlepay $gateway_googlepay;
	private WC_Fisrv_Gateway_Cards $gateway_cards;

	public function __construct()
	{
		$this->id = 'fisrv-gateway-generic';

		$this->method_title = 'Fiserv Checkout';
		$this->method_description = __('Generic option shows all supported methods on redirect page. ', 'fisrv-checkout-for-woocommerce');

		$this->default_title = 'Generic';
		$this->supported_methods = ['creditcard', 'paypal', 'googlepay', 'applepay'];

		$this->gateway_applepay = new WC_Fisrv_Gateway_Applepay();
		$this->gateway_googlepay = new WC_Fisrv_Gateway_Googlepay();
		$this->gateway_cards = new WC_Fisrv_Gateway_Cards();

		parent::__construct();
	}
}
