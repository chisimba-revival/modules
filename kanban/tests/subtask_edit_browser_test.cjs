// Synthetic browser checks against real PHP templates, JS and shared skin. No site/database access.
const {chromium}=require('playwright');
const {execFileSync}=require('node:child_process');
const path=require('node:path'), assert=require('node:assert/strict');
(async()=>{
 const fixture=execFileSync('php',[path.join(__dirname,'fixtures/board.php')],{env:{...process.env,KANBAN_FIXTURE_SUBTASKS:'1'},encoding:'utf8'});
 const viewFixture=execFileSync('php',[path.join(__dirname,'fixtures/board.php')],{env:{...process.env,KANBAN_FIXTURE_SUBTASKS:'1',KANBAN_FIXTURE_VIEW:'1'},encoding:'utf8'});
 assert.ok(!viewFixture.includes('data-subtask-edit'),'View-only boards must not render editors');
 assert.match(viewFixture,/data-subtask-id="e{32}" checked disabled/,'View-only completion must be disabled');
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN||'/usr/bin/google-chrome',headless:true,args:['--no-sandbox']});
 try{
  const page=await browser.newPage({viewport:{width:1280,height:1000}});
  const errors=[],posts=[];let mode='success',saved='First subtask',documents=0;
  page.on('pageerror',e=>errors.push(e.message));page.on('dialog',d=>d.accept());
  const escaped=s=>s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
  await page.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());
   if(url.hostname!=='kanban.test')return route.abort();
   if(url.searchParams.get('action')==='formtoken')return route.fulfill({contentType:'application/json',body:JSON.stringify({ok:true,csrfToken:'fresh'})});
   if(req.method()==='POST'){
    const body=new URLSearchParams(req.postData());posts.push(body);
    await new Promise(resolve=>setTimeout(resolve,80));
    if(mode==='network')return route.abort('connectionfailed');
    if(mode==='conflict')return route.fulfill({status:409,contentType:'application/json',body:JSON.stringify({ok:false,message:'Changed elsewhere; text kept.',csrfToken:'retry'})});
    if(url.searchParams.get('action')==='savesubtask')return route.fulfill({contentType:'application/json',body:JSON.stringify({ok:true,subtask:{id:'1'.repeat(32),title:body.get('title'),completed:false},csrfToken:'next'})});
    if(body.get('subtaskid')==='e'.repeat(32))saved=body.get('title');
    return route.fulfill({contentType:'application/json',body:JSON.stringify({ok:true,message:'Subtask saved.',subtask:{id:body.get('subtaskid'),title:body.get('title')},csrfToken:'next'})});
   }
   const assets={'/kanban.js':path.join(__dirname,'../resources/kanban.js'),'/kanban.css':path.join(__dirname,'../resources/kanban.css'),'/skin.css':path.resolve(__dirname,'../../../framework/app/skins/chisimba-reborn/stylesheet.css'),'/formdrafts.js':path.resolve(__dirname,'../../../framework/app/core_modules/htmlelements/resources/formdrafts.js')};
   if(assets[url.pathname])return route.fulfill({path:assets[url.pathname],contentType:url.pathname.endsWith('.js')?'application/javascript':'text/css'});
   if(req.isNavigationRequest())documents++;
   return route.fulfill({contentType:'text/html',body:fixture.replaceAll('First subtask',escaped(saved))});
  });
  await page.goto('http://kanban.test/');
  const board=page.locator('.kanban-board').first(),task=board.locator('.kanban-task');
  await board.locator('[data-board-toggle]').click();
  const taskEditor=task.locator('[data-task-editor]');
  await taskEditor.locator('summary').click();
  const title=taskEditor.locator('[name=title]');await title.fill('Unsaved task draft');
  for(const width of [1280,390]){
   await page.setViewportSize({width,height:1000});
   const form=await taskEditor.locator('form').boundingBox(),actions=await task.locator('.kanban-task__actions').boundingBox(),move=await task.locator('[data-task-move]').boundingBox();
   assert.ok(Math.abs(form.width-actions.width)<2,'Editor must use full action-row width');
   assert.ok(move.y>=form.y+form.height,'Move buttons must flow below editor');
   const input=await title.boundingBox(),label=await taskEditor.locator('label').first().boundingBox();
   assert.ok(input.y-(label.y+label.height)>=4,'Title label needs a gap');
   const save=await taskEditor.locator('button[type=submit]').boundingBox(),notice=await taskEditor.locator('.chisimba-notice').boundingBox();
   assert.ok(notice.y-(save.y+save.height)>=8,'Draft notice needs breathing room');
   assert.ok(input.x+input.width<=form.x+form.width,'Input must not overflow');
  }
  if(process.env.KANBAN_SCREENSHOT)await task.screenshot({path:process.env.KANBAN_SCREENSHOT});
  const rows=task.locator('[data-subtask-row]'),first=rows.nth(0),second=rows.nth(1);
  await first.locator('summary').focus();await page.keyboard.press('Enter');
  const edit=first.locator('[data-subtask-edit]');
  await edit.locator('[name=title]').fill('<b>Renamed safely</b>');
  await edit.locator('button[type=submit]').click();
  await edit.evaluate(form=>form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true})));
  await page.waitForFunction(()=>document.querySelector('[data-subtask-title]').textContent==='<b>Renamed safely</b>');
  await page.waitForFunction(()=>!document.querySelector('[data-subtask-edit]').dataset.saving);
  assert.equal(posts.length,1);assert.equal(posts[0].get('csrf_token'),'fresh');assert.equal(posts[0].get('original_title'),'First subtask');
  assert.equal(await first.locator('[data-subtask-id]').isChecked(),true);
  assert.equal(await first.locator('[data-subtask-title] b').count(),0);
  assert.equal(await title.inputValue(),'Unsaved task draft');assert.equal(documents,1);
  await second.locator('summary').click();await second.locator('[name=title]').fill('Second independent draft');
  mode='conflict';await edit.locator('[name=title]').fill('Keep conflicted draft');await edit.locator('button[type=submit]').click();
  await page.waitForFunction(()=>document.querySelector('[data-subtask-edit-feedback]').textContent.includes('Changed elsewhere'));
  assert.equal(await edit.locator('[name=title]').inputValue(),'Keep conflicted draft');
  await page.reload();
  assert.equal(await edit.locator('[name=title]').inputValue(),'Keep conflicted draft');
  assert.equal(await second.locator('[name=title]').inputValue(),'Second independent draft');
  assert.equal(await edit.isVisible(),true);
  mode='network';await edit.locator('button[type=submit]').click();
  await page.waitForFunction(()=>document.querySelector('[data-subtask-edit-feedback]').textContent.includes('could not be confirmed'));
  assert.equal(posts.length,3,'No automatic retry');
  await edit.locator('[data-subtask-cancel]').click();
  assert.equal(await edit.locator('[name=title]').inputValue(),saved);
  assert.equal(await first.locator('summary').evaluate(el=>el===document.activeElement),true);
  assert.equal(await second.locator('[name=title]').inputValue(),'Second independent draft');
  mode='success';const add=task.locator('[data-subtask-create]');await add.locator('[name=title]').fill('New editable subtask');await add.locator('button').click();
  await page.waitForFunction(()=>document.querySelectorAll('[data-subtask-row]').length===3);
  const added=rows.nth(2);await added.locator('summary').click();await added.locator('[name=title]').fill('Edited immediately');await added.locator('button[type=submit]').click();
  await page.waitForFunction(()=>Array.from(document.querySelectorAll('[data-subtask-title]')).some(el=>el.textContent==='Edited immediately'));
  assert.equal(documents,2,'Only explicit reload should navigate');assert.deepEqual(errors,[]);
  console.log('PASS: desktop/mobile full-width editor and spacing; keyboard edit, Ajax, XSS safety, completion, duplicate guard, conflicts, independent draft recovery, cancel/focus and immediate editing of new subtasks');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
