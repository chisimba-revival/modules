# Kanban recovery regression checks

Run controller/repository checks with the supported PHP runtime:

```
php kanban/tests/kanban_contract_test.php
php kanban/tests/task_creation_test.php
php kanban/tests/recovery_test.php
php kanban/tests/grant_recovery_test.php
```

The synthetic browser tests use the production templates and scripts. They need
Playwright on NODE_PATH, Chrome (override CHROME_BIN if needed), and a sibling
`framework` checkout containing `htmlelements/resources/formdrafts.js`.

```
php kanban/tests/fixtures/board.php > /tmp/kanban-fixture.html
php kanban/tests/task_creation_test.php success > /tmp/kanban-success.json
node kanban/tests/task_creation_browser_test.cjs /tmp/kanban-fixture.html /tmp/kanban-success.json
node kanban/tests/recovery_browser_test.cjs /tmp/kanban-fixture.html
```

Framework companion tests: `tests/forms/draft_recovery_browser_test.cjs` and
`tests/nativeauth/remembered_login_resumption_test.php`.

`remembered_session_browser_test.cjs` is an **opt-in installed local** test against
https://chisimba.test:8445/ch/. Provision a disposable instructor using canonical
userprovisioningservice/identityservice/groupservice; supply a private JSON file
containing `id`, `username`, `password`, and set CHISIMBA_RECOVERY_TEST=1. It uses
an isolated browser context and must never run with a real person's credentials.
It performs normal remembered login, removes its own PHP session cookies twice
while forms remain open, creates a board/task, rejects an old CSRF token, and
verifies logout revokes the remembered credential. It must not invalidate the
operator's browser session or modify a live server.

Afterwards remove boards/tasks owned by the fixture identity through the Kanban
repositories, revoke its remembered credentials, deactivate it, remove its
instructor membership through GroupService, clear its presence and delete the
private fixture file. Failed runs require the same cleanup. Do not delete real
boards or accounts based on a broad title/username pattern.

Required coordinated versions: Kanban 0.121, htmlelements 0.617, security 3.109,
toolbar 1.806, and the framework request-entry restoration hook. See the framework
`docs/SESSION_RECOVERY_AUDIT.md` for evidence, remaining workflows and limits.
