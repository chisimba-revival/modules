/* Public promotion uses only public event fields. Derek Keats <derek@dkeats.com> */
(() => {
  'use strict';
  const share = document.querySelector('[data-share-event]');
  if (!share) return;
  const status = document.querySelector('[data-share-status]');
  const data = {title: share.dataset.shareTitle, text: share.dataset.shareText, url: share.dataset.shareUrl};
  const copy = async () => {
    try { await navigator.clipboard.writeText(`${data.title}\n\n${data.text}\n\n${data.url}`); status.textContent = status.dataset.copied; }
    catch (_) { status.textContent = status.dataset.copyFailed; }
  };
  share.addEventListener('click', async () => {
    if (!navigator.share) return copy();
    try { await navigator.share(data); } catch (error) { if (error.name !== 'AbortError') status.textContent = status.dataset.copyFailed; }
  });
  document.querySelector('[data-copy-event]').addEventListener('click', copy);
})();
