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
        column.querySelector('.kanban-task-list').appendChild(dragged);
        column.classList.remove('is-dragover');
        post(root.dataset.moveUrl, {taskid: dragged.dataset.taskId, status: column.dataset.status, sortorder: Date.now()});
    });
    root.addEventListener('change', function (event) {
        if (!event.target.matches('[data-subtask-id]')) return;
        var url = new URL(root.dataset.moveUrl, window.location.href);
        url.searchParams.set('action', 'togglesubtask');
        post(url.toString(), {subtaskid: event.target.dataset.subtaskId, completed: event.target.checked ? '1' : '0'});
    });

    function post(url, data) {
        var body = new URLSearchParams(data);
        body.set('csrf_token', root.dataset.csrf);
        body.set('scope', root.dataset.scope);
        body.set('response', 'json');
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString()})
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.csrfToken) root.dataset.csrf = result.csrfToken;
                if (!result.ok) window.alert(result.message || 'The board could not be updated.');
            }).catch(function () { window.alert('The board could not be updated. Reload and try again.'); });
    }
}());
