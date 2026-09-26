/** Real clipboard and save/reload regression. Uses a disposable local account/map.
 * KM_TEST_BASE_URL: local index.php URL; KM_TEST_ACCOUNT: private JSON {username,password}.
 * KM_TEST_RESULT: private JSON output containing the created map ID for cleanup.
 * Source assets are routed from this checkout; PHP persistence uses the real installation.
 * Requires playwright-core and Chrome. Never run against production. @author Derek Keats
 */
const {chromium}=require('playwright-core');
const fs=require('fs'), path=require('path'), assert=require('assert');
(async()=>{
 const base=process.env.KM_TEST_BASE_URL, account=JSON.parse(fs.readFileSync(process.env.KM_TEST_ACCOUNT));
 assert(base && ['chisimba.test','localhost','127.0.0.1'].includes(new URL(base).hostname),'Local test installation required');
 const watchdog=setTimeout(()=>{console.error('Browser regression timed out');process.exit(1)},120000);
 const browser=await chromium.launch({executablePath:process.env.CHROME_PATH||'/usr/bin/google-chrome',headless:true,args:['--no-sandbox','--host-resolver-rules=MAP chisimba.test 127.0.0.1']});
 try {
  const context=await browser.newContext({ignoreHTTPSErrors:true,permissions:['clipboard-read','clipboard-write'],viewport:{width:1440,height:1000}});
  const page=await context.newPage(), errors=[];page.on('pageerror',e=>errors.push(e.message));
  for(const asset of ['knowledgemap.js','knowledgemap.css']) await page.route('**/'+asset+'*',r=>r.fulfill({path:path.join(__dirname,'../resources',asset),contentType:asset.endsWith('.js')?'application/javascript':'text/css'}));
  const url=base+'?module=knowledgemap&scope=personal';
  await page.goto(url);
  await page.locator('[name=username]').fill(account.username);await page.locator('[name=password]').fill(account.password);
  await page.waitForTimeout(1100);await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.getByRole('button',{name:'Login',exact:true}).click()]);
  await page.goto(url);await page.getByText('Create a knowledge map',{exact:true}).click();
  await page.locator('#knowmap-title').fill('QA plain paste 20260926');await page.getByRole('button',{name:'Create map',exact:true}).click();
  await page.waitForURL(u=>u.searchParams.get('action')==='view');
  const mapid=new URL(page.url()).searchParams.get('mapid');fs.writeFileSync(process.env.KM_TEST_RESULT,JSON.stringify({mapid}),{mode:0o600});
  await page.locator('[data-knowmap-action="add-child"]').click();
  const node=page.locator('.knowmap-node').filter({has:page.locator('.knowmap-node__title', {hasText:'New idea'})});
  const title=node.locator('.knowmap-node__title');await title.waitFor();console.log('Created disposable map and node');
  const nodeId=await node.getAttribute('data-node-id');
  async function selectAll(){await title.click();await page.keyboard.press('Control+a');}
  async function paste(text,html='<p style="position:absolute;line-height:0"><b>Office formatting</b></p>'){
   await page.evaluate(async({text,html})=>{await navigator.clipboard.write([new ClipboardItem({'text/plain':new Blob([text],{type:'text/plain'}),'text/html':new Blob([html],{type:'text/html'})})])},{text,html});
   console.log('Clipboard written');await page.keyboard.press('Control+v');console.log('Paste dispatched');
  }
  // The same clipboard supplies both rich LibreOffice-like markup and plain text.
  const text='Understand and be able to discuss the\norigins of African savannas and the role\nof grasses in them';
  await selectAll();console.log('Title selected');await paste(text);
  const fixed=page.locator('[data-node-id="'+nodeId+'"] .knowmap-node__title');
  assert.equal(await fixed.innerText(),text);assert.equal(await fixed.locator('[style],b,p,table,font').count(),0);
  await page.keyboard.press('Control+z');assert.equal(await fixed.innerText(),'New idea');
  await page.keyboard.press('Control+Shift+z');assert.equal(await fixed.innerText(),text);
  await page.locator('[data-knowmap-action="save"]').click();await page.waitForFunction(()=>document.querySelector('[data-knowmap-save-status]').textContent==='Saved');
  await page.reload();assert.equal(await fixed.innerText(),text);
  assert.equal(await fixed.locator('*').count(),0);
  const geometry=await fixed.evaluate(el=>({height:el.getBoundingClientRect().height,line:parseFloat(getComputedStyle(el).lineHeight),width:el.clientWidth,scroll:el.scrollWidth}));
  assert(geometry.height>geometry.line*2);assert(geometry.scroll<=geometry.width+1);
  // Replace only a selected word, preserving surrounding text and interpreting markup literally.
  await fixed.click();await fixed.evaluate(el=>{const r=document.createRange(),t=el.firstChild;let start=t.textContent.indexOf('African');r.setStart(t,start);r.setEnd(t,start+7);let s=getSelection();s.removeAllRanges();s.addRange(r)});
  await paste('southern African <birds> & grasses');
  const revised=text.replace('African','southern African <birds> & grasses');assert.equal(await fixed.innerText(),revised);assert.equal(await fixed.locator('birds').count(),0);
  // Persist the replacement and verify it through the real server.
  await page.locator('[data-knowmap-action="save"]').click();await page.waitForFunction(()=>document.querySelector('[data-knowmap-save-status]').textContent==='Saved');await page.reload();assert.equal(await fixed.innerText(),revised);
  // An HTML-only clipboard must not delete text or inject markup.
  await fixed.click();await fixed.evaluate(el=>{const d=new DataTransfer();d.setData('text/html','<img src=x onerror=alert(1)>');el.dispatchEvent(new ClipboardEvent('paste',{clipboardData:d,bubbles:true,cancelable:true}));});
  assert.equal(await fixed.innerText(),revised);
  // Exercise the Range fallback without interpreting tags or moving the caret outside the title.
  await fixed.evaluate(el=>{const r=document.createRange();r.selectNodeContents(el);r.collapse(false);const s=getSelection();s.removeAllRanges();s.addRange(r);const exec=document.execCommand;document.execCommand=()=>false;try{const d=new DataTransfer();d.setData('text/plain',' & conservation');el.dispatchEvent(new ClipboardEvent('paste',{clipboardData:d,bubbles:true,cancelable:true}));}finally{document.execCommand=exec}});
  assert.equal(await fixed.innerText(),revised+' & conservation');
  await page.locator('[data-knowmap-action="save"]').click();await page.waitForFunction(()=>document.querySelector('[data-knowmap-save-status]').textContent==='Saved');await page.reload();assert.equal(await fixed.innerText(),revised+' & conservation');
  if(process.env.KM_TEST_SCREENSHOT)await page.screenshot({path:process.env.KM_TEST_SCREENSHOT.replace('.png','-desktop.png'),fullPage:true});
  await page.setViewportSize({width:768,height:1024});await page.locator('[data-knowmap-action="panel"]').click();await page.locator('[data-knowmap-zoom="fit"]').click();
  assert(await fixed.isVisible());
  if(process.env.KM_TEST_SCREENSHOT)await page.screenshot({path:process.env.KM_TEST_SCREENSHOT,fullPage:true});
  assert.deepEqual(errors,[]);
  console.log('PASS: native rich clipboard paste, multiline plain text, selection replacement, literal markup, undo/redo, wrapping, real save/reload, Range fallback, HTML-only rejection and tablet viewport');
 } finally {clearTimeout(watchdog);await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
