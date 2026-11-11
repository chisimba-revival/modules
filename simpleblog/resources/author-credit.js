/* Explicit biography selection; typing always retains an independent plain-text credit. */
(function () {
    'use strict';
    const box = document.querySelector('[data-author-picker]');
    if (!box) return;
    const field = box.querySelector('[name="author_credit"]');
    const kind = box.querySelector('[name="author_kind"]');
    const id = box.querySelector('[name="author_id"]');
    const status = box.querySelector('[role="status"]');
    const results = box.querySelector('[data-author-results]');
    const options = box.querySelector('[data-author-options]');
    const unlink = box.querySelector('[data-author-unlink]');
    let timer, request, generation = 0;
    function cancel() {
        generation++;
        clearTimeout(timer);
        if (request) request.abort();
        options.replaceChildren();
        results.hidden = true;
    }
    function plain() {
        kind.value = ''; id.value = ''; unlink.hidden = true;
        status.textContent = field.value.trim() ? box.dataset.plain : box.dataset.default;
    }
    unlink.hidden = !id.value;
    unlink.addEventListener('click', () => { cancel(); plain(); field.focus(); });
    box.addEventListener('keydown', event => {
        if (event.key === 'Escape') { cancel(); field.focus(); status.textContent = id.value ? box.dataset.linked : (field.value.trim() ? box.dataset.plain : box.dataset.default); }
    });
    field.addEventListener('input', () => {
        cancel(); plain();
        const query = field.value.trim(), current = generation;
        if (query.length < 2 || query.length > 120) return;
        timer = setTimeout(async () => {
            request = new AbortController();
            status.textContent = box.dataset.searching;
            try {
                const url = new URL(box.dataset.endpoint, location.href);
                url.searchParams.set('query', query);
                const response = await fetch(url, {signal:request.signal, credentials:'same-origin', headers:{Accept:'application/json'}});
                if (!response.ok || response.redirected || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Unavailable');
                const people = await response.json();
                if (current !== generation) return;
                if (!Array.isArray(people)) throw new Error('Invalid response');
                for (const person of people) {
                    if (!['user','speaker'].includes(person.kind) || typeof person.name !== 'string' || typeof person.id !== 'string') continue;
                    const item = document.createElement('li'), button = document.createElement('button');
                    button.type = 'button'; button.className = 'button chisimba-button-secondary';
                    button.textContent = person.name + ' — ' + box.dataset[person.kind];
                    button.addEventListener('click', () => {
                        cancel(); field.value = person.name; kind.value = person.kind; id.value = person.id;
                        unlink.hidden = false; status.textContent = box.dataset.linked; field.focus();
                        field.dispatchEvent(new Event('change', {bubbles:true}));
                    });
                    item.appendChild(button); options.appendChild(item);
                }
                results.hidden = options.children.length === 0;
                status.textContent = results.hidden ? box.dataset.no_matches : box.dataset.plain;
            } catch (error) {
                if (current === generation && error.name !== 'AbortError') status.textContent = box.dataset.search_failed;
            }
        }, 250);
    });
}());
