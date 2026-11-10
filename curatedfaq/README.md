# Curated FAQ

A new site-level FAQ module. It does not depend on either historical FAQ module.
Visitors can search published questions/answers, filter by shared site category,
and expand answers using native keyboard-accessible disclosures. Lists contain
20 questions per page, ordered numerically and then alphabetically.

Site administrators use `action=manage` to create and edit questions. Answers use
Chisimba's htmlarea/editoradapter and rich-text sanitiser. Status is Draft,
Published or Archived; archiving is reversible. Categories use the shared
classification vocabulary and access-provider boundary. Creating a category and
saving its question is one transaction. Existing shared category names are not
renamed or deleted by this module.

Install through Module Catalogue with the declared dependencies. Fresh installs
create only `tbl_curatedfaq_items`; no request performs schema changes. Transactional
InnoDB storage and the registered primary/source identity constraints are required.
Normal catalogue updates preserve records. The host must restore its site-authored
navigation after catalogue registration if its toolbar registrar resets module links.

All writes check current administrator identity, POST and the canonical CSRF
service. Numeric revisions prevent overwriting a concurrent edit. Client-generated
new record IDs also prevent duplicate creations after uncertain responses. Public
queries and category choices exclude drafts and archives. Preview and editing
responses are private/no-store. Save/token requests return an explicit forbidden
response after session expiry, rather than silently replaying a write after login.

The coordinated framework changes provide a small opt-in `draftform.js` helper,
plus shared filter-row and disclosure skin primitives. The helper stores only
explicitly selected fields in tab-local sessionStorage under a user/record key;
never passwords or CSRF tokens. Restoration retains the original revision.
A failed or uncertain save leaves the draft intact and is never retried silently.
The sign-in link opens a separate tab. Without JavaScript, ordinary form submission
still works and authenticated validation/token failures return submitted values;
tab-local recovery requires JavaScript and available browser storage. Closing a
tab or clearing storage can remove its unsaved local copy.

Both reading and editing have contextual Help. FAQ data/media import is a separate
reviewed operation, not part of installation. Published answers must only refer to
media their audience is authorised to read; this module never widens file access.

## Focused checks

- `php tests/validation_test.php`: input constraints and manager policy.
- `node tests/draftform_test.js /path/to/framework/app/core_modules/htmlelements/resources/draftform.js`:
  tab-local capture/restoration, expiry response, uncertain save, revision conflict,
  token refresh and cleanup after success.
- Preview integration scripts exercise authorised saves, draft privacy, CSRF,
  sanitisation, revision conflict, category creation and transaction rollback,
  archive/restore, public search and pagination. Use isolated fixtures only.

Do not use the preview's generated owner or local paths as production configuration.
