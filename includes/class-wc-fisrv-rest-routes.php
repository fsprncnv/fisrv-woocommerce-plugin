<?php

/**
 *
 * @package  WooCommerce
 * @category Payment Gateways
 * @author   fisrv
 * @since    1.0.1
 */
final class WC_Fisrv_Rest_Routes
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
                return new WP_REST_Response(
                    array(
                        'status' => 'error',
                        'gateway_id' => $gateway_id,
                        'error' => 'Gateway ID invalid',
                        'list' => json_encode((new WC_Payment_Gateways())->payment_gateways()),
                    )
                );
            }

            $list_json = $gateway->get_option('custom_icon');
            $decoded_list = json_decode($list_json, true) ?? array();
            array_push($decoded_list, $data);

            $gateway->update_option('custom_icon', json_encode($decoded_list));

        } catch (\Throwable $th) {
            return new WP_REST_Response(
                array(
                    'status' => 'error',
                    'error' => $th->getMessage(),
                )
            );
        }

        return new WP_REST_Response(
            array(
                'status' => 'ok',
            )
        );
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
                return new WP_REST_Response(
                    array(
                        'status' => 'error',
                        'gateway_id' => $gateway_id,
                        'error' => 'Gateway ID invalid',
                        'list' => json_encode((new WC_Payment_Gateways())->payment_gateways()),
                    )
                );
            }

            $list_json = $gateway->get_option('custom_icon');
            $decoded_list = json_decode($list_json, true) ?? array();
            unset($decoded_list[$icon_id]);

            $gateway->update_option('custom_icon', json_encode($decoded_list));

        } catch (\Throwable $th) {
            return new WP_REST_Response(
                array(
                    'status' => 'error',
                    'error' => $th->getMessage(),
                )
            );
        }

        return new WP_REST_Response(
            array(
                'status' => 'ok',
            )
        );
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
                        WC_Fisrv_Logger::generic_log('Restore settings failed: Could not find gateway');
                        return;
                    }

                    WC_Fisrv_Logger::generic_log("Setting woocommerce_{$gateway_id}_settings to empty string");

                    try {
                        // $gateway_enabled = $gateway->get_option("enabled") ?? 'no';
                        // update_option("woocommerce_{$gateway_id}_settings", '');
                        // sanitize_option("woocommerce_{$gateway_id}_settings", '');
                        // $gateway->update_option("enabled", $gateway_enabled);
                        $gateway->update_option('description', $gateway->description);

                        return new WP_REST_Response([
                            'status' => 'ok',
                            'message' => "Successfully reverted settings of {$gateway_id} to default"
                        ]);
                    } catch (\Throwable $th) {
                        return new WP_REST_Response([
                            'status' => 'error',
                            'message' => $th->getMessage(),
                        ]);
                    }
                },
            )
        );
    }

    public static function register_checkout_report(): void
    {
        register_rest_route(
            self::$webhook_endpoint,
            '/checkout-details',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => function (WP_REST_Request $request) {
                    $checkout_id = (string) $request->get_param('checkout-id');
                    return new WP_REST_Response(WC_Fisrv_Checkout_Handler::get_checkout_details($checkout_id));
                },
            )
        );
    }
}
