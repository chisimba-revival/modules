/* Behavioural checks of progressive chat: lost replies, retries and scope changes. */
const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
function fixture(response) {
 const handlers={};
 const input={value:'Keep this message',readOnly:false,focus(){}};
 const button={disabled:false};
 const fields={channel:{value:'channel'},conversation_context:{value:'course'},submission_id:{value:'same-key'},csrf_token:{value:'old-token'}};
 const form={elements:fields,action:'https://local/send',querySelector:s=>s==='textarea'?input:button,addEventListener:(e,fn)=>handlers[e]=fn};
 const history={scrollTop:0,scrollHeight:500,clientHeight:300,replaceChildren(){}};
 const status={dataset:{sending:'sending',sent:'sent',retry:'retry',offline:'offline',connected:'connected'}};
 const next={elements:{...fields,submission_id:{value:'new-key'},csrf_token:{value:'fresh-token'}}};
 const page={querySelector:s=>s==='[data-conversation-compose]'?next:s==='.chisimba-chat'?{childNodes:[]}:null};
 const document={hidden:false,querySelector:s=>s==='[data-conversation-compose]'?form:s==='[data-chat-status]'?status:s==='.chisimba-chat'?history:null,querySelectorAll:()=>[fields.csrf_token],addEventListener(){}};
 const context={document,window:{addEventListener(){},setInterval(){}},location:{href:'https://local/chat'},URL,URLSearchParams,FormData:class{constructor(){return [['submission_id',fields.submission_id.value]]}},DOMParser:class{parseFromString(){return page;}},fetch:response};
 vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/conversation.js','utf8'),context);
 return {handlers,input,button,fields,status,next};
}
test('lost response retains text and same retry key, re-enables composer',async()=>{
 const f=fixture(async()=>{throw Error('offline')});await f.handlers.submit({preventDefault(){}});
 assert.equal(f.input.value,'Keep this message');assert.equal(f.fields.submission_id.value,'same-key');assert.equal(f.status.textContent,'retry');assert.equal(f.button.disabled,false);assert.equal(f.input.readOnly,false);
});
test('confirmed send clears text and advances key without navigating',async()=>{
 const f=fixture(async()=>({ok:true,text:async()=>''}));await f.handlers.submit({preventDefault(){}});
 assert.equal(f.input.value,'');assert.equal(f.fields.submission_id.value,'new-key');assert.equal(f.fields.csrf_token.value,'fresh-token');assert.equal(f.status.textContent,'sent');
});
test('different active scope cannot clear or redirect unsent text',async()=>{
 const f=fixture(async()=>({ok:true,text:async()=>''}));f.next.elements.conversation_context={value:'other'};
 await f.handlers.submit({preventDefault(){}});assert.equal(f.input.value,'Keep this message');assert.equal(f.fields.submission_id.value,'same-key');
});
