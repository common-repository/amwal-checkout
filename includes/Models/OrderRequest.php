<?php

namespace Amwal\Models;

final class OrderRequest
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var Address
     */
    private $shipTo;

    public function __construct(User $user, Order $order, Address $shipTo = null)
    {
        $this->user   = $user;
        $this->order  = $order;
        $this->shipTo = $shipTo;
    }

    public function get_user()
    {
        return $this->user;
    }

    public function get_order()
    {
        return $this->order;
    }

    public function get_ship_to()
    {
        return $this->shipTo ?? null;
    }
}
