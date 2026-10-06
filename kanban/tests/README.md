# Kanban recovery regression checks

The board fixture now loads module CSS BEFORE shared skin CSS, matching production.
With KANBAN_FIXTURE_HELP it also loads real UI/canvas styles, icons, contextual
Help and Create board controls. Do not reverse this order: it previously hid a
compact-field width constraint and toolbar wrapping failure. Whole-header tests
cover desktop/narrow widths, one desktop action row, full guide/Escape, and
creation draft retention, in addition to individual control checks.

`node kanban/tests/boards_focus_browser_test.cjs` tests 0.141 all-board focus
using the same isolated browser prerequisites below: desktop/mobile dimensions,
preserved collapse states/drafts, inert surroundings, Escape/button exit and
focus return, plus the existing single-project focus journey. Contextual Help
copy is registered; installed-site Help rendering remains an integration check.

Run controller/repository checks with the supported PHP runtime:

```
php kanban/tests/kanban_contract_test.php
php kanban/tests/task_creation_test.php
php kanban/tests/recovery_test.php
php kanban/tests/grant_recovery_test.php
php kanban/tests/subtask_edit_test.php
```

The synthetic browser tests use the production templates and scripts. They need
Playwright on NODE_PATH, Chrome (override CHROME_BIN if needed), and a sibling
`framework` checkout containing `htmlelements/resources/formdrafts.js`.

```
php kanban/tests/fixtures/board.php > /tmp/kanban-fixture.html
php kanban/tests/task_creation_test.php success > /tmp/kanban-success.json
node kanban/tests/task_creation_browser_test.cjs /tmp/kanban-fixture.html /tmp/kanban-success.json
node kanban/tests/recovery_browser_test.cjs /tmp/kanban-fixture.html
KANBAN_FIXTURE_MANAGE=1 php kanban/tests/fixtures/board.php > /tmp/kanban-manage-fixture.html
node kanban/tests/public_link_browser_test.cjs /tmp/kanban-manage-fixture.html
```

Framework companion tests: `tests/forms/draft_recovery_browser_test.cjs` and
`tests/nativeauth/remembered_login_resumption_test.php`.

For Kanban 0.140, `node kanban/tests/subtask_edit_browser_test.cjs` renders its
own fixture and loads the real shared skin. It checks full-width editors and
spacing at desktop/mobile widths, inline subtask saves, keyboard opening,
completion preservation, safe text rendering, duplicate submission prevention,
conflicts, network failures, independent draft recovery, Cancel/focus, newly
created subtask editing, and absence of editors in view-only output. Optional
`KANBAN_SCREENSHOT=/tmp/kanban-edit.png` captures the narrow task editor.
The PHP test exercises the controller and repository with test doubles; these
checks do not replace an installed-site/database smoke test or prove live
database locking. No live sites or application data are modified.

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
