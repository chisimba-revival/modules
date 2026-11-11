/* Copy only the saved address, including after an Ajax save. */
(function () {
    'use strict';
    if (window.chisimbaContentAddressCopy) return;
    window.chisimbaContentAddressCopy = true;
    document.querySelectorAll('[data-content-address-copy]').forEach(button => { button.hidden = false; });
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-content-address-copy]');
        if (!button) return;
        const box = button.closest('[data-content-address]');
        const link = box.querySelector('[data-content-address-link]');
        const status = box.querySelector('[data-content-address-status]');
        button.disabled = true;
        status.textContent = '';
        try {
            await navigator.clipboard.writeText(link.href);
            status.textContent = status.dataset.success;
        } catch (error) {
            status.textContent = status.dataset.failure;
        } finally {
            button.disabled = false;
        }
    });
}());
