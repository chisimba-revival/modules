(function () {
    'use strict';
    var root = document.querySelector('[data-kanban]');
    if (!root) return;
    var messages = JSON.parse(root.dataset.recoveryMessages || '{}');
    var drafts = new WeakMap();
    function prepareDrafts() {
        if (!window.ChisimbaFormDrafts) return;
        root.querySelectorAll('form[method="post"]').forEach(function (form) {
            if (drafts.has(form)) return;
            var names = ['title', 'description', 'notes', 'grants'].filter(function (name) { return form.elements.namedItem(name); });
            if (!names.length) return;
            var action = new URL(form.action, location.href).searchParams.get('action');
            var identity = ['boardid', 'taskid'].map(function (name) { return form.elements.namedItem(name)?.value || ''; });
            drafts.set(form, window.ChisimbaFormDrafts.attach(form, {
                key: JSON.stringify([location.pathname, root.dataset.actor, root.dataset.draftScope, action, identity]),
                fields: names, messages: messages,
                onRestore: function () {
                    var board = form.closest('.kanban-board');
                    if (board) setCollapsed(board, false);
                    var task = form.closest('.kanban-task');
                    if (task) setTaskCollapsed(task, false);
                }
            }));
        });
    }
    var dragged = null;
    var pendingPost = Promise.resolve();
    root.querySelectorAll('.kanban-board').forEach(function (board) {
        try { setCollapsed(board, sessionStorage.getItem('kanban:collapsed:' + board.dataset.boardId) === '1'); }
        catch (error) { /* Storage may be disabled; in-page state still works. */ }
    });

    function setCollapsed(board, collapsed) {
        board.classList.toggle('is-collapsed', collapsed);
        var toggle = board.querySelector('[data-board-toggle]');
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.querySelector('[data-board-toggle-label]').textContent = collapsed ? 'Expand' : 'Collapse';
    }
    root.querySelectorAll('.kanban-task').forEach(function (task) {
        try { setTaskCollapsed(task, sessionStorage.getItem('kanban:task-collapsed:' + task.dataset.taskId) === '1'); }
        catch (error) { /* In-page collapse still works without storage. */ }
    });

    function setTaskCollapsed(task, collapsed) {
        task.classList.toggle('is-collapsed', collapsed);
        var toggle = task.querySelector('[data-task-toggle]');
        var label = collapsed ? 'Expand' : 'Collapse';
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.setAttribute('aria-label', label + ' task: ' + task.querySelector('h4').textContent);
        toggle.textContent = label;
    }
    prepareDrafts();
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
        if (!root.contains(event.target)) return;
        var message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) event.preventDefault();
        if (!event.defaultPrevented && event.target.matches('[data-task-create]')) {
            event.preventDefault();
            createTask(event.target);
        }
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
        if (!event.defaultPrevented && event.target.matches('[data-board-reorder]')) {
            event.preventDefault();
            var reorderForm = event.target;
            var board = reorderForm.closest('.kanban-board');
            var direction = reorderForm.elements.direction.value;
            post(reorderForm.action, {boardid: reorderForm.elements.boardid.value, direction: direction}, function () {
                moveBoard(board, direction);
            });
        }
        if (!event.defaultPrevented && event.target.method.toLowerCase() === 'post') {
            event.preventDefault();
            saveForm(event.target);
        }
    });
    root.addEventListener('click', function (event) {
        var taskToggle = event.target.closest('[data-task-toggle]');
        if (taskToggle) {
            var task = taskToggle.closest('.kanban-task');
            var taskCollapsed = !task.classList.contains('is-collapsed');
            setTaskCollapsed(task, taskCollapsed);
            try { sessionStorage.setItem('kanban:task-collapsed:' + task.dataset.taskId, taskCollapsed ? '1' : '0'); }
            catch (error) { /* Keep the control usable without storage. */ }
            return;
        }
        var toggle = event.target.closest('[data-board-toggle]');
        if (!toggle) return;
        var board = toggle.closest('.kanban-board');
        var collapsed = !board.classList.contains('is-collapsed');
        setCollapsed(board, collapsed);
        try { sessionStorage.setItem('kanban:collapsed:' + board.dataset.boardId, collapsed ? '1' : '0'); }
        catch (error) { /* Keep the control usable without storage. */ }
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
        var task = dragged;
        column.classList.remove('is-dragover');
        post(root.dataset.moveUrl, {taskid: task.dataset.taskId, status: column.dataset.status, sortorder: Date.now()}, function () { moveTask(task, column); });
    });
    root.addEventListener('change', function (event) {
        if (!event.target.matches('[data-subtask-id]')) return;
        var url = new URL(root.dataset.moveUrl, window.location.href);
        url.searchParams.set('action', 'togglesubtask');
        var checkbox = event.target;
        checkbox.disabled = true;
        post(url.toString(), {subtaskid: checkbox.dataset.subtaskId, completed: checkbox.checked ? '1' : '0'}, null, function (message) { checkbox.checked = !checkbox.checked; window.alert(message); }).finally(function () { checkbox.disabled = false; });
    });

    function createTask(form) {
        if (form.dataset.saving === 'true') return;
        var board = form.closest('.kanban-board');
        var feedback = form.querySelector('[data-task-feedback]');
        var fields = Array.from(form.querySelectorAll('input, textarea, button'));
        var data = new URLSearchParams(new FormData(form));
        var title = form.elements.title.value;
        form.dataset.saving = 'true';
        form.setAttribute('aria-busy', 'true');
        fields.forEach(function (field) { field.disabled = true; });
        feedback.textContent = 'Adding task…';
        post(form.action, data, function (result) {
            var fragment = document.createElement('template');
            fragment.innerHTML = result.taskHtml;
            var task = fragment.content.querySelector('.kanban-task');
            if (!task) throw new Error('Missing saved task');
            board.querySelector('.kanban-column[data-status="not_started"] .kanban-task-list').appendChild(task);
            refreshBoardCounts(board);
            form.reset();
            if (drafts.has(form)) drafts.get(form).reset();
            prepareDrafts();
            refreshTokens();
            feedback.textContent = 'Added “' + title + '”. Ready for the next task.';
        }, function (message) {
            feedback.textContent = message;
        }).finally(function () {
            fields.forEach(function (field) { field.disabled = false; });
            delete form.dataset.saving;
            form.removeAttribute('aria-busy');
            // Do not pull focus away from another project the user has opened.
            if (document.activeElement === document.body || form.contains(document.activeElement)) {
                form.elements.title.focus({preventScroll: true});
            }
        });
    }

    function saveForm(form) {
        if (form.dataset.saving === 'true') return;
        var draft = drafts.get(form);
        if (draft) draft.persist();
        var snapshot = draft ? draft.snapshot() : null;
        var data = new URLSearchParams(new FormData(form));
        var feedback = form.querySelector('[data-save-feedback]');
        if (!feedback) {
            feedback = document.createElement('p'); feedback.dataset.saveFeedback = '';
            feedback.setAttribute('role', 'status'); form.appendChild(feedback);
        }
        var controls = Array.from(form.querySelectorAll('input, textarea, select, button'));
        var disabled = controls.map(function (control) { return control.disabled; });
        form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        controls.forEach(function (control) { control.disabled = true; });
        feedback.textContent = messages.saving;
        post(form.action, data, function (result) {
            feedback.textContent = result.message;
            if (draft) draft.saved(snapshot);
            // Only a confirmed save may navigate. Other forms retain their recovery copies.
            var destination = new URL(window.location.href);
            if (root.dataset.scope === 'context') { destination.searchParams.set('scope','context'); destination.searchParams.set('scopeid',root.dataset.scopeId); }
            window.location.assign(destination.toString());
        }, function (message) { feedback.textContent = message; }).finally(function () {
            controls.forEach(function (control, index) { control.disabled = disabled[index]; });
            delete form.dataset.saving; form.removeAttribute('aria-busy');
        });
    }

    function refreshBoardCounts(board) {
        var total = board.querySelectorAll('.kanban-task').length;
        board.dataset.taskTotal = total;
        board.classList.toggle('kanban-board--empty', total === 0);
        board.querySelector('[data-board-total]').textContent = total + (total === 1 ? ' task' : ' tasks');
        ['not_started', 'in_progress', 'completed'].forEach(function (status) {
            var column = board.querySelector('.kanban-column[data-status="' + status + '"]');
            var count = column.querySelectorAll('.kanban-task').length;
            column.querySelector('[data-column-count]').textContent = count;
            var label = status === 'not_started' ? ' not started' : (status === 'in_progress' ? ' in progress' : ' completed');
            board.querySelector('[data-board-status="' + status + '"]').textContent = count + label;
        });
        var completed = board.querySelector('.kanban-column[data-status="completed"]').querySelectorAll('.kanban-task').length;
        var percentage = total ? Math.round(completed / total * 100) : 0;
        board.querySelector('[data-board-progress]').textContent = percentage + '% complete';
        board.querySelector('[data-board-progress-label]').textContent = percentage + '%';
        board.querySelector('progress').value = percentage;
    }

    function moveTask(task, column) {
        var oldColumn = task.closest('.kanban-column');
        if (!oldColumn || oldColumn === column) return;
        column.querySelector('.kanban-task-list').appendChild(task);
        refreshBoardCounts(oldColumn.closest('.kanban-board'));
        refreshBoardCounts(column.closest('.kanban-board'));
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

    function boardsInScope(board) {
        return Array.from(root.querySelectorAll('.kanban-board')).filter(function (item) {
            return item.dataset.boardScopeType === board.dataset.boardScopeType && item.dataset.boardScopeId === board.dataset.boardScopeId;
        });
    }

    function moveBoard(board, direction) {
        var boards = boardsInScope(board);
        var position = boards.indexOf(board);
        var target = position + (direction === 'up' ? -1 : 1);
        if (position < 0 || !boards[target]) return;
        if (direction === 'up') board.parentNode.insertBefore(board, boards[target]);
        else board.parentNode.insertBefore(boards[target], board);
        refreshBoardOrderControls(board);
    }

    function refreshBoardOrderControls(board) {
        var boards = boardsInScope(board);
        boards.forEach(function (item, index) {
            item.querySelectorAll('[data-board-reorder]').forEach(function (form) {
                var button = form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = (form.elements.direction.value === 'up' && index === 0) || (form.elements.direction.value === 'down' && index === boards.length - 1);
            });
        });
    }

    function refreshTokens() {
        root.querySelectorAll('input[name="csrf_token"]').forEach(function (input) {
            input.value = root.dataset.csrf;
        });
    }

    async function checkedJson(response) {
        if (response.redirected || !(response.headers.get('Content-Type') || '').includes('application/json')) {
            throw new Error('not-json');
        }
        var result = await response.json();
        if (!result || typeof result.ok !== 'boolean' || (result.ok && !response.ok)) throw new Error('invalid-response');
        return result;
    }

    function post(url, data, onSuccess, onError) {
        // Fresh tokens prevent stale tabs from submitting evicted single-use tokens.
        // Serialize mutations, and never replay a write whose outcome is uncertain.
        pendingPost = pendingPost.then(async function () {
            var attempted = false;
            try {
                var token = await checkedJson(await fetch(root.dataset.tokenUrl, {
                    method: 'POST', credentials: 'same-origin', signal: AbortSignal.timeout(30000),
                    headers: {'X-Chisimba-Form': 'kanban', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: new URLSearchParams({actor: root.dataset.actor}).toString()
                }));
                if (!token.ok || !token.csrfToken) {
                    (onError || window.alert)(token.message || messages.signin); return;
                }
                root.dataset.csrf = token.csrfToken;
                refreshTokens();
                var body = new URLSearchParams(data);
                body.set('csrf_token', root.dataset.csrf);
                body.set('scope', root.dataset.scope);
                body.set('scopeid', root.dataset.scopeId);
                body.set('actor', root.dataset.actor);
                body.set('response', 'json');
                attempted = true;
                var result = await checkedJson(await fetch(url, {
                    method: 'POST', credentials: 'same-origin', signal: AbortSignal.timeout(30000),
                    headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString()
                }));
                if (result.csrfToken) root.dataset.csrf = result.csrfToken;
                refreshTokens();
                if (!result.ok) (onError || window.alert)(result.message || messages.uncertain);
                else if (onSuccess) onSuccess(result);
            } catch (_) { (onError || window.alert)(attempted ? messages.uncertain : messages.signin); }
        });
        return pendingPost;
    }
}());
