const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const controls = [
  {name:'ans1',type:'radio',value:'answer-a',checked:false},
  {name:'ans1',type:'radio',value:'answer-b',checked:true},
  {name:'ans2',type:'text',value:''},
  {name:'ans3',type:'text',value:'Keep changed question'},
  {name:'csrf_token',type:'hidden',value:'fresh-token'}
];
controls.namedItem = name => ({questionId1:{value:'q1'},questionId2:{value:'q2'},questionId3:{value:'new-q3'}})[name];
const draft = {questionId1:'q1',ans1:'answer-a',questionId2:'q2',ans2:'Retained text',questionId3:'old-q3',ans3:'Stale',csrf_token:'expired'};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/js/attempt-draft.js','utf8'), {
  document:{getElementById:id=>id==='form_submittest'?{elements:controls}:{textContent:JSON.stringify(draft)}}
});
assert.equal(controls[0].checked,true); assert.equal(controls[1].checked,false);
assert.equal(controls[2].value,'Retained text');
assert.equal(controls[3].value,'Keep changed question');
assert.equal(controls[4].value,'fresh-token');
console.log('PASS answer drafts restore only matching questions and never overwrite security tokens');
