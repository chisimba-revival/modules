# Webinar editing and subject classification — 15 September 2026

## Delivered

Staff can use `?module=webinar&action=manage` to add/edit webinars and manage speaker biographies. The public catalogue exposes Add/Manage only to authorised staff, and detail pages offer Edit. The editor owns its workflow while using native rich text, File Manager, skin cards/forms/icon buttons, contextual Help, CSRF and canonical permissions. It saves through Ajax with a normal-form fallback.

The form covers title, description, public banner/card image and alternative text, speakers, start/end in a selected time zone, booking availability, cancellation, a private joining link, a recording link, shared categories and autocomplete tags. New speakers and categories can be added without leaving an unfinished webinar. Speaker biographies are shared across their linked webinars. Preview opens the saved version in a new tab. Incomplete drafts may omit both dates; publication requires valid dates, description and a published speaker.

Drafts and unpublished biographies cannot be read anonymously. Site Administrators may manage the catalogue; other staff require the canonical `chisimba / webinar / manage` capability. Being an ordinary reader or course author alone does not grant site-wide editing. Category creation separately requires the classification management capability; staff without it can choose existing categories. No real account grants were added by this update.

## Save integrity and email effects

Webinar content and classification commit in one InnoDB transaction. Installation/upgrade ensures unique record and source identities, failing on duplicates rather than removing data. Explicit version checking prevents a stale tab from overwriting newer edits. Creation identifiers prevent duplicate records after an uncertain request. CSRF failures return a fresh token while preserving entries, and the browser warns about unsaved changes, including rich text. A timed-out request does not clear the form.

Editing retains imported source identities and legacy payload fields; the original import hash is retained on the first edit and the current hash changes, protecting intentional edits from a later import. Scheduling changes retain registrations. Saving, publishing or rescheduling sends no announcement or other email. Queued confirmations/reminders are checked against the current date, booking/cancellation state and consent before delivery. Joining links are included only in confirmation/reminder mail, never in public detail or preview HTML. Actual mail delivery was not exercised in this batch; staging's existing mail restrictions remain.

## Historical categories

The read-only WordPress export captured subject taxonomy for all 111 webinars. Six source categories were reconciled into the shared site classification vocabulary and attached to 108 webinars; three had no category in WordPress. No event tags were supplied by the source. Event types such as Archive/Webinar are not imported as subject categories.

`export-wordpress-taxonomy.php` runs read-only in the existing WordPress container. `import-wordpress-taxonomy.php` is guarded to the migration site and reads `/tmp/ltb-event-taxonomy.json`. Run without `--apply` to review, then with it to apply. It refuses conflicting existing associations or semantic term collisions. The repeat run reported zero additions and 222 unchanged category/tag association sets. Importing taxonomy does not replace webinar content.

Public upcoming/archive lists now offer category/tag filtering; detail pages link to the corresponding filtered catalogue. Only terms used by published webinars in that list are exposed.

## Verification

Automated checks passed for editor permission boundaries, sanitisation, URL validation, impossible dates/DST gaps, start/end ordering, incomplete drafts, required speakers, stable identities, duplicate/concurrent saves and classification rollback. Thirty registration/delivery-policy assertions passed, including suppression after rescheduling/cancellation. Catalogue schedule, archive import/conflict/image contracts, rich-text fragment checks, PHP lint and JavaScript syntax checks passed.

Real staging browser tests used newly generated disposable accounts through a localhost-only tunnel. Existing login secrets were not copied, public Basic-auth protection stayed enabled, and source assets used in the browser were matched to deployment. Tests covered new draft save/reload, anonymous draft denial, publishing, public booking details, joining-link exclusion, Ajax speaker/category additions and persistence, tag autocomplete, expired-form retry, conflicting tabs, rescheduling, ordinary signed-in reader denial, unpublishing, saved draft preview, Help/Escape, image preview/reload, File Manager opening, rich-text-only unsaved warnings and mobile field containment. Tests used existing public media; no new upload or real email was sent.

Two owned QA records and their unused QA terms were removed. The temporary accounts are disabled, their passwords invalidated and temporary memberships removed after verification. Inactive audit identities remain. The backup comparison found exactly zero changed/added/removed rows across all 181 pre-existing webinar/speaker records, 28 posts, 120 video records and all audience, registration and outbox data.

## Deployment and recovery

This update is staged only at https://migrate.learnthebirds.com/ . Live WordPress, DNS and KengaLearn are unchanged. Source is on the existing modules/framework feature branches; main is not merged.

The server backup is `/srv/learnthebirds-migrate/backups/editor-20260915/`: database.sql and source.tar precede the editor/taxonomy changes. Additional framework files are preserved in framework-extra-before.tar before deployment. Do not restore a whole database over subsequent editorial work without reconciliation. To undo only taxonomy, reconcile the reviewed source-term links; to undo source, use the matching backup/parent commits together. The post-test dump is private and remains on the server.

The module post-install hook prepares the transactional schema and defines the management capability without granting it. The staging-only `configure-editor.php` invokes the same schema preparation for a guarded repeat check. Install the updated Webinar language/register metadata (0.009) before testing.

Remaining migration work includes the separately outstanding signed-in SimpleBlog imported-copy check, review of imported article media, a future YouTube refresh policy, and the final mail/audience/cutover rehearsal. This webinar update does not authorise production cutover or copying existing credentials.
