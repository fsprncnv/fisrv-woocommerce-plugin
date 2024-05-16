<?php

class WebhookHandler
{
    public static string $webhook_endpoint = '/wp-json/fiserv/v1/api';

    private array $event_log = [];

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_post_route']);
        add_action('rest_api_init', [$this, 'register_get_route']);
    }

    function callback_hook(WP_REST_Request $request)
    {
        $request_body = $request->get_body();

        try {
            $json = json_decode($request_body);
            array_push($this->event_log, "Message");

            $response = new WP_REST_Response($json);
            $response->set_status(200);
            return $response;
        } catch (Exception $th) {
            return new WP_Error('Something went wrong', $th->getMessage(), array('status' => 403));
        }
    }

    function register_post_route()
    {
        register_rest_route('fiserv/v1', '/api', [
            'methods' => 'POST',
            'callback' => [$this, 'callback_hook']
        ]);
    }

    function serve_event_logs()
    {
        return new WP_REST_Response($this->event_log);
    }

    function register_get_route()
    {
        register_rest_route('fiserv/v1', '/api', [
            'methods' => 'GET',
            'callback' => [$this, 'serve_event_logs']
        ]);
    }
}
