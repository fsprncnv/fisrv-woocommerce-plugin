<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 *
 * @package  WooCommerce
 * @category Payment Gateways
 * @author   fiserv
 * @since    1.0.1
 */
final class WC_Fiserv_Rest_Routes
{
    public static string $webhook_endpoint = '/fiserv_woocommerce_plugin/v1';

    /**
     * Register endpoint for health check
     * @return void
     */
    public static function register_health_report(): void
    {
        register_rest_route(
            self::$webhook_endpoint,
            '/health',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => function (WP_REST_Request $request) {
                    return new WP_REST_Response(
                        WC_Fiserv_Checkout_Handler::get_health_report(
                            [
                                'is_prod' => $request->get_param('is_prod') === 'yes' ? true : false,
                                'api_key' => $request->get_param('api_key'),
                                'api_secret' => $request->get_param('api_secret'),
                                'store_id' => $request->get_param('store_id'),
                            ]
                        )
                    );
                },
            )
        );
    }

    /**
     * Callback for payment method icon addition
     * @param WP_REST_Request $request
     * @throws \Exception
     * @return WP_REST_Response|WP_Error
     */
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
                        'list' => wp_json_encode((new WC_Payment_Gateways())->payment_gateways()),
                    )
                );
            }

            $list_json = $gateway->get_option('custom_icon');
            $decoded_list = json_decode($list_json, true) ?? array();

            if (!preg_match("/(http)?s?:?(\/\/[^\"']*\.(?:png|jpg|jpeg|gif|png|svg))/i", $data)) {
                throw new Exception("Image URL is invalid");
            }

            if (in_array($data, $decoded_list)) {
                throw new Exception("Image is already in list");
            }

            array_push($decoded_list, $data);
            $gateway->update_option('custom_icon', wp_json_encode($decoded_list));

        } catch (\Throwable $th) {
            return new WP_REST_Response(
                array(
                    'status' => 'error',
                    'message' => $th->getMessage(),
                )
            );
        }

        return new WP_REST_Response(
            array(
                'status' => 'ok',
                'message' => 'Icon added successfully!',
            )
        );
    }

    /**
     * Register endpoint for payment icon addition
     * @return void
     */
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

    /**
     * Callback for custom payment icon removal
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
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
                        'list' => wp_json_encode((new WC_Payment_Gateways())->payment_gateways()),
                    )
                );
            }

            $list_json = $gateway->get_option('custom_icon');
            $decoded_list = json_decode($list_json, true) ?? array();
            unset($decoded_list[$icon_id]);

            $gateway->update_option('custom_icon', wp_json_encode($decoded_list));

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


    /**
     * Register endpoint for payment icon removal
     * @return void
     */
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

    /**
     * Register endpoint for checkout solution details report
     * @return void
     */
    public static function register_checkout_report(): void
    {
        register_rest_route(
            self::$webhook_endpoint,
            '/checkout-details',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => function (WP_REST_Request $request) {
                    $checkout_id = (string) $request->get_param('checkout-id');
                    return new WP_REST_Response(WC_Fiserv_Checkout_Handler::get_checkout_details($checkout_id));
                },
            )
        );
    }
}
