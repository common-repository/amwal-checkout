<?php
/**
 * Amwal Plugin Settings Fields
 *
 * @package Amwal
 */

// Load base field class.
require_once AMWALWC_PATH . 'includes/admin/fields/class-field.php';
// Load input field class.
require_once AMWALWC_PATH . 'includes/admin/fields/class-input.php';
// Load textarea field class.
require_once AMWALWC_PATH . 'includes/admin/fields/class-textarea.php';
// Load checkbox field class.
require_once AMWALWC_PATH . 'includes/admin/fields/class-checkbox.php';
// Load select field class.
require_once AMWALWC_PATH . 'includes/admin/fields/class-select.php';
// Load ajax select field class.
require_once AMWALWC_PATH . 'includes/admin/fields/class-ajaxselect.php';


/**
 * Standard text input field.
 *
 * @param array $args Attribute args for the field.
 *
 * @return Amwal\Admin\Fields\Input
 */
function amwalwc_settings_field_input( $args ) {
	return new Amwal\Admin\Fields\Input( $args );
}

/**
 * Standard textarea field.
 *
 * @param array $args Attribute args for the field.
 *
 * @return Amwal\Admin\Fields\Textarea
 */
function amwalwc_settings_field_textarea( $args ) {
	return new Amwal\Admin\Fields\Textarea( $args );
}

/**
 * Standard checkbox input field.
 *
 * @param array $args Attribute args for the field.
 *
 * @return Amwal\Admin\Fields\Checkbox
 */
function amwalwc_settings_field_checkbox( $args ) {
	return new Amwal\Admin\Fields\Checkbox( $args );
}

/**
 * Regular select settings field.
 *
 * @param array $args Attribute args for the field.
 *
 * @return Amwal\Admin\Fields\Select
 */
function amwalwc_settings_field_select( $args ) {
	return new Amwal\Admin\Fields\Select( $args );
}

/**
 * Ajax select settings field.
 *
 * @param array $args Attribute args for the field.
 *
 * @return Amwal\Admin\Fields\AjaxSelect
 */
function amwalwc_settings_field_ajax_select( $args ) {
    return new Amwal\Admin\Fields\AjaxSelect( $args );
}
