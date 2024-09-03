<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Googlepay extends WC_Fisrv_Payment_Gateway
{

	public function __construct()
	{
		$this->selected_method = PreSelectedPaymentMethod::GOOGLEPAY;
		$this->id = 'fisrv-google-pay';

		$this->method_title = 'Google Pay - Fiserv Checkout';
		$this->method_description = __('Pay with Google Pay via Fiserv', 'fisrv-checkout-for-woocommerce');

		$this->default_title = 'Google Pay';

		parent::__construct();
	}
}
