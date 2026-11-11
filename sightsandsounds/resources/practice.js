/* Native media preview and accidental-navigation protection. No answer keys in script. */
(() => {
  function setup() {
    let dirty = false;
    for (const form of document.querySelectorAll('form')) {
      form.addEventListener('input', () => { dirty = true; });
      form.addEventListener('submit', () => { dirty = false; });
    }
    window.addEventListener('beforeunload', event => {
      if (dirty) { event.preventDefault(); event.returnValue = ''; }
    });
    const form = document.querySelector('[data-ss-upload]');
    if (!form) return;
    let objectUrl;
    form.elements.media.addEventListener('change', () => {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      const preview = form.querySelector('[data-ss-preview]');
      const img = preview.querySelector('img'), audio = preview.querySelector('audio');
      audio.pause(); audio.removeAttribute('src'); img.removeAttribute('src');
      img.hidden = audio.hidden = preview.hidden = true;
      const file = form.elements.media.files[0];
      form.elements.media.setCustomValidity('');
      if (!file) return;
      if (file.size > 10485760) {
        form.elements.media.setCustomValidity(form.dataset.sizeError);
        form.elements.media.reportValidity();
        return;
      }
      objectUrl = URL.createObjectURL(file);
      const target = file.type.startsWith('image/') ? img : audio;
      target.src = objectUrl; target.hidden = preview.hidden = false;
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup); else setup();
})();
