/* Ticket enhancement using the framework's bundled QR encoder. Derek Keats <derek@dkeats.com> */
(() => {
  'use strict';
  const card = document.getElementById('event-ticket');
  if (!card) return;
  const target = card.querySelector('[data-ticket-qr]');
  if (typeof window.qrcode === 'function') {
    const qr = window.qrcode(0, 'M');
    qr.addData(target.dataset.ticketQr);
    qr.make();
    target.innerHTML = qr.createSvgTag({cellSize: 5, margin: 20, scalable: true});
    const svg = target.querySelector('svg');
    svg.setAttribute('width', '240'); svg.setAttribute('height', '240');
    document.querySelector('[data-download-ticket]').disabled = false;
  }
  const ticketDocument = () => {
    const page = document.implementation.createHTMLDocument(card.querySelector('h2').textContent);
    page.documentElement.lang = document.documentElement.lang || 'en';
    const charset = page.createElement('meta'); charset.setAttribute('charset', 'utf-8'); page.head.append(charset);
    const viewport = page.createElement('meta'); viewport.name = 'viewport'; viewport.content = 'width=device-width, initial-scale=1'; page.head.append(viewport);
    page.body.append(page.importNode(card, true));
    return '<!doctype html>\n' + page.documentElement.outerHTML;
  };
  document.querySelector('[data-download-ticket]').addEventListener('click', () => {
    const blob = new Blob([ticketDocument()], {type: 'text/html;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a'); link.href = url; link.download = 'event-ticket.html'; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  });
  document.querySelector('[data-print-ticket]').addEventListener('click', () => {
    const win = window.open('', '_blank');
    if (!win) return;
    win.opener = null; win.document.write(ticketDocument()); win.document.close();
    win.focus(); win.print();
  });
})();
