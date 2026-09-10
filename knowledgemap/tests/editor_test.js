/** Behaviour regressions for chapter ordering and movement. @author Derek Keats */
const fs = require('fs'), vm = require('vm'), assert = require('assert');
let source = fs.readFileSync(__dirname + '/../resources/knowledgemap.js', 'utf8').replace('    boot();', '    globalThis.KnowledgeMap = ReadOnlyKnowledgeMap;');
const context = {document: {}, window: {crypto: {randomUUID: () => 'newnode'}}, crypto: {randomUUID: () => 'newnode'}, Map, Set};
vm.createContext(context); vm.runInContext(source, context);
const proto = context.KnowledgeMap.prototype;
function fixture() {
 const map = Object.create(proto);
 map.graph = {rootId:'root', nodes: ['root','a','b','c','d'].map(id => ({id,title:id,presentation:{}})), relationships:['a','b','c','d'].map((id,order) => ({type:'contains',from:'root',to:id,order}))};
 map.nodes = new Map(map.graph.nodes.map(n=>[n.id,n])); map.reindex(); map.pinBranchSides();
 map.positions = new Map(); map.viewport = {clientWidth:900,clientHeight:600,scrollTop:0,scrollLeft:0}; map.lines = {style:{},setAttribute(){}}; map.nodeLayer = {style:{},querySelector(){},querySelectorAll(){return []}}; map.zoom=1;
 map.render = ()=>{}; map.select = n=>{map.selectedId=n.id}; map.dirty = ()=>{}; map.layout(); return map;
}
let m=fixture(); m.selectedId='d'; m.moveSelected(-1);
assert.deepEqual(Array.from(m.children.get('root')),['a','d','b','c']);
assert(m.positions.get('d').y < m.positions.get('b').y); assert.equal(m.nodes.get('b').presentation.side,'left');
m.addSibling('d'); assert.deepEqual(Array.from(m.children.get('root')),['a','d','node-newnode','b','c']);
assert.equal(m.nodes.get('node-newnode').presentation.side,'left');
assert(m.graph.relationships.every(r=>r.order<2147483647));
m=fixture(); let root=m.positions.get('root'); m.placeMainBranch('c',root.x-400,0); m.layout();
assert.equal(m.nodes.get('c').presentation.side,'left'); assert(m.positions.get('c').y<m.positions.get('b').y);
assert.equal(m.nodes.get('a').presentation.side,'right');
m.nodes.get('b').presentation.collapsed=true; m.nodes.get('a').presentation.offsetX=150;
m.reparent('a','b'); assert.equal(m.parentOf('a'),'b'); assert.equal(m.nodes.get('b').presentation.collapsed,false); assert.equal(m.nodes.get('a').presentation.offsetX,undefined); assert(m.positions.has('a'));
m.reparent('b','a'); assert.equal(m.parentOf('b'),'root');
m.reparent('root','a'); assert.equal(m.parentOf('root'),null);
m.draggedId='a'; m.clearDrag(); assert.equal(m.draggedId,null);
console.log('PASS: same-side reorder, stable sides, sibling insertion, bounded ranks, non-overlapping chapter drop, expanded reparent target, cycle/root rejection, drag cleanup');

// Apply unrelated details without replacing an explicitly chosen decorative icon.
context.document.addEventListener = ()=>{};
m=fixture(); m.selectedId='a';
let submit;
const form={elements:{title:{value:'Edited chapter'},description:{value:'Notes',addEventListener(){}},color:{value:'#f3f8ef',addEventListener(){}},side:{value:'right',disabled:false},mediaIntent:{value:'create-video'}},addEventListener(name,handler){if(name==='submit')submit=handler}};
m.root={querySelectorAll(){return []},querySelector(selector){return selector==='[data-knowmap-node-form]'?form:null}};
m.editable=true;m.nodes.get('a').presentation.mediaIntent='create-video';m.nodes.get('a').presentation.icon='lucide:star';m.bind();submit({preventDefault(){}});
assert.equal(m.nodes.get('a').presentation.icon,'lucide:star');
form.elements.mediaIntent.value='use-text';submit({preventDefault(){}});
assert.equal(m.nodes.get('a').presentation.icon,'lucide:file-text');
form.elements.mediaIntent.value='';submit({preventDefault(){}});
assert.equal(m.nodes.get('a').presentation.mediaIntent,undefined);
console.log('PASS: applying details retains decoration, changing purpose selects its icon, clearing purpose removes semantics');

// Control network completion to exercise overlapping saves and edits in flight.
(async function(){
 const map=fixture();let requests=[],states=[];
 context.FormData=class{constructor(){this.values={}} append(k,v){this.values[k]=v}};
 context.fetch=(url,options)=>new Promise(resolve=>requests.push({url,options,resolve}));
 map.root={dataset:{knowmapSaveUrl:'/save',knowmapCsrf:'token-1'}};
 map.graph.map={id:'fixture',revision:1};map.setSaveState=(state,message)=>states.push(state);
 map.dirty=proto.dirty;map.dirty();map.save();map.save();
 assert.equal(requests.length,1);assert.equal(states.at(-1),'saving');
 map.nodes.get('a').title='Edited during save';map.dirty();
 const complete=async(body)=>{requests.at(-1).resolve({status:200,json:()=>Promise.resolve(body)});await new Promise(resolve=>setImmediate(resolve));};
 await complete({ok:true,revision:2,csrfToken:'token-2'});
 assert.equal(map.graph.map.revision,2);assert.equal(states.at(-1),'unsaved');
 map.save();assert.equal(requests.length,2);assert.equal(requests[1].options.body.values.revision,2);
 assert.equal(requests[1].options.body.values.csrf_token,'token-2');
 assert.equal(JSON.parse(requests[1].options.body.values.document).nodes.find(n=>n.id==='a').title,'Edited during save');
 await complete({ok:true,revision:3,csrfToken:'token-3'});assert.equal(states.at(-1),'saved');
 map.save();await complete({ok:false,message:'Conflict',csrfToken:'token-4'});
 assert.equal(states.at(-1),'error');assert.equal(map.saving,false);assert.equal(map.root.dataset.knowmapCsrf,'token-4');
 console.log('PASS: saves serialise, pending edits remain unsaved, next save uses fresh revision/token, failures retain error state');
})().catch(error=>{console.error(error);process.exitCode=1});

// Cancellation must make a subsequent drop inert, including window focus loss.
{
 const map=fixture(),events={},globalEvents={},windowEvents={};
 const classes=new Set(['is-panning']);
 map.viewport.addEventListener=(name,fn)=>events[name]=fn;
 map.viewport.classList={add:name=>classes.add(name),remove:name=>classes.delete(name)};
 map.viewport.hasPointerCapture=()=>false;
 context.document.addEventListener=(name,fn)=>globalEvents[name]=fn;
 context.window.addEventListener=(name,fn)=>windowEvents[name]=fn;
 map.editable=true;map.bindCanvas();map.draggedId='a';map.dragAnchor={x:10,y:20};
 globalEvents.keydown({key:'Escape'});
 assert.equal(map.draggedId,null);assert.equal(map.dragAnchor,null);
 const before=JSON.stringify(map.graph);
 events.drop({target:{closest:()=>null},preventDefault(){throw new Error('Cancelled drop was processed');}});
 assert.equal(JSON.stringify(map.graph),before);
 map.panning={};map.draggedId='b';windowEvents.blur();
 assert.equal(map.panning,null);assert.equal(map.draggedId,null);assert(!classes.has('is-panning'));
 map.panning={};events.lostpointercapture({pointerId:1});assert.equal(map.panning,null);
 console.log('PASS: Escape cancels later drops, window blur clears movement, lost capture ends panning');
}

// Immediate colour and visibility updates are repeatable and state-driven.
let coloured=fixture();coloured.selectedId='a';let dirtyCount=0;coloured.dirty=()=>dirtyCount++;
coloured.setNodeColour('#123456');coloured.setNodeColour('#abcdef');
assert.equal(coloured.nodes.get('a').presentation.color,'#abcdef');assert.equal(dirtyCount,2);
coloured.setNodeColour('invalid');assert.equal(dirtyCount,2);
let icons=[{dataset:{knowmapVisibilityIcon:'show'}},{dataset:{knowmapVisibilityIcon:'hide'}}],expanded;
let disclosure={setAttribute(key,value){expanded=value},querySelectorAll(){return icons}};
coloured.setVisibilityControl(disclosure,true);assert.equal(expanded,'false');assert.equal(icons[0].hidden,false);assert.equal(icons[1].hidden,true);
coloured.setVisibilityControl(disclosure,false);assert.equal(expanded,'true');assert.equal(icons[0].hidden,true);assert.equal(icons[1].hidden,false);
console.log('PASS: repeated immediate colour changes and both disclosure icon states');

// Link creation keeps the selected parent and opens its URL field without adding a note node.
m=fixture(); m.selectedId='a'; m.labels={link_node:'New link'}; let openedField=''; m.openNodeField=field=>{openedField=field}; m.addLinkNode();
assert.equal(m.parentOf(m.selectedId),'a'); assert.equal(m.nodes.get(m.selectedId).type,'reference'); assert.equal(openedField,'link');
assert.equal(m.nodes.get(m.selectedId).presentation.icon,'lucide:link-2');
console.log('PASS: link node is a child reference with URL editing');

// Copy/paste clones the complete branch, remaps internal relations and leaves its source intact.
let sequence=0; context.crypto.randomUUID=()=>String(++sequence);
m=fixture(); m.root={querySelector(){return null}}; m.labels={}; m.selectedId='root';
m.nodes.get('a').description='Attached note'; m.nodes.get('a').presentation.icon='file:personal-image';
m.graph.relationships.push({id:'url',type:'links_to',from:'a',to:'',externalTarget:'https://example.org',properties:{target:'_blank'},order:4});
m.copyBranch(); m.nodes.get('a').description='Source edited after copying'; m.selectedId='b'; m.pasteBranch();
let pasted=m.selectedId, copiedChildren=m.children.get(pasted);
assert.equal(copiedChildren.length,4); assert.equal(m.parentOf(pasted),'b');
assert.equal(m.nodes.get(copiedChildren[0]).description,'Attached note');
assert.equal(m.nodes.get(copiedChildren[0]).presentation.icon,'file:personal-image');
assert(m.graph.relationships.some(r=>r.from===copiedChildren[0]&&r.externalTarget==='https://example.org'));
assert.equal(new Set(m.graph.nodes.map(n=>n.id)).size,10);
m.pasteBranch(); assert.equal(new Set(m.graph.nodes.map(n=>n.id)).size,15);
assert.equal(m.nodes.get('a').description,'Source edited after copying');
console.log('PASS: immutable branch copies, unique repeated pastes, notes/icons/links and source preservation');

m=fixture(); m.selectedId='a'; m.addChild('a'); let descendant=m.selectedId;
m.moveBranchSide('left'); assert.equal(m.nodes.get('a').presentation.side,'left'); assert.equal(m.parentOf(descendant),'a');
m.moveBranchSide('right'); assert.equal(m.nodes.get('a').presentation.side,'right');
assert.equal(m.selectedId,descendant);
console.log('PASS: moving a selected descendant changes its whole chapter side without reparenting');
