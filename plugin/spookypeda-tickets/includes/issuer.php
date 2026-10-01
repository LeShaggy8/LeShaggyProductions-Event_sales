<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Al completarse la orden: programa la emisión con un pequeño retraso para que Eventin alcance a crear los asistentes. */
function spp_on_order_completed( $order_id ) {
	spp_schedule_issue( $order_id, 1, 30 );
}

function spp_schedule_issue( $order_id, $attempt, $delay ) {
	$args = array( (int) $order_id, (int) $attempt );
	if ( function_exists( 'as_schedule_single_action' ) ) {
		as_schedule_single_action( time() + (int) $delay, 'spp_issue_tickets', $args, 'spookypeda' );
	} else {
		wp_schedule_single_event( time() + (int) $delay, 'spp_issue_tickets', $args );
	}
}

/** Todos los eventin_order_id guardados en la orden de WooCommerce (puede haber más de uno). */
function spp_eventin_order_ids( $order ) {
	$ids = array();
	foreach ( $order->get_meta( 'eventin_order_id', false ) as $meta ) {
		$v = trim( (string) $meta->value );
		if ( '' !== $v ) {
			$ids[ $v ] = true;
		}
	}
	return array_map( 'strval', array_keys( $ids ) );
}

/** Asistentes de Eventin ligados a la orden: WC order → eventin_order_id(s) → etn-attendee. */
function spp_find_attendees( $eventin_order_ids ) {
	if ( ! $eventin_order_ids ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'   => 'etn-attendee',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
			'meta_query'  => array(
				array(
					'key'     => 'eventin_order_id',
					'value'   => $eventin_order_ids,
					'compare' => 'IN',
				),
			),
		)
	);
}

function spp_expected_quantity( $order ) {
	$n = 0;
	foreach ( $order->get_items() as $item ) {
		$n += (int) $item->get_quantity();
	}
	return max( 1, $n );
}

/**
 * Emite (o reemite) los boletos de una orden: registra los Ticket ID y envía el correo.
 *
 * @param int  $order_id
 * @param int  $attempt Número de intento (los reintentos esperan a Eventin).
 * @param bool $force   true = reenvío manual: no espera ni se detiene por _spp_issued.
 */
function spp_issue_tickets( $order_id, $attempt = 1, $force = false ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'completed' !== $order->get_status() ) {
		return false;
	}
	if ( ! $force && $order->get_meta( '_spp_issued' ) ) {
		return true; // Ya emitidos: evita duplicados.
	}

	$lock = 'spp_lock_' . (int) $order_id;
	if ( get_transient( $lock ) ) {
		return false;
	}
	set_transient( $lock, 1, 120 );

	try {
		$eo_ids    = spp_eventin_order_ids( $order );
		$attendees = spp_find_attendees( $eo_ids );
		$expected  = spp_expected_quantity( $order );

		// Eventin aún no termina: reintentar más tarde.
		if ( ! $force && count( $attendees ) < $expected && $attempt < SPP_MAX_ATTEMPTS ) {
			spp_schedule_issue( $order_id, $attempt + 1, 60 * $attempt );
			return false;
		}
		if ( ! $attendees ) {
			$order->add_order_note( 'SpookyPeda Tickets: no se encontraron asistentes de Eventin para esta orden. Usa "Enviar/reenviar boletos con QR" cuando existan.' );
			return false;
		}

		$detail = array();
		foreach ( $attendees as $attendee_id ) {
			$ticket_id = (string) get_post_meta( $attendee_id, 'etn_unique_ticket_id', true );
			$status    = get_post_status( $attendee_id );
			if ( preg_match( '/^[A-Za-z0-9_-]{4,40}$/', $ticket_id ) ) {
				spp_register_ticket( $ticket_id, $order_id, $attendee_id );
				$detail[] = sprintf( '#%d %s (%s)', $attendee_id, $ticket_id, $status );
			} else {
				$detail[] = sprintf( '#%d sin Ticket ID válido (%s)', $attendee_id, $status );
			}
		}
		if ( $force || count( $attendees ) < $expected ) {
			$order->add_order_note(
				sprintf(
					'SpookyPeda Tickets (diagnóstico): eventin_order_id=[%s]; asistentes encontrados=%d; esperados=%d. %s',
					implode( ',', $eo_ids ),
					count( $attendees ),
					$expected,
					implode( ' | ', $detail )
				)
			);
		}

		$tickets = spp_get_order_tickets( $order_id );
		if ( ! $tickets ) {
			$order->add_order_note( 'SpookyPeda Tickets: los asistentes no tenían un Ticket ID válido.' );
			return false;
		}

		if ( spp_send_tickets_email( $order, $tickets ) ) {
			$note = sprintf( 'SpookyPeda Tickets: %d boleto(s) con QR enviados a %s.', count( $tickets ), $order->get_billing_email() );
			if ( count( $tickets ) < $expected ) {
				$note .= sprintf( ' Atención: se esperaban %d.', $expected );
			}
			$order->update_meta_data( '_spp_issued', time() );
			$order->add_order_note( $note );
			$order->save();
			return true;
		}

		// Falló el envío: reintentar o avisar.
		if ( ! $force && $attempt < SPP_MAX_ATTEMPTS ) {
			spp_schedule_issue( $order_id, $attempt + 1, 60 * $attempt );
		} else {
			$order->add_order_note( 'SpookyPeda Tickets: no se pudo enviar el correo con los QR. Revisa el correo del sitio (SMTP) y usa "Enviar/reenviar boletos con QR".' );
		}
		return false;
	} finally {
		delete_transient( $lock );
	}
}

/** Acción manual en la pantalla de la orden (también sirve para órdenes vendidas antes del plugin). */
function spp_order_actions( $actions ) {
	$actions['spp_resend'] = 'Enviar/reenviar boletos con QR';
	return $actions;
}

function spp_order_action_resend( $order ) {
	spp_issue_tickets( $order->get_id(), SPP_MAX_ATTEMPTS, true );
}
