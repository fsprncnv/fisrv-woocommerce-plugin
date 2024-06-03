<?php

use FiservWoocommercePlugin\CheckoutHandler;

if (!defined('ABSPATH')) exit;

class CheckoutGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'fiserv-gateway';
        $this->has_fields = false;
        $this->method_title = 'Fiserv Gateway';
        $this->method_description = 'Description for Fiserv Gateway';
        $this->description = 'Custom Gateway';
        $this->title = 'Fiserv Checkout';

        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init()
    {
        $this->title = get_option('title');
        $this->description = get_option('description');
    }

    public function get_settings()
    {
        return $this->plugin_settings;
    }

    private array $plugin_settings = [
        'enabled' => [
            'title'         => 'Enable/Disable',
            'type'          => 'checkbox',
            'label'         => 'Enable Custom Payment Gateway',
            'default'       => 'yes'
        ],
        'api_key' => [
            'title'         => 'API Key',
            'type'          => 'text',
            'description'   => 'Aquire API Key from Developer Portal',
            'desc_tip'      => true,
        ],
        'api_secret' => [
            'title'         => 'API Secret',
            'type'          => 'password',
            'description'   => 'Aquire API Secret from Developer Portal',
            'desc_tip'      => true,
        ],
        'store_id' => [
            'title'         => 'Store ID',
            'type'          => 'text',
            'description'   => 'Your Store ID for Checkout',
            'desc_tip'      => true,
        ],
    ];

    public function init_form_fields()
    {
        $this->form_fields = $this->plugin_settings;
    }


    function process_payment($order_id)
    {
        $api_key = $this->get_option('api_key');
        $api_secret = $this->get_option('api_secret');
        $store_id = $this->get_option('store_id');

        CheckoutHandler::init_fiserv_sdk($api_key, $api_secret, $store_id);

        // checkoutlink als meta data ablegen
        // checkoutId traceID
        // wc logging

        return CheckoutHandler::create_checkout_link($order_id);
    }
}
