# Independent question banks

MCQ Tests 4.186 and Question Generator 1.0.2 add independent banks. Update both
through Module Catalogue before using the new routes. The update adds three
registered tables and repeatable uniqueness constraints; it does not move or delete
existing tests. No coordinated framework change is required.

A bank belongs to its creator and designated managers, with site-administrator
oversight. Course grants allow teaching staff to read and copy questions; they do
not confer management rights. Grants use the course's persistent record ID as well
as its code. Course deletion does not delete banks, and reusing a course code does
not inherit its grant. Site administrators can make a bank available sitewide.

Open **Question banks** from teaching/administration tools or MCQ Tests. Use
**Add all to bank** on a chapter test, or **Add to question bank** on a reviewed
MCQ set in Question Generator. Select questions from one or several chapter tags
and copy them into a test that is not open to learners. Copies are independent.
Generated evidence, rationale, complete candidate metadata and provenance remain
private to teaching staff. Chapter tags survive a bank → test → bank round trip.

Identical normalised wording and answer/correctness sets are duplicates, regardless
of formatting or answer order. Duplicate imports merge chapter tags and provenance.
The same stem with different answers is reported as a conflict and skipped, never
overwritten. This is deterministic collision detection, not semantic similarity
matching. Bank writes and test copies use transactions and row locks, and bank
identity has a database uniqueness constraint.

The retired unrestricted database-picker routes return HTTP 410; existing picker
entry links now open the permission-checked bank workspace. Existing test questions
remain available through Add all to bank. Only MCQ and true/false questions are
supported. Referenced media URLs are retained; this feature does not transfer file
ownership or duplicate course attachments. Very large-bank paging and fuzzy
similarity detection are not included.

## Repeatable checks

Pure checks (no database or AI):

```sh
php mcqtests/tests/bank_question_test.php
```

Integration fixtures deliberately create users and courses and delete a synthetic
course. **Use a separate disposable installation and database only.** Disable
external delivery/jobs on that installation. The fixture account password is random
and written only to `/tmp/mcq-bank-fixture.json`, mode 0600. Do not commit it.

In the disposable runtime/container, create `.mcq-bank-disposable` in the runtime
root and explicitly set:

```sh
export MCQBANK_LOCAL_TEST=1
export MCQBANK_TEST_RUNTIME=/path/to/disposable/runtime
php packages/mcqtests/tests/fixtures/bank_setup.php
php packages/mcqtests/tests/fixtures/bank_seed.php
php packages/mcqtests/tests/bank_integration_test.php
```

Setup uses the normal module installer and canonical user/group services. Seed
creates a real chapter, tests and reviewed synthetic generator set. The integration
check covers automatic chapter tags, metadata, duplicates and conflicts, repeat
copying, total marks, stale revisions, permissions, forged IDs, open-test denial,
student privacy, transactional rollback, real course deletion, reused course codes,
sitewide access, independent managers and revocation without loss of test copies.
Fixtures are retained for browser testing; afterwards discard the entire disposable
installation/database, including the fixture JSON and generated accounts.

## Verification on 27 September 2026

Passed on isolated PHP 8.5.4 / MariaDB 10.11.18:

- Additive module installation and repeated post-install constraint checks.
- The integration suite above and pure question-identity checks.
- All existing MCQ contract tests; generator workshop, exam, counts, sections,
  more-questions and short-answer suites; legacy-generator route checks.
- PHP syntax, JavaScript syntax and diff whitespace checks.
- Browser: independent bank creation, sharing saved/reloaded, reviewed generator
  selection to bank, evidence/rationale display, duplicate Add all to bank,
  selection retention across chapter filtering, bank → test copy with marks and
  reload, receiving-teacher controls, contextual Help and Escape focus restoration,
  narrow-screen layout.

The installed legacy runtime emits existing PHP deprecations from contextcontent,
workgroup, search and dynamic blocks during fixture creation/deletion. These were
observed, not hidden by the tests. No paid AI calls or production updates were made.
Concurrent load and course-file retention were not tested.

## Knowledge Map chapter banks

MCQ Tests 4.195 and Knowledge Map 1.01 add generated chapter bank links and
immutable attempt papers through two registered tables. On a Quiz node, choose
10–100 bank questions, a quiz size up to that count, and either a fixed random
draw or a new random draw for each attempt (default: 30 / 10 / per attempt).
Generation stays queued and inactive for review. Existing jobs and tests retain
their original behaviour; existing bank copies remain independent.

The fixed mode copies one draw; per-attempt mode copies the pool and saves the
selected question text, answers, key and ordering with the owned result ID.
Resume and grading read that paper without redrawing. Questions carry one mark
each; aggregate marks editing is unavailable for these pools. Author edits to
bank items do not silently change test copies or saved attempts. Review links
expose the full bank and explain the test's selection mode.

`php mcqtests/tests/quiz_pool_controller_test.php` checks the real submission
boundary against forged question/answer IDs and missing/foreign papers.
`quiz_pool_engine_test.php` is included by the isolated full-engine rehearsal,
with a deterministic provider, generated fixture maps and canonical identities;
it covers bank linkage, both modes, durable resume, pagination, snapshot grading,
unselected questions, metadata and transactional publication rollback. It refuses
non-clone databases. No external AI request is needed.
