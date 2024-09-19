<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Cards extends WC_Fisrv_Payment_Gateway
{



    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::CARDS;
        $this->id = FisrvGateway::CREDITCARD->value;

        $this->method_title = 'Credit/Debit - Fiserv Checkout';
        $this->method_description = __('Pay with Credit/Debit via Fiserv', 'fisrv-checkout-for-woocommerce');
        $this->title = __('Credit/Debit Card', 'fisrv-checkout-for-woocommerce');

        parent::__construct();
    }
}
