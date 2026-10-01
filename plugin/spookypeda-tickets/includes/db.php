<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Versión del esquema. Súbela cuando cambien las tablas; spp_maybe_upgrade() corre spp_install() una sola vez.
define( 'SPP_DB_VERSION', '2' );

function spp_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_tickets';
}

function spp_scans_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_scans';
}

/**
 * Crea/actualiza las tablas y el rol del escáner. Es seguro repetirla (dbDelta solo agrega lo que falta):
 *  - v1: {prefijo}spp_tickets (sin cambios en v2).
 *  - v2: {prefijo}spp_scans (bitácora de escaneos) y el rol "Escáner SpookyPeda".
 */
function spp_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tickets = spp_table();
	$scans   = spp_scans_table();
	$charset = $wpdb->get_charset_collate();

	dbDelta( "CREATE TABLE $tickets (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		ticket_id varchar(40) NOT NULL,
		wc_order_id bigint(20) unsigned NOT NULL,
		attendee_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
		status varchar(10) NOT NULL DEFAULT 'unused',
		used_at datetime NULL DEFAULT NULL,
		used_by bigint(20) unsigned NULL DEFAULT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY ticket_id (ticket_id),
		KEY wc_order_id (wc_order_id)
	) $charset;" );

	dbDelta( "CREATE TABLE $scans (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		scanned_at datetime NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		source varchar(10) NOT NULL DEFAULT '',
		code varchar(100) NOT NULL DEFAULT '',
		ticket_id varchar(40) NULL DEFAULT NULL,
		result varchar(16) NOT NULL,
		PRIMARY KEY  (id),
		KEY ticket_id (ticket_id),
		KEY scanned_at (scanned_at)
	) $charset;" );

	spp_setup_roles();
	update_option( 'spp_db_version', SPP_DB_VERSION );
}

/** Rol para el personal de la puerta + permiso de escanear para administradores. */
function spp_setup_roles() {
	add_role( 'spp_scanner', 'Escáner SpookyPeda', array( 'read' => true, 'spp_scan' => true ) );
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'spp_scan' ) ) {
		$admin->add_cap( 'spp_scan' );
	}
}

/** Al actualizar el plugin no se vuelve a ejecutar la activación; esto aplica los cambios de esquema una vez. */
function spp_maybe_upgrade() {
	if ( get_option( 'spp_db_version' ) !== SPP_DB_VERSION ) {
		spp_install();
	}
}

/** Registra un boleto. Si el Ticket ID ya existe, no hace nada (idempotente). */
function spp_register_ticket( $ticket_id, $order_id, $attendee_post_id ) {
	global $wpdb;
	return $wpdb->query(
		$wpdb->prepare(
			'INSERT IGNORE INTO ' . spp_table() . ' (ticket_id, wc_order_id, attendee_post_id, status, created_at) VALUES (%s, %d, %d, %s, %s)',
			$ticket_id,
			$order_id,
			$attendee_post_id,
			'unused',
			gmdate( 'Y-m-d H:i:s' )
		)
	);
}

function spp_get_order_tickets( $order_id ) {
	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare( 'SELECT * FROM ' . spp_table() . ' WHERE wc_order_id = %d ORDER BY id ASC', $order_id )
	);
}
