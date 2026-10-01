<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extrae el Ticket ID de lo escaneado.
 * - Cámara: exige el prefijo del QR (SP5:).
 * - Manual: acepta el ID con o sin prefijo.
 * Devuelve null si no tiene el formato esperado.
 */
function spp_parse_code( $raw, $source ) {
	$raw = trim( (string) $raw );
	$len = strlen( SPP_QR_PREFIX );
	if ( 0 === strncasecmp( $raw, SPP_QR_PREFIX, $len ) ) {
		$id = substr( $raw, $len );
	} elseif ( 'manual' === $source ) {
		$id = $raw;
	} else {
		return null;
	}
	$id = trim( $id );
	return preg_match( '/^[A-Za-z0-9_-]{4,40}$/', $id ) ? $id : null;
}

/** Hora de México para mostrar (la base guarda UTC). */
function spp_local_time( $utc ) {
	try {
		$d = new DateTime( $utc, new DateTimeZone( 'UTC' ) );
		$d->setTimezone( new DateTimeZone( 'America/Mexico_City' ) );
		return $d->format( 'd/m H:i:s' );
	} catch ( Exception $e ) {
		return (string) $utc;
	}
}

/** Bitácora: un renglón por cada intento, válido o no. Un fallo al registrar no debe frenar la entrada. */
function spp_log_scan( $result, $code, $ticket_id, $source, $user_id ) {
	global $wpdb;
	$wpdb->insert(
		spp_scans_table(),
		array(
			'scanned_at' => gmdate( 'Y-m-d H:i:s' ),
			'user_id'    => (int) $user_id,
			'source'     => substr( (string) $source, 0, 10 ),
			'code'       => substr( (string) $code, 0, 100 ),
			'ticket_id'  => $ticket_id,
			'result'     => $result,
		),
		array( '%s', '%d', '%s', '%s', '%s', '%s' )
	);
}

/**
 * Valida y consume un boleto.
 * La marca como usado es UNA sola sentencia UPDATE condicionada a status='unused': si dos celulares
 * escanean a la vez, la base solo deja que una la modifique (1 fila afectada); la otra recibe 0.
 *
 * @return array { result: valid|already_used|invalid|void|error, message, ticket_id?, order_id?, used_at? }
 */
function spp_checkin( $raw, $source, $user_id ) {
	global $wpdb;

	$ticket_id = spp_parse_code( $raw, $source );
	if ( null === $ticket_id ) {
		spp_log_scan( 'invalid', $raw, null, $source, $user_id );
		return array( 'result' => 'invalid', 'message' => 'Código no reconocido.' );
	}

	$table = spp_table();
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE ticket_id = %s", $ticket_id ) );
	if ( ! $row ) {
		spp_log_scan( 'invalid', $raw, $ticket_id, $source, $user_id );
		return array( 'result' => 'invalid', 'message' => 'El boleto no existe.', 'ticket_id' => $ticket_id );
	}

	$tid  = $row->ticket_id; // Forma canónica guardada en la base.
	$base = array( 'ticket_id' => $tid, 'order_id' => (int) $row->wc_order_id );

	if ( 'used' === $row->status ) {
		spp_log_scan( 'already_used', $raw, $tid, $source, $user_id );
		return $base + array(
			'result'  => 'already_used',
			'message' => 'Este boleto ya fue escaneado.',
			'used_at' => $row->used_at ? spp_local_time( $row->used_at ) : '',
		);
	}

	// Anulado a mano, o el pedido ya no está completado (reembolso/cancelación).
	$void = ( 'unused' !== $row->status );
	if ( ! $void && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( (int) $row->wc_order_id );
		$void  = ( ! $order || 'completed' !== $order->get_status() );
	}
	if ( $void ) {
		spp_log_scan( 'void', $raw, $tid, $source, $user_id );
		return $base + array( 'result' => 'void', 'message' => 'Boleto anulado (pedido no completado).' );
	}

	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE $table SET status = 'used', used_at = %s, used_by = %d WHERE ticket_id = %s AND status = 'unused'",
			gmdate( 'Y-m-d H:i:s' ),
			(int) $user_id,
			$tid
		)
	);

	if ( false === $updated ) {
		spp_log_scan( 'error', $raw, $tid, $source, $user_id );
		return $base + array( 'result' => 'error', 'message' => 'Error del servidor. Intenta de nuevo.' );
	}

	if ( 1 === (int) $updated ) {
		spp_log_scan( 'valid', $raw, $tid, $source, $user_id );
		return $base + array( 'result' => 'valid', 'message' => 'Acceso permitido.' );
	}

	// 0 filas: otro escaneo se adelantó (o cambió de estado entre la lectura y la escritura).
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE ticket_id = %s", $tid ) );
	$res = ( $row && 'used' === $row->status ) ? 'already_used' : 'void';
	spp_log_scan( $res, $raw, $tid, $source, $user_id );
	return $base + array(
		'result'  => $res,
		'message' => 'already_used' === $res ? 'Este boleto ya fue escaneado.' : 'Boleto no disponible.',
		'used_at' => ( $row && $row->used_at ) ? spp_local_time( $row->used_at ) : '',
	);
}

/**
 * Endpoint AJAX (solo usuarios con sesión y permiso spp_scan).
 * Protección CSRF: exige POST con Content-Type JSON y una cabecera propia; un sitio externo no puede
 * enviar ese tipo de petición con las cookies del usuario. Sin nonce para que no caduque durante el evento.
 */
function spp_ajax_checkin() {
	nocache_headers();
	if ( ! current_user_can( 'spp_scan' ) ) {
		wp_send_json( array( 'result' => 'error', 'message' => 'Sin permiso.' ), 403 );
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '';
	$ctype  = isset( $_SERVER['CONTENT_TYPE'] ) ? strtolower( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : '';
	$hdr    = isset( $_SERVER['HTTP_X_SPP_SCAN'] ) ? wp_unslash( $_SERVER['HTTP_X_SPP_SCAN'] ) : '';
	if ( 'POST' !== $method || '1' !== $hdr || false === strpos( $ctype, 'application/json' ) ) {
		wp_send_json( array( 'result' => 'error', 'message' => 'Solicitud no válida.' ), 400 );
	}

	$body   = json_decode( (string) file_get_contents( 'php://input' ), true );
	$code   = ( is_array( $body ) && isset( $body['code'] ) ) ? (string) $body['code'] : '';
	$source = ( is_array( $body ) && isset( $body['source'] ) && 'manual' === $body['source'] ) ? 'manual' : 'camera';

	wp_send_json( spp_checkin( $code, $source, get_current_user_id() ) );
}
