/* Incremental saves retain the form, draft and scroll on failed or expired requests. */
(() => {
 const form=document.querySelector('[data-audience-form]');if(!form)return;
 const status=document.querySelector('[data-audience-status]');
 const panel=form.querySelector('[data-mail-progress]');let polling=false;
 const refreshProgress=async()=>{if(!panel||!form.elements.id.value||polling)return;polling=true;try{const url=new URL(panel.dataset.progressUrl,location.href);url.searchParams.set('id',form.elements.id.value);const res=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});if(!res.ok)return;const data=await res.json();if(!data.label)return;panel.hidden=false;panel.className='chisimba-notification chisimba-notification--'+data.tone;panel.querySelector('[data-mail-label]').textContent=data.label;panel.querySelector('[data-mail-count]').textContent=data.message;const q=form.querySelector('[data-campaign-queue]');if(q&&data.state!=='draft'){q.disabled=true;q.querySelector('span:last-child').textContent=data.label;}}catch(error){}finally{polling=false;}};
 if(panel){refreshProgress();setInterval(()=>{if(!document.hidden&&!busy)refreshProgress();},15000);}
 const feedback=form.querySelector('[data-campaign-feedback]');
 const report=message=>{status.textContent=message;if(feedback)feedback.textContent=message;};
 let dirty=false,busy=false,revision=0;
 form.addEventListener('input',()=>{dirty=true;revision++;const q=form.querySelector('[data-campaign-queue]');if(q)q.disabled=true;});
 window.addEventListener('beforeunload',e=>{if(dirty||busy){e.preventDefault();e.returnValue='';}});
 form.addEventListener('submit',async e=>{
  if(!window.fetch)return;e.preventDefault();if(busy)return;
  const button=e.submitter;if(button?.matches('[data-campaign-queue]')&&!window.confirm(form.dataset.confirm))return;
  const body=new FormData(form);body.set('ajax','1');const rev=revision;
  const controls=[...form.querySelectorAll('button')];const disabled=controls.map(b=>b.disabled);controls.forEach(b=>b.disabled=true);busy=true;form.setAttribute('aria-busy','true');report(form.dataset.saving);
  let result=null;const abort=new AbortController();const timer=setTimeout(()=>abort.abort(),30000);
  try{const res=await fetch(button?.hasAttribute('formaction')?button.formAction:form.action,{method:'POST',body,signal:abort.signal,credentials:'same-origin',headers:{Accept:'application/json'}});result=await res.json();if(result.csrf)form.elements.csrf_token.value=result.csrf;
   if(!res.ok||!result.ok){report(result.message||form.dataset.failed);return;}
   if(result.id)form.elements.id.value=result.id;if(result.version!==undefined){form.elements.version.value=result.version;form.elements.revision.value=result.version;}
   if(rev===revision)dirty=false;
   if(result.previewHtml!==undefined){const htmlPreview=form.querySelector('[data-audience-html-preview]');if(htmlPreview)htmlPreview.srcdoc=result.previewHtml;}
   if(result.preview!==undefined)form.querySelector('[data-audience-preview]').textContent=result.preview;
   report(result.message);if(result.state&&result.state!=='draft')refreshProgress();
   if(result.id){const u=new URL(location.href);u.searchParams.set('id',result.id);history.replaceState(null,'',u);}
  }catch(error){report(form.dataset.failed);}
  finally{clearTimeout(timer);busy=false;form.removeAttribute('aria-busy');controls.forEach((b,i)=>b.disabled=disabled[i]);
   if(result?.ok&&result.state){const q=form.querySelector('[data-campaign-queue]'),c=form.querySelector('[data-campaign-cancel]'),fields=form.querySelector('[data-draft-fields]');if(q){q.disabled=dirty||result.state!=='draft';if(result.state!=='draft')q.querySelector('span:last-child').textContent=q.dataset.queuedLabel;}const state=form.querySelector('[data-campaign-state]');if(state)state.textContent=result.stateLabel||result.state;if(c)c.disabled=result.state==='cancelled';if(fields)fields.disabled=result.state!=='draft';}
  }
 });
})();
