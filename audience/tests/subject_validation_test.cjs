const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
let submit,calls=0,focused=0;const events={},attrs={};
const subject={value:'',disabled:false,addEventListener:(n,f)=>events[n]=f,setAttribute:(k,v)=>attrs[k]=v,removeAttribute:k=>delete attrs[k],focus:()=>focused++};
const saveError={hidden:true,focus:()=>focused++};
const error={hidden:true,textContent:'Enter a subject before saving and previewing your email. Your other entries are still here.'},status={},feedback={},save={disabled:false,hasAttribute:()=>false,matches:()=>false};
const form={dataset:{saving:'Saving',failed:'Failed'},elements:{subject,body:{value:'Keep this draft'},csrf_token:{value:'original'},id:{value:''},version:{},revision:{}},querySelector:s=>({'[data-subject-error]':error,'[data-save-subject-error]':saveError,'[data-campaign-feedback]':feedback})[s],querySelectorAll:()=>[save],addEventListener:(n,f)=>{if(n==='submit')submit=f},setAttribute(){},removeAttribute(){}};
const context={document:{querySelector:s=>s==='[data-audience-form]'?form:status},window:{fetch:true,addEventListener(){}},FormData:class{set(){}},AbortController,URL,setTimeout,clearTimeout,location:{href:'https://example.test/'},history:{replaceState(){}},fetch:async()=>{calls++;return {ok:true,json:async()=>({ok:true,message:'Saved'})}}};
vm.runInNewContext(fs.readFileSync(process.argv[2]||__dirname+'/../resources/admin.js','utf8'),context);
(async()=>{
 events.invalid({preventDefault(){}});assert.equal(error.hidden,false);assert.equal(saveError.hidden,false);assert.equal(attrs['aria-invalid'],'true');assert.equal(focused,1);
 for(const value of ['', '   ']){subject.value=value;await submit({preventDefault(){},submitter:save});assert.equal(calls,0);assert.equal(form.elements.body.value,'Keep this draft');assert.equal(form.elements.csrf_token.value,'original');assert.equal(status.textContent,error.textContent);}
 subject.value='Upcoming webinars';events.input();assert.equal(error.hidden,true);assert.equal(saveError.hidden,true);assert.equal(attrs['aria-invalid'],undefined);await submit({preventDefault(){},submitter:save});assert.equal(calls,1);assert.equal(status.textContent,'Saved');assert.equal(form.elements.body.value,'Keep this draft');
 console.log('PASS blank and whitespace subjects show notice and focus; draft/CSRF retained; correction clears notice and allows save');
})().catch(e=>{console.error(e);process.exitCode=1});
