<?php
/**
 * Plugin Name: SpookyPeda Tickets
 * Description: Al completarse una orden de WooCommerce, toma los Ticket ID de Eventin, genera un QR por boleto y lo envía al comprador. Incluye el escáner de entrada en /escaner (Fase 2).
 * Version:     0.2.0
 * Requires PHP: 7.4
 * Author:      LeShaggyProductions
 * Text Domain: spookypeda-tickets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPP_VERSION', '0.2.0' );
define( 'SPP_FILE', __FILE__ );
define( 'SPP_DIR', plugin_dir_path( __FILE__ ) );

// --- Configuración editable ---------------------------------------------
define( 'SPP_QR_PREFIX', 'SP5:' );                       // Lo que precede al Ticket ID dentro del QR.
define( 'SPP_EVENT_NAME', 'SpookyPeda Vol. V' );
define( 'SPP_EVENT_INFO', '30 de octubre de 2026 · El Patio Salón de Eventos, Zacatecas' );
define( 'SPP_MAX_ATTEMPTS', 6 );                         // Reintentos si Eventin aún no creó los asistentes.
// ------------------------------------------------------------------------

require_once SPP_DIR . 'includes/db.php';
require_once SPP_DIR . 'includes/qr.php';
require_once SPP_DIR . 'includes/mailer.php';
require_once SPP_DIR . 'includes/issuer.php';
require_once SPP_DIR . 'includes/checkin.php';
require_once SPP_DIR . 'includes/scanner.php';

register_activation_hook( __FILE__, 'spp_install' );
add_action( 'plugins_loaded', 'spp_boot' );

function spp_boot() {
	// Esquema y rol del escáner: se aplican una sola vez tras actualizar el plugin.
	add_action( 'init', 'spp_maybe_upgrade', 5 );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>SpookyPeda Tickets:</strong> requiere WooCommerce activo.</p></div>';
		} );
		return;
	}
	if ( ! spp_gd_available() ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>SpookyPeda Tickets:</strong> el servidor no tiene la extensión GD de PHP; no se podrán generar los QR.</p></div>';
		} );
	}

	add_action( 'woocommerce_order_status_completed', 'spp_on_order_completed', 99, 1 );
	add_action( 'spp_issue_tickets', 'spp_issue_tickets', 10, 2 );
	add_filter( 'woocommerce_order_actions', 'spp_order_actions' );
	add_action( 'woocommerce_order_action_spp_resend', 'spp_order_action_resend' );

	// Fase 2: escáner (/escaner) y validación.
	add_action( 'template_redirect', 'spp_maybe_render_scanner', 1 );
	add_action( 'wp_ajax_spp_checkin', 'spp_ajax_checkin' );
}
