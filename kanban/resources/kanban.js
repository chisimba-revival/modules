(function () {
    'use strict';
    var root = document.querySelector('[data-kanban]');
    if (!root) return;
    var dragged = null;
    var fullscreenButton = root.querySelector('[data-kanban-fullscreen]');

    if (fullscreenButton && root.requestFullscreen) {
        var updateFullscreenButton = function (active) {
            fullscreenButton.setAttribute('aria-pressed', active ? 'true' : 'false');
            fullscreenButton.setAttribute('aria-label', active ? 'Exit full screen' : 'Enter full screen');
            fullscreenButton.setAttribute('title', active ? 'Exit full screen' : 'Enter full screen');
            fullscreenButton.textContent = active ? 'Exit full screen' : 'Full screen';
        };
        fullscreenButton.addEventListener('click', function () {
            if (document.fullscreenElement) document.exitFullscreen().then(function () { updateFullscreenButton(false); });
            else root.requestFullscreen().then(function () { updateFullscreenButton(true); });
        });
        document.addEventListener('fullscreenchange', function () {
            updateFullscreenButton(Boolean(document.fullscreenElement));
        });
    } else if (fullscreenButton) fullscreenButton.hidden = true;

    document.addEventListener('submit', function (event) {
        var message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) event.preventDefault();
        if (!event.defaultPrevented && event.target.matches('[data-task-move]')) {
            event.preventDefault();
            var form = event.target;
            var task = form.closest('.kanban-task');
            var status = form.elements.status.value;
            var column = form.closest('.kanban-board').querySelector('.kanban-column[data-status="' + status + '"]');
            post(form.action, {taskid: form.elements.taskid.value, status: status, sortorder: Date.now()}, function () {
                moveTask(task, column);
            });
        }
    });
    root.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-board-toggle]');
        if (!toggle) return;
        var board = toggle.closest('.kanban-board');
        var collapsed = board.classList.toggle('is-collapsed');
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.textContent = collapsed ? 'Expand' : 'Collapse';
    });
    root.addEventListener('dragstart', function (event) {
        var task = event.target.closest('.kanban-task[draggable="true"]');
        if (!task) return;
        dragged = task;
        task.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });
    root.addEventListener('dragend', function () {
        if (dragged) dragged.classList.remove('is-dragging');
        dragged = null;
        root.querySelectorAll('.is-dragover').forEach(function (element) { element.classList.remove('is-dragover'); });
    });
    root.addEventListener('dragover', function (event) {
        var column = event.target.closest('.kanban-column');
        if (!column || !dragged) return;
        event.preventDefault();
        column.classList.add('is-dragover');
    });
    root.addEventListener('dragleave', function (event) {
        var column = event.target.closest('.kanban-column');
        if (column) column.classList.remove('is-dragover');
    });
    root.addEventListener('drop', function (event) {
        var column = event.target.closest('.kanban-column');
        if (!column || !dragged) return;
        event.preventDefault();
        moveTask(dragged, column);
        column.classList.remove('is-dragover');
        post(root.dataset.moveUrl, {taskid: dragged.dataset.taskId, status: column.dataset.status, sortorder: Date.now()});
    });
    root.addEventListener('change', function (event) {
        if (!event.target.matches('[data-subtask-id]')) return;
        var url = new URL(root.dataset.moveUrl, window.location.href);
        url.searchParams.set('action', 'togglesubtask');
        post(url.toString(), {subtaskid: event.target.dataset.subtaskId, completed: event.target.checked ? '1' : '0'});
    });

    function moveTask(task, column) {
        var oldColumn = task.closest('.kanban-column');
        if (!oldColumn || oldColumn === column) return;
        column.querySelector('.kanban-task-list').appendChild(task);
        var board = column.closest('.kanban-board');
        [oldColumn, column].forEach(function (item) {
            item.querySelector('[data-column-count]').textContent = item.querySelectorAll('.kanban-task').length;
        });
        ['not_started', 'in_progress', 'completed'].forEach(function (status) {
            var count = board.querySelector('.kanban-column[data-status="' + status + '"]').querySelectorAll('.kanban-task').length;
            var label = status === 'not_started' ? ' not started' : (status === 'in_progress' ? ' in progress' : ' completed');
            board.querySelector('[data-board-status="' + status + '"]').textContent = count + label;
        });
        var total = Number(board.dataset.taskTotal) || 0;
        var completed = board.querySelector('.kanban-column[data-status="completed"]').querySelectorAll('.kanban-task').length;
        board.querySelector('[data-board-progress]').textContent = (total ? Math.round(completed / total * 100) : 0) + '% complete';
        refreshMoveControls(task, column.dataset.status);
    }

    function refreshMoveControls(task, status) {
        var actions = task.querySelector('.kanban-task__actions');
        if (!actions) return;
        actions.querySelectorAll('[data-task-move]').forEach(function (form) { form.remove(); });
        var statuses = ['not_started', 'in_progress', 'completed'];
        var current = statuses.indexOf(status);
        [{label: 'right', index: current + 1}, {label: 'left', index: current - 1}].forEach(function (move) {
            if (!statuses[move.index]) return;
            var form = document.createElement('form');
            form.method = 'post';
            form.action = root.dataset.moveUrl;
            form.setAttribute('data-task-move', '');
            [['taskid', task.dataset.taskId], ['status', statuses[move.index]], ['sortorder', Date.now()]].forEach(function (field) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = field[0];
                input.value = field[1];
                form.appendChild(input);
            });
            var button = document.createElement('button');
            button.className = 'button chisimba-button-secondary';
            button.type = 'submit';
            button.textContent = 'Move ' + move.label;
            form.appendChild(button);
            actions.insertBefore(form, actions.firstChild);
        });
    }

    function post(url, data, onSuccess) {
        var body = new URLSearchParams(data);
        body.set('csrf_token', root.dataset.csrf);
        body.set('scope', root.dataset.scope);
        body.set('response', 'json');
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString()})
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.csrfToken) root.dataset.csrf = result.csrfToken;
                if (!result.ok) window.alert(result.message || 'The board could not be updated.');
                else if (onSuccess) onSuccess(result);
            }).catch(function () { window.alert('The board could not be updated. Reload and try again.'); });
    }
}());
