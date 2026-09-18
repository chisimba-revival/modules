/* Native confirmation, matching the existing Kanban deletion interaction. */
(function () {
    'use strict';
    document.querySelectorAll('[data-workshop-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            form.elements.confirm_delete.value = '0';
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            form.elements.confirm_delete.value = '1';
        });
        form.querySelector('button[type=submit]').disabled = false;
    });
}());
