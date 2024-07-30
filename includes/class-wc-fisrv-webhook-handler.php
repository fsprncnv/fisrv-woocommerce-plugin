<?php

use Fisrv\Models\TransactionStatus;
use Fisrv\Models\WebhookEvent\WebhookEvent;

/**
 * Class that handles creation of webhook consumers to receive order
 * webhook event sent by Checkout Solution.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
final class WC_Fisrv_Webhook_Handler
{

	public static string $webhook_endpoint = '/fisrv_woocommerce_plugin/v1';

	/**
	 * Receive event from fisrv checkout solution
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Event data
	 * @return WP_REST_Response Reponse acknowledging sent data
	 * @return WP_Error 403 Code if request has failed
	 */
	public static function consume_events(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		$request_body = $request->get_body();
		$order_id = $request->get_param('wc_order_id');

		if (!is_string($order_id)) {
			throw new Exception(esc_html__('Query parameter (order ID) is malformed', 'fisrv-checkout-for-woocommerce'));
		}

		try {
			$webhook_event = new WebhookEvent($request_body);
			self::update_order($order_id, $webhook_event);

			$response = new WP_REST_Response(
				array(
					'wc_order_id' => $order_id,
					'events' => $webhook_event,
				)
			);
			$response->set_status(200);

			return $response;
		} catch (Exception $e) {
			return new WP_Error('Webhook handling has failed', $e->getMessage(), array('status' => 403));
		}
	}

	/**
	 * Register POST route at /wp-json/fisrv_woocommerce_plugin/v1/api.
	 * Receive from events from fisrv checkout solution
	 */
	public static function register_consume_events(): void
	{
		register_rest_route(
			self::$webhook_endpoint,
			'/events',
			array(
				'methods' => 'POST',
				'callback' => array(self::class, 'consume_events'),
			)
		);
	}

	/**
	 * Store event log data into WordPress table as order
	 * meta data.
	 *
	 * @param string $order_id Identifier of corresponding order
	 * @param WebhookEvent $event Webhook event sent from checkout solution
	 * @throws Exception Order not found
	 */
	private static function update_order(string $order_id, WebhookEvent $event): void
	{
		$order = wc_get_order($order_id);

		if (!$order instanceof WC_Order) {
			/* translators: %s: Order ID */
			throw new Exception(esc_html(sprintf(__('Order with ID %s has not been found.', 'fisrv-checkout-for-woocommerce'), $order_id)));
		}

		$stored_events_list = json_decode(strval($order->get_meta('_fisrv_plugin_webhook_event')), true);
		$events_list = array();

		if (is_array($stored_events_list)) {
			$events_list = $stored_events_list;
		}

		array_unshift($events_list, $event);
		$event_list_json = wp_json_encode($events_list);

		if (is_string($event_list_json)) {
			$order->update_meta_data('_fisrv_plugin_webhook_event', $event_list_json);
		}

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
				WC_Fisrv_Logger::log($order, 'Order completed');
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
				$wc_status = 'wc-pending';

				break;
		}

		$wc_status_unprefixed = substr($wc_status, 3);

		if ($order->has_status('completed') || $order->has_status('cancelled')) {
			/* translators: %1$s: Prior status ID %2$s: Current status */
			WC_Fisrv_Logger::log($order, sprintf(__('Attempted to change status of order that has been processed already. Prior status: %1$s Attempted status change: %2$s', 'fisrv-checkout-for-woocommerce'), $order->get_status(), $wc_status_unprefixed));

			return;
		}

		$order->update_status($wc_status, __('Transaction status changed', 'fisrv-checkout-for-woocommerce'));
		/* translators: %s: Staus without prefix */
		$order->add_order_note(sprintf(__('Fisrv checkout has updated order to %s', 'fisrv-checkout-for-woocommerce'), $wc_status_unprefixed));
		/* translators: %1$s: Order ID %2$s: Order status */
		WC_Fisrv_Logger::log($order, sprintf(__('Order %1$s changed to status %2$s', 'fisrv-checkout-for-woocommerce'), $order->get_id(), $order->get_status()));

		$order->save_meta_data();
	}
}
