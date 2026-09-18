// Synthetic browser regression. Never connects to an installed Chisimba site.
const {chromium} = require('playwright');
const fs = require('fs'), path = require('path'), assert = require('assert/strict');
(async () => {
 const browser = await chromium.launch({executablePath:process.env.CHROME_BIN || '/usr/bin/google-chrome',headless:true,args:['--no-sandbox']});
 try {
  const page = await browser.newPage();
  page.on('dialog', d => d.accept());
  const errors=[]; page.on('pageerror',e=>errors.push(e.message));
  const fixture=fs.readFileSync(process.argv[2],'utf8');
  let mode='signedout', writes=0, actor='user';
  await page.route('http://kanban.test/**',async route=>{
   const req=route.request(),url=new URL(req.url());
   if(url.searchParams.get('action')==='formtoken') {
    if(mode==='signedout')return route.fulfill({contentType:'text/html',body:'Sign in'});
    return route.fulfill({contentType:'application/json',body:JSON.stringify({ok:true,csrfToken:'renewed'})});
   }
   if(req.method()==='POST') {
    writes++;
    if(mode==='network')return route.abort('connectionfailed');
    return route.fulfill({contentType:'application/json',status:mode==='reject'?403:200,body:JSON.stringify({ok:mode!=='reject',message:mode==='reject'?'Not saved':'Saved',csrfToken:'next'})});
   }
   if(url.pathname==='/formdrafts.js')return route.fulfill({path:path.resolve(__dirname,'../../../framework/app/core_modules/htmlelements/resources/formdrafts.js'),contentType:'application/javascript'});
   if(['/kanban.js','/kanban.css'].includes(url.pathname))return route.fulfill({path:path.join(__dirname,'../resources',url.pathname.slice(1))});
   return route.fulfill({contentType:'text/html',body:fixture.replace('data-actor="user"','data-actor="'+actor+'"')});
  });
  await page.goto('http://kanban.test/');
  const edit=page.locator('.kanban-task__actions details form').first();
  await page.locator('.kanban-task__actions details summary').first().click();
  await edit.locator('[name=notes]').fill('Thirty minutes of irreplaceable notes');
  await edit.locator('button[type=submit]').click();
  await page.waitForFunction(()=>document.querySelector('[data-save-feedback]')?.textContent.includes('login'));
  assert.equal(writes,0,'No write after signed-out preflight');
  assert.equal(await edit.locator('[name=notes]').inputValue(),'Thirty minutes of irreplaceable notes');
  await page.reload();
  assert.equal(await edit.locator('[name=notes]').inputValue(),'Thirty minutes of irreplaceable notes','Draft survives reload');
  assert.equal(await edit.isVisible(),true,'Recovered edit is opened');
  actor='another-user'; await page.reload();
  assert.equal(await edit.locator('[name=notes]').inputValue(),'Keep these notes closed','Different account cannot restore draft');
  actor='user'; await page.reload();
  mode='network';
  await edit.locator('button[type=submit]').click();
  await page.waitForFunction(()=>document.querySelector('[data-save-feedback]')?.textContent.includes('could not be confirmed'));
  assert.equal(writes,1,'Uncertain write is not replayed');
  assert.equal(await edit.locator('[name=notes]').inputValue(),'Thirty minutes of irreplaceable notes');
  mode='reject';
  await edit.locator('button[type=submit]').click();
  await page.waitForFunction(()=>document.querySelector('[data-save-feedback]')?.textContent==='Not saved');
  assert.equal(writes,2);
  mode='success';
  await edit.locator('button[type=submit]').click();
  await page.waitForFunction(()=>!document.querySelector('[data-save-feedback]'));
  assert.equal(writes,3);
  assert.equal(await page.evaluate(()=>Object.keys(sessionStorage).filter(k=>k.startsWith('chisimba:form-draft:')).length),0,'Confirmed save clears recovery copy');
  assert.deepEqual(errors,[]);
  console.log('PASS: signed-out preflight, retained edit, reload recovery, account isolation, network failure without replay, rejected save, confirmed-save cleanup');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
