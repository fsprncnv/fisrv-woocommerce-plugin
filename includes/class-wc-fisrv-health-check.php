<?php

/**
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.1
 */
final class WC_Fisrv_Health_Check
{

	public static string $webhook_endpoint = '/fisrv_woocommerce_plugin/v1';

	public static function register_health_report(): void
	{
		register_rest_route(
			self::$webhook_endpoint,
			'/health',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => function () {
					return new WP_REST_Response(WC_Fisrv_Checkout_Handler::get_health_report());
				},
			)
		);
	}

	public static function add_image(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		try {
			$gateway_id = $request->get_param('gateway-id');
			$data = $request->get_param('data');

			$gateway = WC()->payment_gateways()->payment_gateways()[$gateway_id] ?? false;

			if (!$gateway) {
				return new WP_REST_Response([
					'status' => 'error',
					'gateway_id' => $gateway_id,
					'error' => 'Gateway ID invalid',
					'list' => json_encode((new WC_Payment_Gateways)->payment_gateways())
				]);
			}

			$list_json = $gateway->get_option('custom_icon');
			$decoded_list = json_decode($list_json, true) ?? [];
			array_push($decoded_list, $data);

			$gateway->update_option('custom_icon', json_encode($decoded_list));

		} catch (\Throwable $th) {
			return new WP_REST_Response([
				'status' => 'error',
				'error' => $th->getMessage()
			]);
		}

		return new WP_REST_Response([
			'status' => 'ok'
		]);
	}

	public static function register_add_image(): void
	{
		register_rest_route(
			self::$webhook_endpoint,
			'/image',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array(self::class, 'add_image'),
			)
		);
	}

	public static function remove_image(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		try {
			$gateway_id = $request->get_param('gateway-id');
			$icon_id = $request->get_param('icon-id');
			$gateway = WC()->payment_gateways()->payment_gateways()[$gateway_id] ?? false;

			if (!$gateway) {
				return new WP_REST_Response([
					'status' => 'error',
					'gateway_id' => $gateway_id,
					'error' => 'Gateway ID invalid',
					'list' => json_encode((new WC_Payment_Gateways)->payment_gateways())
				]);
			}

			$list_json = $gateway->get_option('custom_icon');
			$decoded_list = json_decode($list_json, true) ?? [];
			unset($decoded_list[$icon_id]);

			$gateway->update_option('custom_icon', json_encode($decoded_list));

		} catch (\Throwable $th) {
			return new WP_REST_Response([
				'status' => 'error',
				'error' => $th->getMessage()
			]);
		}

		return new WP_REST_Response([
			'status' => 'ok'
		]);
	}

	public static function register_remove_image(): void
	{
		register_rest_route(
			self::$webhook_endpoint,
			'/image',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array(self::class, 'remove_image'),
			)
		);
	}

	public static function register_restore_settings(): void
	{
		register_rest_route(
			self::$webhook_endpoint,
			'/restore-settings',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => function (WP_REST_Request $request) {
					$gateway_id = (string) $request->get_param('gateway-id');
					$gateway = WC()->payment_gateways()->payment_gateways()[$gateway_id] ?? false;

					if (!$gateway) {
						return;
					}

					update_option("woocommerce_{$gateway_id}_settings", '');
				}
			)
		);
	}

}
