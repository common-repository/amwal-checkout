<?php

namespace Amwal\Models;

final class ProductAttribute
{
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function get_key()
    {
        return $this->key;
    }

    public function get_value()
    {
        return $this->value;
    }
}
