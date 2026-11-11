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
