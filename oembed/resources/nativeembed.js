/* Native, opt-in media previews. Never execute remote HTML or JSONP. */
(() => {
    'use strict';
    const script = document.currentScript;
    const selector = script.dataset.embedSelector;
    let endpoint;
    try { endpoint = new URL(script.dataset.embedEndpoint, document.baseURI); }
    catch (_) { return; }
    if (endpoint.origin !== location.origin) return;
    const seen = window.ChisimbaNativeEmbedLinks || (window.ChisimbaNativeEmbedLinks = new WeakSet());
    const start = () => {
        const enhance = root => {
            const links = [...root.querySelectorAll(selector)];
            if (root.matches && root.matches(selector)) links.unshift(root);
            links.forEach(link => {
                if (!(link instanceof HTMLAnchorElement) || seen.has(link)) return;
                seen.add(link);
                const href = link.getAttribute('href');
                if (!href || !/^https?:\/\//i.test(href)) return;
                fetch(endpoint, {method:'POST', credentials:'omit', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({url:href})})
                    .then(response => response.ok ? response.json() : null)
                    .then(media => {
                        if (!media || !['frame','image','audio','video'].includes(media.kind) || !link.isConnected || link.getAttribute('href') !== href) return;
                        const url = new URL(media.url);
                        if (url.protocol !== 'https:') return;
                        const button = document.createElement('button');
                        button.type = 'button'; button.className = 'chisimba-media-preview__button';
                        button.textContent = script.dataset.embedLabel;
                        const preview = document.createElement('span');
                        preview.className = 'chisimba-media-preview';
                        link.after(preview); preview.append(button);
                        button.addEventListener('click', () => {
                            const element = document.createElement({frame:'iframe',image:'img',audio:'audio',video:'video'}[media.kind]);
                            element.className = 'chisimba-media-preview__media';
                            if (media.kind === 'frame') {
                                element.title = script.dataset.embedTitle;
                                element.referrerPolicy = 'strict-origin-when-cross-origin';
                                element.allow = 'fullscreen'; element.allowFullscreen = true;
                            } else if (media.kind === 'image') element.alt = link.textContent || script.dataset.embedTitle;
                            else { element.controls = true; element.preload = 'none'; if (media.kind === 'video') element.playsInline = true; }
                            element.src = url.href;
                            preview.replaceChildren(element);
                            element.tabIndex = 0; element.focus();
                        }, {once:true});
                    }).catch(() => { /* The original link remains usable on failure. */ });
            });
        };
        enhance(document);
        new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) enhance(node);
        }))).observe(document.body, {childList:true, subtree:true});
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true}); else start();
})();
