/* Native media preview and accidental-navigation protection. No answer keys in script. */
(() => {
  function setup() {
    let dirty = false;
    document.addEventListener('input', event => { if(event.target.closest('[data-ss-player]')) dirty=true; });
    document.addEventListener('submit', async event => {
      const form=event.target;
      if(!form.matches('[data-ss-answer]'))return;
      event.preventDefault();
      const root=form.closest('[data-ss-player]'), button=form.querySelector('button');
      if(button.disabled)return;
      const body=new FormData(form);button.disabled=true;
      try {
        const response=await fetch(form.action,{method:'POST',body,credentials:'same-origin'});
        const doc=new DOMParser().parseFromString(await response.text(),'text/html');
        const replacement=doc.querySelector('[data-ss-player]');
        if(!replacement)throw Error('Unavailable');
        root.replaceWith(replacement);dirty=!response.ok;
        replacement.querySelector(response.ok?'[role="status"]':'[role="alert"]')?.setAttribute('tabindex','-1');
        replacement.querySelector(response.ok?'[role="status"]':'[role="alert"]')?.focus();
      } catch {
        dirty=true;let error=root.querySelector('[data-ss-error]');
        if(!error){error=document.createElement('p');error.dataset.ssError='';error.setAttribute('role','alert');form.append(error);}
        error.textContent=root.dataset.error;
      } finally {button.disabled=false;}
    });
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
