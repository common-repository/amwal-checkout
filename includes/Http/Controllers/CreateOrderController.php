<?php

namespace Amwal\Http\Controllers;
use Exception;
use WC_Customer;
use WC_Discounts;
use WP_REST_Request;
use WP_REST_Response;
use WC_Coupon;


class CreateOrderController extends Controller
{

    protected $namespace = 'wc/amwal/v2';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'order/create';

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
        if (array_key_exists("amwal_cart_id", $body)) {
            $this->amwal_cart_id = $body['amwal_cart_id'];
            $amwal_order_id = array_key_exists("amwal_order_id", $body) ? $body['amwal_order_id'] : null;
            try {
	            $transaction_details = $body['transaction_details'];
	            [$validationStatus, $validationMessage] = $this->validateOrder($transaction_details);
				if(!$validationStatus){
					return $this->order_creation_error_response($validationMessage);
				}
                if (!empty($amwal_order_id)) {
                    $order_res = wc_get_order($amwal_order_id);
                } else {
                    $order_schema = $this->amwalwc_build_order_schema($transaction_details);
	                if ( is_wp_error( $order_schema ) || $order_schema instanceof Exception ) {
		                return $this->order_creation_error_response( 'Could not create order schema', $order_schema );
	                }
                    [$order_request, $user_id] = $this->amwalwc_order_core($order_schema);
					if ( is_wp_error( $order_request ) || $order_request instanceof Exception ) {
						return $this->order_creation_error_response( 'Could not create order request', $order_request );
					}
                    $address = $order_request->get_ship_to();
                    if(!empty($address)) {
						WC()->customer = !empty($user_id) ? new WC_Customer( $user_id ) : new WC_Customer();
                        WC()->customer->set_props($address->to_customer_address_props());
                        WC()->customer->save();
	                    $orderShipping = $order_request->get_order()->get_shipping();
						if(!empty($orderShipping)){
							WC()->session->set('chosen_shipping_methods', [$orderShipping->get_rate_id()]);
						}
                        WC()->cart->calculate_totals();
                    }
                    $checkout = WC()->checkout();
					$order_id = $checkout->create_order( [
						'payment_method' => AMWALWC_PAYMENT_METHOD_ID
					] );
                    if(is_wp_error($order_id)){
                        $message = htmlspecialchars_decode( $order_id->get_error_message());
	                    return $this->order_creation_error_response( $message);
                    }
                    $order_res = wc_get_order($order_id);
                    if($address) {
						$address_props = $address->to_address_props();
                        $order_res->set_address($address_props, 'shipping');
                        $order_res->set_address($address_props);
                    }
                    $order_res->set_customer_id(WC()->customer->get_id());
                    $order_res->set_payment_method(AMWALWC_PAYMENT_METHOD_ID);
                    $order_res->set_payment_method_title('Quick Checkout');
//                    $order_res->calculate_totals();
                }
				if(empty($order_res->get_meta('amwal_transaction_data'))) {
					$order_res->update_meta_data( 'amwal_transaction_id', $transaction_details['id'] );
				}
	            if(empty($order_res->get_meta('amwal_cart_id'))) {
                    $order_res->update_meta_data('amwal_cart_id', $transaction_details['ref_id']);
	            }
//                $order_res->set_status(apply_filters('woocommerce_default_order_status', 'pending'));
	            $order_id = $order_res->save();
	            $old_amount = $order_res->get_total();
	            $bins_to_check = get_option(AMWALWC_SETTING_BINS_OFFERS);
	            $bins_promo_code = get_option(AMWALWC_SETTING_BINS_PROMO_CODE);
	            $bins_coupon = null;
	            $new_amount = null;
				if(!empty($bins_to_check) && !empty($bins_promo_code)){
					try {
						$bins_coupon = new WC_Coupon( $bins_promo_code );
						if(!is_wp_error($bins_coupon)){
							$order_after_bins_coupon = $this->check_and_apply_bin_promo_code($transaction_details,$order_res,$bins_to_check,$bins_coupon);
							if(!empty($order_after_bins_coupon)){
								$new_amount = $order_after_bins_coupon;
							}
						}
					} catch (Exception $e){
						$this->sentryExceptionReport->reportException($e,$this->amwal_cart_id, );
					}
				}
	            $amwal_promo_code = get_option(AMWALWC_SETTING_PROMO_CODE);
	            $amwal_coupon = null;
	            if(!empty($amwal_promo_code)){
		            try {
			            $amwal_coupon = new WC_Coupon( $amwal_promo_code );
			            $order_after_amwal_coupon = amwalwc_get_valid_coupon($amwal_coupon,$order_res);
			            if(!empty($order_after_amwal_coupon)){
				            $new_amount = $order_after_amwal_coupon;
			            }
		            } catch (Exception $e) {
			            $this->sentryExceptionReport->reportException($e,$this->amwal_cart_id, );
		            }
	            }
				$coupon_description = '';
				if(!empty($order_after_bins_coupon)){
					$coupon_description = $bins_coupon->get_description();
					if(!empty($order_after_amwal_coupon)){
						$coupon_description .= amwalwc_get_current_lang() == 'ar' ? ' و ' : ' And ';
						$coupon_description .= $amwal_coupon->get_description();
					}
				}
				elseif (!empty($order_after_amwal_coupon)){
					$coupon_description = $amwal_coupon->get_description();
				}
	            $card_bin_additional_discount_message = amwalwc_get_current_lang() == 'ar' ? 'لقد حصلت على خصم إضافي' : 'You have earned an extra discount';
	            $return_result = [
					'order_id' => strval($order_id),
					'amount' => !empty($new_amount) ? (float)$new_amount : (float)$order_res->get_total(),
					'card_bin_additional_discount_message' => !empty($coupon_description) ? $card_bin_additional_discount_message." ".$coupon_description : null,
					'card_bin_additional_discount' => !empty($new_amount) ?  (float)$old_amount - (float)$new_amount : null,
					'old_amount' => $old_amount
				];
                return new WP_REST_Response($return_result, 201);
            } catch (Exception $e) {
                return $this->order_creation_error_response('Could not find order', $e);
            }
        }
        return $this->order_creation_error_response('Could not find amwal cart id');

    }

	private function check_and_apply_bin_promo_code($transaction_details,$order,$bins_to_check,$coupon): ?float {
		$bins = explode(',',$bins_to_check);
		if(!empty($transaction_details['card_bin'])){
			foreach ($bins as $bin) {
				if (str_contains( $transaction_details['card_bin'], $bin ) ) {
					return amwalwc_get_valid_coupon($coupon,$order);
				}
			}
		}
		return null;
	}


    public function get_permission_callback()
    {
        return $this->WCBasicAuth();
    }

	function validateOrder($transaction_details){
		$prev_orders = $this->get_order_by_amwal_transaction_id($transaction_details['id'],1);
		$invalid_statuses = $this->getInvalidOrderStatus();
		if(!empty($prev_orders)){
			if ( is_array($prev_orders)){
				foreach ($prev_orders as $order) {
					if (!in_array($order->get_status(), $invalid_statuses)) {
						return [false, 'Order with this transaction id already exists'];
					}
				}
			} elseif (!in_array( $prev_orders->get_status(), $invalid_statuses ) ) {
				return [false,'Order with this transaction id is already exists'];
			}
		}
		if ( in_array('status',$transaction_details) && $transaction_details['status'] == 'success' ) {
			return [false,'Invalid Payment Transaction Status'];
		}
		if ( $transaction_details['merchant_key'] != amwalwc_get_app_id() ) {
			return [false,'Invalid Payment Transaction Merchant Key'];
		}
		return [true,null];
	}
}