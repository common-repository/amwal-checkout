<?php

namespace Amwal\Http\Controllers;

use WP_REST_Request;
use WP_REST_Response;

class ProductDetailsController extends Controller
{
    protected $namespace = 'wc/amwal/v1';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'product_details';

    /**
     * Route methods.
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * @param  WP_REST_Request  $request
     *
     * @return WP_REST_Response
     */
    public function handle($request)
    {
	    $body = $request->get_json_params();
	    if (array_key_exists("product_id", $body)) {
		    $product = wc_get_product( $body['product_id'] );
		    return new WP_REST_Response($product->get_data(), 200);
	    }
	    return new WP_REST_Response( "Bad Request", 500);
    }


    public function get_permission_callback()
    {
        return $this->WCBasicAuth();
    }
}
