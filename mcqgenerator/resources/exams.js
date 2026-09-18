/* Keep selections and exam edits from being lost when switching chapters. */
(function () {
    'use strict';
    var forms = Array.from(document.querySelectorAll('[data-exam-dirty]'));
    var dirty = new Set();
    forms.forEach(function (form) {
        form.addEventListener('input', function () { dirty.add(form); });
        form.addEventListener('change', function () { dirty.add(form); });
    });
    // The shared token helper emits this only once submission is securely prepared.
    document.addEventListener('workshop:submitting', function (event) { dirty.delete(event.target); });
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || !dirty.size || event.ctrlKey || event.metaKey || link.target === '_blank') return;
        if (!window.confirm(forms[0].dataset.unsaved)) { event.preventDefault(); return; }
        dirty.clear();
    });
    window.addEventListener('beforeunload', function (event) {
        if (dirty.size) { event.preventDefault(); event.returnValue = ''; }
    });
}());
