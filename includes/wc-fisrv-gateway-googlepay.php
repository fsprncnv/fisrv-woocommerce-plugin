<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Googlepay extends WC_Fisrv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::GPAY;
        $this->id = 'fisrv-gateway-gpay';

        $this->method_title = 'Google Pay - Fisrv Checkout';
        $this->method_description = __('Pay with Google Pay via Fisrv', WC_Fisrv_Util::SLUG);

        $this->default_title = 'Google Pay';

        parent::__construct();
    }
}
