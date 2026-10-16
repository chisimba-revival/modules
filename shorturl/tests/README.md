# ShortURL security checkpoint

Run `php shorturl/tests/security_test.php` and
`php shorturl/tests/controller_test.php` from the modules checkout. These use
synthetic identities/tokens and a repository double; no site data is modified.
The actual controller probe verifies anonymous/member/revoked-admin/GET/expired
CSRF requests perform no deletion and a valid administrator POST delegates once.
Policy checks cover scalar IDs, explicit actions and URL/key field validation.
Warnings are failures. Both pass on the local PHP 8.5 container.

This is not a completed revival. Keep ShortURL unregistered until its native UI,
draft recovery, escaped output, prepared repository queries, checked transactional
writes, stale edits, contextual Help and installed/browser tests are complete.
Boxy remains; the redundant jqGrid setup and duplicate delete callback are gone.
The public redirect action remains allowed and no dynamic redirect functionality
has been introduced. The old dynamic implementation was already commented out.
