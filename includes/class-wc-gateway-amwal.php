<?php
/**
 * Class WC_Gateway_Amwal file.
 *
 * @package Amwal
 */

namespace Amwal;

use Exception;
use WC_Coupon;

/**
 * Amwal Checkout payment gateway.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Amwal extends \WC_Payment_Gateway {

    /**
     * Amwal disclaimer text.
     *
     * @var string
     */
    protected $amwal_disclaimer = '';

    /**
     * Gateway class constructor.
     */
    public function __construct() {
        $this->amwal_disclaimer = __( 'Note: Quick Checkout will continue to function even if it is disabled as a payment gateway. This option is here for integrations that require an active payment gateway code.', 'amwal-checkout' );

        $this->id                 = AMWALWC_PAYMENT_METHOD_ID;
        $this->has_fields         = false;
        $this->supports = [
            'products',
            'refunds',
        ];
        $this->init_form_fields();
        $this->init_settings();
        $this->method_title       = 'Quick Checkout';
        $this->method_description = sprintf(
            '%1$s <a href="%2$s">%3$s</a>.<br />%4$s',
            __( 'Payment through', 'amwal-checkout' ),
            esc_url( admin_url( 'admin.php?page=amwal' ) ),
            __( 'Quick Checkout', 'amwal-checkout' ),
            esc_html( $this->amwal_disclaimer )
        );


        // Load the settings.
        $this->enabled = $this->get_option( 'enabled' );
        $this->description = __( 'Pay securely with MADA, credit cards or with Apple Pay', 'amwal-checkout' );
        $this->title   = __( 'Quick Checkout', 'amwal-checkout' );

        // Action hook to saves the settings.
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array( $this, 'process_admin_options' )
        );

        // Action hook to load custom JavaScript.
        add_action("woocommerce_receipt_{$this->id}", [$this, 'receipt_page']);

        add_filter('woocommerce_gateway_icon', [$this, 'set_icons'], 10, 2);
    }

    /**
     * Initialize the form fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'amwal-checkout' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable Amwal', 'amwal-checkout' ),
                'description' => $this->amwal_disclaimer,
                'default'     => 'yes',
            ),
        );
    }
    public function set_icons($icon, $id): string
    {

        if ($id == $this->id) {
            $img = AMWALWC_PLUGIN_DIR . '/assets/images/checkout-logos.svg';
            return "<img id='amwalwc_checkout_payment_brands_logos' src='$img' >";
        }
        return $icon;
    }

    public function process_payment($order_id): array
    {

        global $woocommerce;
        $order = new \WC_Order($order_id);
//        $customerID = $order->get_customer_id();
//
//        if ($customerID > 0 && get_current_user_id() == $customerID) {
//            //Registered
//            $this->is_registered_user = true;
//        } else {
//            //Guest
//            $this->is_registered_user = false;
//        }
//
//        $shipping_cost = number_format($order->get_shipping_total(), 2, '.', '');
//
//        $orderAmount = number_format($order->get_total(), 2, '.', '');
//        $amount = number_format(round($orderAmount, 2), 2, '.', '');
//
//        $firstName = $order->get_billing_first_name();
//        $family = $order->get_billing_last_name();
//        $street = $order->get_billing_address_1();
//        $zip = $order->get_billing_postcode();
//        $city = $order->get_billing_city();
//        $state = $order->get_billing_state();
//        $country = $order->get_billing_country();
//        $email = $order->get_billing_email();
//
//
//        $firstName = preg_replace('/\s/', '', str_replace("&", "", $firstName));
//        $family = preg_replace('/\s/', '', str_replace("&", "", $family));
//        $street = preg_replace('/\s/', '', str_replace("&", "", $street));
//        $city = preg_replace('/\s/', '', str_replace("&", "", $city));
//        $state = preg_replace('/\s/', '', str_replace("&", "", $state));
//        $country = preg_replace('/\s/', '', str_replace("&", "", $country));
//
//        if (empty($state)) {
//            $state = $city;
//        }

        return [
            'result'    => 'success',
            'redirect'  => $order->get_checkout_payment_url(true)
        ];
    }

	/**
	 * Process a refund if supported.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return bool|WP_Error
	 */
    function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return false;
        }

        $request = [];

        $charge_id = $order->get_transaction_id();

        if (!$charge_id) {
            return false;
        }

        if (!is_null($amount)) {
            $request['refund_amount'] = $amount;
        }
        else{
            $request['refund_amount'] = $order->get_total();
        }


        if ($reason) {
            if (strlen($reason) > 500) {
                $reason = function_exists('mb_substr') ? mb_substr($reason, 0, 450) : substr($reason, 0, 450);
                $reason = $reason . '... [See WooCommerce order page for full text.]';
            }

            $request['metadata'] = ['reason' => $reason,];
        }

        $request['transactions_id'] = $charge_id;
        return amwalwc_refund_request($order, $request);
    }

    function receipt_page($order_id)
    {
        amwalwc_render_cart(AMWALWC_BTNPOS_BEFORE_CHECKOUT, $order_id);
    }
}

