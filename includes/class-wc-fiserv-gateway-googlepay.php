<?php

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

        $this->method_title = 'Google Pay - Fiserv Checkout';
        $this->method_description = esc_html__('Pay with Google Pay via Fiserv', 'fiserv-checkout-for-woocommerce');
        $this->title = 'Google Pay';

        parent::__construct();
    }

    /**
     * Callback hook to replace default place order button to custom. This is used when
     * injecting native Google Pay button.
     * @param mixed $order_button
     * @return string
     */
    public static function replace_order_button_html($order_button): string
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[Fisrv_Identifiers::GATEWAY_GOOGLEPAY->value];
        if ($gateway->get_option('googlepay_mode') === 'integrated') {
            $data = WC()->cart->get_cart_contents();
            return $order_button . ' <div data="' . base64_encode(wp_json_encode($data)) . '" id="fs-gpay-container" style="text-align: end;"></div> 
            <div style="background: green; onclick=""></div>';
        }

        return $order_button;
    }
}
