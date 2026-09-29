// Run with a KANBAN_FIXTURE_MANAGE=1 fixture to confirm public-link saves stay in place.
const {chromium} = require('playwright');
const fs = require('fs');
const path = require('path');
const assert = require('assert/strict');
(async () => {
    const browser = await chromium.launch({executablePath: process.env.CHROME_BIN || '/usr/bin/google-chrome', headless: true, args: ['--no-sandbox']});
    try {
        const page = await browser.newPage();
        const fixture = fs.readFileSync(process.argv[2], 'utf8');
        const token = 'f'.repeat(64);
        await page.route('http://kanban.test/**', route => {
            const request = route.request();
            const action = new URL(request.url()).searchParams.get('action');
            if (action === 'formtoken') return route.fulfill({contentType:'application/json',body:JSON.stringify({ok:true,csrfToken:'renewed-token'})});
            if (request.method() === 'POST') return route.fulfill({contentType:'application/json',body:JSON.stringify({ok:true,message:'Public view link created.',publicUrl:'http://kanban.test/index.php?module=kanban&action=publicview&token='+token,csrfToken:'fresh-token'})});
            const filename = new URL(request.url()).pathname;
            if (filename === '/formdrafts.js') return route.fulfill({path:path.resolve(__dirname,'../../../framework/app/core_modules/htmlelements/resources/formdrafts.js'),contentType:'application/javascript'});
            if (filename === '/kanban.js' || filename === '/kanban.css') return route.fulfill({path:path.join(__dirname,'../resources',filename.slice(1)),contentType:filename.endsWith('js')?'application/javascript':'text/css'});
            return route.fulfill({contentType:'text/html',body:fixture});
        });
        await page.goto('http://kanban.test/');
        const board = page.locator('.kanban-board').first();
        const other = page.locator('.kanban-board').nth(1);
        await other.locator('[data-board-toggle]').click();
        const form = board.locator('[data-public-link-form]');
        assert.equal(await form.count(), 1);
        await form.locator('button[type=submit]').click();
        await page.waitForFunction(() => document.querySelector('[data-public-link-url-container]').hidden === false);
        assert.equal(page.url(), 'http://kanban.test/');
        assert.equal(await other.locator('.kanban-board__body').isVisible(), false);
        assert.match(await form.locator('[data-public-link-url]').inputValue(), /token=f{64}$/);
        assert.equal(await form.locator('[data-public-link-submit]').textContent(), 'Replace public view link');
        console.log('PASS: public link creation stays in the open board and reveals the URL inline');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
