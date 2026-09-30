import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const script=fs.readFileSync(new URL('../resources/kanban.js',import.meta.url),'utf8');
const body=script.slice(script.indexOf('    function savePublicLink('),script.indexOf('    function refreshBoardCounts('));
assert(body.includes('function savePublicLink('));
for(const checked of [true,false]) {
    const controls=[{name:'boardid',value:'fixture-board',disabled:false},{name:'csrf_token',value:'fixture-token',disabled:false},{name:'publiclink',value:'1',checked,disabled:false}];
    const nodes={'[data-public-link-feedback]':{},'[data-public-link-url-container]':{},'[data-public-link-url]':{},'[data-public-link-submit]':{}};
    const form={dataset:{},action:'index.php?module=kanban&action=savepubliclink',elements:{publiclink:controls[2]},querySelectorAll:()=>controls,querySelector:key=>nodes[key],setAttribute(){},removeAttribute(){}};
    let submitted;
    class FormData {
        constructor(){this.rows=controls.filter(c=>!c.disabled&&(c.name!=='publiclink'||c.checked)).map(c=>[c.name,c.value]);}
        [Symbol.iterator](){return this.rows[Symbol.iterator]();}
    }
    const context={FormData,URLSearchParams,post(action,payload,success){submitted=payload;success({publicUrl:checked?'https://example.invalid/shared':null,message:'Saved'});return Promise.resolve();}};
    vm.runInNewContext(body+'\nsavePublicLink',context)(form);
    await Promise.resolve();
    assert.equal(submitted.get('boardid'),'fixture-board','Board identity must be captured before disabling controls');
    assert.equal(submitted.get('csrf_token'),'fixture-token');
    assert.equal(submitted.get('publiclink'),checked?'1':null);
    assert(controls.every(c=>!c.disabled));
    assert.equal(form.dataset.saving,undefined);
    assert.equal(nodes['[data-public-link-url-container]'].hidden,!checked);
}
console.log('PASS: public-link create/revoke payloads, restored controls and inline feedback');
