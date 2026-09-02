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
            emailForm.addEventListener('submit', function (event) {
                if (!window.confirm('Send this maintenance email to the selected audience now?')) {
                    event.preventDefault();
                }
            });
        }
    });
}());
