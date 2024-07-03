<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Applepay extends WC_Fisrv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::APPLE;
        $this->id = 'fisrv-gateway-apple';

        $this->method_title = 'Apple Pay - Fisrv Checkout';
        $this->method_description = __('Pay with Apple Pay via Fisrv', 'fisrv-checkout-for-woocommerce');

        $this->default_title = 'Apple Pay';

        parent::__construct();
    }
}
