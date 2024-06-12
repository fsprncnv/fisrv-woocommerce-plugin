<?php

if (!defined('ABSPATH')) exit;

/**
 * Class that handles creation of webhook consumers to receive order
 * webhook event sent by Checkout Solution.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     Fiserv
 * @since      1.0.0
 */
class WC_Fiserv_Logger
{
    const WC_LOG_SOURCE = 'fiserv-checkout-for-woocommerce';

    /**
     * Log some message to WC admin page as info log
     * 
     * @param string $message Message to log
     * @param object $order Order that failed
     */
    public static function log(object $order, string $message): void
    {
        $log_context = self::create_log_context($order);
        wc_get_logger()->notice($message, $log_context);
    }

    /**
     * Log some message to WC admin page as error log
     * 
     * @param string $message Message to log
     * @param object $order Order that failed
     */
    public static function error(object $order, string $message): void
    {
        $log_context = self::create_log_context($order);
        wc_get_logger()->error($message, $log_context);
    }

    /**
     * Create generic message template containing log context from order
     *
     * @param object $order Order that failed 
     */
    private static function create_log_context(object $order): array
    {
        $ipg_link = $order->get_meta('_fiserv_plugin_checkout_link');
        $ipg_checkout_id = $order->get_meta('_fiserv_plugin_checkout_id');
        $ipg_trace_id = $order->get_meta('_fiserv_plugin_trace_id');

        return [
            'source' => self::WC_LOG_SOURCE,
            'wc_order_id' => $order->get_id(),
            'wc_order_key' => $order->get_order_key(),
            'ipg_link' => $ipg_link,
            'ipg_checkout_id' => $ipg_checkout_id,
            'ipg_trace_id' => $ipg_trace_id,
        ];
    }
}
