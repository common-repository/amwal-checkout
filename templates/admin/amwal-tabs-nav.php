<?php
/**
 * Amwal admin settings page nav template.
 *
 * @package Amwal
 */

$amwalwc_tabs       = amwalwc_get_settings_tabs();
$amwalwc_active_tab = amwalwc_get_active_tab();

?>

<nav class="nav-tab-wrapper">
	<?php
	foreach ( $amwalwc_tabs as $tab_name => $tab_label ) :
		$tab_url   = sprintf( 'admin.php?page=amwal&tab=%s', $tab_name );
		$tab_class = array( 'nav-tab' );
		if ( $amwalwc_active_tab === $tab_name ) {
			$tab_class[] = 'nav-tab-active';
		}
		$tab_class = implode( ' ', $tab_class );
		?>
	<a href="<?php echo esc_url( $tab_url ); ?>" class="<?php echo esc_attr( $tab_class ); ?>"><?php echo esc_html( $tab_label ); ?></a>
	<?php endforeach; ?>
</nav>
