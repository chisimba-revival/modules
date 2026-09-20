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
    var paging = false;
    document.addEventListener('click', async function (event) {
        var link = event.target.closest('a[href]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank') return;
        if (link.closest('[data-exam-pagination]')) {
            event.preventDefault();
            if (paging) return;
            var picker = link.closest('[data-exam-picker]');
            var status = picker.querySelector('[data-page-status]');
            var url = new URL(link.href);
            if (url.origin !== location.origin) return;
            paging = true; picker.setAttribute('aria-busy', 'true'); status.hidden = true;
            var controller = new AbortController();
            var timeout = setTimeout(function () { controller.abort(); }, 15000);
            try {
                var response = await fetch(url, {credentials:'same-origin', cache:'no-store', signal:controller.signal});
                if (!response.ok || response.redirected) throw new Error('page_unavailable');
                var page = new DOMParser().parseFromString(await response.text(), 'text/html');
                var replacement = page.querySelector('[data-exam-picker]');
                if (!replacement) throw new Error('page_invalid');
                replacement.open = true;
                picker.replaceWith(replacement);
                // Only the picker changes: chapter selections and exam edits stay intact.
                document.querySelectorAll('[data-exam-dirty] input[name="page"]').forEach(function (field) {
                    field.value = url.searchParams.get('page') || '1';
                });
                var current = new URL(location.href);
                current.searchParams.set('page', url.searchParams.get('page') || '1');
                history.replaceState(null, '', current);
                var summary = replacement.querySelector('summary');
                summary.focus({preventScroll:true});
            } catch (error) {
                status.hidden = false; status.textContent = picker.dataset.loadError;
            } finally {
                clearTimeout(timeout); paging = false; picker.removeAttribute('aria-busy');
            }
            return;
        }
        if (!dirty.size) return;
        if (!window.confirm(forms[0].dataset.unsaved)) { event.preventDefault(); return; }
        dirty.clear();
    });
    window.addEventListener('beforeunload', function (event) {
        if (dirty.size) { event.preventDefault(); event.returnValue = ''; }
    });
}());
