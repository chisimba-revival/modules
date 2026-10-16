const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/../resources/editor.js', 'utf8');
async function scenario(kind, confirmation) {
    const handlers = {}, status = {textContent:''}, button = {disabled:false};
    let requests=0, navigated='', confirmed=true;
    const form={action:'/save',dataset:{return:'/list',failure:'failed',uncertain:'uncertain'},
        draft:'keep my draft', matches:()=>true,reportValidity:()=>true,
        querySelectorAll:()=>[button],setAttribute(){},removeAttribute(){}};
    if(confirmation)form.dataset.confirm='Confirm';
    const context={document:{addEventListener:(name,handler)=>handlers[name]=handler,querySelector:()=>status},
        window:{confirm:()=>confirmed,location:{assign:url=>navigated=url}},
        FormData:class {set(){}},
        fetch:async()=>{requests++;if(kind==='network')throw Error('offline');
            return {ok:kind==='success',redirected:kind==='login',headers:{get:()=>kind==='html'?'text/html':'application/json'},
                json:async()=>({ok:kind==='success',message:'stale draft'})};}};
    vm.runInNewContext(source,context);
    const submit=()=>handlers.submit({target:form,preventDefault(){}});
    if(kind==='cancel')confirmed=false;
    await submit();
    assert.equal(form.draft,'keep my draft');
    if(kind==='success')assert.equal(navigated,'/list');
    else if(kind==='reject'){assert.equal(status.textContent,'stale draft');assert.equal(button.disabled,false);}
    else if(kind==='cancel'){assert.equal(requests,0);assert.equal(button.disabled,false);}
    else {assert.equal(status.textContent,'uncertain');assert.equal(button.disabled,true);await submit();assert.equal(requests,1);}
}
(async()=>{for(const kind of ['success','reject','network','login','html','cancel'])await scenario(kind,kind==='cancel');console.log('PASS native client: draft retention, definite rejection, uncertain-write lockout, login/non-JSON recovery and cancellation');})().catch(e=>{console.error(e);process.exit(1);});
