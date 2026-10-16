# CMS Admin permission boundary checkpoint — 16 October 2026

This is an incremental security repair, not a completed CMS revival. Keep CMS and
CMS Admin unregistered on working sites. Boxy and the other legacy workflows
still need migration; broader route authorisation, stale writes and draft recovery
are not certified by this checkpoint.

Run in the modules checkout:

- `php cmsadmin/tests/permission_request_test.php`
- `php cmsadmin/tests/permission_controller_test.php`

Tests use synthetic identities and repository doubles. They exercise persisted
administrator checks, anonymous/member/CMS-author/revoked-admin denial, POST/CSRF,
scalar fields, confirmation, and the actual controller's zero-write rejection.
Accepted edits delegate the mapping ID, selected user ID and boolean correctly.
Warnings are failures. These pass in the local PHP 8.5 runtime.

The sibling framework's `python3 tests/database/run-pdo-contracts.py` exercises
real PDO temporary tables. `cms_permission_checks.php` verifies that editing one
mapping preserves its identity and other records, with bound IDs and scoped
deletion. The full suite passed.

The existing forms now include shared CSRF tokens; editing no longer overwrites
the mapping ID with the user's ID. Current persisted admin membership replaces
cached admin checks in this permission repository. Other permission systems and
legacy editors are outside this bounded change.

An isolated RAM-only clone reached CMS Admin catalogue installation but did not
complete, so installed/browser acceptance is outstanding. Source tracing also
found absent declared dependencies `feed` and `blockalicious`; trace their callers
and migrate to maintained services before attempting a CMS revival. No legacy
dependency was reintroduced or bypassed. The disposable clone was cleaned up.
