/* Native recording is progressive enhancement; file upload always remains available. */
'use strict';
document.querySelectorAll('[data-spoken-recorder]').forEach(form => {
    const record = form.querySelector('[data-record]');
    const stop = form.querySelector('[data-stop]');
    const status = form.querySelector('[data-record-status]');
    const preview = form.querySelector('[data-record-preview]');
    const input = form.querySelector('input[type=file]');
    let recorder, stream, timer, ticker, previewUrl, recording = false;
    const release = () => {
        clearTimeout(timer); clearInterval(ticker);
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null; recording = false; stop.disabled = true;
        record.disabled = !window.MediaRecorder || !navigator.mediaDevices?.getUserMedia;
    };
    const showPreview = file => {
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        form.querySelector('[name=upload_token]').value = Array.from(bytes, x => x.toString(16).padStart(2, '0')).join('');
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file); preview.src = previewUrl; preview.hidden = false;
        const download = form.querySelector('[data-record-download]'); download.href = previewUrl; download.download = file.name; download.hidden = false;
    };
    input.addEventListener('change', () => { if (input.files[0]) showPreview(input.files[0]); });
    if (window.MediaRecorder && navigator.mediaDevices?.getUserMedia && window.DataTransfer) record.disabled = false;
    record.addEventListener('click', async () => {
        record.disabled = true;
        try {
            stream = await navigator.mediaDevices.getUserMedia({audio: true});
            const mime = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'].find(type => MediaRecorder.isTypeSupported(type));
            recorder = new MediaRecorder(stream, mime ? {mimeType: mime} : undefined);
            const chunks = []; let bytes = 0; let tooLarge = false;
            recorder.addEventListener('dataavailable', event => {
                bytes += event.data.size;
                if (bytes > 20 * 1024 * 1024) { tooLarge = true; if (recorder.state !== 'inactive') recorder.stop(); }
                else if (event.data.size) chunks.push(event.data);
            });
            recorder.addEventListener('error', () => { status.textContent = status.dataset.error; release(); });
            recorder.addEventListener('stop', () => {
                const type = recorder.mimeType || mime || 'audio/webm';
                release();
                if (tooLarge || !chunks.length) { status.textContent = status.dataset.error; return; }
                const extension = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
                const file = new File(chunks, `recording.${extension}`, {type});
                const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files;
                showPreview(file); status.textContent = status.dataset.ready;
            });
            recorder.start(1000); recording = true; stop.disabled = false;
            const start = Date.now();
            const tick = () => { status.textContent = `${status.dataset.recording} ${Math.floor((Date.now() - start) / 1000)} / 180 s`; };
            tick(); ticker = setInterval(tick, 1000);
            timer = setTimeout(() => { if (recorder.state !== 'inactive') recorder.stop(); }, 179000);
        } catch (_) { release(); status.textContent = status.dataset.error; }
    });
    stop.addEventListener('click', () => { if (recorder?.state === 'recording') recorder.stop(); });
    form.addEventListener('submit', async event => {
        if (recording) { event.preventDefault(); stop.focus(); return; }
        event.preventDefault();
        if (!form.reportValidity()) return;
        const submit = form.querySelector('[type=submit]');
        submit.disabled = true; status.textContent = status.dataset.uploading;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            const result = await response.json();
            if (result.ok && result.redirect) { window.location.assign(result.redirect); return; }
            if (result.token) form.querySelector('[name=csrf_token]').value = result.token;
            status.textContent = result.message || status.dataset.uploadError;
        } catch (_) { status.textContent = status.dataset.uploadError; }
        submit.disabled = false;
    });
    window.addEventListener('pagehide', () => { if (recorder?.state === 'recording') recorder.stop(); release(); if (previewUrl) URL.revokeObjectURL(previewUrl); });
});
