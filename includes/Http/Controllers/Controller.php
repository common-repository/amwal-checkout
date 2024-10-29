<?php

namespace Amwal\Http\Controllers;

use Amwal\Models\Address;
use Amwal\Models\Order;
use Amwal\Models\OrderRequest;
use Amwal\Models\User;
use Amwal\Services\UserService;
use Exception;
use TypeError;
use WP_REST_Request;
use WP_REST_Response;
use Amwal\SentryReporter\SentryExceptionReport;


abstract class Controller
{

    const AMWALWC_API_NAMESPACE = 'vendor/amwal/v1';


    /**
     * Route namespace.
     *
     * @var string
     */
    protected $namespace = self::AMWALWC_API_NAMESPACE;

    /**
     * Route name.
     *
     * @var string
     */
    protected $route;

    /**
     * Route methods.
     *
     * @var string
     */
    protected $method;
    /**
     * Route permission callback.
     *
     * @var string
     */
    protected $permissionCallback = '__return_true';
    private $userService;

    public $amwal_cart_id;

	public SentryExceptionReport $sentryExceptionReport;

	public function __construct(UserService $userService = NULL)
    {
        $this->userService = $userService ?: new  UserService();
        $this->sentryExceptionReport = new  SentryExceptionReport();
    }

	public static function amwalwc_update_order_details( $amwal_transaction_id, $body ) {
		if ( ! empty( $amwal_transaction_id ) ) {
			$url = AMWALWC_TRANSACTION_DETAILS . $amwal_transaction_id . '/set_order_details';
			wp_remote_post( $url, array(
					'method' => 'POST',
					'body'   => $body,
				)
			);
		}
	}

    /**
     * Route handler function.
     *
     * @param  WP_REST_Request  $request  JSON request.
     */
    abstract public function handle($request);

    /**
     * @return string
     */
    public function get_route()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function get_method()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function get_namespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function get_permission_callback()
    {
        return $this->permissionCallback;
    }

    public function WCBasicAuth()
    {
        return apply_filters( 'determine_current_user', false );
    }

    /**
     * @param $order_schema
     * @param $amwal_order_id
     * @return array
     * @throws Exception
     */
    public function amwalwc_order_core($order_schema): array
    {
        try {
            $order_request = new OrderRequest(
                User::from_json($order_schema['user']),
                Order::from_json($order_schema['order']),
                isset($order_schema['order']['shipto']) ? Address::from_json($order_schema['order']['shipto']) : null
            );
        } catch (TypeError $ex) {
            throw new Exception($ex);
        }
        if (is_wp_error($order_request)) {
            throw new Exception('Unhandled exception in order request');
        }
        $user_id = $this->userService->get_or_create($order_request->get_user());
        return array($order_request, $user_id);
    }

	public function get_order_by_amwal_transaction_id( $transaction_id, $limit = null ) {
		try {
			$orders = wc_get_orders( [
				'limit'      => $limit ?? "",
				'meta_key'   => 'amwal_transaction_id',
				'meta_value' => $transaction_id,
			] );
		} catch ( Exception $e ) {
			return $this->order_creation_error_response( 'Could not get order by transaction id', $e );
		}
		return $orders;
	}

	public function get_shipping_methods( $cart ) {
		$shipping_data = [];
		if ($cart->needs_shipping()){
			$packages_keys = array_keys( $cart->get_shipping_packages() );
//        $shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );
//        $shipping_tax_class_is_zero = $shipping_tax_class == 'zero-rate' || $shipping_tax_class == '%d9%85%d8%b9%d8%af%d9%84-%d8%b5%d9%81%d8%b1';
			$amwalwc_exclude_shipping_method_placement = amwalwc_get_option_or_set_default( AMWALWC_SETTING_EXCLUDE_SHIPPING_METHODS, array() );
			foreach ( $packages_keys as $key ) {
				$shipping_rates = WC()->session->get( 'shipping_for_package_' . $key )['rates'];
				if (is_array($shipping_rates) || is_object($shipping_rates)) {
					foreach ( $shipping_rates as $rate_key => $rate ) {
						if ( is_array( $amwalwc_exclude_shipping_method_placement ) && in_array( $rate->method_id, $amwalwc_exclude_shipping_method_placement ) ) {
							continue;
						}

						$decimals = wc_get_price_decimals();
						$price    = (float) $rate->cost;
						if ( count( $rate->taxes ) > 0 ) {
							$price += (float) array_sum( $rate->taxes );
						}
						$shipping_data[] = [
							"id"    => $rate->id,
							"label" => $rate->label,
							"price" => round( $price, $decimals ),
						];
					}
				}
			}
		}
		return $shipping_data;
	}

	protected function amwalwc_build_order_schema($transaction)
    {
        try {
            $user = [
                "email" => $transaction["client_email"] ?? "",
                "first_name" => $transaction["client_first_name"] ?? "",
                "last_name" => $transaction["client_last_name"] ?? "",
                "phone_number" => $transaction["client_phone_number"] ?? "",
            ];

            $schema = [
                "user" => $user,
                "order" => []
            ];

            if (!empty($transaction['shipping_details'])) {
                $shipping_details = $transaction['shipping_details'];
                $schema["order"]["shipping"] = [
                    "rate_id" => $shipping_details['id'],
                    "title" => $shipping_details['label'],
                    "cost_cents" => $shipping_details['price'],
                ];
            }

            if (!empty($transaction['address_details'])) {
                $addressDetails = $transaction['address_details'];
                if (!empty($addressDetails['email'])) {
                    $schema['user']['email'] = $addressDetails['email'];
                }
//                $street = "$addressDetails[street1], $addressDetails[street2]";
                $schema['order']['shipto'] = [
                    "recipient" => $transaction["client_phone_number"] ?? "",
                    "phone" => $transaction["client_phone_number"] ?? "",
                    "address1" => $addressDetails["street1"] ?? "",
                    "address2" => $addressDetails["street2"] ?? "",
                    "city" => $addressDetails['city'] ?? "",
                    "postcode" => $addressDetails['postcode'] ?? "12345",
                    "state" => $addressDetails['state_code'] ?? $addressDetails['state'] ?? "",
                    "country" => $addressDetails['country'] ?? "",
                    "notes" => "",
                    "first_name" => $transaction['client_first_name'] ?? "",
                    "last_name" => $transaction['client_last_name'] ?? ""
                ];
                $schema['user']['address_details'] = [
                    "street1" => $addressDetails['street1'] ?? "",
                    "street2" => $addressDetails['street2'] ?? "",
                    "zip" => $addressDetails['postcode'] ?? "12345",
                    "city" => $addressDetails['city'] ?? "",
                    "state" => $addressDetails['state_code'] ?? $addressDetails['state'] ?? "",
                    "country" => $addressDetails['country'] ?? "",
                ];
            }
            return $schema;
        } catch (Exception $e) {
            return $e;
        }
    }

    protected function order_creation_error_response($message, $full_error = NULL)
    {
        amwalwc_report_error($this->amwal_cart_id, ["message" => $message]);
	    if (!empty($full_error) && $full_error instanceof Exception){
		    $thrown_exception = $full_error;
	    } else {
		    $thrown_exception = new Exception( empty($full_error) ? $message: $message .' : '.$full_error);
	    }
		$this->sentryExceptionReport->reportException($thrown_exception,$this->amwal_cart_id, );
        return new WP_REST_Response($message, 500);
    }

	public static function get_transaction_details($amwal_transaction_id) {
		try{
		    $url = AMWALWC_TRANSACTION_DETAILS . $amwal_transaction_id;

		    $response = wp_remote_get($url);

		    // Check for errors in the request
		    if (is_wp_error($response)) {
			    return ['error' ,'There was an error in the HTTP request: ' . $response->get_error_message(), $response['body']];
		    }

		    // Check for non-200 HTTP response code
		    if (wp_remote_retrieve_response_code($response) != 200) {
			    return ['error','The HTTP request returned a non-200 response code: ' . wp_remote_retrieve_response_code($response), $response['body']];
		    }

		    // Retrieve the body of the response
		    $body = wp_remote_retrieve_body($response);

		    // Parse the JSON response
		    $data = json_decode($body, true);

		    // Check for errors in parsing the JSON
		    if (json_last_error() !== JSON_ERROR_NONE) {
			    return ['error','There was an error parsing the JSON response: ' . json_last_error_msg(),''];
		    }

		} catch (Exception $e){
			return ['error','There was an error parsing the JSON response', $e];
		}
		return ['success',$data,''];
    }
	function getInvalidOrderStatus(){
		$pending_status   = apply_filters( 'woocommerce_default_order_status', 'pending' );
		$cancelled_status = apply_filters( 'woocommerce_default_order_status', 'cancelled' );
		$on_hold_status   = apply_filters( 'woocommerce_default_order_status', 'on-hold' );
		$failed_status    = apply_filters( 'woocommerce_default_order_status', 'failed' );

		return array( $pending_status, $cancelled_status, $failed_status, $on_hold_status );
	}

}
