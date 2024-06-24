<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Apple extends WC_Fisrv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::APPLE;
        $this->id = 'fisrv-gateway-apple';

        $this->method_title = 'Apple Pay - Fisrv Checkout';
        $this->method_description = 'Pay with Apple Pay via Fisrv';

        $this->default_title = 'Apple Pay';

        parent::__construct();
    }
}
