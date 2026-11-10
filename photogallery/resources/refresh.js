/* Explicit, non-retrying refresh with visible progress. No drafts are submitted. */
(() => {
    'use strict';
    document.querySelectorAll('[data-photo-refresh]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.busy === 'true') { event.preventDefault(); return; }
            form.dataset.busy = 'true';
            form.querySelector('button[type="submit"]').disabled = true;
            form.querySelector('[role="status"]').textContent = form.dataset.progress;
        });
        window.addEventListener('pageshow', () => {
            form.dataset.busy = 'false';
            form.querySelector('button[type="submit"]').disabled = false;
            form.querySelector('[role="status"]').textContent = '';
        });
    });
})();
