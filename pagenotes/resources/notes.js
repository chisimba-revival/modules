/**
 * Asynchronous editing and attachment behaviour for Notes.
 *
 * @author Derek Keats
 * @package pagenotes
 */
(function(){
 'use strict';
 const editor=document.querySelector('[data-note-editor]');
 const body=document.querySelector('[data-note-body]');
 const count=document.querySelector('[data-word-count]');
 const request=async form=>{const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});let data;try{data=await response.json();}catch(error){throw new Error('The server response could not be read. Your work is kept.');}if(data.csrfToken)document.querySelectorAll('input[name="csrf_token"]').forEach(input=>input.value=data.csrfToken);if(!response.ok||!data.ok)throw new Error(data.message||'The change could not be saved.');return data;};
 const text=(element,message,error=false)=>{if(!element)return;element.textContent=message;element.classList.toggle('error',error);element.classList.toggle('success',!error);};
 const words=()=>{if(!editor||!count)return;const value=(editor.innerText||'').trim();count.textContent=(value?value.split(/\s+/).length:0)+' words';};
 const sync=()=>{if(editor&&body)body.value=editor.innerHTML;words();};
 if(editor){editor.addEventListener('input',()=>{sync();const feedback=document.querySelector('[data-note-feedback]');if(feedback){feedback.textContent='Unsaved changes';feedback.classList.remove('success','error');}});document.querySelectorAll('[data-command]').forEach(button=>{button.addEventListener('mousedown',event=>event.preventDefault());button.addEventListener('click',()=>{editor.focus();let value=button.dataset.value||null;if(button.dataset.command==='createLink'){value=window.prompt('Link address');if(!value)return;}document.execCommand(button.dataset.command,false,value);sync();});});sync();}
 document.addEventListener('submit',async event=>{
  const form=event.target;
  if(!(form instanceof HTMLFormElement))return;
  if(form.matches('[data-note-save]')){event.preventDefault();sync();const feedback=form.querySelector('[data-note-feedback]');const button=form.querySelector('[type="submit"]');button.disabled=true;text(feedback,'Saving…');try{const data=await request(form);text(feedback,data.message);const heading=document.querySelector('[data-note-heading]');if(heading)heading.textContent=data.title;}catch(error){text(feedback,error.message,true);}finally{button.disabled=false;}return;}
  if(form.matches('[data-note-attach]')){event.preventDefault();const feedback=form.querySelector('[data-attach-feedback]');const button=form.querySelector('[type="submit"]');button.disabled=true;try{const data=await request(form);const link=data.link;const item=document.createElement('li');item.textContent=link.targetlabel+' ('+link.targettype.replaceAll('_',' ')+')';document.querySelector('[data-note-links]').append(item);form.reset();text(feedback,data.message);}catch(error){text(feedback,error.message,true);}finally{button.disabled=false;}return;}
  if(form.matches('[data-note-detach]')){event.preventDefault();const button=form.querySelector('[type="submit"]');button.disabled=true;try{await request(form);form.closest('li').remove();}catch(error){button.disabled=false;button.title=error.message;}}
 });
})();
