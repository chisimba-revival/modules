/* Add in place; Buy now and the cart link retain ordinary navigation. */
document.querySelectorAll('[data-shop-page-add]').forEach(form => {
    let busy = false;
    form.addEventListener('submit', async event => {
        if (busy) { event.preventDefault(); return; }
        if (event.submitter?.value !== 'stay') return;
        event.preventDefault();
        const status = form.closest('[data-shop-purchase]').querySelector('[data-shop-add-status]');
        const data = new FormData(form); data.set('intent', 'stay'); data.set('format', 'json');
        busy = true;
        const buttons = [...form.querySelectorAll('button')]; buttons.forEach(b => b.disabled = true);
        status.textContent = form.dataset.pending;
        try {
            const response = await fetch(form.action, {method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error('Unconfirmed cart update');
            document.querySelectorAll('[data-shop-page-add]').forEach(other => other.elements.csrf_token.value = result.token);
            form.elements.request_key.value = result.request_key;
            status.textContent = result.message;
            busy = false; buttons.forEach(b => b.disabled = false);
        } catch (_) {
            // Do not replay an uncertain mutation. Go to cart remains available.
            status.textContent = form.dataset.uncertain;
        }
    });
});
