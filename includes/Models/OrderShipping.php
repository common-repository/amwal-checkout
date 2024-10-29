<?php

namespace Amwal\Models;

final class OrderShipping {
	private $method_id;
	private $method_title;

	public function __construct( $rate_id, $method_title, $cost_cents ) {
		$this->rate_id = $rate_id;
		list( $method_id, $instance_id ) = array_pad( explode( ":", $rate_id ), 2, '' );
		$this->method_id    = $method_id;
		$this->instance_id  = $instance_id;
		$this->method_title = $method_title;
		$this->cost_cents   = $cost_cents;
	}

	public static function from_json( array $json ) {
		return new OrderShipping(
			$json['rate_id'],
			$json['title'] ?? '',
			$json['cost_cents']
		);
	}

	public function get_method_id() {
		return $this->method_id;
	}


	public function get_method_title() {
		return $this->method_title;
	}

	public function get_rate_id() {
		return $this->rate_id;
	}

}
