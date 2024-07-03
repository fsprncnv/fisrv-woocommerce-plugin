<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Cards extends WC_Fisrv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::CARDS;
        $this->id = 'fisrv-gateway-cards';

        $this->method_title = 'Credit card - Fisrv Checkout';
        $this->method_description = __('Pay with credit card via Fisrv', 'fisrv-checkout-for-woocommerce');

        $this->default_title = 'Credit card';

        parent::__construct();
    }
}
