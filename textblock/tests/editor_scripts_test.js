/* Isolated behaviour checks for the actual native editor scripts. No browser/site access. */
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
async function exercise(module, outcome, valid = true) {
    let ready, submit, destination, calls = 0;
    const button = {disabled:false};
    const status = {dataset:{failure:'Draft retained'},textContent:'',setAttribute(){}};
    const form = {dataset:{},action:'https://example.test/index.php?module=textblock&action=save&mode=add',
        elements:{revision:{value:''},id:{value:''},mode:{value:'add'},page:{value:'1'},title:{value:'Draft'},blockid:{value:'widetext1'},csrf_token:{value:'synthetic'}},
        reportValidity(){return valid;},querySelector(){return button;},querySelectorAll(){return [];},addEventListener(event, callback){submit=callback;}};
    const context = {document:{addEventListener(event,callback){ready=callback;},getElementById(id){return id.startsWith('form_')?form:status;}},
        URL, FormData:class {constructor(value){this.value=value;}},location:{href:'https://example.test/',assign(url){destination=url;}},
        status_success:'Saved',status_fail:'Draft retained', fetch:async(url,options)=>{
            calls++;assert.equal(options.method,'POST');assert.equal(options.body.value.elements.csrf_token.value,'synthetic');
            if(outcome==='network')throw new Error('Network error');
            return {ok:outcome!=='reject',redirected:outcome==='login',headers:{get(){return outcome==='reject'?'text/plain':'application/json';}},json:async()=>({ok:true,id:'saved-id',revision:'a'.repeat(64)}),text:async()=>outcome==='reject'?'Denied':'saved-id'};
        }};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname,'..','..',module,'resources',module==='textblock'?'editblock.js':'editor.js'),'utf8'),context);
    ready();await submit({preventDefault(){}});
    assert.equal(button.disabled,false);
    assert.equal(form.elements.title.value,'Draft');
    if(!valid){assert.equal(calls,0);return;}
    assert.equal(calls,1);
    if(outcome==='success') {
        if(module==='textblock'){const url=new URL(destination);assert.equal(url.searchParams.get('action'),'widetext');assert.equal(url.searchParams.has('mode'),false);}
        else {assert.equal(form.elements.id.value,'saved-id');assert.equal(form.elements.mode.value,'edit');assert.equal(form.elements.page.readOnly,true);}
    } else {assert.equal(destination,undefined);assert.equal(form.elements.mode.value,'add');assert.notEqual(status.textContent,'');}
}
(async()=>{for(const module of ['textblock','slate']){for(const outcome of ['success','reject','network','login'])await exercise(module,outcome);await exercise(module,'success',false);}console.log('PASS: native editor success, rejection, login redirect, network recovery, required validation and CSRF form data');})().catch(error=>{console.error(error);process.exitCode=1;});
