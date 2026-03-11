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
    public static string $plugin_rest_path = '/fiserv_woocommerce_plugin/v1';

    /**
     * Register endpoint for health check
     *
     * @return void
     */
    public static function register_health_report(): void
    {
        register_rest_route(
            self::$plugin_rest_path,
            '/health',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => function (WP_REST_Request $request) {
                    return new WP_REST_Response(
                        WC_Fiserv_Checkout_Handler::get_health_report(
                            [
                                'is_prod' => $request->get_param('is_prod') === 'yes',
                                'api_key' => $request->get_param('api_key'),
                                'api_secret' => $request->get_param('api_secret'),
                                'store_id' => $request->get_param('store_id'),
                            ]
                        )
                    );
                },
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            )
        );
    }

    /**
     * Callback for payment method icon addition
     *
     * @param  WP_REST_Request $request
     * @throws \Exception
     * @return WP_REST_Response|WP_Error
     */
    public static function add_image(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $gateway_id = $request->get_param('gateway-id');
            $img_url = $request->get_param('data');

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

            if (self::is_image_url($img_url)) {
                throw new Exception("Image URL is invalid");
            }

            if (in_array($img_url, $decoded_list)) {
                throw new Exception("Image is already in list");
            }

            array_push($decoded_list, $img_url);
            $encoded_list = wp_json_encode($decoded_list);
            $gateway->update_option('custom_icon', $encoded_list);

        } catch (\Throwable $th) {
            return new WP_REST_Response(
                array(
                    'status' => 'error',
                    'message' => $th->getMessage(),
                    'icons' => $encoded_list
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
     * Validate that a URL points to an image (server-side).
     * Returns array: [ 'ok' => bool, 'content_type' => string|null, 'status' => int|null, 'reason' => string|null ]
     */
    private static function is_image_url($url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $args = [
            'timeout' => 8,
            'redirection' => 5,
            'headers' => [
                'Range' => 'bytes=0-0',
                'Accept' => 'image/*,*/*;q=0.8',
            ],
        ];
        $res = wp_remote_head($url, $args);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) < 200) {
            $res = wp_remote_get($url, $args);
        }
        if (is_wp_error($res)) {
            return false;
        }
        $status = wp_remote_retrieve_response_code($res);
        $ct = wp_remote_retrieve_header($res, 'content-type');
        $is_image_ct = false;
        if (is_string($ct)) {
            $ct = strtolower(trim($ct));
            $is_image_ct = str_starts_with($ct, 'image/')
                || in_array($ct, ['text/xml', 'application/xml'], true); // some servers misreport SVG
        }
        return ($status >= 200 && $status < 300) && $is_image_ct;
    }


    /**
     * Register endpoint for payment icon addition
     *
     * @return void
     */
    public static function register_add_image(): void
    {
        register_rest_route(
            self::$plugin_rest_path,
            '/image',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(self::class, 'add_image'),
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            )
        );
    }

    /**
     * Callback for custom payment icon removal
     *
     * @param  WP_REST_Request $request
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
     *
     * @return void
     */
    public static function register_remove_image(): void
    {
        register_rest_route(
            self::$plugin_rest_path,
            '/image',
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array(self::class, 'remove_image'),
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            )
        );
    }

    /**
     * Register endpoint for checkout solution details report
     *
     * @return void
     */
    public static function register_checkout_report(): void
    {
        register_rest_route(
            self::$plugin_rest_path,
            '/checkout-details',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => function (WP_REST_Request $request) {
                    $checkout_id = (string) $request->get_param('checkout-id');
                    return WC_Fiserv_Checkout_Handler::get_checkout_details($checkout_id);
                },
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            )
        );
    }
}
