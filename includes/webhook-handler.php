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
        $order_id = $request->get_param('wc_order_id');

        try {
            array_push(self::$event_log, "Event at " . time());
            $json = json_decode($request_body, true);

            $response = new WP_REST_Response([
                'wc_order_id' => $order_id,
                'event' => $json,
            ]);
            $response->set_status(200);

            $webhook_event = new WebhookEvent($json);
            self::update_order($order_id, $webhook_event);

            return $response;
        } catch (Exception $e) {
            return new WP_Error('Webhook handling has failed', $e->getMessage(), ['status' => 403]);
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
        $table_name = $wpdb->prefix . 'wc_orders_meta';

        // $res = wp_cache_get('_meta_data_query_cache');
        // if (!$res || count($res) === 0) {
        // }

        $query = ("select meta_value, order_id from " . $table_name . " where meta_key = '_fiserv_plugin_webhook_event'");
        $res = $wpdb->get_results($query); // db ok // no cache ok

        $out = [];

        foreach ($res as $entry) {
            if ($entry->meta_value === null) {
                continue;
            }

            array_push(
                $out,
                [
                    'received_at' => $entry->order_id,
                    'wc_order_id' => $entry->order_id,
                    'event' => json_decode($entry->meta_value),
                ]
            );
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
     * @param WebhookEvent $event Webhook event sent from checkout solution
     */
    private static function update_order(int $order_id, WebhookEvent $event): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception(esc_html('Order with ID ' . $order_id . ' has not been found.'));
        }

        $order->update_meta_data('_fiserv_plugin_webhook_event', $event);

        $ipgTransactionStatus = (string) $event->transactionStatus;
        $wc_status = self::$wc_fiserv_status_map[$ipgTransactionStatus];

        if ($order->has_status('wc-completed') || $order->has_status('wc-cancelled')) {
            WCLogger::log($order, 'Attempted to change status of order that has been processed already. Prior status: ' . $order->get_status() . 'Attempted status change: ' . $wc_status);
            return;
        }

        if ($ipgTransactionStatus === 'APPROVED') {
            WCLogger::log($order, 'Order completed');
            $order->payment_complete();
        }

        $order->update_status($wc_status, 'Transaction status changed');
        WCLogger::log($order, 'Order' . $order->get_id() . 'changed to status ' . $order->get_status());

        $order->save_meta_data();
    }

    /**
     * Map mapping ipg checkout solution transactionStatus values 
     * to WC status
     * @todo Subject to change
     */
    public static array $wc_fiserv_status_map = [
        'WAITING' =>            'wc-on-hold',
        'PARTIAL' =>            'wc-processing',
        'APPROVED' =>           'wc-completed',
        'PROCESSING_FAILED' =>  'wc-failed',
        'VALIDATION_FAILED' =>  'wc-failed',
        'DECLINED' =>           'wc-cancelled',
    ];
}
