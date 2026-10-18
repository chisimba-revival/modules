/** Protect unsent web text and prevent repeated clicks; server receipts remain authoritative. */
(() => {
  const history = document.querySelector('.chisimba-chat');
  if (history) history.scrollTop = history.scrollHeight;
  const form = document.querySelector('[data-conversation-compose]');
  if (!form) return;
  const input = form.querySelector('textarea');
  const button = form.querySelector('button[type="submit"]');
  let submitting = false;
  window.addEventListener('beforeunload', event => {
    if (!submitting && input.value.trim()) { event.preventDefault(); event.returnValue = ''; }
  });
  form.addEventListener('submit', () => { submitting = true; button.disabled = true; });
  window.addEventListener('pageshow', () => { submitting = false; button.disabled = false; });
})();
