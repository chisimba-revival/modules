import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
const source = readFileSync(new URL('../resources/admin.js', import.meta.url), 'utf8');
function fixture() {
    const handlers = {}, windowHandlers = {}, statuses = [{}, {}];
    const button = {disabled:false,innerHTML:'Upload and save photos'};
    const field = {disabled:false,value:'Two selected photos'};
    const disabled = {disabled:true};
    const elements = [button,field,disabled]; elements.csrf_token = {value:'original'};
    const progress = {hidden:true}, attrs = {};
    let complete, destination, reset = false, submittedBeforeDisabled = false;
    const form = {
        action:'https://example.test/?module=grassguide&action=uploadphotos', dataset:{}, elements,
        addEventListener:(type,fn)=>{handlers[type]=fn;},
        querySelectorAll:selector=>selector==='[data-save-status]'?statuses:selector==='button[type="submit"]'?[button]:[],
        querySelector:()=>progress,
        setAttribute:(name,value)=>{attrs[name]=value;}, removeAttribute:name=>{delete attrs[name];},
        reset:()=>{reset=true;}
    };
    class FormData {
        constructor(){submittedBeforeDisabled=!field.disabled;}
        *[Symbol.iterator](){yield ['captions',field.value];}
        set(){} get(){return 'species';} has(){return false;}
    }
    runInNewContext(source, {
        URL,FormData,
        document:{
            getElementById:()=>({dataset:{uploadBusy:'Uploading and saving',unsaved:'Unsaved',failed:'Failed',uploadInvalid:'Check fields'}}),
            querySelectorAll:selector=>selector==='[data-grass-form]'?[form]:[]
        },
        window:{addEventListener:(type,fn)=>{windowHandlers[type]=fn;},location:{assign:url=>{destination=url;}}},
        fetch:()=>new Promise(resolve=>{complete=resolve;})
    });
    const dirty=()=>{let blocked=false;windowHandlers.beforeunload({preventDefault(){blocked=true;}});return blocked;};
    return {handlers,form,field,button,disabled,progress,statuses,attrs,dirty,
        submit:()=>handlers.submit({preventDefault(){}}),
        respond:ok=>complete({ok,json:async()=>({ok,message:ok?'Saved':'Caption mismatch',csrf:'renewed',record:{id:'species'}})}),
        state:()=>({reset,destination,submittedBeforeDisabled})};
}
test('pending upload shows busy feedback, protects work, then clears it only on success',async()=>{
    const f=fixture();f.field.value='Edited caption';f.handlers.change();assert.equal(f.dirty(),true);
    const request=f.submit();
    assert.equal(f.state().submittedBeforeDisabled,true);
    assert.equal(f.field.disabled,true);assert.equal(f.button.disabled,true);
    assert.equal(f.progress.hidden,false);assert.equal(f.attrs['aria-busy'],'true');
    assert.equal(f.button.textContent,'Uploading and saving');
    assert.deepEqual(f.statuses.map(s=>s.textContent),['Uploading and saving','Uploading and saving']);
    f.handlers.change();assert.equal(f.statuses[0].textContent,'Uploading and saving');
    assert.equal(f.dirty(),true);
    f.respond(true);await request;
    assert.equal(f.dirty(),false);assert.equal(f.state().reset,true);
    assert.equal(new URL(f.state().destination).searchParams.get('action'),'edit');
    assert.equal(f.button.disabled,true); // Cannot resubmit while navigation is pending.
});
test('rejected upload restores controls and preserves unsaved work with visible errors',async()=>{
    const f=fixture();f.field.value='Edited caption';f.handlers.input();const request=f.submit();f.respond(false);await request;
    assert.equal(f.field.disabled,false);assert.equal(f.disabled.disabled,true);
    assert.equal(f.button.disabled,false);assert.equal(f.progress.hidden,true);
    assert.equal(f.form.elements.csrf_token.value,'renewed');
    assert.equal(f.state().reset,false);assert.equal(f.state().destination,undefined);
    assert.equal(f.dirty(),true);assert.deepEqual(f.statuses.map(s=>s.textContent),['Caption mismatch','Caption mismatch']);
    f.handlers.invalid();assert.equal(f.statuses[1].textContent,'Check fields');
});

test('undoing upload edits clears the warning without losing remaining changes',()=>{
    const f=fixture(), original=f.field.value;
    f.field.value='Edited caption';f.handlers.change();assert.equal(f.dirty(),true);
    f.field.value=original;f.handlers.change();assert.equal(f.dirty(),false);
});
