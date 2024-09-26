<?php

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Gateway instance for credit card pre-selection. Inherits from WC_Fiserv_Payment_Gateway.
 */
class WC_Fiserv_Gateway_Cards extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::CARDS;
        $this->id = Fisrv_Identifiers::GATEWAY_CREDITCARD->value;

        $this->method_title = 'Credit/Debit - Fiserv Checkout';
        $this->method_description = __('Pay with Credit/Debit via Fiserv', 'fiserv-checkout-for-woocommerce');
        $this->title = __('Credit/Debit Card', 'fiserv-checkout-for-woocommerce');

        parent::__construct();
    }
}
