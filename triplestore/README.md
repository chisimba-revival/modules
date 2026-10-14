# Triple Store native administration

Version 0.012 replaces the old administration surfaces with native Chisimba forms,
shared skin primitives and contextual Help. The subject/predicate/object model,
record IDs, creator/date metadata and Mentors storage calls are preserved.

## Access and behaviour

Only logged-in site administrators may browse, edit, delete or retrieve JSON.
The formerly anonymous `view` endpoint is no longer public. Saves/deletes require
POST and a consumed session CSRF token. Existing rows are locked and compared
against the displayed revision; stale forms cannot overwrite or delete a changed
record. Invalid/expired/conflicting submissions retain the entered values. No
schema migration or historical data rewrite is required.

Listing filters use complete values, with stable date/ID ordering and 30 rows per
page. `view&format=flat` and `view&format=nested` remain available to administrators,
now paged using `page`; `X-Total-Count` and `X-Page` describe the result. These are
administration endpoints, not a public relationship API.

Old ExtJS tree/grid endpoints, inline editing, jQuery Ingrid/Editable helpers and
the broken upload endpoint return 410 rather than mutating data. The old ExtJS
resource had no loader in the current source templates. Its orphaned upload UI
passed an undefined subject to import routines. Reintroducing bulk import needs
its own validated, authorised, transactional workflow; it is not part of this
migration. Internal CSV/XML utility methods remain for future review, with no new
web exposure. Grouped storage reads now return distinct values of the requested
field instead of arbitrary ungrouped columns.

Mentors remains unregistered and is not revived here. Compatibility checks cover
its relationship lookups/additions. Its duplicate guards now use the actual query
result and the complete relationship, and service properties are declared for
PHP 8. Further Mentors authorisation, wiki and UI work is required before revival.

## Validation — 14 October 2026

- PHP 8.5 lint and `tests/native_admin_test.php`: administrator/anonymous policy,
  POST and CSRF guards, retained entries, stale update/delete conflicts, validation,
  metadata preservation, protected flat/nested JSON and retired routes.
- `tests/pdo_integration_test.php`: real PDO CRUD, quoted/injection-shaped values,
  nested subject/predicate API and Iterator compatibility, Mentors lookups and
  sequential duplicate guards, pagination, metadata and rollback. Run from the
  application root with `CHISIMBA_TRIPLESTORE_TEST=1`; catalogue installation is
  required. All fixture writes roll back.
- Local Chrome: add/edit, plain-text escaping, empty filtering, delete confirmation
  and cancellation, contextual Help/Escape/focus return, and 390px mobile layout
  without page overflow. No ExtJS scripts loaded. The checked delete service
  subsequently removed the exact browser fixture. Application PHP logs were clean;
  browser diagnostics contained only Grammarly extension messages.
- Direct browser navigation to the JSON endpoint was blocked by the browser client;
  flat/nested response content and access rules were verified by the controller
  harness instead.

Triple Store was installed through the local catalogue for these checks (previously
unregistered, empty store). The local store is empty again after fixture cleanup.
No production deployment or Mentors activation was performed. Normal catalogue
update is required to register the new language strings on future installations.
