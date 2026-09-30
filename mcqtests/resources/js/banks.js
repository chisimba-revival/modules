/** Preserve explicit selections across chapter filters, without storing question content. */
(() => {
    'use strict';
    const root = document.querySelector('[data-bank-workspace]');
    const form = root?.querySelector('[data-bank-operation="pull"]');
    if (!root || !form) return;
    const key = `chisimba-bank:${root.dataset.bankOwner}:${root.dataset.bankId}`;
    let selected = new Set();
    try {
        if (root.dataset.bankSaved === '1') sessionStorage.removeItem(key);
        selected = new Set(JSON.parse(sessionStorage.getItem(key) || '[]'));
    } catch (_) { /* Native form selection remains available without storage. */ }
    const boxes = [...form.querySelectorAll('input[name="selected[]"]')];
    boxes.forEach(box => { if (box.checked) selected.add(box.value); box.checked = selected.has(box.value); });
    const status = form.querySelector('[data-bank-count]');
    const save = () => {
        boxes.forEach(box => box.checked ? selected.add(box.value) : selected.delete(box.value));
        try { sessionStorage.setItem(key, JSON.stringify([...selected])); } catch (_) { }
        status.textContent = status.dataset.label.replace('%d', String(selected.size));
    };
    boxes.forEach(box => box.addEventListener('change', save));
    const all = form.querySelector('[data-bank-select-all]');
    const clear = form.querySelector('[data-bank-clear]');
    all.hidden = false; clear.hidden = false;
    all.addEventListener('click', () => { boxes.forEach(box => { box.checked = true; }); save(); });
    clear.addEventListener('click', () => { selected.clear(); boxes.forEach(box => { box.checked = false; }); save(); });
    form.addEventListener('submit', () => {
        save();
        const visible = new Set(boxes.map(box => box.value));
        form.querySelectorAll('[data-bank-retained]').forEach(input => input.remove());
        selected.forEach(id => {
            if (visible.has(id)) return;
            const input = document.createElement('input'); input.type = 'hidden'; input.name = 'selected[]'; input.value = id;
            input.dataset.bankRetained = '1'; form.append(input);
        });
    });
    save();
})();
