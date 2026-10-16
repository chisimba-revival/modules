# ShortURL native editor checks

Run from the modules checkout:

- `php shorturl/tests/security_test.php`
- `php shorturl/tests/controller_test.php`
- `node shorturl/tests/editor_test.js`

Policy/controller checks cover persisted administrator access, revoked access,
POST/CSRF, scalar input, explicit routes and denied requests performing no writes.
Browser-script fixtures cover cancellation, retained drafts after rejection,
login/non-JSON/network uncertainty and preventing accidental repeated writes.

Run `python3 tests/database/run-pdo-contracts.py` in the sibling framework checkout
for disposable real-PDO checks: bound case-sensitive reads, duplicate paths,
stale saves/deletes, unsafe destinations, failed deletion rollback, and preserved
dynamic rows. No working site data is modified by these checks.

On 16 October 2026 a disposable isolated local clone passed fresh catalogue
installation and repeated update, real HTTP create/edit/delete and stale draft
recovery, and anonymous/member/revoked-admin/method/token denial checks. Chrome
verified add/edit and stale draft recovery plus compact/full Help and Escape focus
return. After Derek dismissed the native confirmation, a fresh page confirmed the mapping
was unchanged. Phone-width form/actions and clean browser diagnostics passed.
The shared page shell retains the previously noted small horizontal overflow.

The native editor replaces ShortURL's Boxy forms and jqGrid callback; the shared
libraries remain for CMS/CMS Admin and Forms. Existing dynamic mappings/key rows
remain read-only, never executed. Keep ShortURL unregistered on working sites
until the CMS revival and explicit deployment review. No production deployment
is included in this change.
