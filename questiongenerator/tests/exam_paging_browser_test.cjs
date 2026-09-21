const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
 const browser = await chromium.launch({executablePath:'/usr/bin/google-chrome',headless:true,args:['--no-sandbox']});
 try {
  const page = await browser.newPage();
  const picker = n => `<details open data-exam-picker data-load-error="Failed"><summary>Chapters ${n}</summary><p data-page-status hidden></p><nav data-exam-pagination><a href="?action=examview&id=e&page=${n+1}">Next</a></nav></details>`;
  let fail=false;
  await page.route('https://exam.test/**', route => route.fulfill({status:fail?500:200,contentType:'text/html',body:picker(2)}));
  await page.goto('https://exam.test/?action=examview&id=e&page=1');
  await page.setContent(`<form data-exam-dirty data-unsaved="Unsaved"><input name="page" value="1"><input name="title" value="Saved"><input type="checkbox" name="selected[]"></form>${picker(1)}`);
  await page.addScriptTag({content:fs.readFileSync(require('path').join(__dirname,'../resources/exams.js'),'utf8')});
  await page.locator('[name=title]').fill('Keep my draft');
  await page.locator('[type=checkbox]').check();
  await page.getByText('Next',{exact:true}).click();
  await page.getByText('Chapters 2',{exact:true}).waitFor();
  if(await page.locator('[name=title]').inputValue()!=='Keep my draft')throw Error('Draft lost');
  if(!await page.locator('[type=checkbox]').isChecked())throw Error('Selection lost');
  if(await page.locator('[name=page]').inputValue()!=='2')throw Error('Save page not updated');
  fail=true; await page.getByText('Next',{exact:true}).click();
  await page.getByText('Failed',{exact:true}).waitFor();
  if(await page.locator('[name=page]').inputValue()!=='2')throw Error('Failed fetch changed page');
  console.log('PASS Ajax paging preserves drafts, selections and save page; failure keeps current state.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
