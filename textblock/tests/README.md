# Textblock and Slate security checks

From the application modules checkout, with PHP 8.5 and mbstring:

```sh
php slate/tests/migration_test.php
php textblock/tests/editor_security_test.php
php textblock/tests/controller_security_test.php
php textblock/tests/help_test.php textblock
php textblock/tests/help_test.php slate
node textblock/tests/editor_scripts_test.js
```

These use synthetic identities, tokens and repositories. Controller probes run
in subprocesses so the real controllers' terminating responses can be checked.
They do not boot an installation, grant access or modify site data.

Real PDO persistence checks run through the sibling framework's isolated runner:

```sh
python3 tests/database/run-pdo-contracts.py
```

Run that command from the framework checkout with Docker access. It creates its
own disposable database; it never uses the local or production site database.
The legacy-editor fixtures cover bound reads, HTML sanitisation, duplicate slots
and page numbers, fixed page identity, scoped deletion, nearest-neighbour block
moves and rollback after a failed second write.

The 15 October browser checks used the real form renderers with synthetic
responses: native required validation, rejected-token and network-failure draft
retention, and Slate's successful save transition. They are not a substitute for
full catalogue installation and end-to-end authenticated browser acceptance.
Neither Textblock nor Slate was enabled during these checks.

The later installed rehearsal added real authenticated Chrome and HTTP checks in
an isolated local clone. Both editors now protect stale saves/deletes with row
revisions, and Slate uses stable persisted page keys and the shared native block
editor. The PDO suite also covers shared ordering, stale layouts, rollback,
repeatable known-URL migration, unknown-hash preservation and deleted pages.
HTTP tests confirmed that removing persisted administrator membership defeats an
already-open admin session and rejected requests leave editor data unchanged.
See the sibling framework's
`docs/dependencies/validate-editor-revival-20261015.md` for the full scope,
migration limitations and remaining activation/release work.

## Native list controls (17 October)

Textblock and Slate now share UI's native `rowdelete` renderer/client. From the
framework checkout run `node tests/ui/rowdelete_test.js` and
`php tests/ui/rowdelete_render_test.php` for cancellation, pending duplicate
prevention, POST/CSRF/revision evidence, response validation, uncertain failures,
same-origin enforcement, focus and output escaping. Textblock previews use native
`details`/`summary`. Both old module list scripts have been removed.

The isolated installed no-jQuery rehearsal also exercised actual delete endpoints
for anonymous/member/revoked-admin, GET, invalid token, stale revision, valid and
repeat submissions, verifying denied writes preserve data. Browser checks covered
keyboard disclosure, cancellation, successful removal, stale recovery and Help.
See `framework/docs/dependencies/native-textblock-slate-lists-20261017.md` for
scope and layout limitations. The modules remain dormant on the real local site.
