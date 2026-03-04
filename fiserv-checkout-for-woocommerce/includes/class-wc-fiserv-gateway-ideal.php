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

        $this->method_title = 'iDEAL | Wero'; // This is the title shown in the admin settings and checkout page
        $this->method_description = __('Pay with iDEAL | Wero', 'fiserv-checkout-for-woocommerce');// This is the description shown in the admin settings under the title
        $this->title = 'iDEAL | Wero';
        $this->description = esc_html__('You will be redirected to an external checkout page.', 'fiserv-checkout-for-woocommerce');

            // Test if constructor runs (WooCommerce log)
            if (function_exists('wc_get_logger')) {
                $logger = wc_get_logger();
                $logger->info('WC_Fiserv_Gateway_Ideal constructor executed', array('source' => 'fiserv-ideal-test'));
            }

        parent::__construct();
    }
}
