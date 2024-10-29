<?php

namespace Amwal\SentryReporter;
use Sentry;
use Sentry\State\Scope;
use Throwable;

class SentryExceptionReport {
	public function __construct() {
		if ( ! function_exists( 'Sentry\init' ) ) {
			return;
		}

		Sentry\init(['dsn' => get_option('amwal_sentry_dsn'), 'traces_sample_rate' => 1.0,'environment' => AMWALWC_ENV]);
	}

	public function reportException( Throwable $exception , string $amwal_cart_id = NULL, string $amwal_transaction_id = NULL, string $order_id = NULL): void {
		if (!class_exists(Sentry\ClientBuilder::class) || !get_option(AMWALWC_SETTING_SENTRY_ENABLED)) {
			return;
		}
		Sentry\configureScope(function (Scope $scope) use ( $amwal_cart_id , $amwal_transaction_id,$order_id ) {
			$scope->setExtra('domain', $_SERVER['HTTP_HOST'] ?? 'runtime cli');
			$scope->setExtra('plugin_version', AMWALWC_VERSION);
			$scope->setExtra('php_version', phpversion());
			if (!empty($amwal_cart_id)) {
				$scope->setExtra('cart_id', $amwal_cart_id);
			}
			if (!empty($amwal_transaction_id)) {
				$scope->setExtra('transaction_id', $amwal_transaction_id);
			}
			if (!empty($order_id)) {
				$scope->setExtra('order_id', $order_id);
			}
		});

		// Send exception to Sentry with the hint
		Sentry\captureException($exception);
	}
}