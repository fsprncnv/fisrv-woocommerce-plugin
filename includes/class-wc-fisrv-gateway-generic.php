<?php

/**
 * Gateway instance for generic option without pre-selection. Inherits from WC_Fisrv_Payment_Gateway.
 */
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

        wp_enqueue_script('fisrv-custom-script');

        parent::__construct();
    }

    /**
     * Settings form fields exclusive for generic payment method
     * @return void
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [
            'section-1' => [
                'title' => 'Basic Settings',
                'type' => 'section_header'
            ],
            'api_key' => array(
                'title' => 'API Key',
                'type' => 'text',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Acquire API Key from Developer Portal', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'api_secret' => array(
                'title' => 'API Secret',
                'type' => 'password',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Acquire API Secret from Developer Portal', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'store_id' => array(
                'title' => 'Store ID',
                'type' => 'text',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Your Store ID for Checkout', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'is_prod' => array(
                'title' => esc_html__('Production Mode', 'fisrv-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Use Live (Production) Mode or Test (Sandbox) Mode', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'healthcheck' => array(
                'title' => esc_html__('API Health', 'fisrv-checkout-for-woocommerce'),
                'type' => 'healthcheck',
                'description' => esc_html__('Get current status of Fiserv API and your configuration', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'enable_log' => array(
                'title' => esc_html__('Enable Developer Logs', 'fisrv-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Enable log messages on WooCommerce', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'section-2' => [
                'title' => 'Order Settings',
                'type' => 'section_header'
            ],
            'autocomplete' => array(
                'title' => esc_html__('Auto-complete Orders', 'fisrv-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Skip processing order status and set to complete status directly', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'enable_browser_lang' => array(
                'title' => esc_html__('Checkout Page Language', 'fisrv-checkout-for-woocommerce'),
                'type' => 'select',
                'css' => 'padding: 8px 10px; border: none;',
                'default' => 'admin',
                'description' => esc_html__('Should language of checkout page be inferred from customer\'s browser or set to admin language', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
                'options' => array(
                    'browser' => esc_html__('Customer\'s preferred language', 'fisrv-checkout-for-woocommerce'),
                    'admin' => esc_html__('Admin dashboard setting', 'fisrv-checkout-for-woocommerce'),
                ),
            ),
            'fail_page' => array(
                'title' => esc_html__('Redirect after payment failure', 'fisrv-checkout-for-woocommerce'),
                'type' => 'select',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Where to redirect if payment failed', 'fisrv-checkout-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => array(
                    'checkout' => esc_html__('Checkout page', 'fisrv-checkout-for-woocommerce'),
                    'cart' => esc_html__('Shopping cart', 'fisrv-checkout-for-woocommerce'),
                    'home' => esc_html__('Home page', 'fisrv-checkout-for-woocommerce'),
                ),
            ),
            'transaction_type' => array(
                'title' => esc_html__('Transaction Type', 'fisrv-checkout-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__('Set transaction type. Currently, only SALE transactions are available.', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
                'default' => 'Sale',
                'css' => 'padding: 8px 10px; border: none; pointer-events: none;',
            ),
            'section-3' => [
                'title' => 'Customization',
                'type' => 'section_header'
            ],
            'wp_theme_data' => array(
                'title' => esc_html__('Theme Colors', 'fisrv-checkout-for-woocommerce'),
                'type' => 'wp_theme_data',
                'description' => esc_html__('Info about current WordPress theme data which you can use to customize your checkout page on our Virtual Terminal', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
        ];

        parent::init_form_fields();
    }
}
