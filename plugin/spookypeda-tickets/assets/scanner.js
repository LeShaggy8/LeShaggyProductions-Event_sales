(function () {
  'use strict';

  var cfg = window.SPP_SCANNER || {};
  var $ = function (id) { return document.getElementById(id); };

  var video = $('spp-video'), startBtn = $('spp-start'), camBox = $('spp-cam'), camMsg = $('spp-cam-msg');
  var resultEl = $('spp-result'), iconEl = $('spp-icon'), titleEl = $('spp-title'), detailEl = $('spp-detail'), nextBtn = $('spp-next');
  var form = $('spp-manual'), input = $('spp-code');

  var canvas = document.createElement('canvas');
  var ctx = canvas.getContext('2d', { willReadFrequently: true });
  var stream = null, running = false, wasRunning = false, busy = false;
  var lastTick = 0, lastCode = '', lastAt = 0, hideTimer = 0, wakeLock = null, reloadOnNext = false;

  var UI = {
    valid:        { cls: 'ok',   icon: '✓', title: 'VÁLIDO' },
    already_used: { cls: 'used', icon: '!', title: 'YA USADO' },
    invalid:      { cls: 'bad',  icon: '✕', title: 'NO VÁLIDO' },
    void:         { cls: 'bad',  icon: '✕', title: 'ANULADO' },
    error:        { cls: 'bad',  icon: '!', title: 'ERROR' }
  };

  function showResult(d) {
    var ui = UI[d.result] || UI.error;
    var lines = [];
    if (d.message) lines.push(d.message);
    if (d.courtesy) lines.push('CORTESÍA');
    if (d.ticket_id) lines.push('Boleto ' + d.ticket_id);
    if (d.order_id) lines.push('Pedido #' + d.order_id);
    if (d.used_at) lines.push('Usado: ' + d.used_at);

    resultEl.className = 'result ' + ui.cls;
    iconEl.textContent = ui.icon;
    titleEl.textContent = ui.title;
    detailEl.textContent = lines.join('\n');
    reloadOnNext = !!d.reload;
    nextBtn.textContent = reloadOnNext ? 'Recargar' : 'Siguiente';

    if (d.result !== 'error') input.value = '';
    if (navigator.vibrate) navigator.vibrate(d.result === 'valid' ? 120 : [200, 100, 200]);

    clearTimeout(hideTimer);
    if (d.result === 'valid') hideTimer = setTimeout(hideResult, 1500); // Los rojos/ámbar esperan un toque.
  }

  function hideResult() {
    if (reloadOnNext) { location.reload(); return; }
    clearTimeout(hideTimer);
    resultEl.className = 'result hidden';
    lastAt = Date.now();
    busy = false;
  }

  function submit(code, source) {
    if (busy) return;
    busy = true;
    fetch(cfg.ajaxUrl + '?action=spp_checkin', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-SPP-Scan': '1' },
      body: JSON.stringify({ code: code, source: source })
    }).then(function (res) {
      if (res.status === 400 || res.status === 401 || res.status === 403) throw { auth: true };
      if (!res.ok) throw new Error('http ' + res.status);
      return res.json();
    }).then(showResult).catch(function (err) {
      lastCode = ''; // Permite reintentar el mismo QR.
      if (err && err.auth) {
        showResult({ result: 'error', reload: true, message: 'Sesión caducada o sin permiso. Recarga e inicia sesión.' });
      } else {
        showResult({ result: 'error', message: 'Sin conexión o error del servidor. Intenta de nuevo.' });
      }
    });
  }

  function handleCode(code) {
    var now = Date.now();
    if (code === lastCode && now - lastAt < 5000) return; // Evita releer el mismo QR de inmediato.
    lastCode = code; lastAt = now;
    submit(code, 'camera');
  }

  function loop() {
    if (!running) return;
    requestAnimationFrame(loop);
    if (busy || !video.videoWidth) return;
    var now = Date.now();
    if (now - lastTick < 120) return;
    lastTick = now;

    var scale = Math.min(1, 640 / video.videoWidth);
    var w = Math.round(video.videoWidth * scale), h = Math.round(video.videoHeight * scale);
    if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; }
    ctx.drawImage(video, 0, 0, w, h);
    var img = ctx.getImageData(0, 0, w, h);
    var qr = window.jsQR && window.jsQR(img.data, w, h, { inversionAttempts: 'dontInvert' });
    if (qr && qr.data) handleCode(qr.data);
  }

  function requestWake() {
    if (navigator.wakeLock) navigator.wakeLock.request('screen').then(function (l) { wakeLock = l; }).catch(function () {});
  }

  function stopCamera() {
    running = false;
    if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
    camBox.classList.remove('live');
  }

  function cameraProblem(err) {
    var name = (err && err.name) || 'desconocido';
    var why = {
      NotAllowedError: 'Permiso denegado. Toca el candado junto a la dirección y permite la cámara; si ya la permitiste, el sitio puede estar bloqueándola.',
      SecurityError: 'El navegador bloqueó la cámara por seguridad (¿página sin https?).',
      NotFoundError: 'Este dispositivo no tiene una cámara disponible.',
      NotReadableError: 'La cámara está en uso por otra app o pestaña. Ciérrala e intenta de nuevo.',
      OverconstrainedError: 'La cámara no cumple los ajustes pedidos.',
      AbortError: 'No se pudo iniciar la cámara.'
    };
    return (why[name] || 'No se pudo abrir la cámara.') + ' [' + name + '] Puedes usar la captura manual.';
  }

  function getStream() {
    var md = navigator.mediaDevices;
    return md.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false
    }).catch(function (err) {
      // Algunos equipos fallan con restricciones; se reintenta con la cámara por defecto.
      if (err && (err.name === 'OverconstrainedError' || err.name === 'NotFoundError' || err.name === 'AbortError')) {
        return md.getUserMedia({ video: true, audio: false });
      }
      throw err;
    });
  }

  function startCamera() {
    camMsg.textContent = '';
    if (window.isSecureContext === false) {
      camMsg.textContent = 'La cámara solo funciona en páginas https://. Abre esta página con https.';
      return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      camMsg.textContent = 'Este navegador no permite usar la cámara (¿navegador dentro de WhatsApp/Instagram?). Ábrelo en Chrome o Safari, o usa la captura manual.';
      return;
    }
    var pp = document.permissionsPolicy || document.featurePolicy;
    if (pp && typeof pp.allowsFeature === 'function' && !pp.allowsFeature('camera')) {
      camMsg.textContent = 'El sitio está bloqueando la cámara con una política de permisos (cabecera Permissions-Policy del servidor o de un plugin de seguridad).';
      return;
    }
    getStream().then(function (s) {
      stream = s;
      video.srcObject = s;
      return video.play();
    }).then(function () {
      startBtn.classList.add('hidden');
      camBox.classList.add('live');
      running = true;
      requestWake();
      loop();
    }).catch(function (err) {
      stopCamera();
      camMsg.textContent = cameraProblem(err);
    });
  }

  camMsg.style.color = '#9fb3ad';
  camMsg.textContent = window.jsQR ? 'Escáner listo. Toca "Activar cámara".' : 'No cargó la librería de lectura (jsQR). La captura manual sigue funcionando.';
  startBtn.addEventListener('click', startCamera);
  nextBtn.addEventListener('click', hideResult);
  resultEl.addEventListener('click', function (e) { if (e.target === resultEl) hideResult(); });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var v = input.value.trim();
    if (v) submit(v, 'manual');
  });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      wasRunning = running;
      stopCamera();
    } else if (wasRunning) {
      wasRunning = false;
      startCamera();
    }
  });
})();
