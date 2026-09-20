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
    // Counts describe the two independent saves: additions and paper edits.
    function updateCounts() {
        var add = document.querySelector('[data-exam-add-count]');
        if (add) {
            var selected = add.closest('form').querySelectorAll('input[name="selected[]"]:checked:not(:disabled)').length;
            add.querySelector('[data-selected-count]').textContent = selected;
            add.querySelector('[data-add-total]').textContent = Number(add.dataset.saved) + selected;
        }
        var edit = document.querySelector('[data-exam-edit-count]');
        if (edit) {
            var entries = Array.from(edit.closest('form').querySelectorAll('[data-exam-entry]'));
            var kept = 0;
            entries.forEach(function (entry) {
                var keep = entry.querySelector('[data-exam-keep]');
                if (keep.checked) kept++;
                var button = entry.querySelector('[data-exam-remove]');
                button.hidden = false;
                button.classList.toggle('chisimba-button-danger', keep.checked);
                button.classList.toggle('chisimba-button-secondary', !keep.checked);
                if (!button.examLabels) {
                    button.examLabels = [button.querySelector('[data-remove-label]').cloneNode(true), button.querySelector('[data-undo-label]').cloneNode(true)];
                    button.examLabels.forEach(function (label) { label.hidden = false; });
                }
                button.replaceChildren(button.examLabels[keep.checked ? 0 : 1].cloneNode(true));
                var pending = entry.querySelector('[data-removal-pending]');
                if (!pending.dataset.message) pending.dataset.message = pending.textContent;
                pending.textContent = keep.checked ? '' : pending.dataset.message;
                pending.classList.toggle('chisimba-pill', !keep.checked);
                pending.hidden = keep.checked;
                // Removed entries retain their values for Undo, but cannot block saving.
                entry.querySelectorAll('input[type="number"]').forEach(function (field) { field.required = keep.checked; });
            });
            edit.querySelector('[data-keep-count]').textContent = kept;
            edit.querySelector('[data-remove-count]').textContent = entries.length - kept;
        }
    }
    document.addEventListener('change', updateCounts);
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-exam-remove]');
        if (!button) return;
        var keep = button.closest('[data-exam-entry]').querySelector('[data-exam-keep]');
        keep.checked = !keep.checked;
        keep.dispatchEvent(new Event('change', {bubbles:true}));
    });
    window.addEventListener('pageshow', updateCounts);
    updateCounts();
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
        // A jump within this document keeps both forms and their unsaved work.
        var destination = new URL(link.href);
        if (destination.origin === location.origin && destination.pathname === location.pathname && destination.search === location.search && destination.hash) return;
        if (!dirty.size) return;
        if (!window.confirm(forms[0].dataset.unsaved)) { event.preventDefault(); return; }
        dirty.clear();
    });
    window.addEventListener('beforeunload', function (event) {
        if (dirty.size) { event.preventDefault(); event.returnValue = ''; }
    });
}());
