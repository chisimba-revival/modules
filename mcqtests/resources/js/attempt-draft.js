/* Restore only answer controls whose question identity still matches the form. */
(function () {
    'use strict';
    var source = document.getElementById('mcq-attempt-draft');
    var form = document.getElementById('form_submittest');
    if (!source || !form) return;
    var draft = JSON.parse(source.textContent);
    Array.from(form.elements).forEach(function (control) {
        var match = /^ans([0-9]{1,4})$/.exec(control.name);
        if (!match || !Object.prototype.hasOwnProperty.call(draft, control.name)) return;
        var question = form.elements.namedItem('questionId' + match[1]);
        if (!question || question.value !== draft['questionId' + match[1]]) return;
        if (control.type === 'radio') control.checked = control.value === draft[control.name];
        else control.value = draft[control.name];
    });
}());
