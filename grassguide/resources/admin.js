/* Progressive enhancement: keep entered work on errors; never retry automatically. */
(() => {
    'use strict';
    const labels = document.getElementById('grassguide-labels');
    if (!labels) return;
    // Escaped text and generated <i> elements only; editorial text is never HTML.
    const escapeBotanical = text => text.replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    const italic = text => '<i>'+escapeBotanical(text).replace(/\b(subsp\.|ssp\.|var\.|f\.) /gu,'</i>$1 <i>')+'</i>';
    document.querySelectorAll('[data-botanical-editor]').forEach(editor => {
        const button=editor.querySelector('[data-italic-target]'),field=document.getElementById(button.dataset.italicTarget),preview=editor.querySelector('[data-botanical-preview]');
        const names=JSON.parse(editor.dataset.botanicalNames).sort((a,b)=>b.length-a.length);
        const pattern=new RegExp('(?<![\\p{L}-])('+names.map(n=>n.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')).join('|')+')(?![\\p{L}-])','gu');
        const refresh=()=>{preview.innerHTML=field.value.split(/\*([^*\r\n]+)\*/gu).map((part,i)=>i%2?italic(part):part.split(pattern).map((text,j)=>j%2?italic(text):escapeBotanical(text)).join('')).join('').replace(/\n/g,'<br>');};
        button.hidden=false;field.addEventListener('input',refresh);
        button.addEventListener('click',()=>{
            const start=field.selectionStart,end=field.selectionEnd;
            field.focus();if(start===end)return;
            const text=field.value.slice(start,end),already=text.startsWith('*')&&text.endsWith('*');
            const replacement=already?text.slice(1,-1):'*'+text+'*';
            if(field.value.length+replacement.length-text.length>field.maxLength)return;
            field.setRangeText(replacement,start,end,'select');field.dispatchEvent(new Event('input',{bubbles:true}));
        });
    });
    document.querySelectorAll('[data-copyright-permission]').forEach(section=>{
        const select=section.closest('form').elements.licence;
        const update=()=>{const state=section.closest('form').elements.state;const enabled=select.value==='copyright'&&(!state||state.value==='active');section.hidden=!enabled;section.querySelector('[name="copyright_confirm"]').required=enabled;section.querySelector('[name="copyright_evidence"]').required=enabled;};
        select.addEventListener('change',update);section.closest('form').elements.state?.addEventListener('change',update);update();
    });
    const dirty = new Set();
    document.querySelectorAll('[data-media-all], [data-media-none]').forEach(button => {
        button.hidden = false;
        button.addEventListener('click', () => {
            button.form.querySelectorAll('input[name="selected[]"]').forEach(input => {
                input.checked = button.hasAttribute('data-media-all');
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
        });
    });
    document.querySelectorAll('[data-photo-files]').forEach(input => {
        const previews = input.form.querySelector('[data-photo-previews]');
        let urls = [];
        const clear = input.form.querySelector('[data-clear-photos]');
        clear.hidden = false; clear.disabled = !input.files.length;
        clear.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('change', {bubbles:true}));
            input.focus();
        });
        input.addEventListener('change', () => {
            urls.forEach(url => URL.revokeObjectURL(url)); urls = [];
            previews.replaceChildren();
            clear.disabled = !input.files.length;
            input.form.querySelector('[data-photo-selection-status]').textContent = input.files.length ? labels.dataset.photoSelected.replace('%s', input.files.length) : '';
            [...input.files].forEach((file, index) => {
                const item = document.createElement('article');
                item.className = 'chisimba-form-card chisimba-form-card--wide';
                const label = document.createElement('p');
                label.textContent = `${index + 1}. ${file.name}`;
                item.append(label);
                if (['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    const image = document.createElement('img');
                    const url = URL.createObjectURL(file); urls.push(url);
                    image.src = url; image.alt = file.name; image.width = 240;
                    item.append(image);
                }
                previews.append(item);
            });
        });
    });
    document.querySelectorAll('[data-standard-request]').forEach(button => {
        button.hidden = false;
        button.addEventListener('click', () => {
            const field = button.form.elements.request_body;
            field.value = button.dataset.standardRequest;
            field.dispatchEvent(new Event('input', {bubbles: true}));
            field.focus();
        });
    });
    document.querySelectorAll('[data-grass-dialog]').forEach(button => {
        const dialog = document.getElementById(button.dataset.grassDialog);
        if (!dialog || typeof dialog.showModal !== 'function') return;
        button.hidden = false;
        const fallback = document.getElementById(dialog.id + '-inline');
        if (fallback) fallback.hidden = true;
        button.addEventListener('click', () => dialog.showModal());
        dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
    });
    document.querySelectorAll('[data-grass-form]').forEach(form => {
        const isUpload = new URL(form.action).searchParams.get('action') === 'uploadphotos';
        // File selection can be undone without discarding captions or other edits.
        const uploadState = () => JSON.stringify([...new FormData(form)].filter(([key]) => key !== 'csrf_token').map(([key,value]) =>
            [key, typeof value === 'string' ? value : value.name ? [value.name,value.size,value.lastModified] : null]));
        const originalUpload = isUpload ? uploadState() : null;
        const setStatus = message => form.querySelectorAll('[data-save-status]').forEach(status => { status.textContent = message; });
        if (isUpload) form.addEventListener('invalid', () => setStatus(labels.dataset.uploadInvalid), true);
        const changed = () => {
            if (form.dataset.busy === '1') return;
            if (isUpload && uploadState() === originalUpload) dirty.delete(form);
            else dirty.add(form);
            setStatus(dirty.has(form) ? labels.dataset.unsaved : '');
            if (new URL(form.action).searchParams.get('action') === 'savecontact') {
                document.querySelectorAll('[data-grass-form]').forEach(other => {
                    if (new URL(other.action).searchParams.get('action') !== 'queuepermission') return;
                    other.dataset.draftDirty = '1';
                    other.querySelector('button[type="submit"]').disabled = true;
                    other.querySelector('[data-save-status]').textContent = labels.dataset.saveContactFirst;
                });
            }
            document.querySelectorAll('[data-grass-notice]').forEach(notice => { notice.hidden = true; });
        };
        form.addEventListener('input', changed);
        form.addEventListener('change', changed);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (form.dataset.busy === '1' || form.dataset.draftDirty === '1') return;
            form.dataset.busy = '1';
            const buttons = [...form.querySelectorAll('button[type="submit"]')];
            const body = new FormData(form);
            body.set('ajax', '1');
            const controls = isUpload ? [...form.elements].filter(control => !control.disabled) : buttons;
            const progress = form.querySelector('[data-upload-progress]');
            const buttonLabels = buttons.map(button => button.innerHTML);
            let saved = false;
            controls.forEach(control => { control.disabled = true; });
            form.setAttribute('aria-busy', 'true');
            if (progress) progress.hidden = false;
            if (isUpload) buttons.forEach(button => { button.textContent = labels.dataset.uploadBusy; });
            setStatus(isUpload ? labels.dataset.uploadBusy : labels.dataset.saving);
            try {
                const response = await fetch(form.action, {method: 'POST', body, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
                const data = await response.json();
                if (typeof data.csrf === 'string') form.elements.csrf_token.value = data.csrf;
                if (typeof data.message !== 'string' || typeof data.ok !== 'boolean') throw new Error('Unexpected response');
                setStatus(data.message);
                if (data.ok && response.ok) {
                    saved = true;
                    dirty.delete(form);
                    if (isUpload) form.reset();
                    // Reload only after a confirmed save, including new record links/preview.
                    const destination = new URL(form.action);
                    const action = destination.searchParams.get('action');
                    if (['savecontact', 'queuepermission'].includes(action)) {
                        destination.searchParams.set('action', 'contributors');
                        destination.searchParams.set('contributor', body.get('id'));
                    } else if (['uploadphotos', 'resumenc'].includes(action)) {
                        destination.searchParams.set('action', action === 'uploadphotos' ? 'edit' : 'photos');
                    } else if (['saverights', 'savepolicy'].includes(action)) {
                        destination.searchParams.set('action', action === 'saverights' ? 'rightstask' : 'rights');
                        if (body.has('target')) destination.searchParams.set('target', body.get('target'));
                    } else destination.searchParams.set('action', ['import', 'enrich'].includes(action) ? 'setup' : action === 'savemedia' ? 'media' : 'edit');
                    if (data.record && !['savecontact', 'queuepermission'].includes(action)) destination.searchParams.set('id', data.record.id);
                    destination.searchParams.set(action === 'queuepermission' ? 'queued' : 'saved', '1');
                    window.location.assign(destination.href);
                }
            } catch (_) {
                setStatus(labels.dataset.failed);
            } finally {
                if (!saved) {
                    controls.forEach(control => { control.disabled = false; });
                    buttons.forEach((button, index) => { button.innerHTML = buttonLabels[index]; });
                    form.dataset.busy = '0';
                    form.removeAttribute('aria-busy');
                    if (progress) progress.hidden = true;
                }
            }
        });
    });
    window.addEventListener('beforeunload', event => {
        if (dirty.size) { event.preventDefault(); event.returnValue = ''; }
    });
})();
