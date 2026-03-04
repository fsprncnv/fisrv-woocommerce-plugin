<?php

if (!defined('ABSPATH')) {
    exit;
}

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Gateway instance for iDEAL pre-selection. Inherits from WC_Fiserv_Payment_Gateway.
 */
class WC_Fiserv_Gateway_Ideal extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::IDEAL;
        $this->id = Fisrv_Identifiers::GATEWAY_IDEAL->value;

        $this->method_title = 'iDEAL';
        $this->method_description = __('Pay with iDEAL', 'fiserv-checkout-for-woocommerce');
        $this->title = 'iDEAL';
        $this->description = esc_html__('You will be redirected to an external checkout page.', 'fiserv-checkout-for-woocommerce');

        parent::__construct();
    }
}
