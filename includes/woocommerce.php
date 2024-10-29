<?php

/**
 * Check whether WooCommerce is active.
 *
 * @return bool
 */
function amwalwc_is_woocommerce_active()
{
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
    }
    $wc_is_active = is_plugin_active( 'woocommerce/woocommerce.php' );

    if ( ! $wc_is_active ) {
        // Add an admin notice that WooCommerce must be active in order for Amwal to work.
        add_action(
            'admin_notices',
            'amwalwc_settings_admin_notice_woocommerce_not_installed'
        );
    }
    return $wc_is_active;
}

/**
 * Add hook for admin notice caused by missing WooCommerce plugin
 */
function amwalwc_admin_notice_woocommerce_is_missing()
{
    add_action(
        'admin_notices',
        'amwalwc_display_admin_notice_for_missing_woocommerce'
    );
}

/**
 * Display the error message when WooCommerce plugin is missing.
 */
function amwalwc_display_admin_notice_for_missing_woocommerce()
{
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        'Amwal Checkout requires an active WooCommerce installation.'
    );
}

/**
 * Check if plugin required configuration is set
 */
function amwalwc_check_amwal_configured()
{
    return get_option('amwal_app_id') && get_option('amwal_valid_app_id');
}

/**
 * Add hook for admin notice caused by incomplete Amwal configuration
 */
function amwalwc_admin_notice_incomplete_configuration()
{
    add_action(
        'admin_notices',
        'amwalwc_display_admin_notice_incomplete_configuration'
    );
}

/**
 * Display error message when API Key & Secret is not configured.
 */
function amwalwc_display_admin_notice_incomplete_configuration()
{
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        'Amwal Checkout <a href="/wp-admin/admin.php?page=amwal&tab=amwal_app_info">API Key</a> are not configured, quick buy buttons will not appear until they are set.'
    );
}

/**
 * Check whether custom Permalinks are disabled.
 *
 * @return bool
 */
function amwalwc_are_permalinks_disabled()
{
    return get_option('permalink_structure') === "";
}

/**
 * Add hook for admin notice caused by disabled custom Permalinks
 */
function amwalwc_admin_notice_permalinks_are_disabled()
{
    add_action(
        'admin_notices',
        'amwalwc_display_admin_notice_for_disabled_permalinks'
    );
}

/**
 * Display the error message when custom Permalinks are disabled.
 */
function amwalwc_display_admin_notice_for_disabled_permalinks()
{
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        sprintf(
            'Amwal Plugin requires pretty <a href="https://wordpress.org/support/article/settings-permalinks-screen/" target="_blank">%s</a> to be enabled.',
            'Permalinks'
        )
    );
}

/**
 * Load the Amwal WC Gateway class
 *
 * @param array $gateways The WC payment gateways.
 *
 * @return array
 */
function amwalwc_add_payment_gateway( $gateways ) {
	$gateways[] = 'Amwal\WC_Gateway_Amwal';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'amwalwc_add_payment_gateway' );