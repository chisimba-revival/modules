/* Renew single-use CSRF just before submission; never retry a submitted mutation. */
(function () {
    'use strict';
    document.querySelectorAll('[data-workshop-form]').forEach(function (form) {
        var busy = false;
        var button = form.querySelector('button[type=submit]');
        var label = button.querySelector('span:last-child');
        var spinner = button.querySelector('[data-workshop-spinner]');
        var idleIcon = button.querySelector('svg');
        var original = label.textContent;
        var animation;
        var status = document.createElement('p');
        status.setAttribute('role', 'status');
        status.hidden = true;
        form.appendChild(status);
        function reset() {
            busy = false; button.disabled = false; button.removeAttribute('aria-busy');
            label.textContent = original;
            if (animation) animation.cancel();
            if (spinner) { spinner.style.display = 'none'; idleIcon.style.display = ''; }
        }
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (busy) return;
            if (form.matches('[data-workshop-delete]')) {
                form.elements.confirm_delete.value = '0';
                if (!window.confirm(form.dataset.confirm)) return;
                form.elements.confirm_delete.value = '1';
            }
            busy = true; button.disabled = true; button.setAttribute('aria-busy', 'true');
            if (form.dataset.pending) {
                label.textContent = form.dataset.pending;
                status.hidden = false; status.textContent = form.dataset.pending;
                if (spinner) { spinner.style.display = ''; idleIcon.style.display = 'none'; }
                var icon = spinner ? spinner.querySelector('svg') : idleIcon;
                if (icon && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    animation = icon.animate([{transform:'rotate(0deg)'},{transform:'rotate(360deg)'}], {duration:1000,iterations:Infinity});
                }
            }
            try {
                var response = await fetch(form.dataset.tokenUrl, {
                    method:'POST', credentials:'same-origin', mode:'same-origin', cache:'no-store',
                    headers:{'X-Chisimba-Form':'questionworkshop','Accept':'application/json'}
                });
                if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) throw new Error('token_unavailable');
                var data = await response.json();
                if (!/^[a-f0-9]{64}$/.test(data.token || '')) throw new Error('token_invalid');
                form.elements.csrf_token.value = data.token;
                HTMLFormElement.prototype.submit.call(form);
            } catch (error) {
                reset(); status.hidden = false; status.textContent = form.dataset.sessionError;
            }
        });
        button.disabled = false;
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) { reset(); status.hidden = true; status.textContent = ''; }
        });
    });
}());
