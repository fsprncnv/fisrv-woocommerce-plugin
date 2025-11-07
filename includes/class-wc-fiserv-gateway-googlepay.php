<?php

if (!defined('ABSPATH')) {
    exit;
}

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Gateway instance for google pay pre-selection. Inherits from WC_Fiserv_Payment_Gateway.
 */
class WC_Fiserv_Gateway_Googlepay extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::GOOGLEPAY;
        $this->id = Fisrv_Identifiers::GATEWAY_GOOGLEPAY->value;

        $this->method_title = 'Google Pay';
        $this->method_description = esc_html__('Pay with Google Pay', 'fiserv-checkout-for-woocommerce');
        $this->title = 'Google Pay';

        parent::__construct();
    }
}
