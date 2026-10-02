<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** ¿La petición es a /escaner (respetando instalaciones en subcarpeta)? */
function spp_is_scanner_request() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $base && 0 === strpos( $path, $base ) ) {
		$path = substr( $path, strlen( $base ) );
	}
	return 'escaner' === trim( $path, '/' );
}

function spp_maybe_render_scanner() {
	if ( ! spp_is_scanner_request() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	// Algunos plugins de seguridad envían camera=() en todo el sitio; aquí se permite la cámara para esta página.
	header( 'Permissions-Policy: camera=(self)' );
	header( "Feature-Policy: camera 'self'" );

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( home_url( '/escaner/' ) ) );
		exit;
	}
	if ( ! current_user_can( 'spp_scan' ) ) {
		status_header( 403 );
		wp_die( 'Tu usuario no tiene permiso para usar el escáner. Pide acceso al organizador.', 'Sin permiso', array( 'response' => 403 ) );
	}

	status_header( 200 ); // WordPress ya pudo marcar esta ruta como 404.
	spp_render_scanner();
	exit;
}

function spp_render_scanner() {
	$cfg = array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) );
	$ver = rawurlencode( SPP_VERSION );
	$user = wp_get_current_user();
	?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#05060a">
<title>Escáner · SpookyPeda</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:flex;flex-direction:column;background:#05060a;color:#e8f1ee;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.12);font-size:14px}
.top strong{color:#39ff88;font-size:16px}
.top a{color:#22e4ff}
main{flex:1;width:100%;max-width:520px;margin:0 auto;padding:16px}
.cam{position:relative;aspect-ratio:3/4;max-height:60vh;width:100%;background:#000;border:1px solid rgba(57,255,136,.4);border-radius:16px;overflow:hidden;display:flex;align-items:center;justify-content:center}
.cam video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.frame{position:absolute;inset:18%;border:3px solid rgba(57,255,136,.85);border-radius:16px;pointer-events:none}
.cam:not(.live) .frame{display:none}
#spp-start{position:relative;z-index:2}
.msg{margin:10px 0 0;min-height:1.2em;font-size:14px;color:#ffb4b4}
.btn{background:#39ff88;color:#05060a;border:0;border-radius:999px;padding:14px 22px;font-weight:700;font-size:16px;cursor:pointer}
.manual{margin-top:20px}
.manual label{display:block;font-size:14px;margin-bottom:6px;color:#9fb3ad}
.row{display:flex;gap:8px}
.row input{flex:1;min-width:0;padding:14px;border-radius:12px;border:1px solid rgba(255,255,255,.25);background:#0c0f14;color:#fff;font-size:18px}
.hidden{display:none!important}
.result{position:fixed;inset:0;z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:24px;text-align:center;color:#fff}
.result.ok{background:#0a7d3e}.result.used{background:#b45309}.result.bad{background:#b91c1c}
.icon{font-size:96px;line-height:1;font-weight:800}
.title{font-size:42px;font-weight:800}
.detail{font-size:18px;white-space:pre-line}
.result .btn{background:#fff;color:#111;margin-top:12px}
</style>
</head>
<body>
<header class="top">
	<strong>Escáner SpookyPeda</strong>
	<span><?php echo esc_html( $user->display_name ); ?> · <a href="<?php echo esc_url( wp_logout_url( home_url( '/escaner/' ) ) ); ?>">Salir</a></span>
</header>
<main>
	<div class="cam" id="spp-cam">
		<video id="spp-video" playsinline muted></video>
		<div class="frame"></div>
		<button type="button" id="spp-start" class="btn">Activar cámara</button>
	</div>
	<p class="msg" id="spp-cam-msg" role="status">Cargando escáner…</p>
	<form class="manual" id="spp-manual" autocomplete="off">
		<label for="spp-code">Si la cámara falla, escribe el Ticket ID</label>
		<div class="row">
			<input id="spp-code" type="text" inputmode="text" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="tm39lapsuj">
			<button class="btn" type="submit">Validar</button>
		</div>
	</form>
</main>
<div id="spp-result" class="result hidden" role="alert" aria-live="assertive">
	<div class="icon" id="spp-icon"></div>
	<div class="title" id="spp-title"></div>
	<div class="detail" id="spp-detail"></div>
	<button type="button" class="btn" id="spp-next">Siguiente</button>
</div>
<script>window.SPP_SCANNER = <?php echo wp_json_encode( $cfg ); ?>;</script>
<script src="<?php echo esc_url( plugins_url( 'assets/jsQR.js', SPP_FILE ) ); ?>?v=<?php echo $ver; // phpcs:ignore WordPress.Security.EscapeOutput ?>"></script>
<script src="<?php echo esc_url( plugins_url( 'assets/scanner.js', SPP_FILE ) ); ?>?v=<?php echo $ver; // phpcs:ignore WordPress.Security.EscapeOutput ?>"></script>
</body>
</html>
	<?php
}
