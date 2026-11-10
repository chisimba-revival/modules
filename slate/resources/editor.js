/* Native validation; failures retain the user's form rather than accepting an error as an ID. */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('form_slatepageEditor');
    if (!form) return;
    form.querySelectorAll('.required').forEach(function (field) { field.required = true; });
    var status = document.getElementById('save_results');
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (form.dataset.saving || !form.reportValidity()) return;
        form.dataset.saving = '1';
        var button = form.querySelector('[type="submit"]');
        if (button) button.disabled = true;
        var failureMessage = status_fail;
        try {
            var response = await fetch('index.php?module=slate&action=pagesave', {method:'POST', body:new FormData(form), credentials:'same-origin'});
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
                failureMessage = !response.redirected && !response.ok && (response.headers.get('content-type') || '').includes('text/plain') ? await response.text() : status_fail;
                throw new Error('Save failed');
            }
            var result = await response.json();
            if (!result.ok || !/^[a-zA-Z0-9_-]{1,32}$/.test(result.id) || !/^[a-f0-9]{64}$/.test(result.revision)) throw new Error('Invalid save result');
            form.elements.id.value = result.id;
            form.elements.revision.value = result.revision;
            form.elements.mode.value = 'edit';
            form.elements.page.readOnly = true;
            status.textContent = status_success;
        } catch (error) {
            status.textContent = failureMessage;
        } finally {
            status.setAttribute('role', 'status');
            delete form.dataset.saving;
            if (button) button.disabled = false;
        }
    });
});
