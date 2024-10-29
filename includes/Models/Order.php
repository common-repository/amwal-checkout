<?php

namespace Amwal\Models;

final class Order
{

    /**
     * @var string
     */
    private $currency;
    /**
     * @var array
     */
    private $cart;
    /**
     * @var string
     */
    private $coupons;
    /**
     * @var int
     */
    private $amwal_discount;

    /**
     * @var OrderShipping
     */
    private $shipping;

    public function __construct($currency, $shipping, $coupons, $amwal_discount, array $cart)
    {
        $this->currency           = $currency;
        $this->shipping           = $shipping;
        $this->amwal_discount   = $amwal_discount;
        $this->coupons             = $coupons;
        $this->cart               = $cart;
    }

    public static function from_json(array $json)
    {
        $items = [];
        if (isset($json['cart'])) {
            $items = array_map(function ($el) {
                return CartItem::from_json($el);
            }, $json['cart']);
        }

        $shipping = NULL;
        if (isset($json['shipping'])) {
            $shipping = OrderShipping::from_json($json['shipping']);
        }

        $discount = 0;
        if (isset($json['amwal_discount'])) {
            $discount = $json['amwal_discount']['amount'];
        }
        return new Order(
            $json['currency'] ?? NULL,
            $shipping,
            $json['coupons'] ?? NULL,
            $discount,
            $items
        );
    }


    public function get_currency()
    {
        return $this->currency;
    }

	public function get_shipping()
	{
		return $this->shipping;
	}

    public function get_cart()
    {
        return $this->cart;
    }
}
