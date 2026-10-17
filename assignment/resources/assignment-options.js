/* Preserve group/outcome selections while their optional controls are hidden. */
(function () {
    'use strict';
    function initialise() {
        [['groups_radio', 'groupslist', 'selectgroups'], ['goals_radio', 'goalslist', 'selectgoals']].forEach(function (group) {
            const choices = document.querySelectorAll('input[name="' + group[0] + '"]');
            if (!choices.length) return;
            function update() {
                const checked = Array.from(choices).find(function (choice) { return choice.checked; });
                group.slice(1).forEach(function (id) {
                    const element = document.getElementById(id);
                    if (element) element.hidden = !checked || checked.value !== '1';
                });
            }
            choices.forEach(function (choice) { choice.addEventListener('change', update); });
            update();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialise, {once: true});
    else initialise();
}());
