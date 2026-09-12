/* Featured-media feedback also works when tag suggestions are unavailable. */
(function(){'use strict';var field=document.getElementById('comp_featured_image'),preview=document.querySelector('[data-featured-preview]'),alt=document.getElementById('featured-alt');
if(!field||!preview)return;
function update(){var value=field.value.trim();preview.hidden=true;if(!value){preview.removeAttribute('src');return;}try{var url=new URL(value,location.href);if(!['http:','https:'].includes(url.protocol))return;preview.src=url.href;preview.alt=alt?alt.value:'';}catch(error){preview.removeAttribute('src');}}
preview.addEventListener('load',function(){preview.hidden=false;});preview.addEventListener('error',function(){preview.hidden=true;});field.addEventListener('input',update);field.addEventListener('change',update);if(alt)alt.addEventListener('input',function(){preview.alt=alt.value;});update();
}());
/* Accessible native suggestions; stale responses cannot replace the latest query. */
(function(){'use strict';var input=document.getElementById('blog-tags'),list=document.getElementById('blog-tag-suggestions'),timer,request;
if(!input||!list)return;
input.addEventListener('input',function(){clearTimeout(timer);if(request)request.abort();var full=input.value,parts=full.split(','),query=parts.pop().trim(),prefix=parts.length?parts.join(',')+', ':'';list.replaceChildren();if(!query)return;
timer=setTimeout(async function(){request=new AbortController();var url=new URL(window.ChisimbaBlogTagSuggestions,location.href);url.searchParams.set('query',query);try{var response=await fetch(url,{signal:request.signal,credentials:'same-origin'});if(!response.ok)return;var names=await response.json();if(input.value!==full)return;list.replaceChildren();names.forEach(function(name){var option=document.createElement('option');option.value=prefix+name;list.appendChild(option);});}catch(error){if(error.name!=='AbortError')list.replaceChildren();}},200);
});}());
