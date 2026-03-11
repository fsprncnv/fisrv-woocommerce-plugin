<?php

if (!defined('ABSPATH')) {
    exit;
}

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Gateway instance for Bizum pre-selection. Inherits from WC_Fiserv_Payment_Gateway.
 */
class WC_Fiserv_Gateway_Bizum extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::BIZUM;
        $this->id = Fisrv_Identifiers::GATEWAY_BIZUM->value;

        $this->method_title = 'Bizum';
        $this->method_description = __('Pay with Bizum', 'fiserv-checkout-for-woocommerce');
        $this->title = 'Bizum';
        $this->description = esc_html__('You will be redirected to an external checkout page.', 'fiserv-checkout-for-woocommerce');

        parent::__construct();
    }
}
