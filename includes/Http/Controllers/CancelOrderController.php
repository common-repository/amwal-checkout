<?php

namespace Amwal\Http\Controllers;


use WP_REST_Request;
use WP_REST_Response;
use function wc_get_orders;

class CancelOrderController extends Controller
{

    protected $namespace = 'wc/amwal/v2';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'orders/cancel';

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
    public function handle($request){
        $body = $request->get_json_params();
        if (array_key_exists("amwal_transaction_id", $body)) {
            $transaction_details = $this->check_transaction_status( $body['amwal_transaction_id'] );
            if(!empty($transaction_details) &&
                $transaction_details['status'] != 'success' // &&
//                $transaction_details['status'] != 'fail'
            ){
                WC()->cart->empty_cart();
                return new WP_REST_Response("Deleted Successfully", 201);
            }
        }

        return new WP_REST_Response("Done Successfully", 201);
    }

    private function check_transaction_status($amwal_transaction_id)
    {
        $url = AMWALWC_TRANSACTION_DETAILS . $amwal_transaction_id;
        $res = wp_remote_retrieve_body( wp_remote_get( $url ) );
        return json_decode($res,true);
    }

	public function get_permission_callback()
	{
		return $this->WCBasicAuth();
	}
}
