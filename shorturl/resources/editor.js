/* Native progressive enhancement: retain drafts on definite failures; never replay uncertain writes. */
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-shorturl-confirm]').forEach(label => {
            label.hidden = true; label.querySelector('input').required = false;
        });
    });
    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.matches('[data-shorturl-form]')) return;
        event.preventDefault();
        if (form.dataset.busy === '1' || !form.reportValidity()) return;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
        const status = document.querySelector('[data-shorturl-status]');
        const body = new FormData(form);
        if (form.dataset.confirm) body.set('confirm', 'yes');
        form.dataset.busy = '1';
        const buttons = Array.from(form.querySelectorAll('button'));
        buttons.forEach(button => { button.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        let uncertain = true;
        try {
            const response = await fetch(form.action, {method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json'}});
            if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Uncertain response');
            const result = await response.json();
            if (response.ok && result.ok === true) { window.location.assign(form.dataset.return); return; }
            // A structured rejection is safe to retry after correcting fields/token; text is retained.
            if (result.ok !== false) throw new Error('Uncertain response');
            uncertain = false;
            status.textContent = result.message || form.dataset.failure;
        } catch (error) {
            status.textContent = form.dataset.uncertain;
        } finally {
            form.removeAttribute('aria-busy');
            if (!uncertain) {
                form.dataset.busy = '0';
                buttons.forEach(button => { button.disabled = false; });
            }
        }
    });
}());
