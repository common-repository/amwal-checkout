<?php

namespace Amwal\Http\Controllers;

use WP_REST_Request;
use WP_REST_Response;


class OrderHealthController extends Controller
{

    public $amwal_cart_id;
    protected $namespace = 'wc/amwal/v2';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'orders/health';
    /**
     * Route methods.
     *
     * @var string
     */
    protected $method = 'POST';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handle($request): WP_REST_Response
    {
        $body = $request->get_body();
        $auth_response = $this->amwalwc_is_authorized($body);
        $response_code = wp_remote_retrieve_response_code($auth_response);
        if (!in_array($response_code, array(200, 201))) {
            return new WP_REST_Response(wp_remote_retrieve_response_message($auth_response), $response_code);
        }
        $result = [];
        $week_date = date("Y-m-d", strtotime("-1 week"));
        $args = [
            'date_created' => '>=' . $week_date
        ];
        $orders = wc_get_orders($args);
        foreach ($orders as $order) {
			if(method_exists($order,"get_payment_method")){
				$result[] = [
					'gateway' => $order->get_payment_method(),
					'status' => $order->get_status(),
					'total' => $order->get_total()
				];
			}

        }
        return new WP_REST_Response($result, 201);
    }


    private function amwalwc_is_authorized($body)
    {
        $wp_request_url = AMWALWC_SERVER_URI . '/admin/verify_token';
        return wp_remote_request(
            $wp_request_url,
            array(
                'method' => 'POST',
                'body' => $body
            )
        );
    }

    public function get_permission_callback()
    {
        return $this->WCBasicAuth();
    }

}
