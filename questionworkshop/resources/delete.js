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

(function () {
    'use strict';
    document.querySelectorAll('[data-workshop-generate]').forEach(function (form) {
        var busy = false;
        var button = form.querySelector('button[type=submit]');
        var label = button.querySelector('span');
        var original = label.textContent;
        var animation;
        var status = document.createElement('p');
        status.setAttribute('role', 'status');
        form.appendChild(status);
        form.addEventListener('submit', function (event) {
            if (busy) { event.preventDefault(); return; }
            busy = true;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            label.textContent = form.dataset.pending;
            status.textContent = form.dataset.pending;
            var icon = button.querySelector('svg');
            if (icon && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                animation = icon.animate([{transform:'rotate(0deg)'},{transform:'rotate(360deg)'}], {duration:1000,iterations:Infinity});
            }
        });
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            busy = false; button.disabled = false; button.removeAttribute('aria-busy');
            label.textContent = original; status.textContent = '';
            if (animation) animation.cancel();
        });
    });
}());
