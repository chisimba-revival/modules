(function () {
    'use strict';
    function initialise() {
        const chooser = document.getElementById('qnoption');
        const mcq = document.getElementById('addquestion');
        const freeform = document.getElementById('freeform');
        if (!chooser || !mcq || !freeform) return;
        function selectForm() {
            mcq.hidden = chooser.value !== 'mcq';
            freeform.hidden = chooser.value !== 'freeform';
        }
        chooser.addEventListener('change', selectForm);
        selectForm();
        const options = mcq.querySelector('select[name="options"]');
        const form = options && options.form;
        if (!form) return;
        const choices = mcq.querySelectorAll('input[name="type"]');
        function selectType(resetCount) {
            const checked = Array.from(choices).find(function (choice) { return choice.checked; });
            const trueFalse = checked && checked.value === 'tf';
            if (trueFalse) options.value = '2';
            else if (resetCount) options.value = '4';
            options.disabled = Boolean(trueFalse);
        }
        choices.forEach(function (choice) { choice.addEventListener('change', function () { selectType(true); }); });
        selectType(false);
        // Only this selector needs enabling for submission. Leave other protected
        // or irrelevant controls disabled, and restore state if submission cancels.
        form.addEventListener('submit', function () {
            options.disabled = false;
            setTimeout(function () { selectType(false); }, 0);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialise, {once: true});
    else initialise();
}());
