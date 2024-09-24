<?php

use Fisrv\Models\PreSelectedPaymentMethod;

class WC_Fisrv_Gateway_Googlepay extends WC_Fisrv_Payment_Gateway
{
    public function __construct()
    {
        $this->selected_method = PreSelectedPaymentMethod::GOOGLEPAY;
        $this->id = FisrvGateway::GOOGLEPAY->value;

        $this->method_title = 'Google Pay - Fiserv Checkout';
        $this->method_description = esc_html__('Pay with Google Pay via Fiserv', 'fisrv-checkout-for-woocommerce');
        $this->title = 'Google Pay';

        parent::__construct();
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'applepay_mode' => array(
                'title' => esc_html__('Google Pay Mode', 'fisrv-checkout-for-woocommerce'),
                'type' => 'select',
                'css' => 'padding: 8px 10px; border: none;',
                'default' => 'redirect',
                'description' => esc_html__('Use integrated Google Pay button or use Google Pay on redirected checkout page', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
                'options' => array(
                    'redirect' => esc_html__('Checkout redirect page', 'fisrv-checkout-for-woocommerce'),
                    'button' => esc_html__('Integrated button', 'fisrv-checkout-for-woocommerce'),
                ),
            ),
        ];

        parent::init_form_fields();
    }
}
