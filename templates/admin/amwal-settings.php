<?php
/**
 * Amwal admin settings page template.
 *
 * @package Amwal
 */

$amwalwc_tabs       = amwalwc_get_settings_tabs();
$amwalwc_active_tab = amwalwc_get_active_tab();

?>
<div class="wrap amwal-settings">
	<h2><?php esc_html_e( 'Amwal Settings', 'amwal-checkout' ); ?></h2>

	<?php
	// Load the tabs nav.
	amwalwc_load_template( 'admin/amwal-tabs-nav' );

	// Load the tab content for the active tab.
	$valid_tab_contents   = array_keys( $amwalwc_tabs );
	$valid_tab_contents[] = 'amwal_advanced';
	if ( ! in_array( $amwalwc_active_tab, $valid_tab_contents, true ) ) {
		$amwalwc_active_tab = 'amwal_app_info';
	}
	$amwalwc_tab_template = 'admin/tabs/' . str_replace( '_', '-', $amwalwc_active_tab );
	amwalwc_load_template( $amwalwc_tab_template );
	?>
</div>
