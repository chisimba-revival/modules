# Verification — 16 September 2026

Target: **Chisimba / KengaLearn**, not Learn-the-Birds. Work is on isolated
`feature/spoken-assessment` branches in modules and framework. No production deploy.

## Passed

- Both schemas exercised through the corrected table-creation helper with temporary
  tables: fields and unique identity constraints verified, temporary tables removed.
  Regression verifies explicit unique declarations use the constraint API, ordinary
  and legacy primary-index paths remain unchanged, and constraint errors fail closed.

- 36 behavioural checks: course/owner/teacher/admin access, cross-course denial,
  instructor-only activity creation, foreign rubric denial, consent, upload retry
  idempotency, five-attempt cap and orphan cleanup, original transcript retention,
  corrected transcript freezing, human feedback/reflection, immutable activity
  snapshots, stale edits, revoked access, provider-off behaviour and explicit recovery.
- Real local database: saved records survive another PHP process, Unicode round trip,
  rollback, duplicate identity rejection, two competing workers claim one request only, stale claim rejection and
  completed-claim replay rejection. Disposable test rows removed afterwards.
- Actual Rubric service: a course rubric retains its course and criteria and can be
  selected for an activity. Regression covers both mixed-case and lower-case
  database column names without replacing stored ownership with requested scope.
- Synthetic WAV, MP3, M4A, OGG, FLAC and streaming WebM accepted. WebM with no container
  duration header uses packet timestamps. Oversize, empty, disguised text, video and
  overlength audio rejected. No actual learner recordings used.
- Transcription boundary: disabled service, outside/private-root escape, symlink,
  remote URL and unsupported model all rejected before any network request.
- Existing AI observability contracts: 12 checks passed.
- Real Chrome/local Chisimba browser journey with four disposable accounts:
  instructor creates an activity; learner records synthetic microphone audio;
  WebM and WAV uploads succeed; private byte-range audio playback works; corrected
  transcript and reflection survive reload; missing CSRF is rejected; instructor
  feedback appears to the learner; changing the activity does not change the old
  attempt snapshot; both attempts remain available.
- The other student, outsider and anonymous visitor cannot access the recording.
  Administrator policy is covered by the behavioural test, not a live admin session.
- Invalid audio upload keeps the local file/preview/download link available to retry.
- Help opens and closes with Escape; 390px layout contains the native audio control;
  no browser page errors. Desktop and mobile screenshots visually checked.
- PHP syntax checks and JavaScript syntax check passed.

## Not represented as tested

- No paid/live transcription or feedback request was made. Worker behaviour and
  structured feedback were exercised with a stub provider; default-off operation
  was tested in the actual runtime.
- No KengaLearn deployment, existing course-data migration or production worker
  schedule. No Learn-the-Birds source or site changes.
- Safari/iOS microphone capture and real mobile hardware were not exercised.

## Before rollout

Verify installed table/index creation, durable ffprobe availability in the server
image, private storage permissions, request size limits and a supervised synthetic
provider round trip. Review feedback quality against an actual teaching rubric.
Configure the worker only when automatic processing is ready to enable.
