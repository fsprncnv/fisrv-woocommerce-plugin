<?php

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Custom Woocommerce payment gateway.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
abstract class WC_Fisrv_Payment_Gateway extends WC_Payment_Gateway
{
    protected PreSelectedPaymentMethod $selected_method;
    protected string $default_title = 'Payment Method';
    protected string $default_description = '';

    public function __construct()
    {
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->init_properties();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Initialize properties from options
     */
    protected function init_properties(): void
    {
        $this->enabled      = $this->get_option('enabled');
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->icon         = $this->get_option('icon');
    }

    /**
     * Initialize form text fields on gateway options page
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title'         => 'Enable/Disable',
                'type'          => 'checkbox',
                'label'         => 'Enable Payment Gateway',
                'default'       => 'yes'
            ],
            'api_key' => [
                'title'         => 'API Key',
                'type'          => 'text',
                'description'   => 'Acquire API Key from Developer Portal',
                'desc_tip'      => true,
            ],
            'api_secret' => [
                'title'         => 'API Secret',
                'type'          => 'password',
                'description'   => 'Acquire API Secret from Developer Portal',
                'desc_tip'      => true,
            ],
            'store_id' => [
                'title'         => 'Store ID',
                'type'          => 'text',
                'description'   => 'Your Store ID for Checkout',
                'desc_tip'      => true,
            ],
            'title' => [
                'title'         => 'Gateway Name',
                'type'          => 'text',
                'description'   => 'Custom name of gateway',
                'default'       => $this->default_title,
                'desc_tip'      => true,
            ],
            'description' => [
                'title'         => 'Gateway Description',
                'type'          => 'text',
                'description'   => 'Custom description of gateway',
                'default'       => $this->default_description,
                'desc_tip'      => true,
            ],
            'icon' => [
                'title'         => 'Gateway Icon',
                'type'          => 'text',
                'description'   => 'Link of image asset',
                'default'       => '',
                'desc_tip'      => true,
            ],
        ];
    }

    protected function is_cached(WC_Order $order): bool | string
    {
        if (!$order->has_status('pending')) {
            return false;
        }

        $cache = $order->get_meta('_fisrv_plugin_checkout_link', true);

        if (!is_string($cache)) {
            return false;
        }

        return $cache;
    }


    /**
     * @return array<string, string>
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            throw new Exception('Processing payment failed. Order is invalid.');
        }

        $checkout_link = WC_Fisrv_Checkout_Handler::create_checkout_link($order, $this->selected_method, $this);

        return [
            'result' => 'success',
            'redirect' => $checkout_link,
        ];
    }
}
