# Legacy module decisions — 14 October 2026

Derek approved pausing Practicals and preserving Triple Store and Mentors for future revival.

- **Practicals:** source renamed to `practicals__PAUSED`, following the existing paused-module convention. Preserve all source and historical tables; no uninstall or data migration is part of this change. The old `practicals` route is intentionally unavailable from this checkout. Registration metadata retains its historical identity for reference, not installation under the paused directory name.
- **Triple Store:** retain `triplestore` and its subject/predicate/object storage. Its relationship model may be useful again. Do not retire it as an unused ExtJS caller.
- **Mentors:** retain `mentors` for eventual revival alongside its Triple Store dependency. It stores mentor/student relationships through dbtriplestore. Revival is not authorised activation in this change.
- **Activity Streamer:** retain the earlier decision to preserve it for possible revival.

Read-only checks on 14 October found Practicals, Triple Store and Mentors unregistered on local and all six production sites. This source change does not deploy to production or change any database. Before a later deployment, recheck registrations and stored/site-specific references before moving deployed Practicals files.

Before enabling any preserved module, review PHP 8 compatibility, dbTable persistence, permissions, CSRF, output escaping, dependencies and native accessible UI, then run local behavioural and browser checks. Triple Store's ExtJS interface must be migrated before its ExtJS dependency can be removed. Preserving its storage model does not require preserving the old UI library. Mentors and Triple Store need a coordinated review; Active Knowledge Maps uses separate storage.

## Triple Store migration follow-up

Triple Store 0.012 now has native administrator-only UI, protected writes and
bound queries; its ExtJS/Ingrid/Editable surfaces are removed. See
`triplestore/README.md` for validation, retired endpoints and remaining revival
limits. It is installed on local for testing only; Mentors remains unregistered.
Activity Streamer is the next preserved ExtJS caller to modernise.

Activity Streamer 0.045 also has a native private browser and protected blocks.
It is installed locally for checks with automatic recording disabled. Event
producers, privacy/retention and any future external publishing still need review
before full revival. See `activitystreamer/README.md`.

## Shared ExtJS retirement

The framework ExtJS libraries and loaders have now been removed from maintained
source/local. Practicals and any other paused module containing ExtJS calls must
be migrated before reactivation. Triple Store and Activity Streamer use their
native replacements. Deploy matching framework/modules releases together; older
production context-content reports still need the coordinated migration.
