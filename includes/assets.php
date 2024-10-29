<?php
add_action('wp_enqueue_scripts', 'amwalwc_enqueue_assets');

function amwalwc_enqueue_assets()
{
	if (AMWALWC_ENV == 'localhost') {
		wp_enqueue_script('amwal-checkout-button', 'http://localhost:3333/build/checkout.esm.js', null, null, true);
	} else {
		wp_register_script( 'amwal-checkout-button', 'https://cdn.jsdelivr.net/npm/amwal-checkout-button@'.AMWALWC_SDK_VERSION.'/dist/checkout/checkout.esm.js', null, null, true );
	}
	wp_enqueue_script('amwal-checkout-button');

    wp_enqueue_style('amwal-checkout-style', plugins_url('../assets/amwal-checkout.css?aver=' . AMWALWC_VERSION, __FILE__) , [], AMWALWC_VERSION);

	wp_register_script( 'amwal-checkout-script', plugins_url('../assets/build/amwal-checkout.js?aver=' . AMWALWC_VERSION, __FILE__) , array('jquery'), AMWALWC_VERSION,true);

	wp_enqueue_script('amwal-checkout-script');
    wp_localize_script( 'amwal-checkout-script', 'AMWALWC_CONSTANTS', array(
        'transactionDetailsURL' => AMWALWC_TRANSACTION_DETAILS,
        'pluginVersion' => AMWALWC_VERSION,
        'extraEvents' => get_option(AMWALWC_SETTING_EXTRA_EVENTS,AMWALWC_SETTING_EXTRA_EVENTS_DEFAULT),
        'siteURL' => get_site_url(),
        )
    );

	wp_dequeue_style( 'amwal-checkout-extra-style');
	wp_enqueue_style( 'amwal-checkout-custom-css', get_template_directory_uri(). '/custom.css', array(), '1.0' );
	$amwal_setting_extra_style = get_option(AMWALWC_SETTING_EXTRA_STYLES,'');
	if ( !empty($amwal_setting_extra_style)) {
		wp_add_inline_style( 'amwal-checkout-custom-css', $amwal_setting_extra_style );
	}
}
add_filter('script_loader_tag', __NAMESPACE__ . '\add_type_to_js_scripts', 10, 3);
function add_type_to_js_scripts($tag, $handle, $source){
	// Add main js file and all modules to the array.
	$theme_handles = array(
		'amwal-checkout-button',
	);
	// Loop through the array and filter the tag.
	foreach($theme_handles as $theme_handle) {
		if ($theme_handle === $handle) {
			return $tag = '<script src="'. esc_url($source).'" type="module"></script>';
		}
	}
	return $tag;
}

function add_extra_css() {
	wp_enqueue_style('custom-css', get_template_directory_uri() . '/amwal-checkout-extra-style.css');
	$amwal_setting_extra_style = get_option(AMWALWC_SETTING_EXTRA_STYLES,'');
	if ( !empty($amwal_setting_extra_style) ) {
		wp_add_inline_style( 'custom-css', $amwal_setting_extra_style );
	}
}
add_action( 'wp_enqueue_scripts', 'add_extra_css' );