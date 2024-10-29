<?php

namespace Amwal\Http\Controllers;

use Exception;
use WP_REST_Request;
use WP_REST_Response;
use function apply_filters;
use function is_wp_error;


class CompleteOrderController extends Controller {


	protected $namespace = 'wc/amwal/v2';
	/**
	 * Route name.
	 *
	 * @var string
	 */
	protected $route = 'order/complete';
	/**
	 * Route methods.
	 *
	 * @var string
	 */
	protected $method = 'POST';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function handle( $request ) {
		try {
			$body                = $request->get_json_params();
			$this->amwal_cart_id = $body['amwal_cart_id'] ?? null;
			$amwal_order_id      = $body['order_id'] ?? null;

			if ( ! $this->amwal_cart_id || ! $amwal_order_id ) {
				return $this->order_creation_error_response( 'Invalid request data: Missing order_id or amwal_cart_id' );
			}

			$order_res = wc_get_order( $amwal_order_id );

			if ( empty($order_res) || is_wp_error( $order_res ) ) {
				return $this->order_creation_error_response( 'Could not find order with the provided order_id' );
			}

			$order_amwal_cart_id = $order_res->get_meta( 'amwal_cart_id' );

			if ( $order_amwal_cart_id !== $this->amwal_cart_id ) {
				$this->amwal_cart_id = $order_amwal_cart_id;
//				return $this->order_creation_error_response( 'Cart id mismatch' );
			}

			$order_status = $order_res->get_status();

			$amwal_transaction_id = $order_res->get_meta( 'amwal_transaction_id' ); // what if not exists
			if ( empty( $amwal_transaction_id ) ) {
				return $this->order_creation_error_response( 'Could not find amwal transaction id in order meta' );
			}

			$processing_status = apply_filters( 'woocommerce_default_order_status', 'processing' );
			if ( $order_status != $processing_status ) {

				$invalid_statuses = $this->getInvalidOrderStatus();
				if ( ! in_array( $order_status, $invalid_statuses ) ) {
					return $this->order_creation_error_response( 'Order status is not valid for processing' );
				}

				list( $response_status, $transaction_details, $response_full_message ) = $this->get_transaction_details( $amwal_transaction_id );

				if ( $response_status === 'error' ) {
					return $this->order_creation_error_response( $transaction_details, $response_full_message );
				}

				$max_retries = 10;
				$tries       = 0;

				while (!in_array( $transaction_details['status'] ?? '', ['success','fail']) && $tries < $max_retries ) {
					sleep( 5 + $tries );
					list( $response_status, $transaction_details, $response_full_message ) = $this->get_transaction_details( $amwal_transaction_id );

					if ( $response_status === 'error' ) {
						return $this->order_creation_error_response( $transaction_details, $response_full_message );
					}

					$tries ++;
				}
				if ( get_option( 'amwalwc_setting_installment_bank_options', false ) ) {
					$order_res->update_meta_data( 'amwal_bank_installments', $transaction_details['payment_method'] === 'Installments' );
				}

				$order_res->update_meta_data( 'amwal_bank_installments', $transaction_details['payment_method'] === 'Installments' );

				if ( $transaction_details['status'] === 'success' ) {
//					$order_res->set_status( $invalid_statuses[3] );

					$order_schema = $this->amwalwc_build_order_schema( $transaction_details );

					if ( is_wp_error( $order_schema ) || $order_schema instanceof Exception ) {
						return $this->order_creation_error_response( 'Could not complete order schema', $order_schema );
					}


					$user_info = $order_schema['user'];
					if ( ! empty( $user_info['email'] ) ) {
						$order_res->set_billing_email( $user_info['email'] );
					}
					if ( ! empty( $user_info['first_name'] ) ) {
						$order_res->set_billing_first_name( $user_info['first_name'] );
						$order_res->set_shipping_first_name( $user_info['first_name'] );
					}
					if ( ! empty( $user_info['last_name'] ) ) {
						$order_res->set_billing_last_name( $user_info['last_name'] );
						$order_res->set_shipping_last_name( $user_info['last_name'] );
					}
					if ( ! empty( $user_info['phone_number'] ) ) {
						$order_res->set_billing_phone( $user_info['phone_number'] );
						$order_res->set_shipping_phone( $user_info['phone_number'] );
					}

					$order_res->save();

					if ( (float) $transaction_details['total_amount'] == $order_res->get_total() ) {
						$order_res->payment_complete( $amwal_transaction_id );
						$order_res->set_payment_method( AMWALWC_PAYMENT_METHOD_ID );
						$order_res->set_status( $processing_status );
						$order_res->save();

					} else {
						$total_error = new Exception( 'Order total amount does not match' );

						return $this->order_creation_error_response( 'Could not validate the transaction', $total_error );
					}
				} elseif ( $transaction_details['status'] === 'fail' ) {
					$order_res->set_status( $invalid_statuses[2] );
					$order_res->update_meta_data( 'failure_reason', $transaction_details['failure_reason'] );
					$order_res->save();

				} else {
					if ( $body['assert_transaction_status'] ) {
						return $this->order_creation_error_response( "Transaction status error" );
					} else {
						return $this->order_creation_error_response( "Could not find a valid transaction status");
					}
				}
			}
			if(get_option( AMWALWC_SETTING_GUEST_AUTO_LOGIN, false )){
				$user = $order_res->get_user();
				if(!is_user_logged_in() && $user){
					$this->amwalwc_log_user_in($user);
				}
			}
			$message = [
				'order_id'  => strval( $order_res->get_id() ),
				'order_url' => $order_res->get_checkout_order_received_url(),
			];
			Controller::amwalwc_update_order_details( $amwal_transaction_id, $message );

			return new WP_REST_Response( $message, 201 );
		} catch ( Exception $e ) {
			return $this->order_creation_error_response( 'An unexpected error occurred during order processing', $e );
		}
	}


	public function get_permission_callback() {
		return $this->WCBasicAuth();
	}

	public function amwalwc_log_user_in($user){
		clean_user_cache($user->ID);
		wp_clear_auth_cookie();
		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID, true, false);
		update_user_caches($user);
	}
}
