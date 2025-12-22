<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway instance for generic option without pre-selection. Inherits from WC_Fiserv_Payment_Gateway.
 */
class WC_Fiserv_Payment_Generic extends WC_Fiserv_Payment_Gateway
{
    public function __construct()
    {
        $this->id = Fisrv_Identifiers::GATEWAY_GENERIC->value;

        $this->method_title = __('Generic Checkout', 'fiserv-checkout-for-woocommerce');
        $this->method_description = __('Generic option shows all supported methods on redirect page.', 'fiserv-checkout-for-woocommerce');
        $this->title = __('Generic Checkout', 'fiserv-checkout-for-woocommerce');
        $this->description = __('You will be redirected to an external checkout page where you will be able to select a payment method.', 'fiserv-checkout-for-woocommerce');

        wp_enqueue_script('fiserv-custom-script');

        parent::__construct();
    }

    /**
     * Settings form fields exclusive for generic payment method
     *
     * @return void
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [
            'section-1' => [
                'title' => esc_html__('Basic Settings', 'fiserv-checkout-for-woocommerce'),
                'type' => 'section_heading'
            ],
            'api_key' => array(
                'title' => esc_html__('API Key', 'fiserv-checkout-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__('Acquire API Key from Developer Portal', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'api_secret' => array(
                'title' => 'API Secret',
                'type' => 'password',
                'description' => esc_html__('Acquire API Secret from Developer Portal', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'store_id' => array(
                'title' => 'Store ID',
                'type' => 'text',
                'description' => esc_html__('Your Store ID for Checkout', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'is_prod' => array(
                'title' => esc_html__('Production Mode', 'fiserv-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'description' => esc_html__('Use Live (Production) Mode or Test (Sandbox) Mode', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'healthcheck' => array(
                'title' => esc_html__('API Health', 'fiserv-checkout-for-woocommerce'),
                'type' => 'healthcheck',
                'description' => esc_html__('Get current status of Fiserv API and your configuration', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'enable_log' => array(
                'title' => esc_html__('Enable Developer Logs', 'fiserv-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'default' => false,
                'description' => esc_html__('Enable log messages on WooCommerce', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'section-2' => [
                'title' => esc_html__('Order Settings', 'fiserv-checkout-for-woocommerce'),
                'type' => 'section_heading'
            ],
            'autocomplete' => array(
                'title' => esc_html__('Auto-complete Orders', 'fiserv-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'description' => esc_html__('Skip processing order status and set to complete status directly', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'fail_page' => array(
                'title' => esc_html__('Redirect after payment failure', 'fiserv-checkout-for-woocommerce'),
                'type' => 'select',
                'description' => esc_html__('Where to redirect if payment failed', 'fiserv-checkout-for-woocommerce'),
                'default' => 'checkout',
                'options' => array(
                    'checkout' => esc_html__('Checkout page', 'fiserv-checkout-for-woocommerce'),
                    'cart' => esc_html__('Shopping cart', 'fiserv-checkout-for-woocommerce'),
                ),
            ),
            'transaction_type' => array(
                'title' => esc_html__('Transaction Type', 'fiserv-checkout-for-woocommerce'),
                'type' => 'transaction_type',
                'desc_tip' => true,
                'description' => esc_html__('Set transaction type. Currently, only SALE transactions are available.', 'fiserv-checkout-for-woocommerce'),
                'default' => 'Sale',
                'css' => 'disabled; pointer-events: none;',
            ),
            'section-3' => [
                'title' => esc_html__('Customization', 'fiserv-checkout-for-woocommerce'),
                'type' => 'section_heading'
            ],
            'wp_theme_data' => array(
                'title' => esc_html__('Theme Colors', 'fiserv-checkout-for-woocommerce'),
                'type' => 'wp_theme_data',
                'desc_tip' => true,
                'description' => esc_html__('Info about current WordPress theme data which you can use to customize your checkout page on our Virtual Terminal', 'fiserv-checkout-for-woocommerce'),
            ),

        ];

        parent::init_form_fields();
    }
}
