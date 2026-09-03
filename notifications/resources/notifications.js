/** Shared toolbar and centre client. @author Derek Keats */
(function () {
    'use strict';
    document.querySelectorAll('[data-notifications-centre], [data-notifications-toolbar]').forEach(function (root) {
        if (root.dataset.notificationsReady) return;
        root.dataset.notificationsReady = '1';
        var preview = root.hasAttribute('data-notifications-toolbar');
        var list = root.querySelector('[data-notification-list]');
        var count = root.querySelector('[data-notification-count]');
        var error = root.querySelector('[data-notification-error]');
        var more = root.querySelector('[data-load-more]');
        var unread = root.querySelector('[data-unread-only]');
        var cursor = null, loading = false, marking = false, counting = false;
        function esc(value) {
            var element = document.createElement('div');
            element.textContent = value == null ? '' : String(value);
            return element.innerHTML;
        }
        function safeTarget(value) {
            try {
                var url = new URL(value, location.href);
                return /^https?:$/.test(url.protocol) && url.origin === location.origin ? url.href : '';
            } catch (_) { return ''; }
        }
        async function request(url, options) {
            var response = await fetch(url, options || {credentials:'same-origin', headers:{Accept:'application/json'}});
            var body = await response.json();
            if (body.csrfToken) root.dataset.csrf = body.csrfToken;
            if (!response.ok || !body.ok) throw new Error(body.error && body.error.code || 'request_failed');
            return body;
        }
        function fail(message) { error.hidden = false; error.textContent = message; }
        async function refreshCount() {
            if (counting) return;
            counting = true;
            try {
                var body = await request(root.dataset.countUrl);
                count.textContent = body.unreadCount > 99 ? '99+' : body.unreadCount;
                count.hidden = preview && body.unreadCount === 0;
                if (preview) root.querySelector('summary').setAttribute('aria-label', 'Notifications, ' + body.unreadCount + ' unread');
            } catch (_) {
                count.hidden = true;
                if (preview) root.querySelector('summary').setAttribute('aria-label', 'Notifications, count unavailable');
            } finally { counting = false; }
        }
        async function load(append) {
            if (loading) return;
            loading = true;
            list.setAttribute('aria-busy', 'true');
            try {
                var url = new URL(root.dataset.feedUrl, location.href);
                url.searchParams.set('limit', preview ? '5' : '25');
                if (unread && unread.checked) url.searchParams.set('unread', '1');
                if (append && cursor) url.searchParams.set('cursor', cursor);
                var body = await request(url.href);
                error.hidden = true;
                if (!append) list.innerHTML = '';
                body.items.forEach(function (item) {
                    var article = document.createElement('article');
                    article.className = 'chisimba-notification chisimba-notification--info' + (item.state === 'unread' ? ' is-unread' : '');
                    article.dataset.id = item.id;
                    var target = item.targetUrl ? safeTarget(item.targetUrl) : '';
                    article.innerHTML = '<div class="chisimba-notification__content"><h2>' + esc(item.title) + '</h2><p>' + esc(item.summary) + '</p><p><small>' + esc(item.createdAt) + '</small></p><div class="notification-actions">' + (target ? '<a href="' + esc(target) + '">Open update</a>' : '') + (item.state === 'unread' ? '<button type="button" data-mark-read>Mark read</button>' : '<small>Read</small>') + '</div></div>';
                    list.appendChild(article);
                });
                if (!body.items.length && !append) list.innerHTML = '<p class="notification-empty">' + (unread && unread.checked ? 'You’re all caught up. No unread updates.' : 'No updates yet. New course and discussion notifications will appear here.') + '</p>';
                cursor = body.page.nextCursor;
                if (more) more.hidden = !body.page.hasMore;
                refreshCount();
            } catch (_) {
                if (!append && !list.querySelector('article')) list.innerHTML = '';
                fail('Updates could not be loaded. Select Refresh to try again.');
            } finally { loading = false; list.removeAttribute('aria-busy'); }
        }
        list.addEventListener('click', async function (event) {
            var button = event.target.closest('[data-mark-read]');
            if (!button || marking) return;
            marking = true;
            button.disabled = true;
            try {
                await request(root.dataset.readUrl, {
                    method:'POST', credentials:'same-origin',
                    headers:{'Content-Type':'application/x-www-form-urlencoded', Accept:'application/json'},
                    body:new URLSearchParams({notification_id:button.closest('[data-id]').dataset.id, csrf_token:root.dataset.csrf}).toString()
                });
                document.dispatchEvent(new Event('notifications-changed'));
            } catch (failure) {
                fail(failure.message === 'invalid_csrf' ? 'Your security token was refreshed. Please select Mark read again.' : 'This update could not be marked read. Please try again.');
            } finally { marking = false; button.disabled = false; }
        });
        root.querySelector('[data-refresh]').addEventListener('click', function () { load(false); });
        if (unread) unread.addEventListener('change', function () { load(false); });
        if (more) more.addEventListener('click', function () { load(true); });
        document.addEventListener('notifications-changed', function () {
            refreshCount();
            if (!preview || root.open) load(false);
        });
        if (preview) {
            root.querySelector('[data-close]').addEventListener('click', function () { root.open = false; root.querySelector('summary').focus(); });
            root.addEventListener('toggle', function () { if (root.open) load(false); });
            document.addEventListener('click', function (event) { if (!root.contains(event.target)) root.open = false; });
            root.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') { root.open = false; root.querySelector('summary').focus(); }
            });
            function check() { if (!document.hidden) refreshCount(); }
            window.addEventListener('focus', check);
            document.addEventListener('visibilitychange', check);
            setInterval(check, 60000);
            refreshCount();
        } else load(false);
    });
}());
