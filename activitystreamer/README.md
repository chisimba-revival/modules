# Activity Streamer native browser

Version 0.045 replaces ExtJS grids/pop-ups with native shared-skin tables, filters,
sorting, paging and contextual Help. All six historical block names remain usable;
unauthorised blocks return no activity content. The normal module page replaces
the old demonstration Atom feed containing fictional people and tasks.

## Access and data

- Site activity is the complete event log, visible only to logged-in administrators.
- Current-context activity and recent content pages are visible only to logged-in
  administrators or the current context's instructors. Students, outsiders,
  revoked instructors and anonymous visitors cannot read these reports.
- Counts, JSON routes and blocks use the same policy before querying. Context is
  taken from the active Chisimba context, never a submitted context identifier.
- Event descriptions render as escaped plain text. Historical event URLs are not
  rendered as arbitrary links. Recent-page links use the canonical contextcontent
  route and its normal access controls. Only original page versions are listed.
- All reads use bound dbTable queries. Stable date/ID ordering supports paging;
  the native page uses 30 rows. Old JSON route names remain protected compatibility
  reads, with `activities` and `totalCount`. Their `start` is interpreted in whole
  pages of the bounded `limit` (1–100).

## Recording and future revival

Installation does **not** start collecting activity: the new `ENABLE_RECORDING`
configuration defaults to false. Existing logs and current content pages remain
browsable. Review event producers, retention and privacy before deliberately
turning recording on in module configuration. This change does not enable it on
any production site.

`activityops::postmade` retains the event-observer API but only records locally
when opted in. Obsolete automatic PubSubHubbub publishing, demonstration feed
creation and unused Twitter configuration are retired. `createFeeds` is a no-op
compatibility method; there are no external callers in the source audit. The old
standalone Activity Streams encoder classes are preserved for future review,
with no external hub dependency or public feed route. They are not a reviewed
publishing API.

The historical `createdby` integer column cannot hold current string user IDs.
Fresh installation uses text(50); a guarded catalogue preinstall hook widens old
columns while preserving their values and skips already adequate text columns.
Previously truncated author identities cannot be reconstructed by this migration.
No activity records are deleted or rewritten. Raw SQL filters in the unused old
`getActivities` helper are rejected; use the scoped browser instead.

## Local validation — 14 October 2026

- PHP 8.5 lint; `tests/native_browser_test.php`: anonymous/student/instructor/
  revoked/admin policy, context scoping, bound search, paging, escaped HTML, all
  six blocks, protected controller/JSON routes, recording opt-in and no vendor JS.
- `tests/schema_upgrade_test.php`: preflighted author widening, fresh-table and
  repeated-upgrade guards. Local catalogue installation and repeat update passed.
- `tests/pdo_integration_test.php`: notification inserts with string authors,
  cross-context exclusion, quoted/injection-shaped searches, stable pagination,
  empty course-page results and rollback. Run from the local application root
  with `CHISIMBA_ACTIVITY_TEST=1`; normal catalogue installation is required.
  Every event fixture is rolled back; the test also verifies recording stays off.
- Local Chrome: empty site log, four existing course pages, title ordering,
  empty search/reset, Help/Escape/focus return and 390px layout without page
  overflow. No ExtJS requests or browser console errors/warnings were observed.
  Non-admin roles and retained block combinations are covered by the policy
  harness, not separate signed-in browser sessions.

Installed on local for verification, recording false, no production deployment.
The separate contextcontent learner-progress log is unchanged. ExtJS shared
wrappers/runtimes still need a final caller audit before deletion.
