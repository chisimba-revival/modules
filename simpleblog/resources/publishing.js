/* Accessible native suggestions; stale responses cannot replace the latest query. */
(function(){'use strict';var input=document.getElementById('blog-tags'),list=document.getElementById('blog-tag-suggestions'),timer,request;
if(!input||!list)return;
input.addEventListener('input',function(){clearTimeout(timer);if(request)request.abort();var full=input.value,parts=full.split(','),query=parts.pop().trim(),prefix=parts.length?parts.join(',')+', ':'';list.replaceChildren();if(!query)return;
timer=setTimeout(async function(){request=new AbortController();var url=new URL(window.ChisimbaBlogTagSuggestions,location.href);url.searchParams.set('query',query);try{var response=await fetch(url,{signal:request.signal,credentials:'same-origin'});if(!response.ok)return;var names=await response.json();if(input.value!==full)return;list.replaceChildren();names.forEach(function(name){var option=document.createElement('option');option.value=prefix+name;list.appendChild(option);});}catch(error){if(error.name!=='AbortError')list.replaceChildren();}},200);
});}());
