<?php
/**
 * Amwal Plugin Settings
 *
 * Adds config UI for wp-admin.
 *
 * @package Amwal
 */

// Load admin notices.
require_once AMWALWC_PATH . 'includes/admin/notices.php';
// Load admin constants.
require_once AMWALWC_PATH . 'includes/admin/constants.php';
// Load admin fields.
require_once AMWALWC_PATH . 'includes/admin/fields.php';

/**
 * Add timestamp when an option is updated.
 *
 * @param string $option    Name of the updated option.
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 */
function amwalwc_updated_option( $option, $old_value, $value ) {
	if ( $old_value === $value ) {
		return;
	}

	$stampable_options = array(
		AMWALWC_SETTING_APP_ID
	);

	if ( in_array( $option, $stampable_options, true ) ) {
		$amwalwc_settings_timestamps = get_option( AMWALWC_SETTINGS_TIMESTAMPS, array() );

		$amwalwc_settings_timestamps[ $option ] = time();

		update_option( AMWALWC_SETTINGS_TIMESTAMPS, $amwalwc_settings_timestamps );
	}
}
add_action( 'updated_option', 'amwalwc_updated_option', 10, 3 );

add_action( 'admin_menu', 'amwalwc_admin_create_menu' );
add_action( 'admin_init', 'amwalwc_maybe_redirect_after_activation', 1 );
add_action( 'admin_init', 'amwalwc_admin_setup_sections' );
add_action( 'admin_init', 'amwalwc_admin_setup_fields' );

/**
 * Add plugin action links to the Amwal plugin on the plugins page.
 *
 * @param array  $plugin_meta The list of links for the plugin.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array  $plugin_data An array of plugin data.
 * @param string $status      Status filter currently applied to the plugin list. Possible
 *                            values are: 'all', 'active', 'inactive', 'recently_activated',
 *                            'upgrade', 'mustuse', 'dropins', 'search', 'paused',
 *                            'auto-update-enabled', 'auto-update-disabled'.
 *
 * @return array
 */
function amwalwc_admin_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
	if ( plugin_basename( AMWALWC_PATH . 'amwal.php' ) !== $plugin_file ) {
		return $plugin_meta;
	}

	// Add "Become a Seller!" CTA if the Amwal App ID has not yet been set.
	if ( function_exists( 'amwalwc_get_app_id' ) ) {
		$amwal_app_id = amwalwc_get_app_id();

		if ( empty( $amwal_app_id ) ) {
			$amwalwc_setting_amwal_onboarding_url = get_option(AMWALWC_MERCHANT_DASHBOARD);

			$plugin_meta[] = printf(
				'%1$s <a href="%2$s" target="_blank" rel="noopener">%3$s</a>',
				esc_html__( "Don't have an app yet?", 'amwal-checkout' ),
				esc_url( $amwalwc_setting_amwal_onboarding_url ),
				esc_html__( 'Create account on Amwal.tech to get an Merchant ID and enter it here.', 'amwal-checkout' )
			);
		}
	}

	$plugin_meta[] = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( admin_url( 'admin.php?page=amwal' ) ),
		esc_html__( 'Settings', 'amwal-checkout' )
	);

	return $plugin_meta;
}
add_action( 'plugin_row_meta', 'amwalwc_admin_plugin_row_meta', 10, 4 );

/**
 * Registers the Amwal menu within wp-admin.
 */
function amwalwc_admin_create_menu() {
	// Add the menu item and page.
	$page_title = __('Amwal Settings', 'amwal-checkout');
	$menu_title = __('Amwal Checkout','amwal-checkout');
	$capability = 'manage_options';
	$slug       = 'amwal';
	$callback   = 'amwalwc_settings_page_content';
	$icon 		= 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iNzMuMDAwMDAwcHQiIGhlaWdodD0iNjEuMDAwMDAwcHQiIHZpZXdCb3g9IjAgMCA3My4wMDAwMDAgNjEuMDAwMDAwIgogcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgbWVldCI+Cgo8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLjAwMDAwMCw2MS4wMDAwMDApIHNjYWxlKDAuMTAwMDAwLC0wLjEwMDAwMCkiCmZpbGw9IiMwMDAwMDAiIHN0cm9rZT0ibm9uZSI+CjxwYXRoIGQ9Ik0yMCA1OTAgYy0xOSAtMTkgLTIwIC0zMyAtMjAgLTI4OSAwIC0yNjggMCAtMjcwIDIyIC0yODUgMTkgLTE0IDcwCi0xNiAzNTAgLTE2IDMyNiAwIDMyNyAwIDM0MiAyMiAxNCAxOSAxNiA2NCAxNiAyOTAgMCAyNjQgMCAyNjcgLTIyIDI4MiAtMTkKMTQgLTY5IDE2IC0zNDUgMTYgLTMxMCAwIC0zMjQgLTEgLTM0MyAtMjB6IG0zOTUgLTg2IGMxOSAtMTAgMzEgLTExIDMzIC01IDIKNiAyOCAxMSA1OCAxMSBsNTQgMCAwIC0yMDAgMCAtMjAwIC01NSAwIGMtMzAgMCAtNTUgNCAtNTUgOSAwIDYgLTE3IDMgLTM3IC02Ci0xMTAgLTQ1IC0yMzQgNDEgLTI1MCAxNzUgLTcgNjQgMTAgMTE0IDU2IDE2NSA1OSA2NSAxMzIgODQgMTk2IDUxeiIvPgo8cGF0aCBkPSJNMzE4IDM5NSBjLTU5IC0zMiAtNTggLTEzOSAyIC0xNzAgNDMgLTIyIDY4IC0xOCAxMDEgMTQgMjMgMjQgMjkgMzgKMjkgNzQgMCAzOCAtNSA0OSAtMzEgNzEgLTM0IDI5IC02MSAzMiAtMTAxIDExeiIvPgo8L2c+Cjwvc3ZnPgo=';
	$position   = 100;

	add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
}

/**
 * Maybe redirect to the Amwal settings page after activation.
 */
function amwalwc_maybe_redirect_after_activation() {
	$activated = get_option( AMWALWC_PLUGIN_ACTIVATED, false );

	if ( $activated ) {
		// Delete the flag to prevent an endless redirect loop.
		delete_option( AMWALWC_PLUGIN_ACTIVATED );

		// Redirect to the Amwal settings page.
		wp_safe_redirect(
			esc_url(
				admin_url( 'admin.php?page=amwal' )
			)
		);
		exit;
	}
}

/**
 * Get the list of tabs for the Amwal settings page.
 *
 * @return array
 */
function amwalwc_get_settings_tabs() {
	/**
	 * Filter the list of settins tabs.
	 *
	 * @param array $settings_tabs The settings tabs.
	 *
	 * @return array
	 */
	return apply_filters(
		'amwalwc_settings_tabs',
		array(
			'amwal_app_info'  => __( 'Configuration', 'amwal-checkout' ),
			'amwal_address'  => __( 'Address Options', 'amwal-checkout' ),
			'amwal_shipping'  => __( 'Shipping Options', 'amwal-checkout' ),
			'amwal_installments'  => __( 'Installments Options', 'amwal-checkout' ),
			'amwal_options'    => __( 'Button Options', 'amwal-checkout' ),
			'amwal_styles'   => __( 'Styles', 'amwal-checkout' ),
			'amwal_offers'   => __( 'Offers', 'amwal-checkout' ),
		)
	);
}

/**
 * Get the active tab in the Amwal settings page.
 *
 * @return string
 */
function amwalwc_get_active_tab() {
	return isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'amwal_app_info'; // phpcs:ignore
}

/**
 * Renders content of Amwal settings page.
 */
function amwalwc_settings_page_content() {
	amwalwc_load_template( 'admin/amwal-settings' );
}

/**
 * Sets up sections for Amwal settings page.
 */
function amwalwc_admin_setup_sections() {

	$section_name = 'amwal_app_info';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_APP_ID );
	register_setting( $section_name, AMWALWC_SETTING_APP_SECRET );
	register_setting( $section_name, AMWALWC_SETTING_CURRENCY );
	register_setting( $section_name, AMWALWC_SETTING_GUEST_AUTO_LOGIN );
	register_setting( $section_name, AMWALWC_SETTING_TEST_MODE );
	register_setting( $section_name, AMWALWC_SETTING_DEBUG_MODE );
	register_setting( $section_name, AMWALWC_SETTING_USE_DARK_MODE );

//	register_setting( $section_name, AMWALWC_SETTINGS_ENABLE_CRONJOBS );


	$section_name = 'amwal_address';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_ADDRESS_REQUIRED_IN_VIRUAL_PRODUCT );
	register_setting( $section_name, AMWALWC_SETTING_ADDRESS_STREET2_REQUIRED );
	register_setting( $section_name, AMWALWC_SETTING_GUEST_EMAIL_REQUIRED );

	$section_name = 'amwal_shipping';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_HIDE_SHIPPING_METHODS );
	register_setting( $section_name, AMWALWC_SETTING_EXCLUDE_SHIPPING_METHODS );
	register_setting( $section_name, AMWALWC_SETTING_POSTCODE_OPTIONAL_COUNTRIES );
	register_setting( $section_name, AMWALWC_SETTING_SHIPPING_EN_STATES );
	register_setting( $section_name, AMWALWC_SETTING_SHIPPING_EN_CITIES);
	register_setting( $section_name, AMWALWC_SETTING_SHIPPING_AR_STATES );
	register_setting( $section_name, AMWALWC_SETTING_SHIPPING_AR_CITIES);

	$section_name = 'amwal_installments';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_EXCLUDE_INSTALLMENT_SLUGS );
	register_setting( $section_name, AMWALWC_SETTING_INSTALLMENT_BANK_OPTIONS );
	register_setting( $section_name, AMWALWC_SETTING_INSTALLMENT_REDIRECT_OPTIONS );

	$section_name = 'amwal_styles';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_EXTRA_EVENTS );
	register_setting( $section_name, AMWALWC_SETTING_PDP_BUTTON_STYLES );
	register_setting( $section_name, AMWALWC_SETTING_CART_BUTTON_STYLES );
	register_setting( $section_name, AMWALWC_SETTING_MINI_CART_BUTTON_STYLES );
	register_setting( $section_name, AMWALWC_SETTING_CHECKOUT_BUTTON_STYLES );
	register_setting( $section_name, AMWALWC_SETTING_EXTRA_STYLES );

	$section_name = 'amwal_options';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_PRODUCT_BUTTON_PLACEMENT );
//	register_setting( $section_name, AMWALWC_SETTING_HIDE_PRODUCT_BUTTON_IF_CART_NOT_EMPTY );
	register_setting( $section_name, AMWALWC_SETTING_CART_PAGE_BUTTON_PLACEMENT );
	register_setting( $section_name, AMWALWC_SETTING_CHECKOUT_PAGE_BUTTON_PLACEMENT );


	$section_name = 'amwal_offers';
	add_settings_section( $section_name, '', false, $section_name );
	register_setting( $section_name, AMWALWC_SETTING_SHOW_DISCOUNT_RIBBON );
	register_setting( $section_name, AMWALWC_SETTINGS_PROMO_SNIPPET_MESSAGE );
	register_setting( $section_name, AMWALWC_SETTINGS_PROMO_SNIPPET_POSITION );
	register_setting( $section_name, AMWALWC_SETTING_PROMO_CODE );
	register_setting( $section_name, AMWALWC_SETTING_BINS_OFFERS );
//	register_setting( $section_name, AMWALWC_SETTING_SHOW_BINS_PROMO_IN_ORDERS );
	register_setting( $section_name, AMWALWC_SETTING_BINS_PROMO_CODE );

	$section_name = 'amwal_hidden';
	register_setting( $section_name, AMWALWC_SETTING_AUTO_RENDER_PRODUCT_BUTTON );
	register_setting( $section_name, AMWALWC_SETTING_AUTO_RENDER_CART_BUTTON );
	register_setting( $section_name, AMWALWC_SETTING_AUTO_RENDER_CHECKOUT_PAGE_BUTTON );
	register_setting( $section_name, AMWALWC_SETTING_AUTO_RENDER_MINICART_BUTTON );
	register_setting( $section_name, AMWALWC_SETTING_SENTRY_ENABLED );
	register_setting( $section_name, AMWALWC_SETTING_SENTRY_DSN );

}

/**
 * Sets up fields for Amwal settings page.
 */
function amwalwc_admin_setup_fields() {
	// App Info settings.
	$settings_section = 'amwal_app_info';
	add_settings_field( AMWALWC_SETTING_APP_ID, __( 'Amwal API Key', 'amwal-checkout' ), 'amwalwc_app_id_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_APP_SECRET, __( 'Amwal API Secret', 'amwal-checkout' ), 'amwalwc_app_secret_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_CURRENCY, __( 'Currency', 'amwal-checkout' ), 'amwal_currency_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_GUEST_AUTO_LOGIN, __( 'Guest Auto Login', 'amwal-checkout' ), 'amwalwc_guest_auto_login', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_USE_DARK_MODE, __( 'Dark Mode', 'amwal-checkout' ), 'amwalwc_setting_use_dark_mode', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_TEST_MODE, __( 'Test Mode', 'amwal-checkout' ), 'amwalwc_test_mode_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_DEBUG_MODE, __( 'Debug Mode', 'amwal-checkout' ), 'amwalwc_debug_mode_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTINGS_ENABLE_CRONJOBS, __( 'Cron Jobs', 'amwal-checkout' ), 'amwalwc_cron_jobs_content', $settings_section, $settings_section );


	$settings_section = 'amwal_address';
	add_settings_field( AMWALWC_SETTING_ADDRESS_STREET2_REQUIRED, __( 'Street 2 Required ', 'amwal-checkout' ), 'amwalwc_address_street2_required_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_ADDRESS_REQUIRED_IN_VIRUAL_PRODUCT, __( 'Address Required In Virtual Products ', 'amwal-checkout' ), 'amwalwc_address_required_in_virtual_product_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_GUEST_EMAIL_REQUIRED, __( 'Guest Email Required', 'amwal-checkout' ), 'amwalwc_guest_email_required_content', $settings_section, $settings_section );

	$settings_section = 'amwal_shipping';
	add_settings_field( AMWALWC_SETTING_HIDE_SHIPPING_METHODS, __( 'Hide shipping methods', 'amwal-checkout' ), 'amwalwc_hide_shipping_methods_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_EXCLUDE_SHIPPING_METHODS, __( 'Exclude shipping methods', 'amwal-checkout' ), 'amwalwc_exclude_shipping_methods_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_POSTCODE_OPTIONAL_COUNTRIES, __( 'Optional Postal Code', 'amwal-checkout' ), 'amwalwc_postcode_optional_countries_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_SHIPPING_EN_STATES, __( 'Shipping to states in English', 'amwal-checkout' ), 'amwalwc_shipping_en_states_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_SHIPPING_EN_CITIES, __( 'Shipping to cities  in English', 'amwal-checkout' ), 'amwalwc_shipping_en_cities_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_SHIPPING_AR_STATES, __( 'Shipping to states in Arabic', 'amwal-checkout' ), 'amwalwc_shipping_ar_states_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_SHIPPING_AR_CITIES, __( 'Shipping to cities in Arabic', 'amwal-checkout' ), 'amwalwc_shipping_ar_cities_content', $settings_section, $settings_section );

	$settings_section = 'amwal_installments';
//	add_settings_field( AMWALWC_SETTING_INSTALLMENT_BANK_OPTIONS, __( 'Enable Bank Installments', 'amwal-checkout' ), 'amwalwc_setting_installment_options_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_EXCLUDE_INSTALLMENT_SLUGS, __( 'Product Instalment Slugs', 'amwal-checkout' ), 'amwalwc_setting_exclude_installment_slugs_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_INSTALLMENT_REDIRECT_OPTIONS, __( 'Checkout URL', 'amwal' ), 'amwalwc_setting_installment_redirect_options_content', $settings_section, $settings_section );


	// Button style settings.
	$settings_section = 'amwal_options';

//	add_settings_field( AMWALWC_SETTING_AUTO_RENDER_PRODUCT_BUTTON, __( 'Automatically render button in product page', 'amwal-checkout' ), 'amwal_auto_render_product_button_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_PRODUCT_BUTTON_PLACEMENT, __( 'Product button placement', 'amwal-checkout' ), 'amwalwc_product_button_placement_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTING_HIDE_PRODUCT_BUTTON_IF_CART_NOT_EMPTY, __( 'Hide button if cart contains at least one item', 'amwal-checkout' ), 'amwalwc_hide_product_button_if_cart_not_empty_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTING_AUTO_RENDER_CART_BUTTON, __( 'Automatically render button in cart page', 'amwal-checkout' ), 'amwal_auto_render_cart_button_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_CART_PAGE_BUTTON_PLACEMENT, __( 'Cart page button placement', 'amwal-checkout' ), 'amwalwc_cart_page_button_placement_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTING_AUTO_RENDER_CHECKOUT_PAGE_BUTTON, __( 'Automatically render button in checkout page', 'amwal-checkout' ), 'amwalwc_auto_render_checkout_page_button_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTING_CHECKOUT_PAGE_BUTTON_PLACEMENT, __( 'Checkout page button placement', 'amwal-checkout' ), 'amwalwc_checkout_page_button_placement_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTING_AUTO_RENDER_MINICART_BUTTON, __( 'Render quick checkout in minicart widget', 'amwal-checkout' ), 'amwalwc_auto_render_minicart_button_content', $settings_section, $settings_section );


	// Button options settings.
	$settings_section = 'amwal_styles';
	add_settings_field( AMWALWC_SETTING_EXTRA_EVENTS, __( 'Extra javascript events', 'amwal-checkout' ), 'amwalwc_checkout_extra_events_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_PDP_BUTTON_STYLES, __( 'Product page button styles', 'amwal-checkout' ), 'amwalwc_pdp_button_styles_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_CART_BUTTON_STYLES, __( 'Cart page button styles', 'amwal-checkout' ), 'amwalwc_cart_button_styles_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_MINI_CART_BUTTON_STYLES, __( 'Mini cart widget button styles', 'amwal-checkout' ), 'amwalwc_mini_cart_button_styles_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_CHECKOUT_BUTTON_STYLES, __( 'Checkout page button styles', 'amwal-checkout' ), 'amwalwc_checkout_button_styles_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_EXTRA_STYLES, __( 'Extra styles', 'amwal-checkout' ), 'amwalwc_extra_styles_content', $settings_section, $settings_section );

	$settings_section = 'amwal_offers';
	add_settings_field( AMWALWC_SETTING_SHOW_DISCOUNT_RIBBON, __( "Show discount ribbon", 'amwal-checkout' ), 'amwalwc_show_discount_ribbon_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTINGS_PROMO_SNIPPET_MESSAGE, __( "Promo snippet message", 'amwal-checkout' ), 'amwalwc_promo_snippet_message_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTINGS_PROMO_SNIPPET_POSITION, __( "Promo snippet position", 'amwal-checkout' ), 'amwalwc_promo_snippet_position_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_PROMO_CODE, __( "Promo code for Amwal orders", 'amwal-checkout' ), 'amwalwc_promo_code_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_BINS_PROMO_CODE, __( 'Promo code for cards bins', 'amwal-checkout' ), 'amwalwc_amwalwc_bins_promo_code_content', $settings_section, $settings_section );
	add_settings_field( AMWALWC_SETTING_BINS_OFFERS, __( 'Cards bins', 'amwal-checkout' ), 'amwalwc_bins_offers_content', $settings_section, $settings_section );
//	add_settings_field( AMWALWC_SETTING_SHOW_BINS_PROMO_IN_ORDERS, __( "Show in Orders", 'amwal-checkout' ), 'amwalwc_show_bins_promo_in_orders_content', $settings_section, $settings_section );
}

/**
 * Renders the App ID field.
 */
function amwalwc_app_id_content() {
	$amwalwc_setting_app_id              = amwalwc_get_app_id();
	$amwalwc_setting_amwal_onboarding_url = get_option(AMWALWC_MERCHANT_DASHBOARD);

	$description = '';
	if ( empty( $amwalwc_setting_app_id ) ) {
		$description = sprintf(
			'%1$s <a href="%2$s" target="_blank" rel="noopener">%3$s</a>',
			esc_html__( "Don't have an app yet?", 'amwal-checkout' ),
			esc_url( $amwalwc_setting_amwal_onboarding_url ),
			esc_html__( 'Create account on Amwal.tech to get an Merchant ID and enter it here.', 'amwal-checkout' )
		);
	}

	amwalwc_settings_field_input(
		array(
			'name'        => 'amwal_app_id',
			'value'       => $amwalwc_setting_app_id,
			'description' => $description,
			'style'		  => 'width:30%'
		)
	);
}

add_filter( 'pre_update_option_amwal_app_id', function( $new_value, $old_value ) {
    if($new_value === ''){
        update_option('amwal_valid_app_id',false);
    }
    if($new_value != $old_value){
        $result = checkMerchant($new_value);
        if(gettype($result) == 'array' || $result != null){
            $valid = array_key_exists('valid',$result) && $result['valid'] == 1;
            update_option('amwal_valid_app_id',$valid);
        }else{
            update_option('amwal_valid_app_id',false);
        }
    }
    return $new_value;
  
  }, 10, 2);

function checkMerchant($merchant_id)
{
    $url = AMWALWC_SERVER_URI.'/merchant/check/'.$merchant_id;
    $res = wp_remote_retrieve_body( wp_remote_get( $url ) );
    return json_decode($res,true);
}

function amwalwc_app_secret_content() {
    $amwalwc_setting_app_secret              = amwalwc_get_app_secret();
    $amwalwc_setting_amwal_onboarding_url = get_option(AMWALWC_MERCHANT_DASHBOARD);

    $description = '';
    if ( empty( $amwalwc_setting_app_secret ) ) {
        $description = sprintf(
            '%1$s <a href="%2$s" target="_blank" rel="noopener">%3$s</a>',
            esc_html__( "Don't have a Secret Api Key yet ?", 'amwal-checkout' ),
            esc_url( $amwalwc_setting_amwal_onboarding_url ),
            esc_html__( 'Go to merchant account. Get the Secret API Key.', 'amwal-checkout')
        );
    }

    amwalwc_settings_field_input(
        array(
            'name'        => 'amwal_app_secret',
            'value'       => $amwalwc_setting_app_secret,
            'description' => $description,
            'style'		  => 'width:30%'
        )
    );
}

//function amwalwc_setting_installment_options_content() {
//
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTING_INSTALLMENT_BANK_OPTIONS,
//			'label'       => __( 'Enable bank installments', 'amwal-checkout' ),
//			'current'       => get_option(AMWALWC_SETTING_INSTALLMENT_BANK_OPTIONS),
//		)
//	);
//}


function amwalwc_setting_installment_redirect_options_content() {

	$description = __('Add a full link of checkout url to be able to use installment options in Amwal Checkout Button.', 'amwal-checkout');

	amwalwc_settings_field_input(
		array(
			'name'        => AMWALWC_SETTING_INSTALLMENT_REDIRECT_OPTIONS,
			'value'       => get_option(AMWALWC_SETTING_INSTALLMENT_REDIRECT_OPTIONS),
			'description' => $description,
			'style'		  => 'width:30%'
		)
	);
}

function amwalwc_setting_exclude_installment_slugs_content() {

	$description = __('Add product categories,tags slugs comma separated to exclude from installment options in Amwal Checkout Button.', 'amwal-checkout');

	amwalwc_settings_field_input(
		array(
			'name'        => AMWALWC_SETTING_EXCLUDE_INSTALLMENT_SLUGS,
			'value'       => get_option(AMWALWC_SETTING_EXCLUDE_INSTALLMENT_SLUGS),
			'description' => $description,
			'style'		  => 'width:30%'
		)
	);
}
function amwal_currency_content(){
	$amwalwc_setting_currency = amwalwc_get_option_or_set_default( AMWALWC_SETTING_CURRENCY, "SA" );
	amwalwc_settings_field_input(
		array(
			'name'        => 'amwal_checkout_currency',
			'value'       => $amwalwc_setting_currency,
			'style'		  => 'width:30%'
		)
	);
}

function amwalwc_guest_auto_login() {
	$amwalwc_guest_auto_login = get_option( AMWALWC_SETTING_GUEST_AUTO_LOGIN, 0 );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_GUEST_AUTO_LOGIN,
			'current'     => $amwalwc_guest_auto_login,
			'label'       => __( 'Enable guest to automatically login after successful payment', 'amwal-checkout' ),
			'description' => __( 'This will allow the guest to view the thank you page after successful payment.', 'amwal-checkout' ),
		)
	);
}

/**
 * Renders a checkbox to set whether or not to enable dark mode.
 */
function amwalwc_setting_use_dark_mode() {
	$amwalwc_use_dark_mode = get_option( AMWALWC_SETTING_USE_DARK_MODE, 0 );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_USE_DARK_MODE,
			'current'     => $amwalwc_use_dark_mode,
			'label'       => __( 'Enable Dark Mode for the Amwal Buttons.', 'amwal-checkout' ),
			'description' => __( 'When this box is checked, the Amwal buttons will be rendered in dark mode.', 'amwal-checkout' ),
		)
	);
}

/**
 * Renders the PDP button styles field.
 */
function amwalwc_pdp_button_styles_content() {
	$amwalwc_setting_pdp_button_styles = amwalwc_get_option_or_set_default( AMWALWC_SETTING_PDP_BUTTON_STYLES, AMWALWC_SETTING_PDP_BUTTON_STYLES_DEFAULT );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwal_pdp_button_styles',
			'value' => $amwalwc_setting_pdp_button_styles,
		)
	);
}

/**
 * Renders the cart button styles field.
 */
function amwalwc_cart_button_styles_content() {
	$amwalwc_setting_cart_button_styles = amwalwc_get_option_or_set_default( AMWALWC_SETTING_CART_BUTTON_STYLES, AMWALWC_SETTING_CART_BUTTON_STYLES_DEFAULT );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwal_cart_button_styles',
			'value' => $amwalwc_setting_cart_button_styles,
		)
	);
}

/**
 * Renders the mini-cart button styles field.
 */
function amwalwc_mini_cart_button_styles_content() {
	$amwalwc_setting_mini_cart_button_styles = amwalwc_get_option_or_set_default( AMWALWC_SETTING_MINI_CART_BUTTON_STYLES, AMWALWC_SETTING_MINI_CART_BUTTON_STYLES_DEFAULT );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwal_mini_cart_button_styles',
			'value' => $amwalwc_setting_mini_cart_button_styles,
		)
	);
}

/**
 * Renders the checkout button styles field.
 */
function amwalwc_checkout_button_styles_content() {
	$amwalwc_setting_checkout_button_styles = amwalwc_get_option_or_set_default( AMWALWC_SETTING_CHECKOUT_BUTTON_STYLES, AMWALWC_SETTING_CHECKOUT_BUTTON_STYLES_DEFAULT );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwal_checkout_button_styles',
			'value' => $amwalwc_setting_checkout_button_styles,
		)
	);
}

function amwalwc_extra_styles_content() {
	$amwalwc_setting_extra_styles = amwalwc_get_option_or_set_default( AMWALWC_SETTING_EXTRA_STYLES, '' );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwal_extra_styles',
			'value' => $amwalwc_setting_extra_styles,
		)
	);
}
function amwalwc_checkout_extra_events_content() {
	$amwalwc_setting_extra_events = amwalwc_get_option_or_set_default( AMWALWC_SETTING_EXTRA_EVENTS, AMWALWC_SETTING_EXTRA_EVENTS_DEFAULT );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwal_checkout_extra_events',
			'value' => $amwalwc_setting_extra_events,
		)
	);
}

function amwalwc_promo_code_content() {
	$amwalwc_promo_code = get_option( AMWALWC_SETTING_PROMO_CODE, "" );

	amwalwc_settings_field_input(
		array(
			'name'  => 'amwalwc_promo_code',
			'value' => $amwalwc_promo_code,
			'style'		  => 'width:30%'
		)
	);
}
//
//function amwalwc_show_bins_promo_in_orders_content() {
//	$amwalwc_show_bins_promo_in_orders = get_option( AMWALWC_SETTING_SHOW_BINS_PROMO_IN_ORDERS, false );
//
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTING_SHOW_BINS_PROMO_IN_ORDERS,
//			'current'     => $amwalwc_show_bins_promo_in_orders,
//			'description' => __( 'When this box is checked, the coupon code will be shown in WooCommerce -> Orders as column named "Amwal Promo" ', 'amwal-checkout' ),
//		)
//	);
//}
function amwalwc_promo_snippet_message_content() {
	$amwalwc_promo_snippet_message = get_option( AMWALWC_SETTINGS_PROMO_SNIPPET_MESSAGE, "" );

	amwalwc_settings_field_input(
		array(
			'name'  => 'amwalwc_settings_promo_snippet_message',
			'value' => $amwalwc_promo_snippet_message,
			'style'		  => 'width:30%'
		)
	);
}
function amwalwc_show_discount_ribbon_content() {
	$amwalwc_show_discount_ribbon_message = get_option( AMWALWC_SETTING_SHOW_DISCOUNT_RIBBON, "" );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_SHOW_DISCOUNT_RIBBON,
			'current'     => $amwalwc_show_discount_ribbon_message,
			'label'       => __( 'Show discount ribbon.', 'amwal-checkout' )
		)
	);
}

function amwalwc_promo_snippet_position_content() {
	$amwalwc_product_button_placement = amwalwc_get_option_or_set_default(AMWALWC_SETTINGS_PROMO_SNIPPET_POSITION, 'woocommerce_after_add_to_cart_button');

	$location_options = array(
		'woocommerce_before_add_to_cart_form' => __('Before Add To Cart Form', 'amwal-checkout'),
		'woocommerce_after_add_to_cart_form' => __('After Add To Cart Form', 'amwal-checkout'),
		'woocommerce_after_add_to_cart_button' => __('After Add To Cart Button', 'amwal-checkout'),
		'woocommerce_after_single_product_summary' => __('After Product Summary', 'amwal-checkout'),
	);

	amwalwc_settings_field_select(
		array(
			'name'        => AMWALWC_SETTINGS_PROMO_SNIPPET_POSITION,
			'options'     => $location_options,
			'value'       => $amwalwc_product_button_placement,
		)
	);
}


//add_filter( 'pre_update_option_' . AMWALWC_SETTINGS_PROMO_SNIPPET_MESSAGE, 'amwalwc_update_promo_snippet_message', 10, 2);
//function amwalwc_update_promo_snippet_message($new_value, $old_value){
//	$button_position = get_option('amwalwc_settings_promo_snippet_position', 'woocommerce_after_add_to_cart_button');
//	if(!empty($new_value)){
//		add_filter(
//			$button_position,
//			'amwalwc_custom_promo_snippet_message',
//			10
//		);
//
//	}
//	else{
//		remove_filter($button_position, 'amwalwc_custom_promo_snippet_message');
//	}
//	return $new_value;
//}
//
//


function amwalwc_bins_offers_content() {
	$amwalwc_bins_offers = get_option( AMWALWC_SETTING_BINS_OFFERS, "" );

	amwalwc_settings_field_textarea(
		array(
			'name'  => 'amwalwc_bins_offers',
			'value' => $amwalwc_bins_offers,
			'description' => __( 'Add cards bins comma separated to add offers to them. i.e : 123456,654321', 'amwal-checkout' ),
		)
	);
}
function amwalwc_amwalwc_bins_promo_code_content() {
	$amwalwc_bins_promo_code = get_option( AMWALWC_SETTING_BINS_PROMO_CODE, "" );

	amwalwc_settings_field_input(
		array(
			'name'  => 'amwalwc_bins_promo_code',
			'value' => $amwalwc_bins_promo_code,
			'style'		  => 'width:30%'
		)
	);
}



function amwalwc_product_button_placement_content() {
	$amwalwc_product_button_placement = amwalwc_get_option_or_set_default(AMWALWC_SETTING_PRODUCT_BUTTON_PLACEMENT, 'woocommerce_after_add_to_cart_button');

	$location_options = array(
        'woocommerce_before_add_to_cart_form' => __('Before Add To Cart Form', 'amwal-checkout'),
        'woocommerce_after_add_to_cart_form' => __('After Add To Cart Form', 'amwal-checkout'),
//		'woocommerce_before_add_to_cart_quantity' => __( 'Before Quantity' , 'amwal-checkout'),
//		'woocommerce_after_add_to_cart_quantity' => __( 'After Quantity' , 'amwal-checkout'),
        'woocommerce_after_add_to_cart_button' => __('After Add To Cart Button', 'amwal-checkout'),
        'woocommerce_after_single_product_summary' => __('After Product Summary', 'amwal-checkout'),
    );

	amwalwc_settings_field_select(
		array(
			'name'        => AMWALWC_SETTING_PRODUCT_BUTTON_PLACEMENT,
			'options'     => $location_options,
			'value'       => $amwalwc_product_button_placement,
		)
	);
}


function amwalwc_exclude_shipping_methods_content() {
    $amwalwc_exclude_shipping_method_placement = amwalwc_get_option_or_set_default(AMWALWC_SETTING_EXCLUDE_SHIPPING_METHODS,array());
    if ( ! empty( $amwalwc_exclude_shipping_method_placement ) ) {
        if ( ! is_array( $amwalwc_exclude_shipping_method_placement ) ) {
            $amwalwc_exclude_shipping_method_placement = array( $amwalwc_exclude_shipping_method_placement );
        }
    }
    $shipping_methods = array();
    foreach(WC()->shipping->get_shipping_methods() as $key=>$val) {
        $shipping_methods[$val->id] = $val->method_title;
    }
    amwalwc_settings_field_ajax_select(
        array(
            'name'        => AMWALWC_SETTING_EXCLUDE_SHIPPING_METHODS,
            'options'     => $shipping_methods,
            'selected'    => $amwalwc_exclude_shipping_method_placement,
            'class'       => 'amwal-select amwal-select-exclude-shipping-methods',
            'description' => __( 'Exclude Shipping methods ', 'amwal-checkout' ),
            'nonce'       => 'exclude-shipping-methods',
        )
    );
}

function amwalwc_shipping_en_states_content() {
	$amwalwc_setting_shipping_en_states_placement = get_option( AMWALWC_SETTING_SHIPPING_EN_STATES);

	amwalwc_settings_field_textarea(
		array(
			'name'  => AMWALWC_SETTING_SHIPPING_EN_STATES,
			'description' => 'Allowed shipping to states in English is a dictionary of states in the countries you are shipping to. For example : {"SA":{ "SA-01" : "Riyadh", "SA-02" : "Makkah", "SA-03" : "Al Madinah"}}',
			'value' => $amwalwc_setting_shipping_en_states_placement,
		)
	);
}
function amwalwc_shipping_en_cities_content() {
	$amwalwc_setting_shipping_en_cities_placement = get_option( AMWALWC_SETTING_SHIPPING_EN_CITIES);

	amwalwc_settings_field_textarea(
		array(
			'name'  => AMWALWC_SETTING_SHIPPING_EN_CITIES,
			'description' => 'Allowed shipping to cities in English is a dictionary of cities in the states you are shipping to. For example : {"SA":{ "SA-01" : ["Afif","Al Aflaj"]}}',
			'value' => $amwalwc_setting_shipping_en_cities_placement,
		)
	);
}

function amwalwc_shipping_ar_states_content() {
	$amwalwc_setting_shipping_ar_states_placement = get_option( AMWALWC_SETTING_SHIPPING_AR_STATES);

	amwalwc_settings_field_textarea(
		array(
			'name'  => AMWALWC_SETTING_SHIPPING_AR_STATES,
			'description' => 'Allowed shipping to cities in Arabic is a dictionary of cities in the states you are shipping to. For example : {"SA":{ "SA-01" : "الرياض", "SA-02" : "مكه", "SA-03" : "المدينه"}}',
			'value' => $amwalwc_setting_shipping_ar_states_placement,
		)
	);
}

function amwalwc_shipping_ar_cities_content() {
	$amwalwc_setting_shipping_ar_cities_placement = get_option( AMWALWC_SETTING_SHIPPING_AR_CITIES);

	amwalwc_settings_field_textarea(
		array(
			'name'  => AMWALWC_SETTING_SHIPPING_AR_CITIES,
			'description' =>  'Allowed shipping to cities in Arabic is a dictionary of cities in the states you are shipping to. For example : {"SA":{ "SA-01" : ["عفيف","الافلاج"]}}',
			'value' => $amwalwc_setting_shipping_ar_cities_placement,
		)
	);
}


foreach ([AMWALWC_SETTING_SHIPPING_EN_STATES, AMWALWC_SETTING_SHIPPING_EN_CITIES, AMWALWC_SETTING_SHIPPING_AR_STATES, AMWALWC_SETTING_SHIPPING_AR_CITIES] as $item){
	add_filter( 'pre_update_option_' . $item, function ( $new_value, $old_value ) {
		if ( ! empty( $new_value ) ) {
			$before_validation = sanitize_text_field( trim($new_value));
			if(!empty($before_validation) && json_decode($before_validation) != null && in_array(gettype(json_decode($before_validation)),['array','object'])){
				return $before_validation;
			}
		}
		return "";
	}, 10, 2 );
}


function amwalwc_postcode_optional_countries_content() {
    $amwalwc_postcode_optional_countries_placement = amwalwc_get_option_or_set_default(AMWALWC_SETTING_POSTCODE_OPTIONAL_COUNTRIES,array());
    if ( ! empty( $amwalwc_postcode_optional_countries_placement ) ) {
        if ( ! is_array( $amwalwc_postcode_optional_countries_placement ) ) {
            $amwalwc_postcode_optional_countries_placement = array( $amwalwc_postcode_optional_countries_placement );
        }
    }
    $optional_countries = [];
    $countryClass = new WC_Countries();
    $countryList = $countryClass->get_shipping_countries();
    foreach ($countryList as $key=>$val){
        $optional_countries[$key] = $val;
    }

    amwalwc_settings_field_ajax_select(
        array(
            'name'        => AMWALWC_SETTING_POSTCODE_OPTIONAL_COUNTRIES,
            'options'     => $optional_countries,
            'selected'    => $amwalwc_postcode_optional_countries_placement,
            'class'       => 'amwal-select amwal-select-postcode-optional-countries',
            'description' => __( 'Exclude Countries From Postal Code Required.', 'amwal-checkout' ),
            'nonce'       => 'postcode-optional-countries',
        )
    );
}

/**
 * Redirect the user after checkout.
 */
//function amwal_auto_render_product_button_content() {
//	$amwalwc_auto_render_checkout_page = get_option( AMWALWC_SETTING_AUTO_RENDER_PRODUCT_BUTTON, '1' );
//
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTING_AUTO_RENDER_PRODUCT_BUTTON,
//			'current'     => $amwalwc_auto_render_checkout_page,
//			'label'       => __( 'Automatically render checkout button in product pages.', 'amwal-checkout' ),
//		)
//	);
//}


//function amwal_auto_render_cart_button_content() {
//	$amwal_auto_render_cart_button = get_option( AMWALWC_SETTING_AUTO_RENDER_CART_BUTTON, '1' );
//
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTING_AUTO_RENDER_CART_BUTTON,
//			'current'     => $amwal_auto_render_cart_button,
//			'label'       => __( 'Automatically render checkout button in cart page.', 'amwal-checkout' ),
//		)
//	);
//}

//function amwalwc_hide_product_button_if_cart_not_empty_content() {
//	$amwalwc_hide_product_button_if_cart_not_empty = get_option( AMWALWC_SETTING_HIDE_PRODUCT_BUTTON_IF_CART_NOT_EMPTY, '1' );
//    if($amwalwc_hide_product_button_if_cart_not_empty == '0' || $amwalwc_hide_product_button_if_cart_not_empty == '0' ){
//        update_option(AMWALWC_SETTING_HIDE_PRODUCT_BUTTON_IF_CART_NOT_EMPTY,1);
//    }
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTING_HIDE_PRODUCT_BUTTON_IF_CART_NOT_EMPTY,
//			'current'     => $amwalwc_hide_product_button_if_cart_not_empty,
//			'label'       => __( ' Hide product button if cart contains at least one item.', 'amwal-checkout' ),
//		)
//	);
//}


function amwalwc_cart_page_button_placement_content() {
	$amwal_auto_render_cart_button = amwalwc_get_option_or_set_default( AMWALWC_SETTING_CART_PAGE_BUTTON_PLACEMENT, 'woocommerce_proceed_to_checkout' );

	$location_options = array(
		'woocommerce_proceed_to_checkout' => __( 'Before Checkout' , 'amwal-checkout'),
		'woocommerce_after_cart_table' => __( 'After Cart Review' , 'amwal-checkout'),
		'woocommerce_cart_totals_after_order_total' => __( 'After Order Totals' , 'amwal-checkout'),
	);

	amwalwc_settings_field_select(
		array(
			'name'        => AMWALWC_SETTING_CART_PAGE_BUTTON_PLACEMENT,
			'options'     => $location_options,
			'value'       => $amwal_auto_render_cart_button,
		)
	);
}

function amwalwc_auto_render_checkout_page_button_content() {
	$amwalwc_auto_render_checkout_page_button = get_option( AMWALWC_SETTING_AUTO_RENDER_CHECKOUT_PAGE_BUTTON, '0' );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_AUTO_RENDER_CHECKOUT_PAGE_BUTTON,
			'current'     => '0',
			'label'       => __( 'Automatically render button in checkout page', 'amwal-checkout' ),
		)
	);
}

function amwalwc_checkout_page_button_placement_content() {
	$amwalwc_checkout_page_button_placement = amwalwc_get_option_or_set_default( AMWALWC_SETTING_CHECKOUT_PAGE_BUTTON_PLACEMENT, 'woocommerce_review_order_before_submit' );

	$location_options = array(
		'woocommerce_checkout_before_customer_details' => __( 'Before Customer Details' , 'amwal-checkout'),
		'woocommerce_checkout_before_order_review' => __( 'Before Order Review' , 'amwal-checkout'),
		'woocommerce_review_order_before_payment' => __( 'Before Payment' , 'amwal-checkout'),
		'woocommerce_review_order_before_submit' => __( 'Before Submit' , 'amwal-checkout'),
	);

	amwalwc_settings_field_select(
		array(
			'name'        => AMWALWC_SETTING_CHECKOUT_PAGE_BUTTON_PLACEMENT,
			'options'     => $location_options,
			'value'       => $amwalwc_checkout_page_button_placement,
		)
	);
}

//function amwalwc_auto_render_minicart_button_content() {
//	$amwalwc_auto_render_minicart_button = get_option( AMWALWC_SETTING_AUTO_RENDER_MINICART_BUTTON, '1' );
//
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTING_AUTO_RENDER_MINICART_BUTTON,
//			'current'     => $amwalwc_auto_render_minicart_button,
//			'label'       => __( 'Render quick checkout in minicart widget', 'amwal-checkout' ),
//		)
//	);
//}


function amwalwc_address_required_in_virtual_product_content() {
	$amwalwc_address_required_in_virtual_product = get_option( AMWALWC_SETTING_ADDRESS_REQUIRED_IN_VIRUAL_PRODUCT, '0' );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_ADDRESS_REQUIRED_IN_VIRUAL_PRODUCT,
			'current'     => $amwalwc_address_required_in_virtual_product,
			'label'       => __( 'Enable address required option even in virtual products', 'amwal-checkout' ),
			'description' => __( 'When address required option is enabled, Amwal Checkout Plugin will require the address even in virtual products.', 'amwal-checkout' ),
		)
	);
}
function amwalwc_address_street2_required_content() {
	$amwalwc_address_street2_required = get_option( AMWALWC_SETTING_ADDRESS_STREET2_REQUIRED, '0' );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_ADDRESS_STREET2_REQUIRED,
			'current'     => $amwalwc_address_street2_required,
			'label'       => __( 'Street 2 will be required in Amwal address', 'amwal-checkout' ),
		)
	);
}

function amwalwc_guest_email_required_content() {
	$amwalwc_guest_email_required = get_option( AMWALWC_SETTING_GUEST_EMAIL_REQUIRED, '1' );

	amwalwc_settings_field_checkbox(
		array(
			'name'        => 'amwal_checkout_guest_email_required',
			'current'     => $amwalwc_guest_email_required,
			'label'       => __( 'Enable guest email required option', 'amwal-checkout' ),
			'description' => __( 'When address required option is enabled, Amwal Checkout Plugin will send order confirmation email to the guest.', 'amwal-checkout' ),
		)
	);
}

function amwalwc_hide_shipping_methods_content(){
    $amwalwc_hide_shipping_methods = amwalwc_get_option_or_set_default( AMWALWC_SETTING_HIDE_SHIPPING_METHODS, 'hide_all' );

    $location_options = array(
        'display_all' => __( 'Display all shipping methods' , 'amwal-checkout'),
        'hide_all' => __( 'Hide all other shipping methods and only show "Free Shipping"' ,'amwal-checkout' ),
        'hide_except_local' => __( 'Hide all other shipping methods and only show "Free Shipping" and "Local Pickup" ' , 'amwal-checkout'),
    );

    $description = sprintf(
        '%1$s',
        esc_html__( 'When "Free Shipping" is available during checkout', 'amwal-checkout' )
    );
    amwalwc_settings_field_select(
        array(
            'name'        => AMWALWC_SETTING_HIDE_SHIPPING_METHODS,
            'options'     => $location_options,
            'description' => $description,
            'value'       => $amwalwc_hide_shipping_methods,
        )
    );
}
/**
 * Renders the Test Mode field.
 */
function amwalwc_test_mode_content() {
	$amwalwc_test_mode = get_option( AMWALWC_SETTING_TEST_MODE, AMWALWC_SETTING_TEST_MODE_NOT_SET );

	if ( AMWALWC_SETTING_TEST_MODE_NOT_SET === $amwalwc_test_mode ) {
		// If the option is AMWALWC_SETTING_TEST_MODE_NOT_SET, then it hasn't yet been set. In this case, we
		// want to configure test mode to be on.
		$amwalwc_test_mode = '1';
		update_option( AMWALWC_SETTING_TEST_MODE, '1' );
	}

	amwalwc_settings_field_checkbox(
		array(
			'name'        => 'amwal_test_mode',
			'current'     => $amwalwc_test_mode,
			'label'       => __( 'Enable test mode', 'amwal-checkout' ),
			'description' => __( 'When test mode is enabled, only logged-in admin users will see the Amwal Checkout button.', 'amwal-checkout' ),
		)
	);
}


/**
 * Renders the Debug Mode field.
 */
function amwalwc_debug_mode_content() {
	$amwalwc_debug_mode = get_option( AMWALWC_SETTING_DEBUG_MODE, AMWALWC_SETTING_DEBUG_MODE_NOT_SET );

	if ( AMWALWC_SETTING_DEBUG_MODE_NOT_SET === $amwalwc_debug_mode ) {
		// If the option is AMWALWC_SETTING_DEBUG_MODE_NOT_SET, then it hasn't yet been set. In this case, we
		$amwalwc_debug_mode = 1;
		update_option( AMWALWC_SETTING_DEBUG_MODE, $amwalwc_debug_mode );
	}

	amwalwc_settings_field_checkbox(
		array(
			'name'        => AMWALWC_SETTING_DEBUG_MODE,
			'current'     => $amwalwc_debug_mode,
			'label'       => __( 'Enable debug mode', 'amwal-checkout' ),
			'description' => __( 'When debug mode is enabled, the Amwal plugin will maintain an error log.', 'amwal-checkout' ),
		)
	);
}
//
//function amwalwc_cron_jobs_content() {
//	$amwalwc_enable_cron_jobs = get_option( AMWALWC_SETTINGS_ENABLE_CRONJOBS, true );
//
//	amwalwc_settings_field_checkbox(
//		array(
//			'name'        => AMWALWC_SETTINGS_ENABLE_CRONJOBS,
//			'current'     => $amwalwc_enable_cron_jobs,
//			'label'       => __( 'Enable Cron Jobs', 'amwal' ),
//			'description' => __( 'When cron jobs is enabled, Amwal will run script every 30 minutes to check Amwal orders status.', 'amwal' ),
//		)
//	);
//}
//
//add_filter( 'pre_update_option_'.AMWALWC_SETTINGS_ENABLE_CRONJOBS, 'amwalwc_update_cron_jobs', 10, 2);
//function amwalwc_update_cron_jobs($new_value, $old_value){
//	print_r(_get_cron_array());
//	if($new_value === '1'){
//
//		do_action( 'amwal_cron_hook_activation' );
//
//		do_action( 'amwal_check_transaction_cron' );
//
//	}
//	else{
//		wp_unschedule_hook('amwal_check_transaction_cron');
//	}
//	return $new_value;
//}
//
//
//add_action( 'amwal_cron_hook_activation', function () {
//	if ( ! wp_next_scheduled( 'amwal_check_transaction_cron' ) ) {
//		wp_schedule_event( time(), 'every_30_minutes', 'amwal_check_transaction_cron' );
//	}
//} );
//
//add_action( 'amwal_check_transaction_cron', function () {
//	$pending_status = apply_filters( 'woocommerce_default_order_status', 'pending' );
//
//	$date_a = date( 'Y-m-d H:i:s', strtotime( '-2 hours' ) );
//	$date_b = date( 'Y-m-d H:i:s', strtotime( '-30 minutes' ) );
//	$orders = wc_get_orders( array(
//		'status'       => $pending_status,
//		'date_created' => strtotime( $date_a ) . '...' . strtotime( $date_b )
//	) );
//
//	foreach ( $orders as $order ) {
//		$amwal_transaction_id = get_post_meta( $order->get_id(), 'amwal_transaction_id', true );
//		if ( $amwal_transaction_id ) {
//			amwalwc_check_transaction_details_request( $order, $amwal_transaction_id );
//		}
//	}
//} );

/**
 * Helper that returns the value of an option if it is set, and sets and returns a default if the option was not set.
 * This is similar to get_option($option, $default), except that it *sets* the option if it is not set instead of just returning a default.
 *
 * @see https://developer.wordpress.org/reference/functions/get_option/
 *
 * @param string $option Name of the option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default Default value to set option to and return if the return value of get_option is falsey.
 * @return mixed The value of the option if it is truthy, or the default if the option's value is falsey.
 */
function amwalwc_get_option_or_set_default( $option, $default ) {
	$val = get_option( $option );
	if ( false !== $val ) {
		return $val;
	}
	update_option( $option, $default );
	return $default;
}

/**
 * Get the Amwal APP ID.
 *
 * @return string
 */
function amwalwc_get_app_id() {
	return get_option( AMWALWC_SETTING_APP_ID );
}

function amwalwc_get_app_secret() {
    return get_option( AMWALWC_SETTING_APP_SECRET );
}
/**
 * Search pages to return for the page select Ajax.
 */
function amwalwc_ajax_search_pages() {
	check_ajax_referer( 'search-pages', 'security' );

	$return = array();

	if ( isset( $_GET['term'] ) ) {
		$q_term = sanitize_text_field( wp_unslash( $_GET['term'] ) );
	}

	if ( empty( $q_term ) ) {
		wp_die();
	}

	$search_results = new WP_Query(
		array(
			's'              => $q_term,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'posts_per_page' => -1,
		)
	);

	if ( $search_results->have_posts() ) {
		while ( $search_results->have_posts() ) {
			$search_results->the_post();

			$return[ get_the_ID() ] = get_the_title();
		}
		wp_reset_postdata();
	}

	wp_send_json( $return );
}
add_action( 'wp_ajax_amwalwc_search_pages', 'amwalwc_ajax_search_pages' );

/**
 * Search users to return for the user select Ajax.
 */
function amwalwc_ajax_search_users() {
	check_ajax_referer( 'search-users', 'security' );

	$return = array();

	if ( isset( $_GET['term'] ) ) {
		$q_term = sprintf(
			'*%s*', // Add leading and trailing '*' for wildcard search.
			sanitize_text_field( wp_unslash( $_GET['term'] ) )
		);
	}

	if ( empty( $q_term ) ) {
		wp_die();
	}

	$search_results = get_users(
		array(
			'search'       => $q_term,
			'role__not_in' => 'Administrator',
		)
	);

	if ( ! empty( $search_results ) ) {
		foreach ( $search_results as $search_result_user ) {
			$return[ $search_result_user->ID ] = $search_result_user->display_name;
		}
	}

	wp_send_json( $return );
}
add_action( 'wp_ajax_amwalwc_search_users', 'amwalwc_ajax_search_users' );
