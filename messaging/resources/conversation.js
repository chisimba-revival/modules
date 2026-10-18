/** Progressive chat updates; the canonical controller still owns permissions and receipts. */
(() => {
  const form = document.querySelector('[data-conversation-compose]');
  if (!form) return;
  const input = form.querySelector('textarea');
  const button = form.querySelector('button[type="submit"]');
  const status = document.querySelector('[data-chat-status]');
  const channel = form.elements.channel.value;
  const scope = form.elements.conversation_context.value;
  let sending = false, revision = 0, polling = false;
  const history = () => document.querySelector('.chisimba-chat');
  if (history()) history().scrollTop = history().scrollHeight;
  const say = key => { status.textContent = status.dataset[key] || ''; };
  function parse(html) {
    const page = new DOMParser().parseFromString(html, 'text/html');
    const next = page.querySelector('[data-conversation-compose]');
    if (!next || next.elements.channel.value !== channel || next.elements.conversation_context.value !== scope) throw Error('scope');
    return { page, next };
  }
  function refresh({page, next}, forceBottom = false) {
    const old = history(), fresh = page.querySelector('.chisimba-chat');
    if (!old || !fresh) throw Error('history');
    const nearBottom = old.scrollHeight - old.scrollTop - old.clientHeight < 80;
    const position = old.scrollTop;
    old.replaceChildren(...fresh.childNodes);
    old.scrollTop = forceBottom || nearBottom ? old.scrollHeight : position;
    // Renew the form token without replacing the user's unsent text or receipt key.
    document.querySelectorAll('input[name="csrf_token"]').forEach(el => { el.value = next.elements.csrf_token.value; });
    const read = document.querySelector('input[name="sequence"]');
    const nextRead = page.querySelector('input[name="sequence"]');
    if (read && nextRead) read.value = nextRead.value;
    const unread = document.querySelector('[data-chat-unread]');
    if (unread) unread.textContent = page.querySelector('[data-chat-unread]').textContent;
  }
  async function poll() {
    if (sending || polling || document.hidden || new URL(location.href).searchParams.has('before')) return;
    polling = true;
    const ticket = revision;
    try {
      const response = await fetch(location.href, {credentials:'same-origin',cache:'no-store'});
      if (!response.ok) throw Error('read');
      const parsed = parse(await response.text());
      if (ticket !== revision || sending) return;
      refresh(parsed); say('connected');
    } catch { if (ticket === revision && !sending) say('offline'); }
    finally { polling = false; }
  }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (sending) return;
    sending = true; revision++; button.disabled = true; input.readOnly = true; say('sending');
    try {
      const response = await fetch(form.action, {method:'POST',credentials:'same-origin',body:new URLSearchParams(new FormData(form)),cache:'no-store'});
      const html = await response.text();
      const parsed = parse(html);
      refresh(parsed, response.ok);
      if (!response.ok) {
        status.textContent = parsed.page.querySelector('[role="alert"]')?.textContent || status.dataset.offline;
        return;
      }
      input.value = '';
      form.elements.submission_id.value = parsed.next.elements.submission_id.value;
      say('sent'); input.focus();
    } catch { say('retry'); }
    finally { sending = false; button.disabled = false; input.readOnly = false; }
  });
  window.addEventListener('beforeunload', event => {
    if (input.value.trim()) { event.preventDefault(); event.returnValue = ''; }
  });
  window.addEventListener('online', poll);
  document.addEventListener('visibilitychange', poll);
  window.setInterval(poll, 5000);
})();
