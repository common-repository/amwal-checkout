<?php
use Amwal\SentryReporter\SentryExceptionReport;
const AMWALWC_PAYLOAD_PREFIX = "amwalwc-payload";
const AMWALWC_BTNPOS_PRODUCT_PAGE = "amwal-product-page"; # regular product page
const AMWALWC_BTNPOS_BEFORE_CHECKOUT = "amwal-before-checkout-form"; # regular checkout page
const AMWALWC_BTNPOS_PROCEED_TO_CHECKOUT = "amwal-proceed-to-checkout"; # cart page
const AMWALWC_BTNPOS_MINICART = "amwal-mini-cart"; # mini cart
const AMWALWC_BTNPOS_PRODUCT_SHORTCODE = "amwal-product-custom";
const AMWALWC_BTNPOS_CART_SHORTCODE = "amwal-cart-custom";

add_action('init', 'amwalwc_load_visual_hook_filters');
add_action('init', 'amwalwc_load_custom_shortcodes');

function amwalwc_load_custom_shortcodes()
{
    add_shortcode('amwal-product-checkout', 'amwalwc_product_checkout_shortcode');
    add_shortcode('amwal-cart-checkout', 'amwalwc_cart_checkout_shortcode');
}

function amwalwc_load_visual_hook_filters()
{
	$promo_snippet_message = get_option('amwalwc_settings_promo_snippet_message', '');
	$promo_snippet_position = get_option('amwalwc_settings_promo_snippet_position', 'woocommerce_after_add_to_cart_button');
	if(!empty($promo_snippet_message)){
		add_filter(
			$promo_snippet_position,
			'amwalwc_custom_promo_snippet_message',
			10
		);
	}

    $button_position = get_option('amwalwc_product_button_placement', 'woocommerce_after_add_to_cart_button');
    $location_options = array(
        'woocommerce_before_add_to_cart_form',
        'woocommerce_after_add_to_cart_form',
        'woocommerce_after_add_to_cart_button',
        'woocommerce_after_single_product_summary'
    );

    if (in_array($button_position, $location_options)) {
        add_filter(
            $button_position,
            'amwalwc_add_checkout_after_main_content',
            10
        );
    } else {
        add_filter(
            'woocommerce_after_add_to_cart_button',
            'amwalwc_add_checkout_after_main_content',
            10
        );
    }
    add_filter(
        'woocommerce_widget_shopping_cart_before_buttons',
        'amwalwc_render_mini_cart',
        10
    );

    add_filter(
        get_option('amwalwc_cart_page_button_placement'),
        'amwalwc_render_proceed_to_checkout',
        10
    );

    add_filter(
        get_option('amwalwc_checkout_page_button_placement'),
        'amwalwc_render_before_checkout_form',
        10
    );
    add_filter(
        'woocommerce_package_rates',
        'amwalwc_handle_shipping_methods_when_free_is_available',
        10,
        2
    );


}
function amwalwc_custom_promo_snippet_message(){
	$amwalwc_promo_snippet_message = get_option( AMWALWC_SETTINGS_PROMO_SNIPPET_MESSAGE, "" );
	if (!empty($amwalwc_promo_snippet_message)){
		echo '<div id="amwalPromo" class="amwal-summary-widget__container badge-position--is-end-line amwal-summary-widget__bundle-ui amwal-summary-widget--inline-outlined amwal-summary-widget__inline-template-2" dir="rtl">
			<div class="amwal-summary-widget__content">
				<span class="amwal-summary-widget__inline__text">&nbsp;'.$amwalwc_promo_snippet_message.'&nbsp;</span>
			</div>
			<div class="amwal-inline-badge amwal-badge"><b>'.__('Quick Checkout','amwal-checkout').'</b></div>
		</div>';
	}
	echo '';
}

function amwalwc_add_checkout_after_main_content()
{
    if (!amwalwc_should_render_button() || !get_option('amwal_auto_render_product_button')) {
        return;
    }
	$cart = WC()->cart;
	if (is_null($cart) || ($cart instanceof WC_Cart && $cart->is_empty())) {
        global $product;
		$target = uniqid( AMWALWC_PAYLOAD_PREFIX );
		if ( ! empty( $product->get_sale_price() ) ) {
			$amount   = $product->get_sale_price();
			$taxes    = wc_get_price_including_tax( $product ) - wc_get_price_excluding_tax( $product );
			$discount = $product->get_regular_price() - $product->get_sale_price();
		} else {
			$amount   = wc_get_price_excluding_tax( $product );
			$taxes    = wc_get_price_including_tax( $product ) - $amount;
			$discount = 0;
		}
		if ( is_plugin_active( 'woo-discount-rules/woo-discount-rules.php' ) ) {
			$discount_details = apply_filters('advanced_woo_discount_rules_get_product_discount_details', false, $product);
			if ($discount_details !== false) {
				$amount = $discount_details['initial_price'];
				$discount = $discount_details['saved_amount'];
				$taxes = 0;
			}
		}
        if ($amount > 0) {
            echo amwalwc_button($target, $taxes, $amount, $amount, $discount, amwalwc_prepare_product($product), AMWALWC_BTNPOS_PRODUCT_PAGE);
        }
    }
}

function amwalwc_render_mini_cart()
{
    if (!amwalwc_should_render_button() || !get_option('amwalwc_auto_render_minicart_button')) {
        return;
    }

	amwalwc_render_cart(AMWALWC_BTNPOS_MINICART);
}

function amwalwc_render_proceed_to_checkout()
{
    if (!amwalwc_should_render_button() || !get_option('amwal_auto_render_cart_button')) {
        return;
    }

	amwalwc_render_cart(AMWALWC_BTNPOS_PROCEED_TO_CHECKOUT);
}

function amwalwc_render_before_checkout_form()
{
    if (!amwalwc_should_render_button() || !get_option('amwalwc_auto_render_checkout_page_button')) {
        return;
    }

//	amwalwc_render_cart(AMWALWC_BTNPOS_BEFORE_CHECKOUT);
}

function amwalwc_product_checkout_shortcode($atts)
{
    if (!amwalwc_should_render_button() || WC()->cart->is_empty()) {
        return '';
    }

    $a = shortcode_atts(['product_id' => NULL], $atts);
    if (!isset($atts['product_id'])) {
        global $product;
        if (!$product) {
            return '';
        }
    } else {
        $product = \wc_get_product($a['product_id']);
    }

    if (!($product instanceof WC_Product)) {
        return '';
    }

    $target = uniqid(AMWALWC_PAYLOAD_PREFIX);
    $amount = wc_get_price_excluding_tax($product);
    $taxes = wc_get_price_including_tax($product) - $amount;
	$discount = 0;

	if ( is_plugin_active( 'woo-discount-rules/woo-discount-rules.php' ) ) {
		$discount_details = apply_filters('advanced_woo_discount_rules_get_product_discount_details', false, $product);
		if ($discount_details !== false) {
			$amount = $discount_details['initial_price'];
			$discount = $discount_details['saved_amount'];
			$taxes = 0;
		}
	}
    if ($amount > 0) {
        echo amwalwc_button($target, $taxes, $amount, $amount, $discount, amwalwc_prepare_product($product), AMWALWC_BTNPOS_PRODUCT_SHORTCODE);
    }

    return '';
}

function amwalwc_cart_checkout_shortcode($atts)
{
    if (!amwalwc_should_render_button()) {
        return;
    }

    $cart = WC()->cart;
    if (is_null($cart) || !($cart instanceof WC_Cart)) {
        return;
    }
    amwalwc_render_cart(AMWALWC_BTNPOS_CART_SHORTCODE);
}


function amwalwc_button($id,$taxes, $amount,$total_amount,$discount, $payload, $position,$coupons = null, $amwal_order_id = null)
{
	$locale = amwalwc_get_current_lang();
    $postcode_optional_countries = amwalwc_get_option_or_set_default(AMWALWC_SETTING_POSTCODE_OPTIONAL_COUNTRIES,array());
    $allowed_address_countries = amwal_get_shipping_country();
	$allowed_address_states = amwal_get_shipping_states($allowed_address_countries);
	$allowed_address_cities = amwalwc_get_shipping_cities($allowed_address_countries);

	if($locale == 'en'){
		$amwalwc_shipping_en_states = json_decode(get_option(AMWALWC_SETTING_SHIPPING_EN_STATES));
		$amwalwc_shipping_en_cities = json_decode(get_option(AMWALWC_SETTING_SHIPPING_EN_CITIES));
		if (!empty($amwalwc_shipping_en_states) && !empty($amwalwc_shipping_en_cities)) {
			$allowed_address_states = $amwalwc_shipping_en_states;
			$allowed_address_cities = $amwalwc_shipping_en_cities;
		}
	} elseif ($locale == 'ar' ){
		$amwalwc_shipping_ar_states = json_decode(get_option(AMWALWC_SETTING_SHIPPING_AR_STATES));
		$amwalwc_shipping_ar_cities = json_decode(get_option(AMWALWC_SETTING_SHIPPING_AR_CITIES));
		if (!empty($amwalwc_shipping_ar_states) && !empty($amwalwc_shipping_ar_cities)) {
			$allowed_address_states = $amwalwc_shipping_ar_states;
			$allowed_address_cities = $amwalwc_shipping_ar_cities;
		}
	}

    $cart_contains_virtual = amwalwc_check_cart_contains_virtual($payload);
    if($total_amount == (float)0){
        $html = "";
    }else{
	    $promo_code = get_option(AMWALWC_SETTING_PROMO_CODE);
	    $discount_description = null;
		if($promo_code){
			try {
				$coupon = new WC_Coupon( $promo_code );
			} catch (Exception $e) {
				$coupon = null;
			}
			$discount_description = !empty($coupon) ? $coupon->get_description(): null;
		}
	    $user = wp_get_current_user();
        $user_email = $user->ID != 0 ? $user->user_email:"";
        $single = $position === AMWALWC_BTNPOS_PRODUCT_PAGE;
        $html = amwalwc_button_style($position);
        $html .= '<p class="amwal-container ' . $position . '">';
        $html .= '<amwal-checkout-button ';
        $html .= 'merchant-id="' . amwalwc_get_app_id() . '" ';
        $html .= 'locale="' . $locale . '" ';
        $html .= 'amount="' . $amount . '"';
        $html .= 'ref-id="'.$id.'"';
        $html .= 'taxes="'.$taxes.'"';
        $html .= 'discount="'.$discount.'"';
//        $html .= 'disabled="true"';
	    if(!empty($discount_description)) $html .= 'discount-description="'.$discount_description.'"';
		$enable_installment = true;
		if(get_option("amwalwc_setting_exclude_installment_slugs")){
			$categories_slugs = explode(',',get_option("amwalwc_setting_exclude_installment_slugs"));
			foreach ($categories_slugs as $slug) {
				foreach ($payload as $key => $value) {
					if(in_array($slug,$value['categories']) || in_array($slug,$value['tags'])){
						$enable_installment = false;
					}
				}
			}

		}
	    if($enable_installment){
		    $html .= 'enable-bank-installments="'.((bool) get_option( AMWALWC_SETTING_INSTALLMENT_BANK_OPTIONS) ? "true":"false").'"';
		    $html .= 'installment-options-url="'.(get_option(AMWALWC_SETTING_INSTALLMENT_REDIRECT_OPTIONS) ?? "").'"';
	    }

        $html .= 'show-payment-brands="true"';
        $html .= 'enable-pre-pay-trigger="true"';
        $html .= 'enable-pre-checkout-trigger="true"';
//        $html .= 'enable-apple-checkout="true"';
        $html .= 'label="'.($single ? "quick-buy" : "checkout").'"';
        $html .= 'country-code="' . get_option("amwal_checkout_currency") . '"';
        $html .= 'debug="'.(get_option("amwalwc_debug_mode") ? "true" : "false").'"';
        $html .= 'show-discount-ribbon="'.(get_option("amwalwc_show_discount_ribbon") ? "true" : "false").'"';
        $html .= 'dark-mode="'.(get_option("amwalwc_use_dark_mode") ? "on" : "off").'"';
        $html .= 'allowed-address-countries="'.esc_html(json_encode($allowed_address_countries)).'"';
        $html .= 'allowed-address-states="'.esc_html(json_encode($allowed_address_states)).'"';
        $html .= 'allowed-address-cities="' . esc_html(json_encode($allowed_address_cities)) . '"';
        if(!empty($postcode_optional_countries) && in_array(gettype($postcode_optional_countries), ['array', 'object'])) {
			$html .= 'postcode-optional-countries="' . esc_html(json_encode($postcode_optional_countries)) . '"';
		}
        $address_required = !$cart_contains_virtual || get_option("amwal_checkout_address_required_in_virtual_product");
        if($address_required && !$amwal_order_id){
            $html .= 'address-required="true"';
            $html .= 'address-handshake="true"';
            $html .= 'email-required="'.((bool) get_option( "amwal_checkout_guest_email_required" ) ? "true":"false") . '"' ;
            $html .= 'street2-required="'.((bool) get_option( "amwalwc_checkout_address_street2_required" ) ? "true":"false") . '"' ;
        }
        else{
            $html .= 'address-required="false"';
            $html .= 'address-handshake="false"';
        }
        if(AMWALWC_ENV != 'prod'){
            $html .= 'test-environment="'.AMWALWC_ENV.'"';
        }
	    $user_info = null;
        if($amwal_order_id){
            $user_info = amwalwc_get_user_address(null,$amwal_order_id);
        }
        elseif($user->ID != 0){
            $user_info = amwalwc_get_user_address($user->ID,null);
        }
        if($user_info){
            $address = [
                "street1"           => $user_info['address_1'],
                "street2"           => $user_info['address_2'],
                "city"              => $user_info['city'],
                "state"             => $user_info['state'],
                "postcode"          => $user_info['postcode'],
                "country"           => $user_info['country'],
            ];
            $html .= 'initial-address="'.esc_html(json_encode($address)).'"';
            $html .= 'initial-email="'.$user_info['email'].'"';
            $html .= 'initial-phone-number="'.$user_info['phone'].'"';
            $html .= 'initial-first-name="'.$user_info['first_name'].'"';
            $html .= 'initial-last-name="'.$user_info['last_name'].'"';
        }
        $html .= 'position="'.$position.'" ';
        if(!empty($amwal_order_id)) {
            $html .= 'checkout-order-id="' . $amwal_order_id . '" ';
        }
        $html .= '></amwal-checkout-button>';
        $html .= '</p>';
    }


    return $html;
}

function amwalwc_get_user_address($user_id = null, $amwal_order_id = null){
	try {
		$info   = [
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'phone',
			'first_name',
			'last_name',
			'email'
		];
		$result = array();
		if ( $user_id ) {
			foreach ( $info as $item ) {
				$temp = get_user_meta( $user_id, 'billing_' . $item, true );
				if ( empty( $temp ) ) {
					$temp = get_user_meta( $user_id, 'shipping_' . $item, true );
				}
				$result[ $item ] = $temp ?? '';
			}
		}
		if ( $amwal_order_id ) {
			$order_data = wc_get_order( $amwal_order_id )->get_data();
			foreach ( $info as $item ) {
				$result[ $item ] = $order_data['billing'][ $item ] ?? $order_data['shipping'][ $item ] ?? '';
			}
		}
		$state           = $result['state'];
		$result['state'] = amwal_get_state_by_code( $result['country'], $state );
	} catch ( Exception $e ) {
		$sentryExceptionReport = new  SentryExceptionReport();
		$sentryExceptionReport->reportException($e);
	}
	return $result;
}
function amwalwc_button_style($position)
{
    switch ($position) {
        case AMWALWC_BTNPOS_PRODUCT_PAGE:
            $style = get_option(AMWALWC_SETTING_PDP_BUTTON_STYLES);
            break;
        case AMWALWC_BTNPOS_MINICART:
            $style = get_option(AMWALWC_SETTING_MINI_CART_BUTTON_STYLES);
            break;
        case AMWALWC_BTNPOS_PROCEED_TO_CHECKOUT:
            $style = get_option(AMWALWC_SETTING_CART_BUTTON_STYLES);
            break;
        case AMWALWC_BTNPOS_BEFORE_CHECKOUT:
            $style = get_option(AMWALWC_SETTING_CHECKOUT_BUTTON_STYLES);
            break;
        default:
            return false;
    }
    return '<style type="text/css">' . esc_html($style) . '</style>';
}


function amwalwc_prepare_product($product): array {
    return [
        amwalwc_get_product_attributes($product)
    ];
}
function amwalwc_calculate_cart_discounts(){
	$cart = WC()->cart;
	$coupons        = amwalwc_get_coupons_code( $cart );
	$discount_total = $coupons ? array_sum($cart->get_coupon_discount_totals()) + array_sum($cart->get_coupon_discount_tax_totals()) : 0;
	if ( is_plugin_active( 'woo-discount-rules/woo-discount-rules.php' ) && ! empty( $cart->get_cart_contents() ) ) {
		$woo_discount_rules_enabled = true;
		foreach ( $cart->get_cart_contents() as $item ) {
			$product          = $item['key'];
			$cart_item_discount = apply_filters( 'advanced_woo_discount_rules_get_cart_item_discount_details', false, $product );

			if ( $cart_item_discount !== false ) {
				$discount_total += (float) $cart_item_discount['saved_amount'] * (float) $item['quantity'];
			} else {
				$woo_discount_rules_enabled = false;
			}
		}
		return $woo_discount_rules_enabled ? $discount_total : getCartDiscounts();
	}
	return getCartDiscounts();
}
function amwalwc_render_cart( $position,$amwal_order_id = null ) {
	try{
		$cart = WC()->cart;
		if ( $cart instanceof WC_Cart ) {
			$target         = uniqid( AMWALWC_PAYLOAD_PREFIX );
			$total_amount   = $cart->get_total("");
			$coupons        = amwalwc_get_coupons_code( $cart );
			if(!empty($amwal_order_id)){
				$order          = wc_get_order($amwal_order_id);
				$total_amount   = $order->get_total();
				$taxes          = $cart->get_total_tax();
				$amount         = max( 0, $total_amount - $taxes);
				$discount_total = 0;
			} else {
				$discount_total = amwalwc_calculate_cart_discounts();
				$amount         = $cart->get_cart_contents_total() + $cart->get_fee_total() + $discount_total;
				$taxes          = $cart->get_cart_contents_tax() + $cart->get_fee_tax();
			}
			if($amount > 0) {
				echo amwalwc_button($target, $taxes, $amount, $total_amount, $discount_total, amwalwc_prepare_cart($cart), $position, $coupons, $amwal_order_id);
			}
		}
	} catch (Exception $e) {
		$sentryExceptionReport = new  SentryExceptionReport();
		$sentryExceptionReport->reportException($e);
	}
}


function getCartDiscounts(){
	$cart = WC()->cart;
	$total_discount = 0;
	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];
		if ( $product->is_on_sale() ) {
			$regular_price  = $product->get_regular_price();
			$sale_price     = $product->get_sale_price();
			$quantity       = $cart_item['quantity'];
			$total_discount += ( $regular_price - $sale_price ) * $quantity;
		}
	}
	$coupons        = amwalwc_get_coupons_code( $cart );
	if ( ! empty( $coupons ) ) {
		$total_discount += array_sum( $cart->get_coupon_discount_totals() ) + array_sum($cart->get_coupon_discount_tax_totals());
	}
	return $total_discount;
}

///**
// * @param WC_Cart $cart
// *
// * @return array
// */
//function amwalwc_get_cart_discounts( WC_Cart $cart): array {
//	$discount_total = 0;
//	$amount         = 0;
//	foreach ( $cart->get_cart_contents() as $item ) {
//		$item_data          = $item['data']->get_data();
//		$item_quantity      = $item['quantity'];
//		$item_regular_price = (float)$item_data['regular_price'] * $item_quantity;
//		$item_sales_price   = $item_data['sale_price'];
//		if ( ! empty( $item_sales_price ) ) {
//			$discount_total += $item_regular_price - ((float)$item_sales_price * $item_quantity);
//		}
//		$amount += $item_regular_price;
//	}
//	if( wc_prices_include_tax() ) {
//		$amount = $amount - ($cart->get_cart_contents_tax() + $cart->get_fee_tax());
//	}
//
//	return array( $discount_total, $amount );
//}

function amwalwc_prepare_cart($cart)
{
    return array_reduce($cart->get_cart_contents(), function ($acc, $el) {
        if (array_key_exists('data', $el) && $el['data'] instanceof WC_Product) {
            array_push($acc, amwalwc_get_product_attributes($el['data'], $el['quantity'], $el['variation']));
        }
        return $acc;
    }, []);
}

function amwalwc_get_product_attributes(WC_Product $product, $qty = null, $variation = null)
{
	$product_categories = array();
	$product_tags = array();

	$product_cat = get_the_terms( $product->get_id(),'product_cat');
	if(is_array($product_cat)) {
		foreach ($product_cat as $category) {
			$product_categories[] = $category->slug;
		}
	}
	$product_tag = get_the_terms( $product->get_id(),'product_tag');
	if(is_array($product_tag)){
		foreach ($product_tag as $tag){
			$product_tags[] = $tag->slug;
		}
	}
    $attrs = [
        'product_id'         => strval($product->get_id()),
        'product_type'       => strval($product->get_type()),
        'in_stock'           => $product->is_in_stock(),
        'price'              => wc_get_price_excluding_tax($product),
        'backorders_allowed' => $product->backorders_allowed(),
        'stock_quantity'     => $product->get_stock_quantity(),
        'sold_individually'  => $product->is_sold_individually(),
        'purchasable'        => $product->is_purchasable(),
        'virtual'            => $product->is_virtual(),
	    'categories'         => $product_categories,
	    'tags'               => $product_tags
    ];

    if(is_a($product, 'WC_Product_Variable')) {
        $attrs['variations']   = array_map(function ($el) use ($qty) {
            return amwalwc_get_product_attributes(wc_get_product($el['variation_id']), $qty);
        }, $product->get_available_variations());
    }
    elseif (is_a($product, 'WC_Product_Variation')) {
        if (!isset($variation)) {
            $variation = $product->get_variation_attributes();
        }
        $attrs['attributes']   = $variation;
    }
    else if (is_a($product, 'WC_Product_Bundle')) {
        $bundled = \WC_PB_DB::query_bundled_items([
            'return' => 'id=>product_id',
            'bundle_id' => [$product->get_id()]
        ]);
        $attrs['bundle_configuration'] = array_reduce($bundled, function ($acc, $el) {
            $acc[strval($el)] = amwalwc_get_product_attributes(wc_get_product($el));
            return $acc;
        }, []);
    }

    if ($qty) {
        $attrs['quantity'] = $qty;
    }

    return $attrs;
}

function amwalwc_get_coupons_code($cart): array {
	$coupons = $cart->get_coupons();
	$coupons_ids = array();
	if(!empty($coupons)){
		foreach($coupons as $coupon){
			$coupons_ids[] = $coupon->get_code();
		}
	}
	return $coupons_ids;
}

function amwalwc_get_current_lang(){
	$lang = get_bloginfo('language' ) ;

	return str_contains($lang, '-') ? explode("-", $lang)[0] : $lang;
}

function amwalwc_should_render_button() {
    if (!amwalwc_check_amwal_configured()) {
        error_log('MISSING AMWAL API KEY');
        return false;
    }
    $amwalwc_test_mode = get_option( AMWALWC_SETTING_TEST_MODE, AMWALWC_SETTING_TEST_MODE_NOT_SET );
    if ($amwalwc_test_mode == '1') {
        return in_array('administrator', wp_get_current_user()->roles, true);
    }

    return true;
}


function amwalwc_handle_shipping_methods_when_free_is_available( $rates ) {
    $amwalwc_hide_shipping_methods = get_option(AMWALWC_SETTING_HIDE_SHIPPING_METHODS);
    if($amwalwc_hide_shipping_methods == 'hide_all'){
        $free = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( 'free_shipping' === $rate -> method_id ) {
                $free[$rate_id] = $rate;
                break;
            }
        }
        return !empty($free) ? $free : $rates;

    } else if ($amwalwc_hide_shipping_methods == 'hide_except_local') {
        $new_rates = array();
        foreach ($rates as $rate_id => $rate) {
            if ('free_shipping' === $rate->method_id) {
                $new_rates[$rate_id] = $rate;
                break;
            }
        }

        if (!empty($new_rates)) {
            foreach ($rates as $rate_id => $rate) {
                if ('local_pickup' === $rate->method_id) {
                    $new_rates[$rate_id] = $rate;
                    break;
                }
            }
            return $new_rates;
        }
        return $rates;
    } else {
        return $rates;
    }
}


function amwal_get_shipping_country()
{
    $countries = [];
    $countryClass = new WC_Countries();
    $countryList = $countryClass->get_shipping_countries();
    foreach ($countryList as $key=>$val){
        $countries[] = $key;
    }
    return $countries;
}
function amwalwc_get_shipping_cities($allowed_countries) {
	try {
		if ( in_array( 'states-cities-and-places-for-woocommerce/states-cities-and-places-for-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$wc_states_and_places = new WC_States_Places();
			$cities               = [];
			foreach ( $allowed_countries as $code => $country ) {
				$cities_in_country = $wc_states_and_places->get_places( $country );
				if ( gettype( $cities_in_country ) === 'array' ) {
					foreach ( $cities_in_country as $key => $value ) {
						$cities[ $country ][ $key ] = $value;
					}
				}
			}

			return $cities;
		}
	} catch (Exception $e){
		$sentryExceptionReport = new  SentryExceptionReport();
		$sentryExceptionReport->reportException($e);
	}
    return [];
}
function amwalwc_check_cart_contains_virtual($payload)
{
    $cart_contains_virtual = true;
    foreach ($payload as $item){
        if(in_array('virtual',$item) && !$item['virtual']) {
            $cart_contains_virtual = false;
            break;
        }
    }
    return $cart_contains_virtual;
}

function amwal_get_shipping_states($countries)
{
    $states = [];
    $final_states = [];
    $wc_countries = new WC_Countries();
    if(!empty($countries)) {
        foreach ($countries as $country) {
            $country_states = $wc_countries->get_states($country);
            if(!empty($country_states)){
                foreach ($country_states as $code => $name){
                    $states[$country][$code] = $name;
                }
            }

        }
        foreach ($states as $key=> $val){
            if(count($val)>0){
                $final_states[$key] = $val;
            }
        }
    }
    return $final_states;
}

function amwal_get_state_by_code($country,$state_code){
    $state = $state_code;
    $wc_countries = new WC_Countries();
    $country_states = $wc_countries->get_states($country);
    if(!empty($country_states)){
        foreach ($country_states as $code => $name){
            if($state_code == $code){
                return $name;
            }
        }
    }
    return $state;
}


