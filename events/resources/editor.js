/* Preserve intentional editing and expose validation inside optional sections.
 * Derek Keats <derek@dkeats.com>
 */
(() => {
  'use strict';
  const form = document.querySelector('[data-event-editor]');
  if (!form) return;
  const price = form.querySelector('[name="price"]');
  const rate = form.querySelector('[name="vat_percent"]');
  const component = form.querySelector('[data-vat-component]');
  if (price && rate && component) {
    const decimal = value => {
      if (!/^(0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/.test(value.trim())) return NaN;
      const [whole, fraction = ''] = value.trim().split('.');
      return Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
    };
    const updateVat = () => {
      const gross = decimal(price.value), basisPoints = decimal(rate.value);
      const valid = Number.isSafeInteger(gross) && gross > 0 && gross <= 100000000 && Number.isSafeInteger(basisPoints) && basisPoints <= 10000;
      const divisor = 10000 + basisPoints;
      component.textContent = valid ? (Math.floor((gross * basisPoints + Math.floor(divisor / 2)) / divisor) / 100).toFixed(2) : '—';
    };
    price.addEventListener('input', updateVat);
    rate.addEventListener('input', updateVat);
    // Preserve the stored rounded amount until either price input changes.
    if (component.textContent.trim() === '—') updateVat();
  }
  const imageUrl = form.querySelector('[name="image_url"]');
  const imageAlt = form.querySelector('[name="image_alt"]');
  const imagePreview = form.querySelector('[data-event-image-preview]');
  if (imageUrl && imagePreview) {
    const updateImage = () => {
      const valid = /^https?:\/\//i.test(imageUrl.value.trim());
      imagePreview.hidden = !valid;
      if (valid) imagePreview.src = imageUrl.value.trim();
      else imagePreview.removeAttribute('src');
      imagePreview.alt = imageAlt.value;
    };
    imageUrl.addEventListener('input', updateImage);
    imageUrl.addEventListener('change', () => { updateImage(); imageUrl.dispatchEvent(new Event('input', {bubbles: true})); });
    imageAlt.addEventListener('input', updateImage);
    form.querySelector('[data-event-image-clear]').addEventListener('click', () => {
      imageUrl.value = ''; imageAlt.value = '';
      imageUrl.dispatchEvent(new Event('input', {bubbles: true}));
    });
  }
  let dirty = false;
  form.addEventListener('input', () => {
    dirty = true;
    const state = form.querySelector('[data-editor-state]');
    if (state) state.textContent = state.dataset.dirty;
  });
  form.addEventListener('submit', () => { dirty = false; });
  window.addEventListener('beforeunload', event => {
    if (dirty) { event.preventDefault(); event.returnValue = ''; }
  });
  const reveal = element => {
    for (let parent = element.parentElement; parent; parent = parent.parentElement) {
      if (parent.tagName === 'DETAILS') parent.open = true;
    }
  };
  form.addEventListener('invalid', event => reveal(event.target), true);
  const invalid = form.querySelector('[aria-invalid="true"]');
  if (invalid) reveal(invalid);
  document.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', () => {
    const target = document.getElementById(link.getAttribute('href').slice(1));
    if (target && target.tagName === 'DETAILS') target.open = true;
    if (target) reveal(target);
  }));
})();
