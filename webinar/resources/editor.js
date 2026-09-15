/** Keep the staff editor in place; native submission remains the fallback. @author Derek Keats */
(() => {
    'use strict';
    const form=document.querySelector('[data-webinar-editor]');if(!form)return;
    let busy=false,dirty=false,revision=0,suggestTimer,suggestRequest;
    const error=document.querySelector('[data-webinar-error]'),status=document.querySelector('[data-webinar-status]');
    const preview=document.querySelector('[data-webinar-preview]');
    const image=document.getElementById('comp_webinar_image'),picture=form.querySelector('[data-webinar-image]');
    function showImage(){
        if(!image||!picture)return;
        const value=image.value.trim();
        try{const url=new URL(value,location.href);if(!value||!['http:','https:'].includes(url.protocol))throw new Error();picture.src=url.href;picture.alt=form.elements.image_alt.value;picture.hidden=false;}
        catch(e){picture.hidden=true;picture.removeAttribute('src');}
    }
    showImage();form.addEventListener('input',()=>{dirty=true;revision++;});form.addEventListener('change',()=>{dirty=true;revision++;showImage();});
    window.addEventListener('beforeunload',event=>{if(dirty||busy){event.preventDefault();event.returnValue='';}});
    form.addEventListener('submit',async event=>{
        if(!window.fetch)return;
        event.preventDefault();if(busy)return;
        const button=event.submitter;
        form.querySelectorAll('[data-chisimba-editor]').forEach(field=>window.ChisimbaEditor?.sync(field.id));
        const submittedRevision=revision;const body=new FormData(form);if(button?.name)body.set(button.name,button.value);body.set('ajax','1');
        busy=true;form.setAttribute('aria-busy','true');status.textContent=form.dataset.saving;error.textContent='';
        const controls=[...form.querySelectorAll('button[type="submit"]')];controls.forEach(b=>b.disabled=true);
        const request=new AbortController();const timer=setTimeout(()=>request.abort(),30000);
        try{
            const response=await fetch(button?.hasAttribute('formaction')?button.formAction:form.action,{method:'POST',body,signal:request.signal,credentials:'same-origin',headers:{Accept:'application/json'}});
            const data=await response.json();if(typeof data.csrf==='string')form.elements.csrf_token.value=data.csrf;
            if(!response.ok||!data.ok){error.textContent=data.message||form.dataset.failed;status.textContent='';return;}
            if(data.aux){
                form.elements.aux_create_id.value=data.aux.next_id;
                const list=form.querySelector(data.aux.kind==='category'?'[data-webinar-categories]':'[data-webinar-speakers]');
                const label=document.createElement('label'),checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.name=data.aux.kind==='category'?'categories[]':'speakers[]';checkbox.value=data.aux.id;checkbox.checked=true;label.append(checkbox,document.createTextNode(' '+data.aux.name));list.append(label);checkbox.focus({preventScroll:true});
                if(data.aux.kind==='category'){const option=document.createElement('option');option.value=data.aux.id;option.textContent=data.aux.name;form.elements.category_parent?.append(option);form.elements.category_name.value='';}
                else{form.elements.speaker_name.value='';form.elements.speaker_bio.value='';}
                dirty=true;
            }else{
                form.elements.id.value=data.id;form.elements.version.value=data.version;
                if(button?.name==='publish_action')form.elements.status.value=button.value;
                history.replaceState(null,'',data.edit);preview.href=data.preview;preview.hidden=false;dirty=revision!==submittedRevision;
            }
            status.textContent=data.message+(dirty&&!data.aux?' '+form.dataset.unsaved:'');
        }catch(e){error.textContent=form.dataset.failed;status.textContent='';}
        finally{clearTimeout(timer);busy=false;form.removeAttribute('aria-busy');controls.forEach(b=>b.disabled=false);}
    });
    const tags=form.elements.tags,choices=document.getElementById('webinar-tag-choices');
    if(tags&&choices){
        tags.setAttribute('list',choices.id);tags.autocomplete='off';
        tags.addEventListener('input',()=>{clearTimeout(suggestTimer);suggestRequest?.abort();suggestTimer=setTimeout(async()=>{
            const pieces=tags.value.split(',');const q=pieces.pop().trim();const prefix=pieces.length?pieces.join(',')+', ':'';
            const url=new URL(form.dataset.suggestions,location.href);url.searchParams.set('q',q);suggestRequest=new AbortController();
            try{const response=await fetch(url,{credentials:'same-origin',signal:suggestRequest.signal});if(!response.ok)return;const data=await response.json();choices.replaceChildren();for(const name of data.tags){const option=document.createElement('option');option.value=prefix+name;choices.append(option);}}catch(e){}
        },180);});
    }
})();
