// Run with playwright on NODE_PATH and rendered fixture/JSON paths as arguments.
const {chromium} = require('playwright');
const fs = require('fs');
const path = require('path');
const assert = require('assert/strict');
(async () => {
    const browser = await chromium.launch({executablePath: process.env.CHROME_BIN || '/usr/bin/google-chrome', headless: true, args: ['--no-sandbox']});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const fixture = fs.readFileSync(process.argv[2], 'utf8');
        const saved = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
        let posts = [], fail = false;
        await page.route('http://kanban.test/**', async route => {
            const request = route.request();
            if (request.method() === 'POST') {
                posts.push(new URLSearchParams(request.postData()));
                await new Promise(resolve => setTimeout(resolve, 80));
                return route.fulfill({contentType:'application/json', body: JSON.stringify(fail ? {ok:false,message:'Permission denied.',csrfToken:'retry-token'} : saved)});
            }
            const filename = new URL(request.url()).pathname;
            if (filename === '/kanban.js' || filename === '/kanban.css') {
                return route.fulfill({path: path.join(__dirname, '../resources', filename.slice(1)), contentType:filename.endsWith('js')?'application/javascript':'text/css'});
            }
            return route.fulfill({contentType:'text/html',body:fixture});
        });
        await page.goto('http://kanban.test/');
        const board = page.locator('.kanban-board').first();
        const other = page.locator('.kanban-board').nth(1);
        const existing = board.locator('[data-task-id="' + 'c'.repeat(32) + '"]');
        await other.locator('[data-board-toggle]').click();
        await existing.locator('[data-task-toggle]').click();
        assert.equal(await existing.locator('.kanban-task__body').isVisible(), false);
        const form = board.locator('[data-task-create]');
        await form.locator('[name=title]').fill('Action from meeting');
        await form.locator('button[type=submit]').click();
        // Repeated submit events while saving must not create duplicates.
        await form.evaluate(element => element.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true})));
        await page.waitForFunction(() => document.querySelectorAll('.kanban-task').length === 2);
        assert.equal(posts.length, 1);
        assert.equal(posts[0].get('csrf_token'), 'initial-token');
        assert.equal(await other.locator('.kanban-board__body').isVisible(), false);
        assert.equal(await existing.locator('.kanban-task__body').isVisible(), false);
        assert.equal(await form.locator('[name=title]').inputValue(), '');
        assert.equal(await form.locator('[name=title]').evaluate(element => element === document.activeElement), true);
        assert.equal(await board.locator('[data-board-total]').textContent(), '2 tasks');
        assert.equal(await board.locator('[data-board-progress]').textContent(), '50% complete');
        const newTask = board.locator('.kanban-column[data-status=not_started] .kanban-task');
        await newTask.locator('[data-task-toggle]').click();
        assert.equal(await newTask.locator('.kanban-task__body').isVisible(), false);
        await newTask.locator('[data-task-toggle]').click();
        await newTask.locator('[data-task-move] button').click();
        await page.waitForFunction(() => document.querySelector('.kanban-column[data-status=in_progress] .kanban-task'));
        assert.equal(posts[1].get('csrf_token'), 'fresh-token');
        assert.equal(await page.locator('input[name=csrf_token]').first().inputValue(), 'fresh-token');
        fail = true;
        await form.locator('[name=title]').fill('Keep this draft');
        await form.locator('button[type=submit]').click();
        await page.waitForFunction(() => document.querySelector('[data-task-feedback]').textContent === 'Permission denied.');
        assert.equal(await form.locator('[name=title]').inputValue(), 'Keep this draft');
        assert.equal(await board.locator('.kanban-task').count(), 2);
        await page.reload();
        assert.equal(await other.locator('.kanban-board__body').isVisible(), false);
        assert.equal(await existing.locator('.kanban-task__body').isVisible(), false);
        await existing.locator('[data-task-toggle]').focus();
        await page.keyboard.press('Enter');
        assert.equal(await existing.locator('.kanban-task__body').isVisible(), true);
        assert.deepEqual(errors, []);
        console.log('PASS: inline save, duplicate-submit guard, counts, focus, token rotation, task actions, errors, persisted collapse and keyboard controls');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
