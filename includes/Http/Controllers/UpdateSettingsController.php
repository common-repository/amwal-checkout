<?php

namespace Amwal\Http\Controllers;

use Exception;
use WC_Customer;
use WP_REST_Request;
use WP_REST_Response;
use function is_wp_error;


class UpdateSettingsController extends Controller {

	public $amwal_cart_id;
	protected $namespace = 'wc/amwal/v2';
	/**
	 * Route name.
	 *
	 * @var string
	 */
	protected $route = 'settings/update';
	/**
	 * Route methods.
	 *
	 * @var string
	 */
	protected $method = 'POST';

	public function __construct() {
		parent::__construct();
	}

	public function handle( $request ) {
		$amwalwc_setting_app_id  = amwalwc_get_app_id();
		if(!empty($amwalwc_setting_app_id)){
			$result = checkMerchant($amwalwc_setting_app_id);
			if(gettype($result) == 'array' || $result != null) {
				if( array_key_exists( 'valid', $result ) && $result['valid'] == 1){
					$body = $request->get_json_params();
					$result = [];
					if ( array_key_exists( "settings", $body ) ) {
						$settings = $body['settings'];
						foreach ( $settings as $key => $value ) {
							$updated = update_option( $key, $value );
							if($updated) {
								$result[ $key ] = get_option( $key );
							}
						}
						return new WP_REST_Response($result, 201);
					}
				}
			}
		}
		return new WP_REST_Response( "Bad Request", 500 );
	}


	public function get_permission_callback() {
		return $this->WCBasicAuth();
	}

}
