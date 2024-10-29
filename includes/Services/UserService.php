<?php

namespace Amwal\Services;

use Amwal\Models\User;

final class UserService
{

    public function get_or_create(User $user)
    {
        // $existing_user = get_user_by('email', $user->get_email());

        $existing_user_first_check = get_user_by('email', $user->get_email());

        $existing_user = $existing_user_first_check ?: get_user_by('login', str_replace('+', '00', $user->get_phone_number()));

        if ($existing_user instanceof \WP_User) {
            $user_id = $this->update_existing_user($user, $existing_user);
        } else {
            $user_id = $this->create_new_user($user);
        }
        if (is_wp_error($user_id)) {
            return null;
        }

        return $user_id;
    }

    private function create_new_user(User $user)
    {
        $email = $user->get_email();
        $userdata = [
            'ID'         => '',
            'user_pass'  => wp_generate_password(),
            'user_login' => $email!=''?$email:str_replace('+', '00', $user->get_phone_number()),
            'user_email' => $email,
            'first_name' => $user->get_first_name(),
            'last_name'  => $user->get_last_name(),
            'role'       => 'customer',
        ];
        $created_user_id = wp_insert_user(wp_slash($userdata));
        $meta_info_list = ['billing','shipping'];
        foreach ($meta_info_list as $info){
            update_user_meta( $created_user_id, $info.'_first_name', $user->get_first_name());
            update_user_meta( $created_user_id, $info.'_last_name', $user->get_last_name());
            update_user_meta( $created_user_id, $info.'_email', $email);
            update_user_meta( $created_user_id, $info.'_phone', $user->get_phone_number());
	        $this->set_address_details( $user, $created_user_id, $info );
        }

        return $created_user_id;
    }

    private function update_existing_user(User $new_user, \WP_User $old_user)
    {
        $old_user_id = $old_user->get('ID');
        $email = $new_user->get_email();
        $userdata = [
            'ID'         => $old_user_id,
            'user_pass'  => $old_user->get('pass'),
//            'user_login' => $email!=''?$email:str_replace('+', '00', $new_user->get_phone_number()),
            'user_email' => $email,
            'first_name' => $new_user->get_first_name(),
            'last_name'  => $new_user->get_last_name()
        ];
        $meta_info_list = ['billing','shipping'];
        foreach ($meta_info_list as $info){
			if(!empty($new_user->get_first_name())) update_user_meta( $old_user_id, $info.'_first_name', $new_user->get_first_name());
            if(!empty($new_user->get_last_name())) update_user_meta( $old_user_id, $info.'_last_name', $new_user->get_last_name());
			update_user_meta( $old_user_id, $info.'_email', $email);
	        if(!empty($new_user->get_phone_number())) update_user_meta( $old_user_id, $info.'_phone', $new_user->get_phone_number());
	        $this->set_address_details( $new_user, $old_user_id, $info );
        }

        return wp_update_user(wp_slash($userdata));
    }

	/**
	 * @param User $new_user
	 * @param $old_user_id
	 * @param string $info
	 *
	 * @return void
	 */
	private function set_address_details( User $new_user, $old_user_id, string $info ): void {
		if ( ! empty( $new_user->get_address_details() ) ) {
			$address_details = $new_user->get_address_details();
			update_user_meta( $old_user_id, $info . '_address_1', $address_details['street1'] ?? "" );
			update_user_meta( $old_user_id, $info . '_address_2', $address_details['street2'] ?? "" );
			update_user_meta( $old_user_id, $info . '_city', $address_details['city'] ?? "" );
			update_user_meta( $old_user_id, $info . '_postcode', $address_details['zip'] ?? "" );
			update_user_meta( $old_user_id, $info . '_country', $address_details['country'] ?? "" );
			update_user_meta( $old_user_id, $info . '_state', $address_details['state'] ?? "" );
		}
	}
}
