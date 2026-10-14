# Calendar native viewing migration — 14 October 2026

Calendar 1.535 removes its jquerycore dependency and the jquery.livequery loader.
Category filters now use native change events and the checkbox's actual checked
state. Both grid entries and detailed-list entries carry their category, so both
views filter consistently. Filters are scoped to the main Calendar view and do
not hide another calendar block elsewhere on the page.

Small-calendar popups are replaced with native details/summary disclosures. They
work without JavaScript, support Tab/Enter/Space, avoid colliding dialog IDs when
several calendars appear on one page, and use escaped plain text for event titles
and summaries. Main-list rich descriptions use the shared HTML sanitizer; external
links are restricted to HTTP(S). No third-party dialog library is required.

Repeated setup clears both source and rendered event arrays. Dates are normalised
before queries, including zero-padded months and malformed input. Category counts
are declared properties. Contextual Help covers filters, keyboard operation and
month navigation. The existing Other classes source is empty; this change does
not implement cross-course aggregation or alter event visibility rules.

## Validation

- Native PHP rendering regression: safe text, disclosure markup, time ordering,
  grid categories and empty/repeated resets.
- Native JavaScript fixture: initial checked state, both render surfaces, repeated
  changes, category isolation and missing optional checkboxes.
- Local PHP 8.5/PDO fixture: personal, current-course and site selection; exclusion
  of another user's personal fixture; repeat render, date normalisation, rollback.
- Chrome: populated and empty months, navigation, main-grid/list checkbox filters,
  side-calendar details with Enter and Space, quick/full Help and Escape, desktop
  and 390-pixel viewport, no console errors. No PHP warning/deprecation/fatal in
  the final test window. The four synthetic browser events, disposable course and
  block placement were removed; the original browser course was restored.
- Syntax and whitespace checks; normal local Module Catalogue registration/hooks.

No event editor, password, attachment upload or existing event was modified in
browser tests. No production deployment. Global skin jQuery remains for other
modules.

## Required follow-up before Calendar deployment

This is a viewing migration, not a certification of the legacy write paths.
Source tracing identified a separate security/reliability task in controller.php:

- Add/update/delete and attachment mutations lack an explicit shared POST/CSRF
  boundary. Preserve non-secret drafts when introducing rejection/recovery.
- Edit permission checks must return strict booleans and stop execution on denial;
  update must authorise the persisted record before changing it.
- Updating currently deletes a batch before rebuilding it. Replace this with a
  checked transaction, retaining identity/attachments and handling failure safely.
- Attachment deletion must occur only after authorised success. Review upload,
  temporary IDs, download ownership/path confinement and cleanup together.
- Test personal/current-course/site and revoked roles, multiday edits, attachment
  bytes, CSRF rejection, concurrent changes and rollback with disposable fixtures.

These issues should take priority over the next jQuery consumer. Other callers of
calendarbase and the legacy attachment model need tracing before changing shared
storage contracts. Do not treat the successful viewing tests as write-path tests.
