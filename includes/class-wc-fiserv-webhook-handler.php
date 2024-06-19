<?php

use Fiserv\Models\TransactionStatus;
use Fiserv\Models\WebhookEvent\WebhookEvent;

/**
 * Class that handles creation of webhook consumers to receive order
 * webhook event sent by Checkout Solution.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     Fiserv
 * @since      1.0.0
 */
final class WC_Fiserv_Webhook_Handler
{
    public static string $webhook_endpoint = '/fiserv_woocommerce_plugin/v1';

    /**
     * Receive event from Fiserv checkout solution
     * 
     * @param WP_REST_Request $request Event data
     * @return WP_REST_Response Reponse acknowledging sent data
     * @return WP_Error 403 Code if request has failed
     */
    public static function consume_events(WP_REST_Request $request): WP_REST_Response | WP_Error
    {
        $request_body = $request->get_body();
        $order_id = $request->get_param('wc_order_id');

        try {
            $webhook_event = new WebhookEvent($request_body);
            self::update_order($order_id, $webhook_event);

            $response = new WP_REST_Response([
                'wc_order_id' => $order_id,
                'events' => $webhook_event,
            ]);
            $response->set_status(200);

            return $response;
        } catch (Exception $e) {
            return new WP_Error('Webhook handling has failed', $e->getMessage(), ['status' => 403]);
        }
    }

    /**
     * Register POST route at /wp-json/fiserv_woocommerce_plugin/v1/api.
     * Receive from events from Fiserv checkout solution
     */
    public static function register_consume_events()
    {
        register_rest_route(self::$webhook_endpoint, '/events', [
            'methods' => 'POST',
            'callback' => [self::class, 'consume_events']
        ]);
    }

    /**
     * Store event log data into Wordpress table as order
     * meta data.
     * 
     * @param int $order_id Identifier of corresponding order
     * @param WebhookEvent $event Webhook event sent from checkout solution
     */
    private static function update_order(int $order_id, WebhookEvent $event): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception(esc_html('Order with ID ' . $order_id . ' has not been found.'));
        }

        $stored_events_list = json_decode($order->get_meta('_fiserv_plugin_webhook_event'), true);
        $events_list = [];

        if (is_array($stored_events_list)) {
            $events_list = $stored_events_list;
        }

        array_unshift($events_list, $event);

        $order->update_meta_data('_fiserv_plugin_webhook_event', json_encode($events_list));
        $ipgTransactionStatus = $event->transactionStatus;

        switch ($ipgTransactionStatus) {
            case TransactionStatus::WAITING:
                $wc_status = 'wc-on-hold';
                break;
            case TransactionStatus::PARTIAL:
                $wc_status = 'wc-processing';
                break;
            case TransactionStatus::APPROVED:
                $wc_status = 'wc-completed';
                WC_Fiserv_Logger::log($order, 'Order completed');
                $order->payment_complete();
                break;
            case TransactionStatus::PROCESSING_FAILED:
                $wc_status = 'wc-failed';
                break;
            case TransactionStatus::VALIDATION_FAILED:
                $wc_status = 'wc-failed';
                break;
            case TransactionStatus::DECLINED:
                $wc_status = 'wc-cancelled';
                break;

            default:
                break;
        }

        $wc_status_unprefixed = substr($wc_status, 3);

        if ($order->has_status('completed') || $order->has_status('cancelled')) {
            WC_Fiserv_Logger::log($order, 'Attempted to change status of order that has been processed already. Prior status: ' . $order->get_status() . ' Attempted status change: ' . $wc_status_unprefixed);
            return;
        }


        $order->update_status($wc_status, 'Transaction status changed');
        $order->add_order_note('Fiserv checkout has updated order to ' . $wc_status_unprefixed);
        WC_Fiserv_Logger::log($order, 'Order ' . $order->get_id() . ' changed to status ' . $order->get_status());

        $order->save_meta_data();
    }
}
