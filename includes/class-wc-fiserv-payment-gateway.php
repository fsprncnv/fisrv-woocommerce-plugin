<?php

/**
 * Custom Woocommerce payment gateway.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     Fiserv
 * @since      1.0.0
 */
final class WC_Fiserv_Payment_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'fiserv-gateway';
        $this->has_fields = false;
        $this->method_title = 'Fiserv Gateway';
        $this->method_description = 'Pay with Fiserv Checkout';
        $this->description = 'Pay with credit card';
        $this->title = 'Fiserv Checkout';

        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        WC_Fiserv_Checkout_Handler::init_fiserv_sdk(
            $this->get_option('api_key'),
            $this->get_option('api_secret'),
            $this->get_option('store_id'),
        );
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
            'label'         => 'Enable Fiserv Payment Gateway',
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
    ];

    public function init_form_fields()
    {
        $this->form_fields = $this->plugin_settings;
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        try {
            $cache = $order->get_meta('_fiserv_plugin_checkout_link', true);
            $retryNumber = $order->get_meta('_fiserv_plugin_cache_retry');

            // if (is_string($cache) && stringStartsWith(self::$checkout_lane_domain && $retryNumber < 2)) {
            //     $retryNumber = $order->get_meta('_fiserv_plugin_cache_retry');

            //     $order->update_meta_data('_fiserv_plugin_cache_retry', $retryNumber + 1);
            //     $order->save_meta_data();
            //     $checkout_link = $cache;
            // } else {
            // }

            $checkout_link = WC_Fiserv_Checkout_Handler::create_checkout_link($order);

            return [
                'result' => 'success',
                'redirect' => $checkout_link,
            ];
        } catch (Throwable $th) {
            $message = 'Failed creating Fiserv Checkout - ' . $th->getMessage();
            wc_add_notice($message, 'error');

            WC_Fiserv_Logger::error($order, $message);
            $order->update_status('wc-failed', $message);

            return [
                'result' => 'failure',
                'redirect' => wc_get_checkout_url(),
            ];
        }
    }
}
