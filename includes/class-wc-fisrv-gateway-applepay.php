<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Applepay extends WC_Fisrv_Payment_Gateway
{

	public function __construct()
	{
		$this->selected_method = PreSelectedPaymentMethod::APPLE;
		$this->id = 'fisrv-apple-pay';

		$this->method_title = 'Apple Pay - Fiserv Checkout';
		$this->method_description = __('Pay with Apple Pay via Fiserv', 'fisrv-checkout-for-woocommerce');

		$this->default_title = 'Apple Pay';
		$this->supported_methods = ['applepay'];

		parent::__construct();
	}
}
