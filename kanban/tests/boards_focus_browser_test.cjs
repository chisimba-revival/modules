// Real templates/scripts/skin, isolated synthetic page; no application writes.
const {chromium}=require('playwright');
const {execFileSync}=require('node:child_process');
const path=require('node:path'),assert=require('node:assert/strict');
(async()=>{
 const html=execFileSync('php',[path.join(__dirname,'fixtures/board.php')],{encoding:'utf8',env:{...process.env,KANBAN_FIXTURE_HELP:'1'}});
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN||'/usr/bin/google-chrome',args:['--no-sandbox']});
 try{
  const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',r=>{
   const url=new URL(r.request().url());if(url.hostname!=='kanban.test')return r.abort();
   if(url.pathname==='/contextualhelp.js')return r.fulfill({path:path.resolve(__dirname,'../../../framework/app/core_modules/help/resources/contextualhelp.js')});
   const extra={'/ui.css':'core_modules/ui/resources/css/ui.css','/canvas-default.css':'skins/chisimba-reborn/canvases/_default/stylesheet.css','/canvas-kenga.css':'skins/chisimba-reborn/canvases/kenga-learn/stylesheet.css'};
   if(extra[url.pathname])return r.fulfill({path:path.resolve(__dirname,'../../../framework/app',extra[url.pathname])});
   assert.equal(r.request().method(),'GET','Focus must not submit data');
   const assets={'/skin.css':'../../../framework/app/skins/chisimba-reborn/stylesheet.css','/kanban.css':'../resources/kanban.css','/kanban.js':'../resources/kanban.js','/formdrafts.js':'../../../framework/app/core_modules/htmlelements/resources/formdrafts.js'};
   return assets[url.pathname]?r.fulfill({path:path.resolve(__dirname,assets[url.pathname])}):r.fulfill({contentType:'text/html',body:html});
  });
  await page.goto('http://kanban.test/');
  const help=page.locator('[data-contextual-help-open]');
  await help.click();assert.equal(await page.locator('.chisimba-drawer').getAttribute('aria-hidden'),'false');
  await page.keyboard.press('Escape');assert.equal(await help.evaluate(e=>e===document.activeElement),true);
  assert.equal(await page.locator('[data-boards-focus-exit]').isVisible(),false);
  await page.locator('[data-kanban-fullscreen]').click();
  await page.waitForFunction(()=>document.fullscreenElement===document.querySelector('[data-kanban]'));
  assert.notEqual(await page.locator('[data-kanban]').evaluate(e=>getComputedStyle(e).backgroundColor),'rgba(0, 0, 0, 0)');
  await page.locator('[data-kanban-fullscreen]').click();
  await page.waitForFunction(()=>!document.fullscreenElement);
  await page.evaluate(()=>{document.querySelector('[data-kanban]').requestFullscreen=()=>Promise.reject(new Error('Denied'));});
  await page.locator('[data-kanban-fullscreen]').click();
  await page.waitForFunction(()=>!document.querySelector('[data-fullscreen-feedback]').hidden);
  assert.equal(await page.locator('[data-kanban-fullscreen]').getAttribute('aria-pressed'),'false');
  const boards=page.locator('.kanban-board'),first=boards.first(),surface=page.locator('[data-boards-surface]');
  await first.locator('[data-board-toggle]').click();
  await first.locator('[data-task-editor] summary').click();
  const input=first.locator('[data-task-editor] [name=title]');await input.fill('Keep this draft');
  for(const width of [1280,390,360]){
   await page.setViewportSize({width,height:900});
   await page.locator('.kanban-page-header').scrollIntoViewIfNeeded();
   const inset=await first.locator('.kanban-board__summary-actions').evaluate(e=>{
    const edge=e.getBoundingClientRect().right;
    return Math.min(...Array.from(e.children).map(child=>edge-child.getBoundingClientRect().right));
   });
   assert.ok(inset>=8,'Project progress and actions need a right-hand inset');
   const full=await page.locator('[data-kanban-fullscreen]').boundingBox(),focus=await page.locator('[data-boards-focus]').boundingBox();
   assert.equal(full.height,focus.height,'Toolbar buttons must match');
   assert.equal(full.y,focus.y,'View actions must share one row');
   const view=await page.locator('.kanban-view-actions button[type=submit]').boundingBox();assert.equal(view.y,full.y);
   const heading=await page.locator('.kanban-heading-actions h1').boundingBox(),helpBox=await help.boundingBox();
   assert.ok(helpBox.x>=heading.x+heading.width,'Help follows heading');
   assert.ok(focus.x+focus.width<=width,`Toolbar stays within viewport: ${focus.x+focus.width} <= ${width}`);
   assert.equal(await page.locator('[data-boards-focus] .chisimba-icon--search').count(),1);
   if(width===1280){
    if(process.env.KANBAN_TOOLBAR_SCREENSHOT)await page.locator('.kanban-page-header').screenshot({path:process.env.KANBAN_TOOLBAR_SCREENSHOT+'.desktop.png'});
    const archive=await page.locator('.kanban-archive-filter label').boundingBox(),create=await page.locator('.kanban-create summary').boundingBox();
    const scope=await page.locator('#kanban-scope').boundingBox();
    assert.ok(archive.x+archive.width<=scope.x,'Archived filter must precede scope dropdown visually');
    assert.ok(await page.locator('.kanban-archive-filter').evaluate(e=>Boolean(e.compareDocumentPosition(document.querySelector('#kanban-scope'))&Node.DOCUMENT_POSITION_FOLLOWING)),'Keyboard order follows visual order');
    assert.ok(Math.abs(archive.y-full.y)<15&&Math.abs(create.y-full.y)<15,`Archive and Create share toolbar: ${archive.y}, ${create.y}, ${full.y}`);
    assert.ok((await page.locator('.kanban-page-header').boundingBox()).height<150,'Whole desktop header stays compact');
    await page.locator('.kanban-create summary').click();await page.locator('#kanban-new-title').fill('Unsaved new board');
    await page.locator('.kanban-create summary').click();await page.locator('.kanban-create summary').click();
    assert.equal(await page.locator('#kanban-new-title').inputValue(),'Unsaved new board');
    await page.locator('.kanban-create summary').click();
    if(process.env.KANBAN_TOOLBAR_SCREENSHOT)await page.locator('.kanban-page-header').screenshot({path:process.env.KANBAN_TOOLBAR_SCREENSHOT+'.desktop.png'});
   }
   if(width===360&&process.env.KANBAN_TOOLBAR_SCREENSHOT)await page.locator('.kanban-page-header').screenshot({path:process.env.KANBAN_TOOLBAR_SCREENSHOT});
   await page.locator('[data-boards-focus]').click();
   const box=await surface.boundingBox();assert.equal(box.x,0);assert.equal(box.y,0);assert.equal(box.width,width);assert.equal(box.height,900);
   assert.equal(await boards.count(),2);assert.equal(await first.locator('.kanban-board__body').isVisible(),true);
   assert.equal(await boards.nth(1).locator('.kanban-board__body').isVisible(),false);
   assert.equal(await page.locator('.kanban-page-header').evaluate(e=>e.inert),true);
   assert.equal(await page.locator('[data-boards-focus-exit]').evaluate(e=>e===document.activeElement),true);
   await boards.nth(1).locator('[data-board-toggle]').click();
   assert.equal(await boards.nth(1).locator('.kanban-board__body').isVisible(),true);
   await boards.nth(1).locator('[data-board-toggle]').click();
   await page.keyboard.press('Escape');
   assert.equal(await input.inputValue(),'Keep this draft');
   assert.equal(await page.locator('.kanban-page-header').evaluate(e=>e.inert),false);
   assert.equal(await page.locator('[data-boards-focus]').evaluate(e=>e===document.activeElement),true);
  }
  await page.locator('[data-boards-focus]').click();await page.locator('[data-boards-focus-exit]').click();
  await first.locator('[data-board-focus]').click();assert.equal(await first.evaluate(e=>e.classList.contains('chisimba-focus-surface--active')),true);
  assert.equal(await first.locator('[data-board-toggle]').isVisible(),false);
  await first.locator('[data-tasks-toggle]').click();
  assert.equal(await first.locator('.kanban-task__body').isVisible(),false);
  assert.match(await first.locator('[data-tasks-toggle]').innerText(),/Expand all tasks/);
  await first.locator('[data-tasks-toggle]').click();
  assert.equal(await first.locator('.kanban-task__body').isVisible(),true);
  await page.keyboard.press('Escape');assert.equal(await input.inputValue(),'Keep this draft');
  assert.deepEqual(errors,[]);console.log('PASS: all-board focus at desktop/mobile, collapse states, inert surroundings, button/Escape exit, focus return, draft preservation and single-project focus regression');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
