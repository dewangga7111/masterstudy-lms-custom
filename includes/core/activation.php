<?php
/**
 * Activation: create custom tables for careers feature.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( MSLC_PLUGIN_FILE, 'mslc_create_tables' );
function mslc_create_tables() {
	global $wpdb;
	$charset = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}karir_kategori (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		nama varchar(255) NOT NULL,
		PRIMARY KEY (id)
	) $charset;" );

	dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}karir (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		nama varchar(255) NOT NULL,
		deskripsi longtext,
		gambar bigint(20) DEFAULT 0,
		kategori_id bigint(20) DEFAULT 0,
		PRIMARY KEY (id)
	) $charset;" );
}
