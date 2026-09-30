/** Lazy video playback and date ordering for owned collections. */
(() => {
    'use strict';
    if (window.chisimbaVideoCollections) return;
    window.chisimbaVideoCollections = true;
    document.querySelectorAll('[data-video-collection] .chisimba-video-collection__toolbar').forEach(bar => { bar.hidden = false; });
    document.addEventListener('click', event => {
        const order = event.target.closest('[data-collection-order]');
        if (order) {
            const collection = order.closest('[data-video-collection]');
            const grid = collection.querySelector('[data-collection-grid]');
            const direction = order.dataset.collectionOrder === 'asc' ? 1 : -1;
            [...grid.children].sort((a, b) => direction * (a.dataset.date.localeCompare(b.dataset.date) || a.dataset.sourceId.localeCompare(b.dataset.sourceId, 'en', {numeric:true}))).forEach(card => grid.append(card));
            collection.dataset.order = order.dataset.collectionOrder;
            collection.querySelectorAll('[data-collection-order]').forEach(button => button.setAttribute('aria-pressed', String(button === order)));
            return;
        }
        const trigger = event.target.closest('[data-collection-play]');
        if (!trigger || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) return;
        const dialog = document.getElementById(trigger.dataset.collectionPlay);
        const holder = dialog?.querySelector('[data-collection-player]');
        if (!holder || typeof dialog.showModal !== 'function') return;
        const embed = trigger.dataset.embed;
        if (!/^https:\/\/(?:www\.tiktok\.com\/player\/v1\/[0-9]{10,25}|www\.youtube-nocookie\.com\/embed\/[A-Za-z0-9_-]{6,20}|player\.vimeo\.com\/video\/[0-9]{6,12})$/.test(embed)) return;
        event.preventDefault();
        holder.replaceChildren();
        const frame = document.createElement('iframe');
        frame.src = `${embed}?autoplay=1`;
        frame.title = trigger.dataset.title;
        frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        frame.allowFullscreen = true;
        frame.referrerPolicy = 'strict-origin-when-cross-origin';
        holder.append(frame);
        dialog.querySelector('.chisimba-ui-window__title').textContent = trigger.dataset.title;
        const backdrop = event => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        };
        dialog.addEventListener('click', backdrop);
        dialog.addEventListener('close', () => {
            holder.replaceChildren();
            dialog.removeEventListener('click', backdrop);
            trigger.focus({preventScroll:true});
        }, {once:true});
        dialog.showModal();
    });
})();
