<?php

use Fiserv\CheckoutSolution;
use FiservWoocommercePlugin\CheckoutHandler;

class CheckoutGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'fiserv-gateway';
        $this->has_fields = false;
        $this->method_title = 'Fiserv Gateway';
        $this->method_description = 'Description for Fiserv Gateway';
        $this->init_form_fields();
        $this->init();
        $this->supports = array('subscriptions');
        // $this->init_settings();
    }

    public function init()
    {
        $this->title = get_option('title');
        $this->description = get_option('description');
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Toggle',
                'type' => 'Enable/Disable',
                'label' => 'Enable Fiserv Checkout',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => 'Checkout with Fiserv',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Customer Message', 'firstdata'),
                'type' => 'textarea',
                'default' => ''
            )
        );
    }

    function process_payment($order_id)
    {
        $checkout_link = CheckoutHandler::create_checkout_link($order_id);
        // $res = CheckoutSolution::createSEPACheckout('255', 'http://google.com', 'http://google.com');
        // $checkout_link = $res->checkout->redirectionUrl;

        return array(
            'result' => 'success',
            'redirect' => $checkout_link,
        );
    }
}
