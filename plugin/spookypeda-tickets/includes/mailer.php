<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Envía el correo "Tus boletos" con un PNG adjunto por boleto.
 *
 * @param WC_Order $order
 * @param object[] $tickets Filas de la tabla spp_tickets.
 * @return bool
 */
function spp_send_tickets_email( $order, $tickets ) {
	$to = $order->get_billing_email();
	if ( ! is_email( $to ) ) {
		return false;
	}

	$files = array();
	$items = '';
	foreach ( $tickets as $i => $t ) {
		$file = spp_qr_png_file( $t->ticket_id );
		if ( ! $file ) {
			continue;
		}
		$files[] = $file;
		$name    = $t->attendee_post_id ? get_post_meta( $t->attendee_post_id, 'etn_name', true ) : '';
		$items  .= '<li style="margin:0 0 8px">Boleto ' . ( $i + 1 )
			. ( $name ? ' — ' . esc_html( $name ) : '' )
			. ' · <strong>' . esc_html( $t->ticket_id ) . '</strong></li>';
	}
	if ( ! $files ) {
		return false;
	}

	$first   = $order->get_billing_first_name();
	$subject = sprintf( 'Tus boletos para %s', SPP_EVENT_NAME );
	$body    = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#111;max-width:560px">'
		. '<h2 style="margin:0 0 12px">' . esc_html( SPP_EVENT_NAME ) . '</h2>'
		. '<p>' . ( $first ? 'Hola ' . esc_html( $first ) . ', ' : '' ) . 'tu compra (pedido #' . esc_html( $order->get_order_number() ) . ') está confirmada.</p>'
		. '<p><strong>' . esc_html( SPP_EVENT_INFO ) . '</strong></p>'
		. '<p>Adjuntamos <strong>un código QR por boleto</strong>. Cada persona debe mostrar el suyo en la entrada; cada QR se puede usar una sola vez. Guárdalos en tu celular.</p>'
		. '<ul style="padding-left:18px">' . $items . '</ul>'
		. '<p style="color:#555;font-size:13px">Si compraste varios boletos, reenvía a cada persona su archivo.</p>'
		. '</div>';

	$ok = wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ), $files );
	spp_cleanup_files( $files );
	return (bool) $ok;
}
