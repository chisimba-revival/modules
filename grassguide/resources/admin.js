/* Progressive enhancement: keep entered work on errors; never retry automatically. */
(() => {
    'use strict';
    const labels = document.getElementById('grassguide-labels');
    if (!labels) return;
    const dirty = new Set();
    document.querySelectorAll('[data-grass-dialog]').forEach(button => {
        const dialog = document.getElementById(button.dataset.grassDialog);
        if (!dialog || typeof dialog.showModal !== 'function') return;
        button.hidden = false;
        const fallback = document.getElementById(dialog.id + '-inline');
        if (fallback) fallback.hidden = true;
        button.addEventListener('click', () => dialog.showModal());
        dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
    });
    document.querySelectorAll('[data-grass-form]').forEach(form => {
        const changed = () => {
            dirty.add(form);
            if (new URL(form.action).searchParams.get('action') === 'savecontact') {
                document.querySelectorAll('[data-grass-form]').forEach(other => {
                    if (new URL(other.action).searchParams.get('action') !== 'queuepermission') return;
                    other.dataset.draftDirty = '1';
                    other.querySelector('button[type="submit"]').disabled = true;
                    other.querySelector('[data-save-status]').textContent = labels.dataset.saveContactFirst;
                });
            }
            document.querySelectorAll('[data-grass-notice]').forEach(notice => { notice.hidden = true; });
        };
        form.addEventListener('input', changed);
        form.addEventListener('change', changed);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (form.dataset.busy === '1' || form.dataset.draftDirty === '1') return;
            form.dataset.busy = '1';
            const status = form.querySelector('[data-save-status]');
            const button = form.querySelector('button[type="submit"]');
            const body = new FormData(form);
            body.set('ajax', '1');
            button.disabled = true;
            status.textContent = labels.dataset.saving;
            try {
                const response = await fetch(form.action, {method: 'POST', body, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
                const data = await response.json();
                if (typeof data.csrf === 'string') form.elements.csrf_token.value = data.csrf;
                if (typeof data.message !== 'string' || typeof data.ok !== 'boolean') throw new Error('Unexpected response');
                status.textContent = data.message;
                if (data.ok && response.ok) {
                    dirty.delete(form);
                    // Reload only after a confirmed save, including new record links/preview.
                    const destination = new URL(form.action);
                    const action = destination.searchParams.get('action');
                    if (['savecontact', 'queuepermission'].includes(action)) {
                        destination.searchParams.set('action', 'contributors');
                        destination.searchParams.set('contributor', body.get('id'));
                    } else if (['saverights', 'savepolicy'].includes(action)) {
                        destination.searchParams.set('action', action === 'saverights' ? 'rightstask' : 'rights');
                        if (body.has('target')) destination.searchParams.set('target', body.get('target'));
                    } else destination.searchParams.set('action', ['import', 'enrich'].includes(action) ? 'setup' : action === 'savemedia' ? 'media' : 'edit');
                    if (data.record && !['savecontact', 'queuepermission'].includes(action)) destination.searchParams.set('id', data.record.id);
                    destination.searchParams.set(action === 'queuepermission' ? 'queued' : 'saved', '1');
                    window.location.assign(destination.href);
                }
            } catch (_) {
                status.textContent = labels.dataset.failed;
            } finally {
                button.disabled = false;
                form.dataset.busy = '0';
            }
        });
    });
    window.addEventListener('beforeunload', event => {
        if (dirty.size) { event.preventDefault(); event.returnValue = ''; }
    });
})();
