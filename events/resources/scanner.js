/* Progressive camera scanning; authenticated POST performs admission. Derek Keats <derek@dkeats.com> */
(() => {
  'use strict';
  const start = document.getElementById('start-camera');
  if (!start) return;
  const stop = document.getElementById('stop-camera'), video = document.getElementById('scan-video');
  const status = document.getElementById('scan-status'), code = document.getElementById('code');
  let stream, running = false;
  const search = document.querySelector('[data-attendee-search]');
  if (search) search.addEventListener('input', () => {
    const term = search.value.toLocaleLowerCase().trim();
    document.querySelectorAll('[data-attendee-name]').forEach(row => { row.hidden = !row.dataset.attendeeName.includes(term); });
  });
  const close = () => { running = false; if (stream) stream.getTracks().forEach(t => t.stop()); video.hidden = true; stop.hidden = true; start.disabled = false; };
  start.addEventListener('click', async () => {
    if (!window.BarcodeDetector || !navigator.mediaDevices) { status.textContent = status.dataset.cameraUnavailable; code.focus(); return; }
    try {
      start.disabled = true;
      const detector = new BarcodeDetector({formats: ['qr_code']});
      stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}, audio: false});
      video.srcObject = stream; video.hidden = false; stop.hidden = false; await video.play(); running = true;
      const scan = async () => {
        if (!running) return;
        try {
          const hits = await detector.detect(video);
          const match = hits.find(hit => /^[A-F0-9]{20}$/.test(hit.rawValue));
          if (match) { code.value = match.rawValue; close(); status.textContent = status.dataset.scanned; code.focus(); return; }
        } catch (_) { close(); status.textContent = status.dataset.cameraUnavailable; return; }
        setTimeout(scan, 250);
      }; scan();
    } catch (_) { close(); status.textContent = status.dataset.cameraUnavailable; code.focus(); }
  });
  stop.addEventListener('click', close);
  window.addEventListener('pagehide', close);
  document.addEventListener('visibilitychange', () => { if (document.hidden) close(); });
})();
