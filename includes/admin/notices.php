<?php
/**
 * Display admin notices.
 *
 * @package Amwal
 */

/**
 * Check for conditions to display admin notices.
 */
function amwalwc_maybe_display_admin_notices() {
	$amwal_app_id              = amwalwc_get_app_id();
	$amwalwc_debug_mode        = get_option( AMWALWC_SETTING_DEBUG_MODE, 0 );
	$amwalwc_test_mode         = get_option( AMWALWC_SETTING_TEST_MODE, '1' );

	if ( ! empty( $amwalwc_debug_mode ) ) {
		add_action( 'admin_notices', 'amwalwc_settings_admin_notice_debug_mode' );
	}

	if ( ! empty( $amwalwc_test_mode ) ) {
		add_action( 'admin_notices', 'amwalwc_settings_admin_notice_test_mode' );
	}
}
add_action( 'admin_init', 'amwalwc_maybe_display_admin_notices' );


/**
 * Template for printing an admin notice.
 *
 * @param string $message The message to display.
 * @param string $type    Optional. The type of message to display.
 */
function amwalwc_admin_notice( $message, $type = 'warning' ) {
	$class = 'notice notice-' . $type;

	printf(
		'<div class="%1$s"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $message )
	);
}

/**
 * Print the Test Mode admin notice.
 */
function amwalwc_settings_admin_notice_test_mode() {
	amwalwc_admin_notice( __( 'Amwal Checkout for WooCommerce is currently in Test Mode.', 'amwal-checkout') );
}

/**
 * Print the Debug Mode admin notice.
 */
function amwalwc_settings_admin_notice_debug_mode() {
	amwalwc_admin_notice( __( 'Amwal Checkout for WooCommerce is currently in Debug Mode.', 'amwal-checkout' ) );
}