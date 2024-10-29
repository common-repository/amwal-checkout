<?php

use Amwal\Http\Controllers\{
    AboutController,
    CreateOrderController,
    CompleteOrderController,
    CreateCheckoutController,
    UpdateCheckoutController,
    CancelOrderController,
    OrderDetailsController,
    OrderHealthController,
	ProductDetailsController,
	UpdateSettingsController
};

add_action('rest_api_init', 'amwalwc_register_routes');
add_action('rest_api_init', 'amwalwc_rest_api_includes');

function amwalwc_register_routes()
{
    $controllers = [
        new AboutController(),
        new CreateOrderController(),
        new CompleteOrderController(),
        new CreateCheckoutController(),
        new UpdateCheckoutController(),
        new CancelOrderController(),
        new OrderDetailsController(),
        new OrderHealthController(),
	    new ProductDetailsController(),
	    new UpdateSettingsController()
    ];
    foreach ($controllers as $controller) {
        register_rest_route(
            $controller->get_namespace(),
            $controller->get_route(),
            [
                'methods'             => $controller->get_method(),
                'callback'            => [$controller, 'handle'],
                'permission_callback' => '__return_true',
            ]
        );
    }
}

function amwalwc_rest_api_includes()
{
    // Fixes https://github.com/woocommerce/woocommerce/issues/27157
    if (empty(WC()->cart)) {
        WC()->frontend_includes();
        wc_load_cart();
    }
}
