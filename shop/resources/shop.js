/* Progressive shop form behaviour. Server validation remains authoritative.
 * @author Derek Keats <derek@dkeats.com>
 */
(() => {
    'use strict';
    const rows = document.querySelector('[data-shop-bands]');
    const add = document.querySelector('[data-shop-add-band]');
    if (rows && add) {
        add.hidden = false;
        add.addEventListener('click', () => {
            const index = rows.children.length;
            if (index >= 20) return;
            const row = rows.lastElementChild.cloneNode(true);
            row.querySelectorAll('input').forEach(input => {
                const old = input.id;
                input.id = old.replace(/-\d+$/, '-' + index);
                input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                input.value = '';
                const label = row.querySelector('label[for="' + old + '"]');
                label.htmlFor = input.id;
                label.textContent = label.textContent.replace(/\d+$/, String(index + 1));
            });
            rows.appendChild(row);
            row.querySelector('input').focus();
            add.disabled = rows.children.length >= 20;
        });
    }
    const image = document.querySelector('[name="image_url"]');
    const preview = document.querySelector('[data-shop-cover]');
    const picker = document.querySelector('[data-shop-picker]');
    if (image && preview) {
        const update = () => {
            let url;
            try { url = new URL(image.value); } catch (_) { preview.hidden = true; return; }
            preview.hidden = url.origin !== location.origin || url.protocol !== 'https:';
            if (!preview.hidden) preview.src = url.href;
        };
        image.addEventListener('change', update);
        if (picker) {
            picker.hidden = false;
            picker.addEventListener('click', () => window.open(picker.dataset.shopPicker, 'chisimbaFilePicker', 'width=920,height=720,resizable=yes,scrollbars=yes'));
            const previous = window.ChisimbaFilePickerReceive;
            window.ChisimbaFilePickerReceive = (target, file) => {
                if (target !== image.id) { if (previous) previous(target, file); return; }
                if (!file || !file.url) return;
                const url = new URL(file.url, location.href);
                if (url.origin !== location.origin || url.protocol !== 'https:') return;
                image.value = url.href; image.dispatchEvent(new Event('change', {bubbles:true}));
            };
        }
    }
})();

// Selection helpers do not replace server validation or individual checkboxes.
document.querySelectorAll('[data-sale-select]').forEach(button => {
    button.hidden = false;
    button.addEventListener('click', () => {
        button.closest('form').querySelectorAll('[name="book_ids[]"]').forEach(input => {
            input.checked = button.dataset.saleSelect === 'all';
        });
    });
});

const salePercent = document.querySelector('[name="percent"]');
if (salePercent) {
    salePercent.min = '1'; salePercent.max = '99';
    salePercent.addEventListener('input', () => {
        const percent = Number(salePercent.value);
        document.querySelectorAll('[data-sale-price]').forEach(row => {
            const cents = Number(row.dataset.regularPrice);
            row.querySelector('output').textContent = Number.isInteger(percent) && percent >= 1 && percent <= 99 && cents > 0
                ? 'R' + (Math.max(1, Math.floor((cents * (100 - percent) + 50) / 100)) / 100).toFixed(2) : '—';
        });
    });
}

// Native non-modal popovers support Escape and click-away without blocking checkout.
// The server offers at most once until the basket is emptied, including reload/back.
const comboOffer = document.querySelector('[data-shop-offer]');
if (comboOffer && typeof comboOffer.showPopover === 'function') {
    const previousFocus = document.activeElement === document.body ? document.querySelector('#shop-name, h1') : document.activeElement;
    comboOffer.addEventListener('toggle', event => {
        if (event.newState === 'closed') requestAnimationFrame(() => {
            // Native light-dismiss may restore body focus after the toggle event.
            // Preserve an input deliberately clicked outside the offer.
            if ((document.activeElement === document.body || comboOffer.contains(document.activeElement)) && previousFocus instanceof HTMLElement) previousFocus.focus({preventScroll:true});
        });
    });
    comboOffer.showPopover();
    comboOffer.querySelector('[popovertargetaction="hide"]').focus({preventScroll:true});
}
