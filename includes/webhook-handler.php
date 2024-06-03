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
        add_action('rest_api_init', [$this, 'register_consume_events']);
        add_action('rest_api_init', [$this, 'register_get_events']);

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
    public function consume_events(WP_REST_Request $request)
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
    public function register_consume_events()
    {
        register_rest_route(self::$webhook_endpoint, '/events', [
            'methods' => 'POST',
            'callback' => [$this, 'consume_events']
        ]);
    }

    /**
     * Callback method for GET route.
     * Response data is list of saved events.
     * 
     * @return WP_REST_Response Response data
     */
    public static function get_events_callback(): WP_REST_Response
    {
        global $wpdb;
        $res = $wpdb->get_results("select meta_value from wp_wc_orders_meta where meta_key = '_fiserv_plugin_webhook_event'");
        $out = [];

        foreach ($res as $entry) {
            array_push($out, json_decode($entry->meta_value));
        }

        return new WP_REST_Response($out);
    }

    /**
     * Register GET route at /wp-json/fiserv_woocommerce_plugin/v1/api.
     * Display all entries of event log.
     */
    public function register_get_events()
    {
        register_rest_route(self::$webhook_endpoint, '/events', [
            'methods' => 'GET',
            'callback' => [$this, 'get_events_callback']
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
        $order = new WC_Order($order_id);
        $order->update_meta_data('_fiserv_plugin_webhook_event', $event_data);
    }

    public function register_button_dispatcher()
    {
        register_rest_route(self::$webhook_endpoint, '/fetch-checkout', [
            'methods' => 'GET',
            'callback' => 'button_dispatcher_callback',
        ]);
    }

    public function button_dispatcher_callback(): void
    {
    }
}
