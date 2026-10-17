# Workgroup regression checks

These tests require an isolated disposable local clone, never a production site.
The fixture refuses any database except `editor_fixture` on `editor-db`.
The HTTP harness is pinned to loopback port 8088 and the named no-jQuery rehearsal
container. It uses synthetic accounts only and deliberately revokes their rights.
The clone must have global jQuery suppressed, outbound networking disabled,
Workgroup updated and Workgroup Admin installed through Module Catalogue.

Prerequisites: the existing editor fixture user/admin credentials and the synthetic
`admissionfree` course. `fixture.php` creates other synthetic accounts through the
canonical provisioning/identity/group services. It creates only named fixture rows
and file bytes, and verifies PDO savepoint rollback. Rerun it to restore fixtures.

```sh
docker exec -i chisimba-no-jquery-rehearsal-20261016-web php < modules/workgroup/tests/fixture.php
docker exec chisimba-no-jquery-rehearsal-20261016-web chown -R www-data:www-data /var/data/filemanager/users /var/www/html/ch/usrfiles/users
CHISIMBA_WORKGROUP_DISPOSABLE=yes python3 modules/workgroup/tests/http_checks.py
```

Coverage: native lists; private original visibility and byte delivery; outsiders,
cross-context IDs and ownership; POST/CSRF; draft preservation and renewed tokens;
stale file/membership edits; idempotent creates; confirmed deletion; invalid members;
current group/course/teacher revocation with existing sessions. The existing File
Manager denial template returns HTTP 200; tests check that it contains denial and
never file bytes. Destroy the clone and its temporary storage after use.

Browser checks additionally cover choosing a personal file in the shared modern
File Manager picker, returning to a draft and saving, contextual Help/full guide,
Escape focus return and a 360px frame. The shared legacy page shell still overflows
horizontally at that width; this batch does not claim to repair the shell.
