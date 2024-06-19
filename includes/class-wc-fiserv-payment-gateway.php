<?php

use Fiserv\Models\PreSelectedPaymentMethod;

/**
 * Custom Woocommerce payment gateway.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     Fiserv
 * @since      1.0.0
 */
abstract class WC_Fiserv_Payment_Gateway extends WC_Payment_Gateway
{
    protected static PreSelectedPaymentMethod $selected_method;
    protected string $default_title = 'yes';
    protected string $default_description;

    public function __construct()
    {
        self::$selected_method = PreSelectedPaymentMethod::CARDS;

        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->init_properties();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        WC_Fiserv_Checkout_Handler::init_fiserv_sdk(
            $this->get_option('api_key'),
            $this->get_option('api_secret'),
            $this->get_option('store_id'),
        );
    }

    protected function init_properties()
    {
        $this->enabled      = $this->get_option('enabled');
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->icon         = $this->get_option('icon');
    }

    public function init_form_fields()
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
                'default'       => 'https://www.innoitus.com.au/wp-content/uploads/2017/12/fiserv-logo.png',
                'desc_tip'      => true,
            ],
        ];
    }

    private function is_cached($order): bool | string
    {
        if (!$order->has_status('pending')) {
            return false;
        }

        $cache = $order->get_meta('_fiserv_plugin_checkout_link', true);

        if (!is_string($cache)) {
            return false;
        }

        return $cache;
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        try {
            if ($cache = self::is_cached($order)) {
                $checkout_link = $cache;
            } else {
                $checkout_link = WC_Fiserv_Checkout_Handler::create_checkout_link($order, self::$selected_method);
            }

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
