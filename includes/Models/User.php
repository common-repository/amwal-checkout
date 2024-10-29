<?php

namespace Amwal\Models;

class User
{

    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $first_name;
    /**
     * @var string
     */
    private $last_name;
    /**
     * @var string
     */
    private $phone_number;
	/**
	 * @var array
	 */
	private $address_details;


    public function __construct(string $email, string $first_name, string $last_name,string $phone_number,array $address_details)
    {
        $this->email            = $email;
        $this->first_name       = $first_name;
        $this->last_name        = $last_name;
        $this->phone_number     = $phone_number;
        $this->address_details  = $address_details;
    }

    public static function from_json(array $json)
    {
        return new User(
            $json['email'] ?? "",
            $json['first_name'] ?? "",
            $json['last_name'] ?? "",
            $json['phone_number']?? "",
			$json['address_details'] ?? []
        );
    }

    public function get_email()
    {
        return $this->email;
    }

    public function get_first_name()
    {
        return $this->first_name;
    }

    public function get_last_name()
    {
        return $this->last_name;
    }
    public function get_phone_number()
    {
        return $this->phone_number;
    }

	public function get_address_details(){
		return $this->address_details;
	}
}
