<?php

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Gateway instance for google pay pre-selection. Inherits from WC_Fisrv_Payment_Gateway.
 */
class WC_Fisrv_Gateway_Googlepay extends WC_Fisrv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::GOOGLEPAY;
        $this->id = Fisrv_Identifiers::GATEWAY_GOOGLEPAY->value;

        $this->method_title = 'Google Pay - Fiserv Checkout';
        $this->method_description = esc_html__('Pay with Google Pay via Fiserv', 'fisrv-checkout-for-woocommerce');
        $this->title = 'Google Pay';

        // if ($this->get_option('googlepay_mode') === 'integrated') {
        //     wp_enqueue_script('google-pay-component');
        //     wp_enqueue_script('google-pay-async');
        // }

        parent::__construct();
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            // 'googlepay_mode' => array(
            //     'title' => esc_html__('Google Pay Mode', 'fisrv-checkout-for-woocommerce'),
            //     'type' => 'select',
            //     'css' => 'padding: 8px 10px; border: none;',
            //     'default' => 'redirect',
            //     'description' => esc_html__('Use integrated Google Pay button or use Google Pay on redirected checkout page', 'fisrv-checkout-for-woocommerce'),
            //     'desc_tip' => true,
            //     'options' => array(
            //         'redirect' => esc_html__('Checkout redirect page', 'fisrv-checkout-for-woocommerce'),
            //         'integrated' => esc_html__('Integrated button', 'fisrv-checkout-for-woocommerce'),
            //     ),
            // ),
        ];

        parent::init_form_fields();
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
