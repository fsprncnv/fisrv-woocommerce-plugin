<?php

if (!defined('ABSPATH')) {
    exit;
}

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

        $this->method_title = __('Credit / Debit Card', 'fiserv-checkout-for-woocommerce');
        $this->method_description = __('Pay with Credit / Debit Card', 'fiserv-checkout-for-woocommerce');
        $this->title = __('Credit / Debit Card', 'fiserv-checkout-for-woocommerce');

        parent::__construct();

        // Remove name customization from card page as Credit / Debit Card name is required
        unset($this->form_fields['title']);
    }
}
