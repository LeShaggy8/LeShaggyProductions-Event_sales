<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SPP_DIR . 'includes/vendor/qrcode.php';

function spp_qr_payload( $ticket_id ) {
	return SPP_QR_PREFIX . $ticket_id;
}

function spp_gd_available() {
	return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagepng' );
}

/** Imagen GD del QR: módulos de 10 px y margen de 4 módulos (zona de silencio). */
function spp_qr_image( $ticket_id ) {
	$qr = QRCode::getMinimumQRCode( spp_qr_payload( $ticket_id ), QR_ERROR_CORRECT_LEVEL_M );
	return $qr->createImage( 10, 40 );
}

/** Escribe el PNG en una carpeta temporal y devuelve su ruta (o false). */
function spp_qr_png_file( $ticket_id ) {
	if ( ! spp_gd_available() ) {
		return false;
	}
	$dir = trailingslashit( get_temp_dir() ) . 'spp-' . wp_generate_password( 8, false, false ) . '/';
	if ( ! wp_mkdir_p( $dir ) ) {
		return false;
	}
	$path = $dir . 'SpookyPeda-V-' . $ticket_id . '.png';
	$ok   = imagepng( spp_qr_image( $ticket_id ), $path );
	return $ok ? $path : false;
}

function spp_cleanup_files( $paths ) {
	foreach ( (array) $paths as $path ) {
		if ( is_file( $path ) ) {
			@unlink( $path );
		}
		@rmdir( dirname( $path ) );
	}
}
