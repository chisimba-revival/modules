/** Native video activation and incremental gallery browsing. @author Derek Keats */
(() => {
    'use strict';
    document.addEventListener('click', async event => {
        const play = event.target.closest('[data-video-id]');
        if (play && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && /^[A-Za-z0-9_-]{11}$/.test(play.dataset.videoId)) {
            const dialog = document.getElementById(play.dataset.uiOpen);
            const holder = dialog?.querySelector('[data-video-player]');
            if (!holder || typeof dialog.showModal !== 'function') return;
            event.preventDefault();
            holder.replaceChildren();
            const frame = document.createElement('iframe');
            frame.src = `https://www.youtube-nocookie.com/embed/${play.dataset.videoId}?autoplay=1&playsinline=1`;
            frame.title = play.dataset.videoTitle;
            frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
            frame.allowFullscreen = true;
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            holder.append(frame);
            dialog.addEventListener('close', () => holder.replaceChildren(), {once:true});
            return;
        }
        const more = event.target.closest('[data-video-more]');
        if (!more || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const gallery = more.closest('[data-video-gallery]');
        const grid = gallery?.querySelector('[data-video-grid]');
        if (!grid) return;
        event.preventDefault();
        if (more.getAttribute('aria-disabled') === 'true') return;
        const status = gallery.querySelector('[data-video-status]');
        more.setAttribute('aria-disabled', 'true');
        gallery.setAttribute('aria-busy','true');
        status.textContent = gallery.dataset.loading;
        try {
            const url = new URL(more.href, location.href);
            url.searchParams.set('fragment','1');
            const response = await fetch(url, {headers:{Accept:'application/json'}, credentials:'same-origin'});
            if (!response.ok) throw new Error('Gallery request failed');
            const data = await response.json();
            if (typeof data.html !== 'string' || !Number.isInteger(data.total) || !Number.isInteger(data.count)) throw new Error('Invalid gallery response');
            const next = data.next ? new URL(data.next, location.href) : null;
            if (next && next.origin !== location.origin) throw new Error('Invalid gallery destination');
            const parsed = new DOMParser().parseFromString(data.html,'text/html');
            const existing = new Set([...grid.querySelectorAll('[data-video-card]')].map(card=>card.dataset.videoCard));
            const added = [];
            for (const card of parsed.querySelectorAll('[data-video-card]')) {
                if (existing.has(card.dataset.videoCard)) continue;
                existing.add(card.dataset.videoCard);added.push(card);grid.append(card);
            }
            if (next) more.href = next.href;
            else more.hidden = true;
            if (added.length) added[0].querySelector('a')?.focus({preventScroll:true});
            status.textContent = gallery.dataset.loaded.replace('{shown}',String(grid.children.length)).replace('{total}',String(data.total));
        } catch (error) {
            status.textContent = gallery.dataset.error;
        } finally {
            more.removeAttribute('aria-disabled');gallery.removeAttribute('aria-busy');
        }
    });
})();
