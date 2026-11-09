/* Independent Ajax saves keep the biography draft and editor scroll intact. */
(() => {
 const form=document.querySelector('[data-speaker-links]');if(!form)return;
 const list=form.querySelector('[data-links-list]'),fields=form.querySelector('[data-links-fields]'),feedback=form.querySelector('[data-links-feedback]');let busy=false;
 const reset=()=>{form.elements.link_id.value=crypto.randomUUID().replaceAll('-','');form.elements.url.value='';form.elements.label.value='';form.elements.category.value='website';};
 const apply=data=>{if(data.csrf)form.elements.csrf_token.value=data.csrf;if(!data.ok)throw new Error(data.message||form.dataset.failed);list.innerHTML=data.html;form.elements.links_version.value=data.version;feedback.textContent=data.message||'';};
 form.addEventListener('submit',async event=>{event.preventDefault();await change('save');});
 async function change(operation){
  if(busy)return;busy=true;form.setAttribute('aria-busy','true');const body=new FormData(form);body.set('operation',operation);fields.disabled=true;list.querySelectorAll('button').forEach(b=>b.disabled=true);feedback.textContent=form.dataset.saving;
  try{const res=await fetch(form.action,{method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json'}});const data=await res.json();apply(data);reset();}
  catch(e){feedback.textContent=e.message||form.dataset.failed;}
  finally{busy=false;fields.disabled=false;list.querySelectorAll('button').forEach(b=>b.disabled=false);form.removeAttribute('aria-busy');}
 }
 list.addEventListener('click',event=>{const button=event.target.closest('button'),row=button?.closest('[data-link-row]');if(!row||busy)return;
  if(button.hasAttribute('data-link-edit')){for(const key of ['category','label','url'])form.elements[key].value=row.dataset[key];form.elements.link_id.value=row.dataset.id;form.elements.url.focus();}
  if(button.hasAttribute('data-link-delete')&&confirm(form.dataset.confirm)){form.elements.link_id.value=row.dataset.id;change('delete');}
 });
 form.querySelector('[data-link-reset]').addEventListener('click',()=>{reset();form.elements.url.focus();});
 document.addEventListener('webinar:saved',async event=>{
  if(form.elements.id.value||!event.detail.id)return;form.elements.id.value=event.detail.id;
  try{const url=new URL(form.action,location.href);url.searchParams.set('id',event.detail.id);const res=await fetch(url,{credentials:'same-origin'});apply(await res.json());fields.disabled=false;document.querySelector('[data-links-save-first]').hidden=true;}
  catch(e){feedback.textContent=form.dataset.failed;}
 });
})();
