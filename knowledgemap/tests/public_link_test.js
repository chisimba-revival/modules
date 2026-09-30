// Public-link submission and clipboard regression. Author: Derek Keats
const fs = require('fs'), vm = require('vm'), assert = require('assert/strict');
const source = fs.readFileSync(require('path').join(__dirname, '../resources/knowledgemap.js'), 'utf8');
let requests = [], copied = '', prevented = 0;
const token = {value:'link-token', disabled:false};
const checkbox = {value:'1', checked:true, disabled:false};
const field = {value:'',disabled:false,focus(){},select(){this.selected=true;}};
const feedback = {}, holder = {}, submit = {disabled:false};
const copy = {disabled:false,addEventListener(name,fn){this.click=fn;}};
const selectors = {'[data-knowmap-copy-url]':copy,'[name="response"]':null,'[data-knowmap-public-link-feedback]':feedback,'[data-knowmap-public-link-url]':field,'[data-knowmap-public-link-url-container]':holder,'[data-knowmap-public-link-submit]':submit};
const controls = [token,checkbox,field,submit,copy];
const form = {action:'/index.php?module=knowledgemap&action=save',dataset:{},elements:{csrf_token:token,publiclink:checkbox},querySelector:s=>selectors[s],querySelectorAll:()=>controls,setAttribute(){},removeAttribute(){},addEventListener(name,fn){this.submit=fn;}};
const context = {document:{querySelectorAll:s=>s==='[data-knowmap-public-link-form]'?[form]:[]},navigator:{clipboard:{async writeText(value){copied=value;}}},FormData:class{constructor(){assert.equal(token.disabled,false);this.token=token.value;this.enabled=checkbox.checked;}},fetch:async(url,options)=>{requests.push(options);return {json:async()=>({ok:true,publicUrl:checkbox.checked?'https://example.test/map?token=full-token':null,publicLinkCsrf:'renewed-'+requests.length,message:'Updated'})};}};
vm.runInNewContext(source,context);
(async()=>{
  assert.equal(typeof form.submit,'function');
  await form.submit({preventDefault(){prevented++;},currentTarget:form});
  assert.equal(prevented,1);assert.equal(requests[0].body.token,'link-token');assert.equal(requests[0].headers['X-Requested-With'],'XMLHttpRequest');
  assert.equal(holder.hidden,false);assert.equal(token.value,'renewed-1');
  copy.click();await new Promise(setImmediate);assert.equal(copied,field.value);assert.equal(feedback.textContent,'Link copied.');
  checkbox.checked=false;
  await form.submit({preventDefault(){},currentTarget:form});
  assert.equal(requests[1].body.token,'renewed-1');assert.equal(holder.hidden,true);assert.equal(field.value,'');assert(controls.every(c=>!c.disabled));
  context.navigator.clipboard.writeText=async()=>{throw Error('denied');};copy.click();await new Promise(setImmediate);assert.equal(field.selected,true);
  console.log('PASS: bound AJAX handler, populated request before disabling, repeated token rotation, revocation and clipboard success/failure');
})().catch(e=>{console.error(e);process.exitCode=1;});
