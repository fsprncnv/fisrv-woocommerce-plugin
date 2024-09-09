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

	public static function health_report(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		return new WP_REST_Response(WC_Fisrv_Checkout_Handler::get_health_report());
	}

	public static function register_health_report(): void
	{
		register_rest_route(
			self::$webhook_endpoint,
			'/health',
			array(
				'methods' => 'GET',
				'callback' => array(self::class, 'health_report'),
			)
		);
	}
}
