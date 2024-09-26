<?php

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Gateway instance for apple pay pre-selection. Inherits from WC_Fiserv_Payment_Gateway.
 */
class WC_Fiserv_Gateway_Applepay extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::APPLE;
        $this->id = Fisrv_Identifiers::GATEWAY_APPLEPAY->value;

        $this->method_title = 'Apple Pay - Fiserv Checkout';
        $this->method_description = __('Pay with Apple Pay via Fiserv', 'fiserv-checkout-for-woocommerce');
        $this->title = 'Apple Pay';

        parent::__construct();
    }
}
