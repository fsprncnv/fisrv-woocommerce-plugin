<?php

class WebhookHandler
{
    public static string $webhook_endpoint = '/fiserv_woocommerce_plugin/v1';
    private static array $event_log = [];
    public static $instance;
    public static int $log_size = 0;
    public static $self_hash_reference;

    public static function get()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        self::$instance = $this;
        add_action('rest_api_init', [$this, 'register_post_route']);
        add_action('rest_api_init', [$this, 'register_get_route']);

        self::$self_hash_reference = spl_object_hash($this);
        self::$log_size = count(self::$event_log);
    }

    /**
     * Receive event from Fiserv checkout solution
     * 
     * @param WP_REST_Request $request Event data
     * @return WP_REST_Response Reponse acknowledging sent data
     * @return WP_Error 403 Code if request has failed
     * 
     * @todo Error handling when handling response object !!
     */
    public function consume_event(WP_REST_Request $request)
    {
        $request_body = $request->get_body();

        try {
            array_push(self::$event_log, "Message at " . time());
            $json = json_decode($request_body);

            $order_id = intval($json->orderId);
            $response = new WP_REST_Response($json);
            $response->set_status(200);

            self::store_data_into_orders_meta($order_id, json_encode($json));

            return $response;
        } catch (Exception $e) {
            return new WP_Error('Something went wrong', $e->getMessage(), array('status' => 403));
        }
    }

    /**
     * Register POST route at /wp-json/fiserv_woocommerce_plugin/v1/api.
     * Receive from events from Fiserv checkout solution
     */
    public function register_post_route()
    {
        register_rest_route(self::$webhook_endpoint, '/events', [
            'methods' => 'POST',
            'callback' => [$this, 'consume_event']
        ]);
    }

    /**
     * Callback method for GET route.
     * Response data is list of saved events.
     * 
     * @return WP_REST_Response Response data
     */
    public static function serve_event_logs()
    {
        return new WP_REST_Response(self::$cur);
    }

    /**
     * Register GET route at /wp-json/fiserv_woocommerce_plugin/v1/api.
     * Display all entries of event log.
     */
    public function register_get_route()
    {
        register_rest_route(self::$webhook_endpoint, '/events', [
            'methods' => 'GET',
            'callback' => [$this, 'serve_event_logs']
        ]);
    }

    /**
     * Store event log data into Wordpress table as order
     * meta data.
     * 
     * @param int $order_id Identifier of corresponding order
     * @param string $event_data Webhook event sent from checkout solution
     */
    private static function store_data_into_orders_meta(int $order_id, string $event_data): void
    {
        global $wpdb;

        $data = [
            'order_id' => $order_id,
            'meta_key' => '_fiserv_plugin_webhook_event',
            'meta_value' => $event_data,
        ];

        $format = ['%d', '%s', '%s'];
        $insert = $wpdb->insert('wp_wc_orders_meta', $data, $format);
    }
}
