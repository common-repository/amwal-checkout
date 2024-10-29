<?php
/**
 * Amwal Debug Mode to add message to WC_Logger
 *
 * @package Amwal
 */
function amwalwc_debug_mode_enabled() {
    $amwalwc_debug_mode = get_option( AMWALWC_SETTING_DEBUG_MODE, AMWALWC_SETTING_DEBUG_MODE_NOT_SET );
    return !empty( $amwalwc_debug_mode );
}

/**
 * Log a message if Amwal debug mode is enabled.
 *
 * @param string $level   WooCommerce log level. One of the following:
 *                          'emergency': System is unusable.
 *                          'alert': Action must be taken immediately.
 *                          'critical': Critical conditions.
 *                          'error': Error conditions.
 *                          'warning': Warning conditions.
 *                          'notice': Normal but significant condition.
 *                          'info': Informational messages.
 *                          'debug': Debug-level messages.
 * @param string $message Message to log.
 */
function amwalwc_log( $level, $message ) {
    if ( $level == 'error' || amwalwc_debug_mode_enabled() ) {
        $logger = wc_get_logger();
        $logger->log($level, $message, array('source' => 'amwalwc'));
    }
}

/**
 * Adds an emergency level message if Amwal debug mode is enabled
 *
 * System is unusable.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_emergency( $message ) {
	amwalwc_log( 'emergency', $message );
}

/**
 * Adds an alert level message if Amwal debug mode is enabled.
 *
 * Action must be taken immediately.
 * Example: Entire website down, database unavailable, etc.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_alert( $message ) {
	amwalwc_log( 'alert', $message );
}

/**
 * Adds a critical level message if Amwal debug mode is enabled.
 *
 * Critical conditions.
 * Example: Application component unavailable, unexpected exception.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_critical( $message ) {
	amwalwc_log( 'critical', $message );
}

/**
 * Adds an error level message if Amwal debug mode is enabled.
 *
 * Runtime errors that do not require immediate action but should typically be logged
 * and monitored.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_error( $message ) {
	amwalwc_log( 'error', $message );
}

/**
 * Adds a warning level message if Amwal debug mode is enabled.
 *
 * Exceptional occurrences that are not errors.
 *
 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not
 * necessarily wrong.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_warning( $message ) {
	amwalwc_log( 'warning', $message );
}

/**
 * Adds a notice level message if Amwal debug mode is enabled.
 *
 * Normal but significant events.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_notice( $message ) {
	amwalwc_log( 'notice', $message );
}

/**
 * Adds a info level message if Amwal debug mode is enabled.
 *
 * Interesting events.
 * Example: User logs in, SQL logs.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_info( $message ) {
	if (amwalwc_debug_mode_enabled()) {
		amwalwc_log( 'info', $message );
	}
}
/**
 * Adds a debug level message if Amwal debug mode is enabled.
 *
 * Detailed debug information.
 *
 * @see WC_Logger::log
 *
 * @param string $message Message to log.
 */
function amwalwc_log_debug( $message ) {
	amwalwc_log( 'debug', $message );
}
