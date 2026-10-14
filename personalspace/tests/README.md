# Personal Space editor checks

The editor uses the shared Context browser controls and Blocks ordering service.
Deploy Personal Space 1.013 with Context 2.057 and Blocks 0.538; perform normal
catalogue updates for language registration. There is no schema change.

From the modules checkout:

```sh
php personalspace/tests/editor_test.php
```

From the installed local application root, with PDO MySQL and Personal Space
already registered:

```sh
CHISIMBA_PERSONALSPACE_TEST=1 php packages/personalspace/tests/order_pdo_test.php
CHISIMBA_BLOCK_TEST=1 php core_modules/context/tests/native_block_order_pdo_test.php
```

The database test creates random synthetic owners and placements inside an outer
transaction, injects a second-write failure and rolls everything back. It does not
change a real user's account or layout. Never run this against production.

From the framework checkout:

```sh
php app/core_modules/context/tests/native_block_editor_test.php
node app/core_modules/context/tests/native_block_client_test.js
BLOCK_EDITOR_MODULE=personalspace node app/core_modules/context/tests/native_block_client_test.js
```

Browser verification uses explicitly labelled disposable user-scoped blocks in
an otherwise empty column. Capture the existing placement rows first; verify
preview, add/reload, move, keyboard operation, confirmation/Escape, stale-page
rejection and Help. Remove only fixture rows and compare the original layout.
Do not remove a real user's placements or submit account/password changes.
