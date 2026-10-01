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

  function startCamera() {
    camMsg.textContent = '';
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      camMsg.textContent = 'Este navegador no permite usar la cámara. Usa la captura manual.';
      return;
    }
    navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false
    }).then(function (s) {
      stream = s;
      video.srcObject = s;
      return video.play();
    }).then(function () {
      startBtn.classList.add('hidden');
      camBox.classList.add('live');
      running = true;
      requestWake();
      loop();
    }).catch(function () {
      camMsg.textContent = 'No se pudo abrir la cámara. Revisa el permiso del navegador o usa la captura manual.';
    });
  }

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
