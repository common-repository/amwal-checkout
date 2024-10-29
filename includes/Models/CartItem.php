<?php

namespace Amwal\Models;

final class CartItem
{
    /**
     * @var int
     */
    private $product_id;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var ProductAttribute[]
     */
    private $attrs;

    public function __construct($product_id, $quantity, $attrs = [])
    {
        $this->product_id = $product_id;
        $this->quantity   = $quantity;
        $this->attrs      = $attrs;
    }

    public static function from_json(array $json)
    {
        $attrs = array_map(function ($el) {
            return new ProductAttribute($el['key'], $el['value']);
        }, $json['attributes'] ?? []);
        return new CartItem( $json['variation_id']?: $json['product_id'], $json['quantity'], $attrs);
    }

    public function get_product_id()
    {
        return absint($this->product_id);
    }

    public function get_quantity()
    {
        return absint($this->quantity);
    }

    public function get_attributes()
    {
        return $this->attrs;
    }

    public function get_attributes_array()
    {
        return array_reduce(
            $this->attrs,
            function ($acc, $el) {
                $acc[$el->get_key()] = $el->get_value();
                return $acc;
            },
            []
        );
    }
}
