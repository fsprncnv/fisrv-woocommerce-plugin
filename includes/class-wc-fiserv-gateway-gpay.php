<?php

use Fiserv\Models\PreSelectedPaymentMethod;

class WC_Fiserv_Gateway_GPay extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::GPAY;
        $this->id = 'fiserv-gateway-gpay';

        $this->method_title = 'Fiserv Checkout Google Pay';
        $this->method_description = 'Pay with Google Pay';

        $this->default_title = 'Fiserv Checkout Google Pay';
        $this->default_description = 'Pay with Google Pay';

        parent::__construct();
    }
}
