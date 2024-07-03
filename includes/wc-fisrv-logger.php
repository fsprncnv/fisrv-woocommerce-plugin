<?php

/**
 * Class that handles creation of webhook consumers to receive order
 * webhook event sent by Checkout Solution.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
final class WC_Fisrv_Logger
{
    public const SOURCE = 'fisrv-checkout-for-woocommerce';

    /**
     * Log some message to WC admin page as info log
     *
     * @param string $message Message to log
     * @param WC_Order $order Order that failed
     */
    public static function log(WC_Order $order, string $message): void
    {
        $log_context = self::create_log_context($order);
        wc_get_logger()->notice($message, $log_context);
    }

    /**
     * Log some message to WC admin page as error log
     *
     * @param string $message Message to log
     * @param WC_Order $order Order that failed
     */
    public static function error(WC_Order $order, string $message): void
    {
        $log_context = self::create_log_context($order);
        wc_get_logger()->error($message, $log_context);
    }

    /**
     * Create generic message template containing log context from order
     *
     * @param WC_Order $order Order that failed
     * @return array<string, mixed>
     */
    private static function create_log_context(WC_Order $order): array
    {
        return [
            'source' => self::SOURCE,
            'wc_order_id' => $order->get_id(),
            'wc_order_key' => $order->get_order_key(),
            'fisrv_link' => $order->get_meta('_fisrv_plugin_checkout_link'),
            'fisrv_checkout_id' => $order->get_meta('_fisrv_plugin_checkout_id'),
            'fisrv_trace_id' => $order->get_meta('_fisrv_plugin_trace_id'),
        ];
    }
}
