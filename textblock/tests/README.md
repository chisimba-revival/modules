# Textblock and Slate security checks

From the application modules checkout, with PHP 8.5 and mbstring:

```sh
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
