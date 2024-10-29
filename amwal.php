<?php
/**
 * Plugin Name: Amwal Checkout
 * Plugin URI: https://amwal.tech/
 * Author: Amwal
 * Author URI: https://amwal.tech
 * Text Domain: amwal-checkout
 * Domain Path: /languages
 * Description: 1-click biometric checkout- the fastest and most secure way to accept online payments.
 * Version: 1.0.61
 * Requires at least: 6.0.2
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-3.0.html
 *
 * @package Amwal
 */

defined('ABSPATH') || exit;

define('AMWALWC_PATH', plugin_dir_path(__FILE__));
define('AMWALWC_URL', plugin_dir_url(__FILE__));
define('AMWALWC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AMWALWC_PAYMENT_METHOD_ID', 'amwalcheckout');
define('AMWALWC_VERSION', '1.0.61');
define('AMWALWC_SDK_VERSION', '0.0.54');
define('AMWALWC_SENTRY_DSN', 'https://ac156c4f6b3f585c864bf4f9c3916ffa@sentry.amwal.tech/6');

require_once AMWALWC_PATH . 'constants.php';
require_once AMWALWC_PATH . 'includes/woocommerce.php';
add_option( 'amwal_valid_app_id', false);
if (amwalwc_is_woocommerce_active()) {
    if (amwalwc_are_permalinks_disabled()) {
        amwalwc_admin_notice_permalinks_are_disabled();
    }

    if (!amwalwc_check_amwal_configured()) {
        amwalwc_admin_notice_incomplete_configuration();
    }

    /*
	 * Composer provides a convenient, automatically generated class loader for
	 * our plugin. We'll require it here so that we don't have to worry about manual
	 * loading any of our classes later on.
	 */
    require_once AMWALWC_PATH . 'vendor/autoload.php';
    require_once AMWALWC_PATH . 'includes/debug.php';
    require_once AMWALWC_PATH . 'includes/assets.php';
    require_once AMWALWC_PATH . 'includes/utilities.php';
    require_once AMWALWC_PATH . 'includes/hooks.php';
    require_once AMWALWC_PATH . 'includes/button.php';
    require_once AMWALWC_PATH . 'includes/routes.php';

    /**
	 * Load the Amwal payment gateway after plugins are loaded.
	 */
	function amwalwc_plugins_loaded() {
		// Add the Amwal Checkout payment gateway object.
		require_once AMWALWC_PATH . 'includes/class-wc-gateway-amwal.php';
	}
	add_action( 'plugins_loaded', 'amwalwc_plugins_loaded' );

} else {
    amwalwc_admin_notice_woocommerce_is_missing();
}


if (!defined('AMWALWC_PLUGIN_FILE')) {
    define('AMWALWC_PLUGIN_FILE', __FILE__);
}

if (!defined('AMWALWC_PLUGIN_DIR')) {

    define('AMWALWC_PLUGIN_DIR', untrailingslashit(plugins_url('/', AMWALWC_PLUGIN_FILE)));
}

if (!defined('AMWALWC_ABSPATH')) {
    define('AMWALWC_ABSPATH', dirname(AMWALWC_PLUGIN_FILE) . '/');
}
define( 'AMWALWC_PLUGIN_ACTIVATED', 'amwalwc_plugin_activated' );
/**
 * Add a flag indicating that the plugin was just activated.
 */
function amwalwc_plugin_activated() {
	// First make sure that WooCommerce is installed and active.
	if ( amwalwc_is_woocommerce_active() ) {
		// Add a flag to show that the plugin was activated.
		add_option( AMWALWC_PLUGIN_ACTIVATED, true );
	}
    update_option( 'wc_hide_shipping_options', 'hide_all' );
	amwalwc_defualt_settings();
}

/**
 * @return void
 */
function amwalwc_defualt_settings(): void {
	update_option( 'amwal_auto_render_cart_button', true );
	update_option( 'amwal_auto_render_product_button', true );
	update_option( 'amwalwc_auto_render_minicart_button', true );
	update_option( 'amwal_checkout_guest_email_required', true );
	update_option( 'amwalwc_checkout_address_street2_required', false );
	update_option( 'amwal_sentry_enabled', true );
	update_option( 'amwal_sentry_dsn', 'https://ac156c4f6b3f585c864bf4f9c3916ffa@sentry.amwal.tech/6' );
}

register_activation_hook( __FILE__, 'amwalwc_plugin_activated' );

add_action( 'init', 'wpdocs_load_textdomain' );

function wpdocs_load_textdomain() {
	load_plugin_textdomain( 'amwal-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}