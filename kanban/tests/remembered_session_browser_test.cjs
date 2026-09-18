// Local installed-runtime test. Requires an explicitly provisioned disposable instructor.
const {chromium}=require('playwright'),fs=require('fs'),assert=require('assert/strict');
(async()=>{
 if(process.env.CHISIMBA_RECOVERY_TEST!=='1')throw new Error('Set CHISIMBA_RECOVERY_TEST=1 for the disposable local test');
 const fixture=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN||'/usr/bin/google-chrome',headless:true,args:['--no-sandbox']});
 try {
  const context=await browser.newContext({ignoreHTTPSErrors:true});const page=await context.newPage();
  page.on('dialog',d=>d.accept());
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  const base='https://chisimba.test:8445/ch/index.php';
  const boardTitle='QA remembered session recovery '+Date.now();
  await page.goto(base+'?module=security&action=showlogin');
  await page.locator('[name=username]').fill(fixture.username);await page.locator('[name=password]').fill(fixture.password);
  await page.locator('[name=remember]').check();
  // Respect the login form's minimum completion interval.
  await page.waitForTimeout(2200);
  await page.locator('form').filter({has:page.locator('[name=password]')}).locator('button[type=submit]').click();
  await page.goto(base+'?module=kanban&scope=personal');
  await page.locator('.kanban-create summary').click();
  await page.locator('#kanban-new-title').fill(boardTitle);
  await page.locator('#kanban-new-description').fill('Text entered before the PHP session was lost');
  const oldToken=await page.locator('[data-kanban]').getAttribute('data-csrf');
  async function losePhpSession(){const remember=(await context.cookies()).find(c=>c.name==='chisimba_remember');assert.ok(remember,'Remember me cookie was issued');await context.clearCookies();await context.addCookies([remember]);return remember.value;}
  const originalRemember=await losePhpSession();
  // Submit the still-open form, not a freshly loaded form.
  await page.locator('.kanban-create button[type=submit]').click();
  await page.getByRole('heading',{name:boardTitle,exact:true}).waitFor();
  assert.notEqual((await context.cookies()).find(c=>c.name==='chisimba_remember').value,originalRemember,'remembered credential rotated');
  const board=page.locator('.kanban-board').filter({has:page.getByRole('heading',{name:boardTitle,exact:true})});
  await board.locator('[data-task-create] [name=title]').fill('Task survives a second session loss');
  await losePhpSession();
  await board.locator('[data-task-create] button[type=submit]').click();
  await board.getByRole('heading',{name:'Task survives a second session loss',exact:true}).waitFor();
  const reject=await context.request.post(base+'?module=kanban&action=saveproject',{form:{response:'json',scope:'personal',actor:fixture.id,csrf_token:oldToken,title:'MUST NOT EXIST'}});
  assert.equal((await reject.json()).ok,false,'Old-session form token remains rejected');
  // Logout must revoke the rotated credential, not merely remove PHPSESSION.
  const cookiesBeforeLogout=(await context.cookies()).filter(c=>c.name==='chisimba_remember');
  assert.equal(await page.locator('script[src*="logout-recovery"]').count(),1,'logout recovery asset present');
  await Promise.all([page.waitForURL(url=>url.searchParams.get('module')!=='kanban'),page.getByRole('button',{name:'Logout',exact:true}).click()]);
  await context.clearCookies();await context.addCookies(cookiesBeforeLogout);
  await page.goto(base+'?module=kanban&scope=personal');
  assert.equal(await page.locator('[data-kanban]').count(),0,'Logged-out credential cannot restore identity');
  assert.deepEqual(errors,[]);
  console.log('PASS: real Remember me login; two lost-PHP-session saves from still-open forms; credential rotation; old token denial; logout revocation; no JavaScript errors');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
