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
            document.querySelectorAll('.system-management form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form === emailForm) {
                        if (!(event.submitter && event.submitter.hasAttribute('data-save-email-draft')) &&
                            !window.confirm('Send this maintenance email to the selected audience now?')) {
                            event.preventDefault();
                            return;
                        }
                    } else {
                        // Carry unsaved email fields alongside the plan or availability mutation.
                        // The server stores them only after administrator and CSRF checks.
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
                    submitting = true;
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
