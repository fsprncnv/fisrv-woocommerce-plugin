<?php

if (!defined('ABSPATH')) {
    exit;
}

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

        $this->method_title = 'Apple Pay';
        $this->method_description = esc_html__('Pay with Apple Pay', 'fiserv-checkout-for-woocommerce');
        $this->title = 'Apple Pay';

        parent::__construct();
    }
}
