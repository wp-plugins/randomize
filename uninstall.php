<?php

if(defined('WP_UNINSTALL_PLUGIN') ){

	// Delete custom table
	global $wpdb;
	$table_name = $wpdb->prefix . 'randomize';
	$sql = 'DROP TABLE IF EXISTS '.$table_name;
	$wpdb->query($sql);
	
}

?>