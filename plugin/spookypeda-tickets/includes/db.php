<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function spp_table() {
	global $wpdb;
	return $wpdb->prefix . 'spp_tickets';
}

/** Crea la tabla propia de boletos (se ejecuta al activar el plugin). */
function spp_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = spp_table();
	$charset = $wpdb->get_charset_collate();

	dbDelta( "CREATE TABLE $table (
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
