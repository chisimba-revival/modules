/* Behavioural checks for Ajax preservation, CSRF renewal and failed updates. */
const fs=require('fs'),vm=require('vm'),assert=require('assert');
async function scenario(ok, saving=false) {
    const listeners={},status={},token={value:'old'},history={replaceWith(){this.replaced=true;}},field={id:'text'},scroller={scrollTop:123,scrollLeft:7};
    let replacement,resolveFetch,synced=false,mounted=false;
    const canvas={parentElement:scroller,querySelectorAll(){return [field];},replaceWith(next){replacement=next;}};
    const next={querySelectorAll(){return [];},addEventListener(){},querySelector(){return null;}};
    const nextForm={elements:{csrf_token:{value:'fresh'},id:{value:'saved-id'},version:{value:'saved-version'}},querySelector(selector){return selector.includes('canvas')?next:{};}};
    const form={dataset:{compositionFailure:'Retained',compositionPending:'Updating',compositionUpdated:'Updated',compositionSaving:'Saving',compositionSaved:'Saved'},elements:{csrf_token:token,id:{value:''},version:{value:'old-version'}},action:'/save',matches(){return true;},hasAttribute(){return saving;},querySelectorAll(){return [];},querySelector(selector){return selector.includes('canvas')?canvas:selector.includes('history')?history:status;},setAttribute(){},removeAttribute(){}};
    const document={addEventListener(name,callback){listeners[name]=callback;},querySelectorAll(){return [];},querySelector(){return null;}};
    class FormData { constructor(received){assert.equal(received,form);assert(synced);} set(key,value){assert.equal(key,saving?'composition_save_ajax':'compose_command');assert.equal(value,saving?'1':'add:text');} }
    const window={fetch:true,scrollX:0,scrollY:200,scrollTo(x,y){assert.equal(y,200);},ChisimbaEditor:{sync(){synced=true;}},ChisimbaEditorMount:async()=>{mounted=true;},ChisimbaEditorUnmount(){assert(ok);}};
    const context={window,document,FormData,AbortController,setTimeout,clearTimeout,URL,innerHeight:900,fetch:()=>new Promise(resolve=>{resolveFetch=resolve;}),DOMParser:class {parseFromString(){return {querySelector(selector){return selector.includes('ajax')?nextForm:ok?null:{textContent:'Invalid block'};}};}}};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/composition.js','utf8'),context);
    let prevented=0;
    const event={target:form,submitter:saving?{name:''}:{name:'compose_command',value:'add:text'},preventDefault(){prevented++;}};
    const running=listeners.submit(event);
    assert(form.inert);assert.equal(status.textContent,saving?'Saving':'Updating');
    await listeners.submit(event);assert.equal(prevented,2); // Do not submit twice while pending.
    resolveFetch({ok,text:async()=>'<response>'});await running;
    assert.equal(token.value,'fresh');assert(!form.inert);assert(!form.dataset.compositionBusy);
    assert.equal(scroller.scrollTop,123);
    if(ok && saving){assert(!replacement);assert(!mounted);assert.equal(form.elements.id.value,'saved-id');assert.equal(form.elements.version.value,'saved-version');assert.equal(status.textContent,'Saved');}
    else if(ok){assert.equal(replacement,next);assert(history.replaced);assert(mounted);assert.equal(status.textContent,'Updated');}
    else {assert(!replacement);assert(!history.replaced);assert(!mounted);assert.equal(status.textContent,'Invalid block');}
}
(async()=>{await scenario(true);await scenario(false);await scenario(true,true);await scenario(false,true);console.log('PASS: Ajax success, rejection preserves original editor, token renewal and concurrent submission guard');})().catch(error=>{console.error(error);process.exit(1);});
