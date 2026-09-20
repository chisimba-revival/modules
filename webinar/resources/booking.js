/** Optional lookup enhances, but never replaces, transactional duplicate protection. */
(() => {
 const form=document.querySelector('[data-booking-form]');if(!form)return;
 const email=form.elements.email,notice=form.querySelector('[data-booking-status]'),button=form.querySelector('button[type="submit"]');
 let timer,busy=false,again=false;
 async function check(){
  if(busy){again=true;return;}
  const value=email.value.trim();if(!value||!email.validity.valid)return;
  busy=true;const abort=new AbortController(),timeout=setTimeout(()=>abort.abort(),10000);
  try{
   const body=new FormData();body.set('email',value);body.set('id',form.elements.id.value);body.set('check_token',form.elements.check_token.value);
   const response=await fetch(form.dataset.checkUrl,{method:'POST',body,credentials:'same-origin',signal:abort.signal});const result=await response.json();
   if(result.token)form.elements.check_token.value=result.token;
   if(value!==email.value.trim())return;
   if(response.ok&&result.ok){notice.hidden=!result.registered;notice.className='chisimba-notification chisimba-notification--success';notice.textContent=result.message;button.disabled=result.registered;}
  }catch(error){/* Registration remains available when this optional check fails. */}
  finally{clearTimeout(timeout);busy=false;if(again){again=false;check();}}
 }
 email.addEventListener('input',()=>{button.disabled=false;notice.hidden=true;clearTimeout(timer);timer=setTimeout(check,500);});
 email.addEventListener('blur',()=>{clearTimeout(timer);check();});
 check();
})();
