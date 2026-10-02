(function () {
    'use strict';
    var root = document.querySelector('[data-kanban]');
    if (!root) return;
    var messages = JSON.parse(root.dataset.recoveryMessages || '{}');
    var drafts = new WeakMap();
    function prepareDrafts() {
        if (!window.ChisimbaFormDrafts) return;
        root.querySelectorAll('form[method="post"]:not([data-note-connect])').forEach(function (form) {
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
        // Start with a compact project overview; restored drafts reopen their board.
        setCollapsed(board, true);
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

    var returnParams = new URLSearchParams(window.location.search);
    var returnBoard = returnParams.get('openboard');
    if (returnBoard) {
        var returnedBoard = root.querySelector('.kanban-board[data-board-id="' + CSS.escape(returnBoard) + '"]');
        if (returnedBoard) {
            setCollapsed(returnedBoard, false);
            var returnTask = returnParams.get('opentask');
            if (returnTask) {
                var returnedTask = returnedBoard.querySelector('.kanban-task[data-task-id="' + CSS.escape(returnTask) + '"]');
                if (returnedTask) setTaskCollapsed(returnedTask, false);
            }
            requestAnimationFrame(function () { returnedBoard.scrollIntoView({block:'start'}); });
        }
    }

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

    var focusedBoard = null;
    var focusWasCollapsed = false;
    var focusScroll = 0;
    var hiddenNeighbours = [];
    function exitProjectFocus() {
        if (!focusedBoard) return;
        var board = focusedBoard;
        focusedBoard = null;
        board.classList.remove('chisimba-focus-surface--active');
        document.body.classList.remove('chisimba-focus-open');
        hiddenNeighbours.forEach(function (item) { item.element.inert = item.inert; });
        hiddenNeighbours = [];
        setCollapsed(board, focusWasCollapsed);
        board.querySelector('[data-board-toggle]').disabled = false;
        var button = board.querySelector('[data-board-focus]');
        button.setAttribute('aria-pressed', 'false');
        button.querySelector('[data-board-focus-label]').textContent = button.dataset.enterLabel;
        window.scrollTo(0, focusScroll);
        button.focus({preventScroll:true});
    }
    root.querySelectorAll('[data-board-focus]').forEach(function (button) {
        button.hidden = false;
        button.addEventListener('click', function () {
            if (focusedBoard) { exitProjectFocus(); return; }
            var board = button.closest('.kanban-board');
            focusedBoard = board;
            focusWasCollapsed = board.classList.contains('is-collapsed');
            focusScroll = window.scrollY;
            setCollapsed(board, false);
            // Keep the same DOM and forms, and remove hidden surroundings from keyboard navigation.
            for (var current = board; current.parentElement; current = current.parentElement) {
                Array.from(current.parentElement.children).forEach(function (sibling) {
                    if (sibling === current || sibling.tagName === 'SCRIPT' || sibling.tagName === 'STYLE') return;
                    hiddenNeighbours.push({element:sibling, inert:sibling.inert});
                    sibling.inert = true;
                });
                if (current.parentElement === document.body) break;
            }
            board.classList.add('chisimba-focus-surface--active');
            document.body.classList.add('chisimba-focus-open');
            board.querySelector('[data-board-toggle]').disabled = true;
            button.setAttribute('aria-pressed', 'true');
            button.querySelector('[data-board-focus-label]').textContent = button.dataset.exitLabel;
            button.focus({preventScroll:true});
        });
    });
    document.addEventListener('keydown', function (event) {
        // Let a modal editor handle its own Escape before leaving the project.
        if (event.key === 'Escape' && focusedBoard && !document.querySelector('dialog[open]')) {
            event.preventDefault();
            exitProjectFocus();
        }
    });

    document.addEventListener('submit', function (event) {
        if (!root.contains(event.target)) return;
        var message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) event.preventDefault();
        if (!event.defaultPrevented && event.target.matches('[data-task-create]')) {
            event.preventDefault();
            createTask(event.target);
        }
        if (!event.defaultPrevented && event.target.matches('[data-subtask-create]')) {
            event.preventDefault();
            createSubtask(event.target);
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
        if (!event.defaultPrevented && event.target.matches('[data-task-delete]')) {
            event.preventDefault();
            deleteTask(event.target);
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
        if (!event.defaultPrevented && event.target.matches('[data-public-link-form]')) {
            event.preventDefault();
            savePublicLink(event.target);
        }
        if (!event.defaultPrevented && event.target.matches('[data-note-connect]')) {
            event.preventDefault();
            connectNote(event.target);
        }
        if (!event.defaultPrevented && event.target.method.toLowerCase() === 'post') {
            event.preventDefault();
            saveForm(event.target);
        }
    });
    root.addEventListener('click', function (event) {
        var noteLink = event.target.closest('[data-note-open]');
        if (noteLink) {
            event.preventDefault();
            openNote(noteLink);
            return;
        }
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

    });

    async function openNote(link) {
        var dialog = document.getElementById('kanban-note-window');
        var content = dialog && dialog.querySelector('[data-note-modal-content]');
        if (!dialog || !content || typeof dialog.showModal !== 'function') {
            window.location.assign(link.href);
            return;
        }
        dialog.querySelector('.chisimba-ui-window__title').textContent = link.textContent.trim();
        content.innerHTML = '<p class="chisimba-muted" role="status">Loading note…</p>';
        dialog.showModal();
        try {
            var response = await fetch(link.dataset.editorUrl, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            var data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'The note could not be opened.');
            dialog.querySelector('.chisimba-ui-window__title').textContent = data.title;
            content.innerHTML = data.html;
            bindModalEditor(dialog, link);
        } catch (error) {
            content.innerHTML = '';
            var message = document.createElement('p');
            message.className = 'chisimba-notice chisimba-notice--error';
            message.setAttribute('role', 'alert');
            message.textContent = error.message;
            var fallback = document.createElement('a');
            fallback.className = 'button chisimba-button-secondary';
            fallback.href = link.href;
            fallback.textContent = 'Open note page';
            content.append(message, fallback);
        }
    }

    function bindModalEditor(dialog, openedLink) {
        var form = dialog.querySelector('[data-modal-note-save]');
        if (!form) return;
        var editor = form.querySelector('[data-modal-note-editor]');
        var body = form.querySelector('[data-modal-note-body]');
        var feedback = form.querySelector('[data-modal-note-feedback]');
        var dirty = false;
        var sync = function () { body.value = editor.innerHTML; };
        editor.addEventListener('input', function () { dirty = true; sync(); feedback.textContent = 'Unsaved changes'; });
        form.querySelectorAll('[data-command]').forEach(function (button) {
            button.addEventListener('mousedown', function (event) { event.preventDefault(); });
            button.addEventListener('click', function () {
                editor.focus();
                var value = button.dataset.value || null;
                if (button.dataset.command === 'createLink') { value = window.prompt('Link address'); if (!value) return; }
                document.execCommand(button.dataset.command, false, value);
                dirty = true; sync(); feedback.textContent = 'Unsaved changes';
            });
        });
        sync();
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            sync();
            var button = form.querySelector('[type="submit"]');
            button.disabled = true; feedback.textContent = 'Saving…';
            try {
                var response = await fetch(form.action, {method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
                var data = await response.json();
                if (data.csrfToken) form.elements.csrf_token.value = data.csrfToken;
                if (!response.ok || !data.ok) throw new Error(data.message || 'The note could not be saved. Your work is kept.');
                dirty = false; feedback.textContent = data.message; feedback.className = 'success';
                dialog.querySelector('.chisimba-ui-window__title').textContent = data.title;
                root.querySelectorAll('[data-note-open][data-note-id="' + CSS.escape(form.dataset.noteId) + '"]').forEach(function (item) { item.textContent = data.title; });
            } catch (error) { feedback.textContent = error.message; feedback.className = 'error'; }
            finally { button.disabled = false; }
        });
        var confirmClose = function (event) {
            if (!dirty || window.confirm('Close this note without saving your changes?')) return;
            event.preventDefault(); event.stopImmediatePropagation();
        };
        var closeButton = dialog.querySelector('[data-ui-close]');
        dialog.addEventListener('cancel', confirmClose);
        closeButton.addEventListener('click', confirmClose, true);
        openedLink.dataset.noteActive = 'true';
        dialog.addEventListener('close', function () { delete openedLink.dataset.noteActive;dialog.removeEventListener('cancel',confirmClose);closeButton.removeEventListener('click',confirmClose,true); }, {once:true});
    }
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

    function createSubtask(form) {
        if (form.dataset.saving === 'true') return;
        var container = form.closest('.kanban-subtasks');
        var feedback = form.querySelector('[data-subtask-feedback]');
        var controls = Array.from(form.querySelectorAll('input, button'));
        var disabled = controls.map(function (control) { return control.disabled; });
        var data = new URLSearchParams(new FormData(form));
        var title = form.elements.title.value;
        form.dataset.saving = 'true';
        form.setAttribute('aria-busy', 'true');
        controls.forEach(function (control) { control.disabled = true; });
        feedback.textContent = 'Adding subtask…';
        post(form.action, data, function (result) {
            if (!result.subtask || !result.subtask.id) throw new Error('Missing saved subtask');
            var label = document.createElement('label');
            label.className = 'kanban-subtask';
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.dataset.subtaskId = result.subtask.id;
            checkbox.checked = Boolean(result.subtask.completed);
            var text = document.createElement('span');
            text.textContent = result.subtask.title;
            label.append(checkbox, text);
            container.insertBefore(label, form);
            form.reset();
            if (drafts.has(form)) drafts.get(form).reset();
            feedback.textContent = 'Added “' + title + '”.';
        }, function (message) {
            feedback.textContent = message;
        }).finally(function () {
            controls.forEach(function (control, index) { control.disabled = disabled[index]; });
            delete form.dataset.saving;
            form.removeAttribute('aria-busy');
            form.elements.title.focus({preventScroll: true});
        });
    }

    function deleteTask(form) {
        if (form.dataset.saving === 'true') return;
        var task = form.closest('.kanban-task');
        var board = form.closest('.kanban-board');
        var button = form.querySelector('button[type="submit"]');
        var taskId = form.elements.taskid.value;
        form.dataset.saving = 'true';
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        post(form.action, {taskid: taskId, boardid: form.elements.boardid.value}, function (result) {
            var column = task.closest('.kanban-column');
            task.remove();
            try { sessionStorage.removeItem('kanban:task-collapsed:' + taskId); }
            catch (error) { /* Deletion remains complete without storage. */ }
            refreshBoardCounts(board);
            var feedback = board.querySelector('[data-task-feedback]');
            if (feedback) feedback.textContent = result.message;
            var next = column.querySelector('.kanban-task [data-task-toggle]') || board.querySelector('[data-task-create] input[name="title"]');
            if (next) next.focus({preventScroll: true});
        }, function (message) {
            window.alert(message);
            button.disabled = false;
            delete form.dataset.saving;
            form.removeAttribute('aria-busy');
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
            // Refresh the saved text only: keep the editor, other drafts, scroll and project focus.
            var task = form.closest('.kanban-task');
            if (task && new URL(form.action, location.href).searchParams.get('action') === 'savetask') {
                var fragment = document.createElement('template');
                fragment.innerHTML = result.taskHtml;
                var saved = fragment.content.querySelector('.kanban-task');
                if (!saved || saved.dataset.taskId !== task.dataset.taskId) throw new Error('Missing saved task');
                var summary = task.querySelector('[data-task-summary]');
                var notesOpen = summary.querySelector('details')?.open || false;
                summary.replaceChildren(...saved.querySelector('[data-task-summary]').childNodes);
                if (summary.querySelector('details')) summary.querySelector('details').open = notesOpen;
                task.querySelector('h4').textContent = saved.querySelector('h4').textContent;
                setTaskCollapsed(task, task.classList.contains('is-collapsed'));
                return;
            }
            // Only a confirmed save may navigate. Other forms retain their recovery copies.
            var destination = new URL(window.location.href);
            if (root.dataset.scope === 'context') { destination.searchParams.set('scope','context'); destination.searchParams.set('scopeid',root.dataset.scopeId); }
            window.location.assign(destination.toString());
        }, function (message) { feedback.textContent = message; }).finally(function () {
            controls.forEach(function (control, index) { control.disabled = disabled[index]; });
            delete form.dataset.saving; form.removeAttribute('aria-busy');
        });
    }

    function savePublicLink(form) {
        if (form.dataset.saving === 'true') return;
        // Disabled controls are omitted by FormData, including the board and CSRF token.
        var payload = new URLSearchParams(new FormData(form));
        var controls = Array.from(form.querySelectorAll('input, button'));
        var disabled = controls.map(function (control) { return control.disabled; });
        var feedback = form.querySelector('[data-public-link-feedback]');
        var enabled = form.elements.publiclink.checked;
        form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        controls.forEach(function (control) { control.disabled = true; });
        feedback.textContent = enabled ? 'Creating public view link…' : 'Disabling public view link…';
        post(form.action, payload, function (result) {
            var urlContainer = form.querySelector('[data-public-link-url-container]');
            var url = form.querySelector('[data-public-link-url]');
            var submit = form.querySelector('[data-public-link-submit]');
            if (result.publicUrl) {
                url.value = result.publicUrl;
                urlContainer.hidden = false;
                submit.textContent = 'Replace public view link';
            } else {
                url.value = '';
                urlContainer.hidden = true;
                submit.textContent = 'Create public view link';
            }
            feedback.textContent = result.message;
        }, function (message) { feedback.textContent = message; }).finally(function () {
            controls.forEach(function (control, index) { control.disabled = disabled[index]; });
            delete form.dataset.saving; form.removeAttribute('aria-busy');
        });
    }

    function connectNote(form) {
        if (form.dataset.saving === 'true') return;
        var panel = form.closest('[data-note-connections]');
        var feedback = panel.querySelector('[data-note-feedback]');
        var controls = Array.from(form.querySelectorAll('input, select, button'));
        var disabled = controls.map(function (control) { return control.disabled; });
        var payload = new URLSearchParams(new FormData(form));
        form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        controls.forEach(function (control) { control.disabled = true; });
        feedback.textContent = 'Connecting note…';
        post(form.action, payload, function (result) {
            var list = panel.querySelector('[data-note-links]');
            if (!list.querySelector('a[href="' + CSS.escape(result.note.url) + '"]')) {
                var item = document.createElement('li');
                var link = document.createElement('a');
                link.href = result.note.url; link.textContent = result.note.title;
                link.dataset.noteOpen = ''; link.dataset.noteId = result.note.id; link.dataset.editorUrl = result.note.editorUrl;
                item.appendChild(link); list.appendChild(item);
            }
            panel.querySelector('[data-note-empty]')?.remove();
            var count = panel.querySelectorAll('[data-note-links] li').length + Number(panel.dataset.localNote || 0);
            var badge = panel.querySelector('[data-note-count]');
            if (!badge) { badge=document.createElement('span');badge.className='chisimba-pill';badge.dataset.noteCount='';panel.querySelector('summary').appendChild(badge); }
            badge.textContent = count;
            root.querySelectorAll('[data-note-connect] select[name="noteid"]').forEach(function (select) {
                if (Array.from(select.options).some(function (option) { return option.value === result.note.id; })) return;
                var option=document.createElement('option');option.value=result.note.id;option.textContent=result.note.title;select.appendChild(option);
                select.closest('form').querySelector('button[type="submit"]').disabled=false;
            });
            form.reset();
            if (drafts.has(form)) drafts.get(form).reset();
            feedback.textContent = result.message;
        }, function (message) { feedback.textContent = message; }).finally(function () {
            controls.forEach(function (control,index) { control.disabled=disabled[index]; });
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
