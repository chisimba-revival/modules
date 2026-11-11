# Sights & Sounds browser practice

Version 0.001 provides a course-scoped question bank, image/MP3 upload and preview,
accepted identification aliases, immutable published activities, random practice
runs, immediate feedback and saved checked answers. Practice is ungraded and
unlimited. Members can participate; current course lecturers and site admins can
author. Every media request checks membership and question/activity access.

Install through Module Catalogue with File Manager 1.117 or later. Enable the
context plugin through the normal course tools. Enter a course before opening
`?module=sightsandsounds`. Add questions, then publish an activity using a snapshot
of the bank. Published records are read-only in this initial increment.

Supported uploads: JPEG/PNG/WebP/MP3, maximum 10 MiB; images at most 8192 pixels per
axis and 24 megapixels. Files receive opaque names and use private File Manager
storage. Existing embedded media metadata is retained; authors should remove
answer-bearing metadata before uploading. Check licence/credit requirements.

Answers match explicitly entered aliases, ignoring case, repeated whitespace and
curly versus straight apostrophes. There is no fuzzy matching or inferred species.
Checked answers persist; unchecked typing is protected by a navigation warning
but is not saved. A new set may repeat questions or species. Reloading/resuming
keeps the current set. Native audio supports replay; original images can be opened
through their protected download link.

This increment does not include question editing/deletion, species taxonomy,
quota blueprints, graded modes, Gradebook contribution, mobile integration or
offline downloads. It must not be used for summative assessment.

## Checks

- `php sightsandsounds/tests/rules_test.php`
- `php sightsandsounds/tests/access_test.php`
- Framework: `php app/core_modules/filemanager/tests/module_media_test.php`
- PHP syntax and `git diff --check` in both repositories.
- `SS_DATABASE_TEST=1 php .../tests/database_test.php` inside the local PHP 8.5
  runtime only, before module installation. Refuses existing module tables,
  creates its own three tables and drops only tables it created. Checks actual
  PDO persistence, isolation, random-set stability, revision conflicts and
  preservation of first-answer accuracy through corrections.

Browser acceptance: author upload/preview for both kinds; publish a mixed set;
member start, wrong answer, correction and resume; outsider and anonymous media
rejection; audio range requests; narrow layout; keyboard and contextual Help.

## Local acceptance — 11 November 2026

Installed through Module Catalogue on the existing PHP 8.5.12 development site;
repeat registration preserved records. A browser author uploaded PNG and MP3
fixtures and published a ten-question mixed practice activity. A student completed
it to 100%, corrected a wrong answer, matched an alternative name and resumed the
same persisted run. Native audio playback and protected range delivery worked.
Student authoring and outsider media reads were denied; anonymous reads required
sign-in. Quick/full Help and Escape focus return passed. Media controls fit a
390px viewport; the existing global navigation shell has a small unrelated
horizontal overflow. Fixture course, content and media were removed; test accounts
were deactivated through the canonical user service.

## Activity instances, completion and mobile practice (0.002)

The bank belongs to a context. Each published activity captures the bank's current
question IDs and its own title, instructions and random question count. Multiple
activities reuse those questions without sharing runs or results. Add each activity
through Gradebook's assessment-plan provider picker; leave practice weighting at
zero. The provider exposes the latest **completed** practice percentage.

The web player checks answers incrementally and Finish practice creates an immutable
receipt. The same services back the authenticated REST catalogue, package, media and
submit endpoints. Packages explicitly identify `mode: practice`; accepted answers
are included for offline feedback and must never be reused for secure graded exams.
Every media response rechecks persisted membership and run ownership. Downloads
are limited to 50 questions, 10 MiB per file and 100 MiB per package.

The Android app commits a download only after all media hashes have verified.
Answer drafts and checked answers persist in IndexedDB, partitioned by server and
account. Finish freezes the submitted checked answers. Send results retries the
same payload; the server recomputes the percentage and returns the same receipt.
A changed payload, stale revision or concurrent browser edit produces a conflict,
never a silent overwrite. There is no automatic background send.

Upgrade through Module Catalogue; the additional `tbl_ss_completions` table leaves
existing items, activities and runs intact. Requires coordinated framework REST and
filemanager 1.118 changes. Test `completion_database_test.php` only with
`SS_DATABASE_TEST=1` on the local `chisimba.test` runtime; it deletes only its random
synthetic scope. The older `database_test.php` is for an empty disposable database.

## Formative assignments (0.003)

See [the assignment lifecycle design](docs/assignment-lifecycle.md). Publishing now
sets practice/formative mode, retries (zero by default), and optional opening/due
dates in the site timezone. Existing activities remain practice. Rules are immutable
in `tbl_ss_assignment_rules`; individual extra attempts are audited in
`tbl_ss_attempt_grants` with actor, reason, time and deduplication ID.

Formative downloads reserve an attempt; interrupted downloads and fresh requests
resume the same unfinished run. Saves reveal no correctness. Protocol 2 packages
exclude answer keys, feedback and correctness fields. The server marks the final
submission; latest completed percentage feeds Gradebook. Overall scores are shown
after submission; formative answer keys are not released in this increment.

No new starts occur outside assignment dates. Existing attempts may submit late,
flagged by original server receipt time without automatic penalty. Current course
lecturers and site administrators can view results and grant an individual extra
attempt with a reason. Members access only their own attempts/results. Custom role
delegation and summative assessment remain deferred.
