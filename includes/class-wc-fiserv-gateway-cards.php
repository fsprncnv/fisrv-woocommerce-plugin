<?php

use Fiserv\Models\PreSelectedPaymentMethod;

class WC_Fiserv_Gateway_Cards extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::CARDS;
        $this->id = 'fiserv-gateway-cards';

        $this->method_title = 'Fiserv Checkout Cards';
        $this->method_description = 'Pay with credit card';

        $this->default_title = 'Fiserv Checkout Cards';
        $this->default_description = 'Pay with credit card';

        parent::__construct();
    }
}
