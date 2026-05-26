<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function tcmp_uninstall( $networkwide = null ) {
	global $wpdb;

}

register_uninstall_hook( TCMP_PLUGIN_FILE, 'tcmp_uninstall' );

