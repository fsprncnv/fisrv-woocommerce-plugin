<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Applepay extends WC_Fisrv_Payment_Gateway
{



    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::APPLE;
        $this->id = FisrvGateway::APPLEPAY->value;

        $this->method_title = 'Apple Pay - Fiserv Checkout';
        $this->method_description = __('Pay with Apple Pay via Fiserv', 'fisrv-checkout-for-woocommerce');
        $this->title = 'Apple Pay';

        parent::__construct();
    }
}
