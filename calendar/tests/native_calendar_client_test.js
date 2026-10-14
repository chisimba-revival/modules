const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const personal=[{hidden:false},{hidden:false}],site=[{hidden:false},{hidden:false}];
const boxes={userbox:{checked:true},sitebox:{checked:false}};
for(const box of Object.values(boxes))box.addEventListener=(name,fn)=>box.change=fn;
const root={querySelector:s=>boxes[s.slice(1)],querySelectorAll:s=>s==='.event_user'?personal:site};
const document={readyState:'complete',getElementById:id=>id==='widecalendar'?root:null};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/calendar.js','utf8'),{document});
assert(personal.every(e=>!e.hidden));assert(site.every(e=>e.hidden));boxes.userbox.checked=false;boxes.userbox.change();assert(personal.every(e=>e.hidden));boxes.userbox.change();assert(personal.every(e=>e.hidden),'change applies checked state rather than blindly toggling');boxes.userbox.checked=true;boxes.userbox.change();assert(personal.every(e=>!e.hidden));assert(site.every(e=>e.hidden),'other categories unchanged');
console.log('PASS grid/list filtering, initial checked state, repeated changes and missing categories');
