<?php

namespace Amwal\Http\Controllers;

use WP_REST_Request;
use WP_REST_Response;


class CreateCheckoutController extends Controller
{

    protected $namespace = 'wc/amwal/v2';
    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'checkout/create';

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
        $cart = WC()->cart;
        if(array_key_exists("pre_existing_order_id",$body)){
            $order = wc_get_order($body['pre_existing_order_id']);
            $total_amount   = $order->get_total();
            $taxes          = $order->get_total_tax('');
            $response_result = [
                'discount'      => 0,
                'taxes'         => $taxes,
                'amount'        => max( 0, $total_amount - $taxes),
                'order_content' => $this->amwalwc_get_order_items($order)
            ];
        }
        else{
	        $cart->calculate_totals();
	        $discount = amwalwc_calculate_cart_discounts();
	        $amount         = $cart->get_cart_contents_total() + $cart->get_fee_total() + $discount;
	        $taxes          = $cart->get_cart_contents_tax() + $cart->get_fee_tax();

            $response_result = [
                'discount'          => $discount,
                'taxes'             => $taxes,
                'amount'            => $amount,
//                'shipping_methods'  => $this->get_shipping_methods($cart),
                'order_content'     => $this->amwalwc_get_cart_items()
            ];

        }
        return new WP_REST_Response($response_result, 201);
    }

    private function amwalwc_handle_product_extra_option($order_cart){
        $products = array();
        foreach ($order_cart as $obj){
            if(array_key_exists('product_id',$obj)){
                $products[] = [
                    'product_id'=>$obj['product_id'],
                    'quantity'=>$obj['quantity'],
                    'variation_id'=>$obj['variation_id']??"",
                ];
            }
            foreach ($obj as $key => $value) {
                if (preg_match('/^(.*)_product_(\d+)_(.*)$/', $key, $matches)) {
                    $productIdKey = $matches[1] . "_product_" . $matches[2];
                    if(!empty($obj[$productIdKey])){
                        $field = $matches[3];
                        if ($field == "quantity") {
                            $quantity = intval($value);
                            if ($quantity > 0) {
                                if (!array_key_exists($productIdKey, $products)) {
                                    $products[$productIdKey] = array(
                                        "product_id" => $obj[$productIdKey],
                                        "quantity" => $quantity,
                                        "variation_id" => ""
                                    );
                                } else {
                                    $products[$productIdKey]["quantity"] = $quantity;
                                }
                            }
                        }
                        else if ($field == "variation_id") {
                            if (!array_key_exists($productIdKey, $products)) {
                                $products[$productIdKey] = array(
                                    "product_id" => $obj[$productIdKey],
                                    "quantity" => 0,
                                    "variation_id" => $value
                                );
                            }
                            else {
                                $products[$productIdKey]["variation_id"] = $value;
                            }
                        }
                    }

                }
            }
        }
        return ['cart'=>array_values($products)];
    }

	private function amwalwc_get_order_items($order) {
		$result_items = [];
		$order_items = $order->get_items();
		$product_data_cache = [];

		foreach ($order_items as $item_id => $item) {
			$product_id = $item->get_product_id();
			$product_data_cache = $this->getProductCache( $product_data_cache, $product_id );

			$result_items[] = [
				'id' => $product_id,
				'name' => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'total' => $item->get_total(),
				'url' => $product_data_cache[$product_id]['url'],
				'image' => $product_data_cache[$product_id]['image']
			];
		}

		return $result_items;
	}

	private function amwalwc_get_cart_items() {
		$result_items = [];
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		$product_data_cache = [];

		foreach ($items as $item_key => $values) {
			$product_id = $values['data']->get_id();

			// Only load product data if we haven't already loaded it for this product ID
			$product_data_cache = $this->getProductCache( $product_data_cache, $product_id );

			$result_items[] = [
				'id' => $product_id,
				'name' => $values['data']->get_name(),
				'quantity' => $values['quantity'],
				'total' => $values['line_total'],
				'url' => $product_data_cache[$product_id]['url'],
				'image' => $product_data_cache[$product_id]['image']
			];
		}

		return $result_items;
	}


	public function get_permission_callback()
    {
        return $this->WCBasicAuth();
    }

	/**
	 * @param array $product_data_cache
	 * @param $product_id
	 *
	 * @return array
	 */
	private function getProductCache( array $product_data_cache, $product_id ): array {
		if ( ! isset( $product_data_cache[ $product_id ] ) ) {
			$_product = wc_get_product( $product_id );
			$image_id = get_post_thumbnail_id( $product_id );
			$image    = $image_id ? wp_get_attachment_image_src( $image_id, 'single-post-thumbnail' ) : null;

			// Cache the product data to avoid repeated queries for the same product
			$product_data_cache[ $product_id ] = [
				'url'   => $_product ? $_product->get_permalink() : null,
				'image' => $image ? $image[0] : null
			];
		}

		return $product_data_cache;
	}
}