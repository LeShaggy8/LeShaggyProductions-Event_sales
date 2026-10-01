<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Versión del esquema. Súbela cuando cambien las tablas; spp_maybe_upgrade() corre spp_install() una sola vez.
define( 'SPP_DB_VERSION', '3' );

function spp_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_tickets';
}

function spp_scans_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_scans';
}

function spp_courtesies_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_courtesies';
}

function spp_credits_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_credits';
}

function spp_credit_log_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_credit_log';
}

/**
 * Crea/actualiza las tablas y el rol del escáner. Es seguro repetirla (dbDelta solo agrega lo que falta):
 *  - v1: {prefijo}spp_tickets.
 *  - v2: {prefijo}spp_scans (bitácora de escaneos) y el rol "Escáner SpookyPeda".
 *  - v3 (cortesías, solo se AGREGA; no se borra ni modifica ningún dato):
 *      · spp_tickets.source  → columna nueva, 'order' por defecto (los boletos existentes quedan 'order').
 *      · spp_courtesies      → registro de cada cortesía (quién, para quién, correo, Ticket ID, envío, anulación).
 *      · spp_credits         → saldo de créditos por usuario de marketing.
 *      · spp_credit_log      → historial de movimientos de créditos (altas, consumos, devoluciones).
 */
function spp_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tickets    = spp_table();
	$scans      = spp_scans_table();
	$courtesies = spp_courtesies_table();
	$credits    = spp_credits_table();
	$credit_log = spp_credit_log_table();
	$charset    = $wpdb->get_charset_collate();

	dbDelta( "CREATE TABLE $tickets (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		ticket_id varchar(40) NOT NULL,
		wc_order_id bigint(20) unsigned NOT NULL,
		attendee_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
		status varchar(10) NOT NULL DEFAULT 'unused',
		source varchar(12) NOT NULL DEFAULT 'order',
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

	dbDelta( "CREATE TABLE $courtesies (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		created_by bigint(20) unsigned NOT NULL,
		winner_name varchar(120) NOT NULL,
		winner_email varchar(190) NOT NULL,
		ticket_id varchar(40) NOT NULL,
		email_status varchar(10) NOT NULL DEFAULT 'pending',
		emailed_at datetime NULL DEFAULT NULL,
		voided_at datetime NULL DEFAULT NULL,
		voided_by bigint(20) unsigned NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY ticket_id (ticket_id),
		KEY created_by (created_by),
		KEY winner_email (winner_email)
	) $charset;" );

	dbDelta( "CREATE TABLE $credits (
		user_id bigint(20) unsigned NOT NULL,
		balance int(10) unsigned NOT NULL DEFAULT 0,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (user_id)
	) $charset;" );

	dbDelta( "CREATE TABLE $credit_log (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		delta int(11) NOT NULL,
		balance_after int(10) unsigned NOT NULL,
		reason varchar(12) NOT NULL,
		ref_courtesy_id bigint(20) unsigned NULL DEFAULT NULL,
		actor_id bigint(20) unsigned NOT NULL,
		note varchar(190) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY user_id (user_id)
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
