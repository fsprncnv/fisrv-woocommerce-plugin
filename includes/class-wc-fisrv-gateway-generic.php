<?php

class WC_Fisrv_Payment_Generic extends WC_Fisrv_Payment_Gateway
{


    private WC_Fisrv_Gateway_Applepay $gateway_applepay;
    private WC_Fisrv_Gateway_Googlepay $gateway_googlepay;
    private WC_Fisrv_Gateway_Cards $gateway_cards;

    public function __construct()
    {
        $this->id = FisrvGateway::GENERIC->value;

        $this->method_title = 'Fiserv Checkout';
        $this->method_description = esc_html__('Generic option shows all supported methods on redirect page.', 'fisrv-checkout-for-woocommerce');
        $this->title = 'Fiserv Checkout';

        parent::__construct();
    }
}
