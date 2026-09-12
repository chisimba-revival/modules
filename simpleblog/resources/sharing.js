/* Share only on an explicit user action; retain a normal link as the fallback. */
(function(){'use strict';if(window.ChisimbaBlogSharingReady)return;window.ChisimbaBlogSharingReady=true;
document.addEventListener('click',async function(event){var button=event.target.closest('[data-blog-share],[data-blog-copy]');if(!button)return;var url=button.dataset.blogShare||button.dataset.blogCopy;
try{if(button.dataset.blogShare&&navigator.share)await navigator.share({url:url});else if(navigator.clipboard){await navigator.clipboard.writeText(url);var status=button.parentElement.parentElement.querySelector('[data-share-status]');if(status)status.textContent=button.dataset.copied||'';}else button.parentElement.parentElement.querySelector('a').focus();}catch(error){if(error.name!=='AbortError')button.parentElement.parentElement.querySelector('a').focus();}
});}());
