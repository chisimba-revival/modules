/**
 * Keep related maintenance and notice date ranges valid while editing.
 *
 * @author Derek Keats
 * @package systemmanagement
 */
(function () {
    'use strict';

    function linkDateRange(startId, endId) {
        var start = document.getElementById(startId);
        var end = document.getElementById(endId);

        if (!start || !end) {
            return;
        }

        function validateRange() {
            end.min = start.value;
            if (start.value && end.value && end.value <= start.value) {
                end.setCustomValidity('The end must be later than the start.');
            } else {
                end.setCustomValidity('');
            }
        }

        start.addEventListener('input', validateRange);
        end.addEventListener('input', validateRange);
        validateRange();
    }

    document.addEventListener('DOMContentLoaded', function () {
        linkDateRange('maintenance-start', 'maintenance-end');
        var emailForm = document.querySelector('[data-maintenance-email]');
        if (emailForm) {
            var fields = {audience: 'email-audience', subject: 'email-subject', message: 'email-message'};
            var dirty = false;
            var submitting = false;
            emailForm.addEventListener('input', function () {
                dirty = true;
                document.querySelector('[data-email-draft-status]').textContent = emailForm.dataset.draftLabel;
            });
            var workspace = document.querySelector('.system-management');
            var busy = false;
            function draftValue() {
                return JSON.stringify(Object.keys(fields).map(function (name) {
                    return document.getElementById(fields[name]).value;
                }));
            }
            function deliveryRows(deliveries) {
                var section = workspace.querySelector('[data-maintenance-delivery]');
                var body = section.querySelector('tbody');
                body.replaceChildren();
                deliveries.forEach(function (item) {
                    var row = document.createElement('tr');
                    var outcome = item.attempt_outcome || item.last_error || workspace.dataset.deliveryPending;
                    if (item.response_code !== null) outcome += ' (' + item.response_code + ')';
                    [item.date_created, item.recipient, item.subject, item.status, item.attempts,
                        item.transport || '', outcome].forEach(function (value) {
                        var cell = document.createElement('td');
                        cell.textContent = value == null ? '' : String(value);
                        row.appendChild(cell);
                    });
                    body.appendChild(row);
                });
                section.querySelector('[data-delivery-table]').hidden = deliveries.length === 0;
                var empty = section.querySelector('[data-delivery-empty]');
                if (empty) empty.hidden = deliveries.length !== 0;
            }
            workspace.querySelectorAll('form').forEach(function (form) {
                var feedback = document.createElement('p');
                feedback.className = 'chisimba-notice';
                feedback.setAttribute('role', 'status');
                feedback.setAttribute('aria-live', 'polite');
                feedback.hidden = true;
                form.appendChild(feedback);
                form.addEventListener('submit', async function (event) {
                    if (busy) { event.preventDefault(); return; }
                    if (form === emailForm) {
                        if (!(event.submitter && event.submitter.hasAttribute('data-save-email-draft')) &&
                            !window.confirm(workspace.dataset.emailConfirm)) {
                            event.preventDefault();
                            return;
                        }
                    } else {
                        Object.keys(fields).forEach(function (name) {
                            var key = 'email_draft[' + name + ']';
                            var input = Array.from(form.elements).find(function (element) { return element.name === key; });
                            if (!input) {
                                input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = key;
                                form.appendChild(input);
                            }
                            input.value = document.getElementById(fields[name]).value;
                        });
                    }
                    if (!window.fetch || !window.FormData || !window.AbortController) { submitting = true; return; }
                    event.preventDefault();
                    var snapshot = draftValue();
                    var data = new FormData(form);
                    data.set('response', 'json');
                    var action = event.submitter && event.submitter.hasAttribute('formaction') ? event.submitter.formAction : form.action;
                    var controls = Array.from(workspace.querySelectorAll('button[type="submit"]'));
                    var disabled = controls.map(function (button) { return button.disabled; });
                    busy = true;
                    controls.forEach(function (button) { button.disabled = true; });
                    form.setAttribute('aria-busy', 'true');
                    feedback.hidden = false;
                    feedback.className = 'chisimba-notice';
                    feedback.textContent = workspace.dataset.ajaxPending;
                    var abort = new AbortController();
                    var timeout = window.setTimeout(function () { abort.abort(); }, 30000);
                    try {
                        var response = await fetch(action, {method: 'POST', body: data,
                            credentials: 'same-origin', signal: abort.signal, headers: {'Accept': 'application/json'}});
                        if (!(response.headers.get('content-type') || '').includes('application/json')) throw new Error('Unexpected response');
                        var result = await response.json();
                        if (!result.csrf || !result.maintenance || !Array.isArray(result.deliveries)) throw new Error('Incomplete response');
                        workspace.querySelectorAll('[name="csrf_token"]').forEach(function (input) { input.value = result.csrf; });
                        feedback.className = 'chisimba-notice ' + (result.ok ? 'chisimba-notice--success' : 'chisimba-notice--error');
                        feedback.textContent = result.error || result.message;
                        workspace.querySelector('[data-maintenance-status]').textContent = result.maintenance.status;
                        var toggle = workspace.querySelector('[data-maintenance-toggle]');
                        toggle.querySelector('[name="state"]').value = result.maintenance.nextState;
                        var button = toggle.querySelector('button');
                        button.classList.toggle('chisimba-state-action--offline', result.maintenance.active);
                        button.classList.toggle('chisimba-state-action--online', !result.maintenance.active);
                        // Only the trusted icon-service markup is inserted as HTML; all message data is text.
                        button.innerHTML = result.maintenance.icon;
                        button.appendChild(document.createTextNode(' ' + result.maintenance.buttonLabel));
                        deliveryRows(result.deliveries);
                        if (snapshot === draftValue()) {
                            if (result.ok) dirty = false;
                            workspace.querySelector('[data-email-draft-status]').textContent = result.draftLabel;
                        }
                    } catch (error) {
                        feedback.className = 'chisimba-notice chisimba-notice--error';
                        feedback.textContent = workspace.dataset.ajaxFailed;
                        // Never retry a mutation automatically: the server may already have queued mail.
                    } finally {
                        window.clearTimeout(timeout);
                        busy = false;
                        controls.forEach(function (button, index) { button.disabled = disabled[index]; });
                        form.removeAttribute('aria-busy');
                    }
                });
            });
            window.addEventListener('beforeunload', function (event) {
                if (dirty && !submitting) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
        }
    });
}());
