/* Native validation and recoverable submission for the Textblock editor. */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('form_blockeditor');
    if (!form) return;
    var status = document.getElementById('textblock-save-status');
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (form.dataset.saving || !form.reportValidity()) return;
        form.dataset.saving = '1';
        var button = form.querySelector('[type="submit"]');
        if (button) button.disabled = true;
        var message = status.dataset.failure;
        try {
            var response = await fetch(form.action, {method:'POST', body:new FormData(form), headers:{Accept:'application/json'}, credentials:'same-origin'});
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
                message = !response.redirected && (response.headers.get('content-type') || '').includes('text/plain') ? await response.text() : status.dataset.failure;
                throw new Error(message);
            }
            var result = await response.json();
            if (!result.ok) throw new Error(status.dataset.failure);
            var target = new URL(form.action, location.href);
            target.searchParams.set('action', form.elements.blockid.value.startsWith('widetext') ? 'widetext' : 'text');
            target.searchParams.delete('mode');
            target.searchParams.delete('id');
            location.assign(target.href);
        } catch (error) {
            status.textContent = message;
            status.setAttribute('role', 'alert');
        } finally {
            delete form.dataset.saving;
            if (button) button.disabled = false;
        }
    });
});
