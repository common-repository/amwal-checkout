<?php
use Amwal\Http\Controllers\Controller;

add_action('init', 'amwalwc_woocommerce_order_created');

function amwalwc_woocommerce_order_created()
{
    if (!isset($_GET['amwal_order_created'])) {
        return;
    }

    $redirect_url = wc_get_endpoint_url('order-received', null, wc_get_checkout_url());

    $order_id = absint($_GET['amwal_order_created']);
    $order = wc_get_order($order_id);
    if ($order) {
        $redirect_url = $order->get_checkout_order_received_url();
    }
    WC()->cart->empty_cart();
    wp_safe_redirect($redirect_url);
    exit;
}

function amwalwc_report_error($ref_id,$body){
    if(!empty($ref_id)) {
        $url = AMWALWC_TRANSACTION_DETAILS . $ref_id . '/report_error';
        wp_remote_post($url, array(
                'method' => 'POST',
                'body' => $body,
            )
        );
    }
}


add_filter('rocket_exclude_js', 'exclude_js_from_minification');
function exclude_js_from_minification($excluded_files = array()) {
    $excluded_files[] = '/wp-content/plugins/amwal-checkout/(.*).js';
    return $excluded_files;
}

function amwalwc_add_order_meta_box_action( $actions ) {
	global $theorder;

	$pending_status     = apply_filters('woocommerce_default_order_status', 'pending');
	$cancelled_status   = apply_filters('woocommerce_default_order_status', 'cancelled');
	$on_hold_status     = apply_filters('woocommerce_default_order_status', 'on-hold');
	$failed_status      = apply_filters( 'woocommerce_default_order_status', 'failed' );

	// bail if the order has been paid for or this action has been run
	if (in_array($theorder->get_status(), [$pending_status,$cancelled_status,$on_hold_status,$failed_status]) && get_post_meta($theorder->id,'amwal_transaction_id',true) ) {
		$actions['wc_custom_order_action'] = __( 'Check Transaction Details', 'amwal-checkout' );
		return $actions;
	}
	return $actions;
}
add_action( 'woocommerce_order_actions', 'amwalwc_add_order_meta_box_action' );

function amwalwc_process_order_meta_box_action( $order ) {

	// add the order note
	$message = sprintf( __( 'Check transaction details %s.', 'amwal-checkout' ), wp_get_current_user()->display_name );
	$order->add_order_note( $message );
	$amwal_transaction_id = get_post_meta($order->id,'amwal_transaction_id',true);
	amwalwc_check_transaction_details_request($order, $amwal_transaction_id);

}
add_action( 'woocommerce_order_action_wc_custom_order_action', 'amwalwc_process_order_meta_box_action' );

function amwalwc_check_transaction_details_request($order,$amwal_transaction_id){
	$processing_status = apply_filters('woocommerce_default_order_status', 'processing');

	[$response_status,$transaction_details,$_] = Controller::get_transaction_details($amwal_transaction_id);
	if(in_array('status', $transaction_details) && $transaction_details['status'] == 'success' && $response_status == 'success' ) {
		$order->set_status($processing_status);
		$order->save();
		Controller::amwalwc_update_order_details( $amwal_transaction_id, [
			'order_id' => strval($order->get_id()),
			'order_url' => $order->get_checkout_order_received_url(),
		] );
	}
	else{
		$message = sprintf( __( 'Transactions status is not success on Quick Checkout', 'amwal-checkout' ), wp_get_current_user()->display_name );
		$order->add_order_note( $message );
	}
}




add_action( 'woocommerce_thankyou', 'add_custom_content_to_thankyou', 10, 1 );
function add_custom_content_to_thankyou( $order_id ) {
	$lang = get_bloginfo('language' ) ;
	$website_lang = str_contains($lang, '-') ? explode("-", $lang)[0] : $lang;
	$order = wc_get_order( $order_id );
	if ( $order->get_meta('amwal_bank_installments') ) {
		$url = AMWALWC_INSTALLMENT_URL . ($website_lang == 'ar' ? '/ar' : '');
		echo '<iframe src="' . $url . '" style="width:100%; height:700px;"></iframe>';
	}
}

//
//function amwalwc_add_order_promo_column_header($columns)
//{
//	$new_columns = array();
//	foreach ($columns as $column_name => $column_info) {
//		$new_columns[$column_name] = $column_info;
//		if ('order_total' === $column_name) {
//			$new_columns['order_promo'] = __('Amwal Promo', 'amwal-checkout');
//		}
//	}
//	return $new_columns;
//}
//
//function amwalwc_add_order_promo_column_content( $column ) {
//	global $post;
//	$amwal_bins_promo_code = get_option(AMWALWC_SETTING_BINS_PROMO_CODE);
//	if ( 'order_promo' === $column ) {
//		$order    = wc_get_order( $post->ID );
//		$amwal_transaction_id = $order->get_meta('amwal_transaction_id');
//		if(!empty($amwal_transaction_id) && !empty($amwal_bins_promo_code)){
//			foreach( $order->get_items('fee') as $item_id => $item_fee ){
//				$fee_name = $item_fee->get_name();
//				$coupon = new WC_Coupon( $amwal_bins_promo_code );
//				if($fee_name == $amwal_bins_promo_code || $fee_name == $coupon->get_description()){
//					echo $coupon->get_code();
//					break;
//				}
//			}
//
//		}
//	}
//}
//
//if ( get_option( AMWALWC_SETTING_SHOW_BINS_PROMO_IN_ORDERS ) == true ) {
//	add_filter( 'manage_edit-shop_order_columns', 'amwalwc_add_order_promo_column_header' );
//	add_action( 'manage_shop_order_posts_custom_column', 'amwalwc_add_order_promo_column_content' );
//}