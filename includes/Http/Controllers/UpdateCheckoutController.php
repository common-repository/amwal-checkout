<?php

namespace Amwal\Http\Controllers;

use Exception;
use WC_Customer;
use WP_REST_Request;
use WP_REST_Response;
use function is_wp_error;


class UpdateCheckoutController extends Controller
{

    public $amwal_cart_id;
    protected $namespace = 'wc/amwal/v2';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'checkout/update';
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
    public function handle($request)
    {
        $body = $request->get_json_params();
        $cart = WC()->cart;
        if (array_key_exists("amwal_cart_id", $body)) {
            $this->amwal_cart_id = $body['amwal_cart_id'];
            try {
                $order_schema = $this->amwalwc_build_order_schema($body);

	            if ( is_wp_error( $order_schema ) || $order_schema instanceof Exception ) {
		            return $this->order_creation_error_response( 'Could not build order schema', $order_schema );
	            }

                [$order_request, $user_id] = $this->amwalwc_order_core($order_schema);
				if ( is_wp_error( $order_request ) || $order_request instanceof Exception ) {
					return $this->order_creation_error_response( 'Could not create order schema', $order_request );
				}
                WC()->customer = new WC_Customer($user_id,true);
                $address = $order_request->get_ship_to();
                if ($address) {
                    WC()->customer->set_props($address->to_customer_address_props());
                }
				$orderShipping = $order_request->get_order()->get_shipping();
                if ($orderShipping != NULL) {
                    WC()->session->set('chosen_shipping_methods', [$orderShipping->get_rate_id()]);
                }
                WC()->cart->calculate_totals();
                $shipping_list = $this->get_shipping_methods($cart);
				if (get_option('amwal_checkout_address_required_in_virtual_product') && !$cart->needs_shipping()) {
					$shipping_list = [[
						"id"    => 'no_shipping',
						"label" => 'free shipping',
						"price" => '0',
					]];
				}
	            $discount       = amwalwc_calculate_cart_discounts();
	            $amount         = $cart->get_cart_contents_total() + $cart->get_fee_total() + $discount;
	            $taxes          = $cart->get_cart_contents_tax() + $cart->get_fee_tax();
            } catch (Exception $e) {
                return $this->order_creation_error_response('Unexpected error in saving shipping details', $e);
            }
            return new WP_REST_Response([
                "shipping_methods" => $shipping_list,
                "taxes" => $taxes,
                "discount" => $discount,
                "amount" => $amount
            ], 201);
        }
        return $this->order_creation_error_response('Could not find amwal cart id');

    }


	public function get_permission_callback()
    {
        return $this->WCBasicAuth();
    }

}
